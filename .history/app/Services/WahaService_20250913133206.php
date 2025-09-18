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

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.waha.url'), '/');
        $this->apiKey  = config('services.waha.key');
    }

    /* =========================
     *  Public API (Messages)
     * ========================= */
    public function sendMessage(WahaSender $sender, string $recipient, string $message): ?array
    {
        $session = $this->resolveSession($sender);
        $digits  = $this->digits($recipient);
        $jid     = $this->jid($recipient);

        // Kirim semua field yang umum dipakai (chatId/phone/jid) agar kompatibel berbagai versi WAHA
        $payload = [
            'chatId' => $jid,
            'phone'  => $digits,
            'jid'    => $jid,
            'text'   => $message,
        ];

        // Coba endpoint populer
        $resp = $this->sendRequest('POST', "/api/{$session}/send-text", $payload);
        if ($resp === null) {
            // fallback lain (beberapa distro memakai path ini)
            $resp = $this->sendRequest('POST', "/api/{$session}/send-message", $payload);
        }
        return $resp;
    }

    public function sendTemplate(WahaSender $sender, string $recipient, string $templateName, array $templateParams = []): ?array
    {
        $session = $this->resolveSession($sender);
        $digits  = $this->digits($recipient);
        $jid     = $this->jid($recipient);

        $payload = [
            'chatId' => $jid,
            'phone'  => $digits,
            'jid'    => $jid,
            'name'   => $templateName,
            'params' => $templateParams,
        ];

        $resp = $this->sendRequest('POST', "/api/{$session}/send-template", $payload);
        if ($resp === null) {
            $resp = $this->sendRequest('POST', "/api/{$session}/template", $payload);
        }
        return $resp;
    }

    /* =========================
     *  Public API (Sessions/Utils)
     * ========================= */
    public function getSessionStatus(WahaSender $sender): ?array
    {
        $session = $this->resolveSession($sender);
        return $this->sendRequest('GET', "/api/{$session}/status");
    }
    public function startSession(WahaSender $sender): ?array
    {
        $session = $this->resolveSession($sender);
        return $this->sendRequest('POST', "/api/{$session}/start");
    }
    public function logoutSession(WahaSender $sender): ?array
    {
        $session = $this->resolveSession($sender);
        return $this->sendRequest('POST', "/api/{$session}/logout");
    }
    public function getQrCode(WahaSender $sender): ?array
    {
        $session = $this->resolveSession($sender);
        return $this->sendRequest('GET', "/api/{$session}/qr");
    }
    public function checkNumber(WahaSender $sender, string $phone): ?array
    {
        $session = $this->resolveSession($sender);
        return $this->sendRequest('POST', "/api/{$session}/check-number", [
            'chatId' => $this->jid($phone),
            'phone'  => $this->digits($phone),
            'jid'    => $this->jid($phone),
        ]);
    }
    public function listTemplates(WahaSender $sender): ?array
    {
        $session = $this->resolveSession($sender);
        return $this->sendRequest('GET', "/api/{$session}/templates");
    }

    /* =========================
     *  Success helper for controllers
     * ========================= */
    public function isSuccessful(?array $resp): bool
    {
        if ($resp === null) return false;
        // indikasi sukses yang umum di berbagai varian
        if (isset($resp['success']) && $resp['success']) return true;
        if (isset($resp['sent'])    && $resp['sent'])    return true;
        if (isset($resp['ok'])      && $resp['ok'])      return true;
        if (isset($resp['queued'])  && $resp['queued'])  return true;
        if (isset($resp['messageId']) || isset($resp['id'])) return true;
        if (isset($resp['error']) || isset($resp['errors'])) return false;
        // fallback: response 2xx tanpa field error dianggap berhasil
        return true;
    }

    /* =========================
     *  Low level
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

    protected function jid(string $raw): string
    {
        $raw = trim($raw);
        if (str_contains($raw, '@')) return $raw; // sudah chatId
        return $this->digits($raw) . '@c.us';     // default JID untuk user
    }

    protected function sendRequest(string $method, string $endpoint, array $data = []): ?array
    {
        if (!$this->baseUrl) {
            Log::error('WahaService: WAHA_URL is not configured.');
            return null;
        }

        $url = $this->baseUrl . $endpoint;

        try {
            $req = Http::withHeaders($this->getAuthHeaders())->timeout(30);

            $resp = strtoupper($method) === 'GET'
                ? $req->get($url, $data)
                : $req->{strtolower($method)}($url, $data);

            if ($resp->failed()) {
                Log::error('WahaService Error', [
                    'method'   => $method,
                    'endpoint' => $endpoint,
                    'status'   => $resp->status(),
                    'response' => $resp->body(),
                    'data'     => $data,
                ]);
                return null;
            }
            $json = $resp->json();
            // Simpan minimal id untuk debugging
            Log::debug('WahaService OK', ['endpoint'=>$endpoint, 'json'=>$json]);
            return $json;
        } catch (Throwable $e) {
            Log::error('WahaService Exception: '.$e->getMessage(), [
                'method'   => $method,
                'endpoint' => $endpoint,
                'data'     => $data,
            ]);
            return null;
        }
    }

    protected function getAuthHeaders(): array
    {
        return $this->apiKey ? ['X-Api-Key' => $this->apiKey] : [];
    }
}
