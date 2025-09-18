<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'status' => $this->faker->randomElement(['trial', 'active', 'converted', 'churn']),
            'score' => $this->faker->numberBetween(10, 100),
            'trial_ends_at' => now()->addDays($this->faker->numberBetween(1, 30)),
            // --- FIX: Mengganti 'owner_id' menjadi 'user_id' ---
            'user_id' => User::inRandomOrder()->first()->id ?? User::factory(),
        ];
    }
}

