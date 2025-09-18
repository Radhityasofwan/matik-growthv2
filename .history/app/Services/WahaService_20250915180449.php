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
            throw new \RuntimeException("WAHA_URL belum valid. Contoh: https://waha.matik.id (tanpa /api). Jalankan: php artisan config:clear");
        }
    }

    /* ======================= Public API ======================= */

    public function sessionStatus(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);

        $info = $this->getSessionInfo($session);
        if (!$info['success']) return $info;

        $out = $this->normalizeSession($info['raw']);

        // Jika WAHA bilang SCAN_QR_CODE tapi belum ada gambar, coba tarik dari endpoint QR khusus
        if (strtoupper((string)$out['state']) === 'SCAN_QR_CODE' && empty($out['qr'])) {
            $img = $this->fetchQrForSession($session);
            if ($img) $out['qr'] = $img;
        }
        return $out;
    }

   public function sessionStart(WahaSender|string $senderOrSession): array
{
    $session = $this->asSession($senderOrSession);

    // pastikan session ada / buat jika 404
    $exists = $this->getSessionInfo($session);
    if (!$exists['success']) {
        if ($this->is404($exists)) {
            $crt = $this->createSession($session);
            if (!$crt['success']) return $crt;
        } else {
            // error jaringan/401/500
            return $exists;
        }
    } else {
        $cur = $this->normalizeSession($exists['raw']);
        $st  = strtoupper((string) $cur['state']);
        if (in_array($st, ['FAILED','STOPPED'], true)) {
            $this->restartSession($session);
        }
    }

    $url = $this->url("/api/sessions/{$this->e($session)}/start");
    try {
        $res  = $this->clientJson()->post($url);
        $json = $res->json() ?? [];

        if (!$res->successful()) {
            // 🔑 Perbaikan utama: kalau /start gagal (mis. 504),
            // kita cek info sesi. Jika sudah ada state (SCAN_QR_CODE/CONNECTED),
            // treat as success agar UI tidak memajang error yang menyesatkan.
            $probe = $this->getSessionInfo($session);
            if ($probe['success']) {
                $norm = $this->normalizeSession($probe['raw']);
                if (strtoupper((string)$norm['state']) !== '' || $norm['connected'] !== null) {
                    // fallback QR fetch jika state = SCAN_QR_CODE
                    if (strtoupper((string)$norm['state']) === 'SCAN_QR_CODE' && empty($norm['qr'])) {
                        $img = $this->fetchQrForSession($session);
                        if ($img) $norm['qr'] = $img;
                    }
                    return $norm;
                }
            }
            return $this->fail("HTTP {$res->status()}", $json, $this->readState($json));
        }

        // respons /start sukses; normalisasi
        $norm = $this->normalizeSession($json);
        if (strtoupper((string)$norm['state']) === 'SCAN_QR_CODE' && empty($norm['qr'])) {
            $img = $this->fetchQrForSession($session);
            if ($img) $norm['qr'] = $img;
        }
        if ($norm['qr'] || $norm['connected'] === true || $norm['state']) return $norm;

        // kalau masih belum jelas, baca info
        return $this->sessionStatus($session);
    } catch (\Throwable $e) {
        // EXC saat start → coba probing dulu
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
            if (!$res->successful()) {
                return $this->fail("HTTP {$res->status()}", $json, $this->readState($json));
            }
            $out = $this->normalizeSession($json);
            $out['connected'] = false;
            $out['state'] = $out['state'] ?: 'LOGGED_OUT';
            return $out;
        } catch (\Throwable $e) {
            return $this->fail("LOGOUT EXC: ".$e->getMessage());
        }
    }

    /** Start + polling hingga 30s; fetch QR dari endpoint terpisah bila perlu */
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
            if ($up === 'FAILED') {
                $this->restartSession($session);
                usleep(300 * 1000);
                continue;
            }
            if (!empty($st['qr']) || $st['connected'] === true) return $st;
        }
        return $last;
    }

    public function qrStatus(WahaSender|string $senderOrSession): array
    {
        return $this->sessionStatus($senderOrSession);
    }

    /* ==================== WAHA endpoints ===================== */

    /** GET /api/sessions/{session} */
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

    /** POST /api/sessions  (body: { name }) */
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
            if (!$res->successful()) {
                return $this->fail("Restart HTTP {$res->status()}", $json, $this->readState($json));
            }
            return $this->normalizeSession($json);
        } catch (\Throwable $e) {
            return $this->fail("RESTART EXC: ".$e->getMessage());
        }
    }

    /* ======================== Helpers ======================== */

    /** Client JSON (default) */
    protected function clientJson()
    {
        $headers = ['User-Agent' => $this->userAgent];
        if (!empty($this->apiKey)) $headers['x-api-key'] = $this->apiKey;

        $c = Http::acceptJson()
            ->retry(2, 300)
            ->timeout(30)
            ->withHeaders($headers);

        return $this->insecure ? $c->withoutVerifying() : $c;
    }

    /** Client generic (untuk ambil image/binary QR) */
    protected function clientGeneric()
    {
        $headers = ['User-Agent' => $this->userAgent];
        if (!empty($this->apiKey)) $headers['x-api-key'] = $this->apiKey;

        $c = Http::retry(2, 300)
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

    /*** ===== Normalisasi dan ekstraksi aman (tanpa Undefined key) ===== ***/
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

    /** Jika string base64 polos → jadikan data:URI png */
    protected function asDataUriIfBase64(string $raw): string
    {
        if (preg_match('~^(data:|https?://)~i', $raw)) return $raw;
        if (preg_match('~^[A-Za-z0-9+/=]{200,}$~', $raw)) {
            return 'data:image/png;base64,' . $raw;
        }
        return $raw;
    }

    /** Coba ambil QR dari endpoint khusus (image atau JSON) */
    protected function fetchQrForSession(string $session): ?string
    {
        $paths = [
            "/api/sessions/{$this->e($session)}/qr",
            "/api/sessions/{$this->e($session)}/qr.png",
            "/api/sessions/{$this->e($session)}/qr-image",
            // beberapa build lama:
            "/api/qr?session={$this->e($session)}",
        ];

        foreach ($paths as $p) {
            $url = $this->url($p);
            try {
                $res = $this->clientGeneric()->get($url);
                if (!$res->successful()) continue;

                $ctype = strtolower((string) $res->header('Content-Type'));
                $body  = (string) $res->body();

                // Jika image binary → jadikan data URI
                if (str_starts_with($ctype, 'image/')) {
                    $b64 = base64_encode($body);
                    $ext = str_starts_with($ctype, 'image/') ? $ctype : 'image/png';
                    return "data:{$ext};base64,{$b64}";
                }

                // Coba parse JSON yg mungkin berisi qr
                $js = null;
                try { $js = $res->json(); } catch (\Throwable $e) { $js = null; }
                if (is_array($js)) {
                    $qr = $this->readQr($js);
                    if (!empty($qr)) return $qr;
                }

                // Kalau bukan JSON, mungkin server mengirim base64 polos
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
}
