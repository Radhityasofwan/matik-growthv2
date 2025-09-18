<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\LeadFollowUpRule;
use App\Models\Lead;
use App\Models\WahaSender;
use App\Services\WahaService;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;

class SendLeadFollowUps extends Command
{
    protected $signature = 'send:lead-follow-ups';
    protected $description = 'Kirim WhatsApp follow-up otomatis ke leads sesuai aturan dinamis';

    public function handle(): int
    {
        $now = now();
        $rules = LeadFollowUpRule::active()->get();

        if ($rules->isEmpty()) {
            $this->info('Tidak ada rule aktif.');
            return Command::SUCCESS;
        }

        $svc = app(WahaService::class);

        foreach ($rules as $rule) {
            try {
                $targets = $this->resolveTargets($rule);
                foreach ($targets as $lead) {
                    if (!$this->isDue($rule, $lead)) continue;

                    $sender = $this->resolveSender($rule);
                    if (!$sender) continue;

                    $phone = $this->normalizePhone($lead->phone);
                    if (!$phone) continue;

                    // Buat pesan (template atau fallback default)
                    $message = $this->buildMessage($rule, $lead);

                    // Kirim
                    $res = $svc->sendMessage($sender, $phone, $message);

                    // Log aktivitas lead (timeline)
                    activity()
                        ->performedOn($lead)
                        ->causedBy($rule->creator ?: auth()->user())
                        ->withProperties([
                            'channel' => 'whatsapp',
                            'auto'    => true,
                            'rule_id' => $rule->id,
                            'sender'  => $sender->id,
                            'resp'    => $res,
                        ])
                        ->log('wa_followup_sent');

                    // Notifikasi internal ke owner (jika punya wa_number)
                    $this->notifyOwner($svc, $lead, $sender);

                    $this->info("Sent follow-up to lead #{$lead->id} ({$lead->email}) via WA.");
                }

                // Update waktu eksekusi terakhir rule
                $rule->last_run_at = $now;
                $rule->saveQuietly();
            } catch (\Throwable $e) {
                Log::error('FollowUp rule error', ['rule_id' => $rule->id, 'err' => $e->getMessage()]);
                $this->error("Error rule {$rule->id}: {$e->getMessage()}");
            }
        }

        return Command::SUCCESS;
    }

    /** Tentukan target leads: spesifik lead atau semua yang cocok kondisi. */
    protected function resolveTargets(LeadFollowUpRule $rule)
    {
        if ($rule->lead_id) {
            return Lead::where('id', $rule->lead_id)->get();
        }

        // Global rule: seleksi berdasarkan kondisi.
        // Kita pakai Activitylog untuk infer chat count & reply.
        // Deskripsi yang dipakai logger: 'wa_followup_sent' atau pengiriman WA lain (jika ada).
        // Balasan WA (jika webhook menulis 'wa_reply') akan dihormati.
        return Lead::query()->get(); // ambil semua, filter nanti via isDue()
    }

    /** Cek apakah lead sudah "due" sesuai rule & aktivitasnya. */
    protected function isDue(LeadFollowUpRule $rule, Lead $lead): bool
    {
        $days = (int) $rule->days_after;
        $anchor = null;

        $chatCount = $this->chatCount($lead);
        $lastChat  = $this->lastChatAt($lead);
        $hasReplyAfterLastChat = $this->hasReplyAfter($lead, $lastChat);

        switch ($rule->condition) {
            case 'no_chat':
                if ($chatCount > 0) return false;
                // anchor = created_at
                $anchor = $lead->created_at ?: $lead->updated_at ?: now()->subYears(1);
                break;

            case 'chat_1_no_reply':
                if ($chatCount !== 1) return false;
                if ($hasReplyAfterLastChat) return false;
                $anchor = $lastChat ?: $lead->created_at ?: now()->subYears(1);
                break;

            case 'chat_2_no_reply':
                if ($chatCount !== 2) return false;
                if ($hasReplyAfterLastChat) return false;
                $anchor = $lastChat ?: $lead->created_at ?: now()->subYears(1);
                break;

            case 'chat_3_no_reply':
                if ($chatCount !== 3) return false;
                if ($hasReplyAfterLastChat) return false;
                $anchor = $lastChat ?: $lead->created_at ?: now()->subYears(1);
                break;

            default:
                return false;
        }

        return $anchor ? now()->diffInDays($anchor) >= $days : false;
    }

    protected function chatCount(Lead $lead): int
    {
        // Hitung aktivitas pengiriman WA otomatis/manual
        return Activity::query()
            ->where('subject_type', '=', get_class($lead))
            ->where('subject_id', '=', $lead->id)
            ->whereIn('description', ['wa_followup_sent','wa_sent','wa_broadcast_sent'])
            ->count();
    }

    protected function lastChatAt(Lead $lead): ?Carbon
    {
        $last = Activity::query()
            ->where('subject_type', '=', get_class($lead))
            ->where('subject_id', '=', $lead->id)
            ->whereIn('description', ['wa_followup_sent','wa_sent','wa_broadcast_sent'])
            ->orderByDesc('created_at')
            ->first();

        return $last?->created_at;
    }

    protected function hasReplyAfter(Lead $lead, ?Carbon $after = null): bool
    {
        if (!$after) return false;

        // Webhook WA idealnya menulis 'wa_reply' pada activity log lead
        return Activity::query()
            ->where('subject_type', '=', get_class($lead))
            ->where('subject_id', '=', $lead->id)
            ->where('description', '=', 'wa_reply')
            ->where('created_at', '>', $after)
            ->exists();
    }

    protected function resolveSender(LeadFollowUpRule $rule): ?\App\Models\WahaSender
    {
        if ($rule->sender) return $rule->sender;

        // fallback: sender default
        return WahaSender::where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    protected function normalizePhone(?string $p): ?string
    {
        if (!$p) return null;
        $d = preg_replace('/\D+/', '', $p);
        if ($d === '') return null;
        if (preg_match('/^0[0-9]{8,}$/', $d)) $d = '62'.substr($d, 1);
        return $d;
    }

    protected function buildMessage(LeadFollowUpRule $rule, Lead $lead): string
    {
        $tpl = $rule->template?->body ?? "Halo {{name}}, kami ingin menindaklanjuti kebutuhan Anda terkait {{store_name}}.";
        // Variabel dinamis (sinkron dg arsitektur template)
        $repl = [
            '/\{\{\s*name\s*\}\}/i'         => $lead->name ?? 'Kak',
            '/\{\{\s*store_name\s*\}\}/i'   => $lead->store_name ?? '-',
            '/\{\{\s*email\s*\}\}/i'        => $lead->email ?? '-',
            '/\{\{\s*trial_ends_at\s*\}\}/i'=> optional($lead->trial_ends_at)->format('d M Y') ?? '-',
        ];
        return preg_replace(array_keys($repl), array_values($repl), $tpl);
    }

    protected function notifyOwner(WahaService $svc, Lead $lead, \App\Models\WahaSender $sender): void
    {
        $owner = $lead->owner;
        $num   = $owner?->wa_number ? preg_replace('/\D+/', '', $owner->wa_number) : null;
        if (!$num) return;

        $msg = "Reminder terkirim ke lead: {$lead->name} ({$lead->store_name}).";
        try { $svc->sendMessage($sender, $num, $msg); } catch (\Throwable $e) {}
    }
}
