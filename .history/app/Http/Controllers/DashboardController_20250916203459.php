<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Subscription;
use App\Models\Task;
use App\Models\WahaSender;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman dashboard utama dengan data ringkasan dari database.
     */
    public function index()
    {
        // --- DATA UNTUK KARTU METRIK ---
        $stats = $this->getStats();

        // --- DATA UNTUK GRAFIK UTAMA (30 HARI TERAKHIR) ---
        $chartData = $this->getChartData();

        // --- DATA UNTUK DONUT CHART STATUS LEADS ---
        $statusCounts = $this->getStatusCounts();

        // --- DATA UNTUK TABEL AKTIVITAS TERBARU ---
        $recentActivities = Activity::with('causer')->latest()->limit(5)->get();

        // Mengirim semua data yang sudah diolah ke view
        return view('dashboard', compact(
            'stats',
            'chartData',
            'statusCounts',
            'recentActivities'
        ));
    }

    /**
     * Mengambil dan mengolah data untuk kartu metrik.
     * @return array
     */
    private function getStats(): array
    {
        // Menghitung MRR dari subscription yang aktif
        $mrr = Subscription::where('status', 'active')->sum('amount');

        // Menghitung status tugas
        $tasks = Task::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $openTasks = $tasks->get('todo', 0);
        $progressTasks = $tasks->get('in_progress', 0);
        $doneTasks = $tasks->get('done', 0);

        return [
            'total_leads' => [
                'label' => 'Total Leads',
                'value' => Lead::count(),
                'change' => 0, // Placeholder, Anda bisa tambahkan logika perbandingan
            ],
            'messages_sent_7d' => [
                'label' => 'Pesan Terkirim (7h)',
                'value' => 0, // Placeholder, perlu logika tracking pesan
                'change' => 0,
            ],
            'reply_rate_7d' => [
                'label' => 'Tingkat Balasan',
                'value' => '0%', // Placeholder
                'change' => 0,
            ],
            'active_senders' => [
                'label' => 'WA Sender Tersambung',
                'value' => WahaSender::where('status', 'authenticated')->count() . '/' . WahaSender::count(),
                'context' => 'Total sesi terdaftar',
            ],
            'mrr' => [
                'label' => 'MRR (Active)',
                'value' => 'Rp ' . number_format($mrr, 0, ',', '.'),
                'context' => 'Subs. aktif: ' . Subscription::where('status', 'active')->count(),
            ],
            'open_tasks' => [
                'label' => 'Tugas',
                'value' => $openTasks + $progressTasks,
                'context' => "Open: {$openTasks} • Progress: {$progressTasks} • Done: {$doneTasks}",
            ],
        ];
    }

    /**
     * Mengambil data untuk grafik aktivitas 30 hari terakhir.
     * @return array
     */
    private function getChartData(): array
    {
        // Placeholder, idealnya ini mengambil data pengiriman pesan
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays(29);
        $labels = [];
        $data = [];

        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $labels[] = $date->format('d M');
            // Ganti dengan query Anda, contoh: menghitung leads baru per hari
            $data[] = Lead::whereDate('created_at', $date)->count();
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Mengambil data jumlah leads berdasarkan status untuk donut chart.
     * @return array
     */
    private function getStatusCounts(): array
    {
        $statusData = Lead::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'labels' => $statusData->keys()->map('ucfirst')->all(),
            'data'   => $statusData->values()->all(),
        ];
    }
}

