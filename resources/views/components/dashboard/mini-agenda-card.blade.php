@props([
    'title',
    'meta',
    'tone' => 'neutral',
])

@php
    $toneClass = match ($tone) {
        'warning' => 'border-orange-200',
        'danger' => 'border-red-200',
        default => 'border-slate-200',
    };
@endphp

<div class="rounded-2xl border bg-white px-3 py-2.5 text-sm text-slate-700 md:px-4 md:py-3 {{ $toneClass }}">
    <p class="font-semibold text-slate-900">{{ $title }}</p>
    <p class="text-slate-500">{{ $meta }}</p>
</div>
