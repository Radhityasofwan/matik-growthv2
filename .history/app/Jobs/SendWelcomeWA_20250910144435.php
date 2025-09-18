<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Models\WATemplate;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWelcomeWA implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Lead $lead)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppClient $whatsAppClient): void
    {
        // Find the welcome template by name (ensure you have one in your seeder/db)
        $template = WATemplate::where('name', 'Welcome Message')->first();

        if (!$template) {
            Log::warning("WhatsApp welcome template not found for lead ID: {$this->lead->id}");
            return;
        }

        // Replace variables in the template body
        $body = str_replace('{{name}}', $this->lead->name, $template->body);

        // Send the message via the WhatsApp service
        $whatsAppClient->sendMessage($this->lead->phone, $body);

        Log::info("Dispatched Welcome WhatsApp message to lead: {$this->lead->name}");
    }
}
