<?php

namespace App\Services;

use App\Models\WahaSender;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WahaService
{
    /* ===================== CONFIG & HTTP ====================== */

    protected function apiBase(?WahaSender $sender = null): ?string
    {
        $base = null;

        if ($sender && isset($sender->base_url) && is_string($sender->base_url) && $sender->base_url !== '') {
            $base = $sender->base_url;
        }

        $base = $base
            ?? config('services.waha.base_url')
            ?? env('WAHA_URL')
            ?? env('WAHA_BASE_URL');

        if (!$base) return null;

        $base = rtrim($base, '/');
        if (!preg_match('~/api/?$~i', $base)) {
            $base .= '/api';
        }
        return $base;
    }

    protected function http()
    {
        $headers = ['Accept' => 'application/json'];

        if ($key = (env('WAHA_KEY') ?: env('WAHA_API_KEY'))) {
            $headers['X-Api-Key']     = $key;
            $headers['Authorization'] = 'Bearer '.$key;
        }
        if ($ua = env('WAHA_UA')) {
            $headers['User-Agent'] = $ua;
        }
        $verify = ! filter_var(env('WAHA_INSECURE', false), FILTER_VALIDATE_BOOLEAN);

        return Http::timeout(25)->withHeaders($headers)->withOptions(['verify' => $verify]);
    }

    protected function sessionName(WahaSender $sender): string
    {
        return $sender->session
            ?? $sender->session_name
            ?? $sender->name
            ?? $sender->number
            ?? 'default';
    }

    /* ===================== SEND TEXT ====================== */

    public function sendMessage(WahaSender $sender, string $recipient, string $text): array
    {
        $to = $this->sanitize($recipient);
        if (!$to) {
            return ['success'=>false,'error'=>'INVALID_RECIPIENT','message'=>'Nomor tujuan tidak valid'];
        }

        $api     = $this->apiBase($sender);
        $session = $this->sessionName($sender);

        if (!$api) {
            $id = 'SIM-'.Str::uuid();
            Log::info('[WAHA][SIMULATED] sendMessage', ['sender_id'=>$sender->id,'session'=>$session,'to'=>$to,'text'=>Str::limit($text,160)]);
            return ['success'=>true,'message_id'=>$id,'status'=>'SIMULATED','path'=>null,'raw'=>['simulated'=>true]];
        }

        $attempts = [
            [ 'url'=>$api.'/sendText', 'payload'=>['session'=>$session,'to'=>$to,'text'=>$text] ],
            [ 'url'=>$api."/sessions/{$session}/messages/send/text", 'payload'=>['to'=>$to,'text'=>$text] ],
            [ 'url'=>$api."/sessions/{$session}/messages", 'payload'=>['to'=>$to,'type'=>'text','text'=>$text] ],
        ];

        $lastJson = null;
        foreach ($attempts as $i => $opt) {
            try {
                $resp = $this->http()->post($opt['url'], $opt['payload']);
                $json = $resp->json() ?? [];
                $msgId = $this->extractMessageId($json);

                Log::info('[WAHA] sendMessage attempt#'.($i+1), [
                    'sender_id'=>$sender->id,'status'=>$resp->status(),'msg_id'=>$msgId,'url'=>$opt['url']
                ]);

                if ($resp->successful()) {
                    return [
                        'success'=>true,
                        'message_id'=>$msgId,
                        'status'=>$json['status'] ?? 'OK',
                        'path'=>$opt['url'],
                        'raw'=>$json,
                    ];
                }
                $lastJson = $json;
            } catch (\Throwable $e) {
                Log::warning('[WAHA] sendMessage attempt#'.($i+1).' error: '.$e->getMessage());
                $lastJson = ['error'=>$e->getMessage()];
            }
        }

        return ['success'=>false,'status'=>'ERROR','path'=>$attempts[0]['url'],'raw'=>$lastJson];
    }

    /* ===================== SESSION STATUS ====================== */

    public function sessionStatus(WahaSender $sender): array
    {
        $api     = $this->apiBase($sender);
        $session = $this->sessionName($sender);
        if (!$api) return ['success'=>false,'error'=>'NO_BASE_URL','message'=>'WAHA_URL/WAHA_BASE_URL belum dikonfigurasi.'];

        $urls = [
            $api."/sessions/{$session}",
            $api."/sessions/{$session}/state",
            $api."/sessions/{$session}/status",
        ];

        foreach ($urls as $url) {
            try {
                $resp = $this->http()->get($url);
                $json = $resp->json();

                // Jika bukan JSON atau kosong, coba endpoint berikutnya
                if (!is_array($json)) continue;

                // Deteksi "not found" meski HTTP 200
                $msg   = strtolower((string)($json['message'] ?? data_get($json,'error.message') ?? ''));
                $ok    = (bool)($json['success'] ?? true);
                if (!$ok || str_contains($msg, 'not found')) {
                    return [
                        'success'   => true,
                        'session'   => $session,
                        'connected' => false,
                        'state'     => 'NOT_CREATED',
                        'qr'        => null,
                        'raw'       => $json,
                    ];
                }

                $state = data_get($json,'state') ?? data_get($json,'result.state') ?? data_get($json,'data.state');
                $connected = data_get($json,'connected') ?? data_get($json,'result.connected');

                if ($connected === null && $state) {
                    $connected = in_array(strtoupper($state), [
                        'OPEN','READY','RUNNING','CONNECTED','ONLINE','AUTHENTICATED','WORKING','LOGGED_IN'
                    ], true);
                }

                $qr = data_get($json,'qr') ?? data_get($json,'result.qr') ?? data_get($json,'data.qr');

                return [
                    'success'   => true,
                    'session'   => $session,
                    'connected' => (bool)$connected,
                    'state'     => $state ? strtoupper($state) : 'UNKNOWN',
                    'qr'        => $qr,
                    'raw'       => $json,
                ];
            } catch (\Throwable $e) {
                Log::debug('[WAHA] sessionStatus try failed: '.$e->getMessage(), ['url'=>$url,'session'=>$session]);
            }
        }

        return ['success'=>false,'error'=>'UNPARSABLE_RESPONSE','message'=>'Tidak bisa membaca status sesi dari WAHA.'];
    }

    /* ===================== SESSION START/LOGOUT ====================== */

    public function sessionStart(WahaSender $sender): array
    {
        $api     = $this->apiBase($sender);
        $session = $this->sessionName($sender);
        if (!$api) return ['success'=>false,'error'=>'NO_BASE_URL','message'=>'WAHA_URL/WAHA_BASE_URL belum dikonfigurasi.'];

        $startUrl  = $api."/sessions/{$session}/start";
        $createUrl = $api."/sessions";

        try {
            // Start langsung
            $resp = $this->http()->post($startUrl);

            // Jika butuh create terlebih dahulu
            if (in_array($resp->status(), [404,409], true)) {
                $this->http()->post($createUrl, ['session'=>$session, 'type'=>'md']);
                $resp = $this->http()->post($startUrl);
            }

            $json  = $resp->json() ?? [];
            $state = data_get($json,'state') ?? data_get($json,'result.state') ?? 'UNKNOWN';
            $qr    = data_get($json,'qr')    ?? data_get($json,'result.qr');

            return [
                'success' => $resp->successful(),
                'session' => $session,
                'state'   => strtoupper($state),
                'qr'      => $qr,
                'path'    => $startUrl,
                'raw'     => $json,
            ];
        } catch (\Throwable $e) {
            Log::error('[WAHA] sessionStart error: '.$e->getMessage(), ['session'=>$session]);
            return ['success'=>false,'error'=>'EXCEPTION','message'=>$e->getMessage()];
        }
    }

    public function qrStart(WahaSender $sender): array
    {
        return $this->sessionStart($sender);
    }

    public function sessionLogout(WahaSender $sender): array
    {
        $api     = $this->apiBase($sender);
        $session = $this->sessionName($sender);
        if (!$api) return ['success'=>false,'error'=>'NO_BASE_URL','message'=>'WAHA_URL/WAHA_BASE_URL belum dikonfigurasi.'];

        $url = $api."/sessions/{$session}/logout";
        try {
            $resp = $this->http()->post($url);
            return ['success'=>$resp->successful(),'path'=>$url,'raw'=>$resp->json()];
        } catch (\Throwable $e) {
            Log::error('[WAHA] sessionLogout error: '.$e->getMessage(), ['session'=>$session]);
            return ['success'=>false,'error'=>'EXCEPTION','message'=>$e->getMessage()];
        }
    }

    /* ===================== SESSION ME & QR IMAGE ====================== */

    public function sessionMe(WahaSender $sender): array
    {
        $api     = $this->apiBase($sender);
        $session = $this->sessionName($sender);
        if (!$api) return ['success'=>false,'error'=>'NO_BASE_URL','message'=>'WAHA_URL/WAHA_BASE_URL belum dikonfigurasi.'];

        $url = $api."/sessions/{$session}/me";
        try {
            $resp = $this->http()->get($url);
            $json = $resp->json() ?? [];

            $id   = data_get($json,'id') ?? data_get($json,'me.id') ?? data_get($json,'result.id');
            $name = data_get($json,'name') ?? data_get($json,'pushName') ?? data_get($json,'me.name') ?? data_get($json,'result.name');

            return [
                'success'=>$resp->successful(),
                'number'=>$this->sanitize($id),
                'display_name'=>$name,
                'raw'=>$json,
            ];
        } catch (\Throwable $e) {
            Log::error('[WAHA] sessionMe error: '.$e->getMessage(), ['session'=>$session]);
            return ['success'=>false,'error'=>'EXCEPTION','message'=>$e->getMessage()];
        }
    }

    public function qrImageBinary(WahaSender $sender): array
    {
        $api     = $this->apiBase($sender);
        $session = $this->sessionName($sender);
        if (!$api) return ['success'=>false,'error'=>'NO_BASE_URL','message'=>'WAHA_URL/WAHA_BASE_URL belum dikonfigurasi.'];

        $urls = [
            $api."/sessions/{$session}/qr.svg",
            $api."/sessions/{$session}/qr.png",
            $api."/sessions/{$session}/qr",
        ];

        foreach ($urls as $url) {
            try {
                $resp = $this->http()->get($url);
                if ($resp->successful()) {
                    $body = $resp->body();
                    $ctype = $resp->header('Content-Type') ?: (Str::endsWith($url,'.svg') ? 'image/svg+xml' : 'image/png');
                    if ($body !== '') {
                        return ['success'=>true,'body'=>$body,'ctype'=>$ctype,'path'=>$url];
                    }
                }
            } catch (\Throwable $e) {
                Log::debug('[WAHA] qrImageBinary try failed: '.$e->getMessage(), ['url'=>$url]);
            }
        }
        return ['success'=>false,'message'=>'QR not available yet'];
    }

    /* ===================== PAIRING CODE ====================== */

    public function requestAuthCode(WahaSender $sender): array
    {
        $api     = $this->apiBase($sender);
        $session = $this->sessionName($sender);
        if (!$api) return ['success'=>false,'error'=>'NO_BASE_URL','message'=>'WAHA_URL/WAHA_BASE_URL belum dikonfigurasi.'];

        $attempts = [
            [ 'url'=>$api."/sessions/{$session}/pairing/start", 'payload'=>[] ],
            [ 'url'=>$api."/sessions/{$session}/start", 'payload'=>['mode'=>'pairing'] ],
            [ 'url'=>$api."/sessions/{$session}/start", 'payload'=>['pairing'=>true] ],
            [ 'url'=>$api."/sessions/{$session}/pairing/code", 'payload'=>[], 'method'=>'get' ],
        ];

        foreach ($attempts as $i => $opt) {
            try {
                $method = strtolower($opt['method'] ?? 'post');
                $resp = $method === 'get'
                    ? $this->http()->get($opt['url'])
                    : $this->http()->post($opt['url'], $opt['payload']);

                $json = $resp->json() ?? [];
                $code = data_get($json,'code') ?? data_get($json,'result.code') ?? data_get($json,'data.code');

                if ($resp->successful() && !empty($code)) {
                    return ['success'=>true,'code'=>$code,'path'=>$opt['url'],'raw'=>$json];
                }
            } catch (\Throwable $e) {
                Log::debug('[WAHA] requestAuthCode attempt#'.($i+1).' failed: '.$e->getMessage());
            }
        }

        return ['success'=>false,'error'=>'CODE_NOT_AVAILABLE','message'=>'Kode pairing tidak tersedia dari server WAHA.'];
    }

    /* ===================== UTILITIES ====================== */

    protected function sanitize(?string $p): ?string
    {
        if (!$p) return null;
        $n = preg_replace('/\D+/', '', $p);
        if (Str::startsWith($n, '0') && strlen($n) >= 9) $n = '62'.substr($n,1);
        return $n ?: null;
    }

    protected function extractMessageId(array $json): ?string
    {
        return $json['message_id']
            ?? data_get($json,'data.message_id')
            ?? data_get($json,'result.key.id')
            ?? data_get($json,'key.id')
            ?? data_get($json,'id')
            ?? null;
    }
}
