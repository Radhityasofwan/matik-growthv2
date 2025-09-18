<?php

namespace Database\Seeders;

use App\Models\Subscription;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have some 'converted' leads to associate subscriptions with.
        if (\App\Models\Lead::where('status', 'converted')->count() < 5) {
            \App\Models\Lead::factory()->count(5)->create(['status' => 'converted']);
        }

        Subscription::factory()->count(15)->create();
    }
}
