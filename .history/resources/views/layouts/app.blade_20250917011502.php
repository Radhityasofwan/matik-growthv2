<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    {{-- State Management Tema Global dengan Alpine.js, disinkronkan dengan localStorage --}}
    x-data="{
        theme: localStorage.getItem('theme') || 'softblue',
        setTheme(t) {
            this.theme = t;
            localStorage.setItem('theme', t);
            document.documentElement.setAttribute('data-theme', t);
            document.documentElement.classList.toggle('dark', t === 'dark');
        }
    }"
    x-init="
        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.classList.toggle('dark', theme === 'dark');
    "
    :data-theme="theme"
    class="h-full font-sans antialiased"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Matik Growth Hub')</title>

    {{-- Favicon untuk menghindari error 404 di console --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon"/>

    {{-- Vite Asset Bundles --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Mencegah FOUC (Flash of Unstyled Content) untuk elemen Alpine --}}
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body
    class="h-full bg-base-200 text-base-content" {{-- UPDATED: text-neutral -> text-base-content --}}
    {{-- State Management Sidebar Global dengan Alpine.js --}}
    x-data="{
        isSidebarExpanded: localStorage.getItem('sidebarExpanded') === null ? true : localStorage.getItem('sidebarExpanded') === 'true',
        isMobileMenuOpen: false,
        init() {
            // Logika untuk mengatur state sidebar berdasarkan ukuran layar saat inisialisasi
            const setSidebarStateOnLoad = () => {
                if (window.innerWidth <= 1024) { this.isSidebarExpanded = false; }
                else { this.isSidebarExpanded = localStorage.getItem('sidebarExpanded') === 'true'; }
            };
            setSidebarStateOnLoad();

            // Listener untuk menyesuaikan state sidebar & menu mobile saat ukuran layar berubah
            window.addEventListener('resize', () => {
                if (window.innerWidth <= 1024) {
                    this.isSidebarExpanded = false;
                    this.isMobileMenuOpen = false;
                } else {
                    this.isSidebarExpanded = localStorage.getItem('sidebarExpanded') === 'true';
                }
            });
        },
        toggleSidebar() {
            this.isSidebarExpanded = !this.isSidebarExpanded;
            localStorage.setItem('sidebarExpanded', this.isSidebarExpanded);
        },
    }"
    x-init="init()"
>
    <div class="flex h-full">
        {{-- Sidebar Partial --}}
        @include('partials.sidebar')

        {{-- Main Content Wrapper --}}
        <div class="flex flex-1 flex-col transition-all duration-300 ease-in-out"
             :class="{ 'lg:ml-64': isSidebarExpanded, 'lg:ml-20': !isSidebarExpanded }">

            {{-- Navbar Partial --}}
            @include('partials.navbar')

            {{-- Konten Utama Halaman --}}
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 sm:p-6 lg:p-8">
                {{-- Container dengan animasi entri global --}}
                <div class="container mx-auto max-w-7xl" data-aos="fade-up" data-aos-duration="600">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @stack('modals')
    @stack('scripts')
</body>
</html>

