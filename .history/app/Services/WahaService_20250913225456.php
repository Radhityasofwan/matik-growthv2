<?php

namespace App\Services;

use App\Models\WahaSender;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WahaService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $userAgent;
    protected bool $insecure;

    public function __construct()
    {
        $this->baseUrl   = rtrim((string) config('services.waha.url'), '/'); // ex: https://waha.matik.id
        $this->apiKey    = (string) config('services.waha.key');            // ex: matikmct
        $this->userAgent = (string) env('WAHA_UA', 'Matik Growth Hub');
        $this->insecure  = (bool) env('WAHA_INSECURE', false);
    }

    /* ------------------- Kirim Pesan (tetap ada) ------------------- */

    public function sendMessage(WahaSender $sender, string $recipient, string $message): ?array
    {
        $session = $this->resolveSession($sender);
        $digits  = $this->digits($recipient);
        $chatId  = $this->jidCUs($digits);

        $payload = ['chatId'=>$chatId, 'text'=>$message, 'session'=>$session];
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
        $digits  = $this->digits($recipient);
        $chatId  = $this->jidCUs($digits);

        $payload = ['chatId'=>$chatId, 'name'=>$templateName, 'params'=>$templateParams, 'session'=>$session];
        $paths = [
            '/api/sendTemplate',
            '/api/send-template',
            "/api/{$this->safe($session)}/send-template",
            "/api/{$this->safe($session)}/template",
        ];
        return $this->tryPost($paths, $payload);
    }

    /* ------------------- Session / QR ------------------- */

    /** Pastikan sesi dimulai (agar QR tersedia). Return true jika panggilan tidak gagal total. */
    public function ensureStarted(string $session): bool
    {
        $session = $this->safe($session);
        $payload = ['session' => $session];

        $candidates = [
            ['POST', "/api/sessions/{$session}/start", []],
            ['POST', "/api/{$session}/start", []],
            ['POST', "/api/start", $payload],
        ];
        foreach ($candidates as [$m, $p, $data]) {
            try {
                $res = $this->client()->{$this->method($m)}($this->url($p), $data);
                if ($res->status() >= 200 && $res->status() < 500) {
                    return true; // bahkan 4xx non-fatal → lanjut polling
                }
            } catch (\Throwable $e) {
                Log::warning('WAHA ensureStarted error', compact('p') + ['err'=>$e->getMessage()]);
            }
        }
        return false;
    }

    /** Ambil status & (jika perlu) QR (data URL). */
    public function qrStatus(string $session): array
    {
        $session = $this->safe($session);

        // 1) coba status
        $statusCandidates = [
            "/api/sessions/{$session}/status",
            "/api/sessions/{$session}/state",
            "/api/{$session}/status",
            "/api/{$session}/state",
        ];
        $state = null;
        foreach ($statusCandidates as $p) {
            try {
                $res = $this->client()->get($this->url($p));
                if ($res->successful()) {
                    $j = $res->json();
                    $state = $j['state'] ?? $j['status'] ?? $j['data']['state'] ?? null;
                    if ($state) break;
                }
            } catch (\Throwable $e) { /* terus */ }
        }

        // 2) jika butuh QR → ambil qrcode
        $qrDataUrl = null;
        if (!$state || in_array(strtoupper($state), ['WAITING_QR','SCAN_QR','QRCODE','PAIRING','INITIALIZING'])) {
            $qrCandidates = [
                "/api/sessions/{$session}/qr?image=true",
                "/api/{$session}/qr?image=true",
                "/api/sessions/{$session}/qrcode",
                "/api/{$session}/qrcode",
            ];
            foreach ($qrCandidates as $p) {
                try {
                    $res = $this->client()->get($this->url($p));
                    if ($res->successful()) {
                        // sebagian server kirim base64 langsung, sebagian url → samakan menjadi data URL
                        $body = $res->json();
                        $b64  = $body['qr'] ?? $body['data']['qr'] ?? null;
                        if (!$b64 && is_string($body)) $b64 = $body;
                        if ($b64) { $qrDataUrl = str_starts_with($b64, 'data:') ? $b64 : ('data:image/png;base64,' . $b64); break; }
                    }
                } catch (\Throwable $e) { /* lanjut */ }
            }
        }

        return [
            'state' => $state ?? ($qrDataUrl ? 'WAITING_QR' : 'UNKNOWN'),
            'qr'    => $qrDataUrl,
        ];
    }

    public function logout(string $session): bool
    {
        $session = $this->safe($session);
        $candidates = [
            ['POST', "/api/sessions/{$session}/logout", []],
            ['POST', "/api/{$session}/logout", []],
        ];
        foreach ($candidates as [$m,$p,$data]) {
            try {
                $res = $this->client()->{$this->method($m)}($this->url($p), $data);
                if ($res->successful()) return true;
            } catch (\Throwable $e) {}
        }
        return false;
    }

    /* ------------------- Helpers ------------------- */

    protected function client()
    {
        $c = Http::acceptJson()->timeout(30)
            ->withHeaders(['x-api-key'=>$this->apiKey,'User-Agent'=>$this->userAgent]);
        return $this->insecure ? $c->withoutVerifying() : $c;
    }
    protected function url(string $path): string { return $this->baseUrl . (str_starts_with($path,'/')?$path:"/$path"); }
    protected function method(string $m): string { return strtolower($m); }
    protected function safe(string $s): string { return rawurlencode($s); }
    protected function digits(string $raw): string {
        $d = preg_replace('/\D+/', '', $raw) ?: $raw;
        if (str_starts_with($d, '0')) $d = '62' . substr($d, 1);
        return $d;
    }
    protected function jidCUs(string $digits): string { return str_contains($digits,'@') ? $digits : $digits.'@c.us'; }

    protected function resolveSession(WahaSender $sender): string
    {
        foreach (['session','session_name'] as $f) if (!empty($sender->{$f})) return (string)$sender->{$f};
        return Str::slug($sender->name ?: 'default'); // fallback
    }

    protected function tryPost(array $paths, array $payload): ?array
    {
        foreach ($paths as $p) {
            try {
                $res = $this->client()->post($this->url($p), $payload);
                if ($res->successful()) return $res->json() ?: ['success'=>true];
            } catch (\Throwable $e) {
                Log::warning('WAHA post failed', ['path'=>$p,'err'=>$e->getMessage()]);
            }
        }
        return null;
    }
}
