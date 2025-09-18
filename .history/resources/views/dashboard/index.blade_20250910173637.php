@extends('layouts.app')

@section('content')
<h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
    Dashboard
</h2>

<!-- KPI Cards with Animation -->
<div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
    <!-- Card 1: Trial -->
    <div data-aos="fade-up" class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <div class="p-3 mr-4 text-orange-500 bg-orange-100 rounded-full dark:text-orange-100 dark:bg-orange-500">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 2a6 6 0 00-6 6v3.586l-1.707 1.707A1 1 0 003 15v1a1 1 0 001 1h12a1 1 0 001-1v-1a1 1 0 00-.293-.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"></path>
            </svg>
        </div>
        <div>
            <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                Trial
            </p>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                {{ $funnelSummary->trial }}
            </p>
        </div>
    </div>
    <!-- Card 2: Active -->
    <div data-aos="fade-up" data-aos-delay="100" class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <div class="p-3 mr-4 text-green-500 bg-green-100 rounded-full dark:text-green-100 dark:bg-green-500">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
            </svg>
        </div>
        <div>
            <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                Active
            </p>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                {{ $funnelSummary->active }}
            </p>
        </div>
    </div>
    <!-- Card 3: Converted -->
    <div data-aos="fade-up" data-aos-delay="200" class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <div class="p-3 mr-4 text-blue-500 bg-blue-100 rounded-full dark:text-blue-100 dark:bg-blue-500">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"></path>
            </svg>
        </div>
        <div>
            <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                Converted
            </p>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                {{ $funnelSummary->convertedPercentage }}%
                <span class="text-xs text-gray-500 dark:text-gray-400">(Total: {{ $funnelSummary->converted }})</span>
            </p>
        </div>
    </div>
    <!-- Card 4: Churn -->
    <div data-aos="fade-up" data-aos-delay="300" class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <div class="p-3 mr-4 text-red-500 bg-red-100 rounded-full dark:text-red-100 dark:bg-red-500">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                 <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.367zM14.89 6.523A6 6 0 016.524 14.89l8.367-8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd"></path>
            </svg>
        </div>
        <div>
            <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                Churn
            </p>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                 {{ $funnelSummary->churnPercentage }}%
                 <span class="text-xs text-gray-500 dark:text-gray-400">(Total: {{ $funnelSummary->churn }})</span>
            </p>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="grid gap-6 mb-8 md:grid-cols-2">
    <div data-aos="fade-up" data-aos-delay="400" class="min-w-0 p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <h4 class="mb-4 font-semibold text-gray-800 dark:text-gray-300">
            Leads Growth (Last 30 Days)
        </h4>
        <div class="relative h-96">
            <canvas id="leadsChart"></canvas>
        </div>
    </div>
    <div data-aos="fade-up" data-aos-delay="500" class="min-w-0 p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <h4 class="mb-4 font-semibold text-gray-800 dark:text-gray-300">
            Today's Due Tasks
        </h4>
        @if ($todayTasks->tasks->isEmpty())
        <div class="flex flex-col items-center justify-center h-full text-center text-gray-500 dark:text-gray-400">
             <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <p class="text-lg">No tasks due today!</p>
            <p class="text-sm">Enjoy your day.</p>
        </div>
        @else
        <div class="space-y-4">
            @foreach ($todayTasks->tasks as $task)
            <div class="flex items-start p-3 border-b border-gray-200 dark:border-gray-700">
                <div class="flex-1">
                    <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $task->title }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Assigned to: {{ $task->assignee->name ?? 'Unassigned' }}</p>
                </div>
                 <div class="badge badge-lg {{ $task->priority_class }}">{{ $task->priority }}</div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const leadsChartEl = document.getElementById('leadsChart');
        if (leadsChartEl) {
            const ctx = leadsChartEl.getContext('2d');
            const chartData = @json($funnelSummary->getChartData());

            const leadsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'New Leads',
                        data: chartData.values,
                        borderColor: 'rgba(59, 130, 246, 0.8)',
                        backgroundColor: 'rgba(59, 130, 246, 0.2)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
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

