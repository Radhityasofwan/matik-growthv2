<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Notifications\CampaignReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendCampaignReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder notifications for campaigns ending in 3 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Mencari kampanye yang akan berakhir...');

        // Cari kampanye yang akan berakhir tepat 3 hari dari sekarang
        $targetDate = Carbon::now()->addDays(3)->toDateString();

        $campaigns = Campaign::where('status', 'active')
                             ->whereDate('end_date', $targetDate)
                             ->get();

        if ($campaigns->isEmpty()) {
            $this->info('Tidak ada kampanye yang akan berakhir dalam 3 hari.');
            return 0;
        }

        $this->info("Menemukan {$campaigns->count()} kampanye. Mengirim notifikasi...");

        foreach ($campaigns as $campaign) {
            // Kirim notifikasi ke pemilik kampanye
            $campaign->owner->notify(new CampaignReminder($campaign));
            $this->line("Notifikasi untuk '{$campaign->name}' telah dikirim ke {$campaign->owner->email}.");
        }

        $this->info('Semua notifikasi pengingat berhasil dikirim.');
        return 0;
    }
}
