<?php

namespace Tests\Unit;

use App\Models\Lead;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the relationship between a subscription and its lead.
     *
     * @return void
     */
    public function test_a_subscription_belongs_to_a_lead(): void
    {
        $lead = Lead::factory()->create();
        $subscription = Subscription::factory()->create(['lead_id' => $lead->id]);

        $this->assertInstanceOf(Lead::class, $subscription->lead);
        $this->assertEquals($lead->id, $subscription->lead->id);
    }
}
