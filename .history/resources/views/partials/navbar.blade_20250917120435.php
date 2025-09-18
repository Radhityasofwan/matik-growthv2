{{-- resources/views/partials/navbar.blade.php — FINAL --}}
<div class="sticky top-0 z-30 p-4 pb-0 sm:p-6 sm:pb-0">
  <header
    x-data="{
      isSearchOpen: false,
      qaOpen: false,
      get firstName(){ return '{{ Str::of(Auth::user()->name)->explode(' ')->first() }}'; },
      greet(){
        const h = new Date().getHours();
        if (h >= 5 && h < 11) return 'Selamat pagi';
        if (h >= 11 && h < 15) return 'Selamat siang';
        if (h >= 15 && h < 18) return 'Selamat sore';
        return 'Selamat malam';
      }
    }"
    data-aos="fade-down"
    class="navbar rounded-box border border-base-300/20 bg-base-100/80 shadow-md backdrop-blur-lg"
    role="banner"
  >
    {{-- Kiri: Hamburger (Mobile) + Greeting (Desktop) --}}
    <div class="navbar-start gap-2">
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

      {{-- Greeting (Desktop) --}}
      <div class="hidden lg:flex items-center">
        <span class="font-semibold text-base-content/80">
          <span x-text="greet()"></span>, {{ Str::of(Auth::user()->name)->explode(' ')->first() }} 👋
        </span>
      </div>
    </div>

    {{-- Kanan: Search, Quick Action, Theme, Notifikasi, Profil --}}
    <div class="navbar-end gap-1">
      {{-- Search trigger (ikon) --}}
      <button
        type="button"
        @click="isSearchOpen = true; $nextTick(() => $refs.searchInput?.focus())"
        class="btn btn-ghost btn-circle"
        aria-label="Buka pencarian"
      >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-5 h-5 opacity-80"><path fill-rule="evenodd" d="M9.965 11.026a5 5 0 1 1 1.06-1.06l2.755 2.754a.75.75 0 1 1-1.06 1.06l-2.755-2.754ZM10.5 7a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" clip-rule="evenodd"/></svg>
      </button>

      {{-- Quick Action --}}
      <div class="dropdown dropdown-end">
        <label tabindex="0" class="btn btn-ghost btn-circle" aria-label="Quick Action">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2 3 14h7l-1 8 10-12h-7l1-8Z"/></svg>
        </label>
        <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-56 z-20 border border-base-300/50">
          <li class="menu-title"><span>Aksi Cepat</span></li>
          <li><a href="{{ route('leads.index') }}">Buka Leads</a></li>
          <li><a href="{{ route('whatsapp.broadcast.create') }}">WA Broadcast</a></li>
          <li><a href="{{ route('tasks.index') }}">Tasks</a></li>
          <li><a href="{{ route('assets') }}">Assets</a></li>
          <div class="divider my-1"></div>
          <li><a href="{{ route('notifications.index') }}">Semua Notifikasi</a></li>
        </ul>
      </div>

     {{-- THEME SWITCHER — compact preview grid + search --}}
<div
  class="dropdown dropdown-end"
  x-data="{
    q: '',
    filtered() {
      const s = this.q.toLowerCase().trim();
      return s ? themes.filter(t => t.toLowerCase().includes(s)) : themes;
    }
  }"
>
  <label tabindex="0" class="btn btn-ghost btn-circle" aria-label="Pilih Tema">
    {{-- Icon palet + indikator tema aktif --}}
    <div class="indicator">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21.5 12c0-5.25-4.25-9.5-9.5-9.5S2.5 6.75 2.5 12s4.25 9.5 9.5 9.5s9.5-4.25 9.5-9.5z"/>
        <path d="M12 2.5a9.5 9.5 0 0 0 0 19V2.5z"/>
      </svg>
      <span class="indicator-item badge badge-xs translate-x-1 translate-y-1" :data-theme="theme"></span>
    </div>
  </label>

  <div tabindex="0" class="dropdown-content z-20 mt-3 w-72 sm:w-80 rounded-box border border-base-300/50 bg-base-100 shadow-lg">
    <div class="p-3">
      {{-- Search --}}
      <label class="input input-bordered input-sm flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-70" viewBox="0 0 16 16" fill="currentColor">
          <path fill-rule="evenodd" d="M9.965 11.026a5 5 0 1 1 1.06-1.06l2.755 2.754a.75.75 0 1 1-1.06 1.06l-2.755-2.754ZM10.5 7a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" clip-rule="evenodd"/>
        </svg>
        <input type="text" class="grow border-0 bg-transparent focus:outline-none" placeholder="Cari tema…" x-model="q" />
      </label>

      {{-- Grid tema dengan preview mini (tanpa mengubah tema global sampai diklik) --}}
      <ul class="mt-3 grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-80 overflow-y-auto pr-1">
        <template x-for="t in filtered()" :key="t">
          <li>
            <button
              type="button"
              @click.prevent="setTheme(t)"
              class="w-full group rounded-xl border border-base-300/60 hover:border-primary/60 transition-colors p-2 text-left"
              :class="{'ring-2 ring-primary/60': theme === t}"
              :aria-pressed="(theme===t).toString()"
            >
              {{-- Preview box: gunakan data-theme lokal agar warna contoh mengikuti tema kandidat --}}
              <div :data-theme="t" class="rounded-lg p-2 bg-base-200">
                <div class="flex items-center gap-1.5">
                  <span class="inline-block h-3 w-3 rounded-full bg-primary"></span>
                  <span class="inline-block h-3 w-3 rounded-full bg-secondary"></span>
                  <span class="inline-block h-3 w-3 rounded-full bg-accent"></span>
                </div>
                <div class="mt-1 h-3 rounded bg-base-300"></div>
              </div>
              <div class="mt-1.5 flex items-center justify-between">
                <span class="text-xs font-medium text-base-content/80" x-text="t.charAt(0).toUpperCase()+t.slice(1)"></span>
                <span class="badge badge-ghost badge-xs" x-show="theme === t">aktif</span>
              </div>
            </button>
          </li>
        </template>
      </ul>
    </div>
  </div>
