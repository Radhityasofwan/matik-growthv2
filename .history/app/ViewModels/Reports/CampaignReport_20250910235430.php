<?php

namespace App\ViewModels\Reports;

use App\Models\Campaign;
use Illuminate\Support\Carbon;
use Spatie\ViewModels\ViewModel;

class CampaignReport extends ViewModel
{
    public Campaign $campaign;

    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    public function campaign(): Campaign
    {
        return $this->campaign;
    }

    /**
     * Menghitung durasi kampanye dalam hari.
     */
    public function periodInDays(): int
    {
        return $this->campaign->start_date->diffInDays($this->campaign->end_date) + 1;
    }

    /**
     * Menghitung budget harian rata-rata.
     */
    public function dailyBudget(): float
    {
        $days = $this->periodInDays();
        return $days > 0 ? $this->campaign->budget / $days : 0;
    }

    /**
     * Menghitung Click-Through Rate (CTR) dari iklan.
     * (Link Clicks / Impressions) * 100
     */
    public function ctr(): float
    {
        if ($this->campaign->impressions <= 0) {
            return 0.0;
        }
        return ($this->campaign->link_clicks / $this->campaign->impressions) * 100;
    }

    /**
     * Menghitung Click-Through Rate (CTR) dari Landing Page.
     * (LP Link Clicks / LP Impressions) * 100
     */
    public function lpCtr(): float
    {
        if ($this->campaign->lp_impressions <= 0) {
            return 0.0;
        }
        return ($this->campaign->lp_link_clicks / $this->campaign->lp_impressions) * 100;
    }

    /**
     * Menghitung Return on Investment (ROI).
     * ((Revenue - Total Spent) / Total Spent) * 100
     */
    public function roi(): float
    {
        if ($this->campaign->total_spent <= 0) {
            return 0.0;
        }
        $profit = $this->campaign->revenue - $this->campaign->total_spent;
        return ($profit / $this->campaign->total_spent) * 100;
    }

    /**
     * Menghitung Return on Ad Spend (ROAS).
     * Revenue / Total Spent
     */
    public function roas(): float
    {
        if ($this->campaign->total_spent <= 0) {
            return 0.0;
        }
        return $this->campaign->revenue / $this->campaign->total_spent;
    }

    /**
     * Menghitung Cost Per Click (CPC).
     * Total Spent / Link Clicks
     */
    public function cpc(): float
    {
        if ($this->campaign->link_clicks <= 0) {
            return 0.0;
        }
        return $this->campaign->total_spent / $this->campaign->link_clicks;
    }

    /**
     * Menghitung Cost Per Result/Lead (CPL/CPR).
     * Total Spent / Results
     */
    public function cpr(): float
    {
        if ($this->campaign->results <= 0) {
            return 0.0;
        }
        return $this->campaign->total_spent / $this->campaign->results;
    }
}
