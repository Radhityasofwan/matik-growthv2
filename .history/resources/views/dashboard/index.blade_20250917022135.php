@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    use Illuminate\Support\Str;

    // ===== Defaults & guards (sinkron dgn controller) =====
    $stats            = $stats ?? [];
    $chartData        = $chartData ?? ['categories'=>[], 'leads'=>[], 'messages'=>[]];
    $statusDonut      = $statusDonut ?? ['labels'=>[], 'series'=>[]];
    $subSummary       = $subSummary ?? ['total'=>0,'active'=>0,'paused'=>0,'cancelled'=>0,'mrr'=>0];
    $taskSummary      = $taskSummary ?? ['open'=>0,'in_progress'=>0,'done'=>0,'mineToday'=>collect()];
    $campaignSummary  = $campaignSummary ?? ['total'=>0,'by_status'=>[],'latest'=>collect()];
    $trialsSoon       = collect($trialsSoon ?? []);
    $recentActivities = collect($recentActivities ?? []);

    // ===== Sender labels =====
    $connected = $stats['connectedSenders'] ?? null;
    $active    = $stats['activeSenders'] ?? 0;
    $total     = $stats['totalSenders'] ?? 0;

    $senderLabelCount = is_null($connected) ? "{$active} / {$total}" : "{$connected} / {$total}";
    $senderLabelDesc  = is_null($connected)
        ? (($active === $total && $total > 0) ? 'Semua sender diaktifkan' : 'Sebagian sender nonaktif')
        : (($connected === $total && $total > 0) ? 'Semua sesi tersambung' : (($total - $connected) . ' sesi belum tersambung'));

    // ===== Deltas untuk cards =====
    $leadsChange = ($stats['leadsThisWeek'] ?? 0) - ($stats['leadsPreviousWeek'] ?? 0);
    $sentChange  = ($stats['messagesSentLast7Days'] ?? 0) - ($stats['messagesSentPrevious7Days'] ?? 0);
    $sentPct     = ($stats['messagesSentPrevious7Days'] ?? 0) > 0 ? ($sentChange / max(1, $stats['messagesSentPrevious7Days'])) * 100 : 0;
    $replyChange = ($stats['replyRate'] ?? 0) - ($stats['replyRatePrevious'] ?? 0);
@endphp

<!-- Header -->
<div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-bold text-neutral">Dashboard</h1>
        <p class="mt-1 text-neutral/60">Ringkasan aktivitas CRM Anda hari ini, {{ auth()->user()?->name ?? 'User' }}.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('leads.index') }}" class="btn btn-outline btn-primary">Kelola Leads</a>
        <a href="{{ route('whatsapp.broadcast.create') }}" class="btn btn-primary">Broadcast Baru</a>
    </div>
</div>

<!-- Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-4">
    <!-- Total Leads -->
    <div class="card bg-base-100 shadow-md border border-base-300/50">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-neutral/60">Total Leads</div>
                <div class="bg-secondary p-2 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold mt-2">{{ number_format($stats['totalLeads'] ?? 0) }}</p>
            <p class="text-xs text-neutral/60 mt-1">
                <span class="{{ $leadsChange >= 0 ? 'text-success' : 'text-error' }} font-semibold">
                    {{ $leadsChange >= 0 ? '▲' : '▼' }} {{ number_format(abs($leadsChange)) }}
                </span> minggu ini
            </p>
        </div>
    </div>

    <!-- Pesan terkirim (7h) -->
    <div class="card bg-base-100 shadow-md border border-base-300/50">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-neutral/60">Pesan Terkirim (7h)</div>
                <div class="bg-secondary p-2 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold mt-2">{{ number_format($stats['messagesSentLast7Days'] ?? 0) }}</p>
            <p class="text-xs text-neutral/60 mt-1">
                <span class="{{ $sentChange >= 0 ? 'text-success' : 'text-error' }} font-semibold">
                    {{ $sentChange >= 0 ? '▲' : '▼' }} {{ number_format(abs($sentPct), 1) }}%
                </span> vs 7 hari lalu
            </p>
        </div>
    </div>

    <!-- Reply rate -->
    <div class="card bg-base-100 shadow-md border border-base-300/50">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-neutral/60">Tingkat Balasan</div>
                <div class="bg-secondary p-2 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold mt-2">{{ number_format($stats['replyRate'] ?? 0, 1) }}%</p>
            <p class="text-xs text-neutral/60 mt-1">
                <span class="{{ $replyChange >= 0 ? 'text-success' : 'text-error' }} font-semibold">
                    {{ $replyChange >= 0 ? '▲' : '▼' }} {{ number_format(abs($replyChange), 1) }}%
                </span> vs 7 hari lalu
            </p>
        </div>
    </div>

    <!-- Senders -->
    <div class="card bg-base-100 shadow-md border border-base-300/50">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-neutral/60">{{ is_null($connected) ? 'WA Sender Aktif' : 'WA Sender Tersambung' }}</div>
                <div class="bg-secondary p-2 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold mt-2">{{ $senderLabelCount }}</p>
            <p class="text-xs text-neutral/60 mt-1">{{ $senderLabelDesc }}</p>
        </div>
    </div>

    <!-- MRR -->
    <div class="card bg-base-100 shadow-md border border-base-300/50">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-neutral/60">MRR (Active)</div>
                <div class="bg-secondary p-2 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 3v18h18"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold mt-2">Rp {{ number_format($subSummary['mrr'] ?? 0, 0, ',', '.') }}</p>
            <p class="text-xs text-neutral/60 mt-1">Subs aktif: {{ number_format($subSummary['active'] ?? 0) }}</p>
        </div>
    </div>

    <!-- Tasks -->
    <div class="card bg-base-100 shadow-md border border-base-300/50">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-neutral/60">Tugas</div>
                <div class="bg-secondary p-2 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold mt-2">{{ ($taskSummary['open'] ?? 0) + ($taskSummary['in_progress'] ?? 0) }}</p>
            <p class="text-xs text-neutral/60 mt-1">Open: {{ $taskSummary['open'] ?? 0 }} • Progress: {{ $taskSummary['in_progress'] ?? 0 }} • Done: {{ $taskSummary['done'] ?? 0 }}</p>
        </div>
    </div>
