<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Task;
use App\Models\User;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        if ($user) {
            Task::create([
                'title' => 'Design new landing page mockup',
                'description' => 'Create a modern and responsive design in Figma.',
                'status' => 'in_progress',
                'priority' => 'high',
                'due_date' => now()->addDays(3),
                'assignee_id' => $user->id,
                'creator_id' => $user->id,
            ]);

            Task::create([
                'title' => 'Develop user authentication feature',
                'description' => 'Implement login, register, and profile update.',
                'status' => 'open',
                'priority' => 'urgent',
                'due_date' => now()->addDays(5),
                'assignee_id' => $user->id,
                'creator_id' => $user->id,
            ]);

            Task::create([
                'title' => 'Write weekly blog post',
                'description' => 'Topic: "5 Ways to Improve Sales with Automation".',
                'status' => 'done',
                'priority' => 'medium',
                'due_date' => now()->subDay(),
                'assignee_id' => $user->id,
                'creator_id' => $user->id,
            ]);
        }
    }
}
