{{-- resources/views/layouts/app.blade.php — FINAL FIX --}}
<!DOCTYPE html>
<html
  lang="{{ str_replace('_', '-', app()->getLocale()) }}"
  x-data="{
    // daftar tema DaisyUI
    themes: ['softblue','light','dark','cupcake','bumblebee','emerald','corporate','synthwave','retro','cyberpunk','valentine','halloween','garden','forest','aqua','lofi','pastel','fantasy','wireframe','black','luxury','dracula','cmyk','autumn','business','acid','lemonade','night','coffee','winter'],
    darkThemes: ['dark','synthwave','halloween','forest','black','luxury','dracula','business','night','coffee'],
    theme: localStorage.getItem('theme') || 'softblue',
    setTheme(t){ this.theme=t; localStorage.setItem('theme',t); document.documentElement.setAttribute('data-theme',t); if(this.darkThemes.includes(t)){document.documentElement.classList.add('dark')} else {document.documentElement.classList.remove('dark')} }
  }"
  x-init="setTheme(theme)"
  class="h-full font-sans antialiased"
>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Matik Growth Hub')</title>

    {{-- paint theme ASAP (hindari FOUC) --}}
    <script>
      (function () {
        const t = localStorage.getItem('theme') || 'softblue';
        const dark = ['dark','synthwave','halloween','forest','black','luxury','dracula','business','night','coffee'];
        document.documentElement.setAttribute('data-theme', t);
        if (dark.includes(t)) document.documentElement.classList.add('dark');
      })();
    </script>

    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
    @stack('head')
  </head>

  {{-- Root app state di BODY: hanya 2 hal untuk global UI --}}
  <body
    x-data="{
      isMobileMenuOpen:false,   // hanya untuk drawer mobile
      isSearchOpen:false,       // modal search
      get greet(){
        const h=new Date().getHours(); if(h>=5&&h<11) return 'Selamat pagi';
        if(h>=11&&h<15) return 'Selamat siang'; if(h>=15&&h<18) return 'Selamat sore'; return 'Selamat malam';
      }
    }"
    :class="{'overflow-hidden': isMobileMenuOpen}"
    class="h-full bg-base-200 text-base-content"
  >
    {{-- Sidebar (mobile drawer + desktop hover) --}}
    @include('partials.sidebar')

    <div class="min-h-screen lg:pl-20 flex flex-col">
      {{-- Navbar (toggle mobile ada di sini) --}}
      @include('partials.navbar')

      <main id="main-content" class="flex-1 overflow-x-hidden overflow-y-auto p-4 sm:p-6 lg:p-8">
        <div class="mx-auto w-full max-w-7xl" data-aos="fade-up" data-aos-duration="600">
          @yield('content')
        </div>
      </main>
    </div>

    {{-- Search Modal --}}
    <dialog
      x-ref="searchModal"
      x-effect="isSearchOpen ? $refs.searchModal.showModal() : ($refs.searchModal.open && $refs.searchModal.close())"
      @keydown.escape.window="isSearchOpen=false"
      @close="isSearchOpen=false"
      class="modal"
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
        <div class="mt-3 text-xs text-base-content/60">Tekan <kbd class="kbd kbd-xs">Esc</kbd> untuk menutup</div>
      </div>
      <form method="dialog" class="modal-backdrop bg-black/40">
        <button @click="isSearchOpen=false">close</button>
      </form>
    </dialog>

    @stack('modals')
    @stack('scripts')
  </body>
</html>
