@php
    $menu = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'pattern' => 'dashboard', 'icon' => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>'],
        ['label' => 'Leads', 'route' => 'leads.index', 'pattern' => 'leads.*', 'icon' => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.12-1.28-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.12-1.28.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>'],
        ['label' => 'Subscriptions', 'route' => 'subscriptions.index', 'pattern' => 'subscriptions.*', 'icon' => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'],
        ['label' => 'Campaigns', 'route' => 'campaigns.index', 'pattern' => 'campaigns.*', 'icon' => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-2.236 9.168-5.518"/></svg>'],
        ['label' => 'Tasks', 'route' => 'tasks.index', 'pattern' => 'tasks.*', 'icon' => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>'],
        ['label' => 'Asset Library', 'route' => 'assets.index', 'pattern' => 'assets.*', 'icon' => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L22 14M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>'],
        ['label' => 'WA Templates', 'route' => 'whatsapp.templates.index', 'pattern' => 'whatsapp.templates.*', 'icon' => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>'],
    ];
@endphp

<!-- Sidebar -->
<aside
    class="flex-shrink-0 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transition-all duration-300 z-20"
    :class="{ 'w-64': sidebarOpen, 'w-20': !sidebarOpen }"
    x-cloak
>
    <div class="flex flex-col h-full">
        <!-- Logo -->
        <div class="h-16 flex items-center justify-center border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
            <a href="{{ \ Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : '#' }}" class="flex items-center space-x-2 text-blue-600 dark:text-blue-400">
                <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span x-show="sidebarOpen" class="font-bold text-xl transition-opacity duration-300">Matik</span>
            </a>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto overflow-x-hidden">
            <ul class="py-4">
                @foreach ($menu as $item)
                    @php
                        $isActive = \ Illuminate\Support\Facades\Route::has($item['route']) && request()->routeIs($item['pattern']);
                        $isDisabled = !\ Illuminate\Support\Facades\Route::has($item['route']);
                    @endphp
                    <li class="relative px-4" :class="{'tooltip tooltip-right': !sidebarOpen}" data-tip="{{ $item['label'] }}">
                        <a href="{{ !$isDisabled ? route($item['route']) : '#' }}"
                           class="flex items-center p-3 my-1 rounded-lg transition-colors duration-200
                                  {{ $isActive ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-300 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}
                                  {{ $isDisabled ? 'opacity-50 cursor-not-allowed' : '' }}"
                           :class="{ 'justify-center': !sidebarOpen }"
                        >
                            {!! $item['icon'] !!}
                            <span class="ml-4 transition-opacity duration-300 whitespace-nowrap" :class="{ 'opacity-0 scale-0 absolute': !sidebarOpen }">{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    </div>
</aside>
