@extends('layouts.app')

@section('title', 'Detail Lead')

@section('content')
@php
    // Label status agar konsisten
    $statusLabel = $statusLabel ?? [
        'trial'     => 'Trial',
        'active'    => 'Aktif',
        'nonactive' => 'Tidak Aktif',
        'converted' => 'Konversi',
        'churn'     => 'Dibatalkan',
    ];

    // Label ramah untuk field yang umum berubah
    $fieldLabels = [
        'name'         => 'Nama',
        'email'        => 'Email',
        'phone'        => 'No. WhatsApp',
        'store_name'   => 'Nama Toko',
        'status'       => 'Status',
        'owner_id'     => 'PIC/Owner',
        'trial_ends_at'=> 'Trial Habis',
        'created_at'   => 'Tanggal Daftar',
        'updated_at'   => 'Diubah',
        'plan'         => 'Nama Paket',
        'amount'       => 'Nominal',
        'cycle'        => 'Siklus',
        'start_date'   => 'Mulai',
        'end_date'     => 'Berakhir',
    ];

    // Helper untuk format tampilan nilai field
    $fmt = function ($key, $val) use ($statusLabel) {
        if ($val === null || $val === '') return '—';
        if (in_array($key, ['created_at','updated_at','trial_ends_at','start_date','end_date'], true)) {
            try { return \Carbon\Carbon::parse($val)->translatedFormat('d M Y H:i'); } catch (\Throwable $e) {
                try { return \Carbon\Carbon::parse($val)->translatedFormat('d M Y'); } catch (\Throwable $e2) { return (string)$val; }
            }
        }
        if ($key === 'status') return $statusLabel[$val] ?? $val;
        if ($key === 'owner_id') {
            $owner = \App\Models\User::find($val);
            return $owner?->name ?? '—';
        }
        if ($key === 'amount') return 'Rp ' . number_format((float)$val, 0, ',', '.');
        return (string)$val;
    };

    // Icon & warna per jenis aktivitas
    $icon = function ($type) {
        $map = [
            'created'     => ['svg' => 'M12 5v14m7-7H5', 'cls' => 'bg-success/10 text-success'],
            'updated'     => ['svg' => 'M3 12h18M12 3v18', 'cls' => 'bg-warning/10 text-warning'],
            'wa_chat'     => ['svg' => 'm3 11 18-5v12L3 14v-3z', 'cls' => 'bg-primary/10 text-primary'],
            'wa_incoming' => ['svg' => 'M21 15a4 4 0 0 1-4 4H7l-4 3V7a4 4 0 0 1 4-4h8', 'cls' => 'bg-info/10 text-info'],
            'wa_reply'    => ['svg' => 'M3 12h8l-3 3m3-3-3-3', 'cls' => 'bg-info/10 text-info'],
            'follow_up'   => ['svg' => 'M12 6v6l4 2', 'cls' => 'bg-secondary/10 text-secondary'],
            'default'     => ['svg' => 'M12 6v6m0 0 4 2M12 12l-4 2', 'cls' => 'bg-neutral/10 text-neutral'],
        ];
        return $map[$type] ?? $map['default'];
    };

    // Teks judul ringkas per event
    $titleOf = function ($a) {
        $type = $a->log_name ?? 'default';
        $desc = (string)($a->description ?? '');
        return match ($type) {
            'created'     => 'Lead dibuat',
            'updated'     => 'Lead diperbarui',
            'wa_chat'     => 'WA terkirim',
            'wa_incoming' => 'WA masuk',
            'wa_reply'    => 'Balasan WA',
            'follow_up'   => 'Reminder Follow-up',
            default       => ($desc !== '' ? $desc : 'Aktivitas'),
        };
    };

    $owner = $lead->owner;
    $ownerAvatar = $owner?->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($owner?->name ?? 'PIC').'&background=1F2937&color=fff&format=png';
    $waSanitize = fn($p) => preg_replace('/\D+/', '', (string)$p);
@endphp

