    {{-- resources/views/partials/navbar.blade.php — FINAL --}}
    <div class="sticky top-0 z-30 p-4 pb-0 sm:p-6 sm:pb-0">
    <header
        x-data="{ isSearchOpen: false }"
        data-aos="fade-down"
        class="navbar rounded-box border border-base-300/20 bg-base-100/80 shadow-md backdrop-blur-lg"
        role="banner"
    >
        {{-- Kiri: Hamburger + Search (desktop) --}}
        <div class="navbar-start gap-1">
        {{-- Hamburger (Mobile) --}}
        <button
            type="button"
            @click="isMobileMenuOpen = !isMobileMenuOpen"
            class="btn btn-ghost btn-circle lg:hidden"
            :aria-expanded="isMobileMenuOpen.toString()"
            aria-controls="app-sidebar"
            aria-label="Buka menu"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        {{-- Search (Desktop) --}}
        <div class="hidden md:block relative ml-1">
            <label class="input input-bordered flex items-center gap-2 w-full max-w-xs">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-4 h-4 opacity-70"><path fill-rule="evenodd" d="M9.965 11.026a5 5 0 1 1 1.06-1.06l2.755 2.754a.75.75 0 1 1-1.06 1.06l-2.755-2.754ZM10.5 7a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" clip-rule="evenodd"/></svg>
            <input type="text" class="grow border-0 bg-transparent focus:outline-none focus:ring-0" placeholder="Search…" aria-label="Cari"/>
            </label>
        </div>
        </div>

        {{-- Kanan: Aksi, Notif, Profil --}}
        <div class="navbar-end gap-1">
        {{-- Search toggle (Mobile) --}}
        <button
            type="button"
            @click="isSearchOpen = !isSearchOpen; $nextTick(() => $refs.mobileSearch?.focus())"
            class="btn btn-ghost btn-circle md:hidden"
            :aria-expanded="isSearchOpen.toString()"
            aria-controls="mobile-search"
            aria-label="Buka pencarian"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-5 h-5 opacity-70"><path fill-rule="evenodd" d="M9.965 11.026a5 5 0 1 1 1.06-1.06l2.755 2.754a.75.75 0 1 1-1.06 1.06l-2.755-2.754ZM10.5 7a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" clip-rule="evenodd"/></svg>
        </button>

        {{-- Theme toggle --}}
        <button
            type="button"
            @click="setTheme(theme === 'dark' ? 'softblue' : 'dark')"
            class="btn btn-ghost btn-circle"
            aria-label="Ubah tema"
        >
            <svg x-show="theme !== 'dark'" x-transition.opacity.duration.300ms xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
            <svg x-show="theme === 'dark'" x-transition.opacity.duration.300ms xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
        </button>

        {{-- Notifikasi (pakai data-* untuk URL agar tidak hardcode route() di JS Vite) --}}
        <div
            class="dropdown dropdown-end"
            x-data="notifBell($el)"
            x-init="init()"
            data-unread="{{ (int) ($unreadNotificationsCount ?? 0) }}"
            data-preview='@json($notificationsPreview ?? [])'
            data-feed="{{ route('notifications.feed') }}"
            data-read-base="/notifications"
            data-read-all="{{ route('notifications.read_all') }}"
        >
            <label tabindex="0" class="btn btn-ghost btn-circle" aria-label="Notifikasi" role="button">
            <div class="indicator">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                <template x-if="unread > 0">
                <span class="badge badge-sm badge-primary indicator-item" x-text="unread > 9 ? '9+' : unread" aria-label="Jumlah notifikasi belum dibaca"></span>
                </template>
            </div>
            </label>

            <div tabindex="0" class="dropdown-content z-[1] mt-4 w-80 sm:w-96 bg-base-100 shadow-lg rounded-box border border-base-300/50">
            <div class="p-3 border-b border-base-300/50 flex items-center justify-between">
                <div class="font-semibold text-sm text-base-content">Notifikasi</div>
                <button type="button" class="btn btn-ghost btn-xs"
                @click="markAll()" :disabled="!items.some(i => i.source === 'notification' && !i.read_at)">
                Tandai semua dibaca
                </button>
            </div>

            <ul class="max-h-80 overflow-y-auto menu p-2" role="listbox" aria-label="Daftar notifikasi">
                <template x-if="items.length === 0">
                <li class="p-4 text-sm text-center text-base-content/60">Belum ada notifikasi baru.</li>
                </template>

                <template x-for="n in items" :key="n.id">
                <li>
                    <a @click.prevent="go(n)" :class="{'opacity-60': n.read_at && n.source === 'notification'}" role="option">
                    <div class="flex-shrink-0 mt-1 self-start">
                        <span class="w-2 h-2 rounded-full inline-block"
                            :class="(n.source === 'notification' ? (n.read_at ? 'bg-base-300' : 'bg-primary') : 'bg-primary')"></span>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-sm truncate text-base-content" x-text="n.title || 'Notifikasi'"></p>
                        <p class="text-xs text-base-content/70 line-clamp-2 whitespace-normal" x-text="n.message"></p>
                        <p class="text-[11px] text-base-content/50 mt-1" x-text="n.created_at"></p>
                    </div>
                    </a>
                </li>
                </template>
            </ul>

            <div class="p-2 border-t border-base-300/50">
                <a href="{{ route('notifications.index') }}" class="btn btn-ghost btn-sm w-full">Lihat Semua Notifikasi</a>
            </div>
            </div>
        </div>

        {{-- Profil --}}
        <div class="dropdown dropdown-end ml-1">
            <label tabindex="0" class="btn btn-ghost flex items-center gap-2" role="button" aria-haspopup="menu">
            <div class="avatar">
                <div class="w-8 rounded-full ring ring-primary/50 ring-offset-base-100 ring-offset-2">
                <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=3B82F6&color=fff&size=128" alt="Avatar pengguna"/>
                </div>
            </div>
            <div class="hidden sm:flex flex-col items-start">
                <span class="font-semibold text-sm text-base-content">{{ Str::words(Auth::user()->name, 2, '') }}</span>
            </div>
            </label>

            <ul tabindex="0" class="menu menu-sm dropdown-content mt-4 z-[1] p-2 shadow-lg bg-base-100 rounded-box w-52 border border-base-300/50" role="menu" aria-label="Menu profil">
            <li class="p-2">
                <p class="font-semibold text-sm text-base-content">{{ Auth::user()->name }}</p>
                <p class="text-xs text-base-content/70 truncate">{{ Auth::user()->email }}</p>
            </li>
            <div class="divider my-0"></div>
            <li><a href="{{ route('profile.edit') }}" role="menuitem">Profil Saya</a></li>
            <li>
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button type="submit" class="w-full text-left text-error p-2 hover:bg-error/10 rounded-lg" role="menuitem">Log Out</button>
                </form>
            </li>
            </ul>
        </div>
        </div>

        {{-- Mobile Search Overlay --}}
        <div
        id="mobile-search"
        x-show="isSearchOpen"
        @click.outside="isSearchOpen = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-4"
        class="absolute top-full left-0 right-0 p-4 md:hidden"
        x-cloak
        role="dialog" aria-modal="true" aria-label="Pencarian"
        >
        <div class="bg-base-100 rounded-box shadow-lg p-2">
            <label class="input input-bordered flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-4 h-4 opacity-70"><path fill-rule="evenodd" d="M9.965 11.026a5 5 0 1 1 1.06-1.06l2.755 2.754a.75.75 0 1 1-1.06 1.06l-2.755-2.754ZM10.5 7a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" clip-rule="evenodd"/></svg>
            <input type="text" class="grow border-0 bg-transparent focus:outline-none focus:ring-0"
                    placeholder="Search…" x-ref="mobileSearch" @keydown.escape.window="isSearchOpen = false" aria-label="Cari"/>
            </label>
        </div>
        </div>
    </header>
    </div>
