<?php

namespace App\Services;

use App\Models\WahaSender;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $userAgent;
    protected bool $insecure;

    public function __construct()
    {
        $this->baseUrl   = rtrim((string) config('services.waha.url'), '/');   // ex: https://waha.matik.id
        $this->apiKey    = (string) config('services.waha.key');               // ex: matikmct
        $this->userAgent = (string) env('WAHA_UA', 'Matik Growth Hub');
        $this->insecure  = (bool) env('WAHA_INSECURE', false);
    }

    /* ============================================================
     |  Helpers
     * ============================================================ */

    protected function client(): PendingRequest
    {
        $c = Http::acceptJson()
            ->timeout(30)
            ->withHeaders([
                // WAHA biasanya membaca lowercase `x-api-key`
                'x-api-key'  => $this->apiKey,
                'User-Agent' => $this->userAgent,
            ]);

        return $this->insecure ? $c->withoutVerifying() : $c;
    }

    protected function url(string $path): string
    {
        $p = str_starts_with($path, '/') ? $path : "/{$path}";
        return $this->baseUrl . $p;
    }

    protected function digits(string $raw): string
    {
        $d = preg_replace('/\D+/', '', $raw) ?: $raw;
        if (str_starts_with($d, '0')) $d = '62' . substr($d, 1);
        return $d;
    }

    protected function jidCUs(string $digits): string
    {
        return str_contains($digits, '@') ? $digits : "{$digits}@c.us";
    }

    protected function resolveSession(WahaSender $sender): string
    {
        foreach (['session', 'session_name', 'sessionId', 'session_key'] as $f) {
            if (!empty($sender->{$f})) return (string)$sender->{$f};
        }
        return (string)($sender->session ?? 'default');
    }

    /* ============================================================
     |  Health
     * ============================================================ */
    public function health(): ?array
    {
        foreach (['/health', '/api/health'] as $p) {
            try {
                $r = $this->client()->get($this->url($p));
                if ($r->successful()) return $r->json();
            } catch (\Throwable $e) {
                Log::warning('WAHA health fail', ['url' => $this->url($p), 'err' => $e->getMessage()]);
            }
        }
        return null;
    }

    /* ============================================================
     |  Kirim Pesan (sinkron dengan client React lama)
     * ============================================================ */
    public function sendMessage(WahaSender $sender, string $recipient, string $message): ?array
    {
        $session = $this->resolveSession($sender);
        $digits  = $this->digits($recipient);

        $payload = [
            'chatId'  => $this->jidCUs($digits),
            'text'    => $message,
            'session' => $session,
        ];

        // urutan paling umum
        $paths = [
            '/api/sendText',                          // dipakai pada app React kamu
            '/api/send-text',
            "/api/{$session}/send-text",
            "/api/{$session}/sendMessage",
        ];

        foreach ($paths as $p) {
            try {
                $res = $this->client()->post($this->url($p), $payload);
                if ($res->successful()) return $res->json() ?: ['success' => true];
                Log::warning('WAHA send non-2xx', ['url' => $this->url($p), 'status' => $res->status(), 'body' => mb_substr($res->body(), 500)]);
            } catch (\Throwable $e) {
                Log::error('WAHA send exception', ['url' => $this->url($p), 'err' => $e->getMessage()]);
            }
        }
        return null;
    }

    /* ============================================================
     |  Session: START (QR)
     * ============================================================ */
    public function startSession(string $session): array
    {
        // Banyak varian WAHA/wrapper yang beredar — kita coba semuanya, berhenti saat ada 2xx
        $payloads = [
            // body JSON dengan nama session
            ['path' => '/api/start',                        'method' => 'post', 'body' => ['session' => $session]],
            ['path' => '/api/sessions/start',               'method' => 'post', 'body' => ['session' => $session]],
            ['path' => "/api/sessions/{$session}/start",    'method' => 'post', 'body' => []],
            ['path' => "/api/session/{$session}/start",     'method' => 'post', 'body' => []],
            ['path' => "/api/{$session}/start",             'method' => 'post', 'body' => []],
            // beberapa instalasi hanya GET
            ['path' => "/api/sessions/{$session}/start",    'method' => 'get',  'body' => []],
            ['path' => "/api/{$session}/start",             'method' => 'get',  'body' => []],
        ];

        foreach ($payloads as $w) {
            try {
                $url = $this->url($w['path']);
                $res = $w['method'] === 'get'
                    ? $this->client()->get($url)
                    : $this->client()->post($url, $w['body']);

                if ($res->successful()) {
                    return [
                        'ok'   => true,
                        'code' => $res->status(),
                        'raw'  => $res->json() ?? $res->body(),
                    ];
                }
                Log::warning('WAHA start non-2xx', ['url' => $url, 'status' => $res->status(), 'body' => mb_substr($res->body(), 0, 500)]);
            } catch (\Throwable $e) {
                Log::error('WAHA start exception', ['path' => $w['path'], 'err' => $e->getMessage()]);
            }
        }

        return ['ok' => false, 'code' => 502, 'raw' => null];
    }

    /* ============================================================
     |  Session: STATUS + QR
     * ============================================================ */
    public function sessionStatus(string $session): array
    {
        // beberapa implementasi menaruh QR & state di endpoint berbeda
        $candidates = [
            "/api/sessions/{$session}/status",
            "/api/session/{$session}/status",
            "/api/{$session}/status",
            "/api/sessions/{$session}/qr",
            "/api/session/{$session}/qr",
            "/api/{$session}/qr",
            // ada juga yang pakai /state
            "/api/sessions/{$session}/state",
            "/api/session/{$session}/state",
            "/api/{$session}/state",
        ];

        foreach ($candidates as $p) {
            try {
                $url = $this->url($p);
                $res = $this->client()->get($url);

                if ($res->successful()) {
                    $json = $res->json();

                    // mapping serbaguna
                    $state = $json['state'] ?? $json['status'] ?? $json['result'] ?? null;
                    $qr    = $json['qr'] ?? $json['qrcode'] ?? $json['dataUrl'] ?? $json['image'] ?? null;
                    if (!$qr && isset($json['data']) && is_array($json['data'])) {
                        $qr = $json['data']['qr'] ?? $json['data']['qrcode'] ?? null;
                        $state = $state ?? ($json['data']['state'] ?? null);
                    }

                    // normalisasi state
                    $normalized = strtoupper((string)$state);
                    if (!$normalized && $qr) $normalized = 'SCAN_QR_CODE';

                    return [
                        'ok'    => true,
                        'code'  => $res->status(),
                        'state' => $normalized ?: 'UNKNOWN',
                        'qr'    => $qr,          // bisa base64 dataURL atau raw string
                        'raw'   => $json,
                    ];
                }

                Log::warning('WAHA status non-2xx', ['url' => $url, 'status' => $res->status(), 'body' => mb_substr($res->body(), 0, 500)]);
            } catch (\Throwable $e) {
                Log::error('WAHA status exception', ['path' => $p, 'err' => $e->getMessage()]);
            }
        }

        return ['ok' => false, 'code' => 502, 'state' => 'UNKNOWN', 'qr' => null, 'raw' => null];
    }
}
