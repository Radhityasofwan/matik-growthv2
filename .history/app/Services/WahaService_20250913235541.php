<?php

namespace App\Services;

use App\Models\WahaSender;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaService
{
    protected string $baseUrl;   // contoh: https://waha.matik.id  (TANPA /api)
    protected ?string $apiKey;   // opsional; isi kalau server-mu pakai X-API-KEY
    protected string $userAgent;
    protected bool $insecure;

    public function __construct()
    {
        $this->baseUrl   = rtrim((string) config('services.waha.url'), '/');
        $this->apiKey    = config('services.waha.key'); // bisa null
        $this->userAgent = (string) env('WAHA_UA', 'Matik Growth Hub');
        $this->insecure  = (bool) env('WAHA_INSECURE', false);
    }

    /**
     * Kirim pesan teks sesuai dok WAHA:
     * POST {base}/api/sendText
     * Body: { "session": "...", "chatId": "...@c.us", "text": "..." }
     */
    public function sendMessage(WahaSender $sender, string $recipient, string $message): ?array
    {
        $session = $this->resolveSession($sender);
        $chatId  = $this->toChatId($recipient);

        $payload = [
            'session' => $session,
            'chatId'  => $chatId,
            'text'    => $message,
        ];

        $url = $this->baseUrl . '/api/sendText';

        try {
            $req = $this->client();
            $res = $req->post($url, $payload);

            if ($res->failed()) {
                Log::warning('Waha sendText non-2xx', [
                    'url'    => $url,
                    'status' => $res->status(),
                    'body'   => mb_substr($res->body(), 0, 500),
                    'payload'=> $payload,
                ]);
                return null;
            }

            return $res->json() ?: ['success' => true];
        } catch (\Throwable $e) {
            Log::error('Waha sendText exception', [
                'url'     => $url,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /** ------------ Helpers ------------ */

    protected function client()
    {
        $headers = ['User-Agent' => $this->userAgent];
        if (!empty($this->apiKey)) {
            // banyak deploy WAHA memakai header 'x-api-key'
            $headers['x-api-key'] = $this->apiKey;
        }

        $c = Http::acceptJson()->timeout(30)->withHeaders($headers);
        return $this->insecure ? $c->withoutVerifying() : $c;
    }

    /** Ambil nama session dari model (fallback 'default') */
    protected function resolveSession(WahaSender $sender): string
    {
        foreach (['session', 'session_name', 'sessionId', 'session_key'] as $f) {
            if (!empty($sender->{$f})) {
                return (string) $sender->{$f};
            }
        }
        return 'default';
    }

    /**
     * Normalisasi ke chatId WA:
     * - jika sudah mengandung '@' → pakai apa adanya
     * - angka / +62 → pakai @c.us (leading 0 → 62)
     * - khusus newsletter: kalau input sudah berakhiran '@newsletter' biarkan
     */
    protected function toChatId(string $raw): string
    {
        $raw = trim($raw);

        if (str_contains($raw, '@')) {
            return $raw; // sudah chatId (mis. 12123@newsletter atau 628xx@c.us)
        }

        // angka saja
        $digits = preg_replace('/\D+/', '', $raw) ?: $raw;

        // normalisasi 0xxxx → 62xxxx
        if (preg_match('/^0\d+$/', $digits)) {
            $digits = '62' . substr($digits, 1);
        } elseif (preg_match('/^\+?\d+$/', $raw)) {
            $digits = ltrim($raw, '+');
        }

        return $digits . '@c.us';
    }
}
