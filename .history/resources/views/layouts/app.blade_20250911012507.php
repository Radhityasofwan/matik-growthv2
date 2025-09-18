<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Matik Growth Hub')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet"/>

    <!-- Vite Assets & DaisyUI -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>

<body
    x-data="{
        // Inisialisasi sidebar: expand di desktop, collapse di mobile
        sidebarOpen: window.innerWidth > 1024,
        // Inisialisasi dark mode
        darkMode: localStorage.getItem('darkMode') || 'system',
        init() {
            this.updateTheme();
            this.$watch('darkMode', val => {
                localStorage.setItem('darkMode', val);
                this.updateTheme();
            });
            // Listener untuk perubahan skema warna OS
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => this.updateTheme());
            // Listener untuk resize window
            window.addEventListener('resize', () => {
                if (window.innerWidth <= 1024) {
                    this.sidebarOpen = false;
                } else {
                    this.sidebarOpen = true;
                }
            });
        },
        updateTheme() {
            const isDark = this.darkMode === 'dark' || (this.darkMode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', isDark);
            // Menambahkan tema DaisyUI berdasarkan mode
            document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
        }
    }"
    class="h-full font-sans antialiased bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200"
>
<div class="flex h-full">
    <!-- Sidebar -->
    @include('partials.sidebar')

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Navbar -->
        @include('partials.navbar')

        <!-- Scrollable Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto">
            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>

