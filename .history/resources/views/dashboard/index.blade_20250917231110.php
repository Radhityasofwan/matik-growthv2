@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    // Mengimpor Str facade untuk digunakan di dalam view.
    use Illuminate\Support\Str;

    // Persiapan data dari controller
    $stats          = $stats ?? [];
    $chartData      = $chartData ?? ['categories'=>[], 'leads'=>[], 'messages'=>[]];
    $statusDonut    = $statusDonut ?? ['labels'=>[], 'series'=>[]];
    $subSummary     = $subSummary ?? ['total'=>0,'active'=>0,'paused'=>0,'cancelled'=>0,'mrr'=>0];
    $taskSummary    = $taskSummary ?? ['open'=>0,'in_progress'=>0,'done'=>0,'mineToday'=>collect()];
    $campaignSummary= $campaignSummary ?? ['total'=>0,'by_status'=>[],'latest'=>collect()];
    $trialsSoon     = $trialsSoon ?? collect();
    $recentActivities = $recentActivities ?? collect();

    // Logika kalkulasi untuk stat cards
    $connected = $stats['connectedSenders'] ?? null;
    $active    = $stats['activeSenders'] ?? 0;
    $total     = $stats['totalSenders'] ?? 0;

    $senderLabelCount = is_null($connected) ? "{$active} / {$total}" : "{$connected} / {$total}";
    $senderLabelDesc  = is_null($connected)
        ? (($active === $total && $total > 0) ? 'Semua sender diaktifkan' : 'Sebagian sender nonaktif')
        : (($connected === $total && $total > 0) ? 'Semua sesi tersambung' : (($total - $connected) . ' sesi belum tersambung'));

    $leadsChange = ($stats['leadsThisWeek'] ?? 0) - ($stats['leadsPreviousWeek'] ?? 0);
    $sentChange  = ($stats['messagesSentLast7Days'] ?? 0) - ($stats['messagesSentPrevious7Days'] ?? 0);
    $sentPct     = ($stats['messagesSentPrevious7Days'] ?? 0) > 0 ? ($sentChange / $stats['messagesSentPrevious7Days']) * 100 : 0;
    $replyChange = ($stats['replyRate'] ?? 0) - ($stats['replyRatePrevious'] ?? 0);
@endphp

<!-- Header Halaman -->
<div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-8" data-aos="fade-down">
    <div>
        <h1 class="text-3xl font-bold text-base-content">Dashboard</h1>
        <p class="mt-1 text-base-content/70">
            Ringkasan aktivitas CRM Anda hari ini, {{ auth()->user()?->name ?? 'User' }}.
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('leads.index') }}" class="btn btn-outline btn-primary">Kelola Leads</a>
        <a href="{{ route('whatsapp.broadcast.create') }}" class="btn btn-primary">Broadcast Baru</a>
    </div>
</div>

<!-- Stat Cards Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6">
    <x-card.stat data-aos="fade-up" data-aos-delay="50" title="Total Leads" value="{{ number_format($stats['totalLeads'] ?? 0) }}" change="{{ $leadsChange >= 0 ? '▲' : '▼' }} {{ number_format(abs($leadsChange)) }} minggu ini" changeType="{{ $leadsChange >= 0 ? 'success' : 'error' }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
    </x-card.stat>
    <x-card.stat data-aos="fade-up" data-aos-delay="100" title="Pesan Terkirim (7h)" value="{{ number_format($stats['messagesSentLast7Days'] ?? 0) }}" change="{{ $sentChange >= 0 ? '▲' : '▼' }} {{ number_format(abs($sentPct), 1) }}% vs 7 hari lalu" changeType="{{ $sentChange >= 0 ? 'success' : 'error' }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
    </x-card.stat>
    <x-card.stat data-aos="fade-up" data-aos-delay="150" title="Tingkat Balasan" value="{{ number_format($stats['replyRate'] ?? 0, 1) }}%" change="{{ $replyChange >= 0 ? '▲' : '▼' }} {{ number_format(abs($replyChange), 1) }}% vs 7 hari lalu" changeType="{{ $replyChange >= 0 ? 'success' : 'error' }}">
         <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"/></svg>
    </x-card.stat>
    <x-card.stat data-aos="fade-up" data-aos-delay="200" title="{{ is_null($connected) ? 'WA Sender Aktif' : 'WA Sender Tersambung' }}" value="{{ $senderLabelCount }}" change="{{ $senderLabelDesc }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
    </x-card.stat>
    <x-card.stat data-aos="fade-up" data-aos-delay="250" title="MRR (Active)" value="Rp {{ number_format($subSummary['mrr'] ?? 0, 0, ',', '.') }}" change="Subs aktif: {{ number_format($subSummary['active'] ?? 0) }}">
         <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
    </x-card.stat>
    <x-card.stat data-aos="fade-up" data-aos-delay="300" title="Tugas Terbuka" value="{{ ($taskSummary['open'] ?? 0) + ($taskSummary['in_progress'] ?? 0) }}" change="Open: {{ $taskSummary['open'] ?? 0 }} • Progress: {{ $taskSummary['in_progress'] ?? 0 }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="20 6 9 17 4 12"/></svg>
    </x-card.stat>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <!-- Chart Aktivitas -->
    <div class="card bg-base-100 shadow-lg border border-base-300/50 lg:col-span-2" data-aos="fade-up">
        <div class="card-body">
            <h2 class="card-title">Aktivitas 30 Hari Terakhir</h2>
            <div x-data="apexChart" x-init="init()" class="w-full h-80 min-h-[320px] mt-4">
                <div x-show="isLoading" class="skeleton w-full h-full rounded-xl"></div>
                <div id="main-chart" x-show="!isLoading" class="w-full h-full" x-cloak></div>
            </div>
        </div>
    </div>

    <!-- Chart Komposisi Leads -->
    <div class="card bg-base-100 shadow-lg border border-base-300/50" data-aos="fade-up" data-aos-delay="100">
        <div class="card-body">
            <h2 class="card-title">Komposisi Status Leads</h2>
            <div x-data="donutChart" x-init="init()" class="w-full h-80 min-h-[320px] flex items-center justify-center">
                <div x-show="isLoading" class="skeleton w-64 h-64 rounded-full"></div>
                <div id="status-donut" x-show="!isLoading" class="w-full h-full" x-cloak></div>
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
            @if($taskSummary['mineToday']->isEmpty())
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
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script type="application/json" id="dashboard-chart-data">@json($chartData)</script>
<script type="application/json" id="dashboard-status-donut">@json($statusDonut)</script>

