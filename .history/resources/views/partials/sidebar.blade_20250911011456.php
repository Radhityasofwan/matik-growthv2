{{-- resources/views/partials/sidebar.blade.php --}}
@php
    // Satu sumber kebenaran untuk seluruh menu
    $menu = [
        [
            'label'   => 'Dashboard',
            'route'   => 'dashboard',
            'pattern' => 'dashboard',
            'icon'    => 'home',
        ],
        [
            'label'   => 'Leads',
            'route'   => 'leads.index',
            'pattern' => 'leads.*',
            'icon'    => 'users',
        ],
        [
            'label'   => 'Subscriptions',
            'route'   => 'subscriptions.index',
            'pattern' => 'subscriptions.*',
            'icon'    => 'subs',
        ],
        [
            'label'   => 'Campaigns',
            'route'   => 'campaigns.index',
            'pattern' => 'campaigns.*',
            'icon'    => 'campaign',
        ],
        [
            'label'   => 'Tasks',
            'route'   => 'tasks.index',
            'pattern' => 'tasks.*',
            'icon'    => 'tasks',
        ],
        [
            'label'   => 'Asset Library',
            'route'   => 'assets.index', // <-- Ini yang sebelumnya error
            'pattern' => 'assets.*',
            'icon'    => 'assets',
        ],
        [
            'label'   => 'WA Templates',
            'route'   => 'whatsapp.templates.index',
            'pattern' => 'whatsapp.templates.*',
            'icon'    => 'chat',
        ],
    ];

    // Helper kecil untuk SVG icon
    function sidebar_icon($name, $classes = 'w-5 h-5') {
        switch ($name) {
            case 'home': return '<svg class="'.$classes.'" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h12a1 1 0 001-1V10"/></svg>';
            case 'users': return '<svg class="'.$classes.'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a6 6 0 00-9-5.197M9 20H4v-1a6 6 0 0112 0v1M15 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>';
            case 'subs': return '<svg class="'.$classes.'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18"/></svg>';
            case 'campaign': return '<svg class="'.$classes.'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6"/></svg>';
            case 'tasks': return '<svg class="'.$classes.'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5h6M9 9h6M9 13h6M5 7H4a2 2 0 00-2 2v9a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2h-1"/></svg>';
            case 'assets': return '<svg class="'.$classes.'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m2-2l1.586-1.586a2 2 0 012.828 0L22 14M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
            case 'chat': return '<svg class="'.$classes.'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>';
            default: return '<span class="'.$classes.'"></span>';
        }
    }

    // Render helper (menghindari render route() jika tidak ada)
    function sidebar_link($item) {
        $exists = \Illuminate\Support\Facades\Route::has($item['route']);
        $href   = $exists ? route($item['route']) : 'javascript:void(0)';
        $active = request()->routeIs($item['pattern']);
        $base   = 'inline-flex items-center w-full text-sm font-semibold transition-colors duration-150';
        $colors = $active
            ? 'text-primary-600 dark:text-primary-300'
            : 'text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-gray-100';
        $disabled = $exists ? '' : 'opacity-50 cursor-not-allowed';

        $bar = $active ? '<span class="absolute inset-y-0 left-0 w-1 bg-primary-600 rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>' : '';

        $icon = sidebar_icon($item['icon'], 'w-5 h-5');

        return <<<HTML
            <li class="relative px-6 py-3">
                {$bar}
                <a href="{$href}" class="{$base} {$colors} {$disabled}">
                    {$icon}
                    <span class="ml-4">{$item['label']}</span>
                </a>
            </li>
        HTML;
    }
@endphp

<!-- Desktop sidebar -->
<aside class="z-20 hidden w-64 flex-shrink-0 overflow-y-auto bg-white dark:bg-gray-800 lg:block">
    <div class="py-4 text-gray-500 dark:text-gray-400">
        <a class="ml-6 text-lg font-bold text-gray-800 dark:text-gray-200" href="{{ Route::has('dashboard') ? route('dashboard') : '#' }}">
            Matik Growth
        </a>
        <ul class="mt-6">
            {!! collect($menu)->map(fn($m) => sidebar_link($m))->implode('') !!}
        </ul>
    </div>
</aside>

<!-- Mobile sidebar -->
<div
    x-show="sidebarOpen"
    x-transition:enter="transition ease-in-out duration-150"
    x-transition:enter-start="opacity-0 transform -translate-x-20"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in-out duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0 transform -translate-x-20"
    @click.away="sidebarOpen = false"
    @keydown.escape="sidebarOpen = false"
    class="fixed inset-y-0 z-20 mt-16 w-64 flex-shrink-0 overflow-y-auto bg-white dark:bg-gray-800 md:hidden">
    <div class="py-4 text-gray-500 dark:text-gray-400">
        <a class="ml-6 text-lg font-bold text-gray-800 dark:text-gray-200" href="{{ Route::has('dashboard') ? route('dashboard') : '#' }}">
            Matik Growth
        </a>
        <ul class="mt-6">
            {!! collect($menu)->map(fn($m) => sidebar_link($m))->implode('') !!}
        </ul>
    </div>
</div>
