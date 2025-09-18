<?php

namespace App\Services;

use App\Models\WahaSender;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WahaService
{
    /* ============================================================
     * ===============  CONFIG & HTTP CLIENT HELPERS  =============
     * ============================================================ */

    /**
     * Ambil base URL WAHA dari (berurutan):
     * - $sender->base_url (jika ada kolomnya)
     * - config('services.waha.base_url')
     * - env('WAHA_URL') atau env('WAHA_BASE_URL')
     * Selalu dipastikan berakhiran "/api".
     */
    protected function apiBase(?WahaSender $sender = null): ?string
    {
        $base = null;

        // Kolom opsional per-sender
        if ($sender && isset($sender->base_url) && is_string($sender->base_url) && $sender->base_url !== '') {
            $base = $sender->base_url;
        }

        $base = $base
            ?? config('services.waha.base_url')
            ?? env('WAHA_URL')
            ?? env('WAHA_BASE_URL');

        if (!$base) return null;

        $base = rtrim($base, '/');
        // pastikan ada /api di paling belakang, tanpa menduplikasi
        if (!preg_match('~/api/?$~i', $base)) {
            $base .= '/api';
        }
        return $base;
    }

    /** HTTP client dengan header-key, UA, dan opsi TLS dari .env */
    protected function http()
    {
        $headers = [
            'Accept' => 'application/json',
        ];

        if ($key = (env('WAHA_KEY') ?: env('WAHA_API_KEY'))) {
            // Kebanyakan build menerima salah satu dari 2 header ini
            $headers['X-Api-Key']     = $key;
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

    /** Nama session yang dipakai di WAHA */
    protected function sessionName(WahaSender $sender): string
    {
        return $sender->session
            ?? $sender->session_name
            ?? $sender->name
            ?? $sender->number
            ?? 'default';
    }

    /* ============================================================
     * =====================  SEND TEXT MESSAGE  ==================
     * ============================================================ */

    /**
     * Kirim pesan WhatsApp via WAHA.
     * Return shape konsisten: success, message_id, status, path, raw.
     * Jika base URL tidak ada → SIMULATED (agar flow app tetap lancar).
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

        $api     = $this->apiBase($sender);
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

        // Coba beberapa varian endpoint yang lazim dipakai WAHA
        $attempts = [
            [
                'url'     => $api . '/sendText',
                'payload' => ['session' => $session, 'to' => $to, 'text' => $text],
            ],
            [
                'url'     => $api . "/sessions/{$session}/messages/send/text",
                'payload' => ['to' => $to, 'text' => $text],
            ],
            [
                'url'     => $api . "/sessions/{$session}/messages",
                'payload' => ['to' => $to, 'type' => 'text', 'text' => $text],
            ],
        ];

        $lastJson = null;
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
                $lastJson = $json;
            } catch (\Throwable $e) {
                Log::warning('[WAHA] sendMessage attempt#'.($i+1).' error: '.$e->getMessage());
                $lastJson = ['error' => $e->getMessage()];
            }
        }

        return [
            'success' => false,
            'status'  => 'ERROR',
            'path'    => $attempts[0]['url'],
            'raw'     => $lastJson,
        ];
    }

    /* ============================================================
     * ===================  SESSION MANAGEMENT  ===================
     * ============================================================ */

    /** GET status sesi. Robust ke banyak variasi JSON WAHA. */
    public function sessionStatus(WahaSender $sender): array
    {
        $api     = $this->apiBase($sender);
        $session = $this->sessionName($sender);

        if (!$api) {
            return ['success' => false, 'error' => 'NO_BASE_URL', 'message' => 'WAHA_URL/WAHA_BASE_URL belum dikonfigurasi.'];
        }

        $urls = [
            $api . "/sessions/{$session}",
            $api . "/sessions/{$session}/state",
            $api . "/sessions/{$session}/status",
        ];

        foreach ($urls as $url) {
            try {
                $resp = $this->http()->get($url);
                $json = $resp->json();

                // Jika bukan JSON valid, coba endpoint lain
                if (!is_array($json)) continue;

                // Normalisasi:
                // - state mungkin ada di: state, result.state, data.state
                // - connected: connected / result.connected
                // - qr inline: qr / result.qr / data.qr
                $state = data_get($json, 'state')
                      ?? data_get($json, 'result.state')
                      ?? data_get($json, 'data.state');

                $connected = data_get($json, 'connected');
                if ($connected === null) {
                    $connected = data_get($json, 'result.connected');
                }
                if ($connected === null && is_string($state)) {
                    $connected = in_array(strtoupper($state), [
                        'OPEN','READY','RUNNING','CONNECTED','ONLINE','AUTHENTICATED','WORKING','LOGGED_IN'
                    ], true);
                }

                $qr = data_get($json, 'qr')
                   ?? data_get($json, 'result.qr')
                   ?? data_get($json, 'data.qr');

                return [
                    'success'   => true,
                    'session'   => $session,
                    'connected' => (bool)$connected,
                    'state'     => $state ?? 'UNKNOWN',
                    'qr'        => $qr,
                    'raw'       => $json,
                ];
            } catch (\Throwable $e) {
                Log::debug('[WAHA] sessionStatus try failed: '.$e->getMessage(), ['url' => $url, 'session' => $session]);
            }
        }

        // Semua endpoint gagal diparse
        return ['success' => false, 'error' => 'UNPARSABLE_RESPONSE', 'message' => 'Tidak bisa membaca status sesi dari WAHA.'];
    }

    /** Start session (alias QR start). */
    public function sessionStart(WahaSender $sender): array
    {
        $api     = $this->apiBase($sender);
        $session = $this->sessionName($sender);
        if (!$api) {
            return ['success' => false, 'error' => 'NO_BASE_URL', 'message' => 'WAHA_URL/WAHA_BASE_URL belum dikonfigurasi.'];
        }

        $startUrl  = $api . "/sessions/{$session}/start";
        $createUrl = $api . "/sessions";

        try {
            // coba langsung start
            $resp = $this->http()->post($startUrl);
            // beberapa build butuh create dulu
            if (in_array($resp->status(), [404, 409], true)) {
                $this->http()->post($createUrl, ['session' => $session, 'type' => 'md']);
                $resp = $this->http()->post($startUrl);
            }

            $json  = $resp->json() ?? [];
            $state = data_get($json, 'state') ?? data_get($json, 'result.state') ?? 'UNKNOWN';
            $qr    = data_get($json, 'qr')    ?? data_get($json, 'result.qr');

            return [
                'success' => $resp->successful(),
                'session' => $session,
                'state'   => $state,
                'qr'      => $qr,
                'path'    => $startUrl,
                'raw'     => $json,
            ];
        } catch (\Throwable $e) {
            Log::error('[WAHA] sessionStart error: '.$e->getMessage(), ['session'=>$session]);
            return ['success'=>false, 'error'=>'EXCEPTION', 'message'=>$e->getMessage()];
        }
    }

    /** Beberapa controller memanggil qrStart(); alias ke sessionStart(). */
    public function qrStart(WahaSender $sender): array
    {
        return $this->sessionStart($sender);
    }

    /** Logout session. */
    public function sessionLogout(WahaSender $sender): array
    {
        $api     = $this->apiBase($sender);
        $session = $this->sessionName($sender);
        if (!$api) {
            return ['success' => false, 'error' => 'NO_BASE_URL', 'message' => 'WAHA_URL/WAHA_BASE_URL belum dikonfigurasi.'];
        }

        $url = $api . "/sessions/{$session}/logout";
        try {
            $resp = $this->http()->post($url);
            return [
                'success' => $resp->successful(),
                'path'    => $url,
                'raw'     => $resp->json(),
            ];
        } catch (\Throwable $e) {
            Log::error('[WAHA] sessionLogout error: '.$e->getMessage(), ['session'=>$session]);
            return ['success'=>false, 'error'=>'EXCEPTION', 'message'=>$e->getMessage()];
        }
    }

    /** GET /api/sessions/{session}/me → info akun (number & display name) */
    public function sessionMe(WahaSender $sender): array
    {
        $api     = $this->apiBase($sender);
        $session = $this->sessionName($sender);
        if (!$api) {
            return ['success' => false, 'error' => 'NO_BASE_URL', 'message' => 'WAHA_URL/WAHA_BASE_URL belum dikonfigurasi.'];
        }

        $url = $api . "/sessions/{$session}/me";
        try {
            $resp = $this->http()->get($url);
            $json = $resp->json() ?? [];

            // Normalisasi berbagai bentuk
            $id   = data_get($json, 'id') ?? data_get($json, 'me.id') ?? data_get($json, 'result.id');
            $name = data_get($json, 'name') ?? data_get($json, 'pushName') ?? data_get($json, 'me.name') ?? data_get($json, 'result.name');

            return [
                'success'      => $resp->successful(),
                'number'       => $this->sanitize($id),
                'display_name' => $name,
                'raw'          => $json,
            ];
        } catch (\Throwable $e) {
            Log::error('[WAHA] sessionMe error: '.$e->getMessage(), ['session'=>$session]);
            return ['success'=>false, 'error'=>'EXCEPTION', 'message'=>$e->getMessage()];
        }
    }

    /** Ambil QR image (binary) dari beberapa endpoint umum. */
    public function qrImageBinary(WahaSender $sender): array
    {
        $api     = $this->apiBase($sender);
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
                if ($resp->successful()) {
                    $body  = $resp->body();
                    $ctype = $resp->header('Content-Type')
                          ?: (Str::endsWith($url, '.svg') ? 'image/svg+xml' : 'image/png');

                    if ($body !== '') {
                        return [
                            'success' => true,
                            'body'    => $body,
                            'ctype'   => $ctype,
                            'path'    => $url,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                Log::debug('[WAHA] qrImageBinary try failed: '.$e->getMessage(), ['url'=>$url]);
            }
        }

        return ['success'=>false, 'message'=>'QR not available yet'];
    }

    /**
     * Minta pairing code bila didukung.
     * Implementasi best-effort: POST start dengan "mode" => "pairing".
     */
    public function requestAuthCode(WahaSender $sender): array
    {
        $api     = $this->apiBase($sender);
        $session = $this->sessionName($sender);
        if (!$api) {
            return ['success' => false, 'error' => 'NO_BASE_URL', 'message' => 'WAHA_URL/WAHA_BASE_URL belum dikonfigurasi.'];
        }

        $url = $api . "/sessions/{$session}/start";
        try {
            $resp = $this->http()->post($url, ['mode' => 'pairing']);
            $json = $resp->json() ?? [];
            $code = data_get($json, 'code') ?? data_get($json, 'result.code');

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
     * ===================  PRIVATE UTILITIES  ====================
     * ============================================================ */

    /** Normalisasi nomor: ambil digit, 08xx → 628xx */
    protected function sanitize(?string $p): ?string
    {
        if (!$p) return null;
        $n = preg_replace('/\D+/', '', $p);
        if (Str::startsWith($n, '0') && strlen($n) >= 9) {
            $n = '62' . substr($n, 1);
        }
        return $n ?: null;
    }

    /** Ekstrak message id dari berbagai variasi respons. */
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
