@extends('layouts.app')

@section('title', 'WhatsApp Message Logs')

@section('content')
<div class="container mx-auto py-6">

    {{-- Header Halaman --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" data-aos="fade-down">
        <div>
            {{-- SINKRONISASI: Menggunakan warna teks theme-aware --}}
            <h1 class="text-3xl font-bold text-base-content">Log Pesan WhatsApp</h1>
            <p class="mt-1 text-base-content/70">Riwayat semua pesan otomatis yang telah terkirim.</p>
        </div>
    </div>

    {{-- SINKRONISASI: Mengganti struktur tabel dengan card dan table DaisyUI --}}
    <div class="card bg-base-100 shadow-lg border border-base-300/50 mt-8" data-aos="fade-up">
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Penerima (Lead)</th>
                        <th>Pesan</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                    <tr class="hover">
                        <td>
                            @if ($log->lead)
                                <a href="{{ route('leads.show', $log->lead) }}" class="link link-hover link-primary font-semibold">{{ $log->lead->name }}</a>
                                <div class="text-xs text-base-content/60">{{ $log->phone_number }}</div>
                            @else
                                <span class="font-semibold">{{ $log->phone_number }}</span>
                            @endif
                        </td>
                        <td class="max-w-sm">
                            <p class="truncate text-sm">{{ $log->message }}</p>
                        </td>
                        <td>
                            <span class="badge {{ $log->status === 'sent' ? 'badge-success' : 'badge-error' }} badge-sm">{{ ucfirst($log->status) }}</span>
                        </td>
                        <td class="text-sm text-base-content/70">
                            {{ $log->created_at->format('d M Y, H:i') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4">
                             {{-- SINKRONISASI: Tampilan state kosong yang lebih baik --}}
                            <div class="text-center py-16">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <h3 class="mt-4 text-lg font-medium text-base-content">Belum ada log pesan</h3>
                                <p class="mt-1 text-sm text-base-content/60">Pesan yang terkirim akan muncul di sini.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if ($logs->hasPages())
        <div class="mt-6" data-aos="fade-up">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
