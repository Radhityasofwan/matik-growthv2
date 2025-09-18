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

    /**
     * Kirim 1 pesan WhatsApp teks.
     * Request:
     *  - sender_id: int (exists:waha_senders,id)
     *  - recipient: string (nomor tujuan)
     *  - message  : string (isi pesan)
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sender_id' => ['required', 'exists:waha_senders,id'],
            'recipient' => ['required', 'string'],
            'message'   => ['required', 'string'],
        ]);

        $sender = WahaSender::findOrFail($data['sender_id']);

        // Normalisasi nomor (hanya digit)
        $recipient = preg_replace('/\D+/', '', $data['recipient']);

        try {
            $resp = $this->wahaService->sendMessage($sender, $recipient, $data['message']);

            if ($resp === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengirim pesan ke layanan WAHA.',
                ], 502);
            }

            return response()->json([
                'success' => true,
                'data'    => $resp,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal saat mengirim pesan.',
            ], 500);
        }
    }

    /**
     * Kirim pesan WhatsApp massal (teks).
     * Request:
     *  - sender_id: int (exists:waha_senders,id)
     *  - recipients: array<{ name?:string, phone:string }>
     *  - message  : string (template/raw). Placeholder yang didukung: {{name}}, {{nama}}, {{nama_pelanggan}}
     */
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
        $successCount = 0;

        foreach ($data['recipients'] as $r) {
            $name = (string) ($r['name'] ?? '');
            $phone = preg_replace('/\D+/', '', (string) $r['phone']);

            // Aman: dukung beberapa placeholder umum
            $msg = str_replace(
                ['{{name}}', '{{ nama }}', '{{nama}}', '{{nama_pelanggan}}', '{{ nama_pelanggan }}'],
                $name,
                $data['message']
            );

            try {
                $res = $this->wahaService->sendMessage($sender, $phone, $msg);
            } catch (\Throwable $e) {
                report($e);
                $res = null;
            }

            $ok = $res !== null;
            if ($ok) $successCount++;

            $results[] = [
                'recipient' => ['name' => $name, 'phone' => $phone],
                'success'   => $ok,
                'response'  => $res,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => "Terkirim: {$successCount}/" . count($data['recipients']),
            'data'    => $results,
        ]);
    }
}
