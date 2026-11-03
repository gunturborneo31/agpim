@props([
    'time',
    'title',
    'meta' => null,
    'href' => null,
    'past' => false,
    // Dipertahankan untuk pemakaian lama.
    'priority' => null,
    'priorityLabel' => null,
])

<article {{ $attributes->class(['agenda-row', 'opacity-55' => $past]) }}>
    <time class="tabular">{{ $time }}</time>
    <div class="min-w-0">
        @if ($href)
            <a href="{{ $href }}" class="font-medium text-ink hover:text-[#2f55d4]">{{ $title }}</a>
        @else
            <p class="font-medium text-ink">{{ $title }}</p>
        @endif
        @if ($meta)
            <p class="mt-0.5 text-[13px] text-muted">{{ $meta }}</p>
        @endif
    </div>
    <div class="col-start-2 flex flex-wrap items-center gap-1.5 sm:col-start-auto sm:justify-end">
        @if (trim($slot) !== '')
            {{ $slot }}
        @elseif ($priority && $priorityLabel)
            <span class="priority-chip priority-{{ $priority }}">{{ $priorityLabel }}</span>
        @endif
    </div>
</article>
