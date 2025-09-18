@props([
    'title' => 'Title',
    'value' => '0',
    'change' => null,
    'changeType' => '' // 'success' or 'error'
])

<div {{ $attributes->merge(['class' => 'card bg-base-100 shadow-sm border border-base-300/50 h-full']) }}>
    <div class="card-body">
        <div class="flex items-start justify-between">
            <div class="text-sm font-semibold text-neutral/60">{{ $title }}</div>
            @if($slot->isNotEmpty())
            {{-- PERBAIKAN: Menambahkan 'text-primary' untuk konsistensi warna ikon --}}
            <div class="bg-secondary text-primary p-2 rounded-lg">
                {{ $slot }}
            </div>
            @endif
        </div>
        <p class="text-3xl font-bold mt-2">{{ $value }}</p>
        @if($change)
        <p class="text-xs text-neutral/60 mt-1">
            <span @class([
                'font-semibold',
                'text-success' => $changeType === 'success',
                'text-error' => $changeType === 'error',
            ])>
                {{ $change }}
            </span>
        </p>
        @endif
    </div>
</div>

