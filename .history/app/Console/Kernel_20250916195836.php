<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

// Registrasi eksplisit command yang kita pakai
use App\Console\Commands\SendLeadFollowUps;
use App\Console\Commands\TasksSweepDue;
use App\Console\Commands\FollowUpProbe;

class Kernel extends ConsoleKernel
{
    /**
     * Jika tidak mengandalkan auto-discovery, pastikan command terdaftar di sini.
     * (Aman dipakai berdampingan dengan $this->load(__DIR__.'/Commands');)
     *
     * @var array<class-string>
     */
    protected $commands = [
        SendLeadFollowUps::class,
        TasksSweepDue::class,
        FollowUpProbe::class, // hanya untuk uji manual, tidak dijadwalkan
    ];

    /**
     * Default timezone untuk semua jadwal scheduler.
     * Kita set ke Asia/Jakarta agar konsisten.
     */
    protected function scheduleTimezone(): \DateTimeZone|string|null
    {
        // Pakai config('app.timezone') jika sudah diset ke Asia/Jakarta,
        // fallback hardcode agar pasti benar.
        return config('app.timezone', 'Asia/Jakarta');
    }

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // === Core engine: Lead follow-ups (Step 1) ===
        // Proses aturan dinamis & kirim WA otomatis.
        // Tanpa bentrok: withoutOverlapping + onOneServer.
        $schedule->command('send:lead-follow-ups --limit=200')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->onOneServer()
            ->description('Process dynamic lead follow-up rules and send WhatsApp messages');

        // === Tasks sweep (H-1 reminder & H+1 overdue warning) ===
        // Dijalankan harian pukul 09:00 WIB.
        $schedule->command('tasks:sweep-due')
            ->dailyAt('09:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->description('Send task WhatsApp notifications: H-1 due reminders and H+1 overdue warnings');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        // Auto-discovery semua command di folder App\Console\Commands
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
