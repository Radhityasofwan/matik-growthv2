<?php

namespace App\Http\Controllers;

use App\ViewModels\Dashboard\FunnelSummary;
use App\ViewModels\Dashboard\TodayTasks;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

// Models
use Spatie\Activitylog\Models\Activity;
use App\Models\Lead;
use App\Models\WahaSender;

// Service WAHA
use App\Services\WahaService;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // ViewModel existing
        $funnelSummary = new FunnelSummary();
        $todayTasks    = new TodayTasks($user);

        // Guard ketersediaan tabel
        $hasLeads    = Schema::hasTable('leads');
        $hasActivity = Schema::hasTable('activity_log');
        $hasSenders  = Schema::hasTable('waha_senders');

        /* =================== STAT KARTU ATAS =================== */
        $totalLeads        = $hasLeads ? (int) Lead::count() : 0;
        $leadsThisWeek     = $hasLeads ? (int) Lead::where('created_at', '>=', now()->startOfWeek())->count() : 0;
        $leadsPreviousWeek = $hasLeads ? (int) Lead::whereBetween(
            'created_at',
            [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()]
        )->count() : 0;

        // Anggap outgoing/incoming WA dicatat di activity_log
        $outgoingLogs = ['wa_send','wa_chat','wa_outgoing','wa_broadcast'];
        $incomingLogs = ['wa_reply','wa_incoming'];

        $messagesSentLast7 = 0;
        $messagesSentPrev7 = 0;
        $replyRate         = 0.0;
        $replyRatePrevious = 0.0;

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

        /* =========== Sender aktif (DB) & tersambung (WAHA) =========== */
        $activeSenders    = 0;        // is_active=1 di DB
        $connectedSenders = null;     // jumlah yang tersambung via WAHA (null = tidak diprobe)
        $totalSenders     = 0;

        if ($hasSenders) {
            $totalSenders  = (int) WahaSender::count();

            // Skema kamu tidak punya kolom `status`, jadi pakai is_active (atau fallback lain jika ada).
            if (Schema::hasColumn('waha_senders', 'is_active')) {
                $activeSenders = (int) WahaSender::where('is_active', 1)->count();
                $activeList    = WahaSender::where('is_active', 1)->orderByDesc('id')->take(10)->get(); // probe max 10
            } elseif (Schema::hasColumn('waha_senders', 'is_connected')) {
                $activeSenders = (int) WahaSender::where('is_connected', 1)->count();
                $activeList    = WahaSender::where('is_connected', 1)->orderByDesc('id')->take(10)->get();
            } elseif (Schema::hasColumn('waha_senders', 'connection_status')) {
                $activeSenders = (int) WahaSender::where('connection_status', 'connected')->count();
                $activeList    = WahaSender::where('connection_status', 'connected')->orderByDesc('id')->take(10)->get();
            } else {
                $activeList    = WahaSender::orderByDesc('id')->take(10)->get();
            }

            // Probe ke WAHA untuk mengetahui berapa yang benar2 CONNECTED (sinkron dgn WahaService)
            try {
                $svc = app(WahaService::class); // constructor akan validasi WAHA_URL
                $connectedSenders = 0;
                foreach ($activeList as $sender) {
                    $st = $svc->sessionStatus($sender);
                    if ($st['success']) {
                        $isConn = $st['connected'] === true;
                        $state  = strtoupper((string) ($st['state'] ?? ''));
                        if ($isConn || in_array($state, ['CONNECTED','READY','AUTHENTICATED','ONLINE','OPEN','RUNNING'], true)) {
                            $connectedSenders++;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Jika WAHA tidak terkonfigurasi/timeout, biarkan null (UI akan fallback).
                $connectedSenders = null;
            }
        }

        $stats = [
            'totalLeads'                => $totalLeads,
            'leadsThisWeek'             => $leadsThisWeek,
            'leadsPreviousWeek'         => $leadsPreviousWeek,
            'messagesSentLast7Days'     => $messagesSentLast7,
            'messagesSentPrevious7Days' => $messagesSentPrev7,
            'replyRate'                 => $replyRate,
            'replyRatePrevious'         => $replyRatePrevious,
            'activeSenders'             => $activeSenders,
            'connectedSenders'          => $connectedSenders, // bisa null
            'totalSenders'              => $totalSenders,
        ];

        /* =================== DATA CHART 30 HARI =================== */
        $days       = collect(range(0, 29))->map(fn ($i) => Carbon::today()->subDays(29 - $i));
        $categories = $days->map->toDateString()->all();

        // Leads per hari
        $leadsPerDay = array_fill(0, 30, 0);
        if ($hasLeads) {
            $leadRows = Lead::selectRaw('DATE(created_at) as d, COUNT(*) as c')
                ->where('created_at', '>=', Carbon::today()->subDays(29)->startOfDay())
                ->groupBy('d')->orderBy('d')->pluck('c', 'd')->all();

            foreach ($days as $idx => $date) {
                $leadsPerDay[$idx] = (int) ($leadRows[$date->toDateString()] ?? 0);
            }
        }

        // Pesan terkirim per hari
        $messagesPerDay = array_fill(0, 30, 0);
        if ($hasActivity) {
            $msgRows = Activity::selectRaw('DATE(created_at) as d, COUNT(*) as c')
                ->whereIn('log_name', $outgoingLogs)
                ->where('created_at', '>=', Carbon::today()->subDays(29)->startOfDay())
                ->groupBy('d')->orderBy('d')->pluck('c', 'd')->all();

            foreach ($days as $idx => $date) {
                $messagesPerDay[$idx] = (int) ($msgRows[$date->toDateString()] ?? 0);
            }
        }

        $chartData = [
            'categories' => $categories,
            'leads'      => $leadsPerDay,
            'messages'   => $messagesPerDay,
        ];

        // Aktivitas terbaru
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
