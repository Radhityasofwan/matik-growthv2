@php
    $unreadInit  = $unreadNotificationsCount ?? 0;
    $previewInit = $notificationsPreview ?? [];
@endphp

<header
    x-data="notifBell('/notifications', {{ (int) $unreadInit }}, @json($previewInit))"
    x-init="init()"
    class="flex items-center justify-between h-20 px-4 sm:px-6 md:px-8 border-b border-base-300/50 bg-base-100 flex-shrink-0"
>
    <div class="flex items-center gap-4">
        <button @click="$dispatch('toggle-mobile-menu')" class="btn btn-ghost btn-circle lg:hidden" aria-label="Menu">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
        </button>
        <div class="hidden md:block relative">
            <input type="text" placeholder="Search..." class="input input-bordered w-full max-w-xs pl-10" />
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-neutral/40" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/></svg>
        </div>
    </div>

    <div class="flex items-center gap-4">
        <!-- Notifikasi -->
        <div class="dropdown dropdown-end" @keydown.escape="$dispatch('close-all')">
            <button @click="open = !open; if(open) fetchFeed()" class="btn btn-ghost btn-circle" aria-label="Notifikasi">
                <div class="indicator">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                    <template x-if="unread > 0">
                        <span class="badge badge-sm badge-primary indicator-item" x-text="unread" aria-live="polite"></span>
                    </template>
                </div>
            </button>

            <div class="dropdown-content z-10 mt-3 w-80 rounded-xl shadow-xl bg-base-100 border border-base-300/50">
                <div class="p-3 border-b border-base-300/50 flex items-center justify-between">
                    <div class="font-semibold">Notifikasi</div>
                    <button
                        class="btn btn-ghost btn-xs"
                        @click="markAll()"
                        :class="{'btn-disabled opacity-50 pointer-events-none': !items.some(i => i.source === 'notification' && !i.read_at)}"
                        :disabled="!items.some(i => i.source === 'notification' && !i.read_at)"
                    >Tandai semua dibaca</button>
                </div>

                <ul class="max-h-80 overflow-auto">
                    <template x-if="items.length === 0">
                        <li class="px-4 py-6 text-sm text-neutral/60 text-center">Belum ada notifikasi.</li>
                    </template>

                    <template x-for="n in items" :key="n.id">
                        <li>
                            <button
                                class="w-full text-left px-4 py-3 hover:bg-base-200 flex gap-3"
                                :class="{'opacity-60': n.read_at && n.source === 'notification'}"
                                @click="go(n)"
                            >
                                <div class="mt-0.5">
                                    <span class="w-2.5 h-2.5 rounded-full"
                                          :class="(n.source === 'notification' ? (n.read_at ? 'bg-base-300' : 'bg-primary') : 'bg-primary')"></span>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-medium text-sm truncate" x-text="n.title || 'Notifikasi'"></div>
                                    <div class="text-xs text-neutral/60 line-clamp-2" x-text="n.message"></div>
                                    <div class="text-[11px] text-neutral/50 mt-1" x-text="n.created_at"></div>
                                </div>
                            </button>
                        </li>
                    </template>
                </ul>

                <div class="p-3 border-t border-base-300/50">
                    <a href="{{ route('notifications.index') }}" class="btn btn-ghost btn-sm w-full">Lihat semua</a>
                </div>
            </div>
        </div>

        <!-- Profil -->
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
            <div x-show="open" x-transition class="absolute right-0 mt-2 w-48 origin-top-right bg-base-100 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-10" x-cloak>
                <div class="py-1">
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-neutral hover:bg-base-200">Profil Saya</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();" class="block w-full text-left px-4 py-2 text-sm text-error hover:bg-base-200">Log Out</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

{{-- Inline JS agar tidak tergantung @vite/app.js --}}
<script>
function notifBell(basePath, unreadInit = 0, preview = []) {
    return {
        open: false,
        unread: unreadInit,
        items: (preview || []).map(p => ({ source: p.source ?? 'notification', ...p })),
        pollMs: 10000,
        timer: null,

        csrf() {
            const m = document.querySelector('meta[name="csrf-token"]');
            return m ? m.getAttribute('content') : '';
        },
        feedUrl()    { return `${basePath}/feed`; },
        readUrl(id)  { return `${basePath}/${encodeURIComponent(id)}/read`; },
        markAllUrl() { return `${basePath}/read-all`; },

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
            } catch (e) {}
        },

        async markReadIfNotification(n) {
            if (n.source !== 'notification') return;
            try {
                await fetch(this.readUrl(n.id), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                n.read_at = new Date().toISOString();
                if (this.unread > 0) this.unread--;
            } catch (e) {}
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
            } catch (e) {}
        },

        async go(n) {
            await this.markReadIfNotification(n);
            if (n.url) window.location.href = n.url;
        }
    }
}
</script>
