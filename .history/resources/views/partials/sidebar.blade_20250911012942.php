<!-- Desktop Sidebar -->
<aside
    class="hidden lg:flex lg:flex-shrink-0 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transition-all duration-300"
    :class="isSidebarExpanded ? 'w-64' : 'w-20'"
>
    <div class="flex flex-col h-full w-full">
        <!-- Logo -->
        <div class="h-16 flex items-center justify-center border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
            <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 text-blue-600 dark:text-blue-400">
                <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span x-show="isSidebarExpanded" class="font-bold text-xl transition-opacity duration-300">Matik</span>
            </a>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto">
            @include('partials.sidebar-menu')
        </nav>
    </div>
</aside>

<!-- Mobile Sidebar (Off-canvas) -->
<div x-show="isMobileMenuOpen" class="fixed inset-0 flex z-40 lg:hidden" x-cloak>
    <!-- Overlay -->
    <div x-show="isMobileMenuOpen"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-600 bg-opacity-75" @click="isMobileMenuOpen = false"></div>

    <!-- Sidebar Panel -->
    <div x-show="isMobileMenuOpen"
         x-transition:enter="transition ease-in-out duration-300 transform"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in-out duration-300 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="relative flex-1 flex flex-col max-w-xs w-full bg-white dark:bg-gray-800">

        <!-- Logo -->
         <div class="h-16 flex items-center justify-center border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
            <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 text-blue-600 dark:text-blue-400">
                <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span class="font-bold text-xl">Matik</span>
            </a>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto">
            @include('partials.sidebar-menu', ['isMobile' => true])
        </nav>
    </div>
</div>
