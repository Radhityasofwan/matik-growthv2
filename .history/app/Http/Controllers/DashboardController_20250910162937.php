<?php

namespace App\Http\Controllers;

use App\ViewModels\Dashboard\FunnelSummary;
use App\ViewModels\Dashboard\TodayTasks;

class DashboardController extends Controller
{
    public function index()
    {
        // --- FIX: Membuat instance ViewModel dan mengirimkannya ke view ---
        $funnelSummary = new FunnelSummary();
        $todayTasks = new TodayTasks();

        return view('dashboard.index', [
            'funnelSummary' => $funnelSummary,
            'todayTasks' => $todayTasks,
        ]);
    }
}

