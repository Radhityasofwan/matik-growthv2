<header class="z-10 py-4 bg-white shadow-md dark:bg-gray-800">
    <div class="container flex items-center justify-between h-full px-6 mx-auto text-primary-600 dark:text-primary-300">
        <!-- Mobile hamburger -->
        <button class="p-1 mr-5 -ml-1 rounded-md lg:hidden focus:outline-none focus:shadow-outline-primary" @click="sidebarOpen = !sidebarOpen" aria-label="Menu">
            <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
            </svg>
        </button>

        <!-- Search input (optional) -->
        <div class="flex justify-center flex-1 lg:mr-32">
        </div>

        <ul class="flex items-center flex-shrink-0 space-x-6">
            <!-- Theme toggler -->
            <li class="flex">
                @include('partials.theme-toggle')
            </li>
            <!-- Notifications menu -->
            <li class="relative">
                 @include('partials.notifications-dropdown')
            </li>
            <!-- Profile menu -->
            <li class="relative">
                @include('partials.profile-dropdown')
            </li>
        </ul>
    </div>
</header>

