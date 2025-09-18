@extends('layouts.app') {{-- Menggunakan layout utama Anda --}}

@section('title', 'Dashboard') {{-- Set judul halaman --}}

@section('content')
    {{-- Header Halaman --}}
    <div class="mb-6" data-aos="fade-down">
        <h1 class="text-2xl font-bold text-neutral">Selamat Datang Kembali!</h1>
        <p class="text-neutral/60">Berikut adalah ringkasan aktivitas Anda hari ini.</p>
    </div>

    {{-- Grid untuk Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Contoh Stat Card. Anda bisa loop data ini dari controller. --}}
        <x-card.stat
            title="Total Leads"
            value="1,280"
            icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>'
            data-aos-delay="100"
        />
        <x-card.stat
            title="Pesan Terkirim"
            value="8,921"
            icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>'
            data-aos-delay="200"
        />
         <x-card.stat
            title="Campaign Aktif"
            value="12"
            icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>'
            data-aos-delay="300"
        />
         <x-card.stat
            title="Task Selesai"
            value="57"
            icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
            data-aos-delay="400"
        />
    </div>

    {{-- Grid untuk konten utama (Chart & Aktivitas) --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mt-6">

        {{-- Kolom Kiri: Chart --}}
        <div class="lg:col-span-3">
            <div class="card bg-base-100 shadow-xl" data-aos="fade-up" data-aos-delay="500">
                <div class="card-body">
                    <h2 class="card-title text-neutral">Analitik Leads Mingguan</h2>

                    {{-- Ganti kondisi $isLoading dengan data asli dari controller --}}
                    @php $isLoading = true; @endphp

                    @if($isLoading)
                        {{-- Skeleton Loader untuk chart --}}
                        <div class="skeleton w-full h-80 mt-4"></div>
                    @else
                        {{-- Container untuk chart library (misal: ApexCharts) --}}
                        <div id="leads-chart" class="mt-4"></div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Kolom Kanan: Aktivitas Terbaru --}}
        <div class="lg:col-span-2">
             <div class="card bg-base-100 shadow-xl" data-aos="fade-up" data-aos-delay="600">
                <div class="card-body">
                    <h2 class="card-title text-neutral">Aktivitas Terbaru</h2>
                    <div class="mt-4 space-y-4">
                        {{-- Item Aktivitas 1 --}}
                        <div class="flex items-start">
                            <div class="avatar placeholder mr-4">
                              <div class="bg-primary text-primary-content rounded-full w-10">
                                <span>WA</span>
                              </div>
                            </div>
                            <div>
                                <p class="font-medium text-sm text-neutral">Broadcast ke <span class="font-bold">"Promo Merdeka"</span> berhasil dikirim.</p>
                                <p class="text-xs text-neutral/60">5 menit yang lalu</p>
                            </div>
                        </div>
                        {{-- Item Aktivitas 2 --}}
                         <div class="flex items-start">
                            <div class="avatar mr-4">
                                <div class="w-10 rounded-full">
                                  <img src="https://i.pravatar.cc/150?u=a042581f4e29026704d" />
                                </div>
                            </div>
                            <div>
                                <p class="font-medium text-sm text-neutral"><span class="font-bold">Andi</span> menyelesaikan task "Follow up leads baru".</p>
                                <p class="text-xs text-neutral/60">1 jam yang lalu</p>
                            </div>
                        </div>
                        {{-- Item Aktivitas 3 --}}
                         <div class="flex items-start">
                            <div class="avatar placeholder mr-4">
                              <div class="bg-success text-success-content rounded-full w-10">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                              </div>
                            </div>
                            <div>
                                <p class="font-medium text-sm text-neutral">5 leads baru ditambahkan dari import.</p>
                                <p class="text-xs text-neutral/60">3 jam yang lalu</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

