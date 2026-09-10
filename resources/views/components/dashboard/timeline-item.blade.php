@props([
    'time',
    'title',
    'meta',
    'priority',
    'priorityLabel',
])

<article class="bento-card border-l-4 border-l-blue-600 p-4 sm:p-5 md:p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-6">
        <div class="min-w-0 text-base font-bold text-slate-800 sm:min-w-[74px] sm:text-lg">{{ $time }}</div>
        <div class="w-full">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h4 class="text-base font-bold text-slate-900 sm:text-lg">{{ $title }}</h4>
                    <p class="text-sm text-slate-600">{{ $meta }}</p>
                </div>
                <span class="priority-chip priority-{{ $priority }}">{{ $priorityLabel }}</span>
            </div>
        </div>
    </div>
</article>
