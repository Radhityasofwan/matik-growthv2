<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        \App\Events\TaskAssigned::class => [
            \App\Listeners\OnTaskAssigned::class,
        ],
        \App\Events\TrialCreated::class => [
            \App\Listeners\OnTrialCreated::class,
        ],
        \App\Events\TrialExpiring::class => [
            \App\Listeners\OnTrialExpiring::class,
        ],
        // --- NEW MAPPING ---
        \App\Events\CampaignCreated::class => [
            \App\Listeners\OnCampaignCreated::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

