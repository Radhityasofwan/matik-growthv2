<!--
    Sidebar yang Dioptimalkan.
    - Class `lg:relative` dihapus. Sidebar sekarang `fixed` di semua ukuran layar.
    - Konten utama akan diberi margin kiri secara dinamis di `app.blade.php`.
    - Logika `translate-x` diperbarui untuk memastikan sidebar selalu terlihat di desktop.
-->

<!-- Overlay for mobile (closes sidebar on click) -->
<div
    x-show="isMobileMenuOpen"
    @click="isMobileMenuOpen = false"
    x-transition:enter="transition-opacity ease-linear duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-300"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-40 bg-black/40 lg:hidden"
    x-cloak
></div>

<aside
    class="fixed inset-y-0 left-0 z-50 flex flex-col flex-shrink-0 w-64 bg-base-100 border-r border-base-300/50 transition-all duration-300 ease-in-out"
    :class="{
        'translate-x-0': isMobileMenuOpen,
        '-translate-x-full lg:translate-x-0': !isMobileMenuOpen,
        'lg:w-64': isSidebarExpanded,
        'lg:w-20': !isSidebarExpanded
    }"
>
    <!-- Logo and Brand -->
    <div class="flex items-center h-20 border-b border-base-300/50 px-4 flex-shrink-0" :class="isSidebarExpanded ? 'justify-between' : 'justify-center'">
        <a href="{{ route('dashboard') }}" class="flex items-center" :class="isSidebarExpanded ? '' : 'justify-center'">
            <x-application-logo class="w-8 h-8 text-primary"/>
            <span x-show="isSidebarExpanded" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" class="ml-3 text-lg font-bold">MatikGrowth</span>
        </a>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 overflow-y-auto p-2" :class="isSidebarExpanded ? 'space-y-1' : 'space-y-2'">
        @php
            $navGroups = [
                'Main' => [
                    ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>'],
                ],
                'Sales' => [
                    ['route' => 'leads.index', 'label' => 'Leads', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'],
                    ['route' => 'subscriptions.index', 'label' => 'Subscriptions', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>'],
                    ['route' => 'campaigns.index', 'label' => 'Campaigns', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>'],
                ],
                'WhatsApp' => [
                    ['route' => 'whatsapp.broadcast.create', 'label' => 'Broadcast', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>'],
                    ['route' => 'whatsapp.templates.index', 'label' => 'WA Templates', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M13 8H7"/><path d="M17 12H7"/></svg>'],
                    ['route' => 'lead-follow-up-rules.index', 'label' => 'Follow Up Rules', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 12H3"/><path d="m3 12 4-4"/><path d="m3 12 4 4"/><path d="M21 12h-6"/><path d="m15 12 4-4"/><path d="m15 12 4 4"/><path d="M12 21V3"/><path d="m12 3 4 4"/><path d="m12 3-4 4"/><path d="m12 21 4-4"/><path d="m12 21-4 4"/></svg>'],
                    ['route' => 'waha-senders.index', 'label' => 'WA Senders', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>'],
                ],
                'Content' => [
                    ['route' => 'tasks.index', 'label' => 'Tasks', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 22h2a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16"/><path d="M9 12l2 2 4-4"/></svg>'],
                    ['route' => 'assets.index', 'label' => 'Assets', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg>'],
                ]
            ];
        @endphp

        @foreach ($navGroups as $group => $links)
            <div class="mt-4 first:mt-0">
                <h3
                    class="px-3 text-xs font-semibold uppercase text-neutral/50"
                    x-show="isSidebarExpanded"
                    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
                >{{ $group }}</h3>
                <div class="mt-1">
                    @foreach ($links as $link)
                        <a
                            href="{{ route($link['route']) }}"
                            class="flex items-center p-3 rounded-lg transition-colors duration-200 overflow-hidden"
                            :class="{
                                'bg-secondary text-primary font-semibold': {{ request()->routeIs($link['route'].'*') ? 'true' : 'false' }},
                                'hover:bg-base-200': !{{ request()->routeIs($link['route'].'*') ? 'true' : 'false' }},
                                'justify-center': !isSidebarExpanded
                            }"
                        >
                            <div class="w-6 h-6 flex-shrink-0">{!! $link['icon'] !!}</div>
                            <span class="ml-3 whitespace-nowrap" x-show="isSidebarExpanded" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0">
                                {{ $link['label'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <!-- Tombol Collapse Desktop -->
    <div class="p-2 border-t border-base-300/50">
        <button
            @click="toggleSidebar"
            class="hidden lg:flex btn btn-ghost w-full items-center"
            :class="isSidebarExpanded ? 'justify-end' : 'justify-center'"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 transition-transform duration-300" :class="isSidebarExpanded ? '' : 'rotate-180'"><path d="M15 18l-6-6 6-6"/></svg>
        </button>
    </div>
</aside>

