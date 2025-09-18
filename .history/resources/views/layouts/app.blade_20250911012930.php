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

    <!-- Vite & Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        .sidebar-link-active {
            background-color: #EBF5FF; /* blue-100 */
            color: #2563EB; /* blue-600 */
        }
        .dark .sidebar-link-active {
            background-color: rgba(37, 99, 235, 0.2); /* blue-900/50 */
            color: #93C5FD; /* blue-300 */
        }
    </style>
</head>

<body
    x-data="{
        isSidebarExpanded: localStorage.getItem('sidebarExpanded') === 'true' || window.innerWidth > 1024,
        isMobileMenuOpen: false,
        darkMode: localStorage.getItem('darkMode') || 'system',

        toggleSidebar() {
            this.isSidebarExpanded = !this.isSidebarExpanded;
            localStorage.setItem('sidebarExpanded', this.isSidebarExpanded);
        },

        init() {
            this.updateTheme();
            this.$watch('darkMode', val => {
                localStorage.setItem('darkMode', val);
                this.updateTheme();
            });
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => this.updateTheme());

            // Handle window resize
            window.addEventListener('resize', () => {
                if (window.innerWidth <= 1024) {
                    this.isSidebarExpanded = false;
                    this.isMobileMenuOpen = false;
                } else {
                    this.isSidebarExpanded = localStorage.getItem('sidebarExpanded') === 'true';
                }
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
    <!-- Sidebar -->
    @include('partials.sidebar')

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Navbar -->
        @include('partials.navbar')

        <!-- Main Content Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 md:p-8">
            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>

