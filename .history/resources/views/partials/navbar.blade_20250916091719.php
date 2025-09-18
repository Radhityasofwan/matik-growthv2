<!--
    Navbar minimalis.
    - Tombol hamburger untuk buka/tutup sidebar di mobile.
    - Menampilkan judul halaman dinamis dari @yield('header').
    - Aksi cepat di sebelah kanan.
-->
<header class="bg-base-100/80 backdrop-blur-sm sticky top-0 z-40 border-b border-base-300/50">
    <div class="navbar min-h-16 px-4 sm:px-6 md:px-8">
        <div class="navbar-start">
            <!-- Hamburger button for mobile -->
            <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="btn btn-ghost btn-circle lg:hidden">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
            </button>

            <!-- Page Header/Title -->
            <div class="hidden lg:block">
                <h1 class="text-xl font-semibold">
                    @yield('header', 'Dashboard')
                </h1>
            </div>
        </div>

        <div class="navbar-center lg:hidden">
             <h1 class="text-lg font-semibold">
                @yield('header', 'Dashboard')
            </h1>
        </div>

        <div class="navbar-end space-x-2">
            <!-- Global Search (Example) -->
            <div class="form-control hidden md:block">
              <input type="text" placeholder="Search..." class="input input-bordered input-sm w-24 md:w-auto" />
            </div>

            <!-- Notifications (Example) -->
            <button class="btn btn-ghost btn-circle">
                <div class="indicator">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <span class="badge badge-xs badge-primary indicator-item"></span>
                </div>
            </button>
        </div>
    </div>
</header>
