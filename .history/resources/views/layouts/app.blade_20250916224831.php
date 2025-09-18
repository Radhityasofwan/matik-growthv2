<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="softblue" class="h-full bg-base-200">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Matik Growth Hub')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet"/>

    <!-- Vite (PERBAIKAN KRUSIAL) -->
    {{-- Kita hanya perlu memanggil entri JS. Vite akan secara otomatis
         menyertakan CSS yang diimpor oleh app.js. Ini akan
         memperbaiki masalah duplikasi file CSS. --}}
    @vite(['resources/js/app.js'])

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
        toggleSidebar(){ this.isSidebarExpanded = !this.isSidebarExpanded; localStorage.setItem('sidebarExpanded', this.isSidebarExpanded); },
    }"
    x-init="init()"
    class="h-full font-sans antialiased text-neutral"
>
    <div class="flex h-full">
        @include('partials.sidebar')

        <div class="flex flex-col flex-1 transition-all duration-300 ease-in-out" :class="{ 'lg:ml-64': isSidebarExpanded, 'lg:ml-20': !isSidebarExpanded }">
            @include('partials.navbar')

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

