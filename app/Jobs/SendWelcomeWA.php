<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Models\WahaSender;
use App\Models\WATemplate;
use App\Services\WahaService;
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
    public function __construct(public Lead $lead, public WATemplate $template)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(WahaService $wahaService): void
    {
        // 1. Find an active sender
        $sender = WahaSender::where('is_active', true)->first();

        if (!$sender) {
            Log::error("No active WAHA sender found for job SendWelcomeWA.");
            $this->fail("No active WAHA sender found.");
            return;
        }

        // 2. Prepare parameters
        $params = [];
        // The model normalizes variables to be without braces, e.g., ['name', 'product']
        $variables = $this->template->normalizedVariables();

        foreach ($variables as $variable) {
            // For now, we only map the 'name' variable from the lead.
            // This can be expanded later if templates need more lead properties.
            if ($variable === 'name') {
                $params[] = $this->lead->name;
            } else {
                // Add a placeholder for any other variables to avoid breaking the template.
                $params[] = '-';
                Log::warning("Unmapped variable '{$variable}' in template '{$this->template->name}' for SendWelcomeWA.");
            }
        }

        // 3. Send the template message via WahaService
        $success = $wahaService->sendTemplate(
            $this->lead->phone,
            $this->template->name,
            $params,
            $sender->session_name
        );

        if ($success) {
            Log::info("Successfully queued WA template '{$this->template->name}' to lead: {$this->lead->name}");
        } else {
            Log::error("Failed to queue WA template '{$this->template->name}' to lead: {$this->lead->name}");
            // Optionally, fail the job so it can be retried.
            $this->fail("Failed to send template via WahaService.");
        }
    }
}
