<?php

namespace App\Console;

use App\Jobs\DetectCampaignDeadlines;
use App\Jobs\DetectOverdueTasks;
use App\Jobs\DetectTrialExpiring;
use App\Jobs\ScoreLead;
use App\Jobs\SendDailyRecap;
use App\Jobs\SendWeeklyDigest;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // --- Core Automation Tasks ---
        $schedule->job(new ScoreLead())->daily()->at('01:00');
        $schedule->job(new DetectTrialExpiring())->hourly();
        $schedule->job(new DetectOverdueTasks())->everyFifteenMinutes();
        $schedule->job(new SendDailyRecap())->daily()->at('17:00');
        $schedule->job(new DetectCampaignDeadlines())->daily()->at('08:00');
        $schedule->command('campaigns:send-reminders')->daily()->at('09:00');
        $schedule->command('tasks:check-overdue')->daily();at('09:00');

        // --- NEWLY SCHEDULED JOB ---
        $schedule->job(new SendWeeklyDigest())->weekly()->mondays()->at('08:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

