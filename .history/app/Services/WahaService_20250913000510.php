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
        $this->baseUrl = config('services.waha.url');
        $this->apiKey = config('services.waha.key');
    }

    /**
     * Send a text message.
     *
     * @param WahaSender $sender
     * @param string $recipient
     * @param string $message
     * @return array|null
     */
    public function sendMessage(WahaSender $sender, string $recipient, string $message): ?array
    {
        return $this->sendRequest('POST', "/api/{$sender->session}/send-text", [
            'chatId' => $recipient,
            'text' => $message,
        ]);
    }

    /**
     * Send a message using a pre-approved template.
     *
     * @param WahaSender $sender
     * @param string $recipient
     * @param string $templateName
     * @param array $templateParams
     * @return array|null
     */
    public function sendTemplate(WahaSender $sender, string $recipient, string $templateName, array $templateParams = []): ?array
    {
        return $this->sendRequest('POST', "/api/{$sender->session}/send-template", [
            'chatId' => $recipient,
            'name' => $templateName,
            'params' => $templateParams,
        ]);
    }

    /**
     * Generic method to handle API requests to Waha.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return array|null
     */
    protected function sendRequest(string $method, string $endpoint, array $data): ?array
    {
        if (!$this->baseUrl) {
            Log::error('WahaService: WAHA_URL is not configured.');
            return null;
        }

        try {
            $request = Http::withHeaders($this->getAuthHeaders())
                ->timeout(30);

            $response = $request->{strtolower($method)}($this->baseUrl . $endpoint, $data);

            if ($response->failed()) {
                Log::error('WahaService Error: Failed to send request to ' . $endpoint, [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'data' => $data,
                ]);
                return null;
            }

            return $response->json();
        } catch (Throwable $e) {
            Log::error('WahaService Exception: ' . $e->getMessage(), [
                'endpoint' => $endpoint,
                'data' => $data,
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Get authentication headers.
     *
     * @return array
     */
    protected function getAuthHeaders(): array
    {
        return $this->apiKey ? ['X-Api-Key' => $this->apiKey] : [];
    }
}