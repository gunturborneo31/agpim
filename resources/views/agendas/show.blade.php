@extends('layouts.app', ['title' => 'AGPIM • Detail Agenda'])

@section('content')
@php
    use App\Enums\UserRole;

    $agendaMediaUploaders = $agenda->documents
        ->whereIn('category', ['photo', 'video'])
        ->map->uploader
        ->filter()
        ->unique('id');

    $agendaEditingUploaders = $agenda->documents
        ->whereIn('category', ['berita', 'konten'])
        ->map->uploader
        ->filter()
        ->unique('id');

    $mediaAkContributors = $agendaMediaUploaders->filter(
        fn ($user) => (($user->role instanceof UserRole ? $user->role->value : $user->role) ?? '') !== UserRole::AdminProkopim->value,
    );
    $mediaProkopimContributors = $agendaMediaUploaders->filter(
        fn ($user) => (($user->role instanceof UserRole ? $user->role->value : $user->role) ?? '') === UserRole::AdminProkopim->value,
    );
    $editingAkContributors = $agendaEditingUploaders->filter(
        fn ($user) => (($user->role instanceof UserRole ? $user->role->value : $user->role) ?? '') !== UserRole::AdminProkopim->value,
    );
    $editingProkopimContributors = $agendaEditingUploaders->filter(
        fn ($user) => (($user->role instanceof UserRole ? $user->role->value : $user->role) ?? '') === UserRole::AdminProkopim->value,
    );

    $prokopimStatusClasses = [
        'belum ada bahan' => 'border-slate-300 bg-slate-50 text-slate-900',
        'sudah ada bahan dokumentasi' => 'border-emerald-300 bg-emerald-50 text-emerald-900',
        'belum diperiksa' => 'border-blue-300 bg-blue-50 text-blue-900',
        'perlu revisi' => 'border-amber-300 bg-amber-50 text-amber-900',
        'ok tayang' => 'border-emerald-300 bg-emerald-100 text-emerald-900',
    ];

    $visualProkopimStatus = $agenda->visual_status_prokopim ?: 'belum ada bahan';
    $videoProkopimStatus = $agenda->video_status_prokopim ?: 'belum ada bahan';
