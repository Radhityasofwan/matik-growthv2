<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateOnboardingTasks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Campaign $campaign)
    {
        // Renamed from 'CreateOnboardingTasks' to represent creating tasks for a new campaign
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Generating default tasks for campaign: {$this->campaign->name}");

        $adminUser = User::first(); // Assign tasks to the first user for simplicity

        $tasks = [
            [
                'title' => 'Phase 1: Siapkan Materi Konten',
                'description' => 'Buat draf tulisan, visual, dan video untuk kampanye ' . $this->campaign->name,
                'priority' => 'high',
                'due_date' => now()->addDays(3),
            ],
            [
                'title' => 'Phase 2: Distribusi & Promosi',
                'description' => 'Jadwalkan posting di media sosial dan siapkan budget iklan.',
                'priority' => 'high',
                'due_date' => now()->addDays(7),
            ],
            [
                'title' => 'Phase 3: Analisis & Laporan',
                'description' => 'Kumpulkan data performa dan buat laporan awal.',
                'priority' => 'medium',
                'due_date' => now()->addDays(14),
            ],
        ];

        foreach ($tasks as $taskData) {
            $this->campaign->tasks()->create([
                'title' => $taskData['title'],
                'description' => $taskData['description'],
                'priority' => $taskData['priority'],
                'due_date' => $taskData['due_date'],
                'assignee_id' => $adminUser->id,
                'status' => 'open',
            ]);
        }

        Log::info("Successfully generated tasks for campaign ID: {$this->campaign->id}");
    }
}
