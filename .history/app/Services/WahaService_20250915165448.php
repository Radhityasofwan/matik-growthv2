<?php

namespace App\Services;

use App\Models\WahaSender;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaService
{
    protected string $baseUrl;         // contoh: https://waha.matik.id   (TANPA /api)
    protected ?string $apiKey;         // opsional; isi jika server pakai x-api-key
    protected string $userAgent;
    protected bool $insecure;

    public function __construct()
    {
        $this->baseUrl   = rtrim((string) config('services.waha.url'), '/');
        $this->apiKey    = config('services.waha.key'); // bisa null
        $this->userAgent = (string) env('WAHA_UA', 'Matik Growth Hub');
        $this->insecure  = (bool) env('WAHA_INSECURE', false);
    }

    /* =========================================================================
     |  SEND TEXT — sesuai dokumentasi yang kamu kirim
     |  POST {base}/api/sendText { session, chatId, text }
     * ========================================================================= */
    public function sendMessage(WahaSender $sender, string $recipient, string $message): ?array
    {
        $session = $this->resolveSession($sender);
        $chatId  = $this->toChatId($recipient);

        $payload = [
            'session' => $session,
            'chatId'  => $chatId,
            'text'    => $message,
        ];

        $url = $this->baseUrl . '/api/sendText';

        try {
            $res = $this->client()->post($url, $payload);

            if ($res->failed()) {
                Log::warning('WAHA sendText non-2xx', [
                    'url'    => $url,
                    'status' => $res->status(),
                    'body'   => mb_substr($res->body(), 0, 500),
                    'payload'=> $payload,
                ]);
                return null;
            }

            return $res->json() ?: ['success' => true];
        } catch (\Throwable $e) {
            Log::error('WAHA sendText exception', ['url' => $url, 'err' => $e->getMessage()]);
            return null;
        }
    }

    /* =========================================================================
     |  SESSION / QR — best-effort untuk berbagai varian WAHA.
     |  Semua method mengembalikan bentuk baku:
     |  ['success'=>bool,'connected'=>?bool,'state'=>?string,'qr'=>?string,'error'=>?string,'raw'=>mixed]
     * ========================================================================= */

    public function sessionStatus(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);

        // Coba berbagai kemungkinan path GET status/state
        $try = [
            "/api/sessions/{$session}/status",
            "/api/session/{$session}/status",
            "/sessions/{$session}/status",
            "/session/{$session}/status",

            "/api/sessions/{$session}/state",
            "/api/session/{$session}/state",
            "/sessions/{$session}/state",
            "/session/{$session}/state",

            // model query param
            "/api/sessions/status?session={$this->e($session)}",
            "/api/session/status?session={$this->e($session)}",
            "/api/state?session={$this->e($session)}",
        ];

        $out = $this->tryGet($try);
        if ($out['success']) {
            $json = $out['raw'];
            // Normalisasi status
            $state = $json['state']     ?? $json['status']   ?? null;
            $conn  = $json['connected'] ?? null;

            if ($conn === null && is_string($state)) {
                $up = strtoupper($state);
                $conn = in_array($up, ['CONNECTED','AUTHENTICATED','READY','WORKING'], true);
            }

            return [
                'success'   => true,
                'connected' => $conn,
                'state'     => $state,
                'qr'        => $json['qr'] ?? null,
                'error'     => null,
                'raw'       => $json,
            ];
        }

        return [
            'success'   => false,
            'connected' => null,
            'state'     => null,
            'qr'        => null,
            'error'     => $out['error'] ?: 'No status endpoint available',
            'raw'       => $out['raw'],
        ];
    }

        public function sessionStart(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);

        // payload multi-key agar kompatibel dgn berbagai WAHA
        $payloads = [
            ['session' => $session],
            ['sessionId' => $session],
            ['name' => $session],
        ];

        $paths = [
            '/api/sessions/start',
            '/api/session/start',
            '/api/start',
            "/api/sessions/{$session}/start",
            "/api/session/{$session}/start",
            "/sessions/{$session}/start",
            "/session/{$session}/start",
            // varian GET/POST dengan query string
            "/api/sessions/start?session={$this->e($session)}",
            "/api/start?session={$this->e($session)}",
        ];

        // coba POST dgn beberapa payload
        foreach ($payloads as $body) {
            $out = $this->tryPost($paths, $body);
            if ($out['success']) {
                $json  = $out['raw'] ?? [];
                return [
                    'success'   => true,
                    'connected' => $json['connected'] ?? null,
                    'state'     => $json['state'] ?? $json['status'] ?? null,
                    'qr'        => $json['qr'] ?? $json['image'] ?? $json['qrcode'] ?? null,
                    'error'     => null,
                    'raw'       => $json,
                ];
            }
        }

        // fallback: beberapa build hanya support GET start
        $out = $this->tryGet($paths);
        if ($out['success']) {
            $json  = $out['raw'] ?? [];
            return [
                'success'   => true,
                'connected' => $json['connected'] ?? null,
                'state'     => $json['state'] ?? $json['status'] ?? null,
                'qr'        => $json['qr'] ?? $json['image'] ?? $json['qrcode'] ?? null,
                'error'     => null,
                'raw'       => $json,
            ];
        }

        return [
            'success'   => false,
            'connected' => null,
            'state'     => null,
            'qr'        => null,
            'error'     => $out['error'] ?: 'No start endpoint available',
            'raw'       => $out['raw'],
        ];
    }


    public function sessionLogout(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);

        $try = [
            "/api/sessions/{$session}/logout",
            "/api/session/{$session}/logout",
            "/sessions/{$session}/logout",
            "/session/{$session}/logout",
            "/api/logout?session={$this->e($session)}",
        ];

        $out = $this->tryPost($try, []);
        if ($out['success']) {
            return [
                'success'   => true,
                'connected' => false,
                'state'     => $out['raw']['state'] ?? 'LOGGED_OUT',
                'qr'        => null,
                'error'     => null,
                'raw'       => $out['raw'],
            ];
        }

        return [
            'success'   => false,
            'connected' => null,
            'state'     => null,
            'qr'        => null,
            'error'     => $out['error'] ?: 'No logout endpoint available',
            'raw'       => $out['raw'],
        ];
    }

    /** Ambil QR jika tersedia (beberapa WAHA expose endpoint ini) */
    public function qrStatus(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);

        $try = [
            "/api/sessions/{$session}/qr",
            "/api/session/{$session}/qr",
            "/sessions/{$session}/qr",
            "/session/{$session}/qr",
            "/api/qr?session={$this->e($session)}",
        ];

        $out = $this->tryGet($try);
        if ($out['success']) {
            $json = $out['raw'];

            // format bisa base64 (dataURL) atau string biasa
            $qr = $json['qr'] ?? $json['image'] ?? $json['qrcode'] ?? null;

            return [
                'success'   => true,
                'connected' => $json['connected'] ?? null,
                'state'     => $json['state'] ?? $json['status'] ?? null,
                'qr'        => $qr,
                'error'     => null,
                'raw'       => $json,
            ];
        }

        return [
            'success'   => false,
            'connected' => null,
            'state'     => null,
            'qr'        => null,
            'error'     => $out['error'] ?: 'No QR endpoint available',
            'raw'       => $out['raw'],
        ];
    }

    /** Beberapa build menyediakan /api/qr/start (atau start mengembalikan QR) */
    public function qrStart(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);
        $body    = ['session' => $session];

        $try = [
            '/api/qr/start',
            "/api/sessions/{$session}/start",
            "/api/session/{$session}/start",
            "/sessions/{$session}/start",
            "/session/{$session}/start",
        ];

        $out = $this->tryPost($try, $body);
        if ($out['success']) {
            $json  = $out['raw'];
            $state = $json['state'] ?? $json['status'] ?? null;
            $qr    = $json['qr']   ?? $json['image'] ?? $json['qrcode'] ?? null;

            return [
                'success'   => true,
                'connected' => $json['connected'] ?? null,
                'state'     => $state,
                'qr'        => $qr,
                'error'     => null,
                'raw'       => $json,
            ];
        }

        return [
            'success'   => false,
            'connected' => null,
            'state'     => null,
            'qr'        => null,
            'error'     => $out['error'] ?: 'No QR start endpoint available',
            'raw'       => $out['raw'],
        ];
    }

    /* =======================================================================
     | Helpers
     * ======================================================================= */

   protected function client()
    {
        $headers = ['User-Agent' => $this->userAgent];
        if (!empty($this->apiKey)) {
            $headers['x-api-key'] = $this->apiKey;
        }
        $c = Http::acceptJson()
            ->retry(2, 250)      // <— retry ringan utk spike network/502
            ->timeout(30)
            ->withHeaders($headers);
        return $this->insecure ? $c->withoutVerifying() : $c;
    }

    /** Try a list of GET endpoints; return first success */
    protected function tryGet(array $paths): array
    {
        $errors = [];
        foreach ($paths as $p) {
            $url = $this->url($p);
            try {
                $res = $this->client()->get($url);
                if ($res->successful()) {
                    return ['success' => true, 'raw' => $res->json()];
                }
                $errors[] = "{$res->method()} {$p} {$res->status()}";
                Log::warning('WAHA GET non-2xx', ['url' => $url, 'status' => $res->status(), 'body' => mb_substr($res->body(), 0, 500)]);
            } catch (\Throwable $e) {
                $errors[] = "GET {$p} EXC";
                Log::error('WAHA GET exception', ['url' => $url, 'err' => $e->getMessage()]);
            }
        }
        return ['success' => false, 'error' => implode(' | ', $errors), 'raw' => null];
    }

    /** Try a list of POST endpoints; return first success */
    protected function tryPost(array $paths, array $payload): array
    {
        $errors = [];
        foreach ($paths as $p) {
            $url = $this->url($p);
            try {
                $res = $this->client()->post($url, $payload);
                if ($res->successful()) {
                    return ['success' => true, 'raw' => $res->json()];
                }
                $errors[] = "{$res->method()} {$p} {$res->status()}";
                Log::warning('WAHA start non-2xx', ['url' => $url, 'status' => $res->status(), 'body' => mb_substr($res->body(), 0, 500)]);
            } catch (\Throwable $e) {
                $errors[] = "POST {$p} EXC";
                Log::error('WAHA POST exception', ['url' => $url, 'err' => $e->getMessage()]);
            }
        }
        return ['success' => false, 'error' => implode(' | ', $errors), 'raw' => null];
    }

    protected function url(string $path): string
    {
        $p = str_starts_with($path, '/') ? $path : "/{$path}";
        return $this->baseUrl . $p;
    }

    protected function asSession(WahaSender|string $senderOrSession): string
    {
        if ($senderOrSession instanceof WahaSender) {
            return $this->resolveSession($senderOrSession);
        }
        return (string) $senderOrSession;
    }

    protected function resolveSession(WahaSender $sender): string
    {
        foreach (['session', 'session_name', 'sessionId', 'session_key'] as $f) {
            if (!empty($sender->{$f})) return (string) $sender->{$f};
        }
        return 'default';
    }

    protected function toChatId(string $raw): string
    {
        $raw = trim($raw);

        if (str_contains($raw, '@')) {
            return $raw; // mis. ...@c.us atau ...@newsletter
        }

        $digits = preg_replace('/\D+/', '', $raw) ?: $raw;
        if (preg_match('/^0\d+$/', $digits)) {
            $digits = '62' . substr($digits, 1);
        } elseif (preg_match('/^\+?\d+$/', $raw)) {
            $digits = ltrim($raw, '+');
        }

        return $digits . '@c.us';
    }

    protected function e(string $s): string
    {
        return rawurlencode($s);
    }
}
