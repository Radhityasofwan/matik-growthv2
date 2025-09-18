<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ViewModels\Dashboard\FunnelSummary;
use App\ViewModels\Dashboard\TodayTasks;
use App\Services\Reports\ReportGenerator;
use App\Models\Lead;
use App\Exports\SalesFunnelExport;

class DashboardController extends Controller
{
    public function index()
    {
        $funnelSummary = new FunnelSummary();
        $todayTasks = new TodayTasks(auth()->user());

        // Data dummy untuk chart
        $chartData = [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'data' => [
                Lead::whereMonth('created_at', 1)->count(),
                Lead::whereMonth('created_at', 2)->count(),
                Lead::whereMonth('created_at', 3)->count(),
                Lead::whereMonth('created_at', 4)->count(),
                Lead::whereMonth('created_at', 5)->count(),
                Lead::whereMonth('created_at', 6)->count(),
            ],
        ];

        return view('dashboard.index', [
            'funnel' => $funnelSummary,
            'todayTasks' => $todayTasks->tasks,
            'chartData' => $chartData,
        ]);
    }

    public function downloadSalesFunnelReport(Request $request, ReportGenerator $reportGenerator, $format)
    {
        $leads = Lead::with('owner')->get();
        $data = [
            'leads' => $leads,
            'stats' => new FunnelSummary(),
            'date' => now()->format('d F Y'),
        ];

        $filename = 'sales-funnel-report-' . now()->format('Y-m-d') . '.' . $format;

        if ($format === 'pdf') {
            return $reportGenerator->generatePdf('reports.sales_funnel_pdf', $data, $filename);
        }

        if ($format === 'xlsx') {
            return $reportGenerator->generateExcel(new SalesFunnelExport($data), $filename);
        }

        return redirect()->back()->with('error', 'Invalid report format.');
    }
}

