<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $campaign->name }} - Matik Growth Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900">
<div class="flex h-screen">
    @include('partials.sidebar')
    <div class="flex-1 flex flex-col overflow-hidden">
        @include('partials.navbar')
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 dark:bg-gray-900">
            <div class="container mx-auto px-6 py-8">
                <div class="sm:flex sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-medium">{{ $campaign->name }}</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $campaign->description }}</p>
                    </div>
                    <a href="{{ route('campaigns.edit', $campaign) }}" class="mt-4 sm:mt-0 text-white bg-blue-600 hover:bg-blue-700 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        Edit Campaign
                    </a>
                </div>

                 <!-- KPI Cards -->
                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                        <h4 class="text-gray-500 dark:text-gray-400 font-medium">Status</h4>
                        <p class="text-xl font-bold text-gray-800 dark:text-white mt-2">{{ ucfirst($campaign->status) }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                        <h4 class="text-gray-500 dark:text-gray-400 font-medium">Budget</h4>
                        <p class="text-xl font-bold text-gray-800 dark:text-white mt-2">${{ number_format($campaign->budget, 2) }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                        <h4 class="text-gray-500 dark:text-gray-400 font-medium">Revenue</h4>
                        <p class="text-xl font-bold text-gray-800 dark:text-white mt-2">${{ number_format($campaign->revenue, 2) }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                        <h4 class="text-gray-500 dark:text-gray-400 font-medium">ROI</h4>
                        <p class="text-xl font-bold mt-2 {{ $campaign->roi >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $campaign->roi }}%</p>
                    </div>
                </div>

                <!-- Timeline and Tasks -->
                <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                         <h4 class="font-semibold text-lg text-gray-800 dark:text-white mb-4">Activity Timeline</h4>
                        <ol class="relative border-l border-gray-200 dark:border-gray-700">
                            @forelse($campaign->activities as $activity)
                            <li class="mb-6 ml-4">
                                <div class="absolute w-3 h-3 bg-gray-200 rounded-full mt-1.5 -left-1.5 border border-white dark:border-gray-900 dark:bg-gray-700"></div>
                                <time class="mb-1 text-sm font-normal leading-none text-gray-400 dark:text-gray-500">{{ $activity->created_at->diffForHumans() }}</time>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $activity->description }}</h3>
                                <p class="text-base font-normal text-gray-500 dark:text-gray-400">by {{ $activity->causer->name ?? 'System' }}</p>
                            </li>
                            @empty
                            <li class="ml-4 text-gray-500 dark:text-gray-400">No activities recorded yet.</li>
                            @endforelse
                        </ol>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                        <h4 class="font-semibold text-lg text-gray-800 dark:text-white">Associated Tasks</h4>
                        <ul class="mt-4 space-y-3">
                            @forelse($campaign->tasks as $task)
                                <li class="flex items-center justify-between">
                                    <span class="dark:text-gray-300">{{ $task->title }}</span>
                                    <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $task->status == 'done' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                    </span>
                                </li>
                            @empty
                                <li class="text-gray-500 dark:text-gray-400">No tasks linked to this campaign.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
