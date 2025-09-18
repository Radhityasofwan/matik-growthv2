<?php

namespace App\Listeners;

use App\Events\TaskAssigned;
use App\Notifications\TaskAssigned as TaskAssignedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OnTaskAssigned implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(TaskAssigned $event): void
    {
        // Kirim notifikasi ke user yang ditugaskan
        $event->assignee->notify(new TaskAssignedNotification($event->task));
    }
}
