<?php

namespace App\Listeners;

use App\Events\TrialExpiring;
use App\Jobs\SendReminderWA;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OnTrialExpiring implements ShouldQueue
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
    public function handle(TrialExpiring $event): void
    {
        SendReminderWA::dispatch($event->lead, $event->daysRemaining)->onQueue('notifications');
    }
}