{{-- Back --}}
<div class="mb-4">
    <a href="{{ route('leads.index') }}" class="link link-neutral">
        ← Kembali ke Leads
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Kartu Info Lead --}}
    <div class="lg:col-span-2 card bg-base-100 border shadow-sm">
        <div class="card-body">
            <div class="flex items-start gap-4">
                <div class="avatar">
                    <div class="w-16 rounded-full ring ring-offset-2 ring-base-300">
                        <img src="{{ $ownerAvatar }}" alt="PIC">
                    </div>
                </div>
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-bold">{{ $lead->name }}</h1>
                        <span class="badge">{{ $statusLabel[$lead->status] ?? $lead->status }}</span>
                    </div>
                    <div class="mt-1 text-sm text-neutral/70 space-x-4">
                        <span>Email: <a class="link" href="mailto:{{ $lead->email }}">{{ $lead->email }}</a></span>
                        @if($lead->phone)
                            <span>WA: <a class="link" href="https://wa.me/{{ $waSanitize($lead->phone) }}" target="_blank">{{ $lead->phone }}</a></span>
                        @endif
                    </div>
                    <div class="mt-2 text-xs text-neutral/60">
                        Owner: <strong>{{ $owner?->name ?? '—' }}</strong>
                        • Dibuat: {{ $lead->created_at?->translatedFormat('d M Y H:i') }}
                        • Trial Habis: {{ $lead->trial_ends_at?->translatedFormat('d M Y') ?? '—' }}
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @if($lead->phone)
                        <a class="btn btn-success btn-sm" href="https://wa.me/{{ $waSanitize($lead->phone) }}" target="_blank">
                            Chat Manual (wa.me)
                        </a>
                        @endif
                        <a class="btn btn-outline btn-sm" href="{{ route('leads.index') }}#edit_lead_modal_{{ $lead->id }}">
                            Edit Lead
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Kartu Langganan --}}
    <div class="card bg-base-100 border shadow-sm">
        <div class="card-body">
            <h3 class="card-title">Langganan</h3>
            @if($lead->subscription)
                <div class="space-y-1 text-sm">
                    <div><span class="text-neutral/60">Paket:</span> <strong>{{ $lead->subscription->plan }}</strong></div>
                    <div><span class="text-neutral/60">Nominal:</span> <strong>Rp {{ number_format($lead->subscription->amount, 0, ',', '.') }}</strong></div>
                    <div><span class="text-neutral/60">Siklus:</span> <strong>{{ $lead->subscription->cycle === 'yearly' ? 'Tahunan' : 'Bulanan' }}</strong></div>
                    <div><span class="text-neutral/60">Mulai:</span> {{ optional($lead->subscription->start_date)->translatedFormat('d M Y') ?? '—' }}</div>
                    <div><span class="text-neutral/60">Berakhir:</span> {{ optional($lead->subscription->end_date)->translatedFormat('d M Y') ?? '—' }}</div>
                </div>
            @else
                <p class="text-sm text-neutral/70">Belum ada data langganan.</p>
            @endif
        </div>
    </div>
</div>

