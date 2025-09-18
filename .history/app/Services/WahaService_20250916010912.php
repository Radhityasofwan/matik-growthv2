<?php

namespace App\Services;

use App\Models\WahaSender;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaService
{
    protected string $baseUrl;     // contoh: https://waha.matik.id (TANPA /api)
    protected ?string $apiKey;     // opsional; isi jika server pakai x-api-key
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
     |  SEND TEXT — Mencoba beberapa varian path/payload; stop di pertama sukses
     * ========================================================================= */
    public function sendMessage(WahaSender $sender, string $recipient, string $message): ?array
    {
        $session = $this->resolveSession($sender);
        $chatId  = $this->toChatId($recipient);

        $paths = [
            '/api/sendText',        // ✅ sesuai Swagger terbaru
            '/api/message',         // varian lama
            '/message',             // varian tanpa /api
            '/api/send-message',    // varian lain
        ];

        $payloads = [
            ['session' => $session, 'chatId'  => $chatId, 'text'    => $message],
            ['session' => $session, 'chatId'  => $chatId, 'message' => $message],
            ['session' => $session, 'receiver'=> $chatId, 'text'    => $message],
        ];

        $errors = [];
        foreach ($paths as $p) {
            $url = $this->url($p);
            foreach ($payloads as $body) {
                try {
                    $res = $this->client()->post($url, $body);
                    if ($res->successful()) {
                        return $res->json() ?: ['success' => true, 'path' => $p];
                    }
                    $errors[] = "POST {$p} {$res->status()}";
                    Log::warning('WAHA sendMessage non-2xx', [
                        'url' => $url, 'status' => $res->status(),
                        'body' => mb_substr($res->body(), 0, 500), 'payload' => $body,
                    ]);
                } catch (\Throwable $e) {
                    $errors[] = "POST {$p} EXC";
                    Log::error('WAHA sendMessage exception', ['url' => $url, 'err' => $e->getMessage()]);
                }
            }
        }

        return ['success' => false, 'raw' => null, 'error' => implode(' | ', $errors)];
    }

    /* =========================================================================
     |  SESSION / QR — Normalisasi output:
     |  ['success'=>bool,'connected'=>?bool,'state'=>?string,'qr'=>?string,'error'=>?string,'raw'=>mixed]
     * ========================================================================= */

    public function sessionStatus(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);

        $try = [
            "/api/sessions/{$session}",             // banyak build mengembalikan info + state + connected
            "/api/sessions/{$session}/status",
            "/api/sessions/{$session}/state",
            "/sessions/{$session}/status",
            "/session/{$session}/status",
            "/sessions/{$session}/state",
            "/session/{$session}/state",
            "/api/sessions/status?session={$this->e($session)}",  // query param
            "/api/state?session={$this->e($session)}",
        ];

        $out = $this->tryGet($try);
        if ($out['success']) {
            $j = $out['raw'];

            // beberapa build menaruh info di data
            if (isset($j['data']) && is_array($j['data'])) $j = $j['data'];

            $state = $j['state']     ?? $j['status']   ?? null;
            $conn  = $j['connected'] ?? null;

            if ($conn === null && is_string($state)) {
                $up = strtoupper($state);
                $conn = in_array($up, ['CONNECTED','READY','AUTHENTICATED','OPEN','ONLINE','RUNNING','WORKING'], true);
            }

            // beberapa build langsung mengikutkan qr
            $qr = $j['qr'] ?? $j['image'] ?? $j['qrcode'] ?? null;

            return [
                'success'   => true,
                'connected' => $conn,
                'state'     => $state,
                'qr'        => $qr,
                'error'     => null,
                'raw'       => $j,
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
        $body    = ['session' => $session];

        $try = [
            "/api/sessions/{$session}/start",   // ✅ sesuai Swagger
            '/api/sessions/start',
            '/api/session/start',
            '/api/start',
        ];

        $out = $this->tryPost($try, $body);
        if ($out['success']) {
            $j = $out['raw'];
            if (isset($j['data']) && is_array($j['data'])) $j = $j['data'];
            return [
                'success'   => true,
                'connected' => $j['connected'] ?? null,
                'state'     => $j['state'] ?? $j['status'] ?? null,
                'qr'        => $j['qr'] ?? null,
                'error'     => null,
                'raw'       => $j,
            ];
        }

        return ['success'=>false,'connected'=>null,'state'=>null,'qr'=>null,'error'=>$out['error'] ?: 'No start endpoint available','raw'=>$out['raw']];
    }

    public function sessionLogout(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);

        $try = [
            "/api/sessions/{$session}/logout",
            "/api/session/{$session}/logout",
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

        return ['success'=>false,'connected'=>null,'state'=>null,'qr'=>null,'error'=>$out['error'] ?: 'No logout endpoint available','raw'=>$out['raw']];
    }

    /** Ambil QR (binary/base64) dari endpoint auth/qr jika tersedia */
    public function qrImageBinary(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);
        $paths   = [
            "/api/{$session}/auth/qr",              // ✅ Swagger: GET /api/{session}/auth/qr
            "/api/sessions/{$session}/qr",
            "/api/session/{$session}/qr",
            "/api/qr?session={$this->e($session)}",
        ];

        foreach ($paths as $p) {
            $url = $this->url($p);
            try {
                $res = $this->client()->withHeaders(['Accept' => 'image/*,application/json'])->get($url);

                if ($res->successful()) {
                    // Kalau server balas image binary
                    $ctype = $res->header('Content-Type');
                    if ($ctype && str_starts_with($ctype, 'image/')) {
                        return ['success'=>true,'body'=>$res->body(),'ctype'=>$ctype];
                    }
                    // Kalau server balas JSON base64
                    $j = $res->json();
                    $b64 = $j['qr'] ?? $j['image'] ?? $j['qrcode'] ?? null;
                    if (is_string($b64) && preg_match('/^[A-Za-z0-9+\/=]+$/', $b64)) {
                        return ['success'=>true,'body'=>base64_decode($b64), 'ctype'=>'image/png'];
                    }
                }
            } catch (\Throwable $e) {
                Log::error('WAHA qrImage exception', ['url'=>$url,'err'=>$e->getMessage()]);
            }
        }

        return ['success'=>false,'body'=>null,'ctype'=>null];
    }

    /** Mulai sesi lalu coba ambil QR (gabungan) */
    public function qrStart(WahaSender|string $senderOrSession): array
    {
        $st = $this->sessionStart($senderOrSession);
        if (!($st['success'] ?? false)) return $st;

        // beberapa build langsung beri state + qr di start
        if (!empty($st['qr']) || !empty($st['state'])) return $st;

        // jika tidak, coba GET auth/qr
        $img = $this->qrImageBinary($senderOrSession);
        if ($img['success']) {
            return [
                'success'   => true,
                'connected' => false,
                'state'     => 'SCAN_QR_CODE',
                'qr'        => 'data:image/png;base64,'.base64_encode($img['body']),
                'error'     => null,
                'raw'       => ['from'=>'qrImageBinary'],
            ];
        }

        // fallback: status polling yang akan mengembalikan SCAN_QR_CODE
        return $this->sessionStatus($senderOrSession);
    }

    /** Info akun yang login (untuk isi number/display_name) */
    public function sessionMe(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);
        $paths = [
            "/api/sessions/{$session}/me",  // ✅ Swagger
            "/api/session/{$session}/me",
        ];
        $out = $this->tryGet($paths);
        if ($out['success']) {
            $j = $out['raw'];
            if (isset($j['data']) && is_array($j['data'])) $j = $j['data'];
            $number = $j['number'] ?? $j['phone'] ?? $j['id'] ?? null;
            if (is_string($number) && str_contains($number, '@')) {
                $number = explode('@', $number)[0];
            }
            $name = $j['name'] ?? $j['displayName'] ?? $j['pushName'] ?? null;
            return ['success'=>true,'number'=>$number,'display_name'=>$name,'raw'=>$j];
        }
        return ['success'=>false,'number'=>null,'display_name'=>null,'raw'=>$out['raw'],'error'=>$out['error'] ?? null];
    }

    /** Minta pairing code (Login with code) */
    public function requestAuthCode(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);
        $paths = [
            "/api/{$session}/auth/request-code",   // ✅ Swagger: POST /api/{session}/auth/request-code
            "/api/sessions/{$session}/auth/request-code",
        ];
        $out = $this->tryPost($paths, ['session'=>$session]);
        if ($out['success']) {
            $j = $out['raw']; if (isset($j['data'])) $j = $j['data'];
            $code = $j['code'] ?? $j['pairingCode'] ?? null;
            $state = $j['state'] ?? 'PAIR_WITH_CODE';
            return ['success'=>true,'code'=>$code,'state'=>$state,'raw'=>$j];
        }
        return ['success'=>false,'code'=>null,'state'=>null,'raw'=>$out['raw'],'error'=>$out['error'] ?? null];
    }

    /* =======================================================================
     | Helpers
     * ======================================================================= */

    protected function client()
    {
        $headers = ['User-Agent' => $this->userAgent];
        if (!empty($this->apiKey)) $headers['x-api-key'] = $this->apiKey;

        $c = Http::acceptJson()->timeout(30)->withHeaders($headers);
        return $this->insecure ? $c->withoutVerifying() : $c;
    }

    protected function tryGet(array $paths): array
    {
        $errors = [];
        foreach ($paths as $p) {
            $url = $this->url($p);
            try {
                $res = $this->client()->get($url);
                if ($res->successful()) {
                    return ['success'=>true,'raw'=>$res->json()];
                }
                $errors[] = "GET {$p} {$res->status()}";
                Log::warning('WAHA GET non-2xx', ['url'=>$url,'status'=>$res->status(),'body'=>mb_substr($res->body(),0,500)]);
            } catch (\Throwable $e) {
                $errors[] = "GET {$p} EXC";
                Log::error('WAHA GET exception', ['url'=>$url,'err'=>$e->getMessage()]);
            }
        }
        return ['success'=>false,'error'=>implode(' | ',$errors),'raw'=>null];
    }

    protected function tryPost(array $paths, array $payload): array
    {
        $errors = [];
        foreach ($paths as $p) {
            $url = $this->url($p);
            try {
                $res = $this->client()->post($url, $payload);
                if ($res->successful()) {
                    return ['success'=>true,'raw'=>$res->json()];
                }
                $errors[] = "POST {$p} {$res->status()}";
                Log::warning('WAHA POST non-2xx', ['url'=>$url,'status'=>$res->status(),'body'=>mb_substr($res->body(),0,500)]);
            } catch (\Throwable $e) {
                $errors[] = "POST {$p} EXC";
                Log::error('WAHA POST exception', ['url'=>$url,'err'=>$e->getMessage()]);
            }
        }
        return ['success'=>false,'error'=>implode(' | ',$errors),'raw'=>null];
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
        return (string)$senderOrSession;
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
        if (str_contains($raw, '@')) return $raw; // sudah termasuk domain (c.us / newsletter)

        // normalisasi Indonesia: 08xxxx -> 628xxxx
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
