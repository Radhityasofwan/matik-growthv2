@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-neutral">Dashboard</h1>
            <p class="mt-1 text-neutral/60">Ringkasan aktivitas CRM Anda hari ini.</p>
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
                <p class="text-4xl font-bold text-neutral mt-2">1,250</p>
                <p class="text-xs text-neutral/60 mt-1 flex items-center">
                    <span class="text-success font-semibold mr-1">▲ 122</span>
                    minggu ini
                </p>
            </div>
        </div>
        <!-- Kartu 2: Leads Baru -->
        <div class="card bg-base-100 shadow-md border border-base-300/50">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h2 class="card-title text-sm font-semibold text-neutral/60">Pesan Terkirim</h2>
                    <div class="bg-secondary p-2 rounded-lg">
                         <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-primary"><line x1="22" x2="11" y1="2" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    </div>
                </div>
                <p class="text-4xl font-bold text-neutral mt-2">8,930</p>
                <p class="text-xs text-neutral/60 mt-1 flex items-center">
                    <span class="text-success font-semibold mr-1">▲ 5.2%</span>
                    vs 7 hari lalu
                </p>
            </div>
        </div>
        <!-- Kartu 3: Pesan Terkirim -->
        <div class="card bg-base-100 shadow-md border border-base-300/50">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h2 class="card-title text-sm font-semibold text-neutral/60">Tingkat Balasan</h2>
                     <div class="bg-secondary p-2 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-primary"><path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"/><path d="M12 13h.01"/></svg>
                    </div>
                </div>
                <p class="text-4xl font-bold text-neutral mt-2">15.8%</p>
                 <p class="text-xs text-neutral/60 mt-1 flex items-center">
                    <span class="text-error font-semibold mr-1">▼ 1.1%</span>
                    vs 7 hari lalu
                </p>
            </div>
        </div>
        <!-- Kartu 4: Sesi Aktif -->
        <div class="card bg-base-100 shadow-md border border-base-300/50">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h2 class="card-title text-sm font-semibold text-neutral/60">WA Sender Aktif</h2>
                     <div class="bg-secondary p-2 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-primary"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    </div>
                </div>
                <p class="text-4xl font-bold text-neutral mt-2">3 / 3</p>
                 <p class="text-xs text-neutral/60 mt-1 flex items-center">
                    Semua sesi terhubung
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
                <div x-data="mainChart()" x-init="init()" class="w-full h-80 -mb-4 -ml-4">
                    <div id="main-chart"></div>
                </div>
            </div>
        </div>

        <!-- Aktivitas Terbaru -->
        <div class="card bg-base-100 shadow-md border border-base-300/50 lg:col-span-2">
            <div class="card-body">
                <h2 class="card-title mb-4">Aktivitas Terbaru</h2>
                <div class="space-y-4">
                    <!-- Item Aktivitas 1 -->
                    <div class="flex items-start">
                        <div class="avatar mr-4">
                            <div class="w-10 rounded-full bg-secondary text-primary flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/></svg>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-neutral"><strong>Andi</strong> mengimpor <strong>50 leads</strong> baru.</p>
                            <p class="text-xs text-neutral/60">2 menit lalu</p>
                        </div>
                    </div>
                     <!-- Item Aktivitas 2 -->
                    <div class="flex items-start">
                         <div class="avatar mr-4">
                            <div class="w-10 rounded-full bg-secondary text-primary flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="m3 11 18-5v12L3 14v-3z"/></svg>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-neutral">Broadcast <strong>"Promo September"</strong> terkirim ke <strong>240 leads</strong>.</p>
                            <p class="text-xs text-neutral/60">15 menit lalu</p>
                        </div>
                    </div>
                     <!-- Item Aktivitas 3 -->
                    <div class="flex items-start">
                         <div class="avatar mr-4">
                            <div class="w-10 rounded-full bg-secondary text-primary flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M11 12H3"/><path d="m3 12 4-4"/><path d="m3 12 4 4"/></svg>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-neutral">Aturan Follow Up <strong>"Follow Up D+3"</strong> mengirim <strong>12 pesan</strong>.</p>
                            <p class="text-xs text-neutral/60">1 jam lalu</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<!-- ApexCharts CDN -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
    function mainChart() {
        return {
            init() {
                const options = {
                    series: [{
                        name: 'Leads Baru',
                        data: [31, 40, 28, 51, 42, 109, 100, 112, 90, 85, 99, 110, 105, 120, 130, 122, 115, 125, 140, 135, 150, 145, 160, 155, 170, 165, 180, 175, 190, 185]
                    }, {
                        name: 'Pesan Terkirim',
                        data: [11, 32, 45, 32, 34, 52, 41, 55, 60, 58, 62, 70, 65, 75, 80, 72, 85, 90, 88, 95, 100, 92, 110, 105, 115, 120, 125, 130, 140, 135]
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
                        categories: [...Array(30).keys()].map(i => {
                            const d = new Date();
                            d.setDate(d.getDate() - (29 - i));
                            return d.toISOString().split('T')[0];
                        }),
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
