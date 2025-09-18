@php
    // NOTE:
    // - "WhatsApp" jadi satu menu saja, mengarah ke broadcast,
    //   namun active state-nya mencakup route senders juga.
    // - WA Logs dihapus dari sidebar.
    // - WA Templates tetap terpisah sesuai kebutuhan sebelumnya.

    $menu = [
        [
            'label'   => 'Dashboard',
            'route'   => 'dashboard',
            'pattern' => 'dashboard',
            'icon'    => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
        ],
        [
            'label'   => 'Leads',
            'route'   => 'leads.index',
            'pattern' => 'leads.*',
            'icon'    => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.12-1.28-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.12-1.28.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>',
        ],
        [
            'label'   => 'Subscriptions',
            'route'   => 'subscriptions.index',
            'pattern' => 'subscriptions.*',
            'icon'    => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
        ],
        [
            'label'   => 'Campaigns',
            'route'   => 'campaigns.index',
            'pattern' => 'campaigns.*',
            'icon'    => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-2.236 9.168-5.518"/></svg>',
        ],
        [
            'label'   => 'Tasks',
            'route'   => 'tasks.index',
            'pattern' => 'tasks.*',
            'icon'    => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>',
        ],
        [
            'label'   => 'Asset Library',
            'route'   => 'assets.index',
            'pattern' => 'assets.*',
            'icon'    => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L22 14M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
        ],
        [
            'label'   => 'WA Templates',
            'route'   => 'whatsapp.templates.index',
            'pattern' => 'whatsapp.templates.*',
            'icon'    => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>',
        ],

        // ====== DIGABUNG: WhatsApp (Broadcast + Senders) ======
        [
            'label'   => 'WhatsApp',
            // menu ini diarahkan ke halaman broadcast
            'route'   => 'whatsapp.broadcast.create',
            // pattern bisa array; aktif untuk kedua halaman:
            'pattern' => ['whatsapp.broadcast.*', 'waha-senders.*'],
            'icon'    => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A2 2 0 0122 9.528v4.944a2 2 0 01-2.447 1.804L15 14M4 6h8m-8 6h8m-8 6h8"/></svg>',
        ],
    ];

    // untuk menyembunyikan label saat sidebar collapsed di desktop
    $isMobile = isset($isMobile) && $isMobile;

    // helper: cek active untuk string/array pattern
    $isRouteActive = function ($pattern) {
        return is_array($pattern) ? request()->routeIs(...$pattern) : request()->routeIs($pattern);
    };
@endphp

<ul class="py-4">
    @foreach ($menu as $item)
        @php
            $isActive = isset($item['pattern']) ? $isRouteActive($item['pattern']) : false;
            $hasRoute = \Illuminate\Support\Facades\Route::has($item['route']);
        @endphp
        <li class="relative px-4">
            <a href="{{ $hasRoute ? route($item['route']) : '#' }}"
               class="flex items-center p-3 my-1 rounded-lg transition-colors duration-200
                      {{ $isActive ? 'sidebar-link-active' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}
                      {{ !$hasRoute ? 'opacity-50 cursor-not-allowed' : '' }}"
               :class="{ 'justify-center': !isSidebarExpanded && !{{ json_encode($isMobile) }} }"
            >
                {!! $item['icon'] !!}
                <span class="ml-4 transition-opacity duration-300"
                      :class="{ 'lg:opacity-0 lg:hidden': !isSidebarExpanded && !{{ json_encode($isMobile) }} }">
                      {{ $item['label'] }}
                </span>
            </a>
        </li>
    @endforeach
</ul>
