{{-- resources/views/layouts/app.blade.php — FINAL --}}
<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="{
        themes: ['softblue', 'light', 'dark', 'cupcake', 'bumblebee', 'emerald', 'corporate', 'synthwave', 'retro', 'cyberpunk', 'valentine', 'halloween', 'garden', 'forest', 'aqua', 'lofi', 'pastel', 'fantasy', 'wireframe', 'black', 'luxury', 'dracula', 'cmyk', 'autumn', 'business', 'acid', 'lemonade', 'night', 'coffee', 'winter'],
        darkThemes: ['dark', 'synthwave', 'halloween', 'forest', 'black', 'luxury', 'dracula', 'business', 'night', 'coffee'],
        theme: localStorage.getItem('theme') || 'softblue',
        setTheme(t) {
            this.theme = t;
            localStorage.setItem('theme', t);
            document.documentElement.setAttribute('data-theme', t);
            if (this.darkThemes.includes(t)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    }"
    x-init="setTheme(theme)"
    class="h-full font-sans antialiased"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Matik Growth Hub')</title>
    <script>
      (function(){
        const theme = localStorage.getItem('theme') || 'softblue';
        const darkThemes = ['dark', 'synthwave', 'halloween', 'forest', 'black', 'luxury', 'dracula', 'business', 'night', 'coffee'];
        document.documentElement.setAttribute('data-theme', theme);
        if (darkThemes.includes(theme)) {
            document.documentElement.classList.add('dark');
        }
      })();
    </script>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
    @stack('head')
</head>
{{-- FIXED: Menyatukan semua state (status) ke dalam satu x-data utama di body --}}
<body
    class="h-full bg-base-200 text-base-content"
    x-data="{
        isSidebarExpanded: localStorage.getItem('sidebarExpanded') === 'true',
        isMobileMenuOpen: false,
        toggleSidebar() {
            this.isSidebarExpanded = !this.isSidebarExpanded;
            localStorage.setItem('sidebarExpanded', this.isSidebarExpanded);
        }
    }"
    :class="{ 'overflow-hidden': isMobileMenuOpen }"
>
    <div class="flex min-h-screen">
        @include('partials.sidebar')
        <div
            class="flex flex-1 flex-col transition-all duration-300 ease-in-out"
            :class="{
                'lg:ml-64': isSidebarExpanded,
                'lg:ml-20': !isSidebarExpanded,
            }"
        >
            @include('partials.navbar')
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

