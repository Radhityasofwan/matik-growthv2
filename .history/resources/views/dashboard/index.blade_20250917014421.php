@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    // Persiapan data dari controller, logika ini dipertahankan
    $stats = $stats ?? [];
    $chartData = $chartData ?? ['categories'=>[], 'leads'=>[], 'messages'=>[]];
    $statusDonut = $statusDonut ?? ['labels'=>[], 'series'=>[]];
    $subSummary = $subSummary ?? ['total'=>0,'active'=>0,'paused'=>0,'cancelled'=>0,'mrr'=>0];
    $taskSummary = $taskSummary ?? ['open'=>0,'in_progress'=>0,'done'=>0,'mineToday'=>collect()];
    $trialsSoon = $trialsSoon ?? collect();
    $recentActivities = $recentActivities ?? collect();

    // Kalkulasi perubahan untuk stat cards
    $leadsChange = ($stats['leadsThisWeek'] ?? 0) - ($stats['leadsPreviousWeek'] ?? 0);
    $sentChange = ($stats['messagesSentLast7Days'] ?? 0) - ($stats['messagesSentPrevious7Days'] ?? 0);
    $sentPct = ($stats['messagesSentPrevious7Days'] ?? 0) > 0 ? ($sentChange / $stats['messagesSentPrevious7Days']) * 100 : 0;
    $replyChange = ($stats['replyRate'] ?? 0) - ($stats['replyRatePrevious'] ?? 0);

    // Logika untuk label status WA Sender
    $connected = $stats['connectedSenders'] ?? null;
    $active    = $stats['activeSenders'] ?? 0;
    $total     = $stats['totalSenders'] ?? 0;
    $senderLabelCount = is_null($connected) ? "{$active} / {$total}" : "{$connected} / {$total}";
    $senderLabelDesc  = is_null($connected)
        ? (($active === $total && $total > 0) ? 'Semua sender diaktifkan' : 'Sebagian sender nonaktif')
        : (($connected === $total && $total > 0) ? 'Semua sesi tersambung' : (($total - $connected) . ' sesi belum tersambung'));
@endphp

<!-- Header Halaman -->
<div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-8" data-aos="fade-down">
    <div>
        <h1 class="text-3xl font-bold text-base-content">Dashboard</h1>
        <p class="mt-1 text-base-content/70">
            Selamat datang kembali, {{ auth()->user()?->name ?? 'User' }}!
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('leads.create') }}" class="btn btn-outline btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            Lead Baru
        </a>
        <a href="{{ route('whatsapp.broadcast.create') }}" class="btn btn-primary">
             <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            Broadcast Baru
        </a>
    </div>
</div>

<!-- Grid Stat Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6">
    {{-- Card untuk setiap statistik dengan animasi entri --}}
    <div data-aos="fade-up" data-aos-delay="50">
        <x-card.stat title="Total Leads" value="{{ number_format($stats['totalLeads'] ?? 0) }}" change="{{ $leadsChange >= 0 ? '▲' : '▼' }} {{ number_format(abs($leadsChange)) }} minggu ini" changeType="{{ $leadsChange >= 0 ? 'success' : 'error' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </x-card.stat>
    </div>
    <div data-aos="fade-up" data-aos-delay="100">
        <x-card.stat title="Pesan Terkirim (7h)" value="{{ number_format($stats['messagesSentLast7Days'] ?? 0) }}" change="{{ $sentChange >= 0 ? '▲' : '▼' }} {{ number_format(abs($sentPct), 1) }}% vs 7 hari lalu" changeType="{{ $sentChange >= 0 ? 'success' : 'error' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        </x-card.stat>
    </div>
    <div data-aos="fade-up" data-aos-delay="150">
        <x-card.stat title="Tingkat Balasan" value="{{ number_format($stats['replyRate'] ?? 0, 1) }}%" change="{{ $replyChange >= 0 ? '▲' : '▼' }} {{ number_format(abs($replyChange), 1) }}% vs 7 hari lalu" changeType="{{ $replyChange >= 0 ? 'success' : 'error' }}">
             <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"/></svg>
        </x-card.stat>
    </div>
    <div data-aos="fade-up" data-aos-delay="200">
        <x-card.stat title="{{ is_null($connected) ? 'WA Sender Aktif' : 'WA Sender Tersambung' }}" value="{{ $senderLabelCount }}" change="{{ $senderLabelDesc }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        </x-card.stat>
    </div>
     <div data-aos="fade-up" data-aos-delay="250">
        <x-card.stat title="MRR (Active)" value="Rp {{ number_format($subSummary['mrr'] ?? 0, 0, ',', '.') }}" change="Subs aktif: {{ number_format($subSummary['active'] ?? 0) }}">
             <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
        </x-card.stat>
    </div>
     <div data-aos="fade-up" data-aos-delay="300">
        <x-card.stat title="Tugas Terbuka" value="{{ ($taskSummary['open'] ?? 0) + ($taskSummary['in_progress'] ?? 0) }}" change="Open: {{ $taskSummary['open'] ?? 0 }} • Progress: {{ $taskSummary['in_progress'] ?? 0 }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="20 6 9 17 4 12"/></svg>
        </x-card.stat>
    </div>
