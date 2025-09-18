<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Campaign;
use App\Models\User;

class CampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::first();

        Campaign::factory()->create([
            'name' => 'Q4 Product Launch',
            'description' => 'End of year product launch campaign targeting new markets.',
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(40),
            'budget' => 5000.00,
            'channel' => 'Ads',
            'status' => 'planning',
            'user_id' => $admin->id,
        ]);

        Campaign::factory()->create([
            'name' => 'Summer Sale WhatsApp Blast',
            'description' => 'Promotional messages for existing customers.',
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(5),
            'budget' => 1500.00,
            'revenue' => 3200.00,
            'channel' => 'WA',
            'status' => 'active',
            'user_id' => $admin->id,
        ]);

        Campaign::factory()->create([
            'name' => 'SEO Content Push - H2',
            'description' => 'Content marketing initiative to boost organic traffic.',
            'start_date' => now()->subDays(60),
            'end_date' => now()->subDays(10),
            'budget' => 3000.00,
            'revenue' => 4500.00,
            'channel' => 'Content',
            'status' => 'completed',
            'user_id' => $admin->id,
        ]);
    }
}
