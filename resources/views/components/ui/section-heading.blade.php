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
        <h2 @class(['text-xl font-semibold text-ink', 'mt-1' => $eyebrow])>{{ $title }}</h2>
    @endif

    @if ($subtitle)
        <p class="mt-1 text-sm text-muted">{{ $subtitle }}</p>
    @endif
</div>
