<?php

namespace App\ViewModels\Dashboard;

use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FunnelSummary
{
    public $trial;
    public $active;
    public $converted;
    public $churn;
    public $total;
    public $converted_rate;
    public $churn_rate;

    public function __construct()
    {
        $this->trial = Lead::where('status', 'trial')->count();
        $this->active = Lead::where('status', 'active')->count();
        $this->converted = Lead::where('status', 'converted')->count();
        $this->churn = Lead::where('status', 'churn')->count();
        $this->total = $this->trial + $this->active + $this->converted + $this->churn;

        $this->converted_rate = $this->total > 0 ? round(($this->converted / $this->total) * 100, 1) : 0;
        $this->churn_rate = $this->total > 0 ? round(($this->churn / $this->total) * 100, 1) : 0;
    }

    /**
     * Mengambil data untuk grafik pertumbuhan lead selama 30 hari terakhir.
     *
     * @return array
     */
    public function getChartData(): array
    {
        $leadsData = Lead::query()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->pluck('count', 'date');

        $labels = [];
        $values = [];
        // Mengisi data untuk 30 hari terakhir, termasuk hari tanpa lead baru
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $labels[] = Carbon::parse($date)->format('M d');
            $values[] = $leadsData[$date] ?? 0;
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }
}

