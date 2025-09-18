<?php

namespace App\Console\Commands;

use App\Jobs\SendLeadFollowUpJob;
use Illuminate\Console\Command;

class SendLeadFollowUps extends Command
{
    protected $signature = 'send:lead-follow-ups {--now : Jalankan sinkron (tanpa queue)}';
    protected $description = 'Proses dan kirim reminder follow-up ke Leads sesuai rules dinamis.';

    public function handle(): int
    {
        if ($this->option('now')) {
            // jalan langsung (sinkron) — berguna untuk debug lokal
            (new SendLeadFollowUpJob())->handle();
            $this->info('Follow-up diproses sinkron.');
            return self::SUCCESS;
        }

        dispatch(new SendLeadFollowUpJob());
        $this->info('Job SendLeadFollowUpJob didispatch ke queue.');
        return self::SUCCESS;
    }
}
