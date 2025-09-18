<?php

namespace Tests\Unit;

use App\Models\Activity;
use App\Models\Lead;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the relationship between a lead and its assigned user.
     *
     * @return void
     */
    public function test_a_lead_belongs_to_a_user(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $lead->user);
        $this->assertEquals($user->id, $lead->user->id);
    }

    /**
     * Test the relationship between a lead and its subscription.
     *
     * @return void
     */
    public function test_a_lead_can_have_one_subscription(): void
    {
        $lead = Lead::factory()->create();
        Subscription::factory()->create(['lead_id' => $lead->id]);

        $this->assertInstanceOf(Subscription::class, $lead->subscription);
    }

    /**
     * Test the polymorphic relationship between a lead and its activities.
     *
     * @return void
     */
    public function test_a_lead_can_have_many_activities(): void
    {
        $lead = Lead::factory()->create();
        Activity::factory()->create([
            'subject_id' => $lead->id,
            'subject_type' => Lead::class,
        ]);

        $this->assertInstanceOf(Activity::class, $lead->activities->first());
        $this->assertCount(1, $lead->activities);
    }
}
