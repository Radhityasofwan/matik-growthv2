<?php

namespace App\Services;

use App\Models\WahaSender;
use Illuminate\Http\Client\Response;
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
        $this->baseUrl   = rtrim((string) config('services.waha.url', env('WAHA_URL')), '/'); // ex: https://waha.matik.id
        $this->apiKey    = (string) config('services.waha.key', env('WAHA_KEY'));            // ex: matikmct
        $this->userAgent = (string) env('WAHA_UA', 'Matik Growth Hub');
        $this->insecure  = (bool) env('WAHA_INSECURE', false);
    }

    /* ======================= Pesan teks ======================= */

    public function sendMessage(WahaSender $sender, string $recipient, string $message): ?array
    {
        $session = $this->resolveSession($sender);
        $chatId  = $this->jidCUs($this->digits($recipient));

        $payload = ['chatId' => $chatId, 'text' => $message, 'session' => $session];

        $paths = [
            '/api/sendText',
            '/api/send-text',
            "/api/{$this->safe($session)}/send-text",
            "/api/{$this->safe($session)}/sendMessage",
        ];

        return $this->tryPost($paths, $payload);
    }

    public function sendTemplate(WahaSender $sender, string $recipient, string $templateName, array $templateParams = []): ?array
    {
        $session = $this->resolveSession($sender);
        $chatId  = $this->jidCUs($this->digits($recipient));

        $payload = ['chatId' => $chatId, 'name' => $templateName, 'params' => $templateParams, 'session' => $session];

        $paths = [
            '/api/sendTemplate',
            '/api/send-template',
            "/api/{$this->safe($session)}/send-template",
            "/api/{$this->safe($session)}/template",
        ];

        return $this->tryPost($paths, $payload);
    }

    /* ======================= Health ======================= */

    public function health(): ?array
    {
        foreach (['/health', '/api/health'] as $p) {
            try {
                $r = $this->client()->get($this->url($p));
                if ($r->successful()) return $r->json();
            } catch (\Throwable $e) {
                Log::warning('WAHA health error', ['err' => $e->getMessage()]);
            }
        }
        return null;
    }

    /* ======================= SESSION APIs ======================= */

    /**
     * Kembalikan:
     * [
     *   success   => bool,
     *   state     => 'CONNECTED'|'QR'|'PAIRING'|'INITIALIZING'|'DISCONNECTED'|null,
     *   connected => bool|null,
     *   qr        => data-uri|null,
     *   error     => string|null,
     *   raw       => mixed
     * ]
     */
    public function sessionStatus(WahaSender $sender): array
    {
        $session = $this->resolveSession($sender);
        $errors  = [];

        // 1) status dgn query ?session=
        $statusQuery = [
            '/api/status',
            '/api/session/status',
            '/api/sessions/status',
            '/status',
            '/session/status',
            '/sessions/status',
            '/api/whatsapp/status', // beberapa implementasi
        ];
        foreach ($statusQuery as $p) {
            $r = $this->safeGet($p, ['session' => $session]);
            if (!$r) { $errors[] = "GET {$p} (exc)"; continue; }
            if ($r->ok()) {
                $norm = $this->normalizeStatus($r);
                if (!$norm['connected'] && $norm['qr'] === null) {
                    // coba ambil QR langsung
                    $qr = $this->tryGetQr($session);
                    if ($qr) $norm['qr'] = $qr;
                }
                return $norm;
            }
            $errors[] = "GET {$p} {$r->status()}";
        }

        // 2) status path /{session}/status
        $statusPath = [
            "/api/{$this->safe($session)}/status",
            "/api/session/{$this->safe($session)}/status",
            "/api/sessions/{$this->safe($session)}/status",
            "/{$this->safe($session)}/status",
            "/whatsapp/{$this->safe($session)}/status",
        ];
        foreach ($statusPath as $p) {
            $r = $this->safeGet($p);
            if (!$r) { $errors[] = "GET {$p} (exc)"; continue; }
            if ($r->ok()) {
                $norm = $this->normalizeStatus($r);
                if (!$norm['connected'] && $norm['qr'] === null) {
                    $qr = $this->tryGetQr($session);
                    if ($qr) $norm['qr'] = $qr;
                }
                return $norm;
            }
            $errors[] = "GET {$p} {$r->status()}";
        }

        // 3) fallback: langsung coba QR
        if ($qr = $this->tryGetQr($session)) {
            return ['success' => true, 'state' => 'QR', 'connected' => false, 'qr' => $qr, 'error' => null, 'raw' => null];
        }

        return ['success' => false, 'state' => null, 'connected' => null, 'qr' => null, 'error' => implode(' | ', $errors), 'raw' => null];
    }

    /** Memulai / restart sesi lalu coba ambil QR. */
    public function sessionStart(WahaSender $sender): array
    {
        $session = $this->resolveSession($sender);
        $payload = ['session' => $session];
        $errors  = [];

        $starts = [
            '/api/start',
            '/api/session/start',
            '/api/sessions/start',
            "/api/{$this->safe($session)}/start",
            "/session/{$this->safe($session)}/start",
            "/sessions/{$this->safe($session)}/start",
            "/api/{$this->safe($session)}/create",
            "/api/{$this->safe($session)}/connect",
            '/api/scan/start',        // beberapa implementasi custom
            '/api/qr/start',
        ];

        foreach ($starts as $p) {
            $r = $this->safePost($p, $payload);
            if (!$r) { $errors[] = "POST {$p} (exc)"; continue; }

            if ($r->successful() || in_array($r->status(), [200,201,202,204], true)) {
                // coba ambil QR segera
                if ($qr = $this->tryGetQr($session)) {
                    return ['success' => true, 'state' => 'QR', 'connected' => null, 'qr' => $qr, 'error' => null, 'raw' => $this->safeJson($r)];
                }
                // kalau belum ada QR, kembalikan sukses start
                return ['success' => true, 'state' => null, 'connected' => null, 'qr' => null, 'error' => null, 'raw' => $this->safeJson($r)];
            }
            $errors[] = "POST {$p} {$r->status()}";
        }

        return ['success' => false, 'state' => null, 'connected' => null, 'qr' => null, 'error' => implode(' | ', $errors), 'raw' => null];
    }

    public function sessionLogout(WahaSender $sender): array
    {
        $session = $this->resolveSession($sender);
        $payload = ['session' => $session];

        $paths = [
            '/api/logout',
            '/api/session/logout',
            '/api/sessions/logout',
            "/api/{$this->safe($session)}/logout",
            "/session/{$this->safe($session)}/logout",
        ];

        foreach ($paths as $p) {
            $r = $this->safePost($p, $payload);
            if ($r && ($r->successful() || $r->status() === 204)) {
                return ['success' => true, 'state' => 'DISCONNECTED', 'connected' => false, 'qr' => null, 'error' => null, 'raw' => $this->safeJson($r)];
            }
        }

        return ['success' => false, 'state' => null, 'connected' => null, 'qr' => null, 'error' => 'All logout paths failed', 'raw' => null];
    }

    /* ======================= Helpers ======================= */

    protected function client()
    {
        $c = Http::timeout(30)
            ->acceptJson()
            ->withHeaders([
                'x-api-key'  => $this->apiKey,      // header case-insensitive
                'User-Agent' => $this->userAgent,
            ]);
        return $this->insecure ? $c->withoutVerifying() : $c;
    }

    protected function url(string $p): string
    {
        if (str_starts_with($p, 'http://') || str_starts_with($p, 'https://')) return $p;
        return $this->baseUrl . (str_starts_with($p, '/') ? $p : "/{$p}");
    }

    protected function safeGet(string $path, array $query = []): ?Response
    {
        try { return $this->client()->get($this->url($path), $query); }
        catch (\Throwable $e) {
            Log::error('WAHA GET exception', ['url' => $this->url($path), 'err' => $e->getMessage()]);
            return null;
        }
    }

    protected function safePost(string $path, array $payload = []): ?Response
    {
        try { return $this->client()->post($this->url($path), $payload); }
        catch (\Throwable $e) {
            Log::error('WAHA POST exception', ['url' => $this->url($path), 'err' => $e->getMessage()]);
            return null;
        }
    }

    protected function tryPost(array $paths, array $payload): ?array
    {
        foreach ($paths as $p) {
            $r = $this->safePost($p, $payload);
            if (!$r) continue;

            if ($r->successful()) {
                $j = $this->safeJson($r);
                Log::debug('WAHA OK', ['url' => $this->url($p), 'json' => $j]);
                return $j ?: ['success' => true];
            }

            Log::warning('WAHA non-2xx', ['url' => $this->url($p), 'status' => $r->status(), 'body' => mb_substr($r->body(), 0, 500)]);
        }
        return null;
    }

    /** Ambil QR di banyak variasi endpoint; return data-uri atau null */
    protected function tryGetQr(string $session): ?string
    {
        // JSON or image (query style)
        $qrQuery = [
            '/api/qr',
            '/api/session/qr',
            '/api/sessions/qr',
            '/qr',
            '/api/whatsapp/qr',
        ];
        foreach ($qrQuery as $p) {
            $r = $this->safeGet($p, ['session' => $session, 'image' => 1]);
            if ($uri = $this->extractQr($r)) return $uri;
        }

        // path style
        $qrPath = [
            "/api/{$this->safe($session)}/qr",
            "/session/{$this->safe($session)}/qr",
            "/sessions/{$this->safe($session)}/qr",
            "/{$this->safe($session)}/qr",
            "/whatsapp/{$this->safe($session)}/qr",
            "/api/{$this->safe($session)}/qrcode",
            "/api/{$this->safe($session)}/qr-code",
        ];
        foreach ($qrPath as $p) {
            $r = $this->safeGet($p);
            if ($uri = $this->extractQr($r)) return $uri;
        }

        return null;
    }

    /** Tarik data-uri dari response (image langsung atau JSON {qr|qrCode|image}) */
    protected function extractQr(?Response $r): ?string
    {
        if (!$r || !$r->ok()) return null;

        $ct = strtolower($r->header('Content-Type', ''));
        if (str_starts_with($ct, 'image/')) {
            $b64 = base64_encode($r->body());
            return "data:{$ct};base64,{$b64}";
        }

        $j = $this->safeJson($r);
        if (!is_array($j)) return null;

        $raw = $j['qr'] ?? $j['qrCode'] ?? $j['image'] ?? null;
        if (!$raw) return null;

        return str_starts_with($raw, 'data:image') ? $raw : "data:image/png;base64,{$raw}";
    }

    protected function normalizeStatus(Response $r): array
    {
        $j = $this->safeJson($r) ?: [];

        $connected = $j['connected'] ?? $j['isConnected'] ?? $j['is_logged_in'] ?? null;
        if (is_string($connected)) $connected = filter_var($connected, FILTER_VALIDATE_BOOLEAN);

        $state = $j['state'] ?? $j['status'] ?? $j['sessionStatus'] ?? ($connected === true ? 'CONNECTED' : null);
        $rawQr = $j['qr'] ?? $j['qrCode'] ?? $j['image'] ?? null;
        $qr    = is_string($rawQr) ? (str_starts_with($rawQr, 'data:image') ? $rawQr : "data:image/png;base64,{$rawQr}") : null;

        return [
            'success'   => true,
            'state'     => is_string($state) ? strtoupper($state) : $state,
            'connected' => is_bool($connected) ? $connected : ($state && strtoupper($state) === 'CONNECTED'),
            'qr'        => $qr,
            'error'     => null,
            'raw'       => $j,
        ];
    }

    protected function safeJson(Response $r): mixed
    {
        try { return $r->json(); } catch (\Throwable) { return null; }
    }

    /* ---------------- misc utils ---------------- */

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
        foreach (['session_name','session','sessionId','session_key'] as $f) {
            if (!empty($sender->{$f})) return (string) $sender->{$f};
        }
        $slug = preg_replace('~[^a-z0-9]+~', '-', strtolower($sender->name ?? 'default'));
        $slug = trim($slug, '-') ?: 'default';
        return $slug;
    }

    protected function safe(string $s): string
    {
        return rawurlencode($s);
    }
}
