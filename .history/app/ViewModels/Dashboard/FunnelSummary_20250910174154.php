<?php

namespace App\ViewModels\Dashboard;

use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use Spatie\ViewModels\ViewModel;

class FunnelSummary extends ViewModel
{
    public int $trial;
    public int $active;
    public int $converted;
    public int $churn;
    public int $convertedPercentage;
    public int $churnPercentage;

    public function __construct()
    {
        $this->trial = Lead::where('status', 'trial')->count();
        $this->active = Lead::where('status', 'active')->count();
        $this->converted = Lead::where('status', 'converted')->count();
        $this->churn = Lead::where('status', 'churn')->count();

        $totalActiveFunnel = $this->active + $this->converted + $this->churn;

        if ($totalActiveFunnel > 0) {
            $this->convertedPercentage = round(($this->converted / $totalActiveFunnel) * 100);
            $this->churnPercentage = round(($this->churn / $totalActiveFunnel) * 100);
        } else {
            $this->convertedPercentage = 0;
            $this->churnPercentage = 0;
        }
    }

    public function getChartData(): array
    {
        $data = Lead::query()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as leads'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->pluck('leads', 'date');

        $dates = collect();
        for ($i = 29; $i >= 0; $i--) {
            $dates->push(now()->subDays($i)->format('Y-m-d'));
        }

        $values = $dates->map(function ($date) use ($data) {
            return $data->get($date, 0);
        });

        return [
            'labels' => $dates->map(fn ($date) => date('d M', strtotime($date))),
            'values' => $values,
        ];
    }
}

