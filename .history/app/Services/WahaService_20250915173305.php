<?php

namespace App\Services;

use App\Models\WahaSender;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaService
{
    protected string $baseUrl;         // contoh: https://waha.matik.id (TANPA /api)
    protected ?string $apiKey;         // opsional; isi jika server pakai x-api-key
    protected string $userAgent;
    protected bool $insecure;

    public function __construct()
    {
        $this->baseUrl   = rtrim((string) config('services.waha.url'), '/');
        $this->apiKey    = config('services.waha.key'); // bisa null
        $this->userAgent = (string) env('WAHA_UA', 'Matik Growth Hub');
        $this->insecure  = (bool) env('WAHA_INSECURE', false);

        if ($this->baseUrl === '' || !preg_match('~^https?://~i', $this->baseUrl)) {
            // Tanpa base URL yang valid, hampir semua call akan EXC. Lebih baik fail-fast dengan pesan jelas.
            throw new \RuntimeException("WAHA_URL belum di-set atau tidak valid. Set .env WAHA_URL (contoh: https://waha.matik.id) lalu php artisan config:clear");
        }
    }

    /* =========================================================================
     |  SEND TEXT
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
     |  SESSION / QR — bentuk baku:
     |  ['success'=>bool,'connected'=>?bool,'state'=>?string,'qr'=>?string,'error'=>?string,'raw'=>mixed]
     * ========================================================================= */

   // di WahaService
public function sessionStatus(WahaSender|string $senderOrSession): array
{
    $session = $this->asSession($senderOrSession);

    $url = $this->baseUrl . "/api/sessions/{$this->e($session)}";
    try {
        $res = $this->client()->get($url);
        if (!$res->successful()) {
            return [
                'success'=>false,
                'error'=>"HTTP {$res->status()}",
                'raw'=>$res->json()
            ];
        }

        $json = $res->json() ?? [];

        // coba baca state dari berbagai kemungkinan key
        $state = $json['state']
              ?? $json['status']
              ?? ($json['session'] ?? null);

        return [
            'success'   => true,
            'connected' => in_array(strtoupper((string)$state), ['CONNECTED','READY','AUTHENTICATED']),
            'state'     => $state,
            'qr'        => $json['qr'] ?? $json['qrcode'] ?? null,
            'error'     => null,
            'raw'       => $json,
        ];
    } catch (\Throwable $e) {
        return ['success'=>false, 'error'=>$e->getMessage(), 'raw'=>null];
    }
}

public function createSession(string $session): array
{
    $url = $this->baseUrl . "/api/sessions";
    try {
        $res = $this->client()->post($url, ['name'=>$session]);
        return ['success'=>$res->successful(), 'raw'=>$res->json()];
    } catch (\Throwable $e) {
        return ['success'=>false, 'error'=>$e->getMessage(), 'raw'=>null];
    }
}

public function sessionStart(WahaSender|string $senderOrSession): array
{
    $session = $this->asSession($senderOrSession);

    // pastikan session sudah ada
    $check = $this->sessionStatus($session);
    if (!$check['success'] && str_contains($check['error'] ?? '', '404')) {
        $this->createSession($session);
    }

    $url = $this->baseUrl . "/api/sessions/{$this->e($session)}/start";
    try {
        $res = $this->client()->post($url);
        $json = $res->json();
        return [
            'success'   => $res->successful(),
            'connected' => $json['state'] === 'CONNECTED',
            'state'     => $json['state'] ?? null,
            'qr'        => $json['qr'] ?? null,
            'error'     => $res->successful() ? null : 'Start failed',
            'raw'       => $json,
        ];
    } catch (\Throwable $e) {
        return ['success'=>false, 'error'=>$e->getMessage(), 'raw'=>null];
    }
}

    public function sessionLogout(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);

        $try = [
            "/api/sessions/{$this->seg($session)}/logout",
            "/api/session/{$this->seg($session)}/logout",
            "/sessions/{$this->seg($session)}/logout",
            "/session/{$this->seg($session)}/logout",
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

    public function qrStatus(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);

        $try = [
            "/api/sessions/{$this->seg($session)}/qr",
            "/api/session/{$this->seg($session)}/qr",
            "/sessions/{$this->seg($session)}/qr",
            "/session/{$this->seg($session)}/qr",
            "/api/qr?session={$this->e($session)}",
            "/qr?session={$this->e($session)}",
        ];

        $out = $this->tryGet($try);
        if ($out['success']) {
            $json = $out['raw'] ?? [];
            $qr   = $json['qr'] ?? $json['image'] ?? $json['qrcode'] ?? null;

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

    /** Start + fallback polling QR singkat (3x) */
    public function qrStart(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);

        $start = $this->sessionStart($session);
        if (!$start['success']) {
            return $start; // kirimkan alasan gagalnya supaya UI bisa tampil jelas
        }
        if (!empty($start['qr'])) {
            return $start;
        }

        for ($i = 0; $i < 3; $i++) {
            usleep(250 * 1000);
            $qr = $this->qrStatus($session);
            if ($qr['success'] && !empty($qr['qr'])) {
                return [
                    'success'   => true,
                    'connected' => $qr['connected'],
                    'state'     => $qr['state'],
                    'qr'        => $qr['qr'],
                    'error'     => null,
                    'raw'       => ['start' => $start['raw'], 'qr' => $qr['raw']],
                ];
            }
        }
        return $start;
    }

    /* =======================================================================
     | Helpers
     * ======================================================================= */

    /** HTTP client dengan retry kecil + TLS opsional */
    protected function client()
    {
        $headers = ['User-Agent' => $this->userAgent];
        if (!empty($this->apiKey)) $headers['x-api-key'] = $this->apiKey;

        $c = Http::acceptJson()
            ->retry(2, 250) // retry ringan
            ->timeout(30)
            ->withHeaders($headers);

        return $this->insecure ? $c->withoutVerifying() : $c;
    }

    /** Try GET banyak path; kumpulkan alasan gagal yang JELAS */
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
                $snippet = trim(mb_substr($res->body() ?? '', 0, 180));
                $errors[] = "GET {$p} HTTP {$res->status()}" . ($snippet ? " BODY: {$snippet}" : "");
                Log::warning('WAHA GET non-2xx', ['url' => $url, 'status' => $res->status(), 'body' => $snippet]);
            } catch (\Throwable $e) {
                $errors[] = "GET {$p} EXC: " . $e->getMessage();
                Log::error('WAHA GET exception', ['url' => $url, 'err' => $e->getMessage()]);
            }
        }
        return ['success' => false, 'error' => implode(' | ', $errors), 'raw' => null];
    }

    /** Try POST banyak path; kumpulkan alasan gagal yang JELAS */
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
                $snippet = trim(mb_substr($res->body() ?? '', 0, 180));
                $errors[] = "POST {$p} HTTP {$res->status()}" . ($snippet ? " BODY: {$snippet}" : "");
                Log::warning('WAHA POST non-2xx', ['url' => $url, 'status' => $res->status(), 'body' => $snippet]);
            } catch (\Throwable $e) {
                $errors[] = "POST {$p} EXC: " . $e->getMessage();
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
        $s = $senderOrSession instanceof WahaSender
            ? $this->resolveSession($senderOrSession)
            : (string) $senderOrSession;

        return $this->normalizeSessionKey($s);
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
        if (str_contains($raw, '@')) return $raw;

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

    /** pastikan key session aman untuk path/query (alnum, . _ -) */
    protected function normalizeSessionKey(string $s): string
    {
        $s = trim($s) ?: 'default';
        $s = preg_replace('/[^A-Za-z0-9._-]+/', '-', $s);
        return substr($s, 0, 64);
    }

    /** encode segmen path aman */
    protected function seg(string $s): string
    {
        // encode tapi pertahankan karakter yang umum (.-_)
        return str_replace('%2F','/', rawurlencode($s));
    }
}
