<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\Lead;
use App\Jobs\ScoreLead;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // --- EXISTING JOBS ---
        $schedule->job(new \App\Jobs\DetectTrialExpiring)->hourly();
        $schedule->job(new \App\Jobs\DetectOverdueTasks)->everyFifteenMinutes();
        $schedule->job(new \App\Jobs\SendDailyRecap)->dailyAt('17:00');

        // --- NEW JOB ---
        // Schedule a task to score all leads daily at midnight.
        $schedule->call(function () {
            Lead::where('status', '!=', 'churn')->each(function ($lead) {
                ScoreLead::dispatch($lead);
            });
        })->daily()->name('score_all_leads');
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

