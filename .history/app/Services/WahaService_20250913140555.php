<?php

namespace App\Services;

use App\Models\WahaSender;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WahaService
{
    protected string $baseUrl;
    protected ?string $apiKey;
    protected bool $insecure;

    public function __construct()
    {
        // boleh berakhiran /api atau tidak; kita handle otomatis
        $this->baseUrl  = rtrim((string) config('services.waha.url'), '/');
        $this->apiKey   = config('services.waha.key');
        $this->insecure = (bool) env('WAHA_INSECURE', false);
    }

    /* ================= Messages ================= */

    public function sendMessage(WahaSender $sender, string $recipient, string $message): ?array
    {
        $session = $this->sessionSeg($sender);
        $digits  = $this->digits($recipient);
        $jidC    = $this->jidCUs($recipient);
        $jidS    = $this->jidSNet($recipient);

        $payload = ['text'=>$message, 'phone'=>$digits, 'chatId'=>$jidC, 'jid'=>$jidC];
        $paths   = ["/api/{$session}/send-text", "/api/{$session}/send-message", "/api/{$session}/sendMessage", "/api/{$session}/messages/text"];

        $resp = $this->tryEndpoints('POST', $paths, $payload);
        if ($resp === null) {
            $payload['chatId'] = $jidS; $payload['jid'] = $jidS;
            $resp = $this->tryEndpoints('POST', $paths, $payload);
        }
        return $resp;
    }

    public function sendTemplate(WahaSender $sender, string $recipient, string $templateName, array $templateParams = []): ?array
    {
        $session = $this->sessionSeg($sender);
        $digits  = $this->digits($recipient);
        $jidC    = $this->jidCUs($recipient);
        $jidS    = $this->jidSNet($recipient);

        $payload = ['name'=>$templateName, 'params'=>$templateParams, 'phone'=>$digits, 'chatId'=>$jidC, 'jid'=>$jidC];
        $paths   = ["/api/{$session}/send-template", "/api/{$session}/template", "/api/{$session}/messages/template"];

        $resp = $this->tryEndpoints('POST', $paths, $payload);
        if ($resp === null) {
            $payload['chatId'] = $jidS; $payload['jid'] = $jidS;
            $resp = $this->tryEndpoints('POST', $paths, $payload);
        }
        return $resp;
    }

    /* =============== Sessions/Utils =============== */

    public function getSessionStatus(WahaSender $sender): ?array
    {
        $s = $this->sessionSeg($sender);
        return $this->tryEndpoints('GET', [
            "/api/{$s}/status", "/api/{$s}/state",
            "/api/sessions/{$s}/status", "/api/session/{$s}/status", "/api/sessions/{$s}",
        ], []);
    }

    public function startSession(WahaSender $sender): ?array
    {
        $s = $this->sessionSeg($sender);
        return $this->tryEndpoints('POST', ["/api/{$s}/start", "/api/sessions/{$s}/start"], []);
    }

    public function logoutSession(WahaSender $sender): ?array
    {
        $s = $this->sessionSeg($sender);
        return $this->tryEndpoints('POST', ["/api/{$s}/logout", "/api/sessions/{$s}/logout"], []);
    }

    public function getQrCode(WahaSender $sender): ?array
    {
        $s = $this->sessionSeg($sender);
        return $this->tryEndpoints('GET', ["/api/{$s}/qr", "/api/{$s}/qrcode", "/api/sessions/{$s}/qr"], []);
    }

    public function checkNumber(WahaSender $sender, string $phone): ?array
    {
        $s = $this->sessionSeg($sender);
        $p = ['phone'=>$this->digits($phone), 'chatId'=>$this->jidCUs($phone), 'jid'=>$this->jidCUs($phone)];
        return $this->tryEndpoints('POST', ["/api/{$s}/check-number", "/api/sessions/{$s}/check-number"], $p);
    }

    public function health(): ?array
    {
        return $this->tryEndpoints('GET', ['/health', '/status', '/api/health'], []);
    }

    public function isSuccessful(?array $resp): bool
    {
        if ($resp === null) return false;
        foreach (['success','sent','ok','queued','connected'] as $k) if (($resp[$k] ?? false) === true) return true;
        if (isset($resp['messageId']) || isset($resp['id'])) return true;
        if (isset($resp['error']) || isset($resp['errors']))  return false;
        return true;
    }

    /* =============== Low-level helpers =============== */

    protected function resolveSession(WahaSender $sender): string
    {
        foreach (['session','session_name','sessionId','session_key'] as $p) {
            if (!empty($sender->{$p})) return (string) $sender->{$p};
        }
        throw new \RuntimeException('WahaService: sender session is empty.');
    }

    protected function sessionSeg(WahaSender $sender): string
    {
        // URL-safe (menangani spasi dsb.)
        return rawurlencode($this->resolveSession($sender));
    }

    protected function digits(string $raw): string
    {
        return preg_replace('/\D+/', '', $raw) ?: $raw;
    }

    protected function jidCUs(string $raw): string
    {
        return str_contains($raw,'@') ? $raw : $this->digits($raw).'@c.us';
    }

    protected function jidSNet(string $raw): string
    {
        return str_contains($raw,'@') ? $raw : $this->digits($raw).'@s.whatsapp.net';
    }

    /** Gabungkan baseUrl + endpoint tanpa double /api */
    protected function makeUrl(string $endpoint): string
    {
        $ep = $endpoint;
        $base = $this->baseUrl;

        // jika base berakhir dengan /api dan endpoint diawali /api/, buang salah satunya
        if (str_ends_with($base, '/api') && str_starts_with($ep, '/api/')) {
            $ep = substr($ep, 4); // hapus prefix '/api'
        }
        if (!str_starts_with($ep, '/')) $ep = '/'.$ep;

        return $base.$ep;
    }

    /** coba banyak endpoint & banyak skema auth */
    protected function tryEndpoints(string $method, array $paths, array $data): ?array
    {
        foreach ($paths as $p) {
            $res = $this->sendRequest($method, $p, $data);
            if ($res !== null) return $res;
        }
        return null;
    }

    protected function sendRequest(string $method, string $endpoint, array $data = []): ?array
    {
        if (!$this->baseUrl) {
            Log::error('WahaService: WAHA_URL is not configured.');
            return null;
        }

        $url  = $this->makeUrl($endpoint);
        $base = Http::timeout(30)->acceptJson();
        if ($this->insecure) $base = $base->withoutVerifying();

        // 4 variasi auth: Bearer, X-API-KEY, X-Api-Key, query ?apikey=
        $clients = [];
        $clients[] = [$this->apiKey ? $base->withToken($this->apiKey) : $base, $url];

        if ($this->apiKey) {
            $clients[] = [$base->withHeaders(['X-API-KEY' => $this->apiKey]), $url];
            $clients[] = [$base->withHeaders(['X-Api-Key' => $this->apiKey]), $url];
            $clients[] = [$base, $url.(str_contains($url,'?')?'&':'?').'apikey='.urlencode($this->apiKey)];
        }

        foreach ($clients as [$c, $u]) {
            try {
                $r = strtoupper($method)==='GET' ? $c->get($u, $data) : $c->{strtolower($method)}($u, $data);

                if ($r->successful()) {
                    $j = $r->json();
                    Log::debug('WAHA OK', ['url'=>$u, 'json'=>$j]);
                    return $j;
                }

                Log::warning('WAHA non-2xx', ['url'=>$u,'status'=>$r->status(),'body'=>$r->body()]);
            } catch (Throwable $e) {
                Log::error('WAHA exception', ['url'=>$u, 'error'=>$e->getMessage()]);
            }
        }
        return null;
    }
}
