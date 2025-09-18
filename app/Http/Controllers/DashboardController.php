<?php

namespace App\Http\Controllers;

use App\ViewModels\Dashboard\FunnelSummary;
use App\ViewModels\Dashboard\TodayTasks;
use Illuminate\Http\Request;
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
use App\Models\User;
// Service WAHA
use App\Services\WahaService;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $authUser = Auth::user();

        // ViewModels eksisting (dipertahankan)
        $funnelSummary = new FunnelSummary();
        $todayTasksVM  = new TodayTasks($authUser);

        // Guard ketersediaan tabel
        $hasLeads         = Schema::hasTable('leads');
        $hasActivity      = Schema::hasTable('activity_log');
        $hasSenders       = Schema::hasTable('waha_senders');
        $hasTasks         = Schema::hasTable('tasks');
        $hasSubscriptions = Schema::hasTable('subscriptions');
        $hasCampaigns     = Schema::hasTable('campaigns');
        $hasUsers         = Schema::hasTable('users');

        /* =================== STAT KARTU ATAS (dipertahankan) =================== */
        $totalLeads        = $hasLeads ? (int) Lead::count() : 0;
        $leadsThisWeek     = $hasLeads ? (int) Lead::where('created_at', '>=', now()->startOfWeek())->count() : 0;
        $leadsPreviousWeek = $hasLeads ? (int) Lead::whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])->count() : 0;

        // WA activity (outgoing vs incoming)
        $outgoingLogs = ['wa_send','wa_chat','wa_outgoing','wa_broadcast'];
        $incomingLogs = ['wa_reply','wa_incoming','lead_reply'];
        $messagesSentLast7  = 0;
        $messagesSentPrev7  = 0;
        $repliesLast7       = 0;
        $repliesPrev7       = 0;
        $replyRate          = 0.0;
        $replyRatePrevious  = 0.0;
        if ($hasActivity) {
            $messagesSentLast7 = (int) Activity::whereIn('log_name', $outgoingLogs)->where('created_at', '>=', now()->subDays(7))->count();
            $messagesSentPrev7 = (int) Activity::whereIn('log_name', $outgoingLogs)->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->count();
            $repliesLast7      = (int) Activity::whereIn('log_name', $incomingLogs)->where('created_at', '>=', now()->subDays(7))->count();
            $repliesPrev7      = (int) Activity::whereIn('log_name', $incomingLogs)->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->count();
            $replyRate         = $messagesSentLast7 > 0 ? round(($repliesLast7 / $messagesSentLast7) * 100, 1) : 0.0;
            $replyRatePrevious = $messagesSentPrev7 > 0 ? round(($repliesPrev7 / $messagesSentPrev7) * 100, 1) : 0.0;
        }

        /* =========== WA Sender aktif (DB) & tersambung (WAHA) =========== */
        $activeSenders    = 0;
        $connectedSenders = null; // null = tidak diprobe (misal WAHA OFF)
        $totalSenders     = 0;
        if ($hasSenders) {
            $totalSenders  = (int) WahaSender::count();
            // Tentukan activeList + activeSenders berdasarkan kolom yang tersedia
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

            // Probe WAHA
            try {
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
                $connectedSenders = null;
            }
        }

        /* =================== SUBSCRIPTIONS (MRR & status) =================== */
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
                ->sum(fn ($s) => (strtolower($s->cycle) === 'yearly') ? ($s->amount / 12) : $s->amount);
            $subSummary['mrr'] = round($subSummary['mrr'], 2);
        }

        /* =================== TASKS (ringkas & hari ini) =================== */
        $taskSummary = [
            'open'        => 0,
            'in_progress' => 0,
            'done'        => 0,
            'mineToday'   => [],
        ];
        if ($hasTasks) {
            $taskSummary['open']        = (int) Task::where('status','open')->count();
            $taskSummary['in_progress'] = (int) Task::where('status','in_progress')->count();
            $taskSummary['done']        = (int) Task::where('status','done')->count();
            $taskSummary['mineToday']   = Task::query()
                ->where('assignee_id', $authUser?->id)
                ->whereDate('due_date', today())
                ->orderBy('priority', 'desc')
                ->orderBy('id','desc')
                ->take(5)
                ->get(['id','title','status','priority','due_date']);
        }

        /* =================== CAMPAIGNS (ringkas) =================== */
        $campaignSummary = [
            'total' => 0,
            'by_status' => [],
            'latest' => [],
        ];
        if ($hasCampaigns) {
            $campaignSummary['total'] = (int) Campaign::count();
            $campaignSummary['by_status'] = Campaign::select('status', DB::raw('COUNT(*) as c'))
                ->groupBy('status')->pluck('c','status')->map(fn($v)=>(int)$v)->toArray();
            $campaignSummary['latest'] = Campaign::latest('id')->take(5)->get(['id','name','status','created_at']);
        }

        /* =================== LEAD STATUS DONUT =================== */
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

        /* =================== TRIAL HAMPIR HABIS =================== */
        $trialsSoon = collect();
        if ($hasLeads && Schema::hasColumn('leads','trial_ends_at')) {
            $trialsSoon = Lead::whereNotNull('trial_ends_at')
                ->whereBetween('trial_ends_at', [now(), now()->addDays(7)])
                ->orderBy('trial_ends_at')
                ->take(6)
                ->get(['id','name','store_name','email','trial_ends_at']);
        }

        /* =================== Aktivitas terbaru =================== */
        $recentActivities = $hasActivity
            ? Activity::with('causer')->latest()->take(10)->get()
            : collect();

        /* =================== DATA UNTUK KANBAN VIEW =================== */
        // Dropdown user untuk filter
        $users = $hasUsers ? User::orderBy('name')->get(['id','name']) : collect();

        // Filter dari querystring (meski form mengarah ke route 'tasks.index', kita hormati param yang ada)
        $assigneeId = $request->query('assignee_id');
        $priority   = $request->query('priority');

        // Siapkan koleksi tugas per status agar sesuai dengan view
        $statusBuckets = ['open','in_progress','review','done'];
        $tasks = [
            'open'        => collect(),
            'in_progress' => collect(),
            'review'      => collect(),
            'done'        => collect(),
        ];

        if ($hasTasks) {
            // Base query dengan eager load relasi yang dipakai di view
            $base = Task::query()
                ->with(['assignee:id,name'])
                ->select(['id','title','description','status','priority','due_date','assignee_id','link','color']);

            if ($assigneeId !== null && $assigneeId !== '') {
                $base->where('assignee_id', $assigneeId);
            }
            if ($priority !== null && $priority !== '') {
                $base->where('priority', $priority);
            }

            // Urutan wajar: prioritas tinggi dulu, lalu due_date terdekat, lalu terbaru
            $priorityOrder = DB::raw("FIELD(priority, 'urgent','high','medium','low')");
            foreach ($statusBuckets as $st) {
                $q = (clone $base)
                    ->where('status', $st)
                    ->orderBy($priorityOrder)
                    ->orderByRaw('COALESCE(due_date, "9999-12-31") asc')
                    ->latest('id');

                $tasks[$st] = $q->get();
            }
        }

        /* =================== Paket variabel ke view =================== */
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
            'recentActivities',
            // ==== variabel yang dibutuhkan kanban ====
            'users',
            'tasks'
        ));
    }
}
