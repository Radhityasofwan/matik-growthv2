<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WATemplate;
use App\Models\WahaSender;
use App\Services\WahaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class BroadcastController extends Controller
{
    protected WahaService $waha;

    public function __construct(WahaService $waha)
    {
        $this->waha = $waha;
    }

    /** Form broadcast */
    public function create()
    {
        // Kolom aman untuk templates
        $tplCols = array_values(array_filter([
            'id',
            Schema::hasColumn('wa_templates', 'name') ? 'name' : null,
        ]));

        $tq = WATemplate::query();
        $tq->when(
            Schema::hasColumn('wa_templates', 'name'),
            fn ($qq) => $qq->orderBy('name'),
            fn ($qq) => $qq->orderBy('id')
        );
        $templates = $tq->get($tplCols ?: ['*']);

        // Ambil daftar sender aktif (alias session_name -> session)
        $senderCols = ['id', 'is_active', 'is_default'];
        if (Schema::hasColumn('waha_senders', 'session_name')) {
            $senderCols[] = DB::raw('session_name as session');
        } elseif (Schema::hasColumn('waha_senders', 'session')) {
            $senderCols[] = 'session';
        }
        if (Schema::hasColumn('waha_senders', 'name'))        $senderCols[] = 'name';
        if (Schema::hasColumn('waha_senders', 'number'))      $senderCols[] = 'number';
        if (Schema::hasColumn('waha_senders', 'display_name'))$senderCols[] = 'display_name';

        $sq = WahaSender::query()
            ->when(Schema::hasColumn('waha_senders', 'is_active'), fn($q) => $q->where('is_active', true))
            ->orderByDesc('is_default');

        if (Schema::hasColumn('waha_senders', 'name')) $sq->orderBy('name'); else $sq->orderBy('id');

        $senders = $sq->get($senderCols);

        // Pastikan view
        $viewName = 'whatsapp.broadcast.create';
        if (!View::exists($viewName)) {
            $viewName = View::exists('whatsapp.broadcast') ? 'whatsapp.broadcast'
                : (View::exists('broadcast') ? 'broadcast' : $viewName);
        }

        return view($viewName, compact('templates', 'senders'));
    }

    /** Submit broadcast */
    public function store(Request $request)
    {
        $data = $request->validate([
            'sender_id'    => ['required','exists:waha_senders,id'],
            'mode'         => ['required','in:custom,template'],
            'message'      => ['required_if:mode,custom','nullable','string'],
            'template_id'  => ['required_if:mode,template','nullable','exists:wa_templates,id'],
            'params_json'  => ['nullable','string'],
            'recipients'   => ['required','string'],
        ], [
            'sender_id.required'      => 'Pilih nomor pengirim.',
            'recipients.required'     => 'Isi daftar penerima.',
            'message.required_if'     => 'Tulis pesan saat mode Custom.',
            'template_id.required_if' => 'Pilih template saat mode Template.',
        ]);

        $sender = WahaSender::where('id', $data['sender_id'])->where('is_active', true)->first();
        if (!$sender) {
            return back()->withErrors(['sender_id' => 'Sender tidak ditemukan atau nonaktif.'])->withInput();
        }

        // Parse penerima: dukung "nama, nomor" | "nomor | nama" | "nomor"
        $rows = preg_split('/\r\n|\r|\n/', trim($data['recipients'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
        $recipients = [];
        foreach ($rows as $row) {
            $name = null; $phone = null; $line = trim($row);
            if (str_contains($line, ',')) {
                [$a,$b] = array_map('trim', explode(',', $line, 2));
                $da = preg_replace('/\D+/', '', $a); $db = preg_replace('/\D+/', '', $b);
                if (strlen($da) >= 7) { $phone = $da; $name = $b; }
                elseif (strlen($db) >= 7) { $phone = $db; $name = $a; }
            } elseif (str_contains($line, '|')) {
                [$a,$b] = array_map('trim', explode('|', $line, 2));
                $da = preg_replace('/\D+/', '', $a); $db = preg_replace('/\D+/', '', $b);
                if (strlen($da) >= 7) { $phone = $da; $name = $b; }
                elseif (strlen($db) >= 7) { $phone = $db; $name = $a; }
            } else {
                $d = preg_replace('/\D+/', '', $line);
                if (strlen($d) >= 7) $phone = $d;
            }
            if ($phone) $recipients[] = ['phone'=>$phone,'name'=>$name ?: Str::substr($phone, -4)];
        }

        if (!$recipients) {
            return back()->withErrors(['recipients' => 'Tidak ada nomor valid yang terdeteksi.'])->withInput();
        }

        $sent = 0; $failed = 0;

        if ($data['mode'] === 'template') {
            $template = WATemplate::find($data['template_id']);
            if (!$template) {
                return back()->withErrors(['template_id' => 'Template tidak ditemukan.'])->withInput();
            }
            $params = [];
            if (!empty($data['params_json'])) {
                $params = json_decode($data['params_json'], true);
                if (!is_array($params)) {
                    return back()->withErrors(['params_json' => 'Params harus JSON object yang valid.'])->withInput();
                }
            }
            foreach ($recipients as $r) {
                try {
                    $resp = $this->waha->sendTemplate($sender, $r['phone'], $template->name, $params);
                    $resp ? $sent++ : $failed++;
                } catch (\Throwable $e) {
                    Log::error('Broadcast template failed', ['e'=>$e->getMessage(),'to'=>$r['phone']]);
                    $failed++;
                }
            }
        } else {
            $message = (string) $data['message'];
            foreach ($recipients as $r) {
                $body = str_replace(['{{name}}','{{nama}}','{{nama_pelanggan}}'], $r['name'], $message);
                try {
                    $resp = $this->waha->sendMessage($sender, $r['phone'], $body);
                    $resp ? $sent++ : $failed++;
                } catch (\Throwable $e) {
                    Log::error('Broadcast custom failed', ['e'=>$e->getMessage(),'to'=>$r['phone']]);
                    $failed++;
                }
            }
        }

        return redirect()->route('whatsapp.broadcast.create')
            ->with('success', "Broadcast selesai. Terkirim: {$sent}, Gagal: {$failed}.");
    }
}
