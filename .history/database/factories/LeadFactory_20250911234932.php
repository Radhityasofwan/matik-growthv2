<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'        => $this->faker->name(),
            'email'       => $this->faker->unique()->safeEmail(),
            'phone'       => $this->faker->phoneNumber(),
            'store_name'  => strtoupper($this->faker->company()),
            'status'      => $this->faker->randomElement(['trial', 'active', 'converted', 'churn']),
            'score'       => $this->faker->numberBetween(10, 100),
            'trial_ends_at' => now()->addDays($this->faker->numberBetween(7, 30)),
            'owner_id'    => User::inRandomOrder()->first()->id ?? User::factory(),
        ];
    }
}
