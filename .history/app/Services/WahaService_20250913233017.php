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
        $session = $this->resolveSession($sender);

        // kombinasi payload
        $payloads = [
            ['name'=>$session],
            ['session'=>$session],
            ['sessionName'=>$session],
            ['name'=>$session,'session'=>$session,'sessionName'=>$session],
        ];

        // daftar endpoint (lihat log kamu: /api/sessions/start ada tapi 422)
        $endpoints = [
            '/api/sessions/start',
            '/api/session/start',
            '/api/start',
            "/api/{$this->safe($session)}/start",
            "/session/{$this->safe($session)}/start",
            "/sessions/{$this->safe($session)}/start",
            '/api/qr/start', // beberapa fork pakai ini
        ];

        $errors = [];

        // 1) POST JSON & POST form ke semua endpoint, semua payload
        foreach ($endpoints as $p) {
            foreach ($payloads as $pl) {
                // JSON
                $r = $this->safePost($p, $pl);
                if ($this->ok($r)) return $this->afterStart($r, $session, $p, 'json', $pl);
                $errors[] = "POST(json) {$p} ".($r?->status() ?? 'exc');

                // FORM (url-encoded) — banyak server minta ini
                $r = $this->safePostForm($p, $pl);
                if ($this->ok($r)) return $this->afterStart($r, $session, $p, 'form', $pl);
                $errors[] = "POST(form) {$p} ".($r?->status() ?? 'exc');
            }
        }

        // 2) GET query ?name=... (beberapa server pakai GET)
        foreach ($endpoints as $p) {
            $r = $this->safeGet($p, ['name'=>$session]);
            if ($this->ok($r)) return $this->afterStart($r, $session, $p, 'get', ['name'=>$session]);
            $errors[] = "GET {$p}?name=... ".($r?->status() ?? 'exc');
        }

        // 3) GET query ?sessionName=...
        foreach ($endpoints as $p) {
            $r = $this->safeGet($p, ['sessionName'=>$session]);
            if ($this->ok($r)) return $this->afterStart($r, $session, $p, 'get', ['sessionName'=>$session]);
            $errors[] = "GET {$p}?sessionName=... ".($r?->status() ?? 'exc');
        }

        return ['success'=>false,'state'=>null,'connected'=>null,'qr'=>null,'error'=>implode(' | ',$errors),'raw'=>null];
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
