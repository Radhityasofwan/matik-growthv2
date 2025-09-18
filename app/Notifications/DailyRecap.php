<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailyRecap extends Notification implements ShouldQueue
{
    use Queueable;

    public $newLeadsCount;
    public $tasksDueTomorrow;

    public function __construct(int $newLeadsCount, int $tasksDueTomorrow)
    {
        $this->newLeadsCount = $newLeadsCount;
        $this->tasksDueTomorrow = $tasksDueTomorrow;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Your Daily Recap from Matik Hub')
                    ->line('Here is your summary for today:')
                    ->line("- New Leads Today: {$this->newLeadsCount}")
                    ->line("- Your Tasks Due Tomorrow: {$this->tasksDueTomorrow}")
                    ->action('Go to Dashboard', url('/dashboard'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Daily recap: {$this->newLeadsCount} new leads, {$this->tasksDueTomorrow} tasks due tomorrow."
        ];
    }
}
