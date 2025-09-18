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

    <!-- Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Mencegah FOUC (Flash of unstyled content) untuk AlpineJS --}}
    <style>[x-cloak]{display:none!important;}</style>
</head>
<body
    x-data="{
        isSidebarExpanded: localStorage.getItem('sidebarExpanded') === null ? true : localStorage.getItem('sidebarExpanded') === 'true',
        isMobileMenuOpen: false,
        init() {
            // Secara default, sidebar diciutkan di layar di bawah lg (1024px)
            if (window.innerWidth < 1024) { this.isSidebarExpanded = false; }

            // Listener untuk menyesuaikan state sidebar saat ukuran window berubah
            window.addEventListener('resize', () => {
                if (window.innerWidth < 1024) {
                    this.isSidebarExpanded = false;
                    this.isMobileMenuOpen = false; // Selalu tutup menu mobile saat resize
                } else {
                    // Kembalikan ke preferensi user di desktop
                    this.isSidebarExpanded = localStorage.getItem('sidebarExpanded') === 'true';
                }
            });
        },
        // Fungsi untuk toggle sidebar di desktop
        toggleSidebar() {
            this.isSidebarExpanded = !this.isSidebarExpanded;
            localStorage.setItem('sidebarExpanded', this.isSidebarExpanded);
        },
    }"
    x-init="init()"
    class="h-full font-sans antialiased text-neutral"
>
    {{-- Sidebar dipanggil di sini, posisinya fixed dan akan mengatur perilakunya sendiri --}}
    @include('partials.sidebar')

    {{-- Kontainer utama untuk konten (Navbar + Main Content) --}}
    {{-- Margin kiri dinamis menyesuaikan state sidebar di layar besar (lg) --}}
    <div class="flex flex-col flex-1 transition-all duration-300 ease-in-out" :class="{ 'lg:ml-64': isSidebarExpanded, 'lg:ml-20': !isSidebarExpanded }">

        {{-- Navbar dipanggil di sini, akan dibuat sticky --}}
        @include('partials.navbar')

        {{-- Area konten utama yang dapat di-scroll --}}
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 sm:p-6">
            <div class="container mx-auto max-w-7xl">
                @yield('content')
            </div>
        </main>
    </div>

    @stack('modals')
    @stack('scripts')
</body>
</html>

