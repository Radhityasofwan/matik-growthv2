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
        $this->baseUrl = rtrim((string) config('services.waha.url'), '/');
        $this->apiKey  = config('services.waha.key');
        $this->insecure = (bool) env('WAHA_INSECURE', false); // set true jika pakai self-signed TLS
    }

    /* =========================
     *  Messages
     * ========================= */

    public function sendMessage(WahaSender $sender, string $recipient, string $message): ?array
    {
        $session = $this->resolveSession($sender);

        // Kirim paket lengkap agar kompatibel berbagai server
        $digits = $this->digits($recipient);
        $jid1   = $this->jidCUs($recipient);
        $jid2   = $this->jidSNet($recipient);

        $payload = [
            'text'   => $message,
            'phone'  => $digits,
            'chatId' => $jid1,
            'jid'    => $jid1,
        ];

        $paths = [
            "/api/{$session}/send-text",
            "/api/{$session}/send-message",
            "/api/{$session}/sendMessage",
            "/api/{$session}/messages/text",
        ];

        $resp = $this->tryEndpoints('POST', $paths, $payload);
        if ($resp === null) {
            // Fallback: coba JID @s.whatsapp.net
            $payload['chatId'] = $jid2;
            $payload['jid']    = $jid2;
            $resp = $this->tryEndpoints('POST', $paths, $payload);
        }

        return $resp;
    }

    public function sendTemplate(WahaSender $sender, string $recipient, string $templateName, array $templateParams = []): ?array
    {
        $session = $this->resolveSession($sender);

        $digits = $this->digits($recipient);
        $jid1   = $this->jidCUs($recipient);
        $jid2   = $this->jidSNet($recipient);

        $payload = [
            'name'   => $templateName,
            'params' => $templateParams,
            'phone'  => $digits,
            'chatId' => $jid1,
            'jid'    => $jid1,
        ];

        $paths = [
            "/api/{$session}/send-template",
            "/api/{$session}/template",
            "/api/{$session}/messages/template",
        ];

        $resp = $this->tryEndpoints('POST', $paths, $payload);
        if ($resp === null) {
            $payload['chatId'] = $jid2;
            $payload['jid']    = $jid2;
            $resp = $this->tryEndpoints('POST', $paths, $payload);
        }

        return $resp;
    }

    /* =========================
     *  Sessions & Utils
     * ========================= */

    public function getSessionStatus(WahaSender $sender): ?array
    {
        $session = $this->resolveSession($sender);
        return $this->tryEndpoints('GET', ["/api/{$session}/status"], []);
    }

    public function startSession(WahaSender $sender): ?array
    {
        $session = $this->resolveSession($sender);
        return $this->tryEndpoints('POST', ["/api/{$session}/start"], []);
    }

    public function logoutSession(WahaSender $sender): ?array
    {
        $session = $this->resolveSession($sender);
        return $this->tryEndpoints('POST', ["/api/{$session}/logout"], []);
    }

    public function getQrCode(WahaSender $sender): ?array
    {
        $session = $this->resolveSession($sender);
        return $this->tryEndpoints('GET', ["/api/{$session}/qr"], []);
    }

    public function checkNumber(WahaSender $sender, string $phone): ?array
    {
        $session = $this->resolveSession($sender);
        $payload = [
            'phone'  => $this->digits($phone),
            'chatId' => $this->jidCUs($phone),
            'jid'    => $this->jidCUs($phone),
        ];
        return $this->tryEndpoints('POST', ["/api/{$session}/check-number"], $payload);
    }

    public function listTemplates(WahaSender $sender): ?array
    {
        $session = $this->resolveSession($sender);
        return $this->tryEndpoints('GET', ["/api/{$session}/templates"], []);
    }

    /* =========================
     *  Success heuristic
     * ========================= */

    public function isSuccessful(?array $resp): bool
    {
        if ($resp === null) return false;
        // variasi sukses umum
        foreach (['success','sent','ok','queued'] as $k) {
            if (isset($resp[$k]) && $resp[$k]) return true;
        }
        if (isset($resp['messageId']) || isset($resp['id'])) return true;
        if (isset($resp['error']) || isset($resp['errors'])) return false;
        // response 2xx tanpa error -> anggap berhasil
        return true;
    }

    /* =========================
     *  Low-level helpers
     * ========================= */

    protected function resolveSession(WahaSender $sender): string
    {
        foreach (['session', 'session_name', 'sessionId', 'session_key'] as $prop) {
            if (!empty($sender->{$prop})) return (string) $sender->{$prop};
        }
        throw new \RuntimeException('WahaService: sender session is empty.');
    }

    protected function digits(string $raw): string
    {
        return preg_replace('/\D+/', '', $raw) ?: $raw;
    }

    protected function jidCUs(string $raw): string
    {
        $raw = trim($raw);
        if (str_contains($raw, '@')) return $raw;
        return $this->digits($raw) . '@c.us';
    }

    protected function jidSNet(string $raw): string
    {
        $raw = trim($raw);
        if (str_contains($raw, '@')) return $raw;
        return $this->digits($raw) . '@s.whatsapp.net';
    }

    /**
     * Coba banyak endpoint & banyak skema otentikasi.
     * Return pertama yang 2xx; selain itu null.
     */
    protected function tryEndpoints(string $method, array $paths, array $data): ?array
    {
        foreach ($paths as $endpoint) {
            $resp = $this->sendRequest($method, $endpoint, $data);
            if ($resp !== null) return $resp;
        }
        return null;
    }

    protected function sendRequest(string $method, string $endpoint, array $data = []): ?array
    {
        if (!$this->baseUrl) {
            Log::error('WahaService: WAHA_URL is not configured.');
            return null;
        }

        $url = $this->baseUrl . $endpoint;

        // siapkan 3 variasi auth: Bearer, X-API-KEY, X-Api-Key (+ fallback query)
        $clients = [];

        $base = Http::timeout(30)->acceptJson();
        if ($this->insecure) $base = $base->withoutVerifying();

        $bearer = $this->apiKey ? $base->withToken($this->apiKey) : $base;
        $clients[] = [$bearer, $url];

        if ($this->apiKey) {
            $clients[] = [$base->withHeaders(['X-API-KEY' => $this->apiKey]), $url];
            $clients[] = [$base->withHeaders(['X-Api-Key' => $this->apiKey]), $url];
            // query apikey
            $urlWithQuery = $url . (str_contains($url, '?') ? '&' : '?') . 'apikey=' . urlencode($this->apiKey);
            $clients[] = [$base, $urlWithQuery];
        }

        foreach ($clients as [$client, $u]) {
            try {
                $res = strtoupper($method) === 'GET'
                    ? $client->get($u, $data)
                    : $client->{strtolower($method)}($u, $data);

                if ($res->successful()) {
                    $json = $res->json();
                    Log::debug('WAHA OK', ['url' => $u, 'json' => $json]);
                    return $json;
                }

                Log::warning('WAHA non-2xx', [
                    'url'     => $u,
                    'status'  => $res->status(),
                    'body'    => $res->body(),
                ]);
            } catch (Throwable $e) {
                Log::error('WAHA exception', ['url'=>$u, 'error'=>$e->getMessage()]);
            }
        }

        return null;
    }
}
