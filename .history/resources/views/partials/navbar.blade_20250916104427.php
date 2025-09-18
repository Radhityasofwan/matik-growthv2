{{--
    CATATAN:
    Navbar ini mengharapkan variabel $unreadNotificationsCount dari AppServiceProvider atau
    View Composer agar badge notifikasi berfungsi.

    Contoh di AppServiceProvider.php dalam method boot():
    view()->composer('partials.navbar', function ($view) {
        $view->with('unreadNotificationsCount', auth()->check() ? auth()->user()->unreadNotifications()->count() : 0);
    });
--}}
<header class="flex items-center justify-between h-20 px-4 sm:px-6 md:px-8 border-b border-base-300/50 bg-base-100 flex-shrink-0">
    <!-- Tombol Hamburger (Mobile) & Search -->
    <div class="flex items-center gap-4">
        <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="btn btn-ghost btn-circle lg:hidden">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
        </button>

        <!-- Search Bar (Desktop) -->
        <div class="hidden md:block relative">
            <input type="text" placeholder="Search..." class="input input-bordered w-full max-w-xs pl-10" />
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-neutral/40"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/></svg>
        </div>
    </div>

    <!-- Ikon Notifikasi & Menu Profil -->
    <div class="flex items-center gap-4">
        <!-- Notifikasi -->
        <a href="{{ route('notifications.index') }}" class="btn btn-ghost btn-circle">
            <div class="indicator">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                @isset($unreadNotificationsCount)
                    @if($unreadNotificationsCount > 0)
                        <span class="badge badge-sm badge-primary indicator-item">{{ $unreadNotificationsCount }}</span>
                    @endif
                @endisset
            </div>
        </a>

        <!-- Dropdown Profil -->
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" @click.away="open = false" class="flex items-center gap-2">
                <div class="avatar">
                    <div class="w-10 rounded-full">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=3B82F6&color=fff" alt="User Avatar" />
                    </div>
                </div>
                <div class="hidden sm:flex flex-col items-start">
                    <span class="font-semibold text-sm">{{ Auth::user()->name }}</span>
                    <span class="text-xs text-neutral/60">{{ Auth::user()->email }}</span>
                </div>
            </button>

            <!-- Menu Dropdown -->
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute right-0 mt-2 w-48 origin-top-right bg-base-100 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-10"
                x-cloak
            >
                <div class="py-1">
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-neutral hover:bg-base-200">
                        Profil Saya
                    </a>

                    <!-- Tombol Logout -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <a
                            href="{{ route('logout') }}"
                            onclick="event.preventDefault(); this.closest('form').submit();"
                            class="block w-full text-left px-4 py-2 text-sm text-error hover:bg-base-200"
                        >
                            Log Out
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

