@props([
    'title',
    'meta',
    'tone' => 'neutral',
    'href' => null,
])

@php
    $dot = match ($tone) {
        'warning' => 'bg-[#d08a1c]',
        'danger' => 'bg-[#c4321f]',
        default => 'bg-slate-300',
    };
@endphp

<div {{ $attributes->class(['flex gap-2.5 py-2 text-sm']) }}>
    <span class="mt-[7px] h-1.5 w-1.5 flex-none rounded-full {{ $dot }}"></span>
    <div class="min-w-0">
        @if ($href)
            <a href="{{ $href }}" class="font-medium text-ink hover:text-[#2f55d4]">{{ $title }}</a>
        @else
            <p class="font-medium text-ink">{{ $title }}</p>
        @endif
        <p class="text-[13px] text-muted">{{ $meta }}</p>
    </div>
</div>
