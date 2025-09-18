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

class SendReminderWA implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Lead $lead, public int $daysRemaining)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppClient $whatsAppClient): void
    {
        $template = WATemplate::where('name', 'Trial Reminder')->first();

        if (!$template) {
            Log::warning("WhatsApp trial reminder template not found for lead ID: {$this->lead->id}");
            return;
        }

        // Replace variables
        $body = str_replace('{{name}}', $this->lead->name, $template->body);
        $body = str_replace('{{expiry_date}}', $this->lead->trial_ends_at->format('d M Y'), $body);
        $body = str_replace('{{days_remaining}}', $this->daysRemaining, $body);


        $whatsAppClient->sendMessage($this->lead->phone, $body);

        Log::info("Dispatched Trial Reminder WhatsApp to lead: {$this->lead->name} ({$this->daysRemaining} days remaining).");
    }
}
