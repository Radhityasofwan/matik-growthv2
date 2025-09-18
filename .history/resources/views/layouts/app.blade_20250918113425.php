{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html
  lang="{{ str_replace('_', '-', app()->getLocale()) }}"
  x-data="{
    themes: ['softblue','light','dark','cupcake','bumblebee','emerald','corporate','synthwave','retro','cyberpunk','valentine','halloween','garden','forest','aqua','lofi','pastel','fantasy','wireframe','black','luxury','dracula','cmyk','autumn','business','acid','lemonade','night','coffee','winter'],
    darkThemes: ['dark','synthwave','halloween','forest','black','luxury','dracula','business','night','coffee'],
    theme: localStorage.getItem('theme') || 'softblue',
    setTheme(t) {
      this.theme = t;
      localStorage.setItem('theme', t);
      document.documentElement.setAttribute('data-theme', t);
      if (this.darkThemes.includes(t)) document.documentElement.classList.add('dark'); else document.documentElement.classList.remove('dark');
    },
    greet: { text: '', icon: '' },
    initGreet() {
        const h = new Date().getHours();
        if (h >= 4 && h < 11) { this.greet = { text: 'Selamat Pagi', icon: '☀️' }; }
        else if (h >= 11 && h < 15) { this.greet = { text: 'Selamat Siang', icon: '🏙️' }; }
        else if (h >= 15 && h < 18) { this.greet = { text: 'Selamat Sore', icon: '🌇' }; }
        else { this.greet = { text: 'Selamat Malam', icon: '🌙' }; }
    }
  }"
  x-init="setTheme(theme); initGreet()"
  class="h-full font-sans antialiased"
>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Matik Growth Hub')</title>

  {{-- Set tema seawal mungkin (hindari flash) --}}
  <script>
    (function(){
      const theme = localStorage.getItem('theme') || 'softblue';
      const darkThemes = ['dark','synthwave','halloween','forest','black','luxury','dracula','business','night','coffee'];
      document.documentElement.setAttribute('data-theme', theme);
      if (darkThemes.includes(theme)) document.documentElement.classList.add('dark');
    })();
  </script>

  <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon"/>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <style>[x-cloak]{display:none!important}</style>
  @stack('head')
</head>

<body
  class="min-h-screen bg-base-200 text-base-content"
  :class="{ 'overflow-hidden': $store.ui.isMobileMenuOpen }"
>
  {{-- Overlay mobile --}}
  <div
    x-show="$store.ui.isMobileMenuOpen"
    x-transition.opacity
    @click="$store.ui.isMobileMenuOpen = false"
    class="fixed inset-0 z-40 bg-black/50 lg:hidden"
    x-cloak
    aria-hidden="true"
  ></div>

  {{-- Sidebar desktop + drawer mobile --}}
  @include('partials.sidebar')

  <div class="flex min-h-screen">
    {{-- Spacer desktop agar konten tidak ketutup --}}
    <div class="hidden lg:block transition-[width] duration-300 ease-in-out" :class="$store.ui.sidebarHover ? 'w-64' : 'w-20'"></div>

    <div class="flex flex-1 flex-col contain-layout">
      @include('partials.navbar')

      <main id="main-content" class="flex-1 overflow-x-hidden overflow-y-auto p-4 sm:p-6 lg:p-8 contain-paint">
        <div class="mx-auto w-full max-w-7xl">
          @yield('content')
        </div>
      </main>
    </div>
  </div>

  {{-- Search Modal (state lewat store → tidak error) --}}
  <dialog
    x-ref="searchModal"
    x-effect="$store.ui.isSearchOpen ? $refs.searchModal.showModal() : ($refs.searchModal.open && $refs.searchModal.close())"
    @keydown.escape.window="$store.ui.isSearchOpen=false"
    @close="$store.ui.isSearchOpen=false"
    class="modal"
  >
    <div class="modal-box bg-base-100">
      <form method="dialog">
        <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2" @click="$store.ui.isSearchOpen=false" aria-label="Tutup">✕</button>
      </form>
      <h3 class="font-semibold mb-2 text-base-content">Pencarian</h3>
      <label class="input input-bordered flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-4 h-4 opacity-70"><path fill-rule="evenodd" d="M9.965 11.026a5 5 0 1 1 1.06-1.06l2.755 2.754a.75.75 0 1 1-1.06 1.06l-2.755-2.754ZM10.5 7a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" clip-rule="evenodd"/></svg>
        <input
          x-ref="searchInput"
          type="text"
          class="grow border-0 bg-transparent focus:outline-none focus:ring-0"
          placeholder="Ketik untuk mencari…"
          aria-label="Cari"
        />
      </label>
      <div class="mt-3 text-xs text-base-content/60">Tekan <kbd class="kbd kbd-xs">Esc</kbd> untuk menutup</div>
    </div>
    <form method="dialog" class="modal-backdrop bg-black/40">
      <button @click="$store.ui.isSearchOpen=false">close</button>
    </form>
  </dialog>

  @stack('modals')
  @stack('scripts')
</body>
</html>

