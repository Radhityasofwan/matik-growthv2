<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskOverdue;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckOverdueTasks extends Command
{
    protected $signature = 'tasks:check-overdue';
    protected $description = 'Check for overdue tasks and send notifications';

    public function handle()
    {
        $this->info('Mencari tugas yang melewati tenggat...');

        // Tidak mengubah status menjadi "overdue" agar tidak bentrok dengan kolom kanban.
        $overdueTasks = Task::where('status', '!=', 'done')
                            ->where('due_date', '<', Carbon::now())
                            ->get();

        if ($overdueTasks->isEmpty()) {
            $this->info('Tidak ada tugas yang terlambat.');
            return 0;
        }

        $this->info("Menemukan {$overdueTasks->count()} tugas. Mengirim notifikasi...");

        foreach ($overdueTasks as $task) {
            if ($task->assignee) {
                $task->assignee->notify(new TaskOverdue($task));
                $this->line("Notifikasi untuk '{$task->title}' dikirim ke {$task->assignee->name}.");
            }
        }

        $this->info('Semua notifikasi berhasil dikirim.');
        return 0;
    }
}
