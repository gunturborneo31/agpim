@props([
    'nextAgenda' => null,
    'leaderLabel' => 'Pimpinan',
])

@php
    $countdown = null;
    if ($nextAgenda && ! $nextAgenda->time_unknown) {
        $start = $nextAgenda->event_date->copy()->setTimeFromTimeString($nextAgenda->start_time);
        $countdown = $start->isFuture()
            ? $start->diffForHumans(['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW])
            : 'sedang berlangsung';
    }
@endphp

<section class="highlight-card p-4 sm:p-5">
    @if ($nextAgenda)
        <p class="text-xs text-muted">
            Berikutnya
            <span class="font-mono font-medium text-[#2f55d4]">· {{ $countdown ?? 'jam belum ditentukan' }}</span>
        </p>
        <h2 class="mt-1.5 text-base font-semibold leading-snug sm:text-lg">{{ $nextAgenda->title }}</h2>
        <p class="mt-1 text-[13px] text-muted">
            {{ $nextAgenda->event_date->isToday() ? 'Hari ini' : $nextAgenda->event_date->translatedFormat('l, j M') }}
            · <span class="font-mono">{{ $nextAgenda->time_range_display }}</span>
        </p>
        <p class="text-[13px] text-muted">{{ $nextAgenda->location }}</p>
        <div class="mt-4 flex flex-wrap gap-2">
            <a href="{{ route('agendas.show', $nextAgenda) }}" class="btn-primary !px-3 !py-1.5">Lihat detail</a>
        </div>
    @else
        <p class="text-xs text-muted">Berikutnya</p>
        <p class="mt-1.5 text-sm text-ink">Belum ada agenda mendatang untuk {{ $leaderLabel }}.</p>
    @endif
</section>
