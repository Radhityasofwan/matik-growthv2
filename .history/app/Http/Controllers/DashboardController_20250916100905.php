<?php

namespace App\Http\Controllers;

use App\ViewModels\Dashboard\FunnelSummary;
use App\ViewModels\Dashboard\TodayTasks;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;

// Models yang dipakai jika tabelnya tersedia
use Spatie\Activitylog\Models\Activity;
use App\Models\Lead;
use App\Models\WahaSender;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // ViewModel yang sudah ada di project
        $funnelSummary = new FunnelSummary();
        $todayTasks    = new TodayTasks($user);

        // Guard ketersediaan tabel agar stabil
        $hasLeads     = Schema::hasTable('leads');
        $hasActivity  = Schema::hasTable('activity_log');
        $hasSenders   = Schema::hasTable('waha_senders');

        // ==== STAT KARTU ATAS ====
        $totalLeads          = $hasLeads ? (int) Lead::count() : 0;
        $leadsThisWeek       = $hasLeads ? (int) Lead::where('created_at', '>=', now()->startOfWeek())->count() : 0;
        $leadsPreviousWeek   = $hasLeads ? (int) Lead::whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])->count() : 0;

        // Anggap outgoing WA dicatat di activity_log dengan nama log seperti berikut
        $outgoingLogs = ['wa_send','wa_chat','wa_outgoing','wa_broadcast'];
        $incomingLogs = ['wa_reply','wa_incoming'];

        $messagesSentLast7     = 0;
        $messagesSentPrev7     = 0;
        $replyRate             = 0.0;
        $replyRatePrevious     = 0.0;

        if ($hasActivity) {
            $messagesSentLast7 = (int) Activity::whereIn('log_name', $outgoingLogs)
                ->where('created_at', '>=', now()->subDays(7))
                ->count();

            $messagesSentPrev7 = (int) Activity::whereIn('log_name', $outgoingLogs)
                ->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])
                ->count();

            $repliesLast7 = (int) Activity::whereIn('log_name', $incomingLogs)
                ->where('created_at', '>=', now()->subDays(7))
                ->count();

            $repliesPrev7 = (int) Activity::whereIn('log_name', $incomingLogs)
                ->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])
                ->count();

            $replyRate         = $messagesSentLast7 > 0 ? round(($repliesLast7 / $messagesSentLast7) * 100, 1) : 0.0;
            $replyRatePrevious = $messagesSentPrev7 > 0 ? round(($repliesPrev7 / $messagesSentPrev7) * 100, 1) : 0.0;
        }

        $activeSenders = $hasSenders ? (int) WahaSender::where('status', 'connected')->count() : 0;
        $totalSenders  = $hasSenders ? (int) WahaSender::count() : 0;

        $stats = [
            'totalLeads'                => $totalLeads,
            'leadsThisWeek'             => $leadsThisWeek,
            'leadsPreviousWeek'         => $leadsPreviousWeek,
            'messagesSentLast7Days'     => $messagesSentLast7,
            'messagesSentPrevious7Days' => $messagesSentPrev7,
            'replyRate'                 => $replyRate,
            'replyRatePrevious'         => $replyRatePrevious,
            'activeSenders'             => $activeSenders,
            'totalSenders'              => $totalSenders,
        ];

        // ==== DATA CHART 30 HARI ====
        $days     = collect(range(0, 29))->map(fn($i) => Carbon::today()->subDays(29 - $i));
        $categories = $days->map->toDateString()->all();

        // Leads per hari
        $leadsPerDay = array_fill(0, 30, 0);
        if ($hasLeads) {
            $leadRows = Lead::selectRaw('DATE(created_at) as d, COUNT(*) as c')
                ->where('created_at', '>=', Carbon::today()->subDays(29)->startOfDay())
                ->groupBy('d')->orderBy('d')->pluck('c','d')->all();
            foreach ($days as $idx => $date) {
                $leadsPerDay[$idx] = (int) ($leadRows[$date->toDateString()] ?? 0);
            }
        }

        // Pesan terkirim per hari (activity_log)
        $messagesPerDay = array_fill(0, 30, 0);
        if ($hasActivity) {
            $msgRows = Activity::selectRaw('DATE(created_at) as d, COUNT(*) as c')
                ->whereIn('log_name', $outgoingLogs)
                ->where('created_at', '>=', Carbon::today()->subDays(29)->startOfDay())
                ->groupBy('d')->orderBy('d')->pluck('c','d')->all();
            foreach ($days as $idx => $date) {
                $messagesPerDay[$idx] = (int) ($msgRows[$date->toDateString()] ?? 0);
            }
        }

        $chartData = [
            'categories' => $categories,
            'leads'      => $leadsPerDay,
            'messages'   => $messagesPerDay,
        ];

        // ==== Aktivitas Terbaru ====
        $recentActivities = $hasActivity
            ? Activity::with('causer')->latest()->take(5)->get()
            : collect();

        return view('dashboard.index', compact(
            'funnelSummary',
            'todayTasks',
            'stats',
            'chartData',
            'recentActivities'
        ));
    }
}
