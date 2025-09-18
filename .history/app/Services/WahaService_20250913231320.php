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
        // Contoh: https://waha.matik.id (tanpa trailing slash)
        $this->baseUrl   = rtrim((string) config('services.waha.url', env('WAHA_URL')), '/');
        $this->apiKey    = (string) config('services.waha.key', env('WAHA_KEY'));
        $this->userAgent = (string) env('WAHA_UA', 'Matik Growth Hub');
        $this->insecure  = (bool) env('WAHA_INSECURE', false);
    }

    /* ----------------------------------------------------------------------
     |  PESAN TEKS (kompatibel dgn React /api/sendText)
     |  Header: x-api-key
     |  Body  : { chatId, text, session }
     * ---------------------------------------------------------------------*/
    public function sendMessage(WahaSender $sender, string $recipient, string $message): ?array
    {
        $session = $this->resolveSession($sender);
        $digits  = $this->digits($recipient);
        $chatId  = $this->jidCUs($digits);

        $payload = [
            'chatId'  => $chatId,
            'text'    => $message,
            'session' => $session,
        ];

        $paths = [
            '/api/sendText',
            '/api/send-text',
            "/api/{$this->safe($session)}/send-text",
            "/api/{$this->safe($session)}/sendMessage",
        ];

        return $this->tryPost($paths, $payload);
    }

    /** Optional: kirim via template */
    public function sendTemplate(WahaSender $sender, string $recipient, string $templateName, array $templateParams = []): ?array
    {
        $session = $this->resolveSession($sender);
        $digits  = $this->digits($recipient);
        $chatId  = $this->jidCUs($digits);

        $payload = [
            'chatId'  => $chatId,
            'name'    => $templateName,
            'params'  => $templateParams,
            'session' => $session,
        ];

        $paths = [
            '/api/sendTemplate',
            '/api/send-template',
            "/api/{$this->safe($session)}/send-template",
            "/api/{$this->safe($session)}/template",
        ];

        return $this->tryPost($paths, $payload);
    }

    /* ----------------------------------------------------------------------
     |  HEALTH (opsional)
     * ---------------------------------------------------------------------*/
    public function health(): ?array
    {
        foreach (['/health', '/api/health'] as $p) {
            try {
                $res = $this->client()->get($this->url($p));
                if ($res->successful()) return $res->json();
            } catch (\Throwable $e) {
                Log::warning('WAHA health error', ['err' => $e->getMessage()]);
            }
        }
        return null;
    }

    /* ----------------------------------------------------------------------
     |  KENDALI SESI (STATUS / START / LOGOUT)
     |  Semua method mengembalikan array normalisasi:
     |  [
     |    'success'  => bool,
     |    'state'    => 'CONNECTED'|'QR'|'PAIRING'|'INITIALIZING'|'DISCONNECTED'|null,
     |    'connected'=> bool|null,
     |    'qr'       => 'data:image/png;base64,...'|null,
     |    'raw'      => mixed  // payload asli dari server (untuk debug)
     |  ]
     * ---------------------------------------------------------------------*/

    /** Cek status sesi + (jika ada) QR */
    public function sessionStatus(WahaSender $sender): array
    {
        $session = $this->resolveSession($sender);

        // 1) Coba endpoint status berbasis query ?session=
        $statusPathsQuery = [
            '/api/status',
            '/api/session/status',
            '/api/sessions/status',
            '/status',
            '/session/status',
            '/sessions/status',
        ];

        foreach ($statusPathsQuery as $p) {
            $res = $this->safeGet($p, ['session' => $session]);
            if ($res && $res->ok()) {
                $norm = $this->normalizeStatus($res);
                if ($norm['qr'] === null && $norm['connected'] === false) {
                    // 2) Jika belum connected & belum ada QR, coba ambil QR terpisah
                    $qr = $this->tryGetQr($session);
                    if ($qr) $norm['qr'] = $qr;
                }
                return $norm;
            }
        }

        // 3) Coba endpoint status berbasis path /{session}/status
        $statusPathsPath = [
            "/api/{$this->safe($session)}/status",
            "/api/session/{$this->safe($session)}/status",
            "/api/sessions/{$this->safe($session)}/status",
            "/{$this->safe($session)}/status",
        ];
        foreach ($statusPathsPath as $p) {
            $res = $this->safeGet($p);
            if ($res && $res->ok()) {
                $norm = $this->normalizeStatus($res);
                if ($norm['qr'] === null && $norm['connected'] === false) {
                    $qr = $this->tryGetQr($session);
                    if ($qr) $norm['qr'] = $qr;
                }
                return $norm;
            }
        }

        // 4) Terakhir: langsung coba ambil QR (kadang server langsung sediakan QR)
        if ($qr = $this->tryGetQr($session)) {
            return [
                'success'   => true,
                'state'     => 'QR',
                'connected' => false,
                'qr'        => $qr,
                'raw'       => null,
            ];
        }

        return [
            'success'   => false,
            'state'     => null,
            'connected' => null,
            'qr'        => null,
            'raw'       => null,
        ];
    }

    /** Mulai / (re)start sesi supaya QR muncul */
    public function sessionStart(WahaSender $sender): array
    {
        $session = $this->resolveSession($sender);

        $payload = ['session' => $session];

        $paths = [
            '/api/start',
            '/api/session/start',
            '/api/sessions/start',
            "/api/{$this->safe($session)}/start",
            "/session/{$this->safe($session)}/start",
        ];

        foreach ($paths as $p) {
            $res = $this->safePost($p, $payload);
            if (!$res) continue;

            if ($res->successful() || $res->status() === 202 || $res->status() === 204) {
                // Banyak server langsung siap di-poll status/QR setelah start
                $qr = $this->tryGetQr($session);
                return [
                    'success'   => true,
                    'state'     => $qr ? 'QR' : null,
                    'connected' => null,
                    'qr'        => $qr,
                    'raw'       => $this->safeJson($res),
                ];
            }

            Log::warning('WAHA start non-2xx', [
                'url' => $this->url($p),
                'status' => $res->status(),
                'body' => mb_substr($res->body(), 0, 500),
            ]);
        }

        return [
            'success'   => false,
            'state'     => null,
            'connected' => null,
            'qr'        => null,
            'raw'       => null,
        ];
    }

    /** Logout / destroy sesi */
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
            $res = $this->safePost($p, $payload);
            if ($res && ($res->successful() || $res->status() === 204)) {
                return [
                    'success'   => true,
                    'state'     => 'DISCONNECTED',
                    'connected' => false,
                    'qr'        => null,
                    'raw'       => $this->safeJson($res),
                ];
            }
        }

        return [
            'success'   => false,
            'state'     => null,
            'connected' => null,
            'qr'        => null,
            'raw'       => null,
        ];
    }

    /* ======================================================================
     |  Helpers
     * =====================================================================*/

    protected function client()
    {
        $c = Http::timeout(30)
            ->acceptJson()
            ->withHeaders([
                'x-api-key'  => $this->apiKey,  // WAHA kamu memakai header ini
                'User-Agent' => $this->userAgent,
            ]);

        return $this->insecure ? $c->withoutVerifying() : $c;
    }

    protected function url(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        $p = str_starts_with($path, '/') ? $path : "/{$path}";
        return $this->baseUrl . $p;
    }

    /** GET aman (return null bila exception) */
    protected function safeGet(string $path, array $query = []): ?Response
    {
        try {
            return $this->client()->get($this->url($path), $query);
        } catch (\Throwable $e) {
            Log::error('WAHA GET error', ['url' => $this->url($path), 'err' => $e->getMessage()]);
            return null;
        }
    }

    /** POST aman (return null bila exception) */
    protected function safePost(string $path, array $payload = []): ?Response
    {
        try {
            return $this->client()->post($this->url($path), $payload);
        } catch (\Throwable $e) {
            Log::error('WAHA POST error', ['url' => $this->url($path), 'err' => $e->getMessage()]);
            return null;
        }
    }

    protected function tryPost(array $paths, array $payload): ?array
    {
        foreach ($paths as $p) {
            $res = $this->safePost($p, $payload);
            if (!$res) continue;

            if ($res->successful()) {
                $json = $this->safeJson($res);
                Log::debug('WAHA OK', ['url' => $this->url($p), 'json' => $json]);
                return $json ?: ['success' => true];
            }

            Log::warning('WAHA non-2xx', [
                'url'    => $this->url($p),
                'status' => $res->status(),
                'body'   => mb_substr($res->body(), 0, 500),
            ]);
        }
        return null;
    }

    /** Ambil QR dari berbagai kemungkinan endpoint; return data-uri atau null */
    protected function tryGetQr(string $session): ?string
    {
        // 1) JSON endpoint yang mengembalikan base64
        $qrJsonPaths = [
            '/api/qr',
            '/api/session/qr',
            '/api/sessions/qr',
            '/qr',
        ];
        foreach ($qrJsonPaths as $p) {
            $res = $this->safeGet($p, ['session' => $session, 'image' => 1]);
            if ($res && $res->ok()) {
                // Bisa berupa JSON {qr:'base64'} atau langsung image bytes
                $ctype = strtolower($res->header('Content-Type', ''));
                if (str_starts_with($ctype, 'application/json')) {
                    $j = $this->safeJson($res);
                    $raw = $j['qr'] ?? $j['qrCode'] ?? $j['image'] ?? null;
                    if (is_string($raw) && str_starts_with($raw, 'data:image')) return $raw;
                    if (is_string($raw)) return $this->toDataUri($raw, 'image/png');
                }

                if (str_starts_with($ctype, 'image/')) {
                    return $this->imageResponseToDataUri($res);
                }
            }
        }

        // 2) Path-based image endpoint: /{session}/qr
        $qrImgPaths = [
            "/api/{$this->safe($session)}/qr",
            "/session/{$this->safe($session)}/qr",
            "/sessions/{$this->safe($session)}/qr",
            "/{$this->safe($session)}/qr",
        ];
        foreach ($qrImgPaths as $p) {
            $res = $this->safeGet($p);
            if ($res && $res->ok()) {
                $ctype = strtolower($res->header('Content-Type', ''));
                if (str_starts_with($ctype, 'image/')) {
                    return $this->imageResponseToDataUri($res);
                }
                $j = $this->safeJson($res);
                $raw = $j['qr'] ?? $j['qrCode'] ?? null;
                if ($raw) return $this->toDataUri($raw, 'image/png');
            }
        }

        return null;
    }

    /** Normalisasi berbagai bentuk payload status menjadi struktur baku */
    protected function normalizeStatus(Response $res): array
    {
        $json = $this->safeJson($res);

        // Deteksi "connected" & "state"
        $connected = $json['connected'] ?? $json['isConnected'] ?? $json['is_logged_in'] ?? null;
        if (is_string($connected)) {
            $connected = filter_var($connected, FILTER_VALIDATE_BOOLEAN);
        }

        $state = $json['state']
            ?? $json['status']
            ?? $json['sessionStatus']
            ?? ($connected === true ? 'CONNECTED' : null);

        // deteksi QR bila ada di payload status
        $qrRaw = $json['qr'] ?? $json['qrCode'] ?? $json['image'] ?? null;
        $qr = null;
        if (is_string($qrRaw)) {
            $qr = str_starts_with($qrRaw, 'data:image')
                ? $qrRaw
                : $this->toDataUri($qrRaw, 'image/png');
        }

        return [
            'success'   => true,
            'state'     => is_string($state) ? strtoupper($state) : $state,
            'connected' => is_bool($connected) ? $connected : ($state && strtoupper($state) === 'CONNECTED'),
            'qr'        => $qr,
            'raw'       => $json,
        ];
    }

    protected function imageResponseToDataUri(Response $res): string
    {
        $ctype = $res->header('Content-Type', 'image/png');
        $b64   = base64_encode($res->body());
        return "data:{$ctype};base64,{$b64}";
    }

    protected function toDataUri(string $base64, string $mime = 'image/png'): string
    {
        // Terima input sudah base64 atau image raw biner yg sudah base64-encoded
        if (!preg_match('~^data:image/[^;]+;base64,~', $base64)) {
            // Jika bukan data-uri, bungkus
            return "data:{$mime};base64,{$base64}";
        }
        return $base64;
    }

    protected function safe(string $s): string
    {
        return rawurlencode($s);
    }

    protected function digits(string $raw): string
    {
        $d = preg_replace('/\D+/', '', $raw) ?: $raw;
        if (str_starts_with($d, '0')) $d = '62' . substr($d, 1);
        return $d;
    }

    protected function jidCUs(string $digits): string
    {
        return str_contains($digits, '@') ? $digits : $digits . '@c.us';
    }

    protected function resolveSession(WahaSender $sender): string
    {
        // Urutan preferensi field
        foreach (['session_name', 'session', 'sessionId', 'session_key'] as $f) {
            if (!empty($sender->{$f})) {
                return (string) $sender->{$f};
            }
        }

        // Fallback: slug dari nama (tidak mengubah DB; controller yg bertugas menyimpan)
        $slug = preg_replace('~[^a-z0-9]+~', '-', strtolower($sender->name ?? 'default'));
        $slug = trim($slug, '-') ?: 'default';
        return $slug;
    }

    protected function safeJson(Response $res): mixed
    {
        try { return $res->json(); } catch (\Throwable) { return null; }
    }
}