<script>
/**
 * UTIL: Ambil palet warna dari DaisyUI (CSS var HSL) dengan fallback warna Tailwind.
 */
function getThemePalette() {
    const styles = getComputedStyle(document.documentElement);
    const keys = ['--p','--s','--a','--in','--su','--wa','--er','--nc'];
    const fallbacks = ['#6366F1','#06B6D4','#F43F5E','#0EA5E9','#22C55E','#F59E0B','#EF4444','#111827'];
    return keys.map((k, i) => {
        const v = styles.getPropertyValue(k).trim();
        return v ? `hsl(${v})` : fallbacks[i];
    });
}
function getNeutralColor(alpha = 1) {
    const styles = getComputedStyle(document.documentElement);
    const bc = styles.getPropertyValue('--bc').trim(); // base-content
    return alpha === 1 ? `hsl(${bc})` : `hsl(${bc} / ${alpha})`;
}
function isDarkMode() {
    return document.documentElement.classList.contains('dark');
}

document.addEventListener('alpine:init', () => {
    // Komponen Alpine untuk Chart Area (Aktivitas)
    Alpine.data('apexChart', () => ({
        isLoading: true,
        chart: null,
        init() {
            const data = JSON.parse(document.getElementById('dashboard-chart-data').textContent || '{}');
            const render = () => {
                this.isLoading = false;
                this.$nextTick(() => this.renderChart(data));
            };
            if (window.ApexCharts) render(); else document.addEventListener('DOMContentLoaded', render);

            // Re-render saat tema (data-theme) berubah
            const observer = new MutationObserver(() => this.renderChart(data));
            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme','class'] });

            // Re-flow on resize untuk jaga proporsional
            window.addEventListener('resize', () => {
                if (this.chart) this.chart.render();
            }, { passive: true });
        },
        renderChart(data) {
            const el = document.getElementById("main-chart");
            if (!el || typeof ApexCharts === 'undefined') return;

            // Hancurkan chart lama bila ada
            if (this.chart) { try { this.chart.destroy(); } catch(e){} this.chart = null; }

            // Empty state
            if (!data.categories?.length) {
                el.innerHTML = `<div class="flex items-center justify-center h-full text-base-content/60">Data tidak tersedia.</div>`;
                return;
            }

            const palette = getThemePalette();
            const labelColor = getNeutralColor(0.7);
            const gridColor  = getNeutralColor(0.12);

            const options = {
                series: [
                    { name: 'Leads Baru',     data: data.leads || [] },
                    { name: 'Pesan Terkirim', data: data.messages || [] }
                ],
                chart: {
                    type: 'area',
                    height: '100%',
                    background: 'transparent',
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 700,
                        animateGradually: { enabled: true, delay: 120 },
                        dynamicAnimation: { enabled: true, speed: 450 }
                    },
                    dropShadow: {
                        enabled: true,
                        top: 4, left: 0, blur: 6, opacity: 0.15
                    }
                },
                theme: { mode: isDarkMode() ? 'dark' : 'light' },
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 3 },
                colors: [palette[0], palette[2]], // primary & accent
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.55,
                        opacityTo: 0.08,
                        stops: [0, 60, 95, 100]
                    }
                },
                markers: {
                    size: 0,
                    strokeWidth: 2,
                    hover: { size: 7 },
                    strokeColors: getNeutralColor(1)
                },
                xaxis: {
                    type: 'datetime',
                    categories: data.categories,
                    labels: { style: { colors: labelColor } },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    crosshairs: {
                        show: true,
                        width: 1,
                        position: 'back',
                        stroke: { color: gridColor, width: 1, dashArray: 4 }
                    },
                    tooltip: { enabled: false }
                },
                yaxis: {
                    min: 0,
                    forceNiceScale: true,
                    labels: { style: { colors: labelColor } }
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    theme: isDarkMode() ? 'dark' : 'light',
                    x: { format: 'dd MMM yyyy' },
                    y: {
                        formatter: (v) => (v ?? 0).toLocaleString()
                    }
                },
                grid: {
                    borderColor: gridColor,
                    strokeDashArray: 4,
                    padding: { left: 8, right: 8 }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    labels: { colors: labelColor },
                    markers: { radius: 12 }
                },
                responsive: [
                    { breakpoint: 1024, options: { stroke: { width: 2 } } },
                    { breakpoint: 640,  options: { legend: { position: 'bottom', horizontalAlign: 'center' } } }
                ]
            };

            this.chart = new ApexCharts(el, options);
            this.chart.render();
        }
    }));

    // Komponen Alpine untuk Donut Chart (Komposisi Leads)
    Alpine.data('donutChart', () => ({
        isLoading: true,
        chart: null,
        init() {
            const data = JSON.parse(document.getElementById('dashboard-status-donut').textContent || '{}');
            const render = () => {
                this.isLoading = false;
                this.$nextTick(() => this.renderChart(data));
            };
            if (window.ApexCharts) render(); else document.addEventListener('DOMContentLoaded', render);

            const observer = new MutationObserver(() => this.renderChart(data));
            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme','class'] });
        },
        renderChart(data) {
            const el = document.getElementById("status-donut");
            if (!el || typeof ApexCharts === 'undefined') return;

            // Hancurkan chart lama bila ada
            if (this.chart) { try { this.chart.destroy(); } catch(e){} this.chart = null; }

            if (!data.series?.length || data.series.every(item => Number(item) === 0)) {
                el.innerHTML = `<div class="flex items-center justify-center h-full text-base-content/60">Tidak ada data status.</div>`;
                return;
            }

            const palette    = getThemePalette(); // ambil dari tema aktif
            const labelColor = getNeutralColor(0.75);
            const totalColor = getNeutralColor(1);

            // Susun palet donut: variasi cerah yang konsisten
            const donutColors = [
                palette[0], // primary
                palette[1], // secondary
                palette[2], // accent
                palette[3], // info
                palette[4], // success
                palette[5], // warning
                palette[6]  // error
            ];

            const options = {
                series: data.series,
                labels: data.labels,
                chart: {
                    type: 'donut',
                    height: '100%',
                    background: 'transparent',
                    animations: { enabled: true, dynamicAnimation: { enabled: true, speed: 400 } }
                },
                theme: { mode: isDarkMode() ? 'dark' : 'light' },
                legend: {
                    position: 'bottom',
                    labels: { colors: labelColor },
                    markers: { radius: 10 }
                },
                colors: donutColors,
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'light',
                        type: 'vertical',
                        opacityFrom: 0.95,
                        opacityTo: 0.85,
                        stops: [0, 50, 100]
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: (val) => `${val.toFixed(1)}%`,
                    style: { fontWeight: 600 },
                    dropShadow: { enabled: true, top: 1, left: 1, blur: 2, opacity: 0.35 }
                },
                plotOptions: {
                    pie: {
                        expandOnClick: true,
                        offsetY: 0,
                        donut: {
                            size: '72%',
                            background: 'transparent',
                            labels: {
                                show: true,
                                name: { show: true, color: labelColor, offsetY: 8 },
                                value: {
                                    show: true,
                                    formatter: (v) => Number(v || 0).toLocaleString(),
                                    color: labelColor,
                                    offsetY: -8
                                },
                                total: {
                                    show: true,
                                    label: 'Total Leads',
                                    color: totalColor,
                                    formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString()
                                }
                            }
                        }
                    }
                },
                states: {
                    hover: { filter: { type: 'lighten', value: 0.08 } },
                    active: { filter: { type: 'darken', value: 0.2 } }
                },
                tooltip: {
                    theme: isDarkMode() ? 'dark' : 'light',
                    y: { formatter: (v) => `${Number(v || 0).toLocaleString()} leads` }
                },
                responsive: [
                    { breakpoint: 640, options: { plotOptions: { pie: { donut: { size: '68%' } } } } }
                ]
            };

            this.chart = new ApexCharts(el, options);
            this.chart.render();
        }
    }));
});
</script>
@endpush
