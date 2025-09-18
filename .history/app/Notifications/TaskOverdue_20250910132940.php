<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Task;

class TaskOverdue extends Notification implements ShouldQueue
{
    use Queueable;

    public $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database']; // Contoh: kirim via email dan simpan di DB
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('Task Overdue: ' . $this->task->title)
                    ->line('The following task has passed its due date.')
                    ->action('View Task', url('/tasks'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'message' => 'Task is overdue!',
        ];
    }
}
