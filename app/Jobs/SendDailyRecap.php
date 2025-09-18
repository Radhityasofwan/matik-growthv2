<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Lead;
use App\Models\Task;
use App\Notifications\DailyRecap;

class SendDailyRecap implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $users = User::where('is_active', true)->get();

        foreach ($users as $user) {
            $newLeadsCount = Lead::whereDate('created_at', today())->count();
            $tasksDueTomorrow = Task::where('assignee_id', $user->id)
                                    ->whereDate('due_date', now()->addDay()->toDateString())
                                    ->count();

            // Hanya kirim jika ada data yang relevan
            if ($newLeadsCount > 0 || $tasksDueTomorrow > 0) {
                $user->notify(new DailyRecap($newLeadsCount, $tasksDueTomorrow));
            }
        }
    }
}
