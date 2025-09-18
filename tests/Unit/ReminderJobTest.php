<?php

namespace Tests\Unit;

use App\Jobs\DetectTrialExpiring;
use App\Jobs\SendReminderWA;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReminderJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test if the DetectTrialExpiring job correctly dispatches the SendReminderWA job.
     *
     * @return void
     */
    public function test_detect_trial_expiring_job_dispatches_reminder_job(): void
    {
        // 1. Setup
        Queue::fake(); // Mengintersep semua job yang akan dikirim ke antrian

        // Buat user dan lead yang masa trial-nya akan habis dalam 3 hari
        $user = User::factory()->create();
        $expiringLead = Lead::factory()->create([
            'user_id' => $user->id,
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(3),
        ]);

        // Buat lead lain yang tidak seharusnya menerima pengingat
        Lead::factory()->create([
            'user_id' => $user->id,
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(10), // Trial masih panjang
        ]);

        Lead::factory()->create([
            'user_id' => $user->id,
            'status' => 'active', // Bukan trial
            'trial_ends_at' => now()->addDays(3),
        ]);

        // 2. Action
        // Jalankan job utama yang ingin kita tes
        (new DetectTrialExpiring())->handle();

        // 3. Assertion
        // Pastikan job SendReminderWA HANYA dikirim untuk lead yang benar
        Queue::assertPushed(SendReminderWA::class, function ($job) use ($expiringLead) {
            return $job->lead->id === $expiringLead->id;
        });

        // Pastikan job SendReminderWA HANYA dikirim sekali
        Queue::assertPushed(SendReminderWA::class, 1);
    }
}
