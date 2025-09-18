@php
    // Inisialisasi data notifikasi dari controller
    $unreadInit  = (int) ($unreadNotificationsCount ?? 0);
    $previewInit = $notificationsPreview ?? [];
@endphp

{{-- Wrapper untuk memposisikan navbar yang sticky dan memberikan padding --}}
<div class="sticky top-0 z-30 px-4 sm:px-6 md:px-8 py-4">
    <header
        x-data="notifBell($el)"
        x-init="init()"
        data-unread="{{ $unreadInit }}"
        data-preview='@js($previewInit)'
        {{-- Efek Glassmorphism: Latar belakang semi-transparan, backdrop-blur, sudut membulat, dan bayangan --}}
        class="navbar bg-base-100/70 backdrop-blur-lg rounded-box shadow-lg border border-base-300/20"
    >
        {{-- Bagian Kiri: Hamburger & Search --}}
        <div class="navbar-start">
            {{-- Tombol Hamburger untuk membuka sidebar di mobile --}}
            <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="btn btn-ghost btn-circle lg:hidden" aria-label="Buka Menu">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
            </button>

            {{-- Kolom Pencarian --}}
            <div class="hidden md:block relative ml-2">
                <input type="text" placeholder="Search..." class="input input-bordered w-full max-w-xs pl-10" />
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-neutral/40" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/></svg>
            </div>
        </div>

        {{-- Bagian Kanan: Notifikasi & Profil --}}
        <div class="navbar-end">
            {{-- Dropdown Notifikasi --}}
            <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-ghost btn-circle" @click="open = !open; if(open) fetchFeed()" aria-label="Notifikasi">
                    <div class="indicator">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                        <template x-if="unread > 0">
                            <span class="badge badge-sm badge-primary indicator-item" x-text="unread" aria-live="polite"></span>
                        </template>
                    </div>
                </label>

                <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-80 mt-4 border border-base-300/50">
                     <div class="p-2 border-b border-base-300/50 flex items-center justify-between">
                        <div class="font-semibold text-sm">Notifikasi</div>
                        <button class="btn btn-ghost btn-xs" @click="markAll()" :disabled="!items.some(i => i.source === 'notification' && !i.read_at)">
                            Tandai semua dibaca
                        </button>
                    </div>
                    <div class="max-h-80 overflow-y-auto">
                        <template x-if="items.length === 0">
                            <li class="p-4 text-sm text-neutral/60 text-center">Belum ada notifikasi.</li>
                        </template>
                        <template x-for="n in items" :key="n.id">
                            <li>
                                <a @click.prevent="go(n)" :class="{'opacity-60': n.read_at && n.source === 'notification'}">
                                     <div class="flex gap-3">
                                        <div class="mt-1">
                                            <span class="w-2 h-2 rounded-full inline-block" :class="(n.source === 'notification' ? (n.read_at ? 'bg-base-300' : 'bg-primary') : 'bg-primary')"></span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="font-medium text-sm truncate" x-text="n.title || 'Notifikasi'"></p>
                                            <p class="text-xs text-neutral/60 line-clamp-2" x-text="n.message"></p>
                                            <p class="text-[11px] text-neutral/50 mt-1" x-text="n.created_at"></p>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        </template>
                    </div>
                     <div class="p-1 border-t border-base-300/50">
                        <a href="{{ route('notifications.index') }}" class="btn btn-ghost btn-sm w-full">Lihat semua notifikasi</a>
                    </div>
                </ul>
            </div>

            {{-- Dropdown Profil --}}
            <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-ghost btn-circle avatar">
                    <div class="w-10 rounded-full ring ring-primary ring-offset-base-100 ring-offset-2">
                         <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=3B82F6&color=fff" alt="User Avatar" />
                    </div>
                </label>
                <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52 border border-base-300/50">
                    <li class="p-2">
                        <p class="font-semibold text-sm">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-neutral/60 truncate">{{ Auth::user()->email }}</p>
                    </li>
                    <div class="divider my-0"></div>
                    <li><a href="{{ route('profile.edit') }}">Profil Saya</a></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full text-left text-error px-4 py-2 text-sm hover:bg-base-200 rounded-lg">Log Out</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>
</div>

{{-- Script Notifikasi: Diletakkan di sini agar mandiri dan tidak bergantung pada urutan load file JS --}}
<script>
function notifBell(el) {
    const unreadInit = parseInt(el.dataset.unread || '0', 10) || 0;
    let preview = [];
    try { preview = JSON.parse(el.dataset.preview || '[]'); } catch (e) { preview = []; }

    return {
        open: false,
        unread: unreadInit,
        items: (preview || []).map(n => ({ source: n.source ?? 'notification', ...n })),
        pollMs: 10000,
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

