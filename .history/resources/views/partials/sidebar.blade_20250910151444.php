<div x-data="{ open: false }" @keydown.window.escape="open = false" class="h-full flex flex-col bg-base-200 text-base-content">
    <!-- Narrow sidebar -->
    <div class="hidden md:flex md:w-20 md:flex-col md:fixed md:inset-y-0">
        <div class="flex-1 flex flex-col min-h-0">
            <div class="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
                <div class="flex items-center flex-shrink-0 px-4">
                    <a href="{{ route('dashboard') }}">
                        <img class="h-8 w-auto" src="https://tailwindui.com/img/logos/workflow-logo-indigo-500-mark-white-text.svg" alt="Workflow">
                    </a>
                </div>
                <nav class="mt-5 flex-1 px-2 space-y-1">
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'bg-base-300' : '' }} hover:bg-base-300 text-base-content group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <x-heroicon-s-home class="h-6 w-6"/>
                    </a>
                     <a href="{{ route('leads.index') }}" class="{{ request()->routeIs('leads.*') ? 'bg-base-300' : '' }} hover:bg-base-300 text-base-content group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <x-heroicon-s-user-group class="h-6 w-6"/>
                    </a>
                     <a href="{{ route('subscriptions.index') }}" class="{{ request()->routeIs('subscriptions.*') ? 'bg-base-300' : '' }} hover:bg-base-300 text-base-content group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <x-heroicon-s-credit-card class="h-6 w-6"/>
                    </a>
                     <a href="{{ route('whatsapp.templates.index') }}" class="{{ request()->routeIs('whatsapp.templates.*') ? 'bg-base-300' : '' }} hover:bg-base-300 text-base-content group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <x-heroicon-s-chat-bubble-left-right class="h-6 w-6"/>
                    </a>
                     <a href="{{ route('tasks.index') }}" class="{{ request()->routeIs('tasks.*') ? 'bg-base-300' : '' }} hover:bg-base-300 text-base-content group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <x-heroicon-s-clipboard-document-list class="h-6 w-6"/>
                    </a>
                    <a href="{{ route('campaigns.index') }}" class="{{ request()->routeIs('campaigns.*') ? 'bg-base-300' : '' }} hover:bg-base-300 text-base-content group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <x-heroicon-s-megaphone class="h-6 w-6"/>
                    </a>
                </nav>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="md:pl-20 flex flex-col flex-1">
         @include('partials.navbar')
        <main class="flex-1">
            <div class="py-6">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                    @yield('content')
                </div>
            </div>
        </main>
    </div>
</div>