</div>

      {{-- Notifikasi (robust: gunakan notifBell jika ada; jika tidak, fallback inline) --}}
      <div
        class="dropdown dropdown-end"
        x-data="(window.notifBell ? window.notifBell($el) : {
          items: [],
          unread: Number($el.dataset.unread || 0),
          init(){
            // Fallback: isi dari preview; jika ada endpoint feed, coba fetch
            let preview = [];
            try { preview = JSON.parse($el.dataset.preview || '[]'); } catch(e) {}
            if (Array.isArray(preview) && preview.length) { this.items = preview; }
            const feed = $el.dataset.feed;
            if (feed) {
              // fetch non-blocking; jika gagal, tetap pakai preview
              fetch(feed, { headers: {'X-Requested-With':'XMLHttpRequest'} })
                .then(r => r.ok ? r.json() : Promise.reject())
                .then(d => { if (Array.isArray(d?.data)) { this.items = d.data; this.unread = Number(d?.unread || this.unread); } })
                .catch(() => {});
            }
          },
          go(n){ const base = $el.dataset.readBase || '/notifications'; window.location.href = `${base}/${n.id}`; },
          markAll(){
            const url = $el.dataset.readAll;
            if (!url) return;
            fetch(url, { method:'POST', headers: {'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':'{{ csrf_token() }}'} })
              .then(() => { this.items = this.items.map(i => ({...i, read_at: i.read_at || (new Date()).toISOString()})); this.unread = 0; })
              .catch(()=>{});
          }
        }))"
        x-init="init && init()"
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
              @click="markAll && markAll()" :disabled="!(items||[]).some(i => i.source === 'notification' && !i.read_at)">
              Tandai semua dibaca
            </button>
          </div>

          <ul class="max-h-80 overflow-y-auto menu p-2" role="listbox" aria-label="Daftar notifikasi">
            <template x-if="!(items||[]).length">
              <li class="p-4 text-sm text-center text-base-content/60">Belum ada notifikasi baru.</li>
            </template>

            <template x-for="n in items" :key="n.id">
              <li>
                <a @click.prevent="go && go(n)" :class="{'opacity-60': n.read_at && n.source === 'notification'}" role="option">
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

    {{-- SEARCH MODAL (command palette style) --}}
    <dialog x-show="isSearchOpen" @keydown.escape.window="isSearchOpen=false" @click.self="isSearchOpen=false" x-cloak class="modal">
      <div class="modal-box bg-base-100">
        <form method="dialog">
          <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2" @click="isSearchOpen=false" aria-label="Tutup">✕</button>
        </form>
        <h3 class="font-semibold mb-2 text-base-content">Pencarian</h3>
        <label class="input input-bordered flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-4 h-4 opacity-70"><path fill-rule="evenodd" d="M9.965 11.026a5 5 0 1 1 1.06-1.06l2.755 2.754a.75.75 0 1 1-1.06 1.06l-2.755-2.754ZM10.5 7a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" clip-rule="evenodd"/></svg>
          <input x-ref="searchInput" type="text" class="grow border-0 bg-transparent focus:outline-none focus:ring-0" placeholder="Ketik untuk mencari…" aria-label="Cari"/>
        </label>
        <div class="mt-3 text-xs text-base-content/60">Tekan <kbd class="kbd kbd-xs">Esc</kbd> untuk menutup</div>
      </div>
      <form method="dialog" class="modal-backdrop bg-black/40"><button @click="isSearchOpen=false">close</button></form>
    </dialog>
  </header>
</div>
