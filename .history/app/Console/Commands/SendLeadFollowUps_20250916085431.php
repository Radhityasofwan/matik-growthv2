<?php

namespace App\Console\Commands;

use App\Jobs\SendLeadFollowUpJob;
use App\Models\Lead;
use App\Models\LeadFollowUpRule;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SendLeadFollowUps extends Command
{
    protected $signature = 'send:lead-follow-ups {--dry-run : Tampilkan target tanpa kirim} {--limit=200 : Batas maksimal target per rule per eksekusi}';

    protected $description = 'Proses aturan follow-up dinamis dan kirim WA otomatis sesuai rule aktif.';

    public function handle(): int
    {
        $now = now();
        $dry = (bool)$this->option('dry-run');
        $cap = (int)$this->option('limit');

        $rules = LeadFollowUpRule::query()->active()->orderBy('id')->get();
        if ($rules->isEmpty()) {
            $this->info('Tidak ada rule aktif.');
            return self::SUCCESS;
        }

        foreach ($rules as $rule) {
            $this->line("== Rule #{$rule->id} [{$rule->condition}] days_after={$rule->days_after} ==");

            $targets = $this->buildQueryForRule($rule, $now)->limit($cap)->get(['leads.id']);
            $count   = $targets->count();

            if ($count === 0) {
                $this->line('  (tidak ada target)');
                $rule->forceFill(['last_run_at' => $now])->saveQuietly();
                continue;
            }

            $this->info("  Target: {$count} lead" . ($dry ? ' (dry-run)' : ''));

            if (!$dry) {
                foreach ($targets as $row) {
                    // jalankan JOB secara sinkron agar langsung kirim (tanpa perlu queue worker)
                    SendLeadFollowUpJob::dispatchSync($rule->id, (int)$row->id);
                }
                $rule->forceFill(['last_run_at' => $now])->saveQuietly();
            }
        }

        return self::SUCCESS;
    }

    /**
     * Build query kandidat untuk satu rule dengan agregasi aman (tanpa HAVING alias bentrok).
     */
    protected function buildQueryForRule(LeadFollowUpRule $rule, Carbon $now)
    {
        // Subquery jumlah & last chat (outgoing)
        $waChats = DB::table('activity_log')
            ->selectRaw('subject_id, COUNT(*) as _chat_count, MAX(created_at) as _last_wa_chat_at')
            ->where('subject_type', Lead::class)
            ->where('log_name', 'wa_chat')
            ->groupBy('subject_id');

        // Subquery last reply (incoming)
        $waReplies = DB::table('activity_log')
            ->selectRaw('subject_id, MAX(created_at) as _last_reply_at')
            ->where('subject_type', Lead::class)
            ->whereIn('log_name', ['wa_reply', 'wa_incoming'])
            ->groupBy('subject_id');

        // Subquery sudah pernah dikirim oleh rule ini (hindari duplikat)
        $already = DB::table('activity_log')
            ->selectRaw('subject_id, 1 as _sent')
            ->where('subject_type', Lead::class)
            ->where('log_name', 'wa_auto_rule')
            ->where('causer_type', LeadFollowUpRule::class)
            ->where('causer_id', $rule->id);

        $q = Lead::query()
            ->select('leads.id')
            ->whereNotNull('leads.phone')
            ->leftJoinSub($waChats, 'wc', fn($j) => $j->on('wc.subject_id', '=', 'leads.id'))
            ->leftJoinSub($waReplies, 'wr', fn($j) => $j->on('wr.subject_id', '=', 'leads.id'))
            ->leftJoinSub($already, 'sent', fn($j) => $j->on('sent.subject_id', '=', 'leads.id'))
            ->whereNull('sent._sent');

        // Batasi ke lead tertentu bila rule spesifik lead_id
        if ($rule->lead_id) {
            $q->where('leads.id', $rule->lead_id);
        }

        $after = $now->copy()->subDays($rule->days_after);

        switch ($rule->condition) {
            case 'no_chat':
                // Belum pernah di-chat dan usia lead >= days_after
                $q->where(function ($w) {
                    $w->whereNull('wc._chat_count')->orWhere('wc._chat_count', 0);
                })->where('leads.created_at', '<=', $after);
                break;

            case 'chat_1_no_reply':
                $q->where('wc._chat_count', 1)
                  ->where(function ($w) {
                      $w->whereNull('wr._last_reply_at')
                        ->orWhereColumn('wr._last_reply_at', '<', 'wc._last_wa_chat_at');
                  })
                  ->where('wc._last_wa_chat_at', '<=', $after);
                break;

            case 'chat_2_no_reply':
                $q->where('wc._chat_count', 2)
                  ->where(function ($w) {
                      $w->whereNull('wr._last_reply_at')
                        ->orWhereColumn('wr._last_reply_at', '<', 'wc._last_wa_chat_at');
                  })
                  ->where('wc._last_wa_chat_at', '<=', $after);
                break;

            case 'chat_3_no_reply':
                $q->where('wc._chat_count', 3)
                  ->where(function ($w) {
                      $w->whereNull('wr._last_reply_at')
                        ->orWhereColumn('wr._last_reply_at', '<', 'wc._last_wa_chat_at');
                  })
                  ->where('wc._last_wa_chat_at', '<=', $after);
                break;

            default:
                // Condition tidak dikenal → kosongkan hasil
                $q->whereRaw('1=0');
        }

        return $q;
    }
}
