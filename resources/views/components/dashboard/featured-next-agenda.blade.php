@props([
    'nextAgenda' => null,
    'leaderLabel' => 'Pimpinan',
])

<section class="featured-gradient relative overflow-hidden rounded-2xl p-5 text-white shadow-[0_16px_36px_rgba(0,76,202,0.25)] sm:rounded-3xl sm:p-6 lg:p-8">
    <div class="absolute -right-14 -top-14 hidden h-56 w-56 rounded-full bg-white/10 blur-3xl sm:block"></div>
    <div class="absolute bottom-6 right-20 hidden h-36 w-36 rounded-full bg-blue-200/30 blur-2xl sm:block"></div>
    <div class="relative z-10">
        <div class="mb-4 flex items-center gap-2">
            <span class="rounded-full bg-white/20 px-3 py-1 text-[11px] font-bold uppercase tracking-widest">Agenda Berikutnya</span>
            <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
        </div>

        @if ($nextAgenda)
            <p class="text-sm text-white/70">{{ $leaderLabel }}</p>
            <h2 class="mt-2 text-2xl font-bold leading-tight sm:text-3xl">{{ $nextAgenda->title }}</h2>
            <div class="mt-5 grid grid-cols-1 gap-3 border-t border-white/20 pt-4 text-sm sm:mt-6 sm:gap-4 sm:pt-5 sm:grid-cols-3">
                <div>
                    <p class="mb-1 text-[11px] font-bold uppercase tracking-wider text-white/70">Jam</p>
                    <p class="font-semibold">{{ $nextAgenda->time_range_display }}</p>
                </div>
                <div>
                    <p class="mb-1 text-[11px] font-bold uppercase tracking-wider text-white/70">Lokasi</p>
                    <p class="font-semibold">{{ $nextAgenda->location }}</p>
                </div>
                <div>
                    <p class="mb-1 text-[11px] font-bold uppercase tracking-wider text-white/70">Countdown</p>
                    <p class="font-semibold">
                        @if ($nextAgenda->time_unknown)
                            Waktu belum ditentukan
                        @else
                            {{ now()->diffForHumans($nextAgenda->event_date->setTimeFromTimeString($nextAgenda->start_time), ['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW]) }}
                        @endif
                    </p>
                </div>
            </div>
        @else
            <div class="rounded-2xl border border-white/30 bg-white/10 p-4 text-sm">Belum ada agenda untuk pimpinan ini.</div>
        @endif
    </div>
</section>
