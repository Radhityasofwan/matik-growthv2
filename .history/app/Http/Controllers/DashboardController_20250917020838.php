<?php

namespace App\Http\Controllers;

use App\ViewModels\Dashboard\FunnelSummary;
use App\ViewModels\Dashboard\TodayTasks;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Models
use Spatie\Activitylog\Models\Activity;
use App\Models\Lead;
use App\Models\Task;
use App\Models\Subscription;
use App\Models\Campaign;
use App\Models\WahaSender;

// Service WAHA
use App\Services\WahaService;

class DashboardController extends Controller
{
    public function index()
    {
        $user   = Auth::user();
        $now    = Carbon::now();
        $today  = $now->copy()->startOfDay();
        $weekS  = $now->copy()->startOfWeek();
        $weekPS = $now->copy()->subWeek()->startOfWeek();
        $weekPE = $now->copy()->subWeek()->endOfWeek();

        // ViewModels (dipertahankan)
        $funnelSummary = new FunnelSummary();
        $todayTasksVM  = new TodayTasks($user);

        // Guard tabel
        $hasLeads         = Schema::hasTable('leads');
        $hasActivity      = Schema::hasTable('activity_log');
        $hasSenders       = Schema::hasTable('waha_senders');
        $hasTasks         = Schema::hasTable('tasks');
        $hasSubscriptions = Schema::hasTable('subscriptions');
        $hasCampaigns     = Schema::hasTable('campaigns');

        /* ============== STAT KARTU ATAS ============== */
        $totalLeads        = $hasLeads ? (int) Lead::count() : 0;
        $leadsThisWeek     = $hasLeads ? (int) Lead::where('created_at', '>=', $weekS)->count() : 0;
        $leadsPreviousWeek = $hasLeads ? (int) Lead::whereBetween('created_at', [$weekPS, $weekPE])->count() : 0;

        // WA activity (outgoing vs incoming)
        $outgoingLogs = ['wa_send','wa_chat','wa_outgoing','wa_broadcast'];
        $incomingLogs = ['wa_reply','wa_incoming','lead_reply'];

        $messagesSentLast7  = 0;
        $messagesSentPrev7  = 0;
        $repliesLast7       = 0;
        $repliesPrev7       = 0;
        $replyRate          = 0.0;
        $replyRatePrevious  = 0.0;

        if ($hasActivity && Schema::hasColumn('activity_log','log_name')) {
            $messagesSentLast7 = (int) Activity::whereIn('log_name', $outgoingLogs)
                ->where('created_at', '>=', $now->copy()->subDays(7))->count();

            $messagesSentPrev7 = (int) Activity::whereIn('log_name', $outgoingLogs)
                ->whereBetween('created_at', [$now->copy()->subDays(14), $now->copy()->subDays(7)])->count();

            $repliesLast7 = (int) Activity::whereIn('log_name', $incomingLogs)
                ->where('created_at', '>=', $now->copy()->subDays(7))->count();

            $repliesPrev7 = (int) Activity::whereIn('log_name', $incomingLogs)
                ->whereBetween('created_at', [$now->copy()->subDays(14), $now->copy()->subDays(7)])->count();

            $replyRate         = $messagesSentLast7 > 0 ? round(($repliesLast7 / $messagesSentLast7) * 100, 1) : 0.0;
            $replyRatePrevious = $messagesSentPrev7 > 0 ? round(($repliesPrev7 / $messagesSentPrev7) * 100, 1) : 0.0;
        }

        /* ======= WA Sender aktif (DB) & tersambung (WAHA) ======= */
        $activeSenders    = 0;
        $connectedSenders = null; // null = tidak diprobe
        $totalSenders     = 0;

        if ($hasSenders) {
            $totalSenders  = (int) WahaSender::count();

            // pilih kolom yang ada untuk status aktif
            if (Schema::hasColumn('waha_senders', 'is_active')) {
                $activeSenders = (int) WahaSender::where('is_active', 1)->count();
                $activeList    = WahaSender::where('is_active', 1)->latest('id')->take(10)->get();
            } elseif (Schema::hasColumn('waha_senders', 'is_connected')) {
                $activeSenders = (int) WahaSender::where('is_connected', 1)->count();
                $activeList    = WahaSender::where('is_connected', 1)->latest('id')->take(10)->get();
            } elseif (Schema::hasColumn('waha_senders', 'connection_status')) {
                $activeSenders = (int) WahaSender::where('connection_status', 'connected')->count();
                $activeList    = WahaSender::where('connection_status', 'connected')->latest('id')->take(10)->get();
            } else {
                $activeList    = WahaSender::latest('id')->take(10)->get();
            }

            // Probe WAHA — jangan ubah WahaService (sesuai aturan proyek)
            try {
                /** @var \App\Services\WahaService $svc */
                $svc = app(WahaService::class);
                $connectedSenders = 0;
                foreach ($activeList as $sender) {
                    $st = $svc->sessionStatus($sender);
                    if (!empty($st['success'])) {
                        $state  = strtoupper((string) ($st['state'] ?? ''));
                        $isConn = ($st['connected'] ?? false) === true;
                        if ($isConn || in_array($state, ['CONNECTED','READY','AUTHENTICATED','ONLINE','OPEN','RUNNING'], true)) {
                            $connectedSenders++;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // jika WAHA down, jangan blok render
                $connectedSenders = null;
            }
        }

        /* ================= SUBSCRIPTIONS ================= */
        $subSummary = [
            'total'     => 0,
            'active'    => 0,
            'paused'    => 0,
            'cancelled' => 0,
            'mrr'       => 0.0,
        ];
        if ($hasSubscriptions) {
            $subSummary['total']     = (int) Subscription::count();
            $subSummary['active']    = (int) Subscription::where('status', 'active')->count();
            $subSummary['paused']    = (int) Subscription::where('status', 'paused')->count();
            $subSummary['cancelled'] = (int) Subscription::where('status', 'cancelled')->count();

            $subSummary['mrr'] = (float) Subscription::where('status','active')
                ->get(['amount','cycle'])
                ->sum(function ($s) {
                    $amount = (float) $s->amount;
                    $cycle  = strtolower((string) $s->cycle);
                    return $cycle === 'yearly' ? ($amount / 12) : $amount;
                });
            $subSummary['mrr'] = round($subSummary['mrr'], 2);
        }

        /* =================== TASKS =================== */
        $taskSummary = [
            'open'        => 0,
            'in_progress' => 0,
            'done'        => 0,
            'mineToday'   => collect(),
        ];
        if ($hasTasks) {
            // status fleksibel (kalau pakai enum/case)
            $taskSummary['open']        = (int) Task::where('status','open')->count();
            $taskSummary['in_progress'] = (int) Task::where('status','in_progress')->count();
            $taskSummary['done']        = (int) Task::where('status','done')->count();

            $q = Task::query()->where('assignee_id', $user?->id);

            // due_date mungkin nullable; hanya ambil yang due harini
            if (Schema::hasColumn('tasks','due_date')) {
                $q->whereDate('due_date', $today);
            } else {
                // fallback: created_at harini
                $q->whereDate('created_at', $today);
            }

            $taskSummary['mineToday'] = $q->orderBy('priority', 'desc')
                ->orderBy('id','desc')
                ->take(5)
                ->get(['id','title','status','priority','due_date']);
        }

        /* =================== CAMPAIGNS =================== */
        $campaignSummary = [
            'total' => 0,
            'by_status' => [],
            'latest' => collect(),
        ];
        if ($hasCampaigns) {
            $campaignSummary['total'] = (int) Campaign::count();
            $campaignSummary['by_status'] = Campaign::select('status', DB::raw('COUNT(*) as c'))
                ->groupBy('status')->pluck('c','status')->map(fn($v)=>(int)$v)->toArray();
            $campaignSummary['latest'] = Campaign::latest('id')->take(5)->get(['id','name','status','created_at']);
        }

        /* ================= LEAD STATUS DONUT ================= */
        $leadStatusSeries = [];
        $leadStatusLabels = [];
        if ($hasLeads && Schema::hasColumn('leads','status')) {
            $raw = Lead::select('status', DB::raw('COUNT(*) as c'))
                ->groupBy('status')->pluck('c','status')->toArray();
            $order = ['trial','active','nonactive','converted','churn'];
            foreach ($order as $s) {
                $leadStatusLabels[] = ucfirst($s);
                $leadStatusSeries[] = (int) ($raw[$s] ?? 0);
            }
        }

        /* ============ DATA CHART 30 HARI ============ */
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
        if ($hasActivity && Schema::hasColumn('activity_log','log_name')) {
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

        /* ============ TRIAL HAMPIR HABIS (≤7 HARI) ============ */
        $trialsSoon = collect();
        if ($hasLeads && Schema::hasColumn('leads','trial_ends_at')) {
            $trialsSoon = Lead::whereNotNull('trial_ends_at')
                ->whereBetween('trial_ends_at', [$now, $now->copy()->addDays(7)])
                ->orderBy('trial_ends_at')
                ->take(6)
                ->get(['id','name','store_name','email','trial_ends_at']);
        }

        /* ============ AKTIVITAS TERBARU ============ */
        $recentActivities = collect();
        if ($hasActivity) {
            $recentActivities = Activity::with('causer')
                ->select(['id','description','causer_id','created_at','log_name'])
                ->latest('id')
                ->take(10)
                ->get();
        }

        // ===== Paketkan untuk Blade sesuai nama yang dipakai =====
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

        $statusDonut = [
            'labels' => $leadStatusLabels,
            'series' => $leadStatusSeries,
        ];

        return view('dashboard.index', compact(
            'funnelSummary',
            'todayTasksVM',
            'stats',
            'chartData',
            'statusDonut',
            'subSummary',
            'taskSummary',
            'campaignSummary',
            'trialsSoon',
            'recentActivities'
        ));
    }
}
