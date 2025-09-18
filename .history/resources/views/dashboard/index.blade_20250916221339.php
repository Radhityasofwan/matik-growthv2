@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    // Data preparation logic remains unchanged
    $stats = $stats ?? [];
    $chartData = $chartData ?? ['categories'=>[], 'leads'=>[], 'messages'=>[]];
    $statusDonut = $statusDonut ?? ['labels'=>[], 'series'=>[]];
    $subSummary = $subSummary ?? ['total'=>0,'active'=>0,'paused'=>0,'cancelled'=>0,'mrr'=>0];
    $taskSummary = $taskSummary ?? ['open'=>0,'in_progress'=>0,'done'=>0,'mineToday'=>collect()];
    $campaignSummary = $campaignSummary ?? ['total'=>0,'by_status'=>[],'latest'=>collect()];
    $trialsSoon = $trialsSoon ?? collect();
    $recentActivities = $recentActivities ?? collect();
    $leadsChange = ($stats['leadsThisWeek'] ?? 0) - ($stats['leadsPreviousWeek'] ?? 0);
    $sentChange = ($stats['messagesSentLast7Days'] ?? 0) - ($stats['messagesSentPrevious7Days'] ?? 0);
    $sentPct = ($stats['messagesSentPrevious7Days'] ?? 0) > 0 ? ($sentChange / $stats['messagesSentPrevious7Days']) * 100 : 0;
    $replyChange = ($stats['replyRate'] ?? 0) - ($stats['replyRatePrevious'] ?? 0);
    $connected = $stats['connectedSenders'] ?? null;
    $active    = $stats['activeSenders'] ?? 0;
    $total     = $stats['totalSenders'] ?? 0;
    $senderLabelCount = is_null($connected) ? "{$active} / {$total}" : "{$connected} / {$total}";
    $senderLabelDesc  = is_null($connected)
        ? (($active === $total && $total > 0) ? 'Semua sender diaktifkan' : 'Sebagian sender nonaktif')
        : (($connected === $total && $total > 0) ? 'Semua sesi tersambung' : (($total - $connected) . ' sesi belum tersambung'));
@endphp

<!-- Header -->
<div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-6" data-aos="fade-down">
    <div>
        <h1 class="text-3xl font-bold text-neutral">Dashboard</h1>
        <p class="mt-1 text-neutral/60">Selamat datang kembali, {{ auth()->user()?->name ?? 'User' }}!</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('leads.create') }}" class="btn btn-outline btn-primary"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h14"/><path d="M12 5v14"/></svg>Lead Baru</a>
        <a href="{{ route('whatsapp.broadcast.create') }}" class="btn btn-primary"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>Broadcast Baru</a>
    </div>
</div>

<!-- Stat Cards Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
    <div data-aos="fade-up" data-aos-delay="50">
        <x-card.stat title="Total Leads" value="{{ number_format($stats['totalLeads'] ?? 0) }}" change="{{ $leadsChange >= 0 ? '▲' : '▼' }} {{ number_format(abs($leadsChange)) }} minggu ini" changeType="{{ $leadsChange >= 0 ? 'success' : 'error' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </x-card.stat>
    </div>
    <div data-aos="fade-up" data-aos-delay="100">
        <x-card.stat title="Pesan Terkirim (7h)" value="{{ number_format($stats['messagesSentLast7Days'] ?? 0) }}" change="{{ $sentChange >= 0 ? '▲' : '▼' }} {{ number_format(abs($sentPct), 1) }}% vs 7 hari lalu" changeType="{{ $sentChange >= 0 ? 'success' : 'error' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        </x-card.stat>
    </div>
    <div data-aos="fade-up" data-aos-delay="150">
        <x-card.stat title="Tingkat Balasan" value="{{ number_format($stats['replyRate'] ?? 0, 1) }}%" change="{{ $replyChange >= 0 ? '▲' : '▼' }} {{ number_format(abs($replyChange), 1) }}% vs 7 hari lalu" changeType="{{ $replyChange >= 0 ? 'success' : 'error' }}">
             <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"/></svg>
        </x-card.stat>
    </div>
    <div data-aos="fade-up" data-aos-delay="200">
        <x-card.stat title="{{ is_null($connected) ? 'WA Sender Aktif' : 'WA Sender Tersambung' }}" value="{{ $senderLabelCount }}" change="{{ $senderLabelDesc }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        </x-card.stat>
    </div>
     <div data-aos="fade-up" data-aos-delay="250">
        <x-card.stat title="MRR (Active)" value="Rp {{ number_format($subSummary['mrr'] ?? 0, 0, ',', '.') }}" change="Subs aktif: {{ number_format($subSummary['active'] ?? 0) }}">
             <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
        </x-card.stat>
    </div>
     <div data-aos="fade-up" data-aos-delay="300">
        <x-card.stat title="Tugas Terbuka" value="{{ ($taskSummary['open'] ?? 0) + ($taskSummary['in_progress'] ?? 0) }}" change="Open: {{ $taskSummary['open'] ?? 0 }} • Progress: {{ $taskSummary['in_progress'] ?? 0 }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="20 6 9 17 4 12"/></svg>
        </x-card.stat>
    </div>
