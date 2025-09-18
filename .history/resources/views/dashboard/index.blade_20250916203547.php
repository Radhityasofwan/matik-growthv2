<x-app-layout>
    {{-- CATATAN: File ini mengharapkan variabel dari DashboardController --}}
    {{-- $stats, $chartData, $statusCounts, $recentActivities, dll. --}}

    <!-- Kontainer Utama -->
    <div class="space-y-6">

        <!-- Header Halaman -->
        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-neutral">Dashboard</h1>
                <p class="mt-1 text-neutral/60">Ringkasan aktivitas CRM Anda hari ini, {{ Auth::user()->name }}.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('leads.index') }}" class="btn btn-outline btn-primary">Kelola Leads</a>
                <a href="{{ route('whatsapp.broadcast.create') }}" class="btn btn-primary">Broadcast Baru</a>
            </div>
        </div>

        <!-- Grid Kartu Metrik -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6">
            @php
                $icons = [
                    'total_leads' => 'users',
                    'messages_sent_7d' => 'send',
                    'reply_rate_7d' => 'message-square-reply',
                    'active_senders' => 'server-cog',
                    'mrr' => 'dollar-sign',
                    'open_tasks' => 'check-circle'
                ];
            @endphp

            @foreach($stats as $key => $stat)
            {{-- PERBAIKAN: Tambahkan pengecekan untuk memastikan $stat adalah sebuah array sebelum dirender --}}
            @if(is_array($stat))
            <div class="card bg-base-100 shadow">
                <div class="card-body p-5">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-neutral/60">{{ $stat['label'] ?? 'Data tidak tersedia' }}</h2>
                        <div class="bg-secondary p-2 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-primary"><use href="https://cdn.jsdelivr.net/npm/lucide-icons/dist/lucide.sprite.svg#{{ $icons[$key] ?? 'alert-circle' }}" /></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-neutral mt-2">{{ $stat['value'] ?? '0' }}</p>
                    <div class="text-xs text-neutral/50 mt-1 flex items-center">
                        @if(isset($stat['change']))
                            <span class="font-semibold mr-1 flex items-center {{ $stat['change'] >= 0 ? 'text-success' : 'text-error' }}">
                                @if($stat['change'] >= 0)
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><path d="M19 12H5"/><path d="m12 5 7 7-7 7"/></svg>
                                @endif
                                {{ abs($stat['change']) }}%
                            </span>
                            vs 7 hari lalu
                        @elseif(isset($stat['context']))
                             <span>{{ $stat['context'] }}</span>
                        @endif
                    </div>
                </div>
            </div>
            @endif
            @endforeach
        </div>

        <!-- Area Grafik -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Grafik Aktivitas Utama -->
            <div class="lg:col-span-2 card bg-base-100 shadow">
                <div class="card-body">
                    <h2 class="card-title">Aktivitas 30 Hari Terakhir</h2>
                    <div x-data='mainChart(@json($chartData ?? ["labels" => [], "data" => []]))' x-init="initChart()" class="w-full h-80">
                         <canvas x-ref="chartCanvas"></canvas>
                    </div>
                </div>
            </div>

            <!-- Donut Chart Status Leads -->
            <div class="card bg-base-100 shadow">
                <div class="card-body">
                    <h2 class="card-title">Status Leads</h2>
                    <div x-data='statusDonut(@json($statusCounts ?? ["labels" => [], "data" => []]))' x-init="initDonut()" class="w-full h-80">
                         <canvas x-ref="donutCanvas"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Aktivitas Terbaru -->
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title">Aktivitas Terbaru</h2>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Deskripsi</th>
                                <th>User</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentActivities as $activity)
                            <tr>
                                <td>{{ $activity->description }}</td>
                                <td>{{ $activity->causer->name ?? 'Sistem' }}</td>
                                <td>{{ $activity->created_at->diffForHumans() }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-neutral/50 py-8">
                                    Belum ada aktivitas.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    {{-- Library Grafik --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide-icons/dist/lucide.min.js"></script>
    <script>
        lucide.createIcons();

        // Inisialisasi Grafik Utama (Alpine.js)
        document.addEventListener('alpine:init', () => {
            Alpine.data('mainChart', (chartData) => ({
                chart: null,
                initChart() {
                    this.chart = new Chart(this.$refs.chartCanvas, {
                        type: 'line',
                        data: {
                            labels: chartData.labels,
                            datasets: [{
                                label: 'Pesan Terkirim',
                                data: chartData.data,
                                borderColor: '#3B82F6',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: { y: { beginAtZero: true } },
                            plugins: { legend: { display: false } }
                        }
                    });
                }
            }));

            // Inisialisasi Donut Chart (Alpine.js)
            Alpine.data('statusDonut', (statusData) => ({
                donut: null,
                initDonut() {
                    this.donut = new Chart(this.$refs.donutCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: statusData.labels,
                            datasets: [{
                                data: statusData.data,
                                backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#64748B'],
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { position: 'bottom' } }
                        }
                    });
                }
            }));
        });
    </script>
    @endpush
</x-app-layout>

