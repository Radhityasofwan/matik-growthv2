<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="softblue" class="h-full">
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

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>

<body
    x-data="{
        isSidebarExpanded: localStorage.getItem('sidebarExpanded') === null ? true : localStorage.getItem('sidebarExpanded') === 'true',
        isMobileMenuOpen: false,
        init() {
            // Set sidebar state on load based on window size
            if (window.innerWidth <= 1024) {
                this.isSidebarExpanded = false;
            }

            // Listener for window resize
            window.addEventListener('resize', () => {
                if (window.innerWidth <= 1024) {
                    this.isSidebarExpanded = false;
                    this.isMobileMenuOpen = false;
                } else {
                    // On larger screens, revert to stored preference
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
    class="h-full font-sans antialiased bg-base-200 text-neutral"
>
    <!-- Sidebar is now fixed and outside the main content flow -->
    @include('partials.sidebar')

    <!-- Main Content Wrapper with dynamic margin to be "pushed" by the sidebar -->
    <div
        class="flex flex-col flex-1 transition-all duration-300 ease-in-out"
        :class="{ 'lg:ml-64': isSidebarExpanded, 'lg:ml-20': !isSidebarExpanded }"
    >
        <!-- Navbar -->
        @include('partials.navbar')

        <!-- Main Content Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 sm:p-6 md:p-8">
            <div class="container mx-auto max-w-7xl">
                 @yield('content')
            </div>
        </main>
    </div>

@stack('modals')
@stack('scripts')
</body>
</html>

