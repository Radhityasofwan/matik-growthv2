<?php

namespace App\Http\Controllers;

use App\Models\WahaSender;
use App\Services\WahaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WahaController extends Controller
{
    protected WahaService $waha;

    public function __construct(WahaService $waha)
    {
        $this->waha = $waha;
    }

    /** kirim 1 pesan teks */
    public function sendMessage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sender_id' => ['required', 'exists:waha_senders,id'],
            'recipient' => ['required', 'string'],
            'message'   => ['required', 'string'],
        ]);

        $sender = WahaSender::findOrFail($data['sender_id']);
        $recipient = preg_replace('/\D+/', '', $data['recipient']);

        $resp = $this->waha->sendMessage($sender, $recipient, $data['message']);

        return response()->json([
            'success' => $this->waha->isSuccessful($resp),
            'data'    => $resp,
        ], $resp ? 200 : 502);
    }

    /** kirim massal teks */
    public function sendBulkMessages(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sender_id'             => ['required', 'exists:waha_senders,id'],
            'recipients'            => ['required', 'array', 'min:1'],
            'recipients.*.name'     => ['nullable', 'string'],
            'recipients.*.phone'    => ['required', 'string'],
            'message'               => ['required', 'string'],
        ]);

        $sender = WahaSender::findOrFail($data['sender_id']);

        $results = [];
        $ok = 0;

        foreach ($data['recipients'] as $r) {
            $name = (string) ($r['name'] ?? '');
            $phone = preg_replace('/\D+/', '', (string) $r['phone']);
            $msg = str_replace(['{{name}}','{{ nama }}','{{nama}}','{{nama_pelanggan}}','{{ nama_pelanggan }}'], $name, $data['message']);

            $res = $this->waha->sendMessage($sender, $phone, $msg);
            $ok += $this->waha->isSuccessful($res) ? 1 : 0;

            $results[] = ['recipient'=>['name'=>$name,'phone'=>$phone], 'success'=>$this->waha->isSuccessful($res), 'response'=>$res];
        }

        return response()->json([
            'success' => true,
            'message' => "Terkirim: {$ok}/".count($data['recipients']),
            'data'    => $results,
        ]);
    }

    /* ========== Session endpoints ========== */

    public function status(WahaSender $wahaSender): JsonResponse
    {
        $res = $this->waha->getSessionStatus($wahaSender);

        // Beri info tambahan saat gagal agar mudah debug
        if (!$res) {
            $health = $this->waha->health();
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat mengambil status dari WAHA. Cek WAHA_URL/WAHA_KEY, konektivitas, atau sesi.',
                'health'  => $health,
                'data'    => null,
            ], 502);
        }

        return response()->json(['success'=>true, 'data'=>$res]);
    }

    public function start(WahaSender $wahaSender): JsonResponse
    {
        $res = $this->waha->startSession($wahaSender);
        return response()->json(['success' => (bool) $res, 'data' => $res], $res ? 200 : 502);
    }

    public function logout(WahaSender $wahaSender): JsonResponse
    {
        $res = $this->waha->logoutSession($wahaSender);
        return response()->json(['success' => (bool) $res, 'data' => $res], $res ? 200 : 502);
    }

    public function qr(WahaSender $wahaSender): JsonResponse
    {
        $res = $this->waha->getQrCode($wahaSender);
        return response()->json(['success' => (bool) $res, 'data' => $res], $res ? 200 : 502);
    }

    /** ?ids=1,2,3 */
    public function statusBatch(Request $request): JsonResponse
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->filter()->map(fn($x) => (int) $x)->values();

        $senders = WahaSender::whereIn('id', $ids)->get();
        $out = [];

        foreach ($senders as $s) {
            $r = $this->waha->getSessionStatus($s);
            $state = $r['status'] ?? $r['state'] ?? (($r['connected'] ?? false) ? 'CONNECTED' : null);
            $out[] = ['id' => $s->id, 'status' => $state, 'raw' => $r];
        }

        return response()->json(['success'=>true, 'data'=>$out]);
    }

    public function checkNumber(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sender_id' => ['required','exists:waha_senders,id'],
            'phone'     => ['required','string'],
        ]);
        $sender = WahaSender::findOrFail($data['sender_id']);
        $res = $this->waha->checkNumber($sender, $data['phone']);
        return response()->json(['success' => (bool) $res, 'data'=>$res], $res ? 200 : 502);
    }

