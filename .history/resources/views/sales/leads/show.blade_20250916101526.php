@extends('layouts.app')

@section('title', 'Detail Lead')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral">Detail Lead</h1>
            <p class="text-sm text-neutral/60">Timeline & riwayat interaksi.</p>
        </div>
        <a href="{{ route('leads.index') }}" class="btn btn-ghost">
            ← Kembali ke Leads
        </a>
    </div>

    {{-- HEADER LEAD --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2 card bg-base-100 shadow-sm border">
            <div class="card-body">
                <div class="flex items-start gap-4">
                    {{-- Avatar Owner (PIC) --}}
                    <div class="avatar">
                        <div class="w-14 h-14 rounded-full ring ring-primary ring-offset-base-100 ring-offset-2 overflow-hidden">
                            <img src="{{ $lead->owner?->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($lead->name ?: 'Lead').'&background=1F2937&color=fff' }}" alt="PIC">
                        </div>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-xl font-semibold">{{ $lead->name ?? $lead->store_name ?? 'Lead' }}</h2>
                        <div class="mt-1 text-sm text-neutral/70">
                            <span class="mr-3">Email: <strong>{{ $lead->email }}</strong></span>
                            <span class="mr-3">WA: <strong>{{ $lead->phone ?? '-' }}</strong></span>
                            <span>Status:
                                <span class="badge badge-outline ml-1">
                                    {{ $statusLabel[$lead->status] ?? ucfirst($lead->status) }}
                                </span>
                            </span>
                        </div>
                        <div class="mt-2 text-xs text-neutral/60">
                            Owner: <strong>{{ $lead->owner?->name ?? '-' }}</strong> •
                            Dibuat: {{ $lead->created_at?->format('d M Y H:i') ?? '-' }} •
                            Trial Habis: {{ $lead->trial_ends_at?->format('d M Y') ?? '-' }}
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    @if($lead->phone)
                        <a class="btn btn-sm btn-success" target="_blank" href="https://wa.me/{{ preg_replace('/\D+/', '', (string)$lead->phone) }}">
                            Chat Manual (wa.me)
                        </a>
                    @endif
                    <a href="{{ route('leads.index') }}#edit_lead_modal_{{ $lead->id }}" class="btn btn-sm">Edit Lead</a>
                </div>
            </div>
        </div>

        {{-- Langganan (jika ada) --}}
        <div class="card bg-base-100 shadow-sm border">
            <div class="card-body">
                <h3 class="card-title text-base">Langganan</h3>
                @if($lead->subscription)
                    <div class="text-sm">
                        <div>Paket: <strong>{{ $lead->subscription->plan }}</strong></div>
                        <div>Nominal: <strong>Rp {{ number_format($lead->subscription->amount,0,',','.') }}</strong></div>
                        <div>Siklus: <strong>{{ $lead->subscription->cycle === 'yearly' ? 'Tahunan' : 'Bulanan' }}</strong></div>
                        <div>Periode:
                            <strong>
                                {{ optional($lead->subscription->start_date)->format('d M Y') ?? '-' }}
                                –
                                {{ optional($lead->subscription->end_date)->format('d M Y') ?? '-' }}
                            </strong>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-neutral/60">Belum ada data langganan.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- TIMELINE --}}
    <div class="card bg-base-100 shadow-sm border">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <h3 class="card-title text-base">Timeline Aktivitas</h3>
                <span class="text-xs text-neutral/60">Total: {{ $activities->total() }}</span>
            </div>

            <div class="mt-4 space-y-6">
                @forelse($activities as $act)
                    <div class="flex items-start gap-3">
                        {{-- Icon by log_name --}}
                        @php
                            $log = strtolower($act->log_name ?? '');
                            $isOutgoing = in_array($log, ['wa_send','wa_chat','wa_outgoing','wa_broadcast']);
                            $isIncoming = in_array($log, ['wa_reply','wa_incoming']);
                        @endphp
                        <div class="mt-1">
                            @if($isOutgoing)
                                <div class="w-8 h-8 rounded-full bg-success/10 text-success flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13"/><path d="M22 2 15 22 11 13 2 9 22 2"/></svg>
                                </div>
                            @elseif($isIncoming)
                                <div class="w-8 h-8 rounded-full bg-info/10 text-info flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                </div>
                            @else
                                <div class="w-8 h-8 rounded-full bg-neutral/10 text-neutral flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                </div>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-x-2 text-sm">
                                <span class="font-medium">{{ $act->description ?? Str::title($act->log_name ?? 'aktivitas') }}</span>
                                <span class="text-neutral/50">•</span>
                                <span class="text-neutral/60">{{ $act->created_at?->format('d M Y H:i') }}</span>
                                @if($act->causer)
                                    <span class="text-neutral/50">•</span>
                                    <span class="text-neutral/60">oleh <strong>{{ $act->causer->name }}</strong></span>
                                @endif
                            </div>
                            @if(!blank($act->properties?->toArray() ?? []))
                                <pre class="mt-2 bg-base-200/60 text-xs p-3 rounded-md overflow-x-auto">{{ json_encode($act->properties, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center text-neutral/60 py-8">Belum ada aktivitas untuk lead ini.</div>
                @endforelse
            </div>

            <div class="mt-6">{{ $activities->links() }}</div>
        </div>
    </div>
</div>
@endsection
