@extends('layouts.app', ['title' => 'AGPIM • Detail Agenda'])

@section('content')
<div class="grid gap-6 xl:grid-cols-[1fr_0.75fr]">
    <section class="card-panel p-7">
        <div class="flex flex-wrap items-center gap-3">
            <span class="priority-chip priority-{{ $agenda->priority }}">{{ config('agpim.priorities')[$agenda->priority]['label'] }}</span>
            <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs uppercase tracking-[0.2em] text-slate-300">{{ config('agpim.statuses')[$agenda->status] ?? $agenda->status }}</span>
        </div>
        <h2 class="mt-4 text-3xl font-semibold text-white">{{ $agenda->title }}</h2>
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="detail-card"><span>Tanggal</span><strong>{{ $agenda->event_date->translatedFormat('d F Y') }}</strong></div>
            <div class="detail-card"><span>Waktu</span><strong>{{ $agenda->start_time }} - {{ $agenda->end_time }}</strong></div>
            <div class="detail-card"><span>Lokasi</span><strong>{{ $agenda->location }}</strong></div>
            <div class="detail-card"><span>Penyelenggara</span><strong>{{ $agenda->opd->name }}</strong></div>
            <div class="detail-card"><span>Yang Hadir</span><strong>{{ config('agpim.dispositions')[$agenda->leader_target] ?? 'Belum dipilih' }}</strong></div>
            <div class="detail-card"><span>PIC</span><strong>{{ $agenda->person_in_charge ?: '-' }} {{ $agenda->pic_phone ? '• '.$agenda->pic_phone : '' }}</strong></div>
        </div>
        <div class="mt-6 rounded-3xl border border-white/10 bg-slate-900/70 p-5 text-sm leading-7 text-slate-300">
            {{ $agenda->description }}
        </div>
    </section>

    <section class="space-y-6">
        <div class="card-panel p-7">
            <p class="section-label">Deteksi Bentrok</p>
            <div class="mt-4 space-y-3">
                @forelse ($conflicts as $conflict)
                    <div class="rounded-2xl border border-amber-300/20 bg-amber-500/10 p-4 text-sm text-amber-50">
                        <p class="font-semibold">Agenda Bentrok: {{ $conflict->title }}</p>
                        <p class="mt-1 text-amber-100/70">{{ $conflict->start_time }} - {{ $conflict->end_time }} • {{ $conflict->location }}</p>
                    </div>
                @empty
                    <div class="empty-state">Tidak ada bentrok yang terdeteksi.</div>
                @endforelse
            </div>
        </div>

        <div class="card-panel p-7">
            <p class="section-label">Dokumen & Tindak Lanjut</p>
            <div class="mt-4 space-y-3 text-sm text-slate-300">
                <div class="detail-card"><span>Surat Undangan</span><strong>{{ $agenda->invitation_letter_path }}</strong></div>
                <div class="detail-card"><span>Draft Sambutan</span><strong>{{ $agenda->speech_draft_path ?: '-' }}</strong></div>
                <div class="detail-card"><span>Dokumen Pendukung</span><strong>{{ $agenda->documents->count() }} dokumen</strong></div>
                <div class="detail-card"><span>Tindak Lanjut</span><strong>{{ $agenda->followUps->count() }} entri</strong></div>
            </div>
        </div>
    </section>
</div>
@endsection
