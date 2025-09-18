<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Task;
use App\Notifications\TaskOverdue;

class DetectOverdueTasks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $overdueTasks = Task::where('status', '!=', 'done')
                            ->where('due_date', '<', now())
                            ->get();

        foreach ($overdueTasks as $task) {
            $task->update(['status' => 'overdue']);

            // Kirim notifikasi ke assignee jika ada
            if ($task->assignee) {
                $task->assignee->notify(new TaskOverdue($task));
            }
        }
    }
}
