@extends('layouts.app')

@section('title', 'Dashboard - Matik Growth Hub')

@section('content')
<div class="container mx-auto px-6 py-8">
    <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-medium">Dashboard</h3>

    <!-- KPI Cards with Animation -->
    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md" data-aos="fade-up">
            <h4 class="text-gray-500 dark:text-gray-400 font-medium">Trial Leads</h4>
            <p class="text-2xl font-bold text-gray-800 dark:text-white mt-2">{{ $funnelSummary['trial'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="100">
            <h4 class="text-gray-500 dark:text-gray-400 font-medium">Active Subscriptions</h4>
            <p class="text-2xl font-bold text-gray-800 dark:text-white mt-2">{{ $funnelSummary['active'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="200">
            <h4 class="text-gray-500 dark:text-gray-400 font-medium">Converted</h4>
            <p class="text-2xl font-bold text-green-500 mt-2">{{ $funnelSummary['converted'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="300">
            <h4 class="text-gray-500 dark:text-gray-400 font-medium">Churned</h4>
            <p class="text-2xl font-bold text-red-500 mt-2">{{ $funnelSummary['churn'] }}</p>
        </div>
    </div>

    <!-- Charts and Tasks -->
    <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md" data-aos="fade-right">
            <h4 class="font-semibold text-lg text-gray-800 dark:text-white">Leads Growth (Last 30 Days)</h4>
            <canvas id="leadsChart" class="mt-4"></canvas>
        </div>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md" data-aos="fade-left">
            <h4 class="font-semibold text-lg text-gray-800 dark:text-white">Today's Tasks</h4>
            <ul class="mt-4 space-y-3">
                @forelse ($todayTasks as $task)
                <li class="flex items-center justify-between">
                    <span class="dark:text-gray-300">{{ $task->title }}</span>
                    <span class="text-xs font-semibold px-2 py-1 rounded-full
                        @switch($task->priority)
                            @case('high') @case('urgent') bg-red-100 text-red-800 @break
                            @case('medium') bg-yellow-100 text-yellow-800 @break
                            @default bg-blue-100 text-blue-800
                        @endswitch">
                        {{ ucfirst($task->priority) }}
                    </span>
                </li>
                @empty
                <li class="text-gray-500 dark:text-gray-400">No tasks due today. Enjoy your day!</li>
                @endforelse
            </ul>
        </div>
    </div>

    <!-- Report Download Links -->
    <div class="mt-8" data-aos="fade-up">
        <h4 class="font-semibold text-lg text-gray-800 dark:text-white">Reports</h4>
        <div class="mt-4 flex space-x-4">
            <a href="{{ route('reports.sales.funnel', 'pdf') }}" class="btn btn-primary">Sales Funnel Report (PDF)</a>
            <a href="{{ route('reports.sales.funnel', 'excel') }}" class="btn btn-secondary">Sales Funnel Report (Excel)</a>
        </div>
    </div>

    <!-- Quick Action Floating Button (Mobile Only) -->
    <a href="{{ route('leads.create') }}" class="sm:hidden fixed bottom-4 right-4 bg-blue-600 text-white p-4 rounded-full shadow-lg hover:bg-blue-700 transition">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const leadsData = @json($leadsGrowth);
    const ctx = document.getElementById('leadsChart').getContext('2d');
    const leadsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: Object.keys(leadsData),
            datasets: [{
                label: 'New Leads',
                data: Object.values(leadsData),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
@endsection

