<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppClient
{
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.whatsapp.api_key');
        $this->apiUrl = config('services.whatsapp.api_url');
    }

    /**
     * Mengirim pesan WhatsApp.
     *
     * @param string $to
     * @param string $message
     * @return bool
     */
    public function sendMessage(string $to, string $message): bool
    {
        // Ini adalah implementasi MOCK/SIMULASI.
        // Ganti dengan logika API call ke provider WhatsApp Anda (e.g., Twilio, UltraMsg).

        if (empty($this->apiKey) || empty($this->apiUrl)) {
            Log::warning('WhatsApp API key or URL is not configured. Simulating message sending.');
            // Simulasi pengiriman berhasil jika tidak ada konfigurasi
            Log::info("Simulated WhatsApp message to {$to}: {$message}");
            return true;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, [
                'to' => $to,
                'body' => $message,
            ]);

            if ($response->successful()) {
                Log::info("WhatsApp message sent successfully to {$to}.");
                return true;
            } else {
                Log::error("Failed to send WhatsApp message to {$to}.", [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::critical('WhatsApp Service Exception.', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
