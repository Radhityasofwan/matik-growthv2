<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Subscription;
use App\Notifications\CampaignReminder;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DetectCampaignDeadlines implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Running job to detect campaign deadlines and subscription expirations.');

        // 1. Detect Campaign Deadlines (H-3)
        $this->findAndNotifyExpiringCampaigns();

        // 2. Detect Subscription Expirations (H-3)
        $this->findAndNotifyExpiringSubscriptions();

        Log::info('Finished detecting deadlines and expirations.');
    }

    private function findAndNotifyExpiringCampaigns(): void
    {
        $reminderDate = Carbon::now()->addDays(3)->toDateString();
        $expiringCampaigns = Campaign::where('status', 'in_progress')
            ->whereDate('end_date', $reminderDate)
            ->get();

        if ($expiringCampaigns->isEmpty()) {
            Log::info('No expiring campaigns found for H-3 reminder.');
            return;
        }

        foreach ($expiringCampaigns as $campaign) {
            Log::info("Campaign '{$campaign->name}' is ending in 3 days. Notifying team.");
            // Assuming campaign has a 'team' or 'owner' to notify. For now, we'll notify the first user.
            $userToNotify = \App\Models\User::first();
            if ($userToNotify) {
                $userToNotify->notify(new CampaignReminder($campaign));
            }
        }
    }

    private function findAndNotifyExpiringSubscriptions(): void
    {
        $reminderDate = Carbon::now()->addDays(3)->toDateString();
        $expiringSubscriptions = Subscription::where('status', 'active')
            ->where('auto_renew', true)
            ->whereDate('end_date', $reminderDate)
            ->get();

        if ($expiringSubscriptions->isEmpty()) {
            Log::info('No expiring subscriptions found for H-3 reminder.');
            return;
        }

        foreach ($expiringSubscriptions as $subscription) {
            Log::info("Subscription for lead '{$subscription->lead->name}' is ending in 3 days. Dispatching renewal WA.");
            SendRenewalWA::dispatch($subscription)->onQueue('notifications');
        }
    }
}
