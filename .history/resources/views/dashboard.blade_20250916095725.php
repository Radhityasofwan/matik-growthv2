@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    {{--
        CATATAN UNTUK PENGEMBANG:
        File ini sekarang mengharapkan variabel-variabel berikut dari DashboardController.
        Pastikan Anda mengirim data ini dari controller ke view.

        $stats = [
            'totalLeads' => (int) App\Models\Lead::count(),
            'leadsThisWeek' => (int) App\Models\Lead::where('created_at', '>=', now()->startOfWeek())->count(),
            'leadsPreviousWeek' => (int) App\Models\Lead::whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])->count(),
            'messagesSentLast7Days' => (int) 500, // Ganti dengan logika query Anda
            'messagesSentPrevious7Days' => (int) 450, // Ganti dengan logika query Anda
            'replyRate' => (float) 15.8, // Ganti dengan logika query Anda
            'replyRatePrevious' => (float) 16.9, // Ganti dengan logika query Anda
            'activeSenders' => (int) App\Models\WahaSender::where('status', 'connected')->count(), // Asumsi status 'connected'
            'totalSenders' => (int) App\Models\WahaSender::count(),
        ];
        $chartData = [
            'categories' => ['2023-08-17', ...], // Array tanggal 30 hari terakhir
            'leads' => [31, 40, ...],      // Array jumlah leads baru per hari
            'messages' => [11, 32, ...],   // Array jumlah pesan terkirim per hari
        ];
        $recentActivities = Spatie\Activitylog\Models\Activity::with('causer')
                                ->latest()
                                ->take(5)
                                ->get();
    --}}

    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-neutral">Dashboard</h1>
            <p class="mt-1 text-neutral/60">Ringkasan aktivitas CRM Anda hari ini, {{ Auth::user()->name }}.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('leads.import') }}" class="btn btn-outline btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 mr-2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                Import Leads
            </a>
            <a href="{{ route('whatsapp.broadcast.create') }}" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 mr-2"><path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>
                Broadcast Baru
            </a>
        </div>
    </div>

    <!-- Grid Kartu Metrik -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Kartu 1: Total Leads -->
        <div class="card bg-base-100 shadow-md border border-base-300/50">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h2 class="card-title text-sm font-semibold text-neutral/60">Total Leads</h2>
                    <div class="bg-secondary p-2 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-primary"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                </div>
                <p class="text-4xl font-bold text-neutral mt-2">{{ number_format($stats['totalLeads'] ?? 0) }}</p>
                @php
                    $leadsChange = ($stats['leadsThisWeek'] ?? 0) - ($stats['leadsPreviousWeek'] ?? 0);
                @endphp
                <p class="text-xs text-neutral/60 mt-1 flex items-center">
                    <span class="{{ $leadsChange >= 0 ? 'text-success' : 'text-error' }} font-semibold mr-1">
                        {{ $leadsChange >= 0 ? '▲' : '▼' }} {{ number_format(abs($leadsChange)) }}
                    </span>
                    minggu ini
                </p>
            </div>
        </div>

        <!-- Kartu 2: Pesan Terkirim -->
        <div class="card bg-base-100 shadow-md border border-base-300/50">
            <div class="card-body">
                @php
                    $sentChange = ($stats['messagesSentLast7Days'] ?? 0) - ($stats['messagesSentPrevious7Days'] ?? 0);
                    $sentPercentage = ($stats['messagesSentPrevious7Days'] ?? 0) > 0 ? ($sentChange / $stats['messagesSentPrevious7Days']) * 100 : 0;
                @endphp
                <div class="flex items-center justify-between">
                    <h2 class="card-title text-sm font-semibold text-neutral/60">Pesan Terkirim</h2>
                    <div class="bg-secondary p-2 rounded-lg">
                         <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-primary"><line x1="22" x2="11" y1="2" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    </div>
                </div>
                <p class="text-4xl font-bold text-neutral mt-2">{{ number_format($stats['messagesSentLast7Days'] ?? 0) }}</p>
                <p class="text-xs text-neutral/60 mt-1 flex items-center">
                    <span class="{{ $sentChange >= 0 ? 'text-success' : 'text-error' }} font-semibold mr-1">
                        {{ $sentChange >= 0 ? '▲' : '▼' }} {{ number_format(abs($sentPercentage), 1) }}%
                    </span>
                    vs 7 hari lalu
                </p>
            </div>
        </div>

        <!-- Kartu 3: Tingkat Balasan -->
        <div class="card bg-base-100 shadow-md border border-base-300/50">
            <div class="card-body">
                 @php
                    $replyRateChange = ($stats['replyRate'] ?? 0) - ($stats['replyRatePrevious'] ?? 0);
                @endphp
                <div class="flex items-center justify-between">
                    <h2 class="card-title text-sm font-semibold text-neutral/60">Tingkat Balasan</h2>
                     <div class="bg-secondary p-2 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-primary"><path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"/><path d="M12 13h.01"/></svg>
                    </div>
                </div>
                <p class="text-4xl font-bold text-neutral mt-2">{{ number_format($stats['replyRate'] ?? 0, 1) }}%</p>
                 <p class="text-xs text-neutral/60 mt-1 flex items-center">
                    <span class="{{ $replyRateChange >= 0 ? 'text-success' : 'text-error' }} font-semibold mr-1">
                        {{ $replyRateChange >= 0 ? '▲' : '▼' }} {{ number_format(abs($replyRateChange), 1) }}%
                    </span>
                    vs 7 hari lalu
                </p>
            </div>
        </div>

        <!-- Kartu 4: WA Sender Aktif -->
        <div class="card bg-base-100 shadow-md border border-base-300/50">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h2 class="card-title text-sm font-semibold text-neutral/60">WA Sender Aktif</h2>
                     <div class="bg-secondary p-2 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-primary"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    </div>
                </div>
                <p class="text-4xl font-bold text-neutral mt-2">{{ $stats['activeSenders'] ?? 0 }} / {{ $stats['totalSenders'] ?? 0 }}</p>
                 <p class="text-xs text-neutral/60 mt-1 flex items-center">
                    {{ ($stats['activeSenders'] ?? 0) == ($stats['totalSenders'] ?? 0) && ($stats['totalSenders'] ?? 0) > 0 ? 'Semua sesi terhubung' : 'Beberapa sesi terputus' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Area Grafik & Aktivitas -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mt-8">
        <!-- Grafik Utama -->
        <div class="card bg-base-100 shadow-md border border-base-300/50 lg:col-span-3">
            <div class="card-body">
                <h2 class="card-title">Aktivitas Leads (30 Hari Terakhir)</h2>
                <div x-data="mainChart(@json($chartData ?? []))" x-init="init()" class="w-full h-80 -mb-4 -ml-4">
                    <div id="main-chart"></div>
                </div>
            </div>
        </div>

        <!-- Aktivitas Terbaru -->
        <div class="card bg-base-100 shadow-md border border-base-300/50 lg:col-span-2">
            <div class="card-body">
                <h2 class="card-title mb-4">Aktivitas Terbaru</h2>
                <div class="space-y-4">
                    @forelse($recentActivities ?? [] as $activity)
                    <div class="flex items-start">
                        <div class="avatar mr-4">
                            <div class="w-10 rounded-full bg-secondary text-primary flex items-center justify-center">
                                <!-- Simple icon based on description -->
                                @if(Str::contains($activity->description, ['mengimpor', 'leads']))
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/></svg>
                                @elseif(Str::contains($activity->description, ['Broadcast', 'terkirim']))
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="m3 11 18-5v12L3 14v-3z"/></svg>
                                @else
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M11 12H3"/><path d="m3 12 4-4"/><path d="m3 12 4 4"/></svg>
                                @endif
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-neutral">
                                {{-- Menambahkan nama causer jika ada --}}
                                @if($activity->causer)
                                    <strong>{{ $activity->causer->name }}</strong>
                                @endif
                                {{ $activity->description }}
                            </p>
                            <p class="text-xs text-neutral/60">{{ $activity->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-neutral/60 py-8">
                        <p>Belum ada aktivitas terbaru.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<!-- ApexCharts CDN -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
    function mainChart(chartData) {
        return {
            init() {
                if (!chartData || !chartData.categories || !chartData.leads || !chartData.messages) {
                    document.querySelector("#main-chart").innerHTML = '<div class="flex items-center justify-center h-full text-neutral/60"><p>Data chart tidak tersedia.</p></div>';
                    return;
                }
                const options = {
                    series: [{
                        name: 'Leads Baru',
                        data: chartData.leads
                    }, {
                        name: 'Pesan Terkirim',
                        data: chartData.messages
                    }],
                    chart: {
                        height: '100%',
                        type: 'area',
                        toolbar: { show: false },
                        zoom: { enabled: false },
                    },
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 2 },
                    colors: ['#3B82F6', '#10B981'],
                    fill: {
                        type: 'gradient',
                        gradient: {
                            opacityFrom: 0.6,
                            opacityTo: 0.1,
                        }
                    },
                    xaxis: {
                        type: 'datetime',
                        categories: chartData.categories,
                        labels: {
                            style: { colors: '#64748B' },
                        },
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                    },
                    yaxis: {
                        labels: {
                            style: { colors: '#64748B' },
                        }
                    },
                    tooltip: {
                        x: { format: 'dd MMM yyyy' },
                        theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                    },
                    grid: {
                        borderColor: '#e5e7eb20',
                        strokeDashArray: 4
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'right',
                        markers: {
                            radius: 12
                        },
                         labels: {
                            colors: '#64748B'
                        },
                    }
                };

                const chart = new ApexCharts(document.querySelector("#main-chart"), options);
                chart.render();
            }
        }
    }
</script>
@endpush

