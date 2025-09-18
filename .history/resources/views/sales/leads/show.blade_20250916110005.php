@extends('layouts.app')

@section('title', 'Detail Lead')

@section('content')
@php
    use Illuminate\Support\Str;
    use Carbon\Carbon;

    $statusLabel = [
        'trial'     => 'Trial',
        'active'    => 'Aktif',
        'nonactive' => 'Tidak Aktif',
        'converted' => 'Konversi',
        'churn'     => 'Dibatalkan',
    ];

    // formatter ringan untuk nilai field
    $fmt = function (string $key, $val) use ($statusLabel) {
        if ($val === null || $val === '') return '—';

        if (in_array($key, ['created_at','updated_at','trial_ends_at','start_date','end_date'], true)) {
            try { return Carbon::parse($val)->translatedFormat('d M Y H:i'); } catch (\Throwable $e) {
                try { return Carbon::parse($val)->translatedFormat('d M Y'); } catch (\Throwable $e2) { return (string)$val; }
            }
        }

        return match ($key) {
            'status' => $statusLabel[$val] ?? (string)$val,
            'amount' => 'Rp ' . number_format((float)$val, 0, ',', '.'),
            default  => (string)$val,
        };
    };

    // ikon/warna per event
    $icon = function ($type) {
        $map = [
            'created'     => ['svg' => 'M12 5v14m7-7H5',                                   'cls' => 'bg-success/10 text-success'],
            'updated'     => ['svg' => 'M3 12h18M12 3v18',                                  'cls' => 'bg-warning/10 text-warning'],
            'wa_chat'     => ['svg' => 'm3 11 18-5v12L3 14v-3z',                            'cls' => 'bg-primary/10 text-primary'],
            'wa_incoming' => ['svg' => 'M21 15a4 4 0 0 1-4 4H7l-4 3V7a4 4 0 0 1 4-4h8',     'cls' => 'bg-info/10 text-info'],
            'wa_reply'    => ['svg' => 'M3 12h8l-3 3m3-3-3-3',                               'cls' => 'bg-info/10 text-info'],
            'follow_up'   => ['svg' => 'M12 6v6l4 2',                                       'cls' => 'bg-secondary/10 text-secondary'],
            default       => ['svg' => 'M12 6v6m0 0 4 2M12 12l-4 2',                         'cls' => 'bg-neutral/10 text-neutral'],
        ];
        return $map[$type] ?? $map['default'];
    };

    // judul ringkas
    $titleOf = function ($a) {
        return match($a->log_name ?? 'default') {
            'created'     => 'Lead dibuat',
            'updated'     => 'Lead diperbarui',
            'wa_chat'     => 'WA terkirim',
            'wa_incoming' => 'WA masuk',
            'wa_reply'    => 'Balasan WA',
            'follow_up'   => 'Reminder Follow-up',
            default       => ($a->description ?: 'Aktivitas'),
        };
    };

    $owner = $lead->owner;
    $ownerAvatar = $owner?->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($owner?->name ?? 'PIC').'&background=1F2937&color=fff&format=png';
    $waSanitize = fn($p) => preg_replace('/\D+/', '', (string)$p);
@endphp

<div class="mb-4">
    <a href="{{ route('leads.index') }}" class="link link-neutral">← Kembali ke Leads</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Kartu Info --}}
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

