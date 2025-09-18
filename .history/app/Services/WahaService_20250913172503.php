<?php

namespace App\Services;

use App\Models\WahaSender;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $userAgent;
    protected bool $insecure;

    public function __construct()
    {
        $this->baseUrl   = rtrim((string) config('services.waha.url'), '/');   // ex: https://waha.matik.id
        $this->apiKey    = (string) config('services.waha.key');               // ex: matikmct
        $this->userAgent = (string) env('WAHA_UA', 'Matik Growth Hub');
        $this->insecure  = (bool) env('WAHA_INSECURE', false);
    }

    /* ===========================
     *  KIRIM PESAN TEKS
     *  Sinkron dengan WAHA React:
     *  POST /api/sendText
     *  Header: x-api-key
     *  Body  : { chatId, text, session }
     * =========================== */
    public function sendMessage(WahaSender $sender, string $recipient, string $message): ?array
    {
        $session = $this->resolveSession($sender);
        $digits  = $this->digits($recipient);
        $chatId  = $this->jidCUs($digits);

        $payload = [
            'chatId'  => $chatId,
            'text'    => $message,
            'session' => $session,
        ];

        // Urutan endpoint: yang utama sesuai source kamu, lalu beberapa fallback umum
        $paths = [
            '/api/sendText',               // persis seperti React client kamu
            '/api/send-text',
            "/api/{$this->safe($session)}/send-text",
            "/api/{$this->safe($session)}/sendMessage",
        ];

        return $this->tryPost($paths, $payload);
    }

    /* =====================================
     *  KIRIM TEMPLATE (opsional/fallback)
     *  Kalau server kamu support:
     *  POST /api/sendTemplate { chatId, name, params, session }
     * ===================================== */
    public function sendTemplate(WahaSender $sender, string $recipient, string $templateName, array $templateParams = []): ?array
    {
        $session = $this->resolveSession($sender);
        $digits  = $this->digits($recipient);
        $chatId  = $this->jidCUs($digits);

        $payload = [
            'chatId'  => $chatId,
            'name'    => $templateName,
            'params'  => $templateParams,
            'session' => $session,
        ];

        $paths = [
            '/api/sendTemplate',
            '/api/send-template',
            "/api/{$this->safe($session)}/send-template",
            "/api/{$this->safe($session)}/template",
        ];

        return $this->tryPost($paths, $payload);
    }

    /* ===========================
     *  STATUS / HEALTH (best effort)
     *  Tidak wajib untuk kirim pesan.
     * =========================== */
    public function health(): ?array
    {
        $paths = ['/health', '/api/health'];
        foreach ($paths as $p) {
            $res = $this->client()->get($this->url($p));
            if ($res->successful()) return $res->json();
        }
        return null;
    }

    /* ========= Helpers ========= */

    protected function client()
    {
        $c = Http::acceptJson()->timeout(30)
            ->withHeaders([
                // WAHA kamu mengharapkan header 'x-api-key' (lowercase OK di Node/Express)
                'x-api-key'   => $this->apiKey,
                'User-Agent'  => $this->userAgent,
            ]);

        return $this->insecure ? $c->withoutVerifying() : $c;
    }

    protected function tryPost(array $paths, array $payload): ?array
    {
        foreach ($paths as $p) {
            try {
                $res = $this->client()->post($this->url($p), $payload);
                if ($res->successful()) {
                    $json = $res->json();
                    Log::debug('WAHA OK', ['url' => $this->url($p), 'json' => $json]);
                    return $json ?: ['success' => true];
                }
                // log & lanjut ke fallback
                Log::warning('WAHA non-2xx', [
                    'url'    => $this->url($p),
                    'status' => $res->status(),
                    'body'   => mb_substr($res->body(), 0, 500),
                ]);
            } catch (\Throwable $e) {
                Log::error('WAHA exception', ['url' => $this->url($p), 'err' => $e->getMessage()]);
            }
        }
        return null;
    }

    protected function url(string $path): string
    {
        $p = str_starts_with($path, '/') ? $path : "/{$path}";
        return $this->baseUrl . $p;
    }

    protected function resolveSession(WahaSender $sender): string
    {
        foreach (['session', 'session_name', 'sessionId', 'session_key'] as $f) {
            if (!empty($sender->{$f})) return (string) $sender->{$f};
        }
        // fallback aman
        return (string) ($sender->session ?? 'default');
    }

    protected function digits(string $raw): string
    {
        $d = preg_replace('/\D+/', '', $raw) ?: $raw;
        // normalisasi awalan 0 -> 62 (sesuai contoh client)
        if (str_starts_with($d, '0')) $d = '62' . substr($d, 1);
        return $d;
    }

    protected function jidCUs(string $digits): string
    {
        return str_contains($digits, '@') ? $digits : $digits . '@c.us';
    }

    protected function safe(string $s): string
    {
        return rawurlencode($s);
    }
}
