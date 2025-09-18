<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a user and authenticate
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /**
     * Test if the leads index page is accessible.
     *
     * @return void
     */
    public function test_leads_index_page_is_rendered_correctly(): void
    {
        $response = $this->get(route('leads.index'));

        $response->assertStatus(200);
        $response->assertSee('All Leads');
    }

    /**
     * Test if a new lead can be created.
     *
     * @return void
     */
    public function test_a_user_can_create_a_new_lead(): void
    {
        $leadData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '1234567890',
            'status' => 'trial',
        ];

        $response = $this->post(route('leads.store'), $leadData);

        $response->assertRedirect(route('leads.index'));
        $this->assertDatabaseHas('leads', [
            'email' => 'john.doe@example.com'
        ]);
    }

    /**
     * Test if an existing lead can be updated.
     *
     * @return void
     */
    public function test_a_user_can_update_a_lead(): void
    {
        $lead = Lead::factory()->create();

        $updatedData = [
            'name' => 'Jane Doe Updated',
            'email' => $lead->email, // email is usually unique
            'phone' => '0987654321',
            'status' => 'converted',
        ];

        $response = $this->put(route('leads.update', $lead), $updatedData);

        $response->assertRedirect(route('leads.index'));
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'name' => 'Jane Doe Updated',
            'status' => 'converted',
        ]);
    }

    /**
     * Test if a lead can be deleted.
     *
     * @return void
     */
    public function test_a_user_can_delete_a_lead(): void
    {
        $lead = Lead::factory()->create();

        $response = $this->delete(route('leads.destroy', $lead));

        $response->assertRedirect(route('leads.index'));
        $this->assertDatabaseMissing('leads', [
            'id' => $lead->id
        ]);
    }
}
