<?php

namespace App\Console;

use App\Jobs\DetectCampaignDeadlines;
use App\Jobs\DetectOverdueTasks;
// use App\Jobs\DetectTrialExpiring; // <- DIHAPUS dari scheduler agar tidak dobel
use App\Jobs\ScoreLead;
use App\Jobs\SendDailyRecap;
use App\Jobs\SendWeeklyDigest;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Pakai timezone dari config (set di config/app.php atau .env APP_TIMEZONE).
     * Contoh: 'Asia/Jakarta'
     */
    protected function scheduleTimezone(): ?\DateTimeZone
    {
        return new \DateTimeZone(config('app.timezone', 'Asia/Jakarta'));
    }

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // === QUEUED JOBS (butuh queue worker) ===
        $schedule->job(new ScoreLead())
            ->dailyAt('01:00')
            ->onOneServer()->withoutOverlapping()->runInBackground();

        $schedule->job(new DetectOverdueTasks())
            ->everyFifteenMinutes()
            ->onOneServer()->withoutOverlapping()->runInBackground();

        $schedule->job(new DetectCampaignDeadlines())
            ->dailyAt('08:00')
            ->onOneServer()->withoutOverlapping()->runInBackground();

        $schedule->job(new SendDailyRecap())
            ->dailyAt('17:00')
            ->onOneServer()->withoutOverlapping()->runInBackground();

        $schedule->job(new SendWeeklyDigest())
            ->weeklyOn(1, '08:00') // Monday 08:00
            ->onOneServer()->withoutOverlapping()->runInBackground();

        // === ARTISAN COMMANDS ===
        // Konsolidasi logika trial ke command ini (tidak pakai DetectTrialExpiring job lagi)
        $schedule->command('leads:update-trial-statuses')
            ->dailyAt('08:00')
            ->onOneServer()->withoutOverlapping();

        // Jalankan pengingat campaign harian (atur jam supaya tidak tabrakan)
        $schedule->command('campaigns:send-reminders')
            ->dailyAt('09:00')
            ->onOneServer()->withoutOverlapping();

        // Cek overdue via command per jam (job sudah tiap 15 menit — jika keduanya memang dibutuhkan, biarkan;
        // kalau dirasa dobel, hapus salah satunya).
        $schedule->command('tasks:check-overdue')
            ->hourlyAt(10) // setiap jam menit ke-10
            ->onOneServer()->withoutOverlapping();

        // Process all dynamic automation rules.
        $schedule->command('app:process-automations')->hourly()->onOneServer()->withoutOverlapping();

         $schedule->command('send:lead-follow-ups')->everyFifteenMinutes()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
    protected $commands = [
    \App\Console\Commands\FollowUpProbe::class,
    ];

}
