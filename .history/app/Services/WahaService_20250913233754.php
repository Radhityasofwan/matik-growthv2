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
        $this->baseUrl   = rtrim((string) config('services.waha.url', env('WAHA_URL')), '/');
        $this->apiKey    = (string) config('services.waha.key', env('WAHA_KEY'));
        $this->userAgent = (string) env('WAHA_UA', 'Matik Growth Hub');
        $this->insecure  = (bool) env('WAHA_INSECURE', false);
    }

    /* ======================= Pesan teks ======================= */

    public function sendMessage(WahaSender $sender, string $recipient, string $message): ?array
    {
        $session = $this->resolveSession($sender);
        $chatId  = $this->jidCUs($this->digits($recipient));

        $payload = [
            'chatId'      => $chatId,
            'text'        => $message,
            // kirim semua alias biar kompatibel
            'session'     => $session,
            'name'        => $session,
            'sessionName' => $session,
        ];

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

        $payload = [
            'chatId'      => $chatId,
            'name'        => $templateName,
            'params'      => $templateParams,
            'session'     => $session,
            'sessionName' => $session,
        ];

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
        foreach (['/health','/api/health'] as $p) {
            try {
                $r = $this->client()->get($this->url($p));
                if ($r->successful()) return $r->json();
            } catch (\Throwable $e) {
                Log::warning('WAHA health error', ['err'=>$e->getMessage()]);
            }
        }
        return null;
    }

    /* ======================= SESSION ======================= */

    public function sessionStatus(WahaSender $sender): array
    {
        $session = $this->resolveSession($sender);
        $errors  = [];

        // query style: ?name=...
        foreach (['/api/sessions/status','/api/session/status'] as $p) {
            $r = $this->safeGet($p, ['name'=>$session]);
            if ($r?->ok()) return $this->normalizeStatusPlusQr($r, $session);
            $errors[] = "GET {$p} ".($r?->status() ?? 'exc');
        }

        // query style: ?session=...
        foreach (['/api/status','/status'] as $p) {
            $r = $this->safeGet($p, ['session'=>$session]);
            if ($r?->ok()) return $this->normalizeStatusPlusQr($r, $session);
            $errors[] = "GET {$p} ".($r?->status() ?? 'exc');
        }

        // path style
        foreach ([
            "/api/sessions/{$this->safe($session)}/status",
            "/api/session/{$this->safe($session)}/status",
            "/api/{$this->safe($session)}/status",
            "/session/{$this->safe($session)}/status",
        ] as $p) {
            $r = $this->safeGet($p);
            if ($r?->ok()) return $this->normalizeStatusPlusQr($r, $session);
            $errors[] = "GET {$p} ".($r?->status() ?? 'exc');
        }

        // terakhir, coba QR langsung
        if ($qr = $this->tryGetQr($session)) {
            return ['success'=>true,'state'=>'QR','connected'=>false,'qr'=>$qr,'error'=>null,'raw'=>null];
        }

        return ['success'=>false,'state'=>null,'connected'=>null,'qr'=>null,'error'=>implode(' | ',$errors),'raw'=>null];
    }

    public function sessionStart(WahaSender $sender): array
{
    // 1) Pastikan nama sesi “aman” (beberapa server menolak terlalu panjang/karakter aneh)
    $raw = $this->resolveSession($sender);
    $session = substr(preg_replace('~[^a-z0-9\-_.]+~i', '-', $raw), 0, 32);
    if ($session === '' || $session === '-') {
        $session = 'default';
    }

    // 2) Kandidat endpoint yang sering muncul di berbagai build WAHA
    $endpoints = [
        '/api/sessions/start',               // paling umum
        '/api/session/start',
        '/api/start',
        "/api/{$this->safe($session)}/start",
        "/session/{$this->safe($session)}/start",
        "/sessions/{$this->safe($session)}/start",
        '/api/session',                      // beberapa build: POST {name} ke /api/session
        '/api/sessions',                     // beberapa build: POST {name} ke /api/sessions
    ];

    // 3) Payload kombinasi (JSON atau FORM)
    $jsonPayloads = [
        ['name' => $session],
        ['session' => $session],
        ['sessionName' => $session],
        ['name' => $session, 'session' => $session, 'sessionName' => $session],
    ];

    $errors = [];

    // Helper untuk menilai sukses
    $isOk = function (?Response $r): bool {
        if (!$r) return false;
        if ($r->successful()) return true;
        // beberapa server balas 204 No Content saat sukses
        return $r->status() === 204;
    };

    /* ------------------- A. POST JSON & FORM ------------------- */
    foreach ($endpoints as $p) {
        foreach ($jsonPayloads as $pl) {
            // JSON
            try {
                $r = $this->client()->post($this->url($p), $pl);
                if ($isOk($r)) {
                    Log::info('WAHA start OK(json)', ['endpoint'=>$p, 'status'=>$r->status()]);
                    $qr = $this->tryGetQr($session);
                    return ['success'=>true,'state'=>$qr ? 'QR' : null,'connected'=>null,'qr'=>$qr,'error'=>null,'raw'=>$this->safeJson($r)];
                } else {
                    Log::warning('WAHA start non-2xx', ['url'=>$this->url($p), 'status'=>$r->status(), 'body'=>mb_substr($r->body(),0,500)]);
                }
            } catch (\Throwable $e) {
                Log::error('WAHA start exception(json)', ['url'=>$this->url($p), 'err'=>$e->getMessage()]);
            }

            // FORM
            try {
                $r = $this->client()->asForm()->post($this->url($p), $pl);
                if ($isOk($r)) {
                    Log::info('WAHA start OK(form)', ['endpoint'=>$p, 'status'=>$r->status()]);
                    $qr = $this->tryGetQr($session);
                    return ['success'=>true,'state'=>$qr ? 'QR' : null,'connected'=>null,'qr'=>$qr,'error'=>null,'raw'=>$this->safeJson($r)];
                } else {
                    Log::warning('WAHA start non-2xx', ['url'=>$this->url($p), 'status'=>$r->status(), 'body'=>mb_substr($r->body(),0,500)]);
                }
            } catch (\Throwable $e) {
                Log::error('WAHA start exception(form)', ['url'=>$this->url($p), 'err'=>$e->getMessage()]);
            }
        }
    }

    /* ------------------- B. POST dengan query ?name= ------------------- */
    // Beberapa server hanya merespons kalau `name` dikirim via querystring, bukan body.
    $queryVariants = [
        ['name' => $session],
        ['session' => $session],
        ['sessionName' => $session],
    ];
    foreach ($endpoints as $p) {
        foreach ($queryVariants as $q) {
            try {
                $r = $this->client()->post($this->url($p).'?'.http_build_query($q));
                if ($isOk($r)) {
                    Log::info('WAHA start OK(post+query)', ['endpoint'=>$p, 'query'=>$q, 'status'=>$r->status()]);
                    $qr = $this->tryGetQr($session);
                    return ['success'=>true,'state'=>$qr ? 'QR' : null,'connected'=>null,'qr'=>$qr,'error'=>null,'raw'=>$this->safeJson($r)];
                } else {
                    Log::warning('WAHA start non-2xx', ['url'=>$this->url($p).'?'.http_build_query($q), 'status'=>$r->status(), 'body'=>mb_substr($r->body(),0,500)]);
                }
            } catch (\Throwable $e) {
                Log::error('WAHA start exception(post+query)', ['url'=>$this->url($p), 'err'=>$e->getMessage()]);
            }
        }
    }

    /* ------------------- C. GET dengan query (last resort) ------------------- */
    foreach ($endpoints as $p) {
        foreach ($queryVariants as $q) {
            try {
                $r = $this->client()->get($this->url($p), $q);
                if ($isOk($r)) {
                    Log::info('WAHA start OK(get+query)', ['endpoint'=>$p, 'query'=>$q, 'status'=>$r->status()]);
                    $qr = $this->tryGetQr($session);
                    return ['success'=>true,'state'=>$qr ? 'QR' : null,'connected'=>null,'qr'=>$qr,'error'=>null,'raw'=>$this->safeJson($r)];
                } else {
                    Log::warning('WAHA start non-2xx', ['url'=>$this->url($p), 'query'=>$q, 'status'=>$r->status(), 'body'=>mb_substr($r->body(),0,500)]);
                }
            } catch (\Throwable $e) {
                Log::error('WAHA start exception(get+query)', ['url'=>$this->url($p), 'err'=>$e->getMessage()]);
            }
        }
    }

    return [
        'success'   => false,
        'state'     => null,
        'connected' => null,
        'qr'        => null,
        'error'     => 'All start attempts failed. Lihat log WAHA start non-2xx untuk endpoint mana yang close.',
        'raw'       => null,
    ];
}

    public function sessionLogout(WahaSender $sender): array
    {
        $session = $this->resolveSession($sender);
        $payloads = [
            ['name'=>$session], ['session'=>$session], ['sessionName'=>$session],
            ['name'=>$session,'session'=>$session,'sessionName'=>$session],
        ];

        $endpoints = [
            '/api/sessions/logout',
            '/api/session/logout',
            '/api/logout',
            "/api/{$this->safe($session)}/logout",
            "/session/{$this->safe($session)}/logout",
        ];

        foreach ($endpoints as $p) {
            foreach ($payloads as $pl) {
                $r = $this->safePost($p, $pl);
                if ($this->ok($r, true)) return ['success'=>true,'state'=>'DISCONNECTED','connected'=>false,'qr'=>null,'error'=>null,'raw'=>$this->safeJson($r)];
                $r = $this->safePostForm($p, $pl);
                if ($this->ok($r, true)) return ['success'=>true,'state'=>'DISCONNECTED','connected'=>false,'qr'=>null,'error'=>null,'raw'=>$this->safeJson($r)];
            }
            // GET query
            $r = $this->safeGet($p, ['name'=>$session]);
            if ($this->ok($r, true)) return ['success'=>true,'state'=>'DISCONNECTED','connected'=>false,'qr'=>null,'error'=>null,'raw'=>$this->safeJson($r)];
        }

        return ['success'=>false,'state'=>null,'connected'=>null,'qr'=>null,'error'=>'All logout paths failed','raw'=>null];
    }

    /* ======================= Helpers ======================= */

    protected function client()
    {
        $c = Http::timeout(30)->acceptJson()->withHeaders([
            'x-api-key'   => $this->apiKey,
            'User-Agent'  => $this->userAgent,
        ]);
        return $this->insecure ? $c->withoutVerifying() : $c;
    }

    protected function url(string $p): string
    {
        if (str_starts_with($p, 'http://') || str_starts_with($p, 'https://')) return $p;
        return $this->baseUrl.(str_starts_with($p,'/') ? $p : "/{$p}");
    }

    protected function safeGet(string $path, array $query = []): ?Response
    {
        try { return $this->client()->get($this->url($path), $query); }
        catch (\Throwable $e) { Log::error('WAHA GET exception',['url'=>$this->url($path),'err'=>$e->getMessage()]); return null; }
    }

    protected function safePost(string $path, array $payload = []): ?Response
    {
        try { return $this->client()->post($this->url($path), $payload); }
        catch (\Throwable $e) { Log::error('WAHA POST exception',['url'=>$this->url($path),'err'=>$e->getMessage()]); return null; }
    }

    protected function safePostForm(string $path, array $payload = []): ?Response
    {
        try { return $this->client()->asForm()->post($this->url($path), $payload); }
        catch (\Throwable $e) { Log::error('WAHA POST(form) exception',['url'=>$this->url($path),'err'=>$e->getMessage()]); return null; }
    }

    protected function ok(?Response $r, bool $allow204=false): bool
    {
        if (!$r) return false;
        if ($r->successful()) return true;
        if ($allow204 && $r->status()===204) return true;
        return false;
    }

    protected function afterStart(Response $r, string $session, string $endpoint, string $mode, array $payload): array
    {
        Log::info('WAHA start OK', ['endpoint'=>$endpoint,'mode'=>$mode,'status'=>$r->status()]);
        // segera coba ambil QR
        $qr = $this->tryGetQr($session);
        return ['success'=>true,'state'=>$qr ? 'QR' : null,'connected'=>null,'qr'=>$qr,'error'=>null,'raw'=>$this->safeJson($r)];
    }

    protected function normalizeStatusPlusQr(Response $r, string $session): array
    {
        $norm = $this->normalizeStatus($r);
        if (($norm['connected'] ?? false) !== true && empty($norm['qr'])) {
            $norm['qr'] = $this->tryGetQr($session);
        }
        return $norm;
    }

    /** Ambil QR (data-uri) dari banyak kemungkinan endpoint */
    protected function tryGetQr(string $session): ?string
    {
        // query: name
        foreach (['/api/sessions/qr','/api/session/qr'] as $p) {
            if ($uri=$this->extractQr($this->safeGet($p, ['name'=>$session,'image'=>1]))) return $uri;
        }
        // query: session
        foreach (['/api/qr','/qr','/api/whatsapp/qr'] as $p) {
            if ($uri=$this->extractQr($this->safeGet($p, ['session'=>$session,'image'=>1]))) return $uri;
        }
        // path
        foreach ([
            "/api/sessions/{$this->safe($session)}/qr",
            "/api/sessions/{$this->safe($session)}/qrcode",
            "/api/{$this->safe($session)}/qr",
            "/session/{$this->safe($session)}/qr",
        ] as $p) {
            if ($uri=$this->extractQr($this->safeGet($p))) return $uri;
        }
        return null;
    }

    protected function extractQr(?Response $r): ?string
    {
        if (!$r || !$r->ok()) return null;
        $ct = strtolower($r->header('Content-Type',''));
        if (str_starts_with($ct,'image/')) return "data:{$ct};base64,".base64_encode($r->body());
        $j = $this->safeJson($r); if (!is_array($j)) return null;
        $raw = $j['qr'] ?? $j['qrCode'] ?? $j['image'] ?? null;
        if (!$raw) return null;
        return str_starts_with($raw,'data:image') ? $raw : "data:image/png;base64,{$raw}";
    }

    protected function normalizeStatus(Response $r): array
    {
        $j = $this->safeJson($r) ?: [];
        $connected = $j['connected'] ?? $j['isConnected'] ?? $j['is_logged_in'] ?? null;
        if (is_string($connected)) $connected = filter_var($connected, FILTER_VALIDATE_BOOLEAN);
        $state = $j['state'] ?? $j['status'] ?? $j['sessionStatus'] ?? ($connected===true?'CONNECTED':null);
        $qrRaw = $j['qr'] ?? $j['qrCode'] ?? $j['image'] ?? null;
        $qr    = is_string($qrRaw) ? (str_starts_with($qrRaw,'data:image')?$qrRaw:"data:image/png;base64,{$qrRaw}") : null;

        return ['success'=>true,'state'=>is_string($state)?strtoupper($state):$state,'connected'=>is_bool($connected)?$connected:($state && strtoupper($state)==='CONNECTED'),'qr'=>$qr,'error'=>null,'raw'=>$j];
    }

    protected function safeJson(Response $r): mixed
    {
        try { return $r->json(); } catch (\Throwable) { return null; }
    }

    /* ---------------- misc utils ---------------- */

    protected function digits(string $raw): string
    {
        $d = preg_replace('/\D+/', '', $raw) ?: $raw;
        if (str_starts_with($d,'0')) $d = '62'.substr($d,1);
        return $d;
    }

    protected function jidCUs(string $digits): string
    {
        return str_contains($digits,'@') ? $digits : "{$digits}@c.us";
    }

    protected function resolveSession(WahaSender $sender): string
    {
        foreach (['session_name','session','sessionId','session_key'] as $f) {
            if (!empty($sender->{$f})) return (string) $sender->{$f};
        }
        $slug = preg_replace('~[^a-z0-9]+~','-', strtolower($sender->name ?? 'default'));
        return trim($slug,'-') ?: 'default';
    }

    protected function safe(string $s): string
    {
        return rawurlencode($s);
    }
}
