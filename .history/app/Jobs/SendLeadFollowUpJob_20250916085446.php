<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Models\LeadFollowUpRule;
use App\Models\WATemplate;
use App\Models\WahaSender;
use App\Services\WahaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendLeadFollowUpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $ruleId;
    public int $leadId;

    public function __construct(int $ruleId, int $leadId)
    {
        $this->ruleId = $ruleId;
        $this->leadId = $leadId;
        // idempotency: jangan retry berkali-kali
        $this->onQueue('default');
        $this->afterCommit();
    }

    public function handle(WahaService $waha): void
    {
        $rule = LeadFollowUpRule::query()->active()->find($this->ruleId);
        $lead = Lead::find($this->leadId);

        if (!$rule || !$lead || !$lead->phone) {
            return;
        }

        /** @var WahaSender|null $sender */
        $sender = $rule->waha_sender_id ? WahaSender::find($rule->waha_sender_id) : WahaSender::query()->where('is_active', true)->orderByDesc('is_default')->first();
        if (!$sender) {
            Log::warning('FollowUp: sender tidak ditemukan', ['rule' => $rule->id, 'lead' => $lead->id]);
            return;
        }

        /** @var WATemplate|null $tpl */
        $tpl = $rule->wa_template_id ? WATemplate::find($rule->wa_template_id) : null;
        $body = $tpl?->body ?: 'Halo {{name}}, kami ingin mengingatkan kembali terkait layanan kami.';
        $msg  = $this->renderTemplate($body, $lead);

        try {
            $res = $waha->sendMessage($sender, (string)$lead->phone, $msg);
            $ok  = is_array($res) ? ($res['success'] ?? false) : false;

            // Catat activity agar:
            // 1) ada jejak chat (wa_chat)
            // 2) rule tidak mengirim ulang (wa_auto_rule; causer = rule)
            if ($ok) {
                activity()
                    ->useLog('wa_chat')
                    ->performedOn($lead)
                    ->withProperties([
                        'rule_id'   => $rule->id,
                        'auto'      => true,
                        'message'   => $msg,
                        'sender_id' => $sender->id,
                        'result'    => $res,
                    ])->log('Auto follow-up sent');

                activity()
                    ->useLog('wa_auto_rule')
                    ->causedBy($rule) // penting: dipakai query anti-duplicated
                    ->performedOn($lead)
                    ->withProperties([
                        'sender_id'   => $sender->id,
                        'template_id' => $tpl?->id,
                    ])->log('Rule applied');
            } else {
                Log::warning('FollowUp gagal kirim', [
                    'rule' => $rule->id,
                    'lead' => $lead->id,
                    'res'  => $res,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('FollowUp exception', ['rule' => $rule->id, 'lead' => $lead->id, 'err' => $e->getMessage()]);
        }
    }

    protected function renderTemplate(string $raw, Lead $lead): string
    {
        // dukung {{name}}, {{store_name}}, {{trial_ends_at}}
        $repls = [
            '/\{\{\s*name\s*\}\}/i'         => $lead->name ?: ($lead->store_name ?: 'Kak'),
            '/\{\{\s*store_name\s*\}\}/i'   => (string)($lead->store_name ?? ''),
            '/\{\{\s*trial_ends_at\s*\}\}/i'=> optional($lead->trial_ends_at)->format('d M Y') ?: '',
            // kompat: @{{name}}
            '/@\{\{\s*name\s*\}\}/i'        => $lead->name ?: ($lead->store_name ?: 'Kak'),
        ];

        $out = $raw;
        foreach ($repls as $pat => $val) {
            $out = preg_replace($pat, $val, $out);
        }
        return trim($out);
    }
}
