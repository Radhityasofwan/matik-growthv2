<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Models\WATemplate;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendRenewalWA implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Subscription $subscription)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppClient $whatsAppClient): void
    {
        Log::info("Attempting to send renewal reminder for subscription ID: {$this->subscription->id}");

        $template = WATemplate::where('name', 'Subscription Renewal')->first();
        if (!$template) {
            Log::error("WhatsApp template 'Subscription Renewal' not found.");
            return;
        }

        $lead = $this->subscription->lead;
        if (!$lead || !$lead->phone) {
            Log::warning("Lead or phone number not found for subscription ID: {$this->subscription->id}");
            return;
        }

        $message = str_replace(
            ['{{name}}', '{{plan}}', '{{end_date}}'],
            [$lead->name, $this->subscription->plan, $this->subscription->end_date->format('d M Y')],
            $template->body
        );

        try {
            $whatsAppClient->sendMessage($lead->phone, $message);
            Log::info("Successfully sent renewal reminder to {$lead->phone} for subscription ID: {$this->subscription->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send renewal reminder for subscription ID: {$this->subscription->id}. Error: " . $e->getMessage());
        }
    }
}
