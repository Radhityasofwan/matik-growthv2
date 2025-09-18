@extends('layouts.app')

@section('title', 'Subscriptions - Matik Growth Hub')

@section('content')
<div class="container mx-auto py-6">

    {{-- Alerts --}}
    @if (session('success'))
        <div class="alert alert-success shadow-lg mb-6" data-aos="fade-down">
            <div>
                <svg xmlns="http://www.w.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error shadow-lg mb-6" data-aos="fade-down">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span><strong>Error!</strong> Mohon periksa kembali form Anda.</span>
            </div>
        </div>
    @endif

    {{-- Header Halaman --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" data-aos="fade-down">
        <div>
            {{-- SINKRONISASI: Menggunakan warna teks theme-aware --}}
            <h1 class="text-3xl font-bold text-base-content">Langganan</h1>
            <p class="mt-1 text-base-content/70">Kelola semua data langganan pelanggan Anda.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            {{-- Tombol ini bisa diaktifkan jika ada fungsionalitas tambah manual --}}
            {{-- <a href="#" class="btn btn-primary">Tambah Langganan</a> --}}
        </div>
    </div>

    @if($subscriptions->isEmpty())
        {{-- SINKRONISASI: Tampilan state kosong yang konsisten --}}
        <div class="text-center py-20 card bg-base-100 mt-8 border border-base-300/50 shadow-lg" data-aos="fade-up">
            <div class="card-body items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-base-content/20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><rect width="20" height="14" x="2" y="5" rx="2"></rect><line x1="2" x2="22" y1="10" y2="10"></line></svg>
                <h3 class="mt-4 text-lg font-medium text-base-content">Belum ada langganan</h3>
                <p class="mt-1 text-sm text-base-content/60">Data langganan akan muncul di sini setelah lead dikonversi.</p>
            </div>
        </div>
    @else
        {{-- SINKRONISASI: Menggunakan komponen card dan table dari DaisyUI --}}
        <div class="card bg-base-100 shadow-lg border border-base-300/50 mt-8" data-aos="fade-up">
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Pelanggan</th>
                            <th>Paket</th>
                            <th>Status</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Berakhir</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subscriptions as $sub)
                        @php
                            // Logika untuk menentukan warna badge status
                            $statusClass = match($sub->status) {
                                'active' => 'badge-success',
                                'paused' => 'badge-warning',
                                'cancelled' => 'badge-error',
                                default => 'badge-ghost',
                            };
                        @endphp
                        <tr class="hover">
                            <td>
                                @if($sub->lead)
                                <a href="{{ route('leads.show', $sub->lead) }}" class="link link-hover link-primary font-semibold">{{ $sub->lead->name }}</a>
                                @else
                                <span class="text-base-content/70 italic">Lead Dihapus</span>
                                @endif
                            </td>
                            <td>
                                <div class="font-medium text-base-content">{{ $sub->plan }}</div>
                                <div class="text-xs text-base-content/60">Rp {{ number_format($sub->amount, 0, ',', '.') }} /{{ $sub->cycle == 'yearly' ? 'thn' : 'bln' }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $statusClass }}">{{ ucfirst($sub->status) }}</span>
                            </td>
                            <td>{{ $sub->start_date ? $sub->start_date->format('d M Y') : '-' }}</td>
                            <td>{{ $sub->end_date ? $sub->end_date->format('d M Y') : 'Selamanya' }}</td>
                            <td class="text-right">
                                {{-- Tombol aksi bisa ditambahkan di sini jika diperlukan --}}
                                <a href="{{ route('leads.show', $sub->lead_id) }}" class="btn btn-ghost btn-xs">Detail Lead</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
         {{-- Pagination --}}
        <div class="mt-6" data-aos="fade-up">
            {{ $subscriptions->links() }}
        </div>
    @endif
</div>
@endsection
