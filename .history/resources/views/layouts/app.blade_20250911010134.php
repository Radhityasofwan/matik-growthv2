{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{
        darkMode: localStorage.getItem('darkMode') || 'system',
        init() {
            this.$watch('darkMode', (val) => {
                localStorage.setItem('darkMode', val);
                this.updateTheme();
            });
            this.updateTheme();
            // sinkron dengan perubahan prefers-color-scheme OS
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => this.updateTheme());
        },
        isDarkMode() {
            if (this.darkMode === 'system') {
                return window.matchMedia('(prefers-color-scheme: dark)').matches;
            }
            return this.darkMode === 'dark';
        },
        updateTheme() {
            document.documentElement.classList.toggle('dark', this.isDarkMode());
        }
      }"
      x-init="init()">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Matik Growth') }}</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet"/>

    {{-- App assets (Vite) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- UI libs (CDN) --}}
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.10.2/dist/full.min.css" rel="stylesheet" type="text/css"/>
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    {{-- AlpineJS (PASTIKAN hanya satu sumber) --}}
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
<div x-data="{ sidebarOpen: false }" class="flex min-h-screen">
    {{-- Sidebar --}}
    @include('partials.sidebar')

    {{-- Main column --}}
    <div class="flex min-h-screen flex-1 flex-col">
        {{-- Navbar (sticky) --}}
        @include('partials.navbar')

        {{-- Main Content --}}
        <main class="flex-1 overflow-y-auto">
            <div class="container mx-auto px-6 py-8">
                {{-- Support both component slots and blade sections --}}
                {{ $slot ?? '' }}
                @yield('content')
            </div>
        </main>
    </div>
</div>

{{-- AOS init --}}
<script src="https://unpkg.com/aos@next/dist/aos.js"></script>
<script>
    AOS.init({ duration: 800, once: true });
</script>

@stack('scripts')
</body>
</html>
