<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Lead;
use App\Models\User;

class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first(); // Assign to first user

        Lead::create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '081234567890',
            'company' => 'Example Corp',
            'status' => 'trial',
            'score' => 50,
            'user_id' => $user->id,
        ]);

        Lead::create([
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'phone' => '081209876543',
            'company' => 'Test Inc.',
            'status' => 'converted',
            'score' => 90,
            'user_id' => $user->id,
        ]);
    }
}
