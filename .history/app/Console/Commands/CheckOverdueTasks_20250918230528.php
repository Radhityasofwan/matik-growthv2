<?php

namespace App\Console;

use App\Jobs\DetectCampaignDeadlines;
use App\Jobs\DetectOverdueTasks;
use App\Jobs\ScoreLead;
use App\Jobs\SendDailyRecap;
use App\Jobs\SendWeeklyDigest;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function scheduleTimezone(): ?\DateTimeZone
    {
        return new \DateTimeZone(config('app.timezone', 'Asia/Jakarta'));
    }

    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new ScoreLead())->dailyAt('01:00')->onOneServer()->withoutOverlapping()->runInBackground();
        $schedule->job(new DetectOverdueTasks())->everyFifteenMinutes()->onOneServer()->withoutOverlapping()->runInBackground();
        $schedule->job(new DetectCampaignDeadlines())->dailyAt('08:00')->onOneServer()->withoutOverlapping()->runInBackground();
        $schedule->job(new SendDailyRecap())->dailyAt('17:00')->onOneServer()->withoutOverlapping()->runInBackground();
        $schedule->job(new SendWeeklyDigest())->weeklyOn(1, '08:00')->onOneServer()->withoutOverlapping()->runInBackground();

        $schedule->command('leads:update-trial-statuses')->dailyAt('08:00')->onOneServer()->withoutOverlapping();
        $schedule->command('campaigns:send-reminders')->dailyAt('09:05')->onOneServer()->withoutOverlapping();

        // H-1 & H+1
        $schedule->command('tasks:sweep-due')->dailyAt('09:00')->onOneServer()->withoutOverlapping();
        $schedule->command('tasks:check-overdue')->hourlyAt(10)->onOneServer()->withoutOverlapping();

        $schedule->command('app:process-automations')->hourly()->onOneServer()->withoutOverlapping();
        $schedule->command('send:lead-follow-ups')->everyFifteenMinutes()->onOneServer()->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }

    protected $commands = [
        \App\Console\Commands\FollowUpProbe::class,
        \App\Console\Commands\TasksSweepDue::class,
        \App\Console\Commands\CheckOverdueTasks::class, // <— pastikan terdaftar
    ];
}
