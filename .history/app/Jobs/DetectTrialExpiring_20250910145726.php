<?php

namespace App\Jobs;

use App\Events\TrialExpiring;
use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DetectTrialExpiring implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Running DetectTrialExpiring job...');

        // Define reminder points (e.g., 3 days and 1 day before expiry)
        $reminderDays = [3, 1];

        foreach ($reminderDays as $days) {
            $targetDate = now()->addDays($days)->startOfDay();

            $leads = Lead::where('status', 'trial')
                ->whereNotNull('trial_ends_at')
                ->whereDate('trial_ends_at', $targetDate)
                ->get();

            foreach ($leads as $lead) {
                Log::info("Found expiring trial for lead: {$lead->name}. Firing event.");
                TrialExpiring::dispatch($lead, $days);
            }
        }
    }
}

