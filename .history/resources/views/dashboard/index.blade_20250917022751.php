@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    // Mengimpor Str facade agar bisa digunakan di dalam view.
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


<!-- SINKRONISASI: Menambahkan Grid untuk Charts -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <!-- Chart Aktivitas -->
    <div class="card bg-base-100 shadow-lg border border-base-300/50 lg:col-span-2" data-aos="fade-up">
        <div class="card-body">
            <h2 class="card-title">Aktivitas 30 Hari Terakhir</h2>
            {{-- FIXED: Menggunakan komponen chart SVG manual --}}
            <div x-data="manualAreaChart(@json($chartData))" x-init="init()" class="w-full h-80 min-h-[320px] relative">
                 <template x-if="!isLoading && points.length > 0">
                    <div class="w-full h-full">
                        <svg :viewBox="`0 0 ${width} ${height}`" class="overflow-visible">
                            <!-- Grid lines -->
                            <g class="grid-lines">
                                <template x-for="line in yAxisLabels" :key="line.y">
                                    <line :x1="0" :x2="width" :y1="line.y" :y2="line.y" class="stroke-base-content/10" stroke-width="1" stroke-dasharray="2"></line>
                                </template>
                            </g>
                            <!-- Paths (Leads & Messages) -->
                            <path :d="areaPathLeads" :fill="themeColors.primary" fill-opacity="0.1"></path>
                            <path :d="linePathLeads" :stroke="themeColors.primary" stroke-width="2" fill="none"></path>
                            <path :d="areaPathMessages" :fill="themeColors.secondary" fill-opacity="0.1"></path>
                            <path :d="linePathMessages" :stroke="themeColors.secondary" stroke-width="2" fill="none"></path>

                            <!-- Axes labels -->
                            <g class="y-axis-labels">
                                <template x-for="line in yAxisLabels" :key="line.label">
                                    <text :x="-10" :y="line.y" text-anchor="end" alignment-baseline="middle" class="text-xs" :fill="themeColors.text" x-text="line.label"></text>
                                </template>
                            </g>
                            <g class="x-axis-labels">
                                <template x-for="(point, index) in points" :key="index">
                                    <text x-show="index % Math.floor(points.length / 5) === 0" :x="point.x" :y="height + 20" text-anchor="middle" class="text-xs" :fill="themeColors.text" x-text="new Date(point.date).toLocaleDateString('id-ID', {day:'numeric', month:'short'})"></text>
                                </template>
                            </g>
                        </svg>
                        <!-- Tooltip -->
                        <div x-show="tooltip.visible" :style="`top: ${tooltip.y}px; left: ${tooltip.x}px;`" class="absolute pointer-events-none transform -translate-x-1/2 -translate-y-full bg-neutral text-neutral-content p-2 rounded-md shadow-lg text-xs">
                            <div x-text="tooltip.date" class="font-bold mb-1"></div>
                            <div>Leads: <span x-text="tooltip.leads" class="font-semibold"></span></div>
                            <div>Pesan: <span x-text="tooltip.messages" class="font-semibold"></span></div>
                        </div>
                    </div>
                 </template>
                 <div x-show="isLoading" class="skeleton w-full h-full"></div>
                 <div x-show="!isLoading && points.length === 0" class="flex items-center justify-center h-full text-base-content/60">Data tidak tersedia.</div>
            </div>
        </div>
    </div>

    <!-- Chart Komposisi Leads -->
    <div class="card bg-base-100 shadow-lg border border-base-300/50" data-aos="fade-up" data-aos-delay="100">
        <div class="card-body">
            <h2 class="card-title">Komposisi Status Leads</h2>
            {{-- FIXED: Menggunakan komponen chart SVG manual --}}
            <div x-data="manualDonutChart(@json($statusDonut))" x-init="init()" class="w-full h-80 min-h-[320px] flex flex-col items-center justify-center gap-4">
                <template x-if="!isLoading && segments.length > 0">
                    <div class="relative w-48 h-48">
                         <svg viewBox="0 0 36 36" class="w-full h-full">
                            <template x-for="(segment, index) in segments" :key="index">
                                <circle
                                    class="transform-gpu origin-center"
                                    :stroke="segment.color"
                                    cx="18" cy="18" r="15.915"
                                    fill="transparent"
                                    stroke-width="4"
                                    :stroke-dasharray="`${segment.percentage} ${100 - segment.percentage}`"
                                    :style="`transform: rotate(${segment.offset - 90}deg)`"
                                ></circle>
                            </template>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <span class="text-2xl font-bold text-base-content" x-text="total"></span>
                            <span class="text-xs text-base-content/70">Total Leads</span>
                        </div>
                    </div>
                </template>
                <div x-show="isLoading" class="skeleton w-48 h-48 rounded-full"></div>
                <div x-show="!isLoading && segments.length === 0" class="flex items-center justify-center h-full text-base-content/60">Data tidak tersedia.</div>
                <!-- Legend -->
                <div class="flex flex-wrap justify-center gap-x-4 gap-y-2 text-xs">
                    <template x-for="(segment, index) in segments" :key="index">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full" :style="`background-color: ${segment.color}`"></span>
                            <span x-text="`${segment.label} (${segment.value})`"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grid Daftar Informasi (Struktur disesuaikan agar konsisten) -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
    <!-- Daftar Trial Hampir Habis -->
    <div class="card bg-base-100 shadow-lg border border-base-300/50" data-aos="fade-up">
        <div class="card-body">
            <h2 class="card-title">Trial Hampir Habis (≤7 hari)</h2>
            @if(collect($trialsSoon)->isEmpty())
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
            @if(collect($taskSummary['mineToday'])->isEmpty())
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
{{-- FIXED: Menghapus semua skrip terkait ApexCharts --}}
<script>
document.addEventListener('alpine:init', () => {
    // Komponen Alpine untuk Chart Area SVG Manual
    Alpine.data('manualAreaChart', (rawData) => ({
        isLoading: true,
        points: [],
        width: 500,
        height: 250,
        linePathLeads: '',
        areaPathLeads: '',
        linePathMessages: '',
        areaPathMessages: '',
        yAxisLabels: [],
        tooltip: { visible: false, x: 0, y: 0, date: '', leads: 0, messages: 0 },
        themeColors: {},
        init() {
            this.updateThemeColors();
            setTimeout(() => {
                const data = rawData || {};
                const leads = data.leads || [];
                const messages = data.messages || [];
                const categories = data.categories || [];

                if (categories.length === 0) {
                    this.isLoading = false;
                    return;
                }

                const allValues = [...leads, ...messages];
                const maxVal = Math.max(...allValues, 0);
                const yMax = Math.ceil(maxVal / 10) * 10 || 10;

                this.points = categories.map((cat, i) => ({
                    date: cat,
                    x: (i / (categories.length - 1)) * this.width,
                    yLeads: this.height - (leads[i] / yMax) * this.height,
                    yMessages: this.height - (messages[i] / yMax) * this.height,
                    leads: leads[i],
                    messages: messages[i]
                }));

                this.linePathLeads = this.points.map((p, i) => (i === 0 ? 'M' : 'L') + `${p.x},${p.yLeads}`).join(' ');
                this.areaPathLeads = this.linePathLeads + ` L${this.width},${this.height} L0,${this.height} Z`;

                this.linePathMessages = this.points.map((p, i) => (i === 0 ? 'M' : 'L') + `${p.x},${p.yMessages}`).join(' ');
                this.areaPathMessages = this.linePathMessages + ` L${this.width},${this.height} L0,${this.height} Z`;

                this.yAxisLabels = Array.from({length: 5}, (_, i) => ({
                    y: (i / 4) * this.height,
                    label: yMax - (i * (yMax/4))
                }));

                this.isLoading = false;

                // Event listener untuk tooltip
                this.$el.addEventListener('mousemove', (e) => {
                    const svgRect = this.$el.querySelector('svg').getBoundingClientRect();
                    const mouseX = e.clientX - svgRect.left;

                    const closestPoint = this.points.reduce((prev, curr) => Math.abs(curr.x - mouseX) < Math.abs(prev.x - mouseX) ? curr : prev);

                    this.tooltip.visible = true;
                    this.tooltip.x = closestPoint.x + (svgRect.left - this.$el.getBoundingClientRect().left);
                    this.tooltip.y = e.clientY - this.$el.getBoundingClientRect().top - 20; // offset tooltip
                    this.tooltip.date = new Date(closestPoint.date).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' });
                    this.tooltip.leads = closestPoint.leads;
                    this.tooltip.messages = closestPoint.messages;
                });
                this.$el.addEventListener('mouseleave', () => {
                    this.tooltip.visible = false;
                });

            }, 500);

             const observer = new MutationObserver(() => this.updateThemeColors());
             observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
        },
        updateThemeColors() {
            const styles = getComputedStyle(document.documentElement);
            this.themeColors = {
                primary: styles.getPropertyValue('--p').trim(),
                secondary: styles.getPropertyValue('--s').trim(),
                text: styles.getPropertyValue('--bc').trim()
            };
        }
    }));

    // Komponen Alpine untuk Chart Donut SVG Manual
    Alpine.data('manualDonutChart', (rawData) => ({
        isLoading: true,
        segments: [],
        total: 0,
        init() {
            setTimeout(() => {
                const data = rawData || {};
                const series = data.series || [];
                const labels = data.labels || [];
                const colors = ['#3B82F6', '#10B981', '#64748B', '#F59E0B', '#EF4444', '#8B5CF6'];

                this.total = series.reduce((sum, value) => sum + value, 0);

                if (this.total === 0) {
                    this.isLoading = false;
                    return;
                }

                let cumulativePercentage = 0;
                this.segments = series.map((value, index) => {
                    const percentage = (value / this.total) * 100;
                    const segment = {
                        label: labels[index] || `Data ${index+1}`,
                        value: value,
                        percentage: percentage,
                        offset: cumulativePercentage,
                        color: colors[index % colors.length]
                    };
                    cumulativePercentage += percentage;
                    return segment;
                });

                this.isLoading = false;
            }, 500);
        }
    }));
});
</script>
@endpush

