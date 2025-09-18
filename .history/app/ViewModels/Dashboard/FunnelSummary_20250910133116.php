<?php

namespace App\ViewModels\Dashboard;

use App\Models\Lead;

class FunnelSummary
{
    public int $totalLeads;
    public int $trialCount;
    public int $activeCount;
    public int $convertedCount;
    public int $churnCount;

    public function __construct()
    {
        $this->totalLeads = Lead::count();
        $this->trialCount = Lead::where('status', 'trial')->count();
        $this->activeCount = Lead::where('status', 'active')->count();
        $this->convertedCount = Lead::where('status', 'converted')->count();
        $this->churnCount = Lead::where('status', 'churn')->count();
    }

    public function trialPercentage(): float
    {
        return $this->totalLeads > 0 ? round(($this->trialCount / $this->totalLeads) * 100, 2) : 0;
    }

    public function conversionRate(): float
    {
        $totalOpportunities = $this->trialCount + $this->activeCount + $this->convertedCount + $this->churnCount;
        return $totalOpportunities > 0 ? round(($this->convertedCount / $totalOpportunities) * 100, 2) : 0;
    }
}
