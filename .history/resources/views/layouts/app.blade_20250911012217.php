<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-g">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Matik Growth Hub')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet"/>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>

<body
    x-data="{
        sidebarOpen: window.innerWidth > 1024 ? true : false,
        darkMode: localStorage.getItem('darkMode') || 'system',
        init() {
            this.updateTheme();
            this.$watch('darkMode', val => {
                localStorage.setItem('darkMode', val);
                this.updateTheme();
            });
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => this.updateTheme());
            window.addEventListener('resize', () => {
                if (window.innerWidth <= 1024) this.sidebarOpen = false;
                else this.sidebarOpen = true;
            });
        },
        updateTheme() {
            const isDark = this.darkMode === 'dark' || (this.darkMode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', isDark);
        }
    }"
    class="h-full font-sans antialiased bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200"
>
<div class="flex h-full">
    @php
        $menu = [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'pattern' => 'dashboard', 'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>'],
            ['label' => 'Leads', 'route' => 'leads.index', 'pattern' => 'leads.*', 'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.12-1.28-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.12-1.28.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>'],
            ['label' => 'Subscriptions', 'route' => 'subscriptions.index', 'pattern' => 'subscriptions.*', 'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'],
            ['label' => 'Campaigns', 'route' => 'campaigns.index', 'pattern' => 'campaigns.*', 'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-2.236 9.168-5.518"/></svg>'],
            ['label' => 'Tasks', 'route' => 'tasks.index', 'pattern' => 'tasks.*', 'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>'],
            ['label' => 'Asset Library', 'route' => 'assets.index', 'pattern' => 'assets.*', 'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L22 14M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>'],
            ['label' => 'WA Templates', 'route' => 'whatsapp.templates.index', 'pattern' => 'whatsapp.templates.*', 'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>'],
        ];
    @endphp

    <!-- Sidebar -->
    <aside
        class="flex-shrink-0 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transition-all duration-300"
        :class="{ 'w-64': sidebarOpen, 'w-20': !sidebarOpen }"
        x-cloak
    >
        <div class="flex flex-col h-full">
            <!-- Logo -->
            <div class="h-16 flex items-center justify-center border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 text-blue-600 dark:text-blue-400">
                    <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span x-show="sidebarOpen" class="font-bold text-xl transition-opacity duration-300">Matik</span>
                </a>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto">
                <ul class="py-4">
                    @foreach ($menu as $item)
                        @php
                            $isActive = request()->routeIs($item['pattern']);
                        @endphp
                        <li class="relative px-4">
                            <a href="{{ \ Illuminate\Support\Facades\Route::has($item['route']) ? route($item['route']) : '#' }}"
                               class="flex items-center p-3 my-1 rounded-lg transition-colors duration-200
                                      {{ $isActive ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                               :class="{ 'justify-center': !sidebarOpen }"
                            >
                                {!! $item['icon'] !!}
                                <span class="ml-4 transition-opacity duration-300" :class="{ 'opacity-0 hidden': !sidebarOpen }">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Navbar -->
        <header class="h-16 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
            <div class="flex items-center justify-between h-full px-6">
                <!-- Sidebar Toggle -->
                <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-full text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>

                <!-- Right Side Actions -->
                <div class="flex items-center space-x-4">
                    <!-- Theme Toggle -->
                    <div class="dropdown dropdown-end">
                        <label tabindex="0" class="btn btn-ghost btn-circle">
                             <svg x-show="darkMode !== 'dark'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                             <svg x-show="darkMode === 'dark'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                        </label>
                        <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-36">
                            <li><a @click.prevent="darkMode = 'light'">Light</a></li>
                            <li><a @click.prevent="darkMode = 'dark'">Dark</a></li>
                            <li><a @click.prevent="darkMode = 'system'">System</a></li>
                        </ul>
                    </div>

                    <!-- Profile Dropdown -->
                    <div class="dropdown dropdown-end">
                        <label tabindex="0" class="btn btn-ghost btn-circle avatar">
                            <div class="w-9 rounded-full">
                                <img src="https://i.pravatar.cc/80?u={{ auth()->id() }}" />
                            </div>
                        </label>
                        <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-52">
                            <li><p class="font-semibold p-2">{{ Auth::user()->name }}</p></li>
                            <li><a href="{{ route('profile') }}">Profile</a></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto">
            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