</div>

<!-- Charts Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <div class="card bg-base-100 shadow-sm border border-base-300/50 lg:col-span-2" data-aos="fade-up">
        <div class="card-body">
            <h2 class="card-title">Aktivitas 30 Hari Terakhir</h2>
            <div x-data="mainChart(@json($chartData))" class="w-full h-80 min-h-[320px]">
                <div x-show="isLoading" class="skeleton w-full h-full"></div>
                <div x-show="!isLoading" id="main-chart" x-cloak></div>
            </div>
        </div>
    </div>
    <div class="card bg-base-100 shadow-sm border border-base-300/50" data-aos="fade-up" data-aos-delay="100">
        <div class="card-body">
            <h2 class="card-title">Komposisi Status Leads</h2>
            <div x-data="statusDonut(@json($statusDonut))" class="w-full h-80 min-h-[320px]">
                <div x-show="isLoading" class="skeleton w-full h-full rounded-full"></div>
                <div x-show="!isLoading" id="status-donut" x-cloak></div>
            </div>
        </div>
    </div>
</div>

{{-- Other sections remain the same --}}

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        Alpine.data('mainChart', (chartData) => ({
            // PERBAIKAN: Menambahkan properti 'isLoading'
            isLoading: true,
            init() {
                setTimeout(() => {
                    this.isLoading = false;
                    this.$nextTick(() => this.renderChart(chartData));
                }, 500);
            },
            renderChart(data) {
                const el = this.$el.querySelector("#main-chart");
                if (!el || !data || !data.categories?.length) {
                    el.innerHTML = '<div class="flex items-center justify-center h-full text-neutral/60">Data chart tidak tersedia.</div>';
                    return;
                }
                const options = {
                    series: [{ name: 'Leads Baru', data: data.leads || [] }, { name: 'Pesan Terkirim', data: data.messages || [] }],
                    chart: { type: 'area', height: 320, toolbar: { show: false }, zoom: { enabled: false } },
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 2 },
                    colors: ['#3B82F6', '#10B981'],
                    fill: { type: 'gradient', gradient: { opacityFrom: 0.6, opacityTo: 0.05, stops: [0, 95, 100] } },
                    xaxis: { type: 'datetime', categories: data.categories, labels: { style: { colors: '#9aa4b2' } }, axisBorder: { show: false }, axisTicks: { show: false } },
                    yaxis: { labels: { style: { colors: '#9aa4b2' } }, min: 0, forceNiceScale: true },
                    tooltip: { x: { format: 'dd MMM yyyy' } },
                    grid: { borderColor: '#e5e7eb20', strokeDashArray: 4, yaxis: { lines: { show: true } } },
                    legend: { position: 'top', horizontalAlign: 'right', labels: { colors: '#64748B' } }
                };
                new ApexCharts(el, options).render();
            }
        }));

        Alpine.data('statusDonut', (donutData) => ({
            // PERBAIKAN: Menambahkan properti 'isLoading'
            isLoading: true,
            init() {
                setTimeout(() => {
                    this.isLoading = false;
                    this.$nextTick(() => this.renderChart(donutData));
                }, 500);
            },
            renderChart(data) {
                const el = this.$el.querySelector("#status-donut");
                if (!el || !data || !data.series?.length) {
                    el.innerHTML = '<div class="flex items-center justify-center h-full text-neutral/60">Tidak ada data status.</div>';
                    return;
                }
                const options = {
                    series: data.series,
                    labels: data.labels,
                    chart: { type: 'donut', height: 320 },
                    legend: { position: 'bottom', labels: { colors: '#64748B' } },
                    colors: ['#60A5FA', '#34D399', '#94A3B8', '#F59E0B', '#EF4444', '#a855f7'],
                    dataLabels: { enabled: true, formatter: (val) => `${val.toFixed(1)}%` },
                    plotOptions: { pie: { donut: { size: '65%' } } },
                    tooltip: { y: { formatter: (v) => `${v} leads` } }
                };
                new ApexCharts(el, options).render();
            }
        }));
    });
</script>
@endpush

