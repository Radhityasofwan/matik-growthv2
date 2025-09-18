{{-- Wrapper untuk membuat navbar sticky dan memberikan efek glass --}}
<div class="sticky top-0 z-30 p-4 pb-0 sm:p-6 sm:pb-0">
    <header
        x-data="{ isSearchOpen: false }"
        data-aos="fade-down"
        class="navbar rounded-box border border-base-300/20 bg-base-100/80 shadow-sm backdrop-blur-lg"
    >
        {{-- Bagian Kiri: Tombol Menu & Search --}}
        <div class="navbar-start">
            {{-- Tombol Hamburger (Mobile) --}}
            <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="btn btn-ghost btn-circle lg:hidden" aria-label="Buka Menu">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>

            {{-- Kolom Pencarian (Desktop) --}}
            <div class="hidden md:block relative ml-2">
                 <label class="input input-bordered flex items-center gap-2 w-full max-w-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-4 h-4 opacity-70"><path fill-rule="evenodd" d="M9.965 11.026a5 5 0 1 1 1.06-1.06l2.755 2.754a.75.75 0 1 1-1.06 1.06l-2.755-2.754ZM10.5 7a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" clip-rule="evenodd" /></svg>
                    <input type="text" class="grow" placeholder="Search..." />
                </label>
            </div>
        </div>

        {{-- Bagian Kanan: Aksi & Profil --}}
        <div class="navbar-end">
            {{-- Tombol Search (Mobile) --}}
            <button @click="isSearchOpen = !isSearchOpen" class="btn btn-ghost btn-circle md:hidden" aria-label="Search">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-5 h-5 opacity-70"><path fill-rule="evenodd" d="M9.965 11.026a5 5 0 1 1 1.06-1.06l2.755 2.754a.75.75 0 1 1-1.06 1.06l-2.755-2.754ZM10.5 7a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" clip-rule="evenodd" /></svg>
            </button>

            {{-- Tombol Theme Toggle --}}
            <button
                @click="setTheme(theme === 'dark' ? 'softblue' : 'dark')"
                class="btn btn-ghost btn-circle"
                aria-label="Toggle Theme"
            >
                {{-- Ikon transisi halus antar tema --}}
                <svg x-show="theme !== 'dark'" x-transition.opacity.duration.300ms xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                <svg x-show="theme === 'dark'" x-transition.opacity.duration.300ms xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
            </button>

            {{-- Dropdown Notifikasi --}}
            <div class="dropdown dropdown-end" x-data="notifBell($el)" x-init="init()" data-unread="{{ (int) ($unreadNotificationsCount ?? 0) }}" data-preview='@json($notificationsPreview ?? [])'>
                <label tabindex="0" class="btn btn-ghost btn-circle" aria-label="Notifikasi">
                    <div class="indicator">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                        <template x-if="unread > 0">
                            <span class="badge badge-sm badge-primary indicator-item" x-text="unread > 9 ? '9+' : unread"></span>
                        </template>
                    </div>
                </label>
                <div tabindex="0" class="dropdown-content z-[1] mt-4 w-80 sm:w-96 bg-base-100 shadow-lg rounded-box border border-base-300/50">
                    <div class="p-3 border-b border-base-300/50 flex items-center justify-between">
                        <div class="font-semibold text-sm">Notifikasi</div>
                        <button class="btn btn-ghost btn-xs" @click="markAll()" :disabled="!items.some(i => i.source === 'notification' && !i.read_at)">
                            Tandai semua dibaca
                        </button>
                    </div>
                    <ul class="max-h-80 overflow-y-auto menu p-2">
                        <template x-if="items.length === 0">
                            <li class="p-4 text-sm text-center text-neutral/60">Belum ada notifikasi baru.</li>
                        </template>
                        <template x-for="n in items" :key="n.id">
                            <li>
                                <a @click.prevent="go(n)" :class="{'opacity-60': n.read_at && n.source === 'notification'}">
                                    <div class="flex-shrink-0 mt-1 self-start">
                                        <span class="w-2 h-2 rounded-full inline-block" :class="(n.source === 'notification' ? (n.read_at ? 'bg-base-300' : 'bg-primary') : 'bg-primary')"></span>
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-medium text-sm truncate" x-text="n.title || 'Notifikasi'"></p>
                                        <p class="text-xs text-neutral/60 line-clamp-2 whitespace-normal" x-text="n.message"></p>
                                        <p class="text-[11px] text-neutral/50 mt-1" x-text="n.created_at"></p>
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

            {{-- Dropdown Profil --}}
            <div class="dropdown dropdown-end ml-2">
                <label tabindex="0" class="btn btn-ghost flex items-center gap-2">
                    <div class="avatar">
                        <div class="w-8 rounded-full ring ring-primary/50 ring-offset-base-100 ring-offset-2">
                             <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=3B82F6&color=fff&size=128" alt="User Avatar" />
                        </div>
                    </div>
                     <div class="hidden sm:flex flex-col items-start">
                        <span class="font-semibold text-sm">{{ Str::words(Auth::user()->name, 2, '') }}</span>
                    </div>
                </label>
                <ul tabindex="0" class="menu menu-sm dropdown-content mt-4 z-[1] p-2 shadow-lg bg-base-100 rounded-box w-52 border border-base-300/50">
                    <li class="p-2">
                        <p class="font-semibold text-sm">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-neutral/60 truncate">{{ Auth::user()->email }}</p>
                    </li>
                    <div class="divider my-0"></div>
                    <li><a href="{{ route('profile.edit') }}">Profil Saya</a></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full text-left text-error p-2 hover:bg-error/10 rounded-lg">Log Out</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>

         <!-- Mobile Search Overlay -->
        <div
            x-show="isSearchOpen"
            @click.away="isSearchOpen = false"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-4"
            class="absolute top-full left-0 right-0 p-4 md:hidden"
            x-cloak
        >
            <div class="bg-base-100 rounded-box shadow-lg p-2">
                <label class="input input-bordered flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-4 h-4 opacity-70"><path fill-rule="evenodd" d="M9.965 11.026a5 5 0 1 1 1.06-1.06l2.755 2.754a.75.75 0 1 1-1.06 1.06l-2.755-2.754ZM10.5 7a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" clip-rule="evenodd" /></svg>
                    <input type="text" class="grow" placeholder="Search..." x-ref="mobileSearch" @keydown.escape.window="isSearchOpen = false" />
                </label>
            </div>
        </div>
    </header>
