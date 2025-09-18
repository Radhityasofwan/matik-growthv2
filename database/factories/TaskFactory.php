<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task> */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $userId = optional(User::inRandomOrder()->first())->id;

        return [
            'title'       => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'status'      => $this->faker->randomElement(['open', 'in_progress', 'review', 'done']),
            'priority'    => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'due_date'    => now()->addDays($this->faker->numberBetween(1, 30)),
            'assignee_id' => $userId,
            'campaign_id' => optional(Campaign::inRandomOrder()->first())->id,
        ];
    }
}
