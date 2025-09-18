<?php

namespace Database\Seeders;

use App\Models\Lead;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing leads to avoid duplicates
        Lead::query()->delete();

        // Create 50 new leads using the factory
        Lead::factory()->count(50)->create();
    }
}