</div>

{{-- Script Notifikasi (mandiri) --}}
<script>
function notifBell(el) {
    const unreadInit = parseInt(el.dataset.unread || '0', 10) || 0;
    let preview = [];
    try { preview = JSON.parse(el.dataset.preview || '[]'); } catch (e) { preview = []; }

    return {
        open: false,
        unread: unreadInit,
        items: (preview || []).map(n => ({ source: n.source ?? 'notification', ...n })),
        pollMs: 15000,
        timer: null,
        csrf() { return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''; },
        feedUrl()    { return '{{ route("notifications.feed") }}'; },
        readUrl(id)  { return `/notifications/${encodeURIComponent(id)}/read`; },
        markAllUrl() { return '{{ route("notifications.read_all") }}'; },

        init() {
            this.fetchFeed();
            this.timer = setInterval(() => this.fetchFeed(), this.pollMs);
            document.addEventListener('close-all', () => this.open = false);
        },
        async fetchFeed() {
            try {
                const res = await fetch(this.feedUrl(), {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) return;
                const json = await res.json();
                this.unread = json.unread_count || 0;
                this.items  = (json.notifications || []).map(n => ({ source: n.source ?? 'notification', ...n }));
            } catch (e) { console.error('Gagal mengambil notifikasi:', e); }
        },
        async markReadIfNotification(n) {
            if (n.source !== 'notification' || n.read_at) return;
            try {
                await fetch(this.readUrl(n.id), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                n.read_at = new Date().toISOString();
                if (this.unread > 0) this.unread--;
            } catch (e) { console.error('Gagal menandai notifikasi:', e); }
        },
        async markAll() {
            if (!this.items.some(x => x.source === 'notification' && !x.read_at)) return;
            try {
                await fetch(this.markAllUrl(), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                this.items = this.items.map(n => n.source === 'notification' ? { ...n, read_at: new Date().toISOString() } : n);
                this.unread = 0;
            } catch (e) { console.error('Gagal menandai semua notifikasi:', e); }
        },
        async go(n) {
            await this.markReadIfNotification(n);
            if (n.url) window.location.href = n.url;
        }
    }
}
</script>
