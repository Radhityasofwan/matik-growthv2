<!DOCTYPE html>
<html lang="en" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Matik Growth Hub')</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.2/dist/full.min.css" rel="stylesheet" type="text/css" />
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- JS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <!-- App Styles -->
    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>

    <!-- Custom Tailwind Config -->
    <script>
        tailwind.config = {
            darkMode: 'class', // Important for DaisyUI dark theme to work with our class-based toggle
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            }
            // The 'require' error is fixed by removing the plugins array.
            // DaisyUI is now loaded via the CDN link above.
        }
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900" x-cloak>
<div class="flex h-screen">
    @include('partials.sidebar')
    <div class="flex-1 flex flex-col overflow-hidden">
        @include('partials.navbar')
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 dark:bg-gray-900">
            @yield('content')
        </main>
    </div>
</div>

<script>
    AOS.init({
        duration: 800,
        once: true,
    });
</script>

@stack('scripts')

</body>
</html>

