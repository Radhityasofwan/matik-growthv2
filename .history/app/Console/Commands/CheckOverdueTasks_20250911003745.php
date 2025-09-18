<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskOverdue;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckOverdueTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:check-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for overdue tasks and send notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Mencari tugas yang melewati tenggat...');

        $overdueTasks = Task::where('status', '!=', 'done')
                            ->where('due_date', '<', Carbon::now())
                            ->where('status', '!=', 'overdue') // Agar tidak mengirim notif berulang
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
            // Update status tugas menjadi 'overdue'
            $task->update(['status' => 'overdue']);
        }

        $this->info('Semua notifikasi berhasil dikirim.');
        return 0;
    }
}
