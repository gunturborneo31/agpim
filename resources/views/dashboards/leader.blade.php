@extends('layouts.app', ['title' => 'AGPIM • Dashboard'])

@section('content')
<div class="mb-6 flex flex-wrap gap-3">
    @foreach ($leaders as $key => $label)
        <a href="{{ route('dashboard', ['leader' => $key]) }}" class="{{ $leader === $key ? 'btn-primary' : 'btn-secondary' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
    <div class="space-y-6">
        <section class="card-panel p-7">
            <p class="section-label">Agenda Berikutnya</p>
            @if ($nextAgenda)
                <div class="mt-4 rounded-[2rem] bg-gradient-to-r from-sky-600 via-blue-600 to-indigo-600 p-6 shadow-xl shadow-sky-950/30">
                    <p class="text-sm text-white/70">{{ $leaders[$leader] }}</p>
                    <h2 class="mt-2 text-3xl font-semibold text-white">{{ $nextAgenda->title }}</h2>
                    <div class="mt-4 grid gap-3 text-sm text-white/85 sm:grid-cols-3">
                        <div>
                            <p class="text-white/60">Jam</p>
                            <p>{{ \Illuminate\Support\Carbon::parse($nextAgenda->start_time)->format('H:i') }} - {{ \Illuminate\Support\Carbon::parse($nextAgenda->end_time)->format('H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-white/60">Lokasi</p>
                            <p>{{ $nextAgenda->location }}</p>
                        </div>
                        <div>
                            <p class="text-white/60">Countdown</p>
                            <p>{{ now()->diffForHumans($nextAgenda->event_date->setTimeFromTimeString($nextAgenda->start_time), ['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW]) }}</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="empty-state mt-4">Belum ada agenda untuk pimpinan ini.</div>
            @endif
        </section>

        <section class="card-panel p-7">
            <div class="flex items-center justify-between">
                <p class="section-label">Timeline Hari Ini</p>
                <p class="text-sm text-slate-400">Pusat dashboard pimpinan</p>
            </div>
            <div class="mt-6 space-y-4">
                @forelse ($timeline as $agenda)
                    <article class="timeline-item">
                        <div class="timeline-hour">{{ \Illuminate\Support\Carbon::parse($agenda->start_time)->format('H:i') }}</div>
                        <div class="timeline-dot"></div>
                        <div class="timeline-card">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-white">{{ $agenda->title }}</h3>
                                    <p class="text-sm text-slate-400">{{ $agenda->location }} • {{ $agenda->opd->alias ?? $agenda->opd->name }}</p>
                                </div>
                                <span class="priority-chip priority-{{ $agenda->priority }}">{{ config('agpim.priorities')[$agenda->priority]['label'] }}</span>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="empty-state">Belum ada agenda hari ini.</div>
                @endforelse
            </div>
        </section>

        <section class="card-panel p-7" x-data="{ tab: 'Hari Ini' }">
            <div class="flex flex-wrap gap-2">
                @foreach ($quickTabs as $label => $items)
                    <button class="tab-chip" :class="tab === '{{ $label }}' ? 'tab-chip-active' : ''" @click="tab = '{{ $label }}'">{{ $label }}</button>
                @endforeach
            </div>
            <div class="mt-5 space-y-3">
                @foreach ($quickTabs as $label => $items)
                    <div x-show="tab === '{{ $label }}'" x-cloak>
                        @forelse ($items as $item)
                            <div class="rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-slate-200">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="font-medium text-white">{{ $item->title }}</p>
                                        <p class="text-slate-400">{{ $item->event_date->translatedFormat('d M Y') }} • {{ $item->start_time }} • {{ $item->location }}</p>
                                    </div>
                                    <span class="text-xs uppercase tracking-[0.2em] text-sky-200">{{ $item->type->name }}</span>
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

    <div class="space-y-6">
        <section class="grid gap-4 sm:grid-cols-2">
            <div class="metric-card"><span>Total agenda hari ini</span><strong>{{ $summary['today'] }}</strong></div>
            <div class="metric-card"><span>Prioritas tinggi</span><strong>{{ $summary['highPriority'] }}</strong></div>
            <div class="metric-card"><span>Menunggu keputusan</span><strong>{{ $summary['pendingDecision'] }}</strong></div>
            <div class="metric-card"><span>Agenda selesai</span><strong>{{ $summary['completed'] }}</strong></div>
        </section>

        <section class="card-panel border-amber-300/20 bg-amber-400/10 p-7">
            <p class="section-label text-amber-100">Agenda Menunggu Keputusan</p>
            <div class="mt-4 space-y-3">
                @forelse ($pending as $agenda)
                    <div class="rounded-2xl border border-amber-300/20 bg-slate-950/40 px-4 py-3 text-sm text-amber-50">
                        <p class="font-semibold">{{ $agenda->title }}</p>
                        <p class="text-amber-100/70">{{ $agenda->event_date->translatedFormat('d M Y') }} • {{ $agenda->location }}</p>
                    </div>
                @empty
                    <div class="empty-state border-amber-300/20 text-amber-50/70">Tidak ada agenda yang menunggu disposisi.</div>
                @endforelse
            </div>
        </section>

        <section class="card-panel border-rose-300/20 bg-rose-500/10 p-7">
            <p class="section-label text-rose-100">Prioritas Tinggi</p>
            <div class="mt-4 space-y-3">
                @forelse ($priorities as $agenda)
                    <div class="rounded-2xl border border-rose-300/20 bg-slate-950/40 px-4 py-3 text-sm text-rose-50">
                        <p class="font-semibold">{{ $agenda->title }}</p>
                        <p class="text-rose-100/70">{{ $agenda->event_date->translatedFormat('d M Y') }} • {{ $agenda->start_time }} • {{ $agenda->location }}</p>
                    </div>
                @empty
                    <div class="empty-state border-rose-300/20 text-rose-50/70">Belum ada prioritas tinggi.</div>
                @endforelse
            </div>
        </section>

        <section class="card-panel p-7">
            <p class="section-label">Agenda Bentrok</p>
            <div class="mt-4 space-y-3">
                @forelse ($conflicts as $conflict)
                    <div class="rounded-2xl border border-amber-300/20 bg-slate-900/70 px-4 py-4 text-sm text-slate-200">
                        <p class="font-semibold text-amber-100">{{ $conflict['a']->title }} ↔ {{ $conflict['b']->title }}</p>
                        <p class="mt-1 text-slate-400">Waktu bentrok: {{ $conflict['window'] }}</p>
                        <p class="mt-2 text-slate-300">{{ $conflict['recommendation'] }}</p>
                    </div>
                @empty
                    <div class="empty-state">Tidak ada bentrok agenda terdeteksi hari ini.</div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
