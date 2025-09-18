<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="softblue" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Matik Growth Hub')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>[x-cloak]{display:none!important;}</style>
</head>
<body
    x-data="{
        isSidebarExpanded: localStorage.getItem('sidebarExpanded') === null ? true : localStorage.getItem('sidebarExpanded') === 'true',
        isMobileMenuOpen: false,
        init() {
            if (window.innerWidth <= 1024) { this.isSidebarExpanded = false; }
            window.addEventListener('resize', () => {
                if (window.innerWidth <= 1024) {
                    this.isSidebarExpanded = false;
                    this.isMobileMenuOpen = false;
                } else {
                    this.isSidebarExpanded = localStorage.getItem('sidebarExpanded') === 'true';
                }
            });
        },
        toggleSidebar(){
            this.isSidebarExpanded = !this.isSidebarExpanded;
            localStorage.setItem('sidebarExpanded', this.isSidebarExpanded);
        },
    }"
    x-init="init()"
    class="h-full font-sans antialiased bg-base-200 text-neutral"
>
    {{-- Sidebar disertakan di sini --}}
    @include('partials.sidebar')

    {{-- Konten Utama --}}
    <div class="flex flex-col flex-1 transition-all duration-300 ease-in-out" :class="{ 'lg:ml-64': isSidebarExpanded, 'lg:ml-20': !isSidebarExpanded }">

        {{-- Navbar disertakan di sini --}}
        @include('partials.navbar')

        {{-- Main Content Area --}}
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 sm:p-6 md:p-8">
            <div class="container mx-auto max-w-7xl">
                {{-- Konten dari halaman spesifik (misal: dashboard.blade.php) akan dimuat di sini --}}
                @yield('content')
            </div>
        </main>
    </div>

    @stack('modals')
    @stack('scripts')
</body>
</html>

