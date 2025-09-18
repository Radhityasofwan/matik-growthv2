<?php

namespace App\Notifications;

use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CampaignReminder extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Campaign $campaign)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // For now, we'll send an email and an in-app notification.
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject("Reminder: Campaign '{$this->campaign->name}' Ends Soon")
                    ->line("This is a reminder that the campaign '{$this->campaign->name}' is scheduled to end in 3 days on {$this->campaign->end_date->format('d M Y')}.")
                    ->action('View Campaign', url('/campaigns/' . $this->campaign->id))
                    ->line('Please review its performance and prepare the final report.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'campaign_id' => $this->campaign->id,
            'campaign_name' => $this->campaign->name,
            'message' => "The campaign '{$this->campaign->name}' is ending in 3 days.",
            'link' => url('/campaigns/' . $this->campaign->id),
        ];
    }
}
