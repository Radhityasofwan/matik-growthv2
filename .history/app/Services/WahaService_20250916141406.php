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
        // Contoh: WAHA_URL=https://waha.matik.id
        $this->baseUrl   = rtrim((string) config('services.waha.url'), '/');
        // Contoh: WAHA_KEY=matikmct
        $this->apiKey    = (string) config('services.waha.key');
        $this->userAgent = (string) env('WAHA_UA', 'Matik Growth Hub');
        $this->insecure  = (bool) env('WAHA_INSECURE', false);
    }

    /* ============================================================
     *  KIRIM PESAN TEKS (sinkron dengan React client yang kamu beri)
     *  POST /api/sendText
     *  Header: x-api-key
     *  Body  : { chatId, text, session }
     * ============================================================ */
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

        // Urutan endpoint: primary (sesuai React) lalu beberapa fallback umum
        $paths = [
            '/api/sendText',
            '/api/send-text',
            "/api/{$this->safe($session)}/send-text",
            "/api/{$this->safe($session)}/sendMessage",
        ];

        return $this->tryPost($paths, $payload);
    }

    /* ============================================================
     *  KIRIM TEMPLATE (opsional—aktif bila server WAHA mendukung)
     *  POST /api/sendTemplate { chatId, name, params, session }
     * ============================================================ */
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

    /* ============================================================
     *  HEALTH (best effort; tidak wajib untuk pengiriman)
     * ============================================================ */
    public function health(): ?array
    {
        foreach (['/health', '/api/health'] as $p) {
            try {
                $res = $this->client()->get($this->url($p));
                if ($res->successful()) return $res->json();
            } catch (\Throwable $e) {
                Log::warning('WAHA health check failed', ['url' => $this->url($p), 'err' => $e->getMessage()]);
            }
        }
        return null;
    }

    /* ========================= Helpers (HTTP) ========================= */

    protected function client()
    {
        $c = Http::acceptJson()
            ->timeout(30)
            ->withHeaders([
                // Node/Express case-insensitive; pakai yang disyaratkan React client kamu
                'x-api-key'  => $this->apiKey,
                'User-Agent' => $this->userAgent,
            ]);

        return $this->insecure ? $c->withoutVerifying() : $c;
    }

    protected function tryPost(array $paths, array $payload): ?array
    {
        foreach ($paths as $p) {
            try {
                $url = $this->url($p);
                $res = $this->client()->post($url, $payload);

                if ($res->successful()) {
                    $json = $res->json();
                    Log::debug('WAHA OK', ['url' => $url, 'json' => $json]);
                    // Beberapa server WAHA mengembalikan string/empty body → tetap anggap success
                    return is_array($json) ? $json : ['success' => true, 'raw' => $res->body()];
                }

                Log::warning('WAHA non-2xx', [
                    'url'    => $url,
                    'status' => $res->status(),
                    'body'   => mb_substr($res->body(), 0, 800),
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

    /* ========================= Helpers (Data) ========================= */

    protected function resolveSession(WahaSender $sender): string
    {
        foreach (['session', 'session_name', 'sessionId', 'session_key'] as $f) {
            if (!empty($sender->{$f})) return (string) $sender->{$f};
        }
        return (string) ($sender->session ?? 'default');
    }

    protected function digits(string $raw): string
    {
        $d = preg_replace('/\D+/', '', $raw) ?: $raw;
        if (str_starts_with($d, '0')) $d = '62' . substr($d, 1); // normalisasi 0xxxx → 62xxxx
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

    /* ===================== Compat Helpers (UI lama) ===================== */

    /**
     * Kompatibilitas: beberapa tempat memanggil $this->waha->isSuccessful($resp)
     * Deteksi sukses dari berbagai bentuk respons WAHA.
     */
    public function isSuccessful(?array $resp): bool
    {
        if ($resp === null) return false;

        // Bentuk paling umum
        if (array_key_exists('success', $resp)) {
            return filter_var($resp['success'], FILTER_VALIDATE_BOOLEAN);
        }

        // Jika ada key error/errors → gagal
        foreach (['error', 'errors'] as $k) {
            if (!empty($resp[$k])) return false;
        }

        // Beberapa server pakai 'result' / 'status'
        foreach (['result', 'status'] as $k) {
            if (isset($resp[$k])) {
                $v = strtolower((string) $resp[$k]);
                if (in_array($v, ['ok','success','sent','queued','true','accepted'], true)) return true;
                if (in_array($v, ['error','fail','failed','false'], true))          return false;
            }
        }

        // Jika ada id/messageId → anggap sukses
        if (isset($resp['id']) || isset($resp['messageId'])) return true;

        // Default: ada JSON tanpa sinyal error → anggap sukses
        return !empty($resp);
    }

    /**
     * Ambil pesan error ramah pengguna dari respons WAHA (jika ada).
     */
    public function getError(?array $resp): ?string
    {
        if ($resp === null) return 'Tidak ada respons dari WAHA.';
        foreach (['error','message','detail','reason'] as $k) {
            if (!empty($resp[$k])) {
                return is_string($resp[$k]) ? $resp[$k] : json_encode($resp[$k], JSON_UNESCAPED_SLASHES);
            }
        }
        return null;
    }
}
