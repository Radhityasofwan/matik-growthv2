{{-- resources/views/layouts/app.blade.php — FINAL --}}
<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
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

    {{-- Set tema sedini mungkin (anti-FOUC warna DaisyUI) --}}
    <script>
      (function(){
        var t = localStorage.getItem('theme') || 'softblue';
        document.documentElement.setAttribute('data-theme', t);
        if (t === 'dark') document.documentElement.classList.add('dark');
      })();
    </script>

    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon"/>

    {{-- Vite Asset Bundles (CSS sebelum JS) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Mencegah FOUC untuk elemen Alpine --}}
    <style>[x-cloak]{display:none!important}</style>

    @stack('head')
</head>
<body
    :class="isMobileMenuOpen ? 'overflow-hidden h-full bg-base-200 text-base-content' : 'h-full bg-base-200 text-base-content'"
    x-data="{
        isSidebarExpanded: localStorage.getItem('sidebarExpanded') === null
            ? true
            : localStorage.getItem('sidebarExpanded') === 'true',
        isMobileMenuOpen: false,
        init() {
            const setSidebarStateOnLoad = () => {
                if (window.innerWidth <= 1024) { this.isSidebarExpanded = false }
                else {
                    const stored = localStorage.getItem('sidebarExpanded')
                    this.isSidebarExpanded = stored ? stored === 'true' : true
                }
            }
            setSidebarStateOnLoad()

            window.addEventListener('resize', () => {
                if (window.innerWidth <= 1024) {
                    this.isSidebarExpanded = false
                    this.isMobileMenuOpen = false
                } else {
                    const stored = localStorage.getItem('sidebarExpanded')
                    this.isSidebarExpanded = stored ? stored === 'true' : true
                }
            })
        },
        toggleSidebar() {
            this.isSidebarExpanded = !this.isSidebarExpanded
            localStorage.setItem('sidebarExpanded', this.isSidebarExpanded)
        },
        openMobileMenu(){ this.isMobileMenuOpen = true },
        closeMobileMenu(){ this.isMobileMenuOpen = false },
    }"
    x-init="init()"
>
    {{-- Skip to content (aksesibilitas, keyboard) --}}
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 btn btn-primary btn-sm z-50">
        Skip to content
    </a>

    <div class="flex min-h-screen">
        {{-- Sidebar Partial (mengandung overlay mobile sendiri) --}}
        @include('partials.sidebar')

        {{-- Main Content Wrapper --}}
        <div
            class="flex flex-1 flex-col transition-all duration-300 ease-in-out"
            :class="{
                'lg:ml-64': isSidebarExpanded,
                'lg:ml-20': !isSidebarExpanded,
            }"
        >
            {{-- Navbar Partial --}}
            <div
              x-data
              @sidebar:toggle.window="toggleSidebar()"
              @mobilemenu:open.window="openMobileMenu()"
              @mobilemenu:close.window="closeMobileMenu()"
            >
              @include('partials.navbar')
            </div>

            {{-- Konten Utama Halaman --}}
            <main id="main-content" class="flex-1 overflow-x-hidden overflow-y-auto p-4 sm:p-6 lg:p-8">
                <div class="mx-auto w-full max-w-7xl" data-aos="fade-up" data-aos-duration="600">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @stack('modals')
    @stack('scripts')
</body>
</html>
