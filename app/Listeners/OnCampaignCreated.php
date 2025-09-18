<?php

namespace App\Listeners;

use App\Events\CampaignCreated;
use App\Jobs\CreateOnboardingTasks;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OnCampaignCreated implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CampaignCreated $event): void
    {
        // Dispatch job to create default tasks for the new campaign
        CreateOnboardingTasks::dispatch($event->campaign)->onQueue('default');
    }
}