</div>

<!-- Area chart -->
<div class="card bg-base-100 shadow-md border border-base-300/50 xl:col-span-2">
  <div class="card-body">
    <h2 class="card-title">Aktivitas 30 Hari Terakhir</h2>
    <div x-data="mainChart(@json($chartData))" x-init="init()" class="w-full h-80">
      <div id="main-chart"></div>
    </div>
  </div>
</div>

<!-- Donut -->
<div class="card bg-base-100 shadow-md border border-base-300/50">
  <div class="card-body">
    <h2 class="card-title">Status Leads</h2>
    <div x-data="statusDonut(@json($statusDonut))" x-init="init()" class="w-full h-80">
      <div id="status-donut"></div>
    </div>
  </div>
</div>

<!-- Lists -->
<div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-6 mt-6">
    <!-- Trial hampir habis -->
    <div class="card bg-base-100 shadow-md border border-base-300/50 xl:col-span-2">
        <div class="card-body">
            <h2 class="card-title">Trial Hampir Habis (≤7 hari)</h2>
            @if($trialsSoon->isEmpty())
                <p class="text-neutral/60">Tidak ada trial yang segera berakhir.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead>
                        <tr>
                            <th>Lead</th>
                            <th class="hidden sm:table-cell">Toko</th>
                            <th class="text-right">Habis</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($trialsSoon as $lead)
                            <tr>
                                <td class="truncate"><a href="{{ route('leads.show', $lead) }}" class="link link-primary">{{ $lead->name }}</a></td>
                                <td class="hidden sm:table-cell truncate">{{ $lead->store_name }}</td>
                                <td class="text-right">{{ optional($lead->trial_ends_at)->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Tugas saya hari ini -->
    <div class="card bg-base-100 shadow-md border border-base-300/50">
        <div class="card-body">
            <h2 class="card-title">Tugas Saya (Hari Ini)</h2>
            @php $mineToday = collect($taskSummary['mineToday'] ?? []); @endphp
            @if($mineToday->isEmpty())
                <p class="text-neutral/60">Tidak ada tugas jatuh tempo hari ini.</p>
            @else
                <ul class="space-y-3">
                    @foreach($mineToday as $t)
                        <li class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-medium truncate">{{ $t->title }}</p>
                                <p class="text-xs text-neutral/60">{{ strtoupper($t->status) }} @if($t->due_date) • Jatuh tempo: {{ \Illuminate\Support\Carbon::parse($t->due_date)->format('d M Y') }} @endif</p>
                            </div>
                            <span class="badge">{{ ucfirst($t->priority) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
            <div class="mt-4">
                <a href="{{ route('tasks.index') }}" class="btn btn-ghost btn-sm w-full">Lihat semua tugas</a>
            </div>
        </div>
    </div>

    <!-- Kampanye terbaru -->
    <div class="card bg-base-100 shadow-md border border-base-300/50">
        <div class="card-body">
            <h2 class="card-title">Kampanye Terbaru</h2>
            @php $latestC = collect($campaignSummary['latest'] ?? []); @endphp
            @if($latestC->isEmpty())
                <p class="text-neutral/60">Belum ada kampanye.</p>
            @else
                <ul class="space-y-3">
                    @foreach($latestC as $c)
                        <li class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-medium truncate">{{ $c->name }}</p>
                                <p class="text-xs text-neutral/60">Status: {{ ucfirst($c->status) }} • {{ $c->created_at?->diffForHumans() }}</p>
                            </div>
                            <a href="{{ route('campaigns.show', $c) }}" class="btn btn-ghost btn-xs">Detail</a>
                        </li>
                    @endforeach
                </ul>
            @endif
            <div class="mt-4">
                <a href="{{ route('campaigns.index') }}" class="btn btn-ghost btn-sm w-full">Lihat semua kampanye</a>
            </div>
        </div>
    </div>
</div>

<!-- Aktivitas Terbaru -->
<div class="card bg-base-100 shadow-md border border-base-300/50 mt-6">
    <div class="card-body">
        <h2 class="card-title mb-2">Aktivitas Terbaru</h2>
        <div class="space-y-4">
            @forelse($recentActivities as $activity)
                <div class="flex items-start">
                    <div class="avatar mr-4">
                        <div class="w-10 h-10 rounded-full bg-secondary text-primary flex items-center justify-center">
                            @php $d = Str::lower($activity->description ?? ''); @endphp
                            @if(Str::contains($d, ['broadcast','kirim','wa']))
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m3 11 18-5v12L3 14v-3z"/></svg>
                            @elseif(Str::contains($d, 'import'))
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/></svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 20h9"/><path d="M16 16l5-5-5-5"/><path d="M3 12h12"/></svg>
                            @endif
                        </div>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-neutral break-words">
                            @if($activity->causer)
                                <strong>{{ $activity->causer->name }}</strong>
                            @endif
                            {{ $activity->description }}
                        </p>
                        <p class="text-xs text-neutral/60">{{ $activity->created_at?->diffForHumans() }}</p>
                    </div>
                </div>
            @empty
                <div class="text-center text-neutral/60 py-8">Belum ada aktivitas.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- pastikan apexcharts dimuat lebih dulu (tanpa defer) --}}
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
/**
 * Komponen ringan untuk dipanggil via x-data="mainChart(@json($chartData))" x-init="init()"
 * Tidak mengubah arsitektur: tidak menambah store/global, tidak mengganggu Alpine yang ada.
 */
function mainChart(chartData) {
  return {
    init() {
      const el = document.querySelector("#main-chart");
      if (!el) return;

      // fallback teks jika data kosong
      if (!chartData || !(chartData.categories || []).length) {
        el.innerHTML = '<div class="flex items-center justify-center h-72 text-base-content/60">Data chart tidak tersedia.</div>';
        return;
      }

      const options = {
        series: [
          { name: 'Leads Baru',      data: chartData.leads || [] },
          { name: 'Pesan Terkirim',  data: chartData.messages || [] }
        ],
        chart: { type: 'area', height: 320, toolbar: { show: false }, zoom: { enabled: false }, background: 'transparent' },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 2 },
        colors: ['#3B82F6', '#10B981'],
        fill: { type: 'gradient', gradient: { opacityFrom: 0.55, opacityTo: 0.05, stops: [0,95,100] } },
        xaxis: {
          type: 'datetime',
          categories: chartData.categories,
          labels: { style: { colors: '#9aa4b2' } },
          axisBorder: { show: false }, axisTicks: { show: false }, tooltip: { enabled: false }
        },
        yaxis: { labels: { style: { colors: '#9aa4b2' } }, min: 0, forceNiceScale: true },
        tooltip: { x: { format: 'dd MMM yyyy' } },
        grid: { borderColor: '#e5e7eb40', strokeDashArray: 3, yaxis: { lines: { show: true } } },
        legend: { position: 'top', horizontalAlign: 'right', labels: { colors: '#64748B' } }
      };

      new ApexCharts(el, options).render();
    }
  }
}

/**
 * Donut chart untuk status leads, dipanggil via x-data="statusDonut(@json($statusDonut))" x-init="init()"
 */
function statusDonut(d) {
  return {
    init() {
      const el = document.querySelector("#status-donut");
      if (!el) return;

      if (!d || !(d.series || []).length) {
        el.innerHTML = '<div class="flex items-center justify-center h-72 text-base-content/60">Tidak ada data status.</div>';
        return;
      }

      const options = {
        series: d.series,
        labels: d.labels,
        chart: { type: 'donut', height: 320, background: 'transparent' },
        legend: { position: 'bottom' },
        colors: ['#60A5FA', '#34D399', '#94A3B8', '#F59E0B', '#EF4444', '#8B5CF6'],
        dataLabels: { enabled: true, formatter: (val) => `${val.toFixed(1)}%` },
        plotOptions: { pie: { donut: { size: '65%' } } },
        tooltip: { y: { formatter: (v) => `${v} leads` } }
      };

      new ApexCharts(el, options).render();
    }
  }
}
</script>
@endpush