</div>

<!-- Grid Charts -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <!-- Chart Aktivitas -->
    <div class="card bg-base-100 shadow-lg border border-base-300/50 lg:col-span-2" data-aos="fade-up">
        <div class="card-body">
            <h2 class="card-title">Aktivitas 30 Hari Terakhir</h2>
            {{-- FIXED: Menggunakan directive @json untuk memastikan data aman disematkan --}}
            <div x-data="mainChart" data-chart-data='@json($chartData)' x-init="init()" class="w-full h-80 min-h-[320px]">
                <div x-show="isLoading" class="skeleton w-full h-full"></div>
                <div x-show="!isLoading" id="main-chart" class="w-full h-full" x-cloak></div>
            </div>
        </div>
    </div>

    <!-- Chart Komposisi Leads -->
    <div class="card bg-base-100 shadow-lg border border-base-300/50" data-aos="fade-up" data-aos-delay="100">
        <div class="card-body">
            <h2 class="card-title">Komposisi Status Leads</h2>
             {{-- FIXED: Menggunakan directive @json untuk memastikan data aman disematkan --}}
            <div x-data="statusDonut" data-donut-data='@json($statusDonut)' x-init="init()" class="w-full h-80 min-h-[320px] flex items-center justify-center">
                <div x-show="isLoading" class="skeleton w-64 h-64 rounded-full"></div>
                <div x-show="!isLoading" id="status-donut" class="w-full h-full" x-cloak></div>
            </div>
        </div>
    </div>
</div>

<!-- Grid Daftar Informasi -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
    <!-- Daftar Trial Hampir Habis -->
    <div class="card bg-base-100 shadow-lg border border-base-300/50" data-aos="fade-up">
        <div class="card-body">
            <h2 class="card-title">Trial Hampir Habis (≤7 hari)</h2>
            @if($trialsSoon->isEmpty())
                <div class="text-center py-8 text-base-content/60">Tidak ada trial yang akan berakhir.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Lead</th><th class="hidden sm:table-cell">Toko</th><th class="text-right">Berakhir Dalam</th></tr></thead>
                        <tbody>
                        @foreach($trialsSoon as $lead)
                            <tr>
                                <td><a href="{{ route('leads.show', $lead) }}" class="link link-hover link-primary font-medium">{{ $lead->name }}</a></td>
                                <td class="hidden sm:table-cell text-base-content/80">{{ $lead->store_name }}</td>
                                <td class="text-right text-base-content/80">{{ optional($lead->trial_ends_at)->diffForHumans(['short' => true]) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Daftar Tugas Saya Hari Ini -->
    <div class="card bg-base-100 shadow-lg border border-base-300/50" data-aos="fade-up" data-aos-delay="100">
        <div class="card-body">
            <div class="flex justify-between items-center">
                <h2 class="card-title">Tugas Saya (Hari Ini)</h2>
                <a href="{{ route('tasks.index') }}" class="btn btn-ghost btn-xs">Lihat Semua</a>
            </div>
            @if(empty($taskSummary['mineToday']) || collect($taskSummary['mineToday'])->isEmpty())
                <div class="text-center py-8 text-base-content/60">Tidak ada tugas jatuh tempo hari ini.</div>
            @else
                <ul class="space-y-3 mt-2">
                    @foreach($taskSummary['mineToday'] as $t)
                        <li class="flex items-center justify-between gap-3 p-2 rounded-lg hover:bg-base-200">
                            <div class="min-w-0">
                                <p class="font-medium truncate">{{ $t->title }}</p>
                                <p class="text-xs text-base-content/60">{{ strtoupper($t->status) }}</p>
                            </div>
                            <div class="badge badge-outline">{{ ucfirst($t->priority) }}</div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>

<!-- Daftar Aktivitas Terbaru -->
<div class="card bg-base-100 shadow-lg border border-base-300/50 mt-6" data-aos="fade-up">
    <div class="card-body">
        <h2 class="card-title mb-4">Aktivitas Terbaru</h2>
        <div class="space-y-4">
            @forelse($recentActivities as $activity)
                <div class="flex items-start gap-4">
                    <div class="avatar placeholder">
                        <div class="bg-secondary text-secondary-content rounded-full w-10 h-10 flex items-center justify-center">
                            @php $d = Str::lower($activity->description ?? ''); @endphp
                            @if(Str::contains($d, ['broadcast','kirim','wa']))
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                            @elseif(Str::contains($d, 'import'))
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                            @endif
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-base-content break-words">
                            @if($activity->causer)
                                <span class="font-semibold">{{ $activity->causer->name }}</span>
                            @endif
                            {{ $activity->description }}
                        </p>
                        <p class="text-xs text-base-content/60">{{ $activity->created_at?->diffForHumans() }}</p>
                    </div>
                </div>
            @empty
                <div class="text-center text-base-content/60 py-8">Belum ada aktivitas terbaru.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Memastikan ApexCharts tersedia secara global untuk skrip inline --}}
