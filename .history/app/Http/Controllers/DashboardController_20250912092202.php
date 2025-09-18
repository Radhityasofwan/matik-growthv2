<?php

namespace App\Http\Controllers;

use App\ViewModels\Dashboard\FunnelSummary;
use App\ViewModels\Dashboard\TodayTasks;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // --- FIX: Mengambil user yang login dan memberikannya ke ViewModel ---
        $user = Auth::user();
        $funnelSummary = new FunnelSummary();
        $todayTasks = new TodayTasks($user);

        return view('dashboard.index', [
            'funnelSummary' => $funnelSummary,
            'todayTasks' => $todayTasks,
        ]);
    }
}

