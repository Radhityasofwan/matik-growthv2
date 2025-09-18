<?php

namespace App\Listeners;

use App\Events\TrialCreated;
use App\Jobs\SendWelcomeWA;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OnTrialCreated implements ShouldQueue
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
    public function handle(TrialCreated $event): void
    {
        // Dispatch the job to send a welcome WhatsApp message
        SendWelcomeWA::dispatch($event->lead)->onQueue('notifications');
    }
}
