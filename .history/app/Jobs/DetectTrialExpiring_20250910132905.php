<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Lead;
use App\Models\User; // Notifikasi mungkin dikirim ke admin/owner
use App\Notifications\TrialExpiringReminder; // Asumsi notifikasi ini ada

class DetectTrialExpiring implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Logika untuk mengirim reminder H-3 dan H-1
        $expiringIn3Days = Lead::where('status', 'trial')
                               ->whereDate('trial_ends_at', now()->addDays(3)->toDateString())
                               ->get();

        foreach ($expiringIn3Days as $lead) {
            // Kirim notifikasi H-3
            // Contoh: $lead->notify(new TrialExpiringReminder($lead, '3 days'));
            // Di sini kita bisa dispatch job lain untuk mengirim WA
            // SendReminderWA::dispatch($lead, 'H-3');
        }

        $expiringIn1Day = Lead::where('status', 'trial')
                               ->whereDate('trial_ends_at', now()->addDay()->toDateString())
                               ->get();

        foreach ($expiringIn1Day as $lead) {
            // Kirim notifikasi H-1
            // Contoh: $lead->notify(new TrialExpiringReminder($lead, '1 day'));
            // SendReminderWA::dispatch($lead, 'H-1');
        }
    }
}
