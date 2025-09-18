@extends('layouts.app')

@section('content')
    <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-6" data-aos="fade-up">Dashboard</h1>

    <!-- KPI Cards with Animation -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6" data-aos="fade-up" data-aos-delay="100">
        <!-- Trial Leads -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
            <h4 class="text-gray-500 dark:text-gray-400 font-medium">Trial</h4>
            <p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">{{ $funnelSummary->trial ?? 0 }}</p>
        </div>
        <!-- Active Leads -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
            <h4 class="text-gray-500 dark:text-gray-400 font-medium">Active</h4>
            <p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">{{ $funnelSummary->active ?? 0 }}</p>
        </div>
        <!-- Converted Leads -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
            <h4 class="text-gray-500 dark:text-gray-400 font-medium">Converted</h4>
            <p class="text-2xl font-bold text-green-500 mt-2">{{ $funnelSummary->converted_rate ?? 0 }}%</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Total: {{ $funnelSummary->converted ?? 0 }}</p>
        </div>
        <!-- Churn Leads -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
            <h4 class="text-gray-500 dark:text-gray-400 font-medium">Churn</h4>
            <p class="text-2xl font-bold text-red-500 mt-2">{{ $funnelSummary->churn_rate ?? 0 }}%</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Total: {{ $funnelSummary->churn ?? 0 }}</p>
        </div>
    </div>

    <!-- Charts and Today's Tasks -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-8">
        <!-- Leads Growth Chart -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md relative h-96" data-aos="fade-up" data-aos-delay="200">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-white">Leads Growth (Last 30 Days)</h2>
            <canvas id="leadsChart"></canvas>
        </div>

        <!-- Today's Tasks -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="300">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-white">Today's Due Tasks</h2>
            <div class="space-y-4">
                @forelse ($todayTasks->tasks as $task)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div>
                            <p class="font-semibold text-gray-700 dark:text-gray-200">{{ $task->title }}</p>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $task->priority }}</span>
                        </div>
                        <a href="{{ route('tasks.index') }}" class="text-primary hover:underline text-sm">View</a>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                          <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No tasks due today!</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Enjoy your day.</p>
                      </div>
                @endforelse
            </div>
        </div>
    </div>

<!-- Floating Action Button for Mobile -->
<a href="{{ route('leads.create') }}" class="fixed bottom-6 right-6 bg-primary text-white p-4 rounded-full shadow-lg lg:hidden" aria-label="Add New Lead">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
    </svg>
</a>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('leadsChart');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            const data = @json($funnelSummary->getChartData());

            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'New Leads',
                        data: data.values,
                        borderColor: 'rgba(59, 130, 246, 1)',
                        backgroundColor: 'rgba(59, 130, 246, 0.2)',
                        fill: true,
                        tension: 0.4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    });
</script>
@endpush