@endphp

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
        <div class="mt-5 rounded-3xl border border-slate-200 bg-slate-50 p-4 text-sm leading-7 text-slate-700 md:mt-6 md:p-5">
            {{ $agenda->description }}
        </div>
    </x-ui.panel>

    <x-ui.panel>
        <x-ui.section-heading eyebrow="Kontributor" title="Daftar tim yang terlibat" />
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <div class="detail-card"><span>Pengaju</span><strong>{{ $agenda->submitter?->name ?? '-' }}</strong></div>
            <div class="detail-card"><span>Verifikator</span><strong>{{ $agenda->reviewer?->name ?? '-' }}</strong></div>
            <div class="detail-card">
                <span>Kontributor Media AK</span>
                <div class="mt-2 space-y-1 text-sm text-slate-700">
                    @forelse ($mediaAkContributors as $user)
                        <div>{{ $user->name }} <span class="text-slate-500">({{ $user->role->label() }})</span></div>
                    @empty
                        <div class="text-slate-400">Tidak ada</div>
                    @endforelse
                </div>
            </div>
            <div class="detail-card">
                <span>Kontributor Media Prokopim</span>
                <div class="mt-2 space-y-1 text-sm text-slate-700">
                    @forelse ($mediaProkopimContributors as $user)
                        <div>{{ $user->name }} <span class="text-slate-500">({{ $user->role->label() }})</span></div>
                    @empty
                        <div class="text-slate-400">Tidak ada</div>
                    @endforelse
                </div>
            </div>
            <div class="detail-card">
                <span>Editor / Drafter AK</span>
                <div class="mt-2 space-y-1 text-sm text-slate-700">
                    @forelse ($editingAkContributors as $user)
                        <div>{{ $user->name }} <span class="text-slate-500">({{ $user->role->label() }})</span></div>
                    @empty
                        <div class="text-slate-400">Tidak ada</div>
                    @endforelse
                </div>
            </div>
            <div class="detail-card">
                <span>Editor / Drafter Prokopim</span>
                <div class="mt-2 space-y-1 text-sm text-slate-700">
                    @forelse ($editingProkopimContributors as $user)
                        <div>{{ $user->name }} <span class="text-slate-500">({{ $user->role->label() }})</span></div>
                    @empty
                        <div class="text-slate-400">Tidak ada</div>
                    @endforelse
                </div>
            </div>
        </div>
    </x-ui.panel>

    <section class="space-y-4 md:space-y-6">
        <x-ui.panel>
            <x-ui.section-heading eyebrow="Deteksi Bentrok" />
            <div class="mt-4 space-y-3">
                @forelse ($conflicts as $conflict)
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        <p class="font-semibold">Agenda Bentrok: {{ $conflict->title }}</p>
                        <p class="mt-1 text-red-600">{{ $conflict->time_range_display }} • {{ $conflict->location }}</p>
                    </div>
                @empty
                    <div class="empty-state">Tidak ada bentrok yang terdeteksi.</div>
                @endforelse
            </div>
            @if (auth()->check() && auth()->user()->hasRole('admin_prokopim', 'super_admin'))
                <div class="mt-4 border-t pt-4">
                    <x-ui.section-heading eyebrow="Prokopim" title="Status Prokopim" />
                    <form method="POST" action="{{ route('agendas.prokopim.update', $agenda) }}" class="mt-3">
                        @csrf
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="form-field">
                                <span>Status Visual (Prokopim)</span>
                                <div class="mt-2">
                                    <span class="inline-flex rounded-full {{ $prokopimStatusClasses[$visualProkopimStatus] ?? 'border-slate-300 bg-slate-50 text-slate-900' }} px-3 py-1 text-xs font-semibold">{{ $agenda->visual_status_prokopim ?: 'Belum ada bahan' }}</span>
                                </div>
                                <select name="visual_status_prokopim" required class="mt-2 w-full rounded-xl border {{ $prokopimStatusClasses[$visualProkopimStatus] ?? 'border-slate-300' }} bg-white px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                    <option value="">Pilih status</option>
                                    <option value="belum ada bahan" @selected($agenda->visual_status_prokopim === 'belum ada bahan')>Belum ada bahan</option>
                                    <option value="sudah ada bahan dokumentasi" @selected($agenda->visual_status_prokopim === 'sudah ada bahan dokumentasi')>Sudah ada bahan dokumentasi</option>
                                    <option value="belum diperiksa" @selected($agenda->visual_status_prokopim === 'belum diperiksa')>Belum diperiksa</option>
                                    <option value="perlu revisi" @selected($agenda->visual_status_prokopim === 'perlu revisi')>Perlu revisi</option>
                                    <option value="ok tayang" @selected($agenda->visual_status_prokopim === 'ok tayang')>Ok tayang</option>
                                </select>
                            </label>
                            <label class="form-field">
                                <span>Status Video (Prokopim)</span>
                                <div class="mt-2">
                                    <span class="inline-flex rounded-full {{ $prokopimStatusClasses[$videoProkopimStatus] ?? 'border-slate-300 bg-slate-50 text-slate-900' }} px-3 py-1 text-xs font-semibold">{{ $agenda->video_status_prokopim ?: 'Belum ada bahan' }}</span>
                                </div>
                                <select name="video_status_prokopim" required class="mt-2 w-full rounded-xl border {{ $prokopimStatusClasses[$videoProkopimStatus] ?? 'border-slate-300' }} bg-white px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                    <option value="">Pilih status</option>
                                    <option value="belum ada bahan" @selected($agenda->video_status_prokopim === 'belum ada bahan')>Belum ada bahan</option>
                                    <option value="sudah ada bahan dokumentasi" @selected($agenda->video_status_prokopim === 'sudah ada bahan dokumentasi')>Sudah ada bahan dokumentasi</option>
                                    <option value="belum diperiksa" @selected($agenda->video_status_prokopim === 'belum diperiksa')>Belum diperiksa</option>
                                    <option value="perlu revisi" @selected($agenda->video_status_prokopim === 'perlu revisi')>Perlu revisi</option>
                                    <option value="ok tayang" @selected($agenda->video_status_prokopim === 'ok tayang')>Ok tayang</option>
                                </select>
                            </label>
                        </div>
                        <div class="mt-3 flex justify-end">
                            <button type="submit" class="btn-primary">Simpan Status Prokopim</button>
                        </div>
                    </form>
                </div>
            @endif
        </x-ui.panel>

        <x-ui.panel>
            <x-ui.section-heading eyebrow="Dokumen & Tindak Lanjut" />
            <div class="mt-4 space-y-3 text-sm text-slate-700">
                <div class="detail-card"><span>Surat Undangan</span><strong>{{ $agenda->invitation_letter_path }}</strong></div>
                <div class="detail-card"><span>Draft Sambutan</span><strong>{{ $agenda->speech_draft_path ?: '-' }}</strong></div>
                <div class="detail-card"><span>Dokumen Pendukung</span><strong>{{ $agenda->documents->count() }} dokumen</strong></div>
                <div class="detail-card"><span>Tindak Lanjut</span><strong>{{ $agenda->followUps->count() }} entri</strong></div>
            </div>
        </x-ui.panel>
    </section>
</div>
@endsection
