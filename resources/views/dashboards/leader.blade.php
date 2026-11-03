@extends('layouts.app', ['title' => 'AGPIM • Dashboard'])

@php
    $statusLabels = config('agpim.statuses');
    $conflictIds = $conflicts->flatMap(fn ($c) => [$c['a']->id, $c['b']->id])->unique();
    $isLeaderUser = auth()->user()?->hasRole('bupati', 'wakil_bupati', 'sekda');
    $needsAttention = $conflicts->isNotEmpty() || $priorities->isNotEmpty();

    $isPast = function ($agenda) {
        if ($agenda->time_unknown || ! $agenda->end_time) {
            return false;
        }

        return $agenda->event_date->copy()->setTimeFromTimeString($agenda->end_time)->isPast();
    };
@endphp

@section('content')
<div x-data="dashboardCalendar({
        map: @js($calendarAgendaMap),
        year: {{ $calendarMonth->year }},
        month: {{ $calendarMonth->month }},
        label: @js($calendarMonthLabel),
        baseUrl: @js(route('dashboard')),
        leader: @js($leader),
        today: @js(today()->toDateString()),
    })"
    @keydown.escape.window="calendarModal ? (calendarModal = false) : (fullCalendar = false)">

    {{-- Kepala halaman --}}
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-sm text-muted">Agenda {{ $leaderLabel }}</p>
            <h1 class="text-2xl font-semibold sm:text-[1.75rem]">{{ today()->translatedFormat('l, j F') }}</h1>
            <p class="mt-1 text-sm text-muted">
                <span class="tabular">{{ $summary['today'] }}</span> agenda hari ini
                @if ($conflicts->isNotEmpty())
                    · <span class="font-medium text-[#c4321f]">{{ $conflicts->count() }} bentrok</span>
                @endif
                @if ($summary['highPriority'] > 0)
                    · {{ $summary['highPriority'] }} prioritas tinggi
                @endif
            </p>
        </div>

        @unless ($isLeaderUser)
            <nav class="segmented max-w-full overflow-x-auto" aria-label="Pilih pimpinan">
                @foreach ($leaders as $key => $label)
                    <a href="{{ route('dashboard', ['leader' => $key]) }}"
                       @class(['tab-chip whitespace-nowrap', 'tab-chip-active' => $leader === $key])
                       @if ($leader === $key) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>
        @endunless
    </header>

    <div class="mt-6 grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
        {{-- Kolom utama --}}
        <div class="min-w-0 space-y-10">
            {{-- Di HP, agenda berikutnya tampil paling atas. --}}
            <div class="lg:hidden">
                <x-dashboard.featured-next-agenda :next-agenda="$nextAgenda" :leader-label="$leaderLabel" />
            </div>

            <section aria-labelledby="today-heading">
                <h2 id="today-heading" class="section-label">Hari ini</h2>
                <div class="mt-2 border-t border-line">
                    @forelse ($timeline as $agenda)
                        <x-dashboard.timeline-item
                            :time="$agenda->start_time_display"
                            :title="$agenda->title"
                            :meta="$agenda->location.' · '.($agenda->opd->alias ?? $agenda->opd->name)"
                            :href="route('agendas.show', $agenda)"
                            :past="$isPast($agenda)"
                        >
                            @if ($conflictIds->contains($agenda->id))
                                <span class="chip chip-bad">Bentrok</span>
                            @elseif ($isPast($agenda))
                                <span class="chip chip-neutral">Selesai</span>
                            @else
                                <x-ui.status-pill :status="$agenda->status" :text="$statusLabels[$agenda->status] ?? $agenda->status" />
                            @endif
                            @if ($agenda->priority === 'tinggi')
                                <span class="priority-chip priority-tinggi">Prioritas</span>
                            @endif
                        </x-dashboard.timeline-item>
                    @empty
                        <p class="py-6 text-sm text-muted">Tidak ada agenda hari ini.</p>
                    @endforelse
                </div>
            </section>

            <section x-data="{ tab: 'Besok' }" aria-labelledby="upcoming-heading">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="upcoming-heading" class="section-label">Mendatang</h2>
                    <div class="segmented">
                        @foreach (array_keys($quickTabs) as $label)
                            @continue($label === 'Hari Ini')
                            <button type="button" class="tab-chip" :class="tab === '{{ $label }}' && 'tab-chip-active'" @click="tab = '{{ $label }}'">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
                @foreach ($quickTabs as $label => $items)
                    @continue($label === 'Hari Ini')
                    <div x-show="tab === '{{ $label }}'" x-cloak class="mt-2 border-t border-line">
                        @forelse ($items as $item)
                            <x-dashboard.timeline-item
                                :time="$item->start_time_display"
                                :title="$item->title"
                                :meta="$item->event_date->translatedFormat('D, j M').' · '.$item->location"
                                :href="route('agendas.show', $item)"
                            >
                                <span class="chip chip-neutral">{{ $item->type->name }}</span>
                            </x-dashboard.timeline-item>
                        @empty
                            <p class="py-6 text-sm text-muted">Tidak ada agenda {{ strtolower($label) }}.</p>
                        @endforelse
                    </div>
                @endforeach
            </section>
        </div>

        {{-- Kolom samping --}}
        <aside class="min-w-0 space-y-6">
            <div class="hidden lg:block">
                <x-dashboard.featured-next-agenda :next-agenda="$nextAgenda" :leader-label="$leaderLabel" />
            </div>

            <div id="dashboard-calendar" class="relative" :class="calendarLoading && 'pointer-events-none opacity-60'">
                @include('dashboards.partials.calendar')
            </div>

            <section aria-labelledby="attention-heading">
                <h2 id="attention-heading" class="section-label">Perlu perhatian</h2>
                @if ($needsAttention)
                    <div class="mt-2 space-y-4">
                        @if ($conflicts->isNotEmpty())
                            <div class="space-y-2">
                                @foreach ($conflicts as $conflict)
                                    <x-dashboard.conflict-card
                                        :title="$conflict['a']->title.' dan '.$conflict['b']->title"
                                        :window="$conflict['window']"
                                        :recommendation="$conflict['recommendation']"
                                    />
                                @endforeach
                            </div>
                        @endif

                        @if ($priorities->isNotEmpty())
                            <div>
                                <p class="text-xs font-medium text-muted">Prioritas tinggi</p>
                                <div class="divide-y divide-line">
                                    @foreach ($priorities as $agenda)
                                        <x-dashboard.mini-agenda-card
                                            tone="danger"
                                            :title="$agenda->title"
                                            :meta="$agenda->event_date->translatedFormat('j M').' · '.$agenda->start_time_display.' · '.$agenda->location"
                                            :href="route('agendas.show', $agenda)"
                                        />
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <p class="mt-2 flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm text-[#1d6b40]" style="background: var(--grad-ok)">
                        <span class="material-symbols-outlined text-[18px]">check_circle</span>
                        Tidak ada bentrok atau agenda prioritas tinggi.
                    </p>
                @endif
            </section>
        </aside>
    </div>

    {{-- Kalender penuh (tampilan bulan bergaya Google Calendar) --}}
    <div x-cloak x-show="fullCalendar" x-transition.opacity class="fixed inset-0 z-[70] flex items-stretch justify-center bg-black/25 sm:p-4" @click.self="fullCalendar = false">
        <div class="flex h-full w-full max-w-7xl flex-col overflow-hidden bg-white shadow-[0_24px_60px_rgba(28,27,25,0.22)] sm:rounded-xl sm:border sm:border-line" role="dialog" aria-modal="true" aria-label="Kalender agenda {{ $leaderLabel }}">
            {{-- Kepala: navigasi bulan --}}
            <div class="flex flex-wrap items-center gap-2 border-b border-line px-4 py-3 sm:px-5" style="padding-top: max(0.75rem, env(safe-area-inset-top))">
                <button type="button" class="btn-secondary !px-3 !py-1.5 !text-xs" @click="goToToday()">Hari ini</button>
                <div class="flex items-center text-muted">
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-md hover:bg-hover" @click="loadMonth(monthUrl(-1))" aria-label="Bulan sebelumnya">
                        <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                    </button>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-md hover:bg-hover" @click="loadMonth(monthUrl(1))" aria-label="Bulan berikutnya">
                        <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                    </button>
                </div>
                <h2 class="text-lg font-semibold sm:text-xl" x-text="calendarLabel"></h2>
                <span x-show="calendarLoading" class="material-symbols-outlined animate-spin text-[18px] text-muted">progress_activity</span>
                <span class="ml-auto hidden text-sm text-muted sm:inline">Agenda {{ $leaderLabel }}</span>
                <button type="button" class="flex h-8 w-8 items-center justify-center rounded-md text-muted hover:bg-hover hover:text-ink" @click="fullCalendar = false" aria-label="Tutup">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            {{-- Nama hari --}}
            <div class="grid grid-cols-7 border-b border-line bg-canvas text-center text-[11px] font-medium text-muted">
                <template x-for="(w, i) in weekdays" :key="w">
                    <div class="py-2" :class="i >= 5 && 'text-[#c4321f]/70'" x-text="w"></div>
                </template>
            </div>

            {{-- Grid tanggal --}}
            <div class="grid min-h-0 flex-1 grid-cols-7 auto-rows-fr overflow-y-auto" :class="calendarLoading && 'opacity-60'">
                <template x-for="day in fullDays" :key="day.key">
                    <div class="group relative flex min-h-[86px] min-w-0 cursor-pointer flex-col gap-1 border-b border-r border-line p-1 transition-colors hover:bg-[#fbfbfa] sm:min-h-[112px] sm:p-1.5"
                         :class="[!day.inMonth && 'bg-canvas/70', day.isWeekend && day.inMonth && 'bg-[#fdfcfb]']"
                         @click="openDate(day.key)">
                        <div class="flex items-center justify-between">
                            <span class="flex h-6 min-w-6 items-center justify-center rounded-full px-1 font-mono text-xs"
                                  :class="day.isToday ? 'grad-blue font-semibold text-white' : (day.inMonth ? 'text-ink' : 'text-slate-300')"
                                  x-text="day.day"></span>
                            <span x-show="day.items.length" class="rounded-full bg-hover px-1.5 font-mono text-[10px] text-muted sm:hidden" x-text="day.items.length"></span>
                        </div>
                        {{-- Chip agenda (desktop) --}}
                        <div class="hidden min-w-0 flex-col gap-0.5 sm:flex">
                            <template x-for="(item, i) in day.items.slice(0, 3)" :key="i">
                                <button type="button" class="flex min-w-0 items-center gap-1 rounded px-1.5 py-0.5 text-left text-[11px] leading-4"
                                        :class="chipClass(item.priority)" :title="item.time + ' · ' + item.title + ' · ' + item.location"
                                        @click.stop="openDate(day.key)">
                                    <span class="flex-none font-mono opacity-80" x-text="startTime(item)"></span>
                                    <span class="truncate font-medium" x-text="item.title"></span>
                                </button>
                            </template>
                            <span x-show="day.items.length > 3" class="px-1.5 text-[11px] font-medium text-muted" x-text="'+' + (day.items.length - 3) + ' lainnya'"></span>
                        </div>
                        {{-- Titik agenda (HP) --}}
                        <div class="flex flex-wrap gap-0.5 sm:hidden">
                            <template x-for="(item, i) in day.items.slice(0, 4)" :key="i">
                                <span class="h-1.5 w-1.5 rounded-full" :class="{ tinggi: 'bg-[#e3685a]', sedang: 'bg-[#e29f3c]' }[item.priority] || 'bg-[#5a7ef1]'"></span>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-line px-4 py-2 text-[11px] text-muted sm:px-5" style="padding-bottom: max(0.5rem, env(safe-area-inset-bottom))">
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#e3685a]"></span>Prioritas tinggi</span>
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#e29f3c]"></span>Sedang</span>
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#5a7ef1]"></span>Biasa</span>
                <span class="ml-auto">Klik tanggal untuk melihat detail agenda</span>
            </div>
        </div>
    </div>

    {{-- Detail tanggal --}}
    <div x-cloak x-show="calendarModal" x-transition.opacity class="fixed inset-0 z-[80] flex items-end justify-center bg-black/25 p-0 sm:items-center sm:p-4" @click.self="calendarModal = false">
        <div class="max-h-[85vh] w-full max-w-2xl overflow-y-auto rounded-t-2xl border border-line bg-white p-5 shadow-[0_20px_50px_rgba(28,27,25,0.18)] sm:rounded-xl" role="dialog" aria-modal="true" aria-labelledby="calendar-modal-title">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs text-muted">Agenda {{ $leaderLabel }}</p>
                    <h3 id="calendar-modal-title" class="mt-0.5 text-lg font-semibold" x-text="selectedLabel"></h3>
                </div>
                <button type="button" class="flex h-8 w-8 items-center justify-center rounded-md text-muted hover:bg-hover hover:text-ink" @click="calendarModal = false" aria-label="Tutup">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <div class="mt-4 border-t border-line">
                <template x-if="selectedItems.length === 0">
                    <p class="py-6 text-sm text-muted">Tidak ada agenda pada tanggal ini.</p>
                </template>

                <template x-for="(item, index) in selectedItems" :key="index">
                    <div class="agenda-row">
                        <time class="tabular" x-text="item.time.split(' - ')[0]"></time>
                        <div class="min-w-0">
                            <p class="font-medium" x-text="item.title"></p>
                            <p class="mt-0.5 text-[13px] text-muted" x-text="item.location + ' · ' + item.type"></p>
                        </div>
                        <div class="col-start-2 flex flex-wrap items-center gap-1.5 sm:col-start-auto sm:justify-end">
                            <span class="priority-chip" :class="'priority-' + item.priority" x-text="item.priorityLabel"></span>
                            <a :href="'/agendas?search=' + encodeURIComponent(item.title)" class="text-[13px] font-medium text-[#2f55d4] hover:underline">Buka</a>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection
