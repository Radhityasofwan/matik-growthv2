<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Lead::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(['trial', 'active', 'converted', 'churn']);

        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'status' => $status,
            'score' => $this->faker->numberBetween(0, 100),
            // Set a trial end date only if the status is 'trial'
            'trial_ends_at' => $status === 'trial' ? now()->addDays($this->faker->numberBetween(1, 14)) : null,
            'owner_id' => User::inRandomOrder()->first()->id ?? \App\Models\User::factory(),
        ];
    }
}

