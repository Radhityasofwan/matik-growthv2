<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WATemplate;
use App\Models\WahaSender;
use App\Notifications\GenericDbNotification;
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
        // Templates (kolom aman)
        $tplCols = array_values(array_filter([
            'id',
            Schema::hasColumn('wa_templates', 'name') ? 'name' : null,
            Schema::hasColumn('wa_templates', 'is_active') ? 'is_active' : null,
        ]));
        $tq = WATemplate::query();
        $tq->when(
            Schema::hasColumn('wa_templates', 'name'),
            fn ($qq) => $qq->orderBy('name'),
            fn ($qq) => $qq->orderBy('id')
        );
        $templates = $tq->get($tplCols ?: ['*']);

        // Senders aktif (alias session_name -> session)
        $senderCols = ['id', 'is_active', 'is_default'];
        if (Schema::hasColumn('waha_senders', 'session_name')) $senderCols[] = DB::raw('session_name as session');
        elseif (Schema::hasColumn('waha_senders', 'session'))  $senderCols[] = 'session';
        if (Schema::hasColumn('waha_senders', 'name'))         $senderCols[] = 'name';
        if (Schema::hasColumn('waha_senders', 'number'))       $senderCols[] = 'number';
        if (Schema::hasColumn('waha_senders', 'display_name')) $senderCols[] = 'display_name';

        $sq = WahaSender::query()
            ->when(Schema::hasColumn('waha_senders', 'is_active'), fn($q) => $q->where('is_active', true))
            ->orderByDesc('is_default');

        if (Schema::hasColumn('waha_senders', 'name')) $sq->orderBy('name'); else $sq->orderBy('id');

        $senders = $sq->get($senderCols);

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
        $actor = $request->user();

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

        /** ===== Pre-flight: pastikan sesi benar-benar siap sebelum kirim ===== */
        try {
            $st = $this->waha->sessionStatus($sender);
            $doneStates = ['CONNECTED','READY','WORKING','OPEN','AUTHENTICATED','ONLINE','LOGGED_IN','RUNNING'];
            $ready = ($st['success'] ?? false) && (
                ($st['connected'] ?? false) === true ||
                in_array(strtoupper((string)($st['state'] ?? '')), $doneStates, true)
            );
            if (!$ready) {
                $stateTxt = strtoupper((string)($st['state'] ?? 'UNKNOWN'));

                // 📣 beri tahu user kenapa gagal
                $actor?->notify(new GenericDbNotification(
                    'Broadcast Gagal',
                    "Sesi WA belum siap (state: {$stateTxt}). Silakan Scan/Connect dulu.",
                    route('whatsapp.broadcast.create')
                ));

                return back()->withErrors([
                    'sender_id' => "Sesi belum siap (state: {$stateTxt}). Silakan Scan/Connect dulu."
                ])->withInput();
            }
        } catch (\Throwable $e) {
            $actor?->notify(new GenericDbNotification(
                'Broadcast Gagal',
                "Gagal cek status sesi: ".$e->getMessage(),
                route('whatsapp.broadcast.create')
            ));
            return back()->withErrors([
                'sender_id' => "Gagal cek status sesi: ".$e->getMessage()
            ])->withInput();
        }
        /** ==================================================================== */

        // Parse & normalisasi penerima
        $rows = preg_split('/\r\n|\r|\n/', trim((string)($data['recipients'] ?? '')), -1, PREG_SPLIT_NO_EMPTY);
        $recipients = [];
        foreach ($rows as $row) {
            $line = trim($row);
            if ($line === '') continue;

            $name = null; $phone = null; $parts = null;

            if (str_contains($line, ','))        $parts = explode(',', $line, 2);
            elseif (str_contains($line, '|'))    $parts = explode('|', $line, 2);

            if ($parts && count($parts) === 2) {
                [$a,$b] = array_map('trim', $parts);
                $da = preg_replace('/\D+/', '', $a);
                $db = preg_replace('/\D+/', '', $b);
                if (strlen($da) >= 7 && strlen($db) < 7) { $phone = $da; $name = $b; }
                elseif (strlen($db) >= 7 && strlen($da) < 7) { $phone = $db; $name = $a; }
                elseif (strlen($da) >= 7) { $phone = $da; $name = $b; }
                elseif (strlen($db) >= 7) { $phone = $db; $name = $a; }
            } else {
                $d = preg_replace('/\D+/', '', $line);
                if (strlen($d) >= 7) $phone = $d;
            }

            if ($phone) {
                if (preg_match('/^0[0-9]{8,}$/', $phone)) $phone = '62'.substr($phone, 1);
                $recipients[] = [
                    'phone' => $phone,
                    'name'  => $name ?: Str::substr($phone, -4),
                ];
            }
        }

        // Dedupe by phone
        if ($recipients) {
            $uniq = [];
            foreach ($recipients as $r) $uniq[$r['phone']] = $r;
            $recipients = array_values($uniq);
        }

        if (!$recipients) {
            return back()->withErrors(['recipients' => 'Tidak ada nomor valid yang terdeteksi.'])->withInput();
        }

        // Params umum (JSON) — opsional
        $globalParams = [];
        if (!empty($data['params_json'])) {
            $decoded = json_decode($data['params_json'], true);
            if (is_array($decoded)) {
                $globalParams = $decoded;
            }
        }

        $sent = 0; $failed = 0;

        if ($data['mode'] === 'template') {
            $template = WATemplate::find($data['template_id']);
            if (!$template || (Schema::hasColumn('wa_templates','is_active') && !$template->is_active)) {
                return back()->withErrors(['template_id' => 'Template tidak ditemukan atau nonaktif.'])->withInput();
            }

            $body = (string)($template->body ?? '');
            $vars = is_array($template->variables ?? null) ? $template->variables : [];

            foreach ($recipients as $r) {
                $ctx = array_merge($globalParams, [
                    'name'            => $r['name'],
                    'nama'            => $r['name'],
                    'nama_pelanggan'  => $r['name'],
                    'phone'           => $r['phone'],
                ]);

                $text = $this->renderTemplate($body, $ctx, $vars);

                try {
                    $resp = $this->waha->sendMessage($sender, $r['phone'], $text);
                    if (($resp['success'] ?? false) === true) { $sent++; }
                    else { $failed++; Log::warning('WAHA send failed (template)', ['to'=>$r['phone'],'err'=>$resp['error'] ?? null]); }
                    usleep(250 * 1000);
                } catch (\Throwable $e) {
                    Log::error('Broadcast template exception', ['e'=>$e->getMessage(),'to'=>$r['phone']]);
                    $failed++;
                }
            }
        } else {
            // Mode custom
            $message = (string) $data['message'];
            foreach ($recipients as $r) {
                $text = strtr($message, [
                    '{{name}}'           => $r['name'],
                    '@{{name}}'          => $r['name'],
                    '{{nama}}'           => $r['name'],
                    '{{nama_pelanggan}}' => $r['name'],
                ]);

                try {
                    $resp = $this->waha->sendMessage($sender, $r['phone'], $text);
                    if (($resp['success'] ?? false) === true) { $sent++; }
                    else { $failed++; Log::warning('WAHA send failed (custom)', ['to'=>$r['phone'],'err'=>$resp['error'] ?? null]); }
                    usleep(250 * 1000);
                } catch (\Throwable $e) {
                    Log::error('Broadcast custom exception', ['e'=>$e->getMessage(),'to'=>$r['phone']]);
                    $failed++;
                }
            }
        }

        $msg = "Broadcast selesai. Terkirim: {$sent}, Gagal: {$failed}.";

        // 📣 Notifikasi ringkasan untuk actor
        $actor?->notify(new GenericDbNotification(
            'Broadcast Selesai',
            $msg,
            route('whatsapp.broadcast.create')
        ));

        $flashKey = $failed > 0 ? 'error' : 'success';
        return redirect()->route('whatsapp.broadcast.create')->with($flashKey, $msg);
    }

    /** Render {{variables}} dengan fallback */
    protected function renderTemplate(string $body, array $context, array $allowedVars = []): string
    {
        $repls = [];

        if (!$allowedVars) {
            preg_match_all('/\{\{\s*([A-Za-z0-9_.-]+)\s*\}\}/', $body, $m);
            $allowedVars = array_values(array_unique($m[1] ?? []));
        }

        foreach ($allowedVars as $k) {
            $val = $context[$k] ?? '';
            if (is_array($val)) $val = json_encode($val, JSON_UNESCAPED_UNICODE);
            $repls['{{'.$k.'}}']  = (string) $val;
            $repls['@{{'.$k.'}}'] = (string) $val;
        }

        foreach (['name','nama','nama_pelanggan'] as $alias) {
            if (!array_key_exists('{{'.$alias.'}}', $repls) && isset($context['name'])) {
                $repls['{{'.$alias.'}}']  = (string)$context['name'];
                $repls['@{{'.$alias.'}}'] = (string)$context['name'];
            }
        }

        return strtr($body, $repls);
    }
}
