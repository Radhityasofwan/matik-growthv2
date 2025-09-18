<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\DetectTrialExpiring;
use App\Jobs\DetectOverdueTasks;
use App\Jobs\DetectCampaignDeadlines;
use App\Jobs\SendWeeklyDigest;
use App\Jobs\SendDailyRecap;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Cek lead yang trial-nya akan berakhir dan kirim notifikasi
        $schedule->job(new DetectTrialExpiring)->hourly()->name('detect-trial-expiring');

        // Cek tugas yang overdue, update status, dan kirim notifikasi
        $schedule->job(new DetectOverdueTasks)->everyFifteenMinutes()->name('detect-overdue-tasks');

        // Cek campaign yang mendekati deadline dan kirim pengingat
        $schedule->job(new DetectCampaignDeadlines)->hourly()->name('detect-campaign-deadlines');

        // Kirim ringkasan mingguan setiap Senin pagi
        $schedule->job(new SendWeeklyDigest)->weeklyOn(1, '08:00')->name('send-weekly-digest');

        // Kirim rekap harian setiap sore
        $schedule->job(new SendDailyRecap)->dailyAt('17:00')->name('send-daily-recap');
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
