@extends('layouts.app')

@section('title', 'Detail Lead')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <img src="{{ $lead->owner?->avatar_url }}" alt="{{ $lead->owner?->name }}" class="h-12 w-12 rounded-full object-cover">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ $lead->name ?? ($lead->store_name ?: 'Lead #'.$lead->id) }}
                </h1>
                <p class="text-sm text-gray-500">
                    Owner: <strong>{{ $lead->owner?->name ?: '-' }}</strong> · {{ $lead->email }} · {{ $lead->phone ?: '-' }}
                </p>
            </div>
        </div>
        <a href="{{ route('leads.index') }}" class="btn btn-ghost">← Kembali</a>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="card bg-base-100 shadow border">
            <div class="card-body py-4">
                <div class="text-xs text-gray-500">Status</div>
                <div class="text-lg font-semibold">{{ $lead->status_label }}</div>
            </div>
        </div>
        <div class="card bg-base-100 shadow border">
            <div class="card-body py-4">
                <div class="text-xs text-gray-500">Total Chat Terkirim</div>
                <div class="text-lg font-semibold">{{ $stats['chat_count'] }}</div>
            </div>
        </div>
        <div class="card bg-base-100 shadow border">
            <div class="card-body py-4">
                <div class="text-xs text-gray-500">Chat Terakhir</div>
                <div class="text-lg font-semibold">
                    {{ $stats['last_chat_at'] ? $stats['last_chat_at']->format('d M Y H:i') : '—' }}
                </div>
            </div>
        </div>
        <div class="card bg-base-100 shadow border">
            <div class="card-body py-4">
                <div class="text-xs text-gray-500">Balasan Terakhir</div>
                <div class="text-lg font-semibold">
                    {{ $stats['last_reply_at'] ? $stats['last_reply_at']->format('d M Y H:i') : '—' }}
                </div>
            </div>
        </div>
    </div>

    {{-- Timeline --}}
    <div class="bg-base-100 border rounded-2xl shadow p-5">
        <h3 class="font-semibold mb-4">Timeline Aktivitas</h3>

        @if($activities->isEmpty())
            <div class="text-sm text-gray-500">Belum ada aktivitas untuk lead ini.</div>
        @else
            <div class="relative">
                <div class="absolute left-4 top-0 bottom-0 w-px bg-base-300"></div>
                <ul class="space-y-6">
                    @foreach($activities as $act)
                        @php
                            $icon = match($act->log_name) {
                                'wa_chat'     => '📤',
                                'wa_reply'    => '📥',
                                'wa_incoming' => '💬',
                                'followup_rule_triggered' => '⏰',
                                'followup_sent' => '🚀',
                                default => '•',
                            };
                            $title = $act->description ?: ($act->log_name ?: 'Aktivitas');
                            $props = $act->properties?->toArray() ?? [];
                        @endphp
                        <li class="relative pl-12">
                            <div class="absolute left-0 top-1.5 h-8 w-8 rounded-full bg-base-200 flex items-center justify-center text-lg">
                                <span>{{ $icon }}</span>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-medium">{{ $title }}</div>
                                    @if(!empty($props))
                                        <div class="text-xs text-gray-500">
                                            {{-- cetak ringkas beberapa properti umum bila ada --}}
                                            @if(isset($props['recipient'])) Ke: {{ $props['recipient'] }} @endif
                                            @if(isset($props['sender'])) · Dari: {{ $props['sender'] }} @endif
                                            @if(isset($props['template'])) · Tpl: {{ $props['template'] }} @endif
                                        </div>
                                    @endif
                                    @if($act->causer)
                                        <div class="mt-2 flex items-center gap-2">
                                            <img src="{{ $act->causer->avatar_url }}" class="h-6 w-6 rounded-full object-cover" alt="{{ $act->causer->name }}">
                                            <span class="text-xs text-gray-500">oleh {{ $act->causer->name }}</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 whitespace-nowrap">
                                    {{ $act->created_at?->timezone(config('app.timezone', 'Asia/Jakarta'))?->format('d M Y H:i') }}
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-6">{{ $activities->links() }}</div>
            </div>
        @endif
    </div>
</div>
@endsection
