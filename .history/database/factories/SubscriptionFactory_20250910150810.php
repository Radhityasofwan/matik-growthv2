<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Subscription::class;


    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 year', 'now');
        $cycle = $this->faker->randomElement(['monthly', 'yearly']);

        return [
            'lead_id' => Lead::where('status', 'converted')->inRandomOrder()->first()?->id ?? Lead::factory(['status' => 'converted']),
            'plan' => $this->faker->randomElement(['basic', 'premium', 'enterprise']),
            'status' => $this->faker->randomElement(['active', 'paused', 'cancelled']),
            'amount' => $this->faker->randomElement([25, 75, 200]),
            'cycle' => $cycle,
            'auto_renew' => $this->faker->boolean(70),
            'start_date' => $startDate,
            'end_date' => $cycle === 'monthly' ? (clone $startDate)->modify('+1 month') : (clone $startDate)->modify('+1 year'),
        ];
    }
}
