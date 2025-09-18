<!--
    Sidebar modern dengan state expand/collapse.
    - x-show="isMobileMenuOpen" untuk tampil/sembunyi di mobile.
    - Class dinamis untuk mengubah lebar di desktop.
    - Teks link disembunyikan saat collapsed (isSidebarExpanded = false).
-->
<aside
    x-show="isMobileMenuOpen"
    @click.away="isMobileMenuOpen = false"
    x-transition:enter="transition ease-in-out duration-300"
    x-transition:enter-start="-translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in-out duration-300"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="-translate-x-full"
    class="fixed inset-y-0 left-0 z-50 flex-shrink-0 w-64 bg-base-100 border-r border-base-300/50 shadow-lg lg:relative lg:z-auto lg:shadow-none lg:translate-x-0 lg:flex lg:flex-col"
    :class="{ 'lg:w-64': isSidebarExpanded, 'lg:w-20': !isSidebarExpanded }"
>
    <!-- Logo and Brand -->
    <div class="flex items-center justify-center h-20 border-b border-base-300/50 px-4" :class="isSidebarExpanded ? 'justify-between' : 'justify-center'">
        <a href="{{ route('dashboard') }}" class="flex items-center" :class="isSidebarExpanded ? '' : 'justify-center'">
            <x-application-logo class="w-8 h-8 text-primary"/>
            <span x-show="isSidebarExpanded" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" class="ml-3 text-lg font-bold">MatikGrowth</span>
        </a>
        <!-- Tombol collapse hanya di desktop -->
        <button @click="toggleSidebar" class="hidden lg:inline-flex btn btn-ghost btn-sm btn-circle">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 transition-transform duration-300" :class="isSidebarExpanded ? '' : 'rotate-180'"><path d="M15 18l-6-6 6-6"/></svg>
        </button>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 overflow-y-auto p-2" :class="isSidebarExpanded ? 'space-y-1' : 'space-y-2'">
        @php
            $navLinks = [
                ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>'],
                ['route' => 'leads.index', 'label' => 'Leads', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'],
                ['route' => 'whatsapp.templates.index', 'label' => 'WA Templates', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M13 8H7"/><path d="M17 12H7"/></svg>'],
                ['route' => 'lead-follow-up-rules.index', 'label' => 'Follow Up Rules', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 12H3"/><path d="m3 12 4-4"/><path d="m3 12 4 4"/><path d="M21 12h-6"/><path d="m15 12 4-4"/><path d="m15 12 4 4"/><path d="M12 21V3"/><path d="m12 3 4 4"/><path d="m12 3-4 4"/><path d="m12 21 4-4"/><path d="m12 21-4 4"/></svg>'],
                ['route' => 'waha-senders.index', 'label' => 'WA Senders', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>'],
            ];
        @endphp

        @foreach ($navLinks as $link)
            <a
                href="{{ route($link['route']) }}"
                class="flex items-center p-3 rounded-lg transition-colors duration-200"
                :class="{
                    'bg-secondary text-primary font-semibold': {{ request()->routeIs($link['route'].'*') ? 'true' : 'false' }},
                    'hover:bg-base-200': !{{ request()->routeIs($link['route'].'*') ? 'true' : 'false' }},
                    'justify-center': !isSidebarExpanded
                }"
                x-data="{ tooltip: '{{ $link['label'] }}' }"
                x-tooltip="!isSidebarExpanded ? tooltip : ''"
            >
                <div class="w-6 h-6">{!! $link['icon'] !!}</div>
                <span class="ml-3" x-show="isSidebarExpanded" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0">
                    {{ $link['label'] }}
                </span>
            </a>
        @endforeach
    </nav>

    <!-- User Profile Dropdown -->
    <div class="p-2 border-t border-base-300/50">
        <div class="dropdown dropdown-top w-full">
            <label tabindex="0" class="btn btn-ghost w-full flex items-center" :class="isSidebarExpanded ? 'justify-start' : 'justify-center'">
                <div class="avatar">
                    <div class="w-8 rounded-full">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=3B82F6&color=fff" alt="User Avatar" />
                    </div>
                </div>
                <div class="text-left ml-3" x-show="isSidebarExpanded" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0">
                    <p class="font-semibold text-sm truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-neutral/60 truncate">{{ Auth::user()->email }}</p>
                </div>
            </label>
            <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
                <li><a href="{{ route('profile.edit') }}">Profile</a></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();">
                            Log Out
                        </a>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</aside>
