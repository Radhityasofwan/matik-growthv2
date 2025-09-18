{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html
  lang="{{ str_replace('_', '-', app()->getLocale()) }}"
  x-data="{
    // ===== THEME (DaisyUI) =====
    themes: ['softblue','light','dark','cupcake','bumblebee','emerald','corporate','synthwave','retro','cyberpunk','valentine','halloween','garden','forest','aqua','lofi','pastel','fantasy','wireframe','black','luxury','dracula','cmyk','autumn','business','acid','lemonade','night','coffee','winter'],
    darkThemes: ['dark','synthwave','halloween','forest','black','luxury','dracula','business','night','coffee'],
    theme: localStorage.getItem('theme') || 'softblue',
    setTheme(t){
      this.theme = t; localStorage.setItem('theme', t);
      document.documentElement.setAttribute('data-theme', t);
      if (this.darkThemes.includes(t)) document.documentElement.classList.add('dark');
      else document.documentElement.classList.remove('dark');
    },

    // ===== UI STATE =====
    isMobileMenuOpen: false,      // hanya dipakai di mobile
    sidebarHover: false,          // desktop: atur expand saat hover
    get greet(){
      const h=new Date().getHours();
      if(h>=5&&h<11) return 'Selamat pagi';
      if(h>=11&&h<15) return 'Selamat siang';
      if(h>=15&&h<18) return 'Selamat sore';
      return 'Selamat malam';
    }
  }"
  x-init="
    // apply theme on first paint
    setTheme(theme);

    // harden mobile/desktop switch
    const syncMode = () => { if (window.innerWidth >= 1024) isMobileMenuOpen = false; };
    window.addEventListener('resize', syncMode);
    document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') isMobileMenuOpen=false; });

    // close mobile drawer ketika navigasi link di sidebar diklik
    document.addEventListener('sidebar:navigated', ()=>{ isMobileMenuOpen=false; });

    // simple horizontal swipe close (mobile)
    let startX = null;
    document.addEventListener('touchstart', (e)=>{ startX = e.touches?.[0]?.clientX ?? null; }, {passive:true});
    document.addEventListener('touchend', (e)=>{
      const endX = e.changedTouches?.[0]?.clientX ?? null;
      if(startX!=null && endX!=null && (startX - endX) > 50) isMobileMenuOpen=false;
      startX = null;
    }, {passive:true});
  "
  class="h-full font-sans antialiased"
>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Matik Growth Hub')</title>
  <script>
    // paint theme ASAP sebelum CSS di-load
    (function(){
      const t = localStorage.getItem('theme') || 'softblue';
      const darks = ['dark','synthwave','halloween','forest','black','luxury','dracula','business','night','coffee'];
      document.documentElement.setAttribute('data-theme', t);
      if (darks.includes(t)) document.documentElement.classList.add('dark');
    })();
  </script>
  <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon"/>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <style>[x-cloak]{display:none!important}</style>
  @stack('head')
</head>

<body
  class="min-h-screen bg-base-200 text-base-content"
  :class="{ 'overflow-hidden': isMobileMenuOpen }"
>
  <div class="flex min-h-screen">
    {{-- SIDEBAR (hover expand di desktop, drawer di mobile) --}}
    @include('partials.sidebar')

    {{-- WRAPPER KONTEN --}}
    <div
      class="flex flex-1 flex-col transition-all duration-300 ease-in-out"
      :class="{
        // desktop: ml mengikuti hover (collapsed 5rem vs expanded 16rem)
        'lg:ml-20': !sidebarHover,
        'lg:ml-64': sidebarHover
      }"
    >
      {{-- NAVBAR (mengandung tombol toggle mobile) --}}
      @include('partials.navbar')

      {{-- MAIN CONTENT --}}
      <main id="main-content" class="flex-1 overflow-x-hidden overflow-y-auto p-4 sm:p-6 lg:p-8">
        <div class="mx-auto w-full max-w-7xl" data-aos="fade-up" data-aos-duration="600">
          @yield('content')
        </div>
      </main>
    </div>
  </div>

  {{-- SEARCH MODAL --}}
  <dialog
  x-ref="searchModal"
  x-effect="$store.ui.isSearchOpen ? $refs.searchModal.showModal() : ($refs.searchModal.open && $refs.searchModal.close())"
  @keydown.escape.window="$store.ui.isSearchOpen = false"
  @close="$store.ui.isSearchOpen = false"
  class="modal">
  >
    <div class="modal-box bg-base-100">
      <form method="dialog">
        <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2" aria-label="Tutup">✕</button>
      </form>
      <h3 class="font-semibold mb-2 text-base-content">Pencarian</h3>
      <label class="input input-bordered flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-4 h-4 opacity-70"><path fill-rule="evenodd" d="M9.965 11.026a5 5 0 1 1 1.06-1.06l2.755 2.754a.75.75 0 1 1-1.06 1.06l-2.755-2.754ZM10.5 7a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" clip-rule="evenodd"/></svg>
        <input x-ref="searchInput" type="text" class="grow border-0 bg-transparent focus:outline-none focus:ring-0" placeholder="Ketik untuk mencari…" aria-label="Cari"/>
      </label>
      <div class="mt-3 text-xs text-base-content/60">Tekan <kbd class="kbd kbd-xs">Esc</kbd> untuk menutup</div>
    </div>
    <form method="dialog" class="modal-backdrop bg-black/40"><button>close</button></form>
  </dialog>

  @stack('modals')
  @stack('scripts')
</body>
</html>
