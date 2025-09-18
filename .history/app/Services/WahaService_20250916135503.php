<?php

namespace App\Services;

use App\Models\WahaSender;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WahaService
{
    /* ============================================================
     * ===============  HELPERS / CONFIG RESOLUTION  ===============
     * ============================================================ */

    /** Ambil base URL dari .env/config dan pastikan berakhiran /api */
    protected function apiBase(): ?string
    {
        // Urutan prioritas: sender->base_url (jika ada), services.php, WAHA_URL, WAHA_BASE_URL
        $base = config('services.waha.base_url')
             ?? env('WAHA_URL')
             ?? env('WAHA_BASE_URL');

        if (!$base) return null;

        $base = rtrim($base, '/');
        // pastikan ada /api di akhir (hindari double /api)
        if (!preg_match('~/api/?$~', $base)) {
            $base .= '/api';
        }
        return $base;
    }

    /** HTTP client dengan header & opsi keamanan dari .env */
    protected function http()
    {
        $headers = [
            'Accept' => 'application/json',
        ];
        if ($key = env('WAHA_KEY')) {
            // mayoritas build WAHA menerima X-Api-Key
            $headers['X-Api-Key'] = $key;
            // beberapa build memakai Authorization Bearer
            $headers['Authorization'] = 'Bearer '.$key;
        }
        if ($ua = env('WAHA_UA')) {
            $headers['User-Agent'] = $ua;
        }

        $verify = ! filter_var(env('WAHA_INSECURE', false), FILTER_VALIDATE_BOOLEAN);

        return Http::timeout(25)
            ->withHeaders($headers)
            ->withOptions(['verify' => $verify]);
    }

    /** Nama session kanonik yang dipakai WAHA */
    protected function sessionName(WahaSender $sender): string
    {
        return $sender->session
            ?? $sender->session_name
            ?? $sender->name
            ?? $sender->number
            ?? 'default';
    }

    /* ============================================================
     * ====================  SEND TEXT MESSAGE  ===================
     * ============================================================ */

    /**
     * Kirim pesan WhatsApp via WAHA.
     * - Jika base URL tidak dikonfigurasi → SIMULASI (tetap success) agar alur sistem stabil.
     * - Kembalikan struktur yang dipakai controller: success, message_id, status, path, raw.
     */
    public function sendMessage(WahaSender $sender, string $recipient, string $text): array
    {
        $to = $this->sanitize($recipient);
        if (!$to) {
            return [
                'success' => false,
                'error'   => 'INVALID_RECIPIENT',
                'message' => 'Nomor tujuan tidak valid',
            ];
        }

        $api     = $this->apiBase();
        $session = $this->sessionName($sender);

        // Tanpa konfigurasi → mode simulasi
        if (!$api) {
            $id = 'SIM-'.Str::uuid();
            Log::info('[WAHA][SIMULATED] sendMessage', [
                'sender_id' => $sender->id,
                'session'   => $session,
                'to'        => $to,
                'text'      => Str::limit($text, 160),
            ]);

            return [
                'success'     => true,
                'message_id'  => $id,
                'status'      => 'SIMULATED',
                'path'        => null,
                'raw'         => ['simulated' => true, 'session' => $session, 'to' => $to],
            ];
        }

        // Coba beberapa varian endpoint yang umum di WAHA
        $attempts = [
            [
                'url'     => $api . '/sendText',
                'payload' => ['session' => $session, 'to' => $to, 'text' => $text],
            ],
            [
                // banyak build modern pakai path ber-sesi
                'url'     => $api . "/sessions/{$session}/messages/send/text",
                'payload' => ['to' => $to, 'text' => $text],
            ],
            [
                // fallback sangat umum
                'url'     => $api . "/sessions/{$session}/messages",
                'payload' => ['to' => $to, 'text' => $text, 'type' => 'text'],
            ],
        ];

        foreach ($attempts as $i => $opt) {
            try {
                $resp = $this->http()->post($opt['url'], $opt['payload']);
                $json = $resp->json() ?? [];
                $msgId = $this->extractMessageId($json);

                Log::info('[WAHA] sendMessage attempt#'.($i+1), [
                    'sender_id' => $sender->id,
                    'status'    => $resp->status(),
                    'msg_id'    => $msgId,
                    'url'       => $opt['url'],
                ]);

                if ($resp->successful()) {
                    return [
                        'success'     => true,
                        'message_id'  => $msgId,
                        'status'      => $json['status'] ?? 'OK',
                        'path'        => $opt['url'],
                        'raw'         => $json,
                    ];
                }

                // lanjut ke attempt berikutnya
                $lastJson = $json;
            } catch (\Throwable $e) {
                Log::warning('[WAHA] sendMessage error attempt#'.($i+1).': '.$e->getMessage());
                $lastJson = ['error' => $e->getMessage()];
            }
        }

        return [
            'success' => false,
            'status'  => 'ERROR',
            'path'    => $attempts[0]['url'],
            'raw'     => $lastJson ?? null,
        ];
    }

    /* ============================================================
     * ===================  SESSION MANAGEMENT  ===================
     * ============================================================ */

    /** GET /api/sessions/{session} */
    public function sessionStatus(WahaSender $sender): array
    {
        $api     = $this->apiBase();
        $session = $this->sessionName($sender);

        if (!$api) {
            return ['success' => false, 'error' => 'NO_BASE_URL', 'message' => 'WAHA_URL/WAHA_BASE_URL belum dikonfigurasi.'];
        }

        $url = $api . "/sessions/{$session}";
        try {
            $resp = $this->http()->get($url);
            $json = $resp->json() ?? [];

            // Normalisasi field
            $state     = data_get($json, 'state') ?? data_get($json, 'result.state');
            $connected = data_get($json, 'connected');
            if ($connected === null) {
                $connected = in_array(strtoupper((string)$state), ['OPEN','READY','RUNNING','CONNECTED','ONLINE','AUTHENTICATED'], true);
            }
            $qr        = data_get($json, 'qr') ?? data_get($json, 'result.qr');

            return [
                'success'   => $resp->successful(),
                'session'   => $session,
                'connected' => (bool)$connected,
                'state'     => $state ?? 'UNKNOWN',
                'qr'        => $qr,
                'raw'       => $json,
            ];
        } catch (\Throwable $e) {
            Log::error('[WAHA] sessionStatus error: '.$e->getMessage(), ['session'=>$session]);
            return ['success'=>false, 'error'=>'EXCEPTION', 'message'=>$e->getMessage()];
        }
    }

    /** POST /api/sessions/{session}/start — alias qrStart() */
    public function sessionStart(WahaSender $sender): array
    {
        $api     = $this->apiBase();
        $session = $this->sessionName($sender);
        if (!$api) {
            return ['success' => false, 'error' => 'NO_BASE_URL', 'message' => 'WAHA_URL/WAHA_BASE_URL belum dikonfigurasi.'];
        }

        $startUrl  = $api . "/sessions/{$session}/start";
        $createUrl = $api . "/sessions";

        try {
            // langsung coba start
            $resp = $this->http()->post($startUrl);
            if ($resp->status() === 404 || $resp->status() === 409) {
                // beberapa build butuh create dulu
                $this->http()->post($createUrl, ['session' => $session, 'type' => 'md']);
                $resp = $this->http()->post($startUrl);
            }

            $json   = $resp->json() ?? [];
            $state  = data_get($json, 'state') ?? data_get($json, 'result.state');
            $qr     = data_get($json, 'qr') ?? data_get($json, 'result.qr');

            return [
                'success' => $resp->successful(),
                'session' => $session,
                'state'   => $state ?? 'UNKNOWN',
                'qr'      => $qr,
                'path'    => $startUrl,
                'raw'     => $json,
            ];
        } catch (\Throwable $e) {
            Log::error('[WAHA] sessionStart error: '.$e->getMessage(), ['session'=>$session]);
            return ['success'=>false, 'error'=>'EXCEPTION', 'message'=>$e->getMessage()];
        }
    }

    /** Beberapa controller memanggil qrStart(), kita alias-kan ke sessionStart(). */
    public function qrStart(WahaSender $sender): array
    {
        return $this->sessionStart($sender);
    }

    /** POST /api/sessions/{session}/logout */
    public function sessionLogout(WahaSender $sender): array
    {
        $api     = $this->apiBase();
        $session = $this->sessionName($sender);
        if (!$api) {
            return ['success' => false, 'error' => 'NO_BASE_URL', 'message' => 'WAHA_URL/WAHA_BASE_URL belum dikonfigurasi.'];
        }

        $url = $api . "/sessions/{$session}/logout";
        try {
            $resp = $this->http()->post($url);
            $json = $resp->json() ?? [];
            return [
                'success' => $resp->successful(),
                'path'    => $url,
                'raw'     => $json,
            ];
        } catch (\Throwable $e) {
            Log::error('[WAHA] sessionLogout error: '.$e->getMessage(), ['session'=>$session]);
            return ['success'=>false, 'error'=>'EXCEPTION', 'message'=>$e->getMessage()];
        }
    }

    /** GET /api/sessions/{session}/me — info akun (number & display name) */
    public function sessionMe(WahaSender $sender): array
    {
        $api     = $this->apiBase();
        $session = $this->sessionName($sender);
        if (!$api) {
            return ['success' => false, 'error' => 'NO_BASE_URL', 'message' => 'WAHA_URL/WAHA_BASE_URL belum dikonfigurasi.'];
        }

        $url = $api . "/sessions/{$session}/me";
        try {
            $resp = $this->http()->get($url);
            $json = $resp->json() ?? [];

            // normalisasi kemungkinan struktur berbeda
            $id   = data_get($json, 'id') ?? data_get($json, 'me.id') ?? data_get($json, 'result.id');
            $name = data_get($json, 'name') ?? data_get($json, 'pushName') ?? data_get($json, 'me.name') ?? data_get($json, 'result.name');

            $digits = $this->sanitize($id ?? '');
            return [
                'success'      => $resp->successful(),
                'number'       => $digits,
                'display_name' => $name,
                'raw'          => $json,
            ];
        } catch (\Throwable $e) {
            Log::error('[WAHA] sessionMe error: '.$e->getMessage(), ['session'=>$session]);
            return ['success'=>false, 'error'=>'EXCEPTION', 'message'=>$e->getMessage()];
        }
    }

    /**
     * Ambil QR image binary untuk ditampilkan via proxy.
     * Coba beberapa endpoint umum: /qr.svg, /qr.png, /qr
     */
    public function qrImageBinary(WahaSender $sender): array
    {
        $api     = $this->apiBase();
        $session = $this->sessionName($sender);
        if (!$api) {
            return ['success' => false, 'error' => 'NO_BASE_URL', 'message' => 'WAHA_URL/WAHA_BASE_URL belum dikonfigurasi.'];
        }

        $urls = [
            $api . "/sessions/{$session}/qr.svg",
            $api . "/sessions/{$session}/qr.png",
            $api . "/sessions/{$session}/qr",
        ];

        foreach ($urls as $url) {
            try {
                $resp = $this->http()->get($url);
                if ($resp->successful() && ($body = $resp->body())) {
                    return [
                        'success' => true,
                        'body'    => $body,
                        'ctype'   => $resp->header('Content-Type') ?: (Str::endsWith($url, '.svg') ? 'image/svg+xml' : 'image/png'),
                        'path'    => $url,
                    ];
                }
            } catch (\Throwable $e) {
                Log::debug('[WAHA] qrImageBinary try failed: '.$e->getMessage(), ['url'=>$url]);
            }
        }

        return ['success'=>false, 'message'=>'QR not available yet'];
    }

    /**
     * Minta pairing code (jika build WAHA mendukung mode pairing code).
     * Implementasi best-effort: POST start dengan hint 'mode' => 'pairing'.
     */
    public function requestAuthCode(WahaSender $sender): array
    {
        $api     = $this->apiBase();
        $session = $this->sessionName($sender);
        if (!$api) {
            return ['success' => false, 'error' => 'NO_BASE_URL', 'message' => 'WAHA_URL/WAHA_BASE_URL belum dikonfigurasi.'];
        }

        $url = $api . "/sessions/{$session}/start";
        try {
            $resp = $this->http()->post($url, ['mode' => 'pairing']);
            $json = $resp->json() ?? [];
            $code = data_get($json, 'code') ?? data_get($json, 'result.code') ?? null;

            return [
                'success' => $resp->successful() && !empty($code),
                'code'    => $code,
                'path'    => $url,
                'raw'     => $json,
            ];
        } catch (\Throwable $e) {
            Log::error('[WAHA] requestAuthCode error: '.$e->getMessage(), ['session'=>$session]);
            return ['success'=>false, 'error'=>'EXCEPTION', 'message'=>$e->getMessage()];
        }
    }

    /* ============================================================
     * ===================  UTILS (private)  ======================
     * ============================================================ */

    /** Ambil angka dari input & normalisasi ringan (0xxxx → 62xxxx). */
    protected function sanitize(?string $p): ?string
    {
        if (!$p) return null;
        $n = preg_replace('/\D+/', '', $p);
        if (Str::startsWith($n, '0') && strlen($n) >= 9) {
            $n = '62' . substr($n, 1);
        }
        return $n ?: null;
    }

    /** Coba ambil message id dari berbagai bentuk respons WAHA. */
    protected function extractMessageId(array $json): ?string
    {
        return $json['message_id']
            ?? data_get($json, 'data.message_id')
            ?? data_get($json, 'result.key.id')
            ?? data_get($json, 'key.id')
            ?? data_get($json, 'id')
            ?? null;
    }
}
