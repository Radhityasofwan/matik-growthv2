<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $budget = $this->faker->numberBetween(500, 5000);
        $revenue = $budget * $this->faker->randomFloat(2, 0.8, 3); // ROI between 80% and 300%

        return [
            'name' => 'Campaign: ' . $this->faker->bs(),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['planning', 'active', 'completed', 'paused']),
            'start_date' => $this->faker->dateTimeBetween('-1 month', '+1 month'),
            'end_date' => $this->faker->dateTimeBetween('+2 months', '+4 months'),
            'budget' => $budget,
            'revenue' => $revenue,
            'user_id' => User::inRandomOrder()->first()->id ?? User::factory(),
        ];
    }
}