{{-- Timeline --}}
<div class="card bg-base-100 border shadow-sm mt-6">
    <div class="card-body">
        <div class="flex items-center justify-between mb-2">
            <h3 class="card-title">Timeline Aktivitas</h3>
            <div class="text-xs text-neutral/60">Total: {{ $activities->total() }}</div>
        </div>

        @forelse($activities as $a)
            @php
                $type   = $a->log_name ?? 'default';
                $meta   = $icon($type);
                $title  = $titleOf($a);
                $props  = is_object($a->properties) && method_exists($a->properties,'toArray')
                            ? $a->properties->toArray() : (array)$a->properties;

                // normalisasi
                $attrs  = $props['attributes'] ?? [];
                $olds   = $props['old'] ?? [];
                if ($attrs instanceof \Illuminate\Support\Collection) $attrs = $attrs->toArray();
                if ($olds  instanceof \Illuminate\Support\Collection) $olds  = $olds->toArray();

                // diff ringkas (hanya field yang berubah)
                $diffs  = [];
                if ($type === 'updated') {
                    $keys = array_unique(array_merge(array_keys((array)$attrs), array_keys((array)$olds)));
                    foreach ($keys as $k) {
                        $nv = $attrs[$k] ?? null;
                        $ov = $olds[$k] ?? null;
                        if ($nv != $ov) {
                            $diffs[] = [
                                'label' => Str::of($k)->replace('_',' ')->title()->toString(),
                                'key'   => $k,
                                'old'   => $ov,
                                'new'   => $nv,
                            ];
                        }
                    }
                }

                // WA snippet
                $number   = data_get($props,'number') ?: data_get($props,'phone');
                $senderId = data_get($props,'sender_id');
                $status   = data_get($props,'result.status') ?: data_get($props,'status');
                $httpCode = data_get($props,'http');
                $text     = data_get($props,'message.extendedTextMessage.text')
                         ?? data_get($props,'message.conversation')
                         ?? data_get($props,'text')
                         ?? (is_string(data_get($props,'message')) ? data_get($props,'message') : null);
            @endphp

            <div class="relative pl-8 py-5 border-b last:border-b-0">
                <div class="absolute left-0 top-0 bottom-0 w-px bg-base-300/70"></div>
                <div class="absolute -left-2.5 mt-2 inline-flex items-center justify-center w-10 h-10 rounded-full {{ $meta['cls'] }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="{{ $meta['svg'] }}"/>
                    </svg>
                </div>

                <div class="flex flex-wrap items-center gap-x-2">
                    <h4 class="font-semibold">{{ $title }}</h4>
                    <span class="text-xs text-neutral/60">• {{ $a->created_at->translatedFormat('d M Y H:i') }}</span>
                    @if($a->causer)<span class="text-xs text-neutral/60">• oleh <strong>{{ $a->causer->name }}</strong></span>@endif
                </div>

                {{-- isi yang enak dibaca --}}
                <div class="mt-2 text-sm space-y-2">
                    @if($type === 'updated')
                        @if(count($diffs))
                            <ul class="space-y-1">
                                @foreach($diffs as $d)
                                    <li class="flex flex-wrap items-start gap-2">
                                        <span class="text-neutral/60 min-w-[120px]">{{ $d['label'] }}:</span>
                                        <span class="line-through text-neutral/50">{{ $fmt($d['key'], $d['old']) }}</span>
                                        <span class="mx-1">→</span>
                                        <span class="font-semibold">{{ $fmt($d['key'], $d['new']) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-neutral/60">Perubahan tersimpan.</div>
                        @endif
                    @elseif(in_array($type, ['wa_chat','wa_incoming','wa_reply']))
                        <div class="flex flex-wrap items-center gap-2">
                            @if($number)<span class="badge badge-ghost">Nomor: {{ $number }}</span>@endif
                            @if($senderId)<span class="badge badge-ghost">Sender: #{{ $senderId }}</span>@endif
                            @if($status)<span class="badge {{ $status === 'PENDING' ? 'badge-warning' : 'badge-success' }}">{{ $status }}</span>@endif
                            @if($httpCode)<span class="badge badge-ghost">HTTP {{ $httpCode }}</span>@endif
                        </div>
                        @if($text)
                            <div class="p-3 rounded-lg bg-base-200/60 whitespace-pre-wrap">
                                {{ Str::limit($text, 600) }}
                            </div>
                        @endif
                    @else
                        <div class="text-neutral/80">
                            {{ $a->description ?: 'Aktivitas tercatat.' }}
                        </div>
                    @endif
                </div>

                {{-- detail teknis opsional --}}
                @php $hasRaw = !empty($props); @endphp
                @if($hasRaw)
                    <details class="mt-3">
                        <summary class="cursor-pointer text-xs text-neutral/60">Lihat detail teknis</summary>
                        <pre class="mt-2 p-3 rounded-lg bg-base-200/60 text-xs overflow-x-auto">{{ json_encode($props, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre>
                    </details>
                @endif
            </div>
        @empty
            <div class="py-10 text-center text-neutral/60">Belum ada aktivitas.</div>
        @endforelse

        <div class="mt-4">{{ $activities->onEachSide(1)->links() }}</div>
    </div>
</div>
@endsection
