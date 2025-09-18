<?php

namespace App\ViewModels\Reports;

use App\Models\Campaign;
use Spatie\ViewModels\ViewModel;

class CampaignReport extends ViewModel
{
    public $campaign;

    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    public function campaign(): Campaign
    {
        return $this->campaign;
    }

    /**
     * Calculate the Return on Investment (ROI) for the campaign.
     *
     * @return float
     */
    public function roi(): float
    {
        if ($this->campaign->budget <= 0) {
            return 0.0;
        }

        $profit = $this->campaign->revenue - $this->campaign->budget;

        return ($profit / $this->campaign->budget) * 100;
    }
}
