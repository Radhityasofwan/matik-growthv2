<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lead;

class UpdateLeadTrialStatuses extends Command
{
    protected $signature = 'leads:update-trial-statuses';
    protected $description = 'Update status lead: trial hampir habis (notifikasi) & trial yang habis jadi nonactive';

    public function handle(): int
    {
        $today = now()->startOfDay();

        // H-1: Notifikasi
        $almost = Lead::where('status','trial')
            ->whereDate('trial_ends_at', $today->copy()->addDay()->toDateString())
            ->get();
        foreach ($almost as $lead) {
            activity()->performedOn($lead)->log("Trial akan habis besok untuk {$lead->store_name} ({$lead->email})");
        }

        // Sudah lewat: set nonactive
        $expired = Lead::where('status','trial')
            ->whereDate('trial_ends_at','<',$today->toDateString())
            ->get();
        foreach ($expired as $lead) {
            $lead->status = 'nonactive';
            $lead->saveQuietly();
            activity()->performedOn($lead)->log("Trial habis → status otomatis Nonaktif untuk {$lead->store_name} ({$lead->email})");
        }

        $this->info("Notified: {$almost->count()}, Set nonactive: {$expired->count()}");
        return self::SUCCESS;
    }
}
