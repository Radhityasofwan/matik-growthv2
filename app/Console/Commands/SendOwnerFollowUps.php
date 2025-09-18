<?php

namespace App\Console\Commands;

use App\Jobs\SendOwnerFollowUpJob;
use App\Models\OwnerFollowUpRule;
use Illuminate\Console\Command;

class SendOwnerFollowUps extends Command
{
    protected $signature   = 'send:owner-follow-ups {--dry-run} {--limit=200}';
    protected $description = 'Proses aturan notifikasi owner.';

    public function handle(): int
    {
        $now   = now();
        $dry   = (bool)$this->option('dry-run');
        $cap   = (int)$this->option('limit');
        $rules = OwnerFollowUpRule::query()->active()->orderBy('id')->get();

        if ($rules->isEmpty()) { $this->info('Tidak ada rule owner aktif.'); return self::SUCCESS; }

        foreach ($rules as $rule) {
            $eligible = SendOwnerFollowUpJob::previewEligible($rule, $cap); // static helper untuk dry-run
            $this->line("== OwnerRule #{$rule->id} [{$rule->condition}] days_after={$rule->days_after} ==");
            $this->info('  Target: '.count($eligible).' lead'.($dry?' (dry-run)':''));

            if (!$dry && $eligible) {
                SendOwnerFollowUpJob::dispatchSync($rule->id);
                $rule->forceFill(['last_run_at'=>$now])->saveQuietly();
            }
        }
        return self::SUCCESS;
    }
}
