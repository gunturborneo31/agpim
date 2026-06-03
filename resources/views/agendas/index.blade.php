@extends('layouts.app', ['title' => 'AGPIM • Pengajuan Agenda'])

@section('content')
<div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
    <section class="card-panel p-7">
        <p class="section-label">Fondasi Form Pengajuan</p>
        <h2 class="mt-3 text-2xl font-semibold text-white">Struktur form sederhana untuk OPD</h2>
        <form method="POST" action="{{ route('agendas.store') }}" class="mt-6 space-y-4 opacity-75">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="form-field">
                    <span>Nama kegiatan</span>
                    <input type="text" placeholder="Rapat Infrastruktur" disabled>
                </label>
                <label class="form-field">
                    <span>Jenis kegiatan</span>
                    <select disabled>
                        @foreach ($agendaTypes as $agendaType)
                            <option>{{ $agendaType->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="form-field">
                    <span>Tanggal kegiatan</span>
                    <input type="date" disabled>
                </label>
                <label class="form-field">
                    <span>Lokasi</span>
                    <input type="text" placeholder="Ruang Rapat Utama" disabled>
                </label>
            </div>
            <div class="rounded-2xl border border-sky-300/20 bg-sky-500/10 p-4 text-sm text-sky-100">
                Endpoint penyimpanan sudah disiapkan di <code>POST /agendas</code> dengan request validation, policy, event, audit trail, dan notifikasi internal. Gunakan autentikasi Laravel pada tahap berikutnya untuk aktivasi penuh.
            </div>
        </form>
    </section>

    <section class="card-panel p-7">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="section-label">Daftar Agenda</p>
                <h2 class="mt-2 text-2xl font-semibold text-white">Ringkasan pengajuan dan status</h2>
            </div>
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm text-slate-300">{{ $agendas->count() }} agenda</span>
        </div>
        <div class="mt-6 grid gap-4">
            @forelse ($agendas as $agenda)
                <a href="{{ route('agendas.show', $agenda) }}" class="rounded-[1.75rem] border border-white/10 bg-slate-900/70 p-5 transition hover:-translate-y-0.5 hover:border-sky-300/30 hover:bg-slate-900">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="priority-chip priority-{{ $agenda->priority }}">{{ config('agpim.priorities')[$agenda->priority]['label'] }}</span>
                                <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs uppercase tracking-[0.2em] text-slate-300">{{ config('agpim.statuses')[$agenda->status] ?? $agenda->status }}</span>
                            </div>
                            <h3 class="mt-3 text-xl font-semibold text-white">{{ $agenda->title }}</h3>
                            <p class="mt-2 text-sm text-slate-400">{{ $agenda->event_date->translatedFormat('d M Y') }} • {{ $agenda->start_time }} - {{ $agenda->end_time }} • {{ $agenda->location }}</p>
                            <p class="mt-2 text-sm text-slate-300">{{ $agenda->opd->name }} • {{ $agenda->type->name }} • Target {{ config('agpim.dispositions')[$agenda->leader_target] ?? 'Belum ditentukan' }}</p>
                        </div>
                        <div class="text-right text-sm text-slate-400">
                            <p>Pengaju</p>
                            <p class="font-medium text-slate-200">{{ $agenda->submitter->name }}</p>
                        </div>
                    </div>
                </a>
            @empty
                <div class="empty-state">Belum ada agenda. Jalankan seeder demo untuk melihat data contoh.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
