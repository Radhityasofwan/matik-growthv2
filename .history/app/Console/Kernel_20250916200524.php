<?php

namespace App\Console;

use App\Jobs\DetectCampaignDeadlines;
use App\Jobs\DetectOverdueTasks;
// use App\Jobs\DetectTrialExpiring; // <- tetap dinonaktifkan agar tidak dobel
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
        // =============================
        // QUEUED JOBS (butuh queue worker)
        // =============================

        // Scoring lead harian
        $schedule->job(new ScoreLead())
            ->dailyAt('01:00')
            ->onOneServer()->withoutOverlapping()->runInBackground();

        // Deteksi task overdue berkala (realtime-ish)
        $schedule->job(new DetectOverdueTasks())
            ->everyFifteenMinutes()
            ->onOneServer()->withoutOverlapping()->runInBackground();

        // Deteksi campaign yg mendekati deadline
        $schedule->job(new DetectCampaignDeadlines())
            ->dailyAt('08:00')
            ->onOneServer()->withoutOverlapping()->runInBackground();

        // Rekap harian
        $schedule->job(new SendDailyRecap())
            ->dailyAt('17:00')
            ->onOneServer()->withoutOverlapping()->runInBackground();

        // Digest mingguan (Senin 08:00)
        $schedule->job(new SendWeeklyDigest())
            ->weeklyOn(1, '08:00')
            ->onOneServer()->withoutOverlapping()->runInBackground();

        // =============================
        // ARTISAN COMMANDS
        // =============================

        // Konsolidasi logika trial (tanpa DetectTrialExpiring job)
        $schedule->command('leads:update-trial-statuses')
            ->dailyAt('08:00')
            ->onOneServer()->withoutOverlapping();

        // Kirim reminder campaign harian — geser sedikit untuk menghindari tabrakan 09:00
        $schedule->command('campaigns:send-reminders')
            ->dailyAt('09:05')
            ->onOneServer()->withoutOverlapping();

        // Sweep WA Task: H-1 due & H+1 overdue (dedup harian)
        $schedule->command('tasks:sweep-due')
            ->dailyAt('09:00')
            ->onOneServer()->withoutOverlapping();

        // Cek overdue via command per jam (pelengkap di luar job quarter-hourly)
        $schedule->command('tasks:check-overdue')
            ->hourlyAt(10) // setiap jam menit ke-10
            ->onOneServer()->withoutOverlapping();

        // Process all dynamic automation rules (umum)
        $schedule->command('app:process-automations')
            ->hourly()
            ->onOneServer()->withoutOverlapping();

        // Mesin reminder follow-up lead dinamis (Step 1)
        $schedule->command('send:lead-follow-ups')
            ->everyFifteenMinutes()
            ->onOneServer()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }

    /**
     * Daftar command yang tidak ter-load otomatis.
     */
    protected $commands = [
        \App\Console\Commands\FollowUpProbe::class,
        \App\Console\Commands\TasksSweepDue::class,
    ];
}
