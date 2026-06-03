@extends('layouts.app', ['title' => 'AGPIM • Agenda Internal'])

@section('content')
<section class="card-panel p-7">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="section-label">Agenda Publik Internal</p>
            <h2 class="mt-2 text-2xl font-semibold text-white">Agenda yang boleh dilihat seluruh OPD</h2>
        </div>
        <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm text-slate-300">Tanpa dokumen internal</span>
    </div>
    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        @forelse ($agendas as $agenda)
            <article class="rounded-[1.75rem] border border-white/10 bg-slate-900/70 p-5">
                <p class="text-xs uppercase tracking-[0.2em] text-sky-200">{{ $agenda->type->name }}</p>
                <h3 class="mt-2 text-xl font-semibold text-white">{{ $agenda->title }}</h3>
                <p class="mt-3 text-sm text-slate-400">{{ $agenda->event_date->translatedFormat('d M Y') }} • {{ $agenda->start_time }} - {{ $agenda->end_time }}</p>
                <p class="mt-2 text-sm text-slate-300">{{ $agenda->location }} • {{ $agenda->opd->name }}</p>
                <p class="mt-2 text-sm text-slate-300">Yang menghadiri: {{ config('agpim.dispositions')[$agenda->leader_target] ?? 'Belum ditentukan' }}</p>
            </article>
        @empty
            <div class="empty-state lg:col-span-2">Belum ada agenda internal yang dipublikasikan.</div>
        @endforelse
    </div>
</section>
@endsection
