@props(['title' => '', 'value' => '', 'change' => null, 'changeType' => 'neutral'])

@php
    $color = match ($changeType) {
        'success' => 'text-success',
        'warning' => 'text-warning',
        'error'   => 'text-error',
        default   => 'text-neutral',
    };
@endphp

<div {{ $attributes->merge(['class' => 'card bg-base-100 shadow-sm border border-base-300/50']) }}>
    <div class="card-body">
        <div class="flex items-center gap-3">
            <div class="rounded-lg p-2 bg-base-200">
                {{ $slot }}
            </div>
            <div>
                <div class="text-sm text-neutral/60">{{ $title }}</div>
                <div class="text-2xl font-bold">{{ $value }}</div>
                @if ($change)
                    <div class="text-xs {{ $color }}">{{ $change }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
