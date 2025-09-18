<?php

namespace App\Services;

use App\Models\WahaSender;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaService
{
    protected string $baseUrl;
    protected ?string $apiKey;
    protected string $userAgent;
    protected bool $insecure;

    public function __construct()
    {
        $this->baseUrl   = rtrim((string) config('services.waha.url'), '/');
        $this->apiKey    = config('services.waha.key');
        $this->userAgent = (string) env('WAHA_UA', 'Matik Growth Hub');
        $this->insecure  = (bool) env('WAHA_INSECURE', false);

        if ($this->baseUrl === '' || !preg_match('~^https?://~i', $this->baseUrl)) {
            throw new \RuntimeException("WAHA_URL invalid. Example: https://waha.matik.id  (no trailing /api)");
        }
    }

    /* ======================= Public API ======================= */

    public function sessionStatus(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);

        $info = $this->getSessionInfo($session);
        if (!$info['success']) return $info;

        $out = $this->normalizeSession($info['raw']);

        // If waiting for scan but QR not visible, try all fallbacks
        if (strtoupper((string)$out['state']) === 'SCAN_QR_CODE' && empty($out['qr'])) {
            // 1) deep scan the same JSON
            $deep = $this->findBase64ImageDeep($info['raw']);
            if ($deep) $out['qr'] = $deep;

            // 2) try dedicated QR endpoints (image/json/base64)
            if (empty($out['qr'])) {
                $img = $this->fetchQrForSession($session);
                if ($img) $out['qr'] = $img;
            }
        }

        return $out;
    }

    public function sessionStart(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);

        // Ensure session exists (or create)
        $exists = $this->getSessionInfo($session);
        if (!$exists['success']) {
            if ($this->is404($exists)) {
                $crt = $this->createSession($session);
                if (!$crt['success']) return $crt;
            } else {
                return $exists;
            }
        } else {
            $cur = $this->normalizeSession($exists['raw']);
            $st  = strtoupper((string) $cur['state']);
            if (in_array($st, ['FAILED','STOPPED'], true)) $this->restartSession($session);
        }

        $url = $this->url("/api/sessions/{$this->e($session)}/start");
        try {
            $res  = $this->clientJson()->post($url);
            $json = $res->json() ?? [];

            if (!$res->successful()) {
                // Treat 5xx/504 as non-fatal if probing shows the session is progressing
                $probe = $this->getSessionInfo($session);
                if ($probe['success']) {
                    $norm = $this->normalizeSession($probe['raw']);
                    if ($norm['state'] || $norm['connected'] !== null) {
                        if (strtoupper((string)$norm['state']) === 'SCAN_QR_CODE' && empty($norm['qr'])) {
                            $norm['qr'] = $this->findBase64ImageDeep($probe['raw']) ?: $this->fetchQrForSession($session);
                        }
                        return $norm;
                    }
                }
                return $this->fail("HTTP {$res->status()}", $json, $this->readState($json));
            }

            $norm = $this->normalizeSession($json);
            if (strtoupper((string)$norm['state']) === 'SCAN_QR_CODE' && empty($norm['qr'])) {
                $norm['qr'] = $this->fetchQrForSession($session);
            }
            if ($norm['qr'] || $norm['connected'] === true || $norm['state']) return $norm;

            return $this->sessionStatus($session);
        } catch (\Throwable $e) {
            $probe = $this->getSessionInfo($session);
            if ($probe['success']) return $this->normalizeSession($probe['raw']);
            return $this->fail("START EXC: ".$e->getMessage());
        }
    }

    public function sessionLogout(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);
        $url = $this->url("/api/sessions/{$this->e($session)}/logout");
        try {
            $res  = $this->clientJson()->post($url);
            $json = $res->json() ?? [];
            if (!$res->successful()) return $this->fail("HTTP {$res->status()}", $json, $this->readState($json));
            $out = $this->normalizeSession($json);
            $out['connected'] = false;
            $out['state'] = $out['state'] ?: 'LOGGED_OUT';
            return $out;
        } catch (\Throwable $e) {
            return $this->fail("LOGOUT EXC: ".$e->getMessage());
        }
    }

    public function qrStart(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);

        $start = $this->sessionStart($session);
        if (!$start['success']) return $start;
        if (!empty($start['qr']) || ($start['connected'] === true)) return $start;

        $last = $start;
        for ($i=0; $i<30; $i++) {
            sleep(1);
            $st = $this->sessionStatus($session);
            $last = $st;
            if (!$st['success']) continue;

            $up = strtoupper((string) $st['state']);
            if ($up === 'FAILED') { $this->restartSession($session); usleep(300*1000); continue; }

            if (!empty($st['qr']) || $st['connected'] === true) return $st;
        }
        return $last;
    }

    public function qrStatus(WahaSender|string $senderOrSession): array
    {
        return $this->sessionStatus($senderOrSession);
    }

    /* ==================== WAHA endpoints ===================== */

    protected function getSessionInfo(string $session): array
    {
        $url = $this->url("/api/sessions/{$this->e($session)}");
        try {
            $res  = $this->clientJson()->get($url);
            $json = $res->json() ?? [];
            if (!$res->successful()) {
                $snippet = trim(mb_substr($res->body() ?? '', 0, 200));
                return $this->fail("HTTP {$res->status()}".($snippet?" BODY: {$snippet}":''), $json);
            }
            return ['success'=>true, 'raw'=>$json, 'error'=>null, 'connected'=>null, 'state'=>null, 'qr'=>null];
        } catch (\Throwable $e) {
            return $this->fail("GET EXC: ".$e->getMessage());
        }
    }

    protected function createSession(string $session): array
    {
        $url = $this->url("/api/sessions");
        try {
            $res  = $this->clientJson()->post($url, ['name'=>$session]);
            $json = $res->json() ?? [];
            if (!$res->successful()) {
                $snippet = trim(mb_substr($res->body() ?? '', 0, 200));
                return $this->fail("Create HTTP {$res->status()}".($snippet?" BODY: {$snippet}":''), $json);
            }
            return ['success'=>true, 'raw'=>$json, 'error'=>null, 'connected'=>null, 'state'=>null, 'qr'=>null];
        } catch (\Throwable $e) {
            return $this->fail("CREATE EXC: ".$e->getMessage());
        }
    }

    protected function restartSession(string $session): array
    {
        $url = $this->url("/api/sessions/{$this->e($session)}/restart");
        try {
            $res  = $this->clientJson()->post($url);
            $json = $res->json() ?? [];
            if (!$res->successful()) return $this->fail("Restart HTTP {$res->status()}", $json, $this->readState($json));
            return $this->normalizeSession($json);
        } catch (\Throwable $e) {
            return $this->fail("RESTART EXC: ".$e->getMessage());
        }
    }

    /* ======================== Helpers ======================== */

    protected function clientJson()
    {
        $headers = ['User-Agent' => $this->userAgent];
        if (!empty($this->apiKey)) $headers['x-api-key'] = $this->apiKey;

        $c = Http::acceptJson()->retry(2, 300)->timeout(30)->withHeaders($headers);
        return $this->insecure ? $c->withoutVerifying() : $c;
    }

    protected function clientGeneric()
    {
        $headers = ['User-Agent' => $this->userAgent];
        if (!empty($this->apiKey)) $headers['x-api-key'] = $this->apiKey;

        // Prioritaskan image; fallback JSON
        $headers['Accept'] = 'image/*,application/json;q=0.9,*/*;q=0.8';

        $c = Http::retry(2, 300)->timeout(30)->withHeaders($headers);
        return $this->insecure ? $c->withoutVerifying() : $c;
    }

    /** Wrapper kecil agar pemanggil lama $this->client() tetap jalan */
    protected function client()
    {
        return $this->clientJson();
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

    protected function normalizeSession($json): array
    {
        $state = $this->readState($json);
        $qr    = $this->readQr($json);
        if (empty($qr)) $qr = $this->findBase64ImageDeep($json);
        $err   = $this->readError($json);
        $conn  = $this->readConnected($json, $state);

        return [
            'success'   => true,
            'connected' => $conn,
            'state'     => $state,
            'qr'        => $qr,
            'error'     => $err,
            'raw'       => $json,
        ];
    }

    protected function readState($json): ?string
    {
        if (!is_array($json)) return null;
        foreach (['state','status','data.state','result.state','session.state'] as $k) {
            $v = $this->arrGet($json, $k);
            if (is_string($v) && $v !== '') return $v;
        }
        return null;
    }

    protected function readQr($json): ?string
    {
        if (!is_array($json)) return null;
        $candidates = [
            'qr','qrcode','qrCode','qr_image','qrImage','qrPNG',
            'qr.base64','qr.image',
            'data.qr','data.qrcode','data.qrCode','data.qrImage',
            'result.qr','result.qrcode','result.qrCode','result.qrImage',
            'image','payload.qr'
        ];
        foreach ($candidates as $k) {
            $v = $this->arrGet($json, $k);
            if (is_string($v) && $v !== '') return $this->asDataUriIfBase64($v);
        }
        return null;
    }

    protected function findBase64ImageDeep($json): ?string
    {
        $stack = [$json];
        while ($stack) {
            $cur = array_pop($stack);
            if (is_array($cur)) {
                foreach ($cur as $val) $stack[] = $val;
            } elseif (is_object($cur)) {
                foreach (get_object_vars($cur) as $val) $stack[] = $val;
            } elseif (is_string($cur)) {
                if (!preg_match('~^(data:|https?://)~i', $cur)
                    && preg_match('~^[A-Za-z0-9+/=]{200,}$~', $cur)) {
                    return 'data:image/png;base64,' . $cur;
                }
            }
        }
        return null;
    }

    protected function readConnected($json, ?string $state): ?bool
    {
        if (is_array($json) && array_key_exists('connected', $json)) {
            $v = $json['connected'];
            if (is_bool($v)) return $v;
            if (is_string($v)) return in_array(strtolower($v), ['true','1','yes'], true);
            if (is_numeric($v)) return (bool)$v;
        }
        if (is_string($state)) {
            $up = strtoupper($state);
            if (in_array($up, ['CONNECTED','READY','AUTHENTICATED','WORKING'], true)) return true;
            if (in_array($up, ['DISCONNECTED','LOGGED_OUT','STOPPED','FAILED'], true)) return false;
        }
        return null;
    }

    protected function readError($json): ?string
    {
        if (!is_array($json)) return null;
        foreach (['error','message','detail','details','reason','errorMessage','data.error','result.error'] as $k) {
            $v = $this->arrGet($json, $k);
            if (is_string($v) && trim($v) !== '') return trim($v);
            if (is_array($v) && isset($v['message']) && is_string($v['message'])) return trim($v['message']);
        }
        return null;
    }

    protected function arrGet(array $arr, string $dotKey)
    {
        if (array_key_exists($dotKey, $arr)) return $arr[$dotKey];
        $cur = $arr;
        foreach (explode('.', $dotKey) as $k) {
            if (!is_array($cur) || !array_key_exists($k, $cur)) return null;
            $cur = $cur[$k];
        }
        return $cur;
    }

    protected function normalizeSessionKey(string $s): string
    {
        $s = trim($s) ?: 'default';
        $s = preg_replace('/[^A-Za-z0-9._-]+/', '-', $s);
        return substr($s, 0, 64);
    }

    protected function e(string $s): string { return rawurlencode($s); }

    protected function is404(array $resp): bool
    {
        $err = (string)($resp['error'] ?? '');
        return str_contains($err, '404') || str_contains($err, 'Not Found');
    }

    protected function fail(string $msg, $raw = null, $state = null): array
    {
        return ['success'=>false, 'connected'=>null, 'state'=>$state, 'qr'=>null, 'error'=>$msg, 'raw'=>$raw];
    }

    protected function asDataUriIfBase64(string $raw): string
    {
        if (preg_match('~^(data:|https?://)~i', $raw)) return $raw;
        if (preg_match('~^[A-Za-z0-9+/=]{200,}$~', $raw)) return 'data:image/png;base64,' . $raw;
        return $raw;
    }

    protected function fetchQrForSession(string $session): ?string
    {
        $paths = [
            "/api/sessions/{$this->e($session)}/qr",
            "/api/sessions/{$this->e($session)}/qr.png",
            "/api/sessions/{$this->e($session)}/qr-image",
            "/api/{$this->e($session)}/auth/qr",
            "/api/{$this->e($session)}/auth/qr.png",
            "/api/qr?session={$this->e($session)}",
        ];

        foreach ($paths as $p) {
            $url = $this->url($p);
            try {
                $res = $this->clientGeneric()->get($url);
                if (!$res->successful()) continue;

                $ctype = strtolower((string) $res->header('Content-Type'));
                $body  = (string) $res->body();

                if (str_starts_with($ctype, 'image/')) {
                    $b64 = base64_encode($body);
                    $ext = $ctype ?: 'image/png';
                    return "data:{$ext};base64,{$b64}";
                }

                $js = null; try { $js = $res->json(); } catch (\Throwable $e) {}
                if (is_array($js)) {
                    $qr = $this->readQr($js) ?: $this->findBase64ImageDeep($js);
                    if (!empty($qr)) return $qr;
                }

                if ($body && preg_match('~^[A-Za-z0-9+/=]{200,}$~', $body)) {
                    return 'data:image/png;base64,' . $body;
                }
            } catch (\Throwable $e) {
                Log::warning('WAHA fetchQr error', ['url' => $url, 'err' => $e->getMessage()]);
                continue;
            }
        }
        return null;
    }

    /** Ambil QR image sebagai BINARY + content-type (untuk diproxy ke browser) */
    public function qrImageBinary(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);

        $paths = [
            "/api/sessions/{$this->e($session)}/qr",
            "/api/sessions/{$this->e($session)}/qr.png",
            "/api/sessions/{$this->e($session)}/qr-image",
            "/api/{$this->e($session)}/auth/qr",
            "/api/{$this->e($session)}/auth/qr.png",
            "/api/qr?session={$this->e($session)}",
        ];

        foreach ($paths as $p) {
            $url = $this->url($p);
            try {
                $res = $this->clientGeneric()->get($url);
                $status = $res->status();
                $ctype  = strtolower((string) $res->header('Content-Type'));
                $body   = (string) $res->body();

                if ($status >= 200 && $status < 300) {
                    if (str_starts_with($ctype, 'image/')) {
                        return ['success'=>true, 'ctype'=>$ctype ?: 'image/png', 'body'=>$body];
                    }

                    if ($ctype === '' || $ctype === 'application/octet-stream') {
                        if (strlen($body) > 500) {
                            return ['success'=>true, 'ctype'=>'image/png', 'body'=>$body];
                        }
                    }

                    $js = null;
                    try { $js = $res->json(); } catch (\Throwable $e) {}
                    if (is_array($js)) {
                        $dataUri = $this->readQr($js) ?: $this->findBase64ImageDeep($js);
                        if (is_string($dataUri) && preg_match('~^data:image/[^;]+;base64,(.+)$~', $dataUri, $m)) {
                            $bin = base64_decode($m[1], true);
                            if ($bin !== false) return ['success'=>true, 'ctype'=>'image/png', 'body'=>$bin];
                        }
                    }

                    if ($body && preg_match('~^[A-Za-z0-9+/=]{200,}$~', $body)) {
                        $bin = base64_decode($body, true);
                        if ($bin !== false) return ['success'=>true, 'ctype'=>'image/png', 'body'=>$bin];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('WAHA qrImageBinary error', ['url'=>$url, 'err'=>$e->getMessage()]);
                continue;
            }
        }

        $probe = $this->getSessionInfo($session);
        if ($probe['success']) {
            $norm = $this->normalizeSession($probe['raw']);
            if ($norm['connected'] === true) {
                return ['success'=>false, 'ctype'=>null, 'body'=>null];
            }
        }

        return ['success'=>false, 'ctype'=>null, 'body'=>null];
    }

    public function requestAuthCode(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);

        $paths = [
            "/api/{$this->e($session)}/auth/request-code",
            "/api/sessions/{$this->e($session)}/auth/request-code",
        ];

        $errors = [];
        foreach ($paths as $p) {
            $url = $this->url($p);
            try {
                $res  = $this->clientJson()->post($url);
                $json = $res->json() ?? [];
                if ($res->successful()) {
                    $code  = $this->readPairingCode($json);
                    $state = $this->readState($json);
                    return [
                        'success' => (bool) $code,
                        'code'    => $code,
                        'state'   => $state,
                        'raw'     => $json,
                        'error'   => $code ? null : 'Kode tidak ditemukan di respons.',
                    ];
                }
                $errors[] = "HTTP ".$res->status();
            } catch (\Throwable $e) {
                $errors[] = "EXC ".$e->getMessage();
            }
        }

        return ['success'=>false,'code'=>null,'state'=>null,'raw'=>null,'error'=>implode(' | ', $errors)];
    }

    protected function readPairingCode($json): ?string
    {
        if (!is_array($json)) return null;
        $keys = [
            'code','pairingCode','login_code','loginCode',
            'data.code','result.code','payload.code','pairing.code',
            'pairing.code_value'
        ];
        foreach ($keys as $k) {
            $v = $this->arrGet($json, $k);
            if (is_string($v) && trim($v) !== '') return trim($v);
            if (is_numeric($v)) return (string)$v;
        }
        return null;
    }

    public function sessionMe(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);
        $paths = [
            "/api/sessions/{$this->e($session)}/me",
            "/api/{$this->e($session)}/me",
        ];
        foreach ($paths as $p) {
            $url = $this->url($p);
            try {
                $res  = $this->clientJson()->get($url);
                $json = $res->json() ?? [];
                if ($res->successful()) {
                    $number = $this->arrGet($json, 'id') ?? $this->arrGet($json, 'wid') ?? $this->arrGet($json, 'user') ?? null;
                    $display = $this->arrGet($json, 'pushName') ?? $this->arrGet($json, 'name') ?? $this->arrGet($json, 'display_name') ?? null;
                    if (is_array($number) && isset($number['user'])) $number = $number['user'];
                    if (is_string($number)) $number = preg_replace('/\D+/', '', $number);
                    return ['success'=>true, 'number'=>$number, 'display_name'=>$display, 'raw'=>$json];
                }
            } catch (\Throwable $e) {}
        }
        return ['success'=>false, 'number'=>null, 'display_name'=>null, 'raw'=>null];
    }

    /* ==================== Sending Messages ==================== */

    /**
     * Kirim pesan teks sederhana ke nomor.
     * Mencoba banyak variasi PATH + PAYLOAD yang umum dipakai berbagai build WAHA.
     */
    public function sendMessage(WahaSender $sender, string $recipient, string $message): ?array
    {
        $session = $this->resolveSession($sender);
        $chatId  = $this->toChatId($recipient);

        $paths = [
            '/api/sendText',          // ✅ sesuai Swagger
            '/api/message',           // varian lama
            '/message',               // varian tanpa /api
            '/api/send-message',      // varian lain
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
                        'url' => $url,
                        'status' => $res->status(),
                        'body' => mb_substr($res->body(), 0, 500),
                        'payload' => $body,
                    ]);
                } catch (\Throwable $e) {
                    $errors[] = "POST {$p} EXC";
                    Log::error('WAHA sendMessage exception', [
                        'url' => $url,
                        'err' => $e->getMessage(),
                        'payload' => $body,
                    ]);
                }
            }
        }

        return [
            'success' => false,
            'raw'     => null,
            'error'   => implode(' | ', $errors),
        ];
    }

    /**
     * Normalisasi penanda sukses di berbagai bentuk respons WAHA.
     * Jika server balas 204/202 tanpa body, anggap sukses (queued/accepted).
     */
    public function isSuccessful(array $resp, int $httpStatus = 200): bool
    {
        if (in_array($httpStatus, [202, 204], true)) return true;

        $checks = [
            fn($r) => isset($r['success']) && $r['success'] === true,
            fn($r) => isset($r['sent']) && (bool)$r['sent'] === true,
            fn($r) => isset($r['ok']) && (bool)$r['ok'] === true,
            fn($r) => isset($r['status']) && in_array(strtolower((string)$r['status']), ['ok','success','sent','queued'], true),
            fn($r) => isset($r['messageId']) && is_string($r['messageId']) && $r['messageId'] !== '',
            fn($r) => isset($r['id']) && is_string($r['id']) && $r['id'] !== '',
            fn($r) => isset($r['data']['id']) && is_string($r['data']['id']) && $r['data']['id'] !== '',
            fn($r) => isset($r['result']['id']) && is_string($r['result']['id']) && $r['result']['id'] !== '',
            fn($r) => isset
