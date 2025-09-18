<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyDigest extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param int $newLeadsCount
     * @param int $convertedLeadsCount
     * @param int $completedTasksCount
     */
    public function __construct(
        public int $newLeadsCount,
        public int $convertedLeadsCount,
        public int $completedTasksCount
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Your Matik Growth Hub Weekly Digest')
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line('Here is your performance summary for the past week:')
                    ->line("- New Leads: **{$this->newLeadsCount}**")
                    ->line("- Converted Leads: **{$this->convertedLeadsCount}**")
                    ->line("- Completed Tasks: **{$this->completedTasksCount}**")
                    ->action('View Dashboard', url('/dashboard'))
                    ->line('Keep up the great work!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            // No in-app notification for this digest for now.
        ];
    }
}
