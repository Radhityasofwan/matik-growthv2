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
        return $this->sendRequest('POST', "/api/{$session}/send-text", [
            'chatId' => $this->formatChatId($recipient),
            'text'   => $message,
        ]);
    }

    public function sendTemplate(WahaSender $sender, string $recipient, string $templateName, array $templateParams = []): ?array
    {
        $session = $this->resolveSession($sender);
        return $this->sendRequest('POST', "/api/{$session}/send-template", [
            'chatId' => $this->formatChatId($recipient),
            'name'   => $templateName,
            'params' => $templateParams,
        ]);
    }

    /* =========================
     *  Public API (Sessions)
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

    /* =========================
     *  Public API (Utils)
     * ========================= */
    public function checkNumber(WahaSender $sender, string $phone): ?array
    {
        $session = $this->resolveSession($sender);
        return $this->sendRequest('POST', "/api/{$session}/check-number", [
            'chatId' => $this->formatChatId($phone),
        ]);
    }

    public function listTemplates(WahaSender $sender): ?array
    {
        $session = $this->resolveSession($sender);
        return $this->sendRequest('GET', "/api/{$session}/templates");
    }

    /* =========================
     *  Helpers
     * ========================= */
    protected function resolveSession(WahaSender $sender): string
    {
        // Prioritas: session -> session_name -> sessionId -> session_key
        foreach (['session', 'session_name', 'sessionId', 'session_key'] as $prop) {
            if (!empty($sender->{$prop})) return (string) $sender->{$prop};
        }
        // Kalau tetap kosong, fail-early agar kelihatan di log
        throw new \RuntimeException('WahaService: sender session is empty.');
    }

    protected function formatChatId(string $raw): string
    {
        // Hanya angka; WAHA biasanya menerima msisdn atau chatId lengkap.
        $digits = preg_replace('/\D+/', '', $raw) ?: $raw;
        return $digits;
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

            return $resp->json();
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
