<header class="h-16 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
    <div class="flex items-center justify-between h-full px-6">
        <!-- Sidebar toggles (tetap) -->
        <button @click="isSidebarExpanded = !isSidebarExpanded" class="hidden lg:block p-2 rounded-full text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700" aria-label="Toggle Sidebar">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="lg:hidden p-2 rounded-full text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700" aria-label="Toggle Mobile Menu">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <div class="flex items-center space-x-2 md:space-x-4">
            <!-- Theme toggler (biarkan seperti semula) -->
            <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-ghost btn-circle">
                    <svg x-show="darkMode === 'light' || (darkMode === 'system' && !window.matchMedia('(prefers-color-scheme: dark)').matches)" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <svg x-show="darkMode === 'dark' || (darkMode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                </label>
                <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-36">
                    <li><a @click.prevent="darkMode = 'light'">Light</a></li>
                    <li><a @click.prevent="darkMode = 'dark'">Dark</a></li>
                    <li><a @click.prevent="darkMode = 'system'">System</a></li>
                </ul>
            </div>

            <!-- Profile Dropdown -->
            <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-ghost btn-circle avatar">
                    <div class="w-9 rounded-full">
                        <img
                            alt="User Avatar"
                            src="{{ auth()->check() ? auth()->user()->avatar_url : 'https://ui-avatars.com/api/?name=User&background=1F2937&color=fff&format=png' }}"
                            loading="lazy"
                            onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'User') }}&background=1F2937&color=fff&format=png';"
                        >
                    </div>
                </label>
                <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-52">
                    <li><p class="font-semibold p-2">{{ auth()->user()->name }}</p></li>
                    <li><a href="{{ route('profile.edit') }}">Profile</a></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>
