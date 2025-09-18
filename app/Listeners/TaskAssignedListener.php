<?php

namespace App\Listeners;

use App\Events\TaskAssigned as TaskAssignedEvent;
use App\Notifications\TaskAssigned as TaskAssignedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class TaskAssignedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(TaskAssignedEvent $event): void
    {
        // Kirim notifikasi ke pengguna yang ditugaskan
        $event->assignee->notify(new TaskAssignedNotification($event->task));
    }
}
