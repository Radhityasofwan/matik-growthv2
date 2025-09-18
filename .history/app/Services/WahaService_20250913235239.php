<?php
namespace App\Services;

use App\Models\WahaSender;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaService
{
    /* … properti & constructor kamu … */

    /* ===== START SESSION (robust) ===== */
    public function sessionStart(WahaSender $sender): array
    {
        $raw = $this->resolveSession($sender);
        $session = substr(preg_replace('~[^a-z0-9\-_.]+~i', '-', $raw ?: 'default'), 0, 32);
        if ($session === '' || $session === '-') $session = 'default';

        $endpoints = [
            '/api/sessions/start',
            '/api/session/start',
            '/api/start',
            "/api/{$this->safe($session)}/start",
            "/session/{$this->safe($session)}/start",
            "/sessions/{$this->safe($session)}/start",
            '/api/session',
            '/api/sessions',
        ];

        $jsonPayloads = [
            ['name' => $session],
            ['session' => $session],
            ['sessionName' => $session],
            ['name' => $session, 'session' => $session, 'sessionName' => $session],
        ];

        $ok = fn (?Response $r) => $r && ($r->successful() || $r->status() === 204);

        foreach ($endpoints as $p) {
            foreach ($jsonPayloads as $pl) {
                // JSON
                try {
                    $r = $this->client()->post($this->url($p), $pl);
                    if ($ok($r)) {
                        $qr = $this->getQrImage($session);
                        return ['success'=>true,'session'=>$session,'qr'=>$qr,'raw'=>$this->safeJson($r)];
                    }
                    Log::warning('WAHA start non-2xx', ['url'=>$this->url($p),'status'=>$r->status(),'body'=>mb_substr($r->body(),0,500)]);
                } catch (\Throwable $e) {
                    Log::error('WAHA start exception(json)', ['url'=>$this->url($p),'err'=>$e->getMessage()]);
                }

                // FORM
                try {
                    $r = $this->client()->asForm()->post($this->url($p), $pl);
                    if ($ok($r)) {
                        $qr = $this->getQrImage($session);
                        return ['success'=>true,'session'=>$session,'qr'=>$qr,'raw'=>$this->safeJson($r)];
                    }
                    Log::warning('WAHA start non-2xx', ['url'=>$this->url($p),'status'=>$r->status(),'body'=>mb_substr($r->body(),0,500)]);
                } catch (\Throwable $e) {
                    Log::error('WAHA start exception(form)', ['url'=>$this->url($p),'err'=>$e->getMessage()]);
                }
            }
        }

        // POST + querystring (?name=)
        $qs = [
            ['name'=>$session], ['session'=>$session], ['sessionName'=>$session],
        ];
        foreach ($endpoints as $p) {
            foreach ($qs as $q) {
                try {
                    $r = $this->client()->post($this->url($p).'?'.http_build_query($q));
                    if ($ok($r)) {
                        $qr = $this->getQrImage($session);
                        return ['success'=>true,'session'=>$session,'qr'=>$qr,'raw'=>$this->safeJson($r)];
                    }
                    Log::warning('WAHA start non-2xx', ['url'=>$this->url($p).'?'.http_build_query($q),'status'=>$r->status(),'body'=>mb_substr($r->body(),0,500)]);
                } catch (\Throwable $e) {
                    Log::error('WAHA start exception(post+query)', ['url'=>$this->url($p),'err'=>$e->getMessage()]);
                }
            }
        }

        return ['success'=>false,'session'=>$session,'qr'=>null,'raw'=>null,'error'=>'All start attempts failed'];
    }

    /* ===== STATUS + QR (robust) ===== */
    public function sessionStatus(WahaSender $sender): array
    {
        $session = $this->resolveSession($sender);
        $cands = [
            ['/api/sessions/status', ['name'=>$session]],
            ['/api/session/status',  ['name'=>$session]],
            ['/api/status',          ['name'=>$session]],
            ["/api/{$this->safe($session)}/status", []],
            ["/session/{$this->safe($session)}/status", []],
            ["/sessions/{$this->safe($session)}/status", []],
        ];

        foreach ($cands as [$p, $q]) {
            try {
                $r = $this->client()->get($this->url($p), $q);
                if ($r->successful()) {
                    $j = $r->json();
                    // Normalisasi kemungkinan kunci
                    $connected = $j['connected'] ?? $j['isConnected'] ?? $j['online'] ?? null;
                    $state     = $j['state']     ?? $j['status']      ?? null;
                    $qr        = $j['qr']        ?? $j['qrCode']      ?? $j['image'] ?? null;
                    if (!$qr) $qr = $this->getQrImage($session);
                    return ['success'=>true,'connected'=>$connected,'state'=>$state,'qr'=>$qr,'raw'=>$j];
                }
            } catch (\Throwable $e) {
                Log::error('WAHA status exception', ['url'=>$this->url($p),'err'=>$e->getMessage()]);
            }
        }

        // fallback: coba ambil QR langsung
        $qr = $this->getQrImage($session);
        return ['success'=>true,'connected'=>null,'state'=>null,'qr'=>$qr,'raw'=>null];
    }

    /* ===== Ambil QR image (berbagai varian path) ===== */
    public function getQrImage(string $session): ?string
    {
        // urutan kandidat paling sering ditemui
        $gets = [
            ['/api/sessions/qr',      ['name'=>$session,'image'=>1]],
            ['/api/session/qr',       ['name'=>$session,'image'=>1]],
            ['/api/qr',               ['name'=>$session,'image'=>1]],
            ["/api/{$this->safe($session)}/qr", ['image'=>1]],
            ["/session/{$this->safe($session)}/qr", []],
            ["/sessions/{$this->safe($session)}/qr", []],
        ];

        foreach ($gets as [$p, $q]) {
            try {
                $r = $this->client()->get($this->url($p), $q);
                if ($r->successful()) {
                    // Bisa JSON (base64), bisa langsung png bytes
                    $ct = strtolower($r->header('Content-Type') ?? '');
                    if (str_contains($ct, 'image')) {
                        $b64 = base64_encode($r->body());
                        return "data:{$ct};base64,{$b64}";
                    }
                    $j = $r->json();
                    if (is_array($j)) {
                        $v = $j['qr'] ?? $j['image'] ?? $j['data'] ?? $j['png'] ?? $j['base64'] ?? null;
                        if ($v) {
                            // bila belum ada prefix data:
                            if (!str_starts_with($v, 'data:image')) $v = 'data:image/png;base64,'.ltrim($v, ',');
                            return $v;
                        }
                    } else {
                        // mungkin string base64 langsung
                        $s = trim((string) $r->body());
                        if ($s !== '') {
                            if (!str_starts_with($s, 'data:image')) $s = 'data:image/png;base64,'.ltrim($s, ',');
                            return $s;
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::error('WAHA getQr exception', ['url'=>$this->url($p),'err'=>$e->getMessage()]);
            }
        }
        return null;
    }

    /* ====== helpers yg sudah ada di class kamu ======
       - client(), url(), resolveSession(), safe(), safeJson() (kalau belum ada, buat saja kecil: return is_array($r->json())?$r->json():null)
    ====== */
}
