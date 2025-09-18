<?php

namespace App\Http\Controllers;

use App\Models\WahaSender;
use App\Services\WahaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WahaController extends Controller
{
    protected WahaService $wahaService;

    public function __construct(WahaService $wahaService)
    {
        $this->wahaService = $wahaService;
    }

    /* ========= Messages ========= */
    public function sendMessage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sender_id' => ['required','exists:waha_senders,id'],
            'recipient' => ['required','string'],
            'message'   => ['required','string'],
        ]);

        $sender    = WahaSender::findOrFail($data['sender_id']);
        $recipient = preg_replace('/\D+/', '', $data['recipient']);

        $resp = $this->wahaService->sendMessage($sender, $recipient, $data['message']);

        if ($resp === null) {
            return response()->json(['success'=>false,'message'=>'Gagal mengirim pesan ke WAHA.'], 502);
        }
        return response()->json(['success'=>true,'data'=>$resp]);
    }

    public function sendBulkMessages(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sender_id'          => ['required','exists:waha_senders,id'],
            'recipients'         => ['required','array','min:1'],
            'recipients.*.name'  => ['nullable','string'],
            'recipients.*.phone' => ['required','string'],
            'message'            => ['required','string'],
        ]);

        $sender = WahaSender::findOrFail($data['sender_id']);

        $results = [];
        $success = 0;

        foreach ($data['recipients'] as $r) {
            $name  = (string) ($r['name'] ?? '');
            $phone = preg_replace('/\D+/', '', (string) $r['phone']);

            $msg = str_replace(
                ['{{name}}','{{ nama }}','{{nama}}','{{nama_pelanggan}}','{{ nama_pelanggan }}'],
                $name,
                $data['message']
            );

            $res = $this->wahaService->sendMessage($sender, $phone, $msg);
            $ok  = $res !== null;
            if ($ok) $success++;

            $results[] = [
                'recipient' => ['name'=>$name,'phone'=>$phone],
                'success'   => $ok,
                'response'  => $res,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => "Terkirim: {$success}/".count($data['recipients']),
            'data'    => $results,
        ]);
    }

    /* ========= Sessions ========= */
    public function status(WahaSender $wahaSender): JsonResponse
    {
        $resp = $this->wahaService->getSessionStatus($wahaSender);
        return response()->json(['success' => $resp !== null, 'data' => $resp]);
    }

    public function start(WahaSender $wahaSender): JsonResponse
    {
        $resp = $this->wahaService->startSession($wahaSender);
        return response()->json(['success' => $resp !== null, 'data' => $resp]);
    }

    public function logout(WahaSender $wahaSender): JsonResponse
    {
        $resp = $this->wahaService->logoutSession($wahaSender);
        return response()->json(['success' => $resp !== null, 'data' => $resp]);
    }

    public function qr(WahaSender $wahaSender): JsonResponse
    {
        $resp = $this->wahaService->getQrCode($wahaSender);
        return response()->json(['success' => $resp !== null, 'data' => $resp]);
    }

    /** Batch status: ?ids=1,2,3 (dipakai untuk refresh dropdown) */
    public function statusBatch(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        if (is_string($ids)) $ids = array_filter(array_map('intval', explode(',', $ids)));
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['success'=>false,'message'=>'Param ids kosong.'], 422);
        }

        $senders = WahaSender::whereIn('id', $ids)->get();
        $out = [];
        foreach ($senders as $s) {
            $st = $this->wahaService->getSessionStatus($s);
            $out[] = [
                'id'     => $s->id,
                'status' => $st['status'] ?? ($st['state'] ?? null),
                'raw'    => $st,
            ];
        }
        return response()->json(['success'=>true, 'data'=>$out]);
    }

    /* ========= Utils ========= */
    public function checkNumber(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sender_id' => ['required','exists:waha_senders,id'],
            'phone'     => ['required','string'],
        ]);
        $sender = WahaSender::findOrFail($data['sender_id']);
        $resp   = $this->wahaService->checkNumber($sender, $data['phone']);
        return response()->json(['success'=>$resp !== null, 'data'=>$resp]);
    }
}
