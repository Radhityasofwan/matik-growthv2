<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\WahaSender;
use App\Services\WahaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Notifications\GenericDbNotification;

class WahaController extends Controller
{
    /** Webhook publik dari WAHA */
    public function webhook(Request $request)
    {
        $data = $request->json()->all() ?: [];
        Log::info('WAHA Webhook', ['payload' => $data]);

        $event = $data['event'] ?? $data['type'] ?? null;
        $msg   = $data['payload'] ?? $data['message'] ?? $data['data'] ?? $data;

        if (!in_array($event, ['message','MESSAGE','messages.upsert','chat','send'], true)
            && !isset($msg['body']) && !isset($msg['text'])) {
            return response('IGNORED', 200);
        }

        $fromMe = (bool)($msg['fromMe'] ?? $msg['from_me'] ?? $msg['isMe'] ?? false);
        $mid    = (string)($msg['id'] ?? $msg['messageId'] ?? $msg['key']['id'] ?? '');
        $text   = (string)($msg['body'] ?? $msg['text'] ?? $msg['message'] ?? '');
        $chatId = (string)($msg['chatId'] ?? $msg['chat_id'] ?? $msg['chat'] ?? $msg['remoteJid'] ?? $msg['from'] ?? $msg['to'] ?? '');
        $author = (string)($msg['author'] ?? $msg['from'] ?? '');
        $to     = (string)($msg['to'] ?? $msg['recipient'] ?? '');

        $jid    = $fromMe ? ($to ?: $chatId) : ($author ?: $chatId);
        $digits = $this->digitsFromJid($jid);
        if ($digits === '') return response('NO_NUMBER', 200);

        $lead = Lead::where('phone', $digits)->first();
        if (!$lead) $lead = Lead::where('phone', 'like', "%{$digits}")->first();
        if (!$lead) return response('NO_LEAD', 200);

        // dedup by message id
        if ($mid !== '' && $lead->activities()
                ->whereIn('log_name', ['wa_chat','lead_reply'])
                ->where('properties->message_id', $mid)->exists()) {
            return response('DUPLICATE', 200);
        }

        if ($fromMe) {
            activity('wa_chat')->performedOn($lead)
                ->withProperties(['source'=>'webhook','message_id'=>$mid,'text'=>$text,'chat_id'=>$chatId])
                ->log('Outbound message');
        } else {
            activity('lead_reply')->performedOn($lead)
                ->withProperties(['source'=>'webhook','message_id'=>$mid,'text'=>$text,'chat_id'=>$chatId])
                ->log('Inbound reply');

            // 📣 notif ke owner saat ada balasan
            if ($lead->owner) {
                $snippet = Str::limit(trim($text), 120);
                $lead->owner->notify(new GenericDbNotification(
                    'Balasan WhatsApp',
                    "{$lead->name} membalas: {$snippet}",
                    route('leads.show', $lead)
                ));
            }
        }

        return response('OK', 200);
    }

    private function digitsFromJid(string $jid): string
    {
        $d = preg_replace('/\D+/', '', $jid);
        if (Str::startsWith($d, '0') && strlen($d) >= 9) $d = '62'.substr($d, 1);
        return $d;
    }

    /** Kirim 1 pesan WA ke 1 nomor. Body: { sender_id, recipient, message } */
    public function sendMessage(Request $request, WahaService $svc)
    {
        $data = $request->validate([
            'sender_id' => ['required','integer','exists:waha_senders,id'],
            'recipient' => ['required','string'],
            'message'   => ['required','string'],
        ]);

        $actor  = $request->user();
        $sender = WahaSender::findOrFail($data['sender_id']);
        $phone  = preg_replace('/\D+/', '', $data['recipient']);

        $resp = $svc->sendMessage($sender, $phone, $data['message']);

        if ($phone && ($lead = Lead::where('phone', $phone)->first())) {
            activity('wa_chat')->performedOn($lead)->causedBy($actor)
                ->withProperties([
                    'source'     => 'manual_send',
                    'message_id' => $resp['message_id'] ?? null,
                    'text'       => $data['message'],
                    'path'       => $resp['path'] ?? null,
                    'raw'        => $resp['raw'] ?? null,
                ])->log('Outbound message');
        }

        // 📣 notif ringkas untuk pengirim
        $ok = (bool)($resp['success'] ?? false);
        $actor?->notify(new GenericDbNotification(
            $ok ? 'WA Terkirim' : 'WA Gagal',
            $ok ? 'Pesan berhasil dikirim.' : 'Gagal mengirim pesan.',
            $phone && isset($lead) ? route('leads.show', $lead) : route('dashboard')
        ));

        return response()->json($resp ?: ['success'=>false], $ok ? 200 : 502);
    }

    /** Kirim pesan massal */
    public function sendBulkMessages(Request $request, WahaService $svc)
    {
        $data = $request->validate([
            'sender_id'  => ['required','integer','exists:waha_senders,id'],
            'recipients' => ['required','array','min:1'],
            'recipients.*.name'  => ['nullable','string','max:255'],
            'recipients.*.phone' => ['required','string','max:30'],
            'message'    => ['required','string'],
        ]);

        $actor  = $request->user();
        $sender = WahaSender::findOrFail($data['sender_id']);

        $ok=0; $fail=0; $results=[];
        foreach ($data['recipients'] as $rcp) {
            $phone = preg_replace('/\D+/', '', $rcp['phone']);
            if ($phone === '') { $fail++; continue; }

            $msg = str_replace(['{{name}}','{{nama}}','{{nama_pelanggan}}'], $rcp['name'] ?? '', $data['message']);

            try {
                $resp = $svc->sendMessage($sender, $phone, $msg);
                $ok += !empty($resp['success']) ? 1 : 0;
                $fail += empty($resp['success']) ? 1 : 0;

                if ($lead = Lead::where('phone', $phone)->first()) {
                    activity('wa_chat')->performedOn($lead)->causedBy($actor)
                        ->withProperties([
                            'source'     => 'bulk_send',
                            'message_id' => $resp['message_id'] ?? null,
                            'text'       => $msg,
                            'path'       => $resp['path'] ?? null,
                            'raw'        => $resp['raw'] ?? null,
                        ])->log('Outbound message (bulk)');
                }
                $results[] = ['phone'=>$phone,'success'=>(bool)($resp['success'] ?? false)];
            } catch (\Throwable $e) {
                $fail++; $results[] = ['phone'=>$phone,'success'=>false,'error'=>$e->getMessage()];
            }
        }

        // 📣 notif ringkasan
        $actor?->notify(new GenericDbNotification(
            'Bulk WA Selesai',
            "Terkirim: {$ok}, Gagal: {$fail}.",
            route('dashboard')
        ));

        return response()->json([
            'success' => $fail === 0,
            'ok'      => $ok,
            'fail'    => $fail,
            'results' => $results,
        ], $fail === 0 ? 200 : 207);
    }
}
