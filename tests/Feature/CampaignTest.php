<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /**
     * Test if the campaigns index page is accessible.
     *
     * @return void
     */
    public function test_campaigns_index_page_is_rendered_correctly(): void
    {
        $response = $this->get(route('campaigns.index'));

        $response->assertStatus(200);
        $response->assertSee('All Campaigns');
    }

    /**
     * Test if a new campaign can be created.
     *
     * @return void
     */
    public function test_a_user_can_create_a_new_campaign(): void
    {
        $campaignData = [
            'name' => 'Q4 Product Launch',
            'channel' => 'Google Ads',
            'budget' => 5000,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ];

        $response = $this->post(route('campaigns.store'), $campaignData);

        $response->assertRedirect(route('campaigns.index'));
        $this->assertDatabaseHas('campaigns', [
            'name' => 'Q4 Product Launch'
        ]);
    }

    /**
     * Test if an existing campaign can be updated.
     *
     * @return void
     */
    public function test_a_user_can_update_a_campaign(): void
    {
        $campaign = Campaign::factory()->create();

        $updatedData = [
            'name' => 'Updated Campaign Name',
            'channel' => 'Facebook Ads',
            'budget' => 7500,
            'start_date' => $campaign->start_date->format('Y-m-d'),
            'end_date' => $campaign->end_date->format('Y-m-d'),
        ];

        $response = $this->put(route('campaigns.update', $campaign), $updatedData);

        $response->assertRedirect(route('campaigns.index'));
        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'name' => 'Updated Campaign Name',
            'channel' => 'Facebook Ads',
        ]);
    }

    /**
     * Test if a campaign can be deleted.
     *
     * @return void
     */
    public function test_a_user_can_delete_a_campaign(): void
    {
        $campaign = Campaign::factory()->create();

        $response = $this->delete(route('campaigns.destroy', $campaign));

        $response->assertRedirect(route('campaigns.index'));
        $this->assertDatabaseMissing('campaigns', [
            'id' => $campaign->id
        ]);
    }
}
