{{-- resources/views/demo/ui.blade.php --}}
@extends('layouts.app') {{-- atau sesuaikan layoutmu --}}

@section('title', 'UI Demo – softblue')

@section('content')
  <main class="min-h-screen bg-base-100 text-base-content" data-theme="softblue">
    {{-- HERO / Header section --}}
    <section class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
      <div class="flex items-center justify-between gap-3">
        <div data-aos="fade-up" class="space-y-2">
          <h1 class="text-2xl sm:text-3xl font-bold">UI Demo (softblue)</h1>
          <p class="text-sm sm:text-base opacity-80">Bersih, modern, interaktif — fokus mobile.</p>
        </div>

        {{-- Theme toggle contoh kecil (opsional) --}}
        <label class="swap swap-rotate" x-data="{ dark: document.documentElement.classList.contains('dark') }"
               x-init="$watch('dark', v => document.documentElement.classList.toggle('dark', v))">
          <input type="checkbox" class="theme-controller" x-model="dark" />
          <svg class="swap-off fill-current w-6 h-6"><use href="#icon-sun" /></svg>
          <svg class="swap-on  fill-current w-6 h-6"><use href="#icon-moon" /></svg>
        </label>
      </div>
    </section>

    {{-- CTA sticky untuk mobile --}}
    <div class="fixed inset-x-0 bottom-0 z-40 sm:hidden" data-aos="fade-up">
      <div class="mx-3 mb-3 rounded-2xl shadow-lg bg-base-100 border border-base-300 p-3 flex items-center gap-3">
        <div class="text-sm">
          <div class="font-semibold">Siap mulai?</div>
          <div class="opacity-70">Aksi cepat langsung di sini.</div>
        </div>
        <a href="#action" class="btn btn-primary btn-sm ml-auto">Lanjut</a>
      </div>
    </div>

    {{-- Cards grid demo --}}
    <section class="container mx-auto px-4 sm:px-6 lg:px-8 pb-8 sm:pb-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6" data-aos="fade-up">
      <div class="card bg-base-100 border border-base-300 hover:shadow-xl transition-all">
        <div class="card-body">
          <h3 class="card-title text-base sm:text-lg">Aksesibilitas Form</h3>
          <p class="opacity-80">Input & tombol DaisyUI sudah aksesibel.</p>
          <form class="space-y-3" aria-labelledby="login-title">
            <input type="email" class="input input-bordered w-full" placeholder="Email" aria-label="Email">
            <input type="password" class="input input-bordered w-full" placeholder="Password" aria-label="Password">
            <button type="button" class="btn btn-primary w-full">Masuk</button>
          </form>
        </div>
      </div>

      <div class="card bg-base-100 border border-base-300 hover:shadow-xl transition-all">
        <div class="card-body">
          <h3 class="card-title text-base sm:text-lg">Interaksi Halus</h3>
          <p class="opacity-80">AOS + transisi DaisyUI membuat UI terasa hidup.</p>
          <div class="flex gap-2">
            <span class="badge badge-primary">Primary</span>
            <span class="badge">Default</span>
            <span class="badge badge-outline">Outline</span>
          </div>
        </div>
      </div>

      <div class="card bg-base-100 border border-base-300 hover:shadow-xl transition-all">
        <div class="card-body">
          <h3 class="card-title text-base sm:text-lg">Notifikasi</h3>
          <div class="space-y-2">
            <div class="alert alert-info"><span>Info: perubahan disimpan.</span></div>
            <div class="alert alert-success"><span>Sukses: data tersimpan.</span></div>
          </div>
        </div>
      </div>
    </section>

    {{-- Chart dengan skeleton anti-CLS --}}
    <section class="container mx-auto px-4 sm:px-6 lg:px-8 pb-8 sm:pb-12" data-aos="fade-up">
      <div class="card bg-base-100 border border-base-300">
        <div class="card-body">
          <h3 class="card-title text-base sm:text-lg">Tren Mingguan</h3>
          <div id="chart-skeleton" class="skeleton w-full h-80 rounded-xl"></div>
          <div id="chart-demo" class="w-full h-80"></div>
        </div>
      </div>
    </section>

    {{-- Tabel responsif (wrap + min-width) --}}
    <section class="container mx-auto px-4 sm:px-6 lg:px-8 pb-24 sm:pb-12" data-aos="fade-up">
      <div class="card bg-base-100 border border-base-300">
        <div class="card-body">
          <div class="flex items-center justify-between">
            <h3 class="card-title text-base sm:text-lg">Transaksi</h3>
            <span class="badge">7 hari terakhir</span>
          </div>

          <div class="overflow-x-auto h-scroll-snap">
            <table class="table table-zebra min-w-[720px] h-snap">
              <thead>
                <tr>
                  <th>Tanggal</th>
                  <th>Order</th>
                  <th>Status</th>
                  <th class="text-right">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Mon</td><td>#INV-1001</td>
                  <td><span class="badge badge-success">Paid</span></td>
                  <td class="text-right">Rp 1.200.000</td>
                </tr>
                <tr>
                  <td>Tue</td><td>#INV-1002</td>
                  <td><span class="badge badge-warning">Pending</span></td>
                  <td class="text-right">Rp 850.000</td>
                </tr>
                <tr>
                  <td>Wed</td><td>#INV-1003</td>
                  <td><span class="badge badge-error">Failed</span></td>
                  <td class="text-right">Rp 0</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="mt-4 flex justify-end">
            <button class="btn btn-primary">Lihat semua</button>
          </div>
        </div>
      </div>
    </section>
  </main>

  {{-- Icon sprite (kecil, opsional) --}}
  <svg width="0" height="0" class="hidden">
    <symbol id="icon-sun" viewBox="0 0 24 24"><path d="M6.76 4.84l-1.8-1.79-1.41 1.41 1.79 1.8 1.42-1.42zm10.48 14.32l1.79 1.8 1.41-1.41-1.8-1.79-1.4 1.4zM12 4V1h-0v3h0zm0 19v-3h0v3h0zm8-8h3v0h-3v0zM1 12h3v0H1v0zm14.24-7.16l1.42 1.42 1.79-1.8-1.41-1.41-1.8 1.79zM4.22 18.36l1.41 1.41 1.8-1.79-1.42-1.42-1.79 1.8zM12 7a5 5 0 100 10 5 5 0 000-10z"/></symbol>
    <symbol id="icon-moon" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1111.21 3a7 7 0 109.79 9.79z"/></symbol>
  </svg>
@endsection
