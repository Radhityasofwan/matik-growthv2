<?php

namespace App\Console\Commands;

use App\Jobs\SendWelcomeWA;
use App\Models\AutomationRule;
use App\Models\Lead;
use App\Models\WATemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessAutomations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-automations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all active automation rules for leads, tasks, etc.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting automation processing...');
        Log::info('Automation Engine: Starting processing.');

        $activeRules = AutomationRule::where('is_active', true)->get();

        if ($activeRules->isEmpty()) {
            $this->info('No active automation rules to process.');
            return 0;
        }

        foreach ($activeRules as $rule) {
            $this->line("Processing rule: {$rule->name}");

            switch ($rule->trigger_event) {
                case 'lead_trial_ending':
                    $this->processLeadTrialEndingRule($rule);
                    break;
                // Add other trigger events here in the future
                default:
                    Log::warning("Automation Engine: Unknown trigger event '{$rule->trigger_event}' for rule ID {$rule->id}.");
                    break;
            }
        }

        Log::info('Automation Engine: Finished processing.');
        $this->info('Automation processing finished.');
        return 0;
    }

    private function processLeadTrialEndingRule(AutomationRule $rule): void
    {
        $daysBefore = $rule->trigger_config['days_before'] ?? null;

        if (!is_int($daysBefore)) {
            Log::error("Automation Engine: Invalid or missing 'days_before' in trigger_config for rule ID {$rule->id}.");
            return;
        }

        $leads = Lead::where('status', 'trial')
                     ->whereDate('trial_ends_at', now()->addDays($daysBefore)->toDateString())
                     ->get();

        $this->info("Found {" . $leads->count() . "} leads for rule '{$rule->name}'.");

        foreach ($leads as $lead) {
            $this->executeAction($rule, $lead);
        }
    }

    private function executeAction(AutomationRule $rule, Lead $lead): void
    {
        switch ($rule->action_type) {
            case 'send_waha_template':
                $templateId = $rule->action_config['template_id'] ?? null;

                if (!$templateId) {
                    Log::error("Automation Engine: Missing 'template_id' in action_config for rule ID {$rule->id}.");
                    return;
                }

                $template = WATemplate::find($templateId);

                if (!$template) {
                    Log::error("Automation Engine: WATemplate with ID {$templateId} not found for rule ID {$rule->id}.");
                    return;
                }

                SendWelcomeWA::dispatch($lead, $template);
                Log::info("Automation Engine: Dispatched action for rule ID {$rule->id} to lead ID {$lead->id} with template ID {$template->id}.");
                break;
            // Add other action types here in the future
            default:
                Log::warning("Automation Engine: Unknown action type '{$rule->action_type}' for rule ID {$rule->id}.");
                break;
        }
    }
}