@extends('layouts.app', ['title' => 'AGPIM • Dashboard'])

@section('content')
<div class="mb-6 overflow-x-auto md:mb-8">
    <div class="flex min-w-max gap-3 pb-2">
    @foreach ($leaders as $key => $label)
        <a href="{{ route('dashboard', ['leader' => $key]) }}" class="{{ $leader === $key ? 'bg-blue-700 text-white shadow-[0_10px_24px_rgba(0,76,202,0.22)]' : 'bg-slate-200/70 text-slate-600 hover:bg-slate-200' }} inline-flex rounded-full px-8 py-3 text-sm font-semibold transition">{{ $label }}</a>
    @endforeach
    </div>
</div>

<div x-data="{
    calendarModal: false,
    selectedDate: null,
    selectedItems: [],
    selectedLabel: '',
    openDate(date, label) {
        const items = (this.calendarMap[date] || []).slice();
        this.selectedDate = date;
        this.selectedLabel = label;
        this.selectedItems = items;
        this.calendarModal = true;
    },
    calendarMap: @js($calendarAgendaMap),
}" @keydown.escape.window="calendarModal = false" class="grid grid-cols-1 gap-6 md:gap-8 lg:grid-cols-12">
    <div class="space-y-6 md:space-y-8 lg:col-span-8">
        <x-dashboard.featured-next-agenda :next-agenda="$nextAgenda" :leader-label="$leaderLabel" />

        <section>
            <div class="flex items-center justify-between">
                <h3 class="flex items-center gap-2 text-xl font-bold text-slate-900">
                    <span class="material-symbols-outlined text-blue-700">event_note</span>
                    Timeline Hari Ini
                </h3>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Pusat dashboard pimpinan</p>
            </div>
            <div class="mt-4 space-y-3 md:mt-5 md:space-y-4">
                @forelse ($timeline as $agenda)
                    <x-dashboard.timeline-item
                        :time="$agenda->start_time_display"
                        :title="$agenda->title"
                        :meta="$agenda->location.' • '.($agenda->opd->alias ?? $agenda->opd->name)"
                        :priority="$agenda->priority"
                        :priority-label="config('agpim.priorities')[$agenda->priority]['label']"
                    />
                @empty
                    <div class="empty-state">Belum ada agenda hari ini.</div>
                @endforelse
            </div>
        </section>

        <section class="card-panel p-5 md:p-7">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="section-label flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-slate-500">calendar_month</span>
                        Kalender Agenda
                    </p>
                    <h3 class="mt-2 text-xl font-bold text-slate-900">Agenda per tanggal</h3>
                    <p class="mt-1 text-sm text-slate-500">Klik tanggal untuk melihat detail agenda pada hari tersebut.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('dashboard', ['leader' => $leader, 'calendar_month' => $calendarMonth->copy()->subMonthNoOverflow()->month, 'calendar_year' => $calendarMonth->copy()->subMonthNoOverflow()->year]) }}" class="inline-flex h-10 items-center gap-2 rounded-full border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:text-blue-700">
                        <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                        Sebelumnya
                    </a>
                    <div class="rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700">
                        {{ $calendarMonthLabel }}
                    </div>
                    <a href="{{ route('dashboard', ['leader' => $leader, 'calendar_month' => $calendarMonth->copy()->addMonthNoOverflow()->month, 'calendar_year' => $calendarMonth->copy()->addMonthNoOverflow()->year]) }}" class="inline-flex h-10 items-center gap-2 rounded-full border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:text-blue-700">
                        Berikutnya
                        <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                    </a>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                <div>
                    <span class="font-semibold text-slate-900">Bulan aktif:</span> {{ $calendarMonthLabel }}
                </div>
                <div class="font-semibold text-slate-700">Tahun {{ $calendarMonth->year }}</div>
                <div class="rounded-full bg-blue-700 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-white">
                    {{ $calendarDays->where('count', '>', 0)->count() }} tanggal aktif
                </div>
            </div>

            <div class="mt-5 grid grid-cols-7 gap-2 text-center text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 md:mt-6">
                @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $dayLabel)
                    <div class="py-2">{{ $dayLabel }}</div>
                @endforeach
            </div>

            <div class="mt-2 grid grid-cols-7 gap-2 md:mt-3">
                @foreach ($calendarDays as $day)
                    <button type="button"
                        class="group min-h-[88px] rounded-2xl border p-3 text-left transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_12px_28px_rgba(0,76,202,0.12)] {{ $day['isToday'] ? 'border-blue-700 bg-blue-700 text-white shadow-[0_10px_24px_rgba(0,76,202,0.2)] ring-2 ring-blue-200' : ($day['count'] > 0 ? 'border-emerald-200 bg-emerald-100 text-emerald-950 shadow-[0_10px_24px_rgba(16,185,129,0.12)]' : ($day['isCurrentMonth'] ? 'border-slate-200 bg-white text-slate-900' : 'border-slate-100 bg-slate-50 text-slate-400')) }}"
                        @click="openDate('{{ $day['date'] }}', '{{ \Illuminate\Support\Carbon::parse($day['date'])->translatedFormat('d F Y') }}')">
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-sm font-bold">{{ $day['day'] }}</span>
                            @if ($day['count'] > 0)
                                <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full {{ $day['isToday'] ? 'bg-white/15 text-white' : 'bg-emerald-600 text-white' }} px-2 text-[11px] font-bold shadow-[0_8px_18px_rgba(0,76,202,0.25)] transition group-hover:scale-105">
                                    {{ $day['count'] }}
                                </span>
                            @endif
                        </div>
                        @if ($day['count'] > 0)
                            <p class="mt-6 text-[11px] font-semibold uppercase tracking-[0.18em] {{ $day['isToday'] ? 'text-white/90' : 'text-emerald-800' }}">{{ $day['count'] }} agenda</p>
                        @else
                            <p class="mt-6 text-[11px] text-slate-400">Tidak ada agenda</p>
                        @endif
                    </button>
                @endforeach
            </div>
        </section>

        <section class="card-panel p-5 md:p-7" x-data="{ tab: 'Hari Ini' }">
            <div class="flex flex-wrap gap-2">
                @foreach ($quickTabs as $label => $items)
                    <button class="tab-chip" :class="tab === '{{ $label }}' ? 'tab-chip-active' : ''" @click="tab = '{{ $label }}'">{{ $label }}</button>
                @endforeach
            </div>
            <div class="mt-4 space-y-3 md:mt-5">
                @foreach ($quickTabs as $label => $items)
                    <div x-show="tab === '{{ $label }}'" x-cloak>
                        @forelse ($items as $item)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ $item->title }}</p>
                                        <p class="text-slate-500">{{ $item->event_date->translatedFormat('d M Y') }} • {{ $item->start_time_display }} • {{ $item->location }}</p>
                                    </div>
                                    <span class="text-xs uppercase tracking-[0.2em] text-blue-700">{{ $item->type->name }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">Tidak ada agenda pada tab {{ $label }}.</div>
                        @endforelse
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="space-y-6 md:space-y-8 lg:col-span-4">
        <section class="grid gap-4 sm:grid-cols-2">
            <x-ui.metric-card label="Total agenda hari ini" :value="$summary['today']" />
            <x-ui.metric-card label="Prioritas tinggi" :value="$summary['highPriority']" />
            <x-ui.metric-card label="Menunggu keputusan" :value="$summary['pendingDecision']" />
            <x-ui.metric-card label="Agenda selesai" :value="$summary['completed']" />
        </section>

        <section class="rounded-3xl border border-orange-200 bg-orange-50/70 p-5 md:p-7">
            <p class="section-label flex items-center gap-2 text-orange-700">
                <span class="material-symbols-outlined text-[18px]">hourglass_empty</span>
                Agenda Menunggu Keputusan
            </p>
            <div class="mt-4 space-y-3">
                @forelse ($pending as $agenda)
                    <x-dashboard.mini-agenda-card
                        tone="warning"
                        :title="$agenda->title"
                        :meta="$agenda->event_date->translatedFormat('d M Y').' • '.$agenda->location"
                    />
                @empty
                    <div class="empty-state rounded-2xl border border-orange-200 bg-white px-4 py-3">Tidak ada agenda yang menunggu disposisi.</div>
                @endforelse
            </div>
        </section>

        <section class="rounded-3xl border border-red-200 bg-red-50/70 p-5 md:p-7">
            <p class="section-label flex items-center gap-2 text-red-700">
                <span class="material-symbols-outlined text-[18px]">bolt</span>
                Prioritas Tinggi
            </p>
            <div class="mt-4 space-y-3">
                @forelse ($priorities as $agenda)
                    <x-dashboard.mini-agenda-card
                        tone="danger"
                        :title="$agenda->title"
                        :meta="$agenda->event_date->translatedFormat('d M Y').' • '.$agenda->start_time_display.' • '.$agenda->location"
                    />
                @empty
                    <div class="empty-state rounded-2xl border border-red-200 bg-white px-4 py-3">Belum ada prioritas tinggi.</div>
                @endforelse
            </div>
        </section>

        <section class="card-panel p-5 md:p-7">
            <p class="section-label flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] text-slate-500">warning</span>
                Agenda Bentrok
            </p>
            <div class="mt-4 space-y-3">
                @forelse ($conflicts as $conflict)
                    <x-dashboard.conflict-card
                        :title="$conflict['a']->title.' ↔ '.$conflict['b']->title"
                        :window="$conflict['window']"
                        :recommendation="$conflict['recommendation']"
                    />
                @empty
                    <div class="empty-state">Tidak ada bentrok agenda terdeteksi hari ini.</div>
                @endforelse
            </div>
        </section>
    </div>

    <div x-cloak x-show="calendarModal" class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/45 p-4" @click.self="calendarModal = false">
        <div class="card-panel max-h-[90vh] w-full max-w-4xl overflow-y-auto p-5 md:p-7">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="section-label flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-slate-500">event</span>
                        Detail Tanggal
                    </p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-900" x-text="selectedLabel"></h3>
                    <p class="mt-1 text-sm text-slate-500" x-text="selectedDate"></p>
                </div>
                <button type="button" class="rounded-full border border-slate-200 p-2 text-slate-500 hover:text-slate-700" @click="calendarModal = false">
                    <span class="material-symbols-outlined text-[20px] leading-none">close</span>
                </button>
            </div>

            <div class="mt-5 space-y-3 md:mt-6">
                <template x-if="selectedItems.length === 0">
                    <div class="empty-state">Tidak ada agenda pada tanggal ini.</div>
                </template>

                <template x-for="(item, index) in selectedItems" :key="index">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="priority-chip" :class="'priority-' + item.priority" x-text="item.priorityLabel"></span>
                                </div>
                                <h4 class="mt-3 text-lg font-semibold text-slate-900" x-text="item.title"></h4>
                                <p class="mt-2 text-sm text-slate-500" x-text="item.time + ' • ' + item.location"></p>
                                <p class="mt-1 text-sm text-slate-600" x-text="item.type"></p>
                            </div>
                            <a :href="'/agendas?search=' + encodeURIComponent(item.title)" class="btn-secondary text-xs">Lihat Agenda</a>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection
