{{-- resources/views/partials/navbar.blade.php — FINAL --}}
<div class="sticky top-0 z-30 p-4 pb-0 sm:p-6 sm:pb-0">
  <header
    x-data="{ isSearchOpen: false }"
    data-aos="fade-down"
    class="navbar rounded-box border border-base-300/20 bg-base-100/80 shadow-md backdrop-blur-lg"
    role="banner"
  >
    {{-- Kiri: Hamburger (Mobile) --}}
    <div class="navbar-start gap-1">
      <button
        type="button"
        @click="isMobileMenuOpen = !isMobileMenuOpen"
        class="btn btn-ghost btn-circle lg:hidden"
        aria-label="Buka menu"
        aria-expanded="false"
        :aria-expanded="isMobileMenuOpen.toString()"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
    </div>

    {{-- Kanan: Aksi, Notif, Profil --}}
    <div class="navbar-end gap-1">
      {{-- Theme switcher --}}
      <div class="dropdown dropdown-end">
        <label tabindex="0" class="btn btn-ghost btn-circle" aria-label="Pilih Tema">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 12c0-5.25-4.25-9.5-9.5-9.5S2.5 6.75 2.5 12s4.25 9.5 9.5 9.5s9.5-4.25 9.5-9.5z"/><path d="M12 2.5a9.5 9.5 0 0 0 0 19V2.5z"/></svg>
        </label>
        <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-52 z-20 border border-base-300/50 max-h-96 overflow-y-auto">
            <template x-for="t in themes" :key="t">
                <li><a @click.prevent="setTheme(t)" :class="{'active': theme === t}" x-text="t.charAt(0).toUpperCase() + t.slice(1)"></a></li>
            </template>
        </ul>
      </div>

      {{-- Notifikasi --}}
      <div
        class="dropdown dropdown-end"
        x-data="notifBell($el)"
        x-init="init()"
        data-unread="{{ (int) ($unreadNotificationsCount ?? 0) }}"
        data-preview='@json($notificationsPreview ?? [])'
      >
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
            <div class="font-semibold text-sm text-base-content">Notifikasi</div>
            <button type="button" class="btn btn-ghost btn-xs"
              @click="markAll()" :disabled="!items.some(i => i.source === 'notification' && !i.read_at)">
              Tandai semua dibaca
            </button>
          </div>

          <ul class="max-h-80 overflow-y-auto menu p-2">
            <template x-if="items.length === 0">
              <li class="p-4 text-sm text-center text-base-content/60">Belum ada notifikasi baru.</li>
            </template>

            <template x-for="n in items" :key="n.id">
              <li>
                <a @click.prevent="go(n)" :class="{'opacity-60': n.read_at && n.source === 'notification'}">
                  <div class="flex-shrink-0 mt-1 self-start">
                    <span class="w-2 h-2 rounded-full inline-block" :class="(n.read_at ? 'bg-base-300' : 'bg-primary')"></span>
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
        <label tabindex="0" class="btn btn-ghost flex items-center gap-2">
          <div class="avatar">
            <div class="w-8 rounded-full ring ring-primary/50 ring-offset-base-100 ring-offset-2">
              <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=3B82F6&color=fff&size=128" alt="Avatar pengguna"/>
            </div>
          </div>
          <div class="hidden sm:flex flex-col items-start">
            <span class="font-semibold text-sm text-base-content">{{ Str::words(Auth::user()->name, 2, '') }}</span>
          </div>
        </label>

        <ul tabindex="0" class="menu menu-sm dropdown-content mt-4 z-[1] p-2 shadow-lg bg-base-100 rounded-box w-52 border border-base-300/50">
          <li class="p-2">
            <p class="font-semibold text-sm text-base-content">{{ Auth::user()->name }}</p>
            <p class="text-xs text-base-content/70 truncate">{{ Auth::user()->email }}</p>
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
  </header>
</div>
