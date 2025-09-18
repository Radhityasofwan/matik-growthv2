<?php

namespace App\Jobs;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScoreLead implements ShouldQueue
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
    public function handle(): void
    {
        $score = 0;
        // Simple scoring logic based on status
        switch ($this->lead->status) {
            case 'trial':
                $score += 5;
                break;
            case 'active':
                $score += 15;
                break;
            case 'converted':
                $score += 50;
                break;
            case 'churn':
                $score = 0; // Reset score for churned leads
                break;
        }

        // Add points for having a phone number
        if (!empty($this->lead->phone)) {
            $score += 5;
        }

        $this->lead->update(['score' => $score]);

        Log::info("Scored lead {$this->lead->name} (ID: {$this->lead->id}) with a score of {$score}.");
    }
}
