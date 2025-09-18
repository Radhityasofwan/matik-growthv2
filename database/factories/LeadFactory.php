<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'         => $this->faker->name(),
            'email'        => $this->faker->unique()->safeEmail(),
            'phone'        => $this->faker->numerify('08##########'),
            'store_name'   => strtoupper($this->faker->company()),
            'status'       => $this->faker->randomElement(['trial', 'active', 'converted', 'churn']),
            'score'        => $this->faker->numberBetween(0, 100),
            'trial_ends_at'=> now()->addDays($this->faker->numberBetween(7, 30)),
            'owner_id'     => User::query()->inRandomOrder()->value('id') ?? User::factory(),
        ];
    }
}
