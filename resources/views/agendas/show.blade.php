@extends('layouts.app', ['title' => 'AGPIM • Detail Agenda'])

@section('content')
<div class="grid gap-4 md:gap-6 xl:grid-cols-[1fr_0.75fr]">
    <x-ui.panel>
        <div class="flex flex-wrap items-center gap-3">
            <span class="priority-chip priority-{{ $agenda->priority }}">{{ config('agpim.priorities')[$agenda->priority]['label'] }}</span>
            <x-ui.status-pill :status="$agenda->status" :text="config('agpim.statuses')[$agenda->status] ?? $agenda->status" />
        </div>
        <h2 class="mt-4 text-2xl font-semibold text-slate-900 md:text-3xl">{{ $agenda->title }}</h2>
        <div class="mt-5 grid gap-3 sm:grid-cols-2 md:mt-6 md:gap-4">
            <div class="detail-card"><span>Tanggal</span><strong>{{ $agenda->event_date->translatedFormat('d F Y') }}</strong></div>
            <div class="detail-card"><span>Waktu</span><strong>{{ $agenda->time_range_display }}</strong></div>
            <div class="detail-card"><span>Lokasi</span><strong>{{ $agenda->location }}</strong></div>
            <div class="detail-card"><span>Penyelenggara</span><strong>{{ $agenda->opd->name }}</strong></div>
            <div class="detail-card"><span>Yang Hadir</span><strong>{{ config('agpim.dispositions')[$agenda->leader_target] ?? 'Belum dipilih' }}</strong></div>
            <div class="detail-card"><span>PIC</span><strong>{{ $agenda->person_in_charge ?: '-' }} {{ $agenda->pic_phone ? '• '.$agenda->pic_phone : '' }}</strong></div>
        </div>
        <div class="mt-5 rounded-xl border border-line bg-canvas p-4 text-sm leading-7 text-ink md:mt-6 md:p-5">
            {{ $agenda->description }}
        </div>
    </x-ui.panel>

    <x-ui.panel>
        <x-ui.section-heading eyebrow="Pengajuan" title="Yang terlibat" />
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <div class="detail-card"><span>Pengaju</span><strong>{{ $agenda->submitter?->name ?? '-' }}</strong></div>
            <div class="detail-card"><span>Verifikator</span><strong>{{ $agenda->reviewer?->name ?? '-' }}</strong></div>
        </div>
    </x-ui.panel>

    <section class="space-y-4 md:space-y-6">
        <x-ui.panel>
            <x-ui.section-heading eyebrow="Deteksi Bentrok" />
            <div class="mt-4 space-y-3">
                @forelse ($conflicts as $conflict)
                    <div class="rounded-lg px-3 py-2.5 text-sm text-[#8e2318]" style="background: var(--grad-bad)">
                        <p class="font-medium">Agenda bentrok: {{ $conflict->title }}</p>
                        <p class="mt-0.5 text-[13px]">{{ $conflict->time_range_display }} · {{ $conflict->location }}</p>
                    </div>
                @empty
                    <div class="empty-state">Tidak ada bentrok yang terdeteksi.</div>
                @endforelse
            </div>
        </x-ui.panel>

        <x-ui.panel>
            <x-ui.section-heading eyebrow="Dokumen & Tindak Lanjut" />
            <div class="mt-4 space-y-3 text-sm text-ink">
                <div class="detail-card"><span>Surat Undangan</span><strong>{{ $agenda->invitation_letter_path }}</strong></div>
                <div class="detail-card"><span>Draft Sambutan</span><strong>{{ $agenda->speech_draft_path ?: '-' }}</strong></div>
                <div class="detail-card"><span>Tindak Lanjut</span><strong>{{ $agenda->followUps->count() }} entri</strong></div>
            </div>
        </x-ui.panel>
    </section>
</div>
@endsection
