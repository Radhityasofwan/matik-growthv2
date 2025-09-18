<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WATemplate;
use App\Models\WahaSender;
use App\Services\WahaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
        // Pilih kolom yang benar-benar ada agar aman di berbagai versi skema
        $cols = array_values(array_filter([
            'id',
            Schema::hasColumn('wa_templates', 'name') ? 'name' : null,
            Schema::hasColumn('wa_templates', 'description') ? 'description' : null,
        ]));

        $q = WATemplate::query();
        if (Schema::hasColumn('wa_templates', 'name')) {
            $q->orderBy('name');
        } else {
            $q->orderBy('id');
        }

        $templates = $q->get($cols ?: ['*']);

        return view('whatsapp.broadcast.create', compact('templates'));
    }

    /** Submit broadcast */
    public function store(Request $request)
    {
        $data = $request->validate([
            'sender_id'    => ['required','exists:waha_senders,id'],
            'mode'         => ['required','in:custom,template'],
            'message'      => ['required_if:mode,custom','nullable','string'],
            // ✅ perbaikan: nama tabel benar 'wa_templates'
            'template_id'  => ['required_if:mode,template','nullable','exists:wa_templates,id'],
            'params_json'  => ['nullable','string'], // JSON object untuk template params
            'recipients'   => ['required','string'], // satu nomor per baris
        ], [
            'sender_id.required'      => 'Pilih nomor pengirim.',
            'recipients.required'     => 'Isi daftar penerima.',
            'message.required_if'     => 'Tulis pesan saat mode Custom.',
            'template_id.required_if' => 'Pilih template saat mode Template.',
        ]);

        // Validasi sender aktif
        $sender = WahaSender::where('id', $data['sender_id'])->where('is_active', true)->first();
        if (!$sender) {
            return back()->withErrors(['sender_id' => 'Sender tidak ditemukan atau nonaktif.'])->withInput();
        }

        // Parse recipients (satu per baris). Format didukung:
        // 1) 628xxxxx
        // 2) Nama, 628xxxxx
        // 3) 628xxxxx | Nama
        $rows = preg_split('/\r\n|\r|\n/', trim($data['recipients'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
        $recipients = [];
        foreach ($rows as $row) {
            $name = null; $phone = null;
            $line = trim($row);

            if (str_contains($line, ',')) {
                [$a,$b] = array_map('trim', explode(',', $line, 2));
                $digitsA = preg_replace('/\D+/', '', $a);
                $digitsB = preg_replace('/\D+/', '', $b);
                if (strlen($digitsA) >= 7) { $phone = $digitsA; $name = $b; }
                elseif (strlen($digitsB) >= 7) { $phone = $digitsB; $name = $a; }
            } elseif (str_contains($line, '|')) {
                [$a,$b] = array_map('trim', explode('|', $line, 2));
                $digitsA = preg_replace('/\D+/', '', $a);
                $digitsB = preg_replace('/\D+/', '', $b);
                if (strlen($digitsA) >= 7) { $phone = $digitsA; $name = $b; }
                elseif (strlen($digitsB) >= 7) { $phone = $digitsB; $name = $a; }
            } else {
                $digits = preg_replace('/\D+/', '', $line);
                if (strlen($digits) >= 7) $phone = $digits;
            }

            if ($phone) {
                $recipients[] = [
                    'phone' => $phone,
                    'name'  => $name ?: Str::substr($phone, -4),
                ];
            }
        }

        if (empty($recipients)) {
            return back()->withErrors(['recipients' => 'Tidak ada nomor valid yang terdeteksi.'])->withInput();
        }

        // Kirim
        $mode = $data['mode'];
        $sent = 0; $failed = 0;

        if ($mode === 'template') {
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
                    Log::error('Broadcast template failed', ['e' => $e->getMessage(), 'to' => $r['phone']]);
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
                    Log::error('Broadcast custom failed', ['e' => $e->getMessage(), 'to' => $r['phone']]);
                    $failed++;
                }
            }
        }

        $summary = "Broadcast selesai. Terkirim: {$sent}, Gagal: {$failed}.";
        return redirect()->route('whatsapp.broadcast.create')->with('success', $summary);
    }
}
