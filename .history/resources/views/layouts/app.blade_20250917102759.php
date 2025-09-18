{{-- resources/views/layouts/app.blade.php — FINAL --}}
<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="{
        // Menambahkan semua tema DaisyUI + tema kustom Anda
        themes: ['softblue', 'light', 'dark', 'cupcake', 'bumblebee', 'emerald', 'corporate', 'synthwave', 'retro', 'cyberpunk', 'valentine', 'halloween', 'garden', 'forest', 'aqua', 'lofi', 'pastel', 'fantasy', 'wireframe', 'black', 'luxury', 'dracula', 'cmyk', 'autumn', 'business', 'acid', 'lemonade', 'night', 'coffee', 'winter'],
        // Daftar tema yang dianggap 'gelap' untuk keperluan lain (misal: chart)
        darkThemes: ['dark', 'synthwave', 'halloween', 'forest', 'black', 'luxury', 'dracula', 'business', 'night', 'coffee'],
        theme: localStorage.getItem('theme') || 'softblue',
        setTheme(t) {
            this.theme = t;
            localStorage.setItem('theme', t);
            document.documentElement.setAttribute('data-theme', t);
            // Menambahkan/menghapus kelas 'dark' secara manual untuk kompatibilitas dengan Tailwind `dark:` variant
            if (this.darkThemes.includes(t)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    }"
    x-init="
        // Inisialisasi tema saat halaman dimuat
        setTheme(theme);
    "
    class="h-full font-sans antialiased"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Matik Growth Hub')</title>

    {{-- Script untuk mencegah FOUC (Flash of Unstyled Content) saat tema dimuat --}}
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

    {{-- Vite Asset Bundles --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>[x-cloak]{display:none!important}</style>
    @stack('head')
</head>
<body
    class="h-full bg-base-200 text-base-content"
    x-data="{
        isSidebarExpanded: localStorage.getItem('sidebarExpanded') === 'true',
        isMobileMenuOpen: false,
    }"
    :class="{ 'overflow-hidden': isMobileMenuOpen }"
>
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        @include('partials.sidebar')

        {{-- Main Content Wrapper --}}
        <div
            class="flex flex-1 flex-col transition-all duration-300 ease-in-out"
            :class="{
                'lg:ml-64': isSidebarExpanded,
                'lg:ml-20': !isSidebarExpanded,
            }"
        >
            {{-- Navbar --}}
            @include('partials.navbar')

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

