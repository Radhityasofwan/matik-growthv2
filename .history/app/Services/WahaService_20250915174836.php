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
            throw new \RuntimeException("WAHA_URL belum valid. Set .env WAHA_URL (mis. https://waha.matik.id) dan jalankan php artisan config:clear");
        }
    }

    /* ======================= Public API ======================= */

    public function sessionStatus(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);
        $info = $this->getSessionInfo($session);
        if (!$info['success']) return $info;
        return $this->normalizeSession($info['raw']);
    }

    public function sessionStart(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);

        // 1) Pastikan session ada
        $exists = $this->getSessionInfo($session);
        if (!$exists['success']) {
            if ($this->is404($exists)) {
                $crt = $this->createSession($session);
                if (!$crt['success']) return $crt;
            } else {
                return $exists; // error jaringan/401/500
            }
        } else {
            // Kalau sudah ada tapi FAILED/STOPPED → coba restart agar bersih
            $cur = $this->normalizeSession($exists['raw']);
            $st  = strtoupper((string) $cur['state']);
            if (in_array($st, ['FAILED','STOPPED'], true)) {
                $this->restartSession($session);
            }
        }

        // 2) Start
        $url = $this->url("/api/sessions/{$this->e($session)}/start");
        try {
            $res  = $this->client()->post($url);
            $json = $res->json() ?? [];
            if (!$res->successful()) {
                return $this->fail("HTTP {$res->status()}", $json, null);
            }

            // 3) Kalau response start belum bawa info jelas, segera baca info
            $norm = $this->normalizeSession($json);
            if ($norm['qr'] || $norm['connected'] === true || $norm['state']) return $norm;

            return $this->sessionStatus($session);
        } catch (\Throwable $e) {
            return $this->fail("START EXC: ".$e->getMessage());
        }
    }

    public function sessionLogout(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);
        $url = $this->url("/api/sessions/{$this->e($session)}/logout");
        try {
            $res  = $this->client()->post($url);
            $json = $res->json() ?? [];
            if (!$res->successful()) {
                return $this->fail("HTTP {$res->status()}", $json, null);
            }
            $out = $this->normalizeSession($json);
            $out['connected'] = false;
            $out['state'] = $out['state'] ?: 'LOGGED_OUT';
            return $out;
        } catch (\Throwable $e) {
            return $this->fail("LOGOUT EXC: ".$e->getMessage());
        }
    }

    /** Start + polling singkat untuk QR/CONNECTED; lakukan self-heal kalau FAILED */
  public function qrStart(WahaSender|string $senderOrSession): array
{
    $session = $this->asSession($senderOrSession);

    $start = $this->sessionStart($session);
    if (!$start['success']) return $start;
    if (!empty($start['qr']) || ($start['connected'] === true)) return $start;

    // Poll up-to 30x (±30 detik) cari QR / CONNECTED
    $last = $start;
    for ($i = 0; $i < 30; $i++) {
        sleep(1);
        $st = $this->sessionStatus($session);
        $last = $st;
        if (!$st['success']) continue;

        $up = strtoupper((string) $st['state']);
        if ($up === 'FAILED') {
            $this->restartSession($session);
            usleep(300 * 1000);
            continue;
        }

        if (!empty($st['qr']) || ($st['connected'] === true)) return $st;
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
            $res  = $this->client()->get($url);
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

    /** POST /api/sessions  (body: { name }) */
    protected function createSession(string $session): array
    {
        $url = $this->url("/api/sessions");
        try {
            $res  = $this->client()->post($url, ['name'=>$session]);
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
            $res  = $this->client()->post($url);
            $json = $res->json() ?? [];
            if (!$res->successful()) {
                return $this->fail("Restart HTTP {$res->status()}", $json);
            }
            return $this->normalizeSession($json);
        } catch (\Throwable $e) {
            return $this->fail("RESTART EXC: ".$e->getMessage());
        }
    }

    /* ======================== Helpers ======================== */

    protected function client()
    {
        $headers = ['User-Agent' => $this->userAgent];
        if (!empty($this->apiKey)) $headers['x-api-key'] = $this->apiKey;

        $c = Http::acceptJson()
            ->retry(2, 300)
            ->timeout(30)
            ->withHeaders($headers);

        return $this->insecure ? $c->withoutVerifying() : $c;
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

    /** Normalisasi payload session ke bentuk baku */
    protected function normalizeSession($json): array
    {
        $state = $this->readState($json);
        $qr    = $this->readQr($json);
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
        $candidates = ['state','status','data.state','result.state','session.state'];
        foreach ($candidates as $k) {
            $v = $this->arrGet($json, $k);
            if (is_string($v) && $v !== '') return $v;
        }
        return null;
    }

    protected function readQr($json): ?string
{
    if (!is_array($json)) return null;
    // Coba berbagai kemungkinan key yang sering dipakai berbagai build
    $candidates = [
        'qr','qrcode','qrCode','qr_image','qrImage','qrPNG',
        'qr.base64','qr.image',
        'data.qr','data.qrcode','data.qrCode','data.qrImage',
        'result.qr','result.qrcode','result.qrCode','result.qrImage',
        'image',
    ];
    foreach ($candidates as $k) {
        $v = $this->arrGet($json, $k);
        if (is_string($v) && $v !== '') {
            return $this->asDataUriIfBase64($v);
        }
    }
    return null;
}

/** Jika string tampak seperti base64 PNG/JPEG tanpa prefix, jadikan data-URI */
protected function asDataUriIfBase64(string $raw): string
{
    // sudah data-URI / url? biarkan
    if (preg_match('~^(data:|https?://)~i', $raw)) return $raw;

    // indikasi base64 "panjang" (heuristik)
    if (preg_match('~^[A-Za-z0-9+/=]{200,}$~', $raw)) {
        // default asumsikan PNG
        return 'data:image/png;base64,' . $raw;
    }

    return $raw;
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
        $candidates = ['error','message','detail','details','reason','errorMessage','data.error','result.error'];
        foreach ($candidates as $k) {
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
}
