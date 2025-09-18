<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaigns - Matik Growth Hub</title>
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
                        <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-medium">Campaigns</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage all your marketing campaigns.</p>
                    </div>
                    <a href="{{ route('campaigns.create') }}" class="mt-4 sm:mt-0 text-white bg-blue-600 hover:bg-blue-700 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        Create Campaign
                    </a>
                </div>

                <!-- Filters -->
                <div class="mt-6">
                    <form action="{{ route('campaigns.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <input type="text" name="search" placeholder="Search by name..." value="{{ request('search') }}" class="w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white">
                        <select name="status" class="w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white">
                            <option value="">All Statuses</option>
                            <option value="planning" @selected(request('status') == 'planning')>Planning</option>
                            <option value="active" @selected(request('status') == 'active')>Active</option>
                            <option value="completed" @selected(request('status') == 'completed')>Completed</option>
                            <option value="on_hold" @selected(request('status') == 'on_hold')>On Hold</option>
                        </select>
                        <button type="submit" class="w-full md:w-auto px-5 py-2.5 bg-gray-700 text-white rounded-lg hover:bg-gray-800">Filter</button>
                    </form>
                </div>

                <!-- Campaigns Table -->
                <div class="mt-8 overflow-x-auto">
                    <div class="inline-block min-w-full shadow-md rounded-lg overflow-hidden">
                         <table class="min-w-full leading-normal">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Budget/Revenue</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ROI</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">End Date</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800">
                                @forelse ($campaigns as $campaign)
                                <tr>
                                    <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                        <a href="{{ route('campaigns.show', $campaign) }}" class="text-blue-600 hover:text-blue-900 font-semibold">{{ $campaign->name }}</a>
                                        <p class="text-gray-600 dark:text-gray-400 whitespace-no-wrap">{{ $campaign->channel }}</p>
                                    </td>
                                    <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                        <span class="relative inline-block px-3 py-1 font-semibold leading-tight rounded-full
                                            @switch($campaign->status)
                                                @case('planning') bg-blue-100 text-blue-900 @break
                                                @case('active') bg-green-100 text-green-900 @break
                                                @case('completed') bg-gray-100 text-gray-900 @break
                                                @case('on_hold') bg-yellow-100 text-yellow-900 @break
                                            @endswitch">
                                            {{ ucfirst($campaign->status) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                        <p class="text-gray-900 dark:text-white whitespace-no-wrap">${{ number_format($campaign->budget, 2) }}</p>
                                        <p class="text-gray-600 dark:text-gray-400 whitespace-no-wrap">${{ number_format($campaign->revenue, 2) }}</p>
                                    </td>
                                     <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                        <span class="{{ $campaign->roi >= 0 ? 'text-green-600' : 'text-red-600' }} font-semibold">{{ $campaign->roi }}%</span>
                                    </td>
                                    <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                        <p class="text-gray-900 dark:text-white whitespace-no-wrap">{{ $campaign->end_date->format('M d, Y') }}</p>
                                    </td>
                                    <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm text-right">
                                        <a href="{{ route('campaigns.edit', $campaign) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-5 text-center text-gray-500">No campaigns found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                         </table>
                         <div class="px-5 py-5 bg-white dark:bg-gray-800 border-t flex flex-col xs:flex-row items-center xs:justify-between">
                            {{ $campaigns->withQueryString()->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