/** Quick scan endpoint+auth untuk WAHA. GET /waha/diagnose?session=xxx */
    public function diagnose(Request $request): JsonResponse
    {
        $base = rtrim((string) config('services.waha.url'), '/'); // contoh: https://waha.matik.id
        $key  = (string) config('services.waha.key');
        $insecure = (bool) env('WAHA_INSECURE', false);

        // Tentukan sesi: dari query ?session=, atau ambil 1 yang aktif dari DB
        $session = trim((string) ($request->query('session')
                    ?: WahaSender::where('is_active', true)->value('session')
                    ?: 'test'));
        $s      = $session;
        $sEnc   = rawurlencode($session);

        $mk = fn(string $p) => (str_starts_with($p, '/') ? '' : '/') . $p; // pastikan leading slash

        // Kandidat HEALTH (tanpa & dengan /api)
        $healthPaths = [
            '/health','/status','/api/health','/api/status','/v1/health','/api/v1/health','/wa/health','/client/health',
        ];

        // Kandidat STATUS (tanpa & dengan /api), pakai nama sesi raw & encoded
        $statusPaths = [];
        foreach ([$s, $sEnc] as $sx) {
            $statusPaths = array_merge($statusPaths, [
                "/{$sx}/status", "/{$sx}/state",
                "/sessions/{$sx}", "/sessions/{$sx}/status",
                "/session/{$sx}/status",
                "/api/{$sx}/status", "/api/{$sx}/state",
                "/api/sessions/{$sx}", "/api/sessions/{$sx}/status",
                "/api/session/{$sx}/status",
                "/client/{$sx}/status", "/instance/{$sx}/status",
            ]);
        }

        // 4 skema auth umum dipakai WAHA
        $authers = [
            ['name' => 'X-Api-Key', 'apply' => fn($req) => $key ? $req->withHeaders(['X-Api-Key' => $key]) : $req],
            ['name' => 'X-API-KEY', 'apply' => fn($req) => $key ? $req->withHeaders(['X-API-KEY' => $key]) : $req],
            ['name' => 'Bearer',    'apply' => fn($req) => $key ? $req->withToken($key) : $req],
            ['name' => 'query',     'apply' => fn($req, $url) => $key ? [$req, $url.(str_contains($url,'?')?'&':'?').'apikey='.urlencode($key)] : [$req, $url]],
        ];

        $httpBase = Http::acceptJson()->timeout(12);
        if ($insecure) $httpBase = $httpBase->withoutVerifying();

        $probe = function(array $paths) use ($base, $authers, $httpBase, $mk) {
            $out = [];
            foreach ($paths as $p) {
                $url = $base . $mk($p);
                foreach ($authers as $a) {
                    $name = $a['name'];
                    $client = $httpBase;
                    $target = $url;

                    if ($name === 'query') {
                        [$client, $target] = $a['apply']($client, $url);
                    } else {
                        $client = $a['apply']($client);
                    }

                    try {
                        $res = $client->get($target);
                        $status = $res->status();
                        $body   = substr((string) $res->body(), 0, 140);
                    } catch (\Throwable $e) {
                        $status = 0;
                        $body   = 'EXC: '.$e->getMessage();
                    }

                    $out[] = ['url' => $target, 'auth' => $name, 'status' => $status, 'body' => $body];
                }
            }
            return $out;
        };

        $healthRes = $probe($healthPaths);
        $statusRes = $probe($statusPaths);

        // Susun hasil: yang status 200/201 di atas
        $sort = function(array $rows) {
            usort($rows, function($a,$b){
                $pa = in_array($a['status'], [200,201]) ? 0 : ($a['status'] ? 1 : 2);
                $pb = in_array($b['status'], [200,201]) ? 0 : ($b['status'] ? 1 : 2);
                return $pa <=> $pb;
            });
            return $rows;
        };

        return response()->json([
            'base'        => $base,
            'session'     => $session,
            'health'      => $sort($healthRes),
            'statusPaths' => $sort($statusRes),
            'hint'        => 'Cari baris dengan status 200/201. URL+auth itulah kombinasi yang harus dipakai WahaService.',
        ]);
    }
}
