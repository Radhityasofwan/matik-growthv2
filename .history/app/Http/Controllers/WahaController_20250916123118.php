<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\WahaSender;
use App\Services\WahaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WahaController extends Controller
{
    /**
     * Webhook publik dari WAHA.
     * Set di WAHA settings → webhook URL → POST ke route('waha.webhook').
     * Jangan dibungkus auth middleware.
     */
    public function webhook(Request $request)
    {
        $data = $request->json()->all() ?: [];
        Log::info('WAHA Webhook', ['payload' => $data]);

        // Struktur toleran
        $event = $data['event'] ?? $data['type'] ?? null;
        $msg   = $data['payload'] ?? $data['message'] ?? $data['data'] ?? $data;

        // Hanya proses yang berhubungan dengan pesan
        if (!in_array($event, ['message','MESSAGE','messages.upsert', 'chat', 'send'], true) && !isset($msg['body']) && !isset($msg['text'])) {
            return response('IGNORED', 200);
        }

        // Ekstraksi umum
        $fromMe  = (bool)($msg['fromMe'] ?? $msg['from_me'] ?? $msg['isMe'] ?? false);
        $mid     = (string)($msg['id'] ?? $msg['messageId'] ?? $msg['key']['id'] ?? '');
        $text    = (string)($msg['body'] ?? $msg['text'] ?? $msg['message'] ?? '');
        $chatId  = (string)($msg['chatId'] ?? $msg['chat_id'] ?? $msg['chat'] ?? $msg['remoteJid'] ?? $msg['from'] ?? $msg['to'] ?? '');
        $author  = (string)($msg['author'] ?? $msg['from'] ?? '');
        $to      = (string)($msg['to'] ?? $msg['recipient'] ?? '');

        // Nomor lead:
        // jika inbound (fromMe=false) → ambil dari 'from'/author/chatId
        // jika outbound (fromMe=true)  → ambil dari 'to' atau chatId
        $jid = $fromMe ? ($to ?: $chatId) : ($author ?: $chatId);
        $digits = $this->digitsFromJid($jid);

        if ($digits === '') return response('NO_NUMBER', 200);

        $lead = Lead::where('phone', $digits)->first();
        if (!$lead) {
            // fallback: cocokkan suffix 10-12 digit (untuk kasus format berbeda)
            $lead = Lead::where('phone', 'like', "%{$digits}")->first();
        }
        if (!$lead) return response('NO_LEAD', 200);

        // dedup berdasar message id
        if ($mid !== '' && $lead->activities()
                ->whereIn('log_name', ['wa_chat','lead_reply'])
                ->where('properties->message_id', $mid)->exists()) {
            return response('DUPLICATE', 200);
        }

        // log aktivitas
        if ($fromMe) {
            activity('wa_chat')->performedOn($lead)
                ->withProperties([
                    'source'     => 'webhook',
                    'message_id' => $mid,
                    'text'       => $text,
                    'chat_id'    => $chatId,
                ])->log('Outbound message');
        } else {
            activity('lead_reply')->performedOn($lead)
                ->withProperties([
                    'source'     => 'webhook',
                    'message_id' => $mid,
                    'text'       => $text,
                    'chat_id'    => $chatId,
                ])->log('Inbound reply');
        }

        return response('OK', 200);
    }

    private function digitsFromJid(string $jid): string
    {
        // contoh jid: 62812xxxx@c.us → ambil angka
        $d = preg_replace('/\D+/', '', $jid);
        // normalisasi simple: jika 0xxxx → konversi ke 62xxxx (optional)
        if (Str::startsWith($d, '0') && strlen($d) >= 9) {
            $d = '62' . substr($d, 1);
        }
        return $d;
    }

    /**
     * Kirim 1 pesan WA ke 1 nomor.
     * Body: { sender_id, recipient, message }
     */
    public function sendMessage(Request $request, WahaService $svc)
    {
        $data = $request->validate([
            'sender_id' => ['required','integer','exists:waha_senders,id'],
            'recipient' => ['required','string'],
            'message'   => ['required','string'],
        ]);

        $sender = WahaSender::findOrFail($data['sender_id']);
        $phone  = preg_replace('/\D+/', '', $data['recipient']);

        $resp = $svc->sendMessage($sender, $phone, $data['message']);

        // log aktivitas “wa_chat”
        if ($phone) {
            if ($lead = Lead::where('phone', $phone)->first()) {
                activity('wa_chat')->performedOn($lead)->causedBy($request->user())
                    ->withProperties([
                        'source'     => 'manual_send',
                        'message_id' => $resp['message_id'] ?? null,
                        'text'       => $data['message'],
                        'path'       => $resp['path'] ?? null,
                        'raw'        => $resp['raw'] ?? null,
                    ])->log('Outbound message');
            }
        }

        return response()->json($resp ?: ['success'=>false], ($resp['success'] ?? false) ? 200 : 502);
    }

    /**
     * Kirim pesan massal.
     * Body: { sender_id, recipients: [{name, phone}], message }
     * Catatan: message masih template mentah — placeholder diganti di FE (sudah kamu buat).
     */
    public function sendBulkMessages(Request $request, WahaService $svc)
    {
        $data = $request->validate([
            'sender_id'  => ['required','integer','exists:waha_senders,id'],
            'recipients' => ['required','array','min:1'],
            'recipients.*.name'  => ['nullable','string','max:255'],
            'recipients.*.phone' => ['required','string','max:30'],
            'message'    => ['required','string'],
        ]);

        $sender = WahaSender::findOrFail($data['sender_id']);
        $ok = 0; $fail = 0;
        $results = [];

        foreach ($data['recipients'] as $rcp) {
            $phone = preg_replace('/\D+/', '', $rcp['phone']);
            if ($phone === '') { $fail++; continue; }

            $msg = $data['message'];
            $msg = str_replace(['{{name}}','{{nama}}','{{nama_pelanggan}}'], $rcp['name'] ?? '', $msg);

            try {
                $resp = $svc->sendMessage($sender, $phone, $msg);
                $results[] = ['phone'=>$phone, 'success'=>$resp['success'] ?? false, 'message_id'=>$resp['message_id'] ?? null];

                if (!empty($resp['success'])) $ok++; else $fail++;

                if ($lead = Lead::where('phone', $phone)->first()) {
                    activity('wa_chat')->performedOn($lead)->causedBy($request->user())
                        ->withProperties([
                            'source'     => 'bulk_send',
                            'message_id' => $resp['message_id'] ?? null,
                            'text'       => $msg,
                            'path'       => $resp['path'] ?? null,
                            'raw'        => $resp['raw'] ?? null,
                        ])->log('Outbound message (bulk)');
                }
            } catch (\Throwable $e) {
                $fail++;
                Log::error('Bulk send WA error', ['phone'=>$phone, 'err'=>$e->getMessage()]);
                $results[] = ['phone'=>$phone, 'success'=>false, 'error'=>$e->getMessage()];
            }
        }

        return response()->json([
            'success' => $fail === 0,
            'ok'      => $ok,
            'fail'    => $fail,
            'results' => $results,
        ], $fail === 0 ? 200 : 207);
    }
}
