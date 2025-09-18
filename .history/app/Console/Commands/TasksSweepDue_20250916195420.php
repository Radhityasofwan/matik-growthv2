<?php

namespace App\Console\Commands;

use App\Jobs\SendTaskWhatsAppJob;
use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TasksSweepDue extends Command
{
    protected $signature = 'tasks:sweep-due {--limit=500}';
    protected $description = 'Kirim WA otomatis: H-1 due date & H+1 overdue untuk tasks (dedup harian).';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $this->line('== Sweep H-1 (due besok) ==');
        $dueTomorrow = Task::dueTomorrow()
            ->whereIn('status', ['open','in_progress'])
            ->limit($limit)->get();

        foreach ($dueTomorrow as $task) {
            if ($this->alreadySentToday($task->id, 'task_wa_due')) continue;
            SendTaskWhatsAppJob::dispatchSync($task->id, 'due_h1');
            $this->markSentToday($task->id, 'task_wa_due');
        }

        $this->line('== Sweep H+1 (overdue) ==');
        $overduePlusOne = Task::overduePlusOne()
            ->whereIn('status', ['open','in_progress'])
            ->limit($limit)->get();

        foreach ($overduePlusOne as $task) {
            if ($this->alreadySentToday($task->id, 'task_wa_overdue')) continue;
            SendTaskWhatsAppJob::dispatchSync($task->id, 'overdue_h1');
            $this->markSentToday($task->id, 'task_wa_overdue');
        }

        $this->info('Selesai sweep.');
        return self::SUCCESS;
    }

    protected function alreadySentToday(int $taskId, string $logName): bool
    {
        $today = Carbon::today();
        return DB::table('activity_log')
            ->where('subject_type', Task::class)
            ->where('subject_id', $taskId)
            ->where('log_name', $logName)
            ->whereDate('created_at', $today)
            ->exists();
    }

    protected function markSentToday(int $taskId, string $logName): void
    {
        activity($logName)->performedOn((new Task)->forceFill(['id'=>$taskId,'exists'=>true]))->withProperties([
            'date_key' => now()->toDateString()
        ])->log('dedup mark');
    }
}
