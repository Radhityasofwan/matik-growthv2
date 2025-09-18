<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use App\Notifications\WeeklyDigest;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWeeklyDigest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Generating weekly digest.");

        $startDate = Carbon::now()->subWeek();
        $endDate = Carbon::now();

        // 1. Gather Metrics
        $newLeadsCount = Lead::whereBetween('created_at', [$startDate, $endDate])->count();
        $convertedLeadsCount = Lead::where('status', 'converted')
            ->whereBetween('updated_at', [$startDate, $endDate]) // Assuming status change updates this timestamp
            ->count();
        $completedTasksCount = Task::where('status', 'done')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();

        // 2. Find Users and Send Notification
        $users = User::where('is_active', true)->get();

        if ($users->isEmpty()) {
            Log::warning('No active users found to send weekly digest.');
            return;
        }

        foreach ($users as $user) {
            Log::info("Sending weekly digest to {$user->email}.");
            $user->notify(new WeeklyDigest($newLeadsCount, $convertedLeadsCount, $completedTasksCount));
        }

        Log::info("Finished sending weekly digests.");
    }
}
