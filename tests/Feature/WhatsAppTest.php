<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WATemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppTest extends TestCase
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
     * Test if the WhatsApp templates index page is accessible.
     *
     * @return void
     */
    public function test_wa_templates_index_page_is_rendered_correctly(): void
    {
        $response = $this->get(route('whatsapp.templates.index'));

        $response->assertStatus(200);
        $response->assertSee('WhatsApp Templates');
    }

    /**
     * Test if a new WA template can be created.
     *
     * @return void
     */
    public function test_a_user_can_create_a_new_wa_template(): void
    {
        $templateData = [
            'name' => 'New Customer Welcome',
            'body' => 'Hello {{name}}, welcome to our service!',
        ];

        $response = $this->post(route('whatsapp.templates.store'), $templateData);

        $response->assertRedirect(route('whatsapp.templates.index'));
        $this->assertDatabaseHas('wa_templates', [
            'name' => 'New Customer Welcome',
            'variables' => json_encode(['name']),
        ]);
    }

    /**
     * Test if an existing WA template can be updated.
     *
     * @return void
     */
    public function test_a_user_can_update_a_wa_template(): void
    {
        $template = WATemplate::factory()->create();

        $updatedData = [
            'name' => 'Updated Template Name',
            'body' => 'Hi {{customer_name}}, your order {{order_id}} has been shipped.',
        ];

        $response = $this->put(route('whatsapp.templates.update', $template), $updatedData);

        $response->assertRedirect(route('whatsapp.templates.index'));
        $this->assertDatabaseHas('wa_templates', [
            'id' => $template->id,
            'name' => 'Updated Template Name',
            'variables' => json_encode(['customer_name', 'order_id']),
        ]);
    }

    /**
     * Test if a WA template can be deleted.
     *
     * @return void
     */
    public function test_a_user_can_delete_a_wa_template(): void
    {
        $template = WATemplate::factory()->create();

        $response = $this->delete(route('whatsapp.templates.destroy', $template));

        $response->assertRedirect(route('whatsapp.templates.index'));
        $this->assertDatabaseMissing('wa_templates', [
            'id' => $template->id
        ]);
    }
}