<script src="https://cdn.jsdelivr.net/npm/apexcharts" defer></script>

<script>
document.addEventListener('alpine:init', () => {
    // Komponen Alpine untuk Chart Aktivitas Utama (Area Chart)
    Alpine.data('mainChart', () => ({
        isLoading: true,
        chart: null,
        init() {
            let chartData;
            try {
                // Parsing data dari atribut data-*
                chartData = JSON.parse(this.$el.dataset.chartData || '{}');
            } catch (e) {
                console.error('Error parsing chart data JSON:', e);
                this.isLoading = false;
                return;
            }

            // Menunggu ApexCharts siap
            const checkApex = () => {
                if (window.ApexCharts) {
                    setTimeout(() => {
                        this.isLoading = false;
                        this.$nextTick(() => this.renderChart(chartData));
                    }, 500);

                    // Observer untuk perubahan tema
                    const observer = new MutationObserver((mutations) => {
                        mutations.forEach((mutation) => {
                            if (mutation.type === 'attributes' && mutation.attributeName === 'data-theme') {
                                this.renderChart(chartData);
                            }
                        });
                    });
                    observer.observe(document.documentElement, { attributes: true });
                } else {
                    // Coba lagi jika library belum ter-load
                    setTimeout(checkApex, 50);
                }
            };
            checkApex();
        },
        renderChart(data) {
            const el = this.$el.querySelector("#main-chart");
            if (!el) return;
            if (this.chart) this.chart.destroy();

            if (!data || !data.categories?.length) {
                el.innerHTML = `<div class="flex items-center justify-center h-full text-base-content/60">Data chart tidak tersedia.</div>`;
                return;
            }

            const isDark = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(226, 232, 240, 0.6)';
            const labelColor = isDark ? '#9ca3af' : '#64748b';

            const options = {
                series: [ { name: 'Leads Baru', data: data.leads || [] }, { name: 'Pesan Terkirim', data: data.messages || [] } ],
                chart: { type: 'area', height: '100%', toolbar: { show: false }, zoom: { enabled: false }, background: 'transparent' },
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 3 },
                colors: ['#3B82F6', '#10B981'],
                fill: { type: 'gradient', gradient: { opacityFrom: 0.5, opacityTo: 0.05, stops: [0, 95, 100] } },
                xaxis: { type: 'datetime', categories: data.categories, labels: { style: { colors: labelColor } }, axisBorder: { show: false }, axisTicks: { show: false }, tooltip: { enabled: false } },
                yaxis: { labels: { style: { colors: labelColor } }, min: 0, forceNiceScale: true },
                tooltip: { theme: isDark ? 'dark' : 'light', x: { format: 'dd MMM yyyy' } },
                grid: { borderColor: gridColor, strokeDashArray: 4, yaxis: { lines: { show: true } } },
                legend: { position: 'top', horizontalAlign: 'right', labels: { colors: labelColor } }
            };
            this.chart = new ApexCharts(el, options);
            this.chart.render();
        }
    }));

    // Komponen Alpine untuk Chart Komposisi Status (Donut Chart)
    Alpine.data('statusDonut', () => ({
        isLoading: true,
        chart: null,
        init() {
            let donutData;
             try {
                donutData = JSON.parse(this.$el.dataset.donutData || '{}');
            } catch (e) {
                console.error('Error parsing donut data JSON:', e);
                this.isLoading = false;
                return;
            }

            const checkApex = () => {
                if (window.ApexCharts) {
                     setTimeout(() => {
                        this.isLoading = false;
                        this.$nextTick(() => this.renderChart(donutData));
                    }, 500);

                    const observer = new MutationObserver(() => this.renderChart(donutData));
                    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
                } else {
                    setTimeout(checkApex, 50);
                }
            };
            checkApex();
        },
        renderChart(data) {
            const el = this.$el.querySelector("#status-donut");
            if (!el) return;
            if (this.chart) this.chart.destroy();

            if (!data || !data.series?.length) {
                el.innerHTML = `<div class="flex items-center justify-center h-full text-base-content/60">Tidak ada data status.</div>`;
                return;
            }

            const isDark = document.documentElement.classList.contains('dark');
            const labelColor = isDark ? '#9ca3af' : '#64748b';

            const options = {
                series: data.series,
                labels: data.labels,
                chart: { type: 'donut', height: '100%', background: 'transparent' },
                legend: { position: 'bottom', labels: { colors: labelColor } },
                colors: ['#3B82F6', '#10B981', '#64748B', '#F59E0B', '#EF4444', '#8B5CF6'],
                dataLabels: { enabled: true, formatter: (val) => `${val.toFixed(1)}%` },
                plotOptions: { pie: { donut: { size: '65%' } } },
                tooltip: { theme: isDark ? 'dark' : 'light', y: { formatter: (v) => `${v} leads` } }
            };
            this.chart = new ApexCharts(el, options);
            this.chart.render();
        }
    }));
});
</script>
@endpush

