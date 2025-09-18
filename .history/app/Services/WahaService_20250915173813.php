<?php

namespace App\Services;

use App\Models\WahaSender;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaService
{
    protected string $baseUrl;         // contoh: https://waha.matik.id  (TANPA /api)
    protected ?string $apiKey;         // opsional; isi jika server pakai x-api-key
    protected string $userAgent;
    protected bool $insecure;

    public function __construct()
    {
        $this->baseUrl   = rtrim((string) config('services.waha.url'), '/');
        $this->apiKey    = config('services.waha.key');
        $this->userAgent = (string) env('WAHA_UA', 'Matik Growth Hub');
        $this->insecure  = (bool) env('WAHA_INSECURE', false);

        if ($this->baseUrl === '' || !preg_match('~^https?://~i', $this->baseUrl)) {
            throw new \RuntimeException(
                "WAHA_URL belum di-set/invalid. Set .env WAHA_URL (mis. https://waha.matik.id) lalu php artisan config:clear"
            );
        }
    }

    /* =========================================================================
     |  Publik API – dipakai controller
     * ========================================================================= */

    /** Bentuk baku status session */
    public function sessionStatus(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);
        $info = $this->getSessionInfo($session);
        if (!$info['success']) {
            return $info; // sudah memuat error jelas
        }
        return $this->normalizeSession($info['raw']);
    }

    public function sessionStart(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);

        // Pastikan session ADA. Kalau 404 → create.
        $exists = $this->getSessionInfo($session);
        if (!$exists['success'] && $this->is404($exists)) {
            $crt = $this->createSession($session);
            if (!$crt['success']) return $crt;
        } elseif (!$exists['success'] && !$this->is404($exists)) {
            // error lain (SSL/timeout/dll)
            return $exists;
        }

        $url = $this->url("/api/sessions/{$this->e($session)}/start");
        try {
            $res  = $this->client()->post($url);
            $json = $res->json() ?? [];
            if (!$res->successful()) {
                return [
                    'success' => false,
                    'connected' => null,
                    'state' => $this->readState($json),
                    'qr' => $this->readQr($json),
                    'error' => "HTTP {$res->status()}",
                    'raw' => $json,
                ];
            }
            // beberapa build tidak mengembalikan state/qr di /start
            $norm = $this->normalizeSession($json);
            if ($norm['state'] || $norm['qr']) return $norm;

            // fallback: segera baca info setelah start
            return $this->sessionStatus($session);
        } catch (\Throwable $e) {
            return ['success'=>false, 'connected'=>null, 'state'=>null, 'qr'=>null, 'error'=>$e->getMessage(), 'raw'=>null];
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
                return ['success'=>false, 'connected'=>null, 'state'=>$this->readState($json), 'qr'=>null, 'error'=>"HTTP {$res->status()}", 'raw'=>$json];
            }
            $norm = $this->normalizeSession($json);
            if (!$norm['state']) $norm['state'] = 'LOGGED_OUT';
            $norm['connected'] = false;
            return $norm;
        } catch (\Throwable $e) {
            return ['success'=>false, 'connected'=>null, 'state'=>null, 'qr'=>null, 'error'=>$e->getMessage(), 'raw'=>null];
        }
    }

    /** Mulai lalu polling singkat untuk QR/CONNECTED */
    public function qrStart(WahaSender|string $senderOrSession): array
    {
        $session = $this->asSession($senderOrSession);

        $start = $this->sessionStart($session);
        if (!$start['success']) return $start;
        if (!empty($start['qr']) || ($start['connected'] === true)) return $start;

        // Poll up-to 6x (±6 detik) untuk menunggu QR tersedia
        for ($i = 0; $i < 6; $i++) {
            sleep(1);
            $st = $this->sessionStatus($session);
            if (!$st['success']) continue;
            if (!empty($st['qr']) || ($st['connected'] === true)) return $st;
        }

        // tetap kembalikan hasil terakhir yang kita punya (minimal tidak error)
        return $this->sessionStatus($session);
    }

    /** QR status = info session, karena WAHA tidak punya endpoint /qr tersendiri */
    public function qrStatus(WahaSender|string $senderOrSession): array
    {
        return $this->sessionStatus($senderOrSession);
    }

    /* =========================================================================
     |  Endpoints WAHA (sesuai dok kamu)
     * ========================================================================= */

    /** GET /api/sessions/{session} */
    protected function getSessionInfo(string $session): array
    {
        $url = $this->url("/api/sessions/{$this->e($session)}");
        try {
            $res  = $this->client()->get($url);
            $json = $res->json() ?? [];
            if (!$res->successful()) {
                $snippet = trim(mb_substr($res->body() ?? '', 0, 200));
                return ['success'=>false, 'error'=>"HTTP {$res->status()}".($snippet?" BODY: {$snippet}":''), 'raw'=>$json];
            }
            return ['success'=>true, 'raw'=>$json];
        } catch (\Throwable $e) {
            return ['success'=>false, 'error'=>"GET EXC: ".$e->getMessage(), 'raw'=>null];
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
                return ['success'=>false, 'error'=>"Create HTTP {$res->status()}".($snippet?" BODY: {$snippet}":''), 'raw'=>$json];
            }
            return ['success'=>true, 'raw'=>$json];
        } catch (\Throwable $e) {
            return ['success'=>false, 'error'=>"CREATE EXC: ".$e->getMessage(), 'raw'=>null];
        }
    }

    /* =========================================================================
     |  Helpers
     * ========================================================================= */

    protected function client()
    {
        $headers = ['User-Agent' => $this->userAgent];
        if (!empty($this->apiKey)) $headers['x-api-key'] = $this->apiKey;

        $c = Http::acceptJson()
            ->retry(2, 300) // retry ringan
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

    /** Normalisasi payload session dari WAHA menjadi bentuk baku */
    protected function normalizeSession($json): array
    {
        $state = $this->readState($json);      // bisa null, aman
        $qr    = $this->readQr($json);         // bisa null, aman
        $conn  = $this->readConnected($json, $state);

        return [
            'success'   => true,
            'connected' => $conn,
            'state'     => $state,
            'qr'        => $qr,
            'error'     => null,
            'raw'       => $json,
        ];
    }

    /** baca state dari berbagai kemungkinan key TANPA error notice */
    protected function readState($json): ?string
    {
        if (!is_array($json)) return null;
        $candidates = [
            'state', 'status',
            // beberapa implementasi meletakkan di nested:
            'session.state', 'data.state', 'result.state',
        ];
        foreach ($candidates as $k) {
            $v = $this->arrGet($json, $k);
            if (is_string($v) && $v !== '') return $v;
        }
        return null;
    }

    /** baca QR dari kemungkinan key */
    protected function readQr($json): ?string
    {
        if (!is_array($json)) return null;
        $candidates = ['qr', 'qrcode', 'qrCode', 'data.qr', 'result.qr', 'image'];
        foreach ($candidates as $k) {
            $v = $this->arrGet($json, $k);
            if (is_string($v) && $v !== '') return $v;
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
            if (in_array($up, ['DISCONNECTED','LOGGED_OUT','STOPPED'], true)) return false;
        }
        return null;
    }

    /** safe getter untuk key bertitik, tanpa notice */
    protected function arrGet(array $arr, string $dotKey)
    {
        if (isset($arr[$dotKey])) return $arr[$dotKey];
        $keys = explode('.', $dotKey);
        $cur = $arr;
        foreach ($keys as $k) {
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

    protected function e(string $s): string
    {
        return rawurlencode($s);
    }

    protected function is404(array $resp): bool
    {
        $err = (string)($resp['error'] ?? '');
        return str_contains($err, '404') || str_contains($err, 'Not Found');
    }
}
