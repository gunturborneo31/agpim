@props([
    'title',
    'window',
    'recommendation',
])

<div class="rounded-lg px-3 py-2.5 text-sm" style="background: var(--grad-bad)">
    <p class="font-medium text-[#8e2318]">{{ $title }}</p>
    <p class="mt-0.5 text-[13px] text-[#8e2318]/80">Bentrok pukul <span class="font-mono">{{ $window }}</span></p>
    <p class="mt-1 text-[13px] text-muted">{{ $recommendation }}</p>
</div>
