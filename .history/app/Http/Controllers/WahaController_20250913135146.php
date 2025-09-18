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
}
