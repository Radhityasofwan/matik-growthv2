<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            LeadSeeder::class,
            WATemplateSeeder::class,
            TaskSeeder::class,
            CampaignSeeder::class,
            SubscriptionSeeder::class, // <-- Add this seeder
        ]);
    }
}

