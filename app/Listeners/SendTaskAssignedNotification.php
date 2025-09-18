<?php

namespace App\Listeners;

use App\Events\TaskAssigned;
use App\Models\WahaSender;
use App\Services\WahaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendTaskAssignedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected $wahaService;

    /**
     * Create the event listener.
     */
    public function __construct(WahaService $wahaService)
    {
        $this->wahaService = $wahaService;
    }

    /**
     * Handle the event.
     */
    public function handle(TaskAssigned $event): void
    {
        $task = $event->task;
        $assignee = $event->assignee;

        // Find a default active Waha sender
        $sender = WahaSender::where('is_active', true)->where('is_default', true)->first();

        // If no default sender, try to find any active sender
        if (!$sender) {
            $sender = WahaSender::where('is_active', true)->first();
        }

        if ($sender && $assignee->phone) {
            $message = "Halo {$assignee->name},\n\nAnda memiliki tugas baru yang ditugaskan:\n\nJudul: {$task->title}\nDeskripsi: {$task->description}\nPrioritas: {$task->priority}\nJatuh Tempo: " . ($task->due_date ? $task->due_date->format('d M Y') : 'Tidak ada') . "\n\nMohon segera periksa tugas ini.";

            $this->wahaService->sendMessage($sender, $assignee->phone, $message);
        }
    }
}
