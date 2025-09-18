<?php

namespace App\Services;

use App\Models\WahaSender;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WahaService
{
    /**
     * Kirim pesan WhatsApp via WAHA.
     * - Jika base URL tidak dikonfigurasi → SIMULASI (tetap success) agar alur sistem stabil.
     * - Kembalikan struktur yang dipakai controller: success, message_id, status, path, raw.
     */
    public function sendMessage(WahaSender $sender, string $recipient, string $text): array
    {
        $to = $this->sanitize($recipient);
        if (!$to) {
            return [
                'success' => false,
                'error'   => 'INVALID_RECIPIENT',
                'message' => 'Nomor tujuan tidak valid',
            ];
        }

        // Ambil konfigurasi WAHA
        $base    = $sender->base_url ?? config('services.waha.base_url') ?? env('WAHA_BASE_URL');
        $session = $sender->session ?? $sender->name ?? $sender->number ?? 'default';

        // Tanpa konfigurasi → mode simulasi
        if (!$base) {
            $id = 'SIM-'.Str::uuid();
            Log::info('[WAHA][SIMULATED] sendMessage', [
                'sender_id' => $sender->id,
                'session'   => $session,
                'to'        => $to,
                'text'      => Str::limit($text, 160),
            ]);

            return [
                'success'     => true,
                'message_id'  => $id,
                'status'      => 'SIMULATED',
                'path'        => null,
                'raw'         => ['simulated' => true, 'session' => $session, 'to' => $to],
            ];
        }

        $url = rtrim($base, '/') . '/sendText'; // sesuaikan jika endpoint WAHA berbeda
        $payload = [
            'session' => $session,
            'to'      => $to,
            'text'    => $text,
        ];

        try {
            $resp = Http::timeout(20)->post($url, $payload);
            $json = $resp->json() ?? [];
            $msgId = $this->extractMessageId($json);

            Log::info('[WAHA] sendMessage', [
                'sender_id' => $sender->id,
                'status'    => $resp->status(),
                'msg_id'    => $msgId,
                'url'       => $url,
            ]);

            return [
                'success'     => $resp->successful(),
                'message_id'  => $msgId,
                'status'      => $json['status'] ?? ($resp->successful() ? 'OK' : 'ERROR'),
                'path'        => $url,
                'raw'         => $json,
            ];
        } catch (\Throwable $e) {
            Log::error('[WAHA] sendMessage error: '.$e->getMessage(), ['sender_id' => $sender->id]);
            return [
                'success' => false,
                'error'   => 'EXCEPTION',
                'message' => $e->getMessage(),
            ];
        }
    }

    /** Ambil angka dari input & normalisasi ringan (0xxxx → 62xxxx). */
    protected function sanitize(?string $p): ?string
    {
        if (!$p) return null;
        $n = preg_replace('/\D+/', '', $p);
        if (Str::startsWith($n, '0') && strlen($n) >= 9) {
            $n = '62' . substr($n, 1);
        }
        return $n ?: null;
    }

    /** Coba ambil message id dari berbagai bentuk respons WAHA. */
    protected function extractMessageId(array $json): ?string
    {
        return $json['message_id']
            ?? data_get($json, 'data.message_id')
            ?? data_get($json, 'result.key.id')
            ?? data_get($json, 'key.id')
            ?? data_get($json, 'id')
            ?? null;
    }
}
