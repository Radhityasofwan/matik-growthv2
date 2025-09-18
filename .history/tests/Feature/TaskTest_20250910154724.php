<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
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
     * Test if the task board page is accessible.
     *
     * @return void
     */
    public function test_task_board_is_rendered_correctly(): void
    {
        $response = $this->get(route('tasks.index'));

        $response->assertStatus(200);
        $response->assertSee('Task Board');
    }

    /**
     * Test if a new task can be created.
     *
     * @return void
     */
    public function test_a_user_can_create_a_new_task(): void
    {
        $taskData = [
            'title' => 'Create a new design prototype',
            'description' => 'Use Figma for the design.',
            'due_date' => now()->addWeek()->format('Y-m-d'),
            'priority' => 'high',
            'user_id' => $this->user->id,
        ];

        $response = $this->post(route('tasks.store'), $taskData);

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas('tasks', [
            'title' => 'Create a new design prototype'
        ]);
    }

    /**
     * Test if an existing task can be updated.
     *
     * @return void
     */
    public function test_a_user_can_update_a_task(): void
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $updatedData = [
            'title' => 'Updated Task Title',
            'description' => $task->description,
            'due_date' => $task->due_date->format('Y-m-d'),
            'priority' => 'urgent',
            'user_id' => $this->user->id,
        ];

        $response = $this->put(route('tasks.update', $task), $updatedData);

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Task Title',
            'priority' => 'urgent',
        ]);
    }

    /**
     * Test if a task status can be updated (simulating drag and drop).
     *
     * @return void
     */
    public function test_a_user_can_update_task_status(): void
    {
        $task = Task::factory()->create(['status' => 'open']);

        $response = $this->put(route('tasks.updateStatus', $task), [
            'status' => 'in_progress'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'in_progress',
        ]);
    }
}
