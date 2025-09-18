<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Models\LeadFollowUpRule;
use App\Models\WahaSender;
use App\Http\Controllers\WahaController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendLeadFollowUpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ?int $ruleId;

    public function __construct(?int $ruleId = null)
    {
        $this->ruleId = $ruleId;
    }

    public function handle(): void
    {
        $rules = $this->ruleId
            ? LeadFollowUpRule::query()->active()->whereKey($this->ruleId)->get()
            : LeadFollowUpRule::query()->active()->get();

        if ($rules->isEmpty()) {
            Log::info('[FollowUp] Tidak ada rule aktif.');
            return;
        }

        foreach ($rules as $rule) {
            $this->processRule($rule);
        }
    }

    protected function processRule(LeadFollowUpRule $rule): void
    {
        $eligible = $this->eligibleLeadsFor($rule);

        if ($eligible->isEmpty()) {
            $rule->updateQuietly(['last_run_at' => now()]);
            Log::info("[FollowUp] Rule #{$rule->id} ({$rule->condition}) tidak ada target.");
            return;
        }

        $sender = $this->resolveSender($rule);
        if (!$sender) {
            Log::warning("[FollowUp] Rule #{$rule->id} tidak punya sender aktif. Skip.");
            return;
        }

        $templateText = $this->resolveTemplateBody($rule);
        $sent = 0; $errors = 0;

        foreach ($eligible as $lead) {
            try {
                $to = $this->waSanitize($lead->phone);
                if (!$to) {
                    activity('follow_up')->performedOn($lead)->withProperties([
                        'rule_id'   => $rule->id,
                        'sender_id' => $sender->id,
                        'status'    => 'SKIPPED_NO_PHONE',
                    ])->log('Follow-up di-skip (tanpa nomor).');
                    continue;
                }

                $messageToLead = $this->fillTemplate($templateText, $lead);
                $resultLead    = $this->sendViaWaha($sender->id, $to, $messageToLead);

                activity('follow_up')->performedOn($lead)->withProperties([
                    'rule_id'   => $rule->id,
                    'sender_id' => $sender->id,
                    'number'    => $to,
                    'http'      => data_get($resultLead, 'http'),
                    'status'    => data_get($resultLead, 'result.status') ?? data_get($resultLead, 'status'),
                    'text'      => Str::limit($messageToLead, 500),
                ])->log('WA follow-up terkirim');

                $sent++;

                // Notifikasi owner (jika ada wa_number)
                $this->notifyOwner($rule, $lead, $sender, $messageToLead, $resultLead);
            } catch (\Throwable $e) {
                $errors++;
                Log::error('[FollowUp] Gagal kirim: '.$e->getMessage(), ['lead_id' => $lead->id, 'rule_id' => $rule->id]);
                activity('follow_up')->performedOn($lead)->withProperties([
                    'rule_id'   => $rule->id,
                    'sender_id' => $sender->id,
                    'error'     => $e->getMessage(),
                ])->log('WA follow-up gagal');
            }
        }

        $rule->updateQuietly(['last_run_at' => now()]);
        Log::info("[FollowUp] Rule #{$rule->id} selesai. sent=$sent errors=$errors targets={$eligible->count()}");
    }

    protected function notifyOwner(LeadFollowUpRule $rule, Lead $lead, WahaSender $sender, string $messageToLead, array $resultLead): void
    {
        $owner       = $lead->owner;
        $ownerNumber = $owner?->wa_number ? $this->waSanitize($owner->wa_number) : null;

        if (!$ownerNumber) {
            activity('follow_up_notify_owner')->performedOn($lead)->withProperties([
                'rule_id'   => $rule->id,
                'sender_id' => $sender->id,
                'owner_id'  => $owner?->id,
                'status'    => 'SKIPPED_NO_OWNER_NUMBER',
            ])->log('Notifikasi owner di-skip (owner tidak isi WA).');
            return;
        }

        $leadNumber = $this->waSanitize($lead->phone);
        if ($leadNumber && $leadNumber === $ownerNumber) {
            activity('follow_up_notify_owner')->performedOn($lead)->withProperties([
                'rule_id'   => $rule->id,
                'sender_id' => $sender->id,
                'owner_id'  => $owner?->id,
                'status'    => 'SKIPPED_SAME_NUMBER',
            ])->log('Notifikasi owner di-skip (nomor sama dengan lead).');
            return;
        }

        $ruleLabel = match ($rule->condition) {
            'no_chat'         => 'Belum di-chat',
            'chat_1_no_reply' => 'Chat 1x (belum balas)',
            'chat_2_no_reply' => 'Chat 2x (belum balas)',
            'chat_3_no_reply' => 'Chat 3x (belum balas)',
            default           => Str::headline($rule->condition)
        };

        $statusLead = data_get($resultLead, 'result.status') ?? data_get($resultLead, 'status') ?? 'UNKNOWN';
        $httpLead   = data_get($resultLead, 'http');
        $detailUrl  = route('leads.show', $lead);

        $ownerMsg = "Notifikasi Follow-up 📣\n"
                  . "Lead: {$lead->name} ({$leadNumber})\n"
                  . "Rule: {$ruleLabel}\n"
                  . "Status: {$statusLead}".($httpLead ? " (HTTP {$httpLead})" : '')."\n"
                  . "Pengirim: {$sender->name} ({$sender->number})\n"
                  . "Link: {$detailUrl}";

        try {
            $resultOwner = $this->sendViaWaha($sender->id, $ownerNumber, $ownerMsg);

            activity('follow_up_notify_owner')->performedOn($lead)->causedBy($owner)->withProperties([
                'rule_id'   => $rule->id,
                'sender_id' => $sender->id,
                'owner_id'  => $owner->id,
                'number'    => $ownerNumber,
                'http'      => data_get($resultOwner, 'http'),
                'status'    => data_get($resultOwner, 'result.status') ?? data_get($resultOwner, 'status'),
            ])->log('Notifikasi owner terkirim');
        } catch (\Throwable $e) {
            activity('follow_up_notify_owner')->performedOn($lead)->causedBy($owner)->withProperties([
                'rule_id'   => $rule->id,
                'sender_id' => $sender->id,
                'owner_id'  => $owner?->id,
                'error'     => $e->getMessage(),
            ])->log('Notifikasi owner gagal');
        }
    }

    /**
     * Ambil leads yang memenuhi kondisi. Gunakan alias meta_* agar tidak bentrok
     * dengan kolom asli (misal chat_count).
     */
    protected function eligibleLeadsFor(LeadFollowUpRule $rule)
    {
        $days  = (int) $rule->days_after;
        $since = now()->copy()->subDays($days)->startOfDay();

        $sub = Lead::query()
            ->select('leads.*')
            ->selectSub(function ($q) {
                $q->from('activity_log')
                  ->selectRaw('COUNT(*)')
                  ->whereColumn('activity_log.subject_id', 'leads.id')
                  ->where('activity_log.subject_type', Lead::class)
                  ->where('activity_log.log_name', 'wa_chat');
            }, 'meta_chat_count')
            ->selectSub(function ($q) {
                $q->from('activity_log')
                  ->selectRaw('MAX(created_at)')
                  ->whereColumn('activity_log.subject_id', 'leads.id')
                  ->where('activity_log.subject_type', Lead::class)
                  ->where('activity_log.log_name', 'wa_chat');
            }, 'meta_last_wa_chat_at')
            ->selectSub(function ($q) {
                $q->from('activity_log')
                  ->selectRaw('MAX(created_at)')
                  ->whereColumn('activity_log.subject_id', 'leads.id')
                  ->where('activity_log.subject_type', Lead::class)
                  ->whereIn('activity_log.log_name', ['wa_reply','wa_incoming']);
            }, 'meta_last_reply_at');

        if ($rule->lead_id) {
            $sub->where('leads.id', $rule->lead_id);
        }

        $sub->whereNotNull('leads.phone');

        $rows = DB::query()->fromSub($sub, 'L');

        switch ($rule->condition) {
            case 'no_chat':
                $rows->where('meta_chat_count', '=', 0)
                     ->where('L.created_at', '<=', $since);
                break;

            case 'chat_1_no_reply':
                $rows->where('meta_chat_count', '=', 1)
                     ->where(function ($q) {
                         $q->whereNull('meta_last_reply_at')
                           ->orWhereColumn('meta_last_reply_at', '<', 'meta_last_wa_chat_at');
                     })
                     ->where('meta_last_wa_chat_at', '<=', $since);
                break;

            case 'chat_2_no_reply':
                $rows->where('meta_chat_count', '=', 2)
                     ->where(function ($q) {
                         $q->whereNull('meta_last_reply_at')
                           ->orWhereColumn('meta_last_reply_at', '<', 'meta_last_wa_chat_at');
                     })
                     ->where('meta_last_wa_chat_at', '<=', $since);
                break;

            case 'chat_3_no_reply':
                $rows->where('meta_chat_count', '=', 3)
                     ->where(function ($q) {
                         $q->whereNull('meta_last_reply_at')
                           ->orWhereColumn('meta_last_reply_at', '<', 'meta_last_wa_chat_at');
                     })
                     ->where('meta_last_wa_chat_at', '<=', $since);
                break;

            default:
                $rows->whereRaw('1=0');
        }

        $leads = $rows->orderBy('L.id')->limit(200)->get()->map(function ($r) {
            $lead = new Lead((array) $r);
            $lead->exists = true;
            $lead->id     = (int) $r->id;
            return $lead;
        });

        return $leads;
    }

    protected function resolveSender(LeadFollowUpRule $rule): ?WahaSender
    {
        if ($rule->sender) return $rule->sender;

        return WahaSender::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    protected function resolveTemplateBody(LeadFollowUpRule $rule): string
    {
        if ($rule->template) {
            return (string) $rule->template->body;
        }
        return "Halo {{name}}, saya {{owner_name}} dari {{company}}. "
             . "Kami ingin follow-up terkait kebutuhan Anda. Balas pesan ini ya 🙂";
    }

    protected function fillTemplate(string $tpl, Lead $lead): string
    {
        $owner = $lead->owner;
        $map = [
            '{{name}}'          => $lead->name ?? ($lead->store_name ?: 'Kak'),
            '{{nama}}'          => $lead->name ?? ($lead->store_name ?: 'Kak'),
            '{{store_name}}'    => $lead->store_name ?? '-',
            '{{trial_ends_at}}' => optional($lead->trial_ends_at)->format('d M Y') ?? '-',
            '{{owner_name}}'    => $owner?->name ?? 'Tim',
            '{{company}}'       => $lead->company ?? ($lead->store_name ?? 'Perusahaan kami'),
        ];
        $withAt = [];
        foreach ($map as $k => $v) { $withAt['@'.$k] = $v; }

        return strtr($tpl, $withAt + $map);
    }

    protected function waSanitize(?string $p): ?string
    {
        if (!$p) return null;
        $n = preg_replace('/\D+/', '', $p);
        return $n ?: null;
    }

    /**
     * Kirim via controller WAHA.
     * Kompatibel dengan signature lama (1 arg) & baru (2 arg).
     */
    protected function sendViaWaha(int $senderId, string $recipient, string $message): array
    {
        $req = Request::create('/_internal/followup', 'POST', [
            'sender_id' => $senderId,
            'recipient' => $recipient,
            'message'   => $message,
        ]);

        $controller = app(WahaController::class);

        // Deteksi jumlah parameter yang dibutuhkan
        try {
            $ref = new \ReflectionMethod($controller, 'sendMessage');
            $paramCount = $ref->getNumberOfParameters();
        } catch (\Throwable $e) {
            $paramCount = 1; // fallback
        }

        // Panggil dengan 1 atau 2 argumen sesuai signature aktual
        if ($paramCount >= 2) {
            $resp = $controller->sendMessage($req, null);
        } else {
            $resp = $controller->sendMessage($req);
        }

        $http = method_exists($resp, 'getStatusCode') ? $resp->getStatusCode() : 200;
        $payload = [];

        if (method_exists($resp, 'getData')) {
            $payload = (array) $resp->getData(true);
        } elseif (method_exists($resp, 'getContent')) {
            $json = json_decode($resp->getContent(), true);
            if (is_array($json)) $payload = $json;
        }

        return ['http' => $http] + $payload;
    }
}
