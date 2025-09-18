<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="{
        theme: localStorage.getItem('theme') || 'softblue',
        setTheme(t) {
            this.theme = t
            localStorage.setItem('theme', t)
            document.documentElement.setAttribute('data-theme', t)
            document.documentElement.classList.toggle('dark', t === 'dark')
        }
    }"
    x-init="
        // jalankan saat load pertama
        document.documentElement.setAttribute('data-theme', theme)
        document.documentElement.classList.toggle('dark', theme === 'dark')
    "
    :data-theme="theme"
    class="h-full font-sans antialiased text-neutral bg-base-200"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Matik Growth Hub')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet"/>

    <!-- Favicon (supaya tidak 404) -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon"/>

    <!-- Vite Bundles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>[x-cloak]{display:none!important;}</style>
</head>
<body
    x-data="{
        isSidebarExpanded: localStorage.getItem('sidebarExpanded') === null ? true : localStorage.getItem('sidebarExpanded') === 'true',
        isMobileMenuOpen: false,
        init() {
            const setSidebarState = () => {
                if (window.innerWidth <= 1024) { this.isSidebarExpanded = false; }
                else { this.isSidebarExpanded = localStorage.getItem('sidebarExpanded') === 'true'; }
            };
            setSidebarState();
            window.addEventListener('resize', () => {
                if (window.innerWidth <= 1024) { this.isSidebarExpanded = false; this.isMobileMenuOpen = false; }
                else { setSidebarState(); }
            });
        },
        toggleSidebar(){
            this.isSidebarExpanded = !this.isSidebarExpanded;
            localStorage.setItem('sidebarExpanded', this.isSidebarExpanded);
        },
    }"
    x-init="init()"
    class="h-full"
>
    <div class="flex h-full">
        {{-- Partial Sidebar --}}
        @include('partials.sidebar')

        <div class="flex flex-col flex-1 transition-all duration-300 ease-in-out"
             :class="{ 'lg:ml-64': isSidebarExpanded, 'lg:ml-20': !isSidebarExpanded }">

            {{-- Partial Navbar --}}
            @include('partials.navbar')

            {{-- Main Content --}}
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 sm:p-6 lg:p-8">
                <div class="container mx-auto max-w-7xl">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @stack('modals')
    @stack('scripts')
</body>
</html>
