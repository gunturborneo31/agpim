@props([
    'eyebrow' => null,
    'title' => null,
    'subtitle' => null,
])

<div {{ $attributes }}>
    @if ($eyebrow)
        <p class="section-label">{{ $eyebrow }}</p>
    @endif

    @if ($title)
        <h2 class="mt-2 text-2xl font-semibold text-slate-900">{{ $title }}</h2>
    @endif

    @if ($subtitle)
        <p class="mt-2 text-sm text-slate-600">{{ $subtitle }}</p>
    @endif
</div>
