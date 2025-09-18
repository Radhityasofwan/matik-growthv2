{{-- resources/views/layouts/app.blade.php — FINAL --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full font-sans antialiased">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Matik Growth Hub')</title>

  {{-- Pre-apply theme to avoid FOUC (use DaisyUI built-ins) --}}
  <script>
    (function () {
      const DEFAULT_THEME = 'light';
      const theme = localStorage.getItem('theme') || DEFAULT_THEME;
      const darkThemes = ['dark','synthwave','halloween','forest','black','luxury','dracula','business','night','coffee','dim','nord','sunset'];
      const root = document.documentElement;
      root.setAttribute('data-theme', theme);
      if (darkThemes.includes(theme)) root.classList.add('dark'); else root.classList.remove('dark');
    })();
  </script>

  <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon"/>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <style>[x-cloak]{display:none!important}</style>
  @stack('head')
</head>

<body
  class="h-full bg-base-100 text-base-content"
  x-data="{
    themes: ['light','dark','cupcake','bumblebee','emerald','corporate','synthwave','retro','cyberpunk','valentine','halloween','garden','forest','aqua','lofi','pastel','fantasy','wireframe','black','luxury','dracula','cmyk','autumn','business','acid','lemonade','night','coffee','winter','dim','nord','sunset'],
    darkThemes: ['dark','synthwave','halloween','forest','black','luxury','dracula','business','night','coffee','dim','nord','sunset'],
    theme: localStorage.getItem('theme') || 'light',
    setTheme(t){
      this.theme = t; localStorage.setItem('theme', t);
      const r = document.documentElement; r.setAttribute('data-theme', t);
      if (this.darkThemes.includes(t)) r.classList.add('dark'); else r.classList.remove('dark');
      document.dispatchEvent(new CustomEvent('theme:changed', { detail: { theme: t } }));
    },
    isMobileMenuOpen:false, isSearchOpen:false,
    get greet(){
      const h = new Date().getHours();
      if (h>=5&&h<11) return 'Selamat pagi';
      if (h>=11&&h<15) return 'Selamat siang';
      if (h>=15&&h<18) return 'Selamat sore';
      return 'Selamat malam';
    }
  }"
  x-init="setTheme(theme)"
  :class="{ 'overflow-hidden': isMobileMenuOpen }"
  @keydown.escape.window="isSearchOpen=false"
  @keydown.k.window.prevent="(e => { if (e.metaKey || e.ctrlKey) { isSearchOpen = true; $nextTick(() => $refs.searchInput?.focus()) } })(event)"
>
  <div class="flex min-h-screen">
    @include('partials.sidebar')

    <div class="flex flex-1 flex-col transition-all duration-300 ease-in-out lg:ml-20 peer-hover:lg:ml-64 peer-focus-within:lg:ml-64">
      @include('partials.navbar')

      <main id="main-content" class="flex-1 overflow-x-hidden overflow-y-auto p-4 sm:p-6 lg:p-8">
        <div class="mx-auto w-full max-w-7xl" data-aos="fade-up" data-aos-duration="600">
          @yield('content')
        </div>
      </main>
    </div>
  </div>

  {{-- Search Modal --}}
  <dialog
    x-ref="searchModal"
    x-cloak
    class="modal"
    x-effect="isSearchOpen ? $refs.searchModal.showModal() : ($refs.searchModal.open && $refs.searchModal.close())"
    @close="isSearchOpen=false"
  >
    <div class="modal-box bg-base-100">
      <form method="dialog">
        <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2" @click="isSearchOpen=false" aria-label="Tutup">✕</button>
      </form>

      <h3 class="font-semibold mb-2 text-base-content">Pencarian</h3>
      <label class="input input-bordered flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-4 h-4 opacity-70"><path fill-rule="evenodd" d="M9.965 11.026a5 5 0 1 1 1.06-1.06l2.755 2.754a.75.75 0 1 1-1.06 1.06l-2.755-2.754ZM10.5 7a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" clip-rule="evenodd"/></svg>
        <input x-ref="searchInput" type="text" class="grow border-0 bg-transparent focus:outline-none focus:ring-0" placeholder="Ketik untuk mencari…" aria-label="Cari"/>
      </label>
      <div class="mt-3 text-xs text-base-content/60">Tekan <kbd class="kbd kbd-xs">Esc</kbd> untuk menutup • Pintasan <kbd class="kbd kbd-xs">⌘</kbd>/<kbd class="kbd kbd-xs">Ctrl</kbd>+<kbd class="kbd kbd-xs">K</kbd></div>
    </div>
    <form method="dialog" class="modal-backdrop bg-black/40">
      <button @click="isSearchOpen=false">close</button>
    </form>
  </dialog>

  @stack('modals')
  @stack('scripts')
</body>
</html>
