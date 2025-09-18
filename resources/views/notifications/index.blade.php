@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator $notifications */
    $hasNotifications = $notifications->count() > 0;
@endphp

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-3xl font-bold text-neutral">Notifikasi</h1>
        <p class="mt-1 text-neutral/60">Semua notifikasi akun kamu.</p>
    </div>

    @if($hasNotifications)
        <form action="{{ route('notifications.read_all') }}" method="POST">
            @csrf
            <button class="btn btn-outline btn-primary btn-sm">
                Tandai semua dibaca
            </button>
        </form>
    @endif
</div>

@if($hasNotifications)
    <div class="bg-base-100 rounded-xl border border-base-300/50 shadow-sm">
        <ul class="divide-y divide-base-300/50">
            @foreach($notifications as $n)
                @php
                    $data = (array) ($n->data ?? []);
                    $title = $data['title']   ?? class_basename($n->type) ?? 'Notifikasi';
                    $message = $data['message'] ?? ($data['body'] ?? '');
                    $url = $data['url'] ?? null;
                @endphp
                <li class="p-4 sm:p-5 hover:bg-base-200/50 transition-colors">
                    <div class="flex items-start gap-4">
                        <div class="mt-1">
                            <span class="inline-block w-2.5 h-2.5 rounded-full {{ $n->read_at ? 'bg-base-300' : 'bg-primary' }}"></span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="font-semibold text-neutral truncate">{{ $title }}</h3>
                                <span class="text-xs text-neutral/60 whitespace-nowrap">{{ $n->created_at->diffForHumans() }}</span>
                            </div>
                            @if($message)
                                <p class="text-sm text-neutral/70 mt-1 break-words">{{ $message }}</p>
                            @endif

                            <div class="mt-3 flex items-center gap-3">
                                @if($url)
                                    <form action="{{ route('notifications.read', $n->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="redirect" value="{{ $url }}">
                                        <button class="btn btn-primary btn-sm">Buka</button>
                                    </form>
                                @endif

                                @if(is_null($n->read_at))
                                    <form action="{{ route('notifications.read', $n->id) }}" method="POST">
                                        @csrf
                                        <button class="btn btn-ghost btn-sm">Tandai dibaca</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="px-4 py-3 sm:px-5 border-t border-base-300/50">
            {{ $notifications->links() }}
        </div>
    </div>
@else
    {{-- Fallback jika belum ada notifikasi: tampilkan activity log terbaru (read-only) --}}
    <div class="bg-base-100 rounded-xl border border-base-300/50 shadow-sm">
        <div class="p-4 sm:p-5 border-b border-base-300/50">
            <h2 class="font-semibold">Belum ada notifikasi</h2>
            <p class="text-sm text-neutral/60 mt-1">Berikut aktivitas terbaru sebagai informasi.</p>
        </div>

        <ul class="divide-y divide-base-300/50">
            @forelse($activities as $a)
                <li class="p-4 sm:p-5">
                    <div class="flex items-start gap-3">
                        <div class="mt-1">
                            <span class="inline-block w-2.5 h-2.5 rounded-full bg-primary"></span>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-medium text-neutral">{{ $a->log_name ? ucwords(str_replace(['_', '-'], ' ', $a->log_name)) : 'Aktivitas' }}</span>
                                <span class="text-xs text-neutral/60 whitespace-nowrap">{{ $a->created_at?->diffForHumans() }}</span>
                            </div>
                            <p class="text-sm text-neutral/70 mt-1 break-words">
                                {{ $a->causer?->name ? $a->causer->name.' — ' : '' }}{{ $a->description ?: 'Aktivitas sistem' }}
                            </p>
                        </div>
                    </div>
                </li>
            @empty
                <li class="p-6 text-center text-neutral/60">Belum ada aktivitas.</li>
            @endforelse
        </ul>
    </div>
@endif
@endsection
