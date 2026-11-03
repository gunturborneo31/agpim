<section class="card-panel p-4" aria-labelledby="calendar-heading">
    <div class="flex items-center justify-between">
        <h2 id="calendar-heading" class="text-sm font-medium">{{ $calendarMonthLabel }}</h2>
        <div class="flex items-center gap-0.5 text-muted">
            <button type="button" class="mr-1 flex h-7 items-center gap-1 rounded-md px-2 text-xs font-medium text-[#2f55d4] hover:bg-hover" @click="fullCalendar = true" title="Buka kalender penuh">
                <span class="material-symbols-outlined text-[16px]">open_in_full</span> Kalender penuh
            </button>
            <a href="{{ route('dashboard', ['leader' => $leader, 'calendar_month' => $calendarMonth->copy()->subMonthNoOverflow()->month, 'calendar_year' => $calendarMonth->copy()->subMonthNoOverflow()->year]) }}" class="flex h-7 w-7 items-center justify-center rounded-md hover:bg-hover" aria-label="Bulan sebelumnya" @click.prevent="loadMonth($el.href)">
                <span class="material-symbols-outlined text-[18px]">chevron_left</span>
            </a>
            <a href="{{ route('dashboard', ['leader' => $leader, 'calendar_month' => $calendarMonth->copy()->addMonthNoOverflow()->month, 'calendar_year' => $calendarMonth->copy()->addMonthNoOverflow()->year]) }}" class="flex h-7 w-7 items-center justify-center rounded-md hover:bg-hover" aria-label="Bulan berikutnya" @click.prevent="loadMonth($el.href)">
                <span class="material-symbols-outlined text-[18px]">chevron_right</span>
            </a>
        </div>
    </div>

    <div class="mt-3 grid grid-cols-7 gap-0.5 text-center">
        @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $dayLabel)
            <div class="pb-1 text-[11px] text-muted">{{ $dayLabel }}</div>
        @endforeach
        @foreach ($calendarDays as $day)
            <button type="button"
                @class([
                    'mini-cal-day',
                    'is-out' => ! $day['isCurrentMonth'],
                    'has-agenda' => $day['count'] > 0,
                    'is-today' => $day['isToday'],
                ])
                title="{{ $day['count'] > 0 ? $day['count'].' agenda' : 'Tidak ada agenda' }}"
                @click="openDate('{{ $day['date'] }}', '{{ \Illuminate\Support\Carbon::parse($day['date'])->translatedFormat('l, j F Y') }}')">
                {{ $day['day'] }}
            </button>
        @endforeach
    </div>
</section>