{{-- Timeline Aktivitas --}}
<div class="card bg-base-100 border shadow-sm mt-6">
    <div class="card-body">
        <div class="flex items-center justify-between mb-2">
            <h3 class="card-title">Timeline Aktivitas</h3>
            <div class="text-xs text-neutral/60">Total: {{ $activities->total() }}</div>
        </div>

        @forelse($activities as $a)
            @php
                $type = $a->log_name ?? 'default';
                $meta = $icon($type);
                $title = $titleOf($a);
                $props = $a->properties instanceof \Spatie\Activitylog\ActivityLogStatus ? [] : ($a->properties ?? collect());
                // Properti standar Spatie: attributes (new) & old
                $attributes = data_get($props, 'attributes', []);
                $old = data_get($props, 'old', []);

                // Cuplikan WA (kalau ada)
                $method = data_get($props, 'method', null);
                $senderId = data_get($props, 'sender_id', null);
                $number   = data_get($props, 'number', null) ?: data_get($props, 'phone', null);
                $message  = data_get($props, 'message', null) ?: data_get($props, 'text', null);

                // Diff field untuk event "updated"
                $diffs = [];
                if ($type === 'updated') {
                    $keys = array_unique(array_merge(array_keys((array)$attributes), array_keys((array)$old)));
                    foreach ($keys as $k) {
                        $new = $attributes[$k] ?? null;
                        $ov  = $old[$k] ?? null;
                        if ($new != $ov) {
                            $diffs[] = [
                                'key' => $k,
                                'label' => $fieldLabels[$k] ?? Str::title(str_replace('_',' ',$k)),
                                'old' => $ov,
                                'new' => $new,
                            ];
                        }
                    }
                }
            @endphp

            <div class="relative pl-8 py-5 border-b last:border-b-0">
                {{-- Garis vertikal & bullet --}}
                <div class="absolute left-0 top-0 bottom-0 w-px bg-base-300/70"></div>
                <div class="absolute -left-2.5 mt-2 inline-flex items-center justify-center w-10 h-10 rounded-full {{ $meta['cls'] }}">
                    {{-- ikon sederhana (stroke current) --}}
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="{{ $meta['svg'] }}"/>
                    </svg>
                </div>

                <div class="flex flex-wrap items-center gap-x-2">
                    <h4 class="font-semibold">{{ $title }}</h4>
                    <span class="text-xs text-neutral/60">• {{ $a->created_at->translatedFormat('d M Y H:i') }}</span>
                    @if($a->causer)
                        <span class="text-xs text-neutral/60">• oleh <strong>{{ $a->causer->name }}</strong></span>
                    @endif
                </div>

                {{-- Ringkasan konten per jenis --}}
                <div class="mt-2 text-sm">
                    @if(in_array($type, ['wa_chat','wa_reply','wa_incoming']))
                        <div class="flex flex-wrap items-center gap-2">
                            @if($number)<span class="badge badge-ghost">Nomor: {{ $number }}</span>@endif
                            @if($method)<span class="badge badge-ghost">Via: {{ strtoupper($method) }}</span>@endif
                            @if($senderId)<span class="badge badge-ghost">Sender: #{{ $senderId }}</span>@endif
                        </div>
                        @if($message)
                            <div class="mt-2 p-3 rounded-lg bg-base-200/60">
                                <div class="text-xs text-neutral/50 mb-1">{{ $type === 'wa_chat' ? 'Pesan dikirim:' : 'Pesan diterima:' }}</div>
                                <div class="whitespace-pre-wrap">{{ Str::limit($message, 500) }}</div>
                            </div>
                        @endif
                    @elseif($type === 'updated')
                        @if (count($diffs))
                            <div class="overflow-hidden rounded-lg border bg-base-100">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th class="w-40">Field</th>
                                            <th>Sebelum</th>
                                            <th>Sesudah</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($diffs as $d)
                                            <tr>
                                                <td class="text-neutral/70">{{ $d['label'] }}</td>
                                                <td>{{ $fmt($d['key'], $d['old']) }}</td>
                                                <td><strong>{{ $fmt($d['key'], $d['new']) }}</strong></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-neutral/60">Perubahan tersimpan.</div>
                        @endif
                    @else
                        @if($a->description)
                            <div class="text-neutral/80">{{ $a->description }}</div>
                        @else
                            <div class="text-neutral/60">Aktivitas tercatat.</div>
                        @endif
                    @endif
                </div>

                {{-- Detail teknis (opsional untuk developer) --}}
                @if(!empty($a->properties) && count((array)$a->properties))
                    <details class="mt-3">
                        <summary class="cursor-pointer text-xs text-neutral/60">Lihat detail teknis</summary>
                        <pre class="mt-2 p-3 rounded-lg bg-base-200/60 text-xs overflow-x-auto">{{ json_encode($a->properties, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre>
                    </details>
                @endif
            </div>
        @empty
            <div class="py-10 text-center text-neutral/60">Belum ada aktivitas.</div>
        @endforelse

        {{-- Pagination --}}
        <div class="mt-4">{{ $activities->onEachSide(1)->links() }}</div>
    </div>
</div>
@endsection
