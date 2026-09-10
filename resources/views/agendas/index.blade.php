@extends('layouts.app', ['title' => 'AGPIM • Pengajuan Agenda'])

@section('content')
@php
    $isVerificationMode = $isVerificationMode ?? (auth()->check() && ! auth()->user()->hasRole('opd'));
    $canSubmitAgenda = auth()->check() && auth()->user()->hasRole('opd');

    $resolveStorageUrl = static function (?string $path): ?string {
        if (! $path) {
            return null;
        }

        return asset('storage/'.$path);
    };

    $currentSort = $filters['sort'] ?? 'latest';
    $sortUrl = static function (string $sortKey) use ($filters): string {
        return route('agendas.index', array_merge($filters, ['sort' => $sortKey]));
    };

    $prokopimStatusClasses = [
        '-' => 'border-rose-500 bg-rose-200 text-rose-900',
        'belum ada bahan' => 'border-slate-500 bg-slate-200 text-slate-900',
        'sudah ada bahan dokumentasi' => 'border-emerald-500 bg-emerald-200 text-emerald-900',
        'belum diperiksa' => 'border-blue-500 bg-blue-200 text-blue-900',
        'perlu revisi' => 'border-amber-500 bg-amber-200 text-amber-900',
        'ok tayang' => 'border-emerald-500 bg-emerald-200 text-emerald-900',
    ];

    $akStatusClasses = [
        '-' => 'border-rose-500 bg-rose-200 text-rose-900',
        'Belum' => 'border-rose-500 bg-rose-200 text-rose-900',
        'menunggu' => 'border-amber-500 bg-amber-200 text-amber-900',
        'sedang dikerjakan' => 'border-sky-500 bg-sky-200 text-sky-900',
        'selesai' => 'border-emerald-500 bg-emerald-200 text-emerald-900',
    ];

@endphp
<div x-data="{ modal: @js(old('modal', '')), selectedDocIndex: 0, selectedAgendaId: null, mediaModalUrl: '', mediaModalTitle: '', showFilters: false }" x-init="$watch('modal', value => document.body.classList.toggle('popup-open', Boolean(value)))" @keydown.escape.window="modal = ''; mediaModalUrl = ''; mediaModalTitle = ''" class="space-y-4 md:space-y-6">
    <x-ui.panel>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <x-ui.section-heading eyebrow="Agenda" :title="$isVerificationMode ? 'Verifikasi agenda masuk' : 'Daftar pengajuan agenda'" subtitle="Tampilan tabel dengan pencarian, filter, pagination, dan aksi cepat." />
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <span class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-600">{{ $agendas->total() }} agenda</span>
                <button type="button" class="btn-secondary" @click="showFilters = !showFilters" aria-controls="agenda-filter" :aria-pressed="showFilters" :title="showFilters ? 'Sembunyikan Filter' : 'Tampilkan Filter'">
                    <span x-text="showFilters ? 'Sembunyikan Filter' : 'Tampilkan Filter'"></span>
                </button>
                @if (! $isVerificationMode && $canSubmitAgenda)
                    <button type="button" class="btn-primary" @click="modal = 'create'">Tambah Agenda</button>
                @endif
                @if (auth()->check() && ! auth()->user()->hasRole('opd'))
                    <button type="button" class="btn-secondary" @click="modal = 'admin-create'">Tambah Agenda Langsung Setujui</button>
                @endif
            </div>
        </div>

        @if ($isVerificationMode)
            <div class="mt-5 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700">
                Role selain OPD melihat seluruh agenda dari pengaju OPD. Tombol verifikasi hanya tampil untuk status <strong>Diajukan</strong>.
            </div>
        @endif

        <form id="agenda-filter" x-cloak x-show="showFilters" x-transition x-ref="agendaFilterForm" method="GET" action="{{ route('agendas.index') }}" class="mt-4 grid gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-3 md:mt-5 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <label class="form-field xl:col-span-2">
                <span>Cari agenda</span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Judul, lokasi, OPD, jenis, atau pengaju" @input.debounce.400ms="$refs.agendaFilterForm.requestSubmit()">
            </label>
            <label class="form-field">
                <span>OPD</span>
                <select name="opd_id" @change="$refs.agendaFilterForm.requestSubmit()">
                    <option value="">Semua OPD</option>
                    @foreach (($opdOptions ?? collect()) as $opdOption)
                        <option value="{{ $opdOption->id }}" @selected(($filters['opd_id'] ?? '') === (string) $opdOption->id)>{{ $opdOption->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-field">
                <span>Status</span>
                <select name="status" @change="$refs.agendaFilterForm.requestSubmit()">
                    <option value="">Semua status</option>
                    @foreach (config('agpim.statuses') as $statusKey => $statusLabel)
                        <option value="{{ $statusKey }}" @selected(($filters['status'] ?? '') === $statusKey)>{{ $statusLabel }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-field">
                <span>Prioritas</span>
                <select name="priority" @change="$refs.agendaFilterForm.requestSubmit()">
                    <option value="">Semua prioritas</option>
                    @foreach (config('agpim.priorities') as $priorityKey => $priority)
                        <option value="{{ $priorityKey }}" @selected(($filters['priority'] ?? '') === $priorityKey)>{{ $priority['label'] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-field">
                <span>Tanggal Awal</span>
                <input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}" @change="$refs.agendaFilterForm.requestSubmit()">
            </label>
            <label class="form-field">
                <span>Tanggal Akhir</span>
                <input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}" @change="$refs.agendaFilterForm.requestSubmit()">
            </label>
            <label class="form-field">
                <span>Sort By</span>
                <select name="sort" @change="$refs.agendaFilterForm.requestSubmit()">
                    <option value="latest" @selected(($filters['sort'] ?? 'latest') === 'latest')>Terbaru</option>
                    <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>Terlama</option>
                    <option value="title_asc" @selected(($filters['sort'] ?? '') === 'title_asc')>Judul A-Z</option>
                    <option value="title_desc" @selected(($filters['sort'] ?? '') === 'title_desc')>Judul Z-A</option>
                    <option value="opd_asc" @selected(($filters['sort'] ?? '') === 'opd_asc')>OPD A-Z</option>
                    <option value="opd_desc" @selected(($filters['sort'] ?? '') === 'opd_desc')>OPD Z-A</option>
                    <option value="type_asc" @selected(($filters['sort'] ?? '') === 'type_asc')>Jenis A-Z</option>
                    <option value="type_desc" @selected(($filters['sort'] ?? '') === 'type_desc')>Jenis Z-A</option>
                    <option value="priority_high" @selected(($filters['sort'] ?? '') === 'priority_high')>Prioritas Tertinggi</option>
                    <option value="priority_low" @selected(($filters['sort'] ?? '') === 'priority_low')>Prioritas Terendah</option>
                    <option value="status_asc" @selected(($filters['sort'] ?? '') === 'status_asc')>Status Awal</option>
                    <option value="status_desc" @selected(($filters['sort'] ?? '') === 'status_desc')>Status Akhir</option>
                </select>
            </label>
            <div class="flex items-end justify-end xl:col-span-4">
                @if (($filters['search'] ?? '') !== '' || ($filters['status'] ?? '') !== '' || ($filters['priority'] ?? '') !== '' || ($filters['opd_id'] ?? '') !== '' || ($filters['start_date'] ?? '') !== '' || ($filters['end_date'] ?? '') !== '' || (($filters['sort'] ?? 'latest') !== 'latest'))
                    <a href="{{ route('agendas.index') }}" class="btn-secondary w-full sm:w-auto">Reset</a>
                @else
                    <!-- <span class="text-xs text-slate-400">Filter otomatis aktif</span> -->
                @endif
            </div>
        </form>

        <div class="mt-3 mb-2 flex justify-end w-full">
            <div class="flex items-center gap-2">
                <button id="font-decrease" type="button" class="rounded-md border border-slate-200 bg-white px-2 py-1 text-sm text-slate-700 hover:bg-slate-50" aria-label="Perkecil font">-</button>
                <button id="font-increase" type="button" class="rounded-md border border-slate-200 bg-white px-2 py-1 text-sm text-slate-700 hover:bg-slate-50" aria-label="Perbesar font">+</button>
                <span id="font-size-label" class="ml-2 text-sm text-slate-500" aria-hidden="true"></span>
            </div>
        </div>

        <div class="mt-5 overflow-x-auto rounded-[1.75rem] border border-slate-200 bg-white md:mt-6 w-full">
            <style>
                .agenda-table {
                    border-collapse: collapse;
                    width: 100%;
                    table-layout: auto;
                }
                .agenda-table th,
                .agenda-table td {
                    border: 1px solid #cbd5e1;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
                .agenda-table th {
                    background: #f8fafc;
                }
                @media (max-width: 768px) {
                    .agenda-table {
                        table-layout: auto;
                    }
                    .agenda-table th,
                    .agenda-table td {
                        white-space: normal;
                        overflow-wrap: anywhere;
                        max-width: none;
                    }
                }
            </style>
            <table class="agenda-table w-full text-sm text-slate-700">
                <thead>
                    <style>
                        .agenda-table tr.selected-row td { background-color: #eff6ff; }
                    </style>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-[0.18em] text-slate-500">
                        <th class="px-3 py-3 text-center" rowspan="2">No</th>
                        <th class="min-w-[140px] px-3 py-3" rowspan="2">Tanggal</th>
                        <th class="min-w-[120px] px-3 py-3" rowspan="2">Waktu</th>
                        <th class="min-w-[120px] px-3 py-3" rowspan="2">Hari</th>
                        <th class="min-w-[260px] px-3 py-3" rowspan="2">Nama Kegiatan/Materi Konten</th>
                        <th class="min-w-[220px] px-3 py-3" rowspan="2">Tempat Kegiatan/Acara</th>
                        <th class="min-w-[220px] px-3 py-3" rowspan="2">Pejabat Yang Menghadiri</th>
                        <th class="min-w-[320px] px-3 py-3" colspan="4">Konten Visual</th>
                        <th class="min-w-[320px] px-3 py-3" colspan="4">Konten Video</th>
                        <th class="min-w-[320px] px-3 py-3" colspan="2">Berita</th>
                        <th class="min-w-[220px] px-3 py-3" colspan="3">Bahan</th>
                        <th class="min-w-[320px] px-3 py-3" colspan="3">Hasil Editing</th>
                        <th class="px-3 py-3" rowspan="2">Catatan Prokopim</th>
                    </tr>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-[0.18em] text-slate-500">
                        <th class="px-3 py-3">Materi</th>
                        <th class="px-3 py-3">Status AK</th>
                        <th class="px-3 py-3">Status Prokopim</th>
                        <th class="px-3 py-3">Link Publish</th>
                        <th class="px-3 py-3">Materi</th>
                        <th class="px-3 py-3">Status AK</th>
                        <th class="px-3 py-3">Status Prokopim</th>
                        <th class="px-3 py-3">Link Publish</th>
                        <th class="px-3 py-3">Materi</th>
                        <th class="px-3 py-3">Link Publish</th>
                        <th class="px-3 py-3">Foto</th>
                        <th class="px-3 py-3">Video</th>
                        <th class="px-3 py-3">Sambutan</th>
                        <th class="px-3 py-3">Konten Visual</th>
                        <th class="px-3 py-3">Konten Video</th>
                        <th class="px-3 py-3">Berita</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($agendas as $agenda)
                        @php
                            $dayLabel = $agenda->event_date->translatedFormat('l');
                            $publishUrl = route('agendas.show', $agenda);
                            $verificationLabel = $agenda->verified_at ? 'Terverifikasi' : 'Menunggu verifikasi';
                            $prokopimLabel = config('agpim.statuses')[$agenda->status] ?? $agenda->status;
                            $photoDocuments = $agenda->documents->where('category', 'photo');
                            $videoDocuments = $agenda->documents->where('category', 'video');
                            $beritaDocuments = $agenda->documents->where('category', 'berita');
                            $kontenDocuments = $agenda->documents->where('category', 'konten');
                            $sambutanUrl = $agenda->speech_draft_path ? $resolveStorageUrl($agenda->speech_draft_path) : null;
                            $prokopimNote = $agenda->disposition_note ?: $agenda->verification_note;
                            $visualItems = $agenda->visual_content ?: [];
                            $videoContentSummary = $agenda->video_content ?: '-';
                            $visualStatusAk = $agenda->visual_status_ak ?: '-';
                            $visualStatusProkopim = $agenda->visual_status_prokopim ?: '-';
                            $videoStatusAk = $agenda->video_status_ak ?: '-';
                            $videoStatusProkopim = $agenda->video_status_prokopim ?: '-';
                            $hasVisualPublish = count($visualItems) > 0;
                            $hasVideoPublish = $videoContentSummary !== '-' || $videoDocuments->isNotEmpty();
                            $hasBeritaPublish = $beritaDocuments->isNotEmpty();
                            $hasVisualEditingFiles = $photoDocuments->isNotEmpty();
                            $hasVideoEditingFiles = $videoDocuments->isNotEmpty();
                            $hasBeritaEditingFiles = $beritaDocuments->isNotEmpty();
                            $beritaMateri = $hasBeritaPublish ? $beritaDocuments->count() . ' file' : 'Belum diisi';
                        @endphp
                        <tr @click="selectedAgendaId = {{ $agenda->id }}" :class="selectedAgendaId === {{ $agenda->id }} ? 'bg-blue-50' : ''" class="border-b border-slate-100 align-top last:border-b-0 cursor-pointer">
                            <td class="px-3 py-4 text-center text-sm font-semibold text-slate-600">{{ $loop->iteration + (($agendas->currentPage() - 1) * $agendas->perPage()) }}</td>
                            <td class="px-3 py-4">
                                <p class="font-medium text-slate-900">{{ $agenda->event_date->translatedFormat('d M Y') }}</p>
                            </td>
                            <td class="px-3 py-4">
                                <p class="font-medium text-slate-900">{{ $agenda->time_range_display }}</p>
                            </td>
                            <td class="px-3 py-4">
                                <p class="font-medium text-slate-900">{{ $dayLabel }}</p>
                            </td>
                            <td class="px-3 py-4">
                                <p class="font-semibold text-slate-900">{{ $agenda->title }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $agenda->type->name ?? '-' }}</p>
                            </td>
                            <td class="px-3 py-4">
                                <p class="font-medium text-slate-900">{{ $agenda->location }}</p>
                            </td>
                            <td class="px-3 py-4">
                                <p class="font-medium text-slate-900">{{ $agenda->person_in_charge ?: '-' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $agenda->leader_target ? (config('agpim.dispositions')[$agenda->leader_target] ?? $agenda->leader_target) : 'Belum ditentukan' }}</p>
                            </td>
                            <td class="px-3 py-4 {{ $agenda->visual_content_status === 'draft' ? 'bg-amber-50' : ($agenda->visual_content_status === 'fix' ? 'bg-emerald-50' : '') }}">
                                @if (auth()->check() && auth()->user()->hasRole('admin_prokopim', 'super_admin'))
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button type="button" class="rounded-md bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-200" @click="modal = 'edit-visual-{{ $agenda->id }}'" title="Edit materi visual">
                                            Edit Materi
                                        </button>
                                        @if ($agenda->visual_content_status)
                                            <span class="inline-flex rounded-full border {{ $agenda->visual_content_status === 'draft' ? 'border-amber-300 bg-amber-50 text-amber-700' : 'border-emerald-300 bg-emerald-50 text-emerald-700' }} px-2 py-1 text-xs font-semibold">
                                                {{ $agenda->visual_content_status === 'draft' ? 'Draft' : 'Fix' }}
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-500">Belum ada status</span>
                                        @endif
                                    </div>
                                @else
                                    @if (count($visualItems) > 0)
                                        <div class="space-y-1">
                                            @foreach ($visualItems as $item)
                                                <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">{{ $item }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-slate-400">Belum diisi</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-3 py-4 {{ $akStatusClasses[$visualStatusAk] ?? 'border-slate-300 bg-slate-100 text-slate-700' }}">
                                @if (auth()->check() && auth()->user()->hasRole('admin_prokopim', 'super_admin'))
                                    <form method="POST" action="{{ route('agendas.prokopim.update', $agenda) }}" data-status-form data-media-url="{{ route('agendas.media.index', ['agenda' => $agenda, 'type' => 'berita']) }}">
                                        @csrf
                                        <select name="visual_status_ak" data-status-name="visual_status_ak" data-status-select class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                            <option value="" disabled {{ $visualStatusAk && $visualStatusAk !== '-' ? '' : 'selected' }}>{{ $visualStatusAk !== '-' ? $visualStatusAk : 'Pilih status' }}</option>
                                            <option value="Belum" @selected($agenda->visual_status_ak === 'Belum')>Belum</option>
                                            <option value="menunggu" @selected($agenda->visual_status_ak === 'menunggu')>Menunggu</option>
                                            <option value="sedang dikerjakan" @selected($agenda->visual_status_ak === 'sedang dikerjakan')>Sedang dikerjakan</option>
                                            <option value="selesai" @selected($agenda->visual_status_ak === 'selesai')>Selesai</option>
                                        </select>
                                    </form>
                                    @if ($visualStatusAk === 'selesai')
                                        <a href="{{ route('agendas.media.index', ['agenda' => $agenda, 'type' => 'berita']) }}" target="_blank" rel="noopener" class="inline-flex rounded-full border border-slate-300 bg-white px-3 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-100 mt-2">View Hasil Editing</a>
                                    @endif
                                @else
                                    <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700">{{ $visualStatusAk }}</span>
                                    @if ($visualStatusAk === 'selesai')
                                        <a href="{{ route('agendas.media.index', ['agenda' => $agenda, 'type' => 'berita']) }}" target="_blank" rel="noopener" class="inline-flex rounded-full border border-slate-300 bg-white px-3 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-100 mt-2">View Hasil Editing</a>
                                    @endif
                                @endif
                            </td>
                            <td class="px-3 py-4 {{ $prokopimStatusClasses[$visualStatusProkopim] ?? 'border-slate-300 bg-slate-100 text-slate-700' }}">
                                @if (auth()->check() && auth()->user()->hasRole('admin_prokopim', 'super_admin'))
                                    <form method="POST" action="{{ route('agendas.prokopim.update', $agenda) }}" data-status-form>
                                        @csrf
                                        <select name="visual_status_prokopim" data-status-name="visual_status_prokopim" data-status-select class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                            <option value="" disabled {{ $visualStatusProkopim ? '' : 'selected' }}>{{ $visualStatusProkopim ?: 'Pilih status' }}</option>
                                            <option value="belum ada bahan" @selected($agenda->visual_status_prokopim === 'belum ada bahan')>Belum ada bahan</option>
                                            <option value="sudah ada bahan dokumentasi" @selected($agenda->visual_status_prokopim === 'sudah ada bahan dokumentasi')>Sudah ada bahan dokumentasi</option>
                                            <option value="belum diperiksa" @selected($agenda->visual_status_prokopim === 'belum diperiksa')>Belum diperiksa</option>
                                            <option value="perlu revisi" @selected($agenda->visual_status_prokopim === 'perlu revisi')>Perlu revisi</option>
                                            <option value="ok tayang" @selected($agenda->visual_status_prokopim === 'ok tayang')>Ok tayang</option>
                                        </select>
                                    </form>
                                @else
                                    <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700">{{ $visualStatusProkopim }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-4">
                                @if (auth()->check() && auth()->user()->hasRole('admin_prokopim', 'super_admin'))
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if ($agenda->visual_published_link)
                                            <a href="{{ $agenda->visual_published_link }}" target="_blank" rel="noopener" class="font-medium text-blue-700 hover:text-blue-800">Buka Link</a>
                                        @else
                                            <span class="text-xs text-slate-500">Belum ada link</span>
                                        @endif
                                        <button type="button" class="rounded-md bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-200" @click="modal = 'edit-visual-link-{{ $agenda->id }}'" title="Edit link publish">
                                            Edit Link
                                        </button>
                                    </div>
                                @else
                                    @if ($hasVisualPublish)
                                        <a href="{{ $publishUrl }}" target="_blank" rel="noopener" class="font-medium text-blue-700 hover:text-blue-800">Buka detail</a>
                                    @else
                                        <span class="text-slate-500">Belum ada</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-3 py-4 {{ $agenda->video_content_status === 'draft' ? 'bg-amber-50' : ($agenda->video_content_status === 'fix' ? 'bg-emerald-50' : '') }}">
                                @if (auth()->check() && auth()->user()->hasRole('admin_prokopim', 'super_admin'))
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button type="button" class="rounded-md bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-200" @click="modal = 'edit-video-{{ $agenda->id }}'" title="Edit materi video">
                                            Edit Materi
                                        </button>
                                        @if ($agenda->video_content_status)
                                            <span class="inline-flex rounded-full border {{ $agenda->video_content_status === 'draft' ? 'border-amber-300 bg-amber-50 text-amber-700' : 'border-emerald-300 bg-emerald-50 text-emerald-700' }} px-2 py-1 text-xs font-semibold">
                                                {{ $agenda->video_content_status === 'draft' ? 'Draft' : 'Fix' }}
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-500">Belum ada status</span>
                                        @endif
                                    </div>
                                @else
                                    <p class="text-sm text-slate-700">{{ $videoContentSummary }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-4 {{ $akStatusClasses[$videoStatusAk] ?? 'border-slate-300 bg-slate-100 text-slate-700' }}">
                                @if (auth()->check() && auth()->user()->hasRole('admin_prokopim', 'super_admin'))
                                    <form method="POST" action="{{ route('agendas.prokopim.update', $agenda) }}" data-status-form>
                                        @csrf
                                        <select name="video_status_ak" data-status-name="video_status_ak" data-status-select class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                            <option value="" disabled {{ $videoStatusAk && $videoStatusAk !== '-' ? '' : 'selected' }}>{{ $videoStatusAk !== '-' ? $videoStatusAk : 'Pilih status' }}</option>
                                            <option value="Belum" @selected($agenda->video_status_ak === 'Belum')>Belum</option>
                                            <option value="menunggu" @selected($agenda->video_status_ak === 'menunggu')>Menunggu</option>
                                            <option value="sedang dikerjakan" @selected($agenda->video_status_ak === 'sedang dikerjakan')>Sedang dikerjakan</option>
                                            <option value="selesai" @selected($agenda->video_status_ak === 'selesai')>Selesai</option>
                                        </select>
                                    </form>
                                @else
                                    <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700">{{ $videoStatusAk }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-4 {{ $prokopimStatusClasses[$videoStatusProkopim] ?? 'border-slate-300 bg-slate-100 text-slate-700' }}">
                                @if (auth()->check() && auth()->user()->hasRole('admin_prokopim', 'super_admin'))
                                    <form method="POST" action="{{ route('agendas.prokopim.update', $agenda) }}" data-status-form>
                                        @csrf
                                        <select name="video_status_prokopim" data-status-name="video_status_prokopim" data-status-select class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                            <option value="" disabled {{ $videoStatusProkopim ? '' : 'selected' }}>{{ $videoStatusProkopim ?: 'Pilih status' }}</option>
                                            <option value="belum ada bahan" @selected($agenda->video_status_prokopim === 'belum ada bahan')>Belum ada bahan</option>
                                            <option value="sudah ada bahan dokumentasi" @selected($agenda->video_status_prokopim === 'sudah ada bahan dokumentasi')>Sudah ada bahan dokumentasi</option>
                                            <option value="belum diperiksa" @selected($agenda->video_status_prokopim === 'belum diperiksa')>Belum diperiksa</option>
                                            <option value="perlu revisi" @selected($agenda->video_status_prokopim === 'perlu revisi')>Perlu revisi</option>
                                            <option value="ok tayang" @selected($agenda->video_status_prokopim === 'ok tayang')>Ok tayang</option>
                                        </select>
                                    </form>
                                @else
                                    <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700">{{ $videoStatusProkopim }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-4">
                                @if (auth()->check() && auth()->user()->hasRole('admin_prokopim', 'super_admin'))
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if ($agenda->video_published_link)
                                            <a href="{{ $agenda->video_published_link }}" target="_blank" rel="noopener" class="font-medium text-blue-700 hover:text-blue-800">Buka Link</a>
                                        @else
                                            <span class="text-xs text-slate-500">Belum ada link</span>
                                        @endif
                                        <button type="button" class="rounded-md bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-200" @click="modal = 'edit-video-link-{{ $agenda->id }}'" title="Edit link publish">
                                            Edit Link
                                        </button>
                                    </div>
                                @else
                                    @if ($hasVideoPublish)
                                        <a href="{{ $publishUrl }}" target="_blank" rel="noopener" class="font-medium text-blue-700 hover:text-blue-800">Buka detail</a>
                                    @else
                                        <span class="text-slate-500">Belum ada</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-3 py-4">
                                <span class="text-sm text-slate-700">{{ $beritaMateri }}</span>
                            </td>
                            <td class="px-3 py-4">
                                @if ($hasBeritaPublish)
                                    <a href="{{ $publishUrl }}" target="_blank" rel="noopener" class="font-medium text-blue-700 hover:text-blue-800">Buka detail</a>
                                @else
                                    <span class="text-slate-500">Belum ada</span>
                                @endif
                            </td>
                            <td class="px-3 py-4 {{ $hasVisualEditingFiles ? 'bg-emerald-50' : '' }}">
                                <button type="button" @click="modal = 'media-{{ $agenda->id }}'; mediaModalUrl = '{{ route('agendas.media.index', ['agenda' => $agenda, 'type' => 'photo', 'popup' => 1]) }}'; mediaModalTitle = 'Kelola Konten Visual'" class="inline-flex rounded-full border {{ $hasVisualEditingFiles ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-slate-300 bg-white text-slate-700' }} px-3 py-1 text-xs font-semibold hover:bg-slate-50">Kelola</button>
                            </td>
                            <td class="px-3 py-4 {{ $hasVideoEditingFiles ? 'bg-emerald-50' : '' }}">
                                <button type="button" @click="modal = 'media-{{ $agenda->id }}'; mediaModalUrl = '{{ route('agendas.media.index', ['agenda' => $agenda, 'type' => 'video', 'popup' => 1]) }}'; mediaModalTitle = 'Kelola Konten Video'" class="inline-flex rounded-full border {{ $hasVideoEditingFiles ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-slate-300 bg-white text-slate-700' }} px-3 py-1 text-xs font-semibold hover:bg-slate-50">Kelola</button>
                            </td>
                            <td class="px-3 py-4 {{ $hasBeritaEditingFiles ? 'bg-emerald-50' : '' }}">
                                <button type="button" @click="modal = 'media-{{ $agenda->id }}'; mediaModalUrl = '{{ route('agendas.media.index', ['agenda' => $agenda, 'type' => 'berita', 'popup' => 1]) }}'; mediaModalTitle = 'Kelola Berita'" class="inline-flex rounded-full border {{ $hasBeritaEditingFiles ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-slate-300 bg-white text-slate-700' }} px-3 py-1 text-xs font-semibold hover:bg-slate-50">Kelola</button>
                            </td>
                            <td class="px-3 py-4 {{ $photoDocuments->isNotEmpty() ? 'bg-emerald-50' : '' }}">
                                <div class="flex flex-wrap gap-2 text-sm">
                                    @if ($photoDocuments->isNotEmpty())
                                        <button type="button" @click="modal = 'media-{{ $agenda->id }}'; mediaModalUrl = '{{ route('agendas.media.index', ['agenda' => $agenda, 'type' => 'photo']) }}'; mediaModalTitle = 'Kelola Foto'" class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-100">View foto</button>
                                    @endif
                                    <button type="button" @click="modal = 'media-{{ $agenda->id }}'; mediaModalUrl = '{{ route('agendas.media.index', ['agenda' => $agenda, 'type' => 'photo']) }}'; mediaModalTitle = 'Kelola Foto'" class="inline-flex rounded-full border border-slate-300 bg-white px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">Kelola foto</button>
                                </div>
                            </td>
                            <td class="px-3 py-4 {{ $videoDocuments->isNotEmpty() ? 'bg-emerald-50' : '' }}">
                                <div class="flex flex-wrap gap-2 text-sm">
                                    @if ($videoDocuments->isNotEmpty())
                                        <button type="button" @click="modal = 'media-{{ $agenda->id }}'; mediaModalUrl = '{{ route('agendas.media.index', ['agenda' => $agenda, 'type' => 'video']) }}'; mediaModalTitle = 'Kelola Video'" class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">View video</button>
                                    @endif
                                    <button type="button" @click="modal = 'media-{{ $agenda->id }}'; mediaModalUrl = '{{ route('agendas.media.index', ['agenda' => $agenda, 'type' => 'video']) }}'; mediaModalTitle = 'Kelola Video'" class="inline-flex rounded-full border border-slate-300 bg-white px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">Kelola video</button>
                                </div>
                            </td>
                            <td class="px-3 py-4">
                                <div class="flex flex-wrap gap-2 text-sm">
                                    <a href="{{ route('agendas.edit', $agenda) }}" class="inline-flex rounded-full border border-slate-300 bg-white px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">Kelola sambutan</a>
                                    @if ($sambutanUrl)
                                        <a href="{{ $sambutanUrl }}" target="_blank" rel="noopener" class="inline-flex rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-100">Lihat</a>
                                    @else
                                        <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-500">Tidak ada bahan sambutan</span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-3 py-4 text-sm text-slate-600">
                                {{ $prokopimNote ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="24" class="px-4 py-10 text-center text-sm text-slate-500">
                                @if($isVerificationMode)
                                    Belum ada agenda dari pengaju OPD yang cocok dengan filter saat ini.
                                @else
                                    Belum ada agenda yang cocok dengan filter saat ini.
                                @endif
                            </td>
                        </tr>
                    @endforelse

                    @foreach ($agendas as $agenda)
                        <div x-cloak x-show="modal === 'media-{{ $agenda->id }}'" class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/70 p-0 sm:p-2" @click.self="modal = ''; mediaModalUrl = ''; mediaModalTitle = ''">
                            <div class="card-panel relative flex h-[96vh] w-[96vw] max-w-none flex-col overflow-hidden rounded-3xl border-0 p-0 shadow-2xl">
                                <button type="button" class="absolute right-4 top-4 z-10 rounded-full border border-slate-200 bg-white/90 p-2 text-slate-600 shadow-sm backdrop-blur hover:text-slate-800" @click="modal = ''; mediaModalUrl = ''; mediaModalTitle = ''" aria-label="Tutup popup">
                                    <span class="material-symbols-outlined text-[20px] leading-none">close</span>
                                </button>
                                <div class="flex-1 overflow-hidden">
                                    <iframe :src="mediaModalUrl" class="h-full w-full border-0" title="Media manager"></iframe>
                                </div>
                            </div>
                        </div>

                        <!-- Modal untuk edit konten materi visual -->
                        <div x-cloak x-show="modal === 'edit-visual-{{ $agenda->id }}'" class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/70 p-3" @click.self="modal = ''">
                            <div class="card-panel flex h-[calc(100vh-2rem)] w-full max-w-6xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl">
                                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                                    <div>
                                        <h2 class="text-xl font-bold text-slate-900">Edit Materi Visual</h2>
                                        <p class="mt-1 text-sm text-slate-600">{{ $agenda->title }}</p>
                                    </div>
                                    <button type="button" class="rounded-full border border-slate-200 bg-white p-2 text-slate-500 transition hover:text-slate-700" @click="modal = ''">
                                        <span class="material-symbols-outlined text-[20px] leading-none">close</span>
                                    </button>
                                </div>
                                <form method="POST" action="{{ route('agendas.prokopim.update', $agenda) }}" class="flex flex-1 flex-col overflow-hidden px-5 py-4" data-content-form="visual-{{ $agenda->id }}">
                                    @csrf
                                    <div class="flex-1 overflow-y-auto pr-1">
                                        <label class="form-field h-full">
                                            <span>Isi Materi Konten Visual</span>
                                            <textarea id="visualContent-{{ $agenda->id }}" name="visual_content" rows="15" class="min-h-[calc(100vh-20rem)] w-full resize-none rounded-3xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100" placeholder="Ketikkan materi konten visual...">{{ old('visual_content', $agenda->visual_content) }}</textarea>
                                        </label>
                                    </div>
                                    <div class="sticky bottom-0 left-0 z-10 mt-4 border-t border-slate-200 bg-white/95 py-4 backdrop-blur-sm">
                                        <div class="flex flex-wrap justify-end gap-3">
                                            <button type="button" class="btn-secondary" @click="modal = ''">Batal</button>
                                            <button type="submit" name="visual_content_status" value="draft" class="btn-secondary">Simpan sebagai Draft</button>
                                            <button type="submit" name="visual_content_status" value="fix" class="btn-primary">Simpan sebagai Fix</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div x-cloak x-show="modal === 'edit-visual-link-{{ $agenda->id }}'" class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/45 p-4" @click.self="modal = ''">
                            <div class="card-panel max-h-[90vh] w-full max-w-xl overflow-y-auto p-5 md:p-7">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h2 class="text-xl font-bold text-slate-900">Edit Link Publish Visual</h2>
                                        <p class="mt-1 text-sm text-slate-600">{{ $agenda->title }}</p>
                                    </div>
                                    <button type="button" class="rounded-full border border-slate-200 p-2 text-slate-500 hover:text-slate-700" @click="modal = ''">
                                        <span class="material-symbols-outlined text-[20px] leading-none">close</span>
                                    </button>
                                </div>

                                <form method="POST" action="{{ route('agendas.prokopim.update', $agenda) }}" class="mt-6">
                                    @csrf
                                    <label class="form-field">
                                        <span>Link Publish Visual</span>
                                        <input type="url" name="visual_published_link" value="{{ old('visual_published_link', $agenda->visual_published_link) }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100" placeholder="https://example.com" />
                                    </label>
                                    <div class="mt-4 flex justify-end gap-3">
                                        <button type="button" class="btn-secondary" @click="modal = ''">Batal</button>
                                        <button type="submit" class="btn-primary">Simpan Link</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Modal untuk edit konten materi video -->
                        <div x-cloak x-show="modal === 'edit-video-{{ $agenda->id }}'" class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/70 p-3" @click.self="modal = ''">
                            <div class="card-panel flex h-[calc(100vh-2rem)] w-full max-w-6xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl">
                                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                                    <div>
                                        <h2 class="text-xl font-bold text-slate-900">Edit Materi Video</h2>
                                        <p class="mt-1 text-sm text-slate-600">{{ $agenda->title }}</p>
                                    </div>
                                    <button type="button" class="rounded-full border border-slate-200 bg-white p-2 text-slate-500 transition hover:text-slate-700" @click="modal = ''">
                                        <span class="material-symbols-outlined text-[20px] leading-none">close</span>
                                    </button>
                                </div>
                                <form method="POST" action="{{ route('agendas.prokopim.update', $agenda) }}" class="flex flex-1 flex-col overflow-hidden px-5 py-4" data-content-form="video-{{ $agenda->id }}">
                                    @csrf
                                    <div class="flex-1 overflow-y-auto pr-1">
                                        <label class="form-field h-full">
                                            <span>Isi Materi Konten Video</span>
                                            <textarea id="videoContent-{{ $agenda->id }}" name="video_content" rows="15" class="min-h-[calc(100vh-20rem)] w-full resize-none rounded-3xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100" placeholder="Ketikkan materi konten video...">{{ old('video_content', $agenda->video_content) }}</textarea>
                                        </label>
                                    </div>
                                    <div class="sticky bottom-0 left-0 z-10 mt-4 border-t border-slate-200 bg-white/95 py-4 backdrop-blur-sm">
                                        <div class="flex flex-wrap justify-end gap-3">
                                            <button type="button" class="btn-secondary" @click="modal = ''">Batal</button>
                                            <button type="submit" name="video_content_status" value="draft" class="btn-secondary">Simpan sebagai Draft</button>
                                            <button type="submit" name="video_content_status" value="fix" class="btn-primary">Simpan sebagai Fix</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div x-cloak x-show="modal === 'edit-video-link-{{ $agenda->id }}'" class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/45 p-4" @click.self="modal = ''">
                                            <div class="card-panel max-h-[90vh] w-full max-w-xl overflow-y-auto p-5 md:p-7 flex flex-col gap-4">
                                                <div class="flex items-start justify-between gap-4">
                                                    <div>
                                                        <h2 class="text-xl font-bold text-slate-900">Edit Link Publish Video</h2>
                                                        <p class="mt-1 text-sm text-slate-600">{{ $agenda->title }}</p>
                                                    </div>
                                                    <button type="button" class="rounded-full border border-slate-200 p-2 text-slate-500 hover:text-slate-700" @click="modal = ''">
                                                        <span class="material-symbols-outlined text-[20px] leading-none">close</span>
                                                    </button>
                                                </div>

                                                <form method="POST" action="{{ route('agendas.prokopim.update', $agenda) }}" class="mt-2 flex flex-col gap-4">
                                                    @csrf
                                                    <label class="form-field">
                                                        <span>Link Publish Video</span>
                                                        <input type="url" name="video_published_link" value="{{ old('video_published_link', $agenda->video_published_link) }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100" placeholder="https://example.com" />
                                                    </label>
                                                    <div class="flex justify-end gap-3">
                                                        <button type="button" class="btn-secondary" @click="modal = ''">Batal</button>
                                                        <button type="submit" class="btn-primary">Simpan Link</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <script>
            (function () {
                const akMap = @json($akStatusClasses);
                const prokopimMap = @json($prokopimStatusClasses);

                // collect all utility tokens used in maps so we can remove them safely
                const allTokens = new Set();
                Object.values(akMap).concat(Object.values(prokopimMap)).forEach(v => {
                    v.split(/\s+/).filter(Boolean).forEach(t => allTokens.add(t));
                });

                const tokens = Array.from(allTokens);

                function removeStatusTokens(el) {
                    if (!el || !el.classList) return;
                    tokens.forEach(t => el.classList.remove(t));
                }

                function applyClassesFor(select, value) {
                    const name = select.dataset.statusName || select.name || '';
                    const isAk = name.toLowerCase().includes('_ak');
                    const map = isAk ? akMap : prokopimMap;
                    const classString = map[value] || '';
                    const cls = classString.split(/\s+/).filter(Boolean);

                    const td = select.closest('td');
                    if (td) {
                        removeStatusTokens(td);
                        td.classList.add(...cls);
                    }

                    // style the select/button itself as well
                    removeStatusTokens(select);
                    select.classList.add(...cls);

                    // special: when AK becomes 'selesai', ensure a "View Hasil Editing" link exists
                    if (isAk && String(value).toLowerCase() === 'selesai') {
                        const form = select.closest('form[data-status-form]');
                        if (form) {
                            const mediaUrl = form.dataset.mediaUrl;
                            if (mediaUrl) {
                                // look for existing link
                                let link = form.parentElement.querySelector('.view-hasil-editing-link');
                                if (!link) {
                                    link = document.createElement('a');
                                    link.target = '_blank';
                                    link.rel = 'noopener';
                                    link.className = 'view-hasil-editing-link inline-flex rounded-full border border-slate-300 bg-white px-3 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-100 mt-2';
                                    link.textContent = 'View Hasil Editing';
                                    form.parentElement.appendChild(link);
                                }
                                link.href = mediaUrl;
                            }
                        }
                    } else if (isAk) {
                        // remove link if present when status changes away from selesai
                        const form = select.closest('form[data-status-form]');
                        if (form) {
                            const link = form.parentElement.querySelector('.view-hasil-editing-link');
                            if (link) link.remove();
                        }
                    }
                }

                // attach listeners
                document.addEventListener('DOMContentLoaded', function () {
                    document.querySelectorAll('select[data-status-select]').forEach(select => {
                        // apply initial classes based on current value
                        try {
                            applyClassesFor(select, select.value || select.options[select.selectedIndex]?.text || '');
                        } catch (e) {
                            // ignore
                        }

                        select.addEventListener('change', async function (e) {
                            const sel = e.target;
                            const form = sel.closest('form[data-status-form]');
                            const prevValue = sel.dataset.prevValue ?? sel.getAttribute('data-prev') ?? '';

                            // store current as prev for next change
                            sel.dataset.prevValue = sel.value;

                            // update UI immediately
                            applyClassesFor(sel, sel.value);

                            if (!form) return;

                            const tokenEl = document.querySelector('meta[name="csrf-token"]');
                            const token = tokenEl ? tokenEl.getAttribute('content') : ''; 

                            try {
                                const res = await fetch(form.action, {
                                    method: 'POST',
                                    headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                                    body: new FormData(form),
                                    credentials: 'same-origin'
                                });

                                const contentType = res.headers.get('content-type') || '';
                                let payload = null;
                                if (contentType.indexOf('application/json') !== -1) {
                                    payload = await res.json();
                                } else {
                                    payload = await res.text();
                                }

                                if (!res.ok) {
                                    console.error('Save failed', res.status, payload);
                                    // revert UI to previous value
                                    try { applyClassesFor(sel, prevValue); sel.value = prevValue; } catch (e) {}
                                    const message = (payload && payload.message) ? payload.message : 'Penyimpanan status gagal (kode ' + res.status + ').';
                                    alert(message);
                                    return;
                                }

                                // success — optionally show non-blocking feedback
                                if (payload && payload.status === 'success' && payload.message) {
                                    console.info('Saved:', payload.message);
                                }
                            } catch (err) {
                                console.error('Network error saving status', err);
                                // revert UI
                                try { applyClassesFor(sel, prevValue); sel.value = prevValue; } catch (e) {}
                                alert('Terjadi kesalahan jaringan saat menyimpan status.');
                            }
                        });
                    });
                });
            })();
        </script>

        @if ($agendas->hasPages())
            <div class="mt-5 md:mt-6">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="text-sm text-slate-600">
                        Menampilkan <span class="font-medium text-slate-900">{{ $agendas->firstItem() }}</span>
                        – <span class="font-medium text-slate-900">{{ $agendas->lastItem() }}</span>
                        dari <span class="font-medium text-slate-900">{{ $agendas->total() }}</span> agenda
                    </div>
                    <div class="flex justify-start md:justify-end">
                        <div class="inline-flex items-center rounded-md bg-white/50 p-1">
                            {{ $agendas->links() }}
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </x-ui.panel>

    @if (! $isVerificationMode && $canSubmitAgenda)
        <div x-cloak x-show="modal === 'create'" class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/45 p-4" @click.self="modal = ''">
            <div class="card-panel max-h-[90vh] w-full max-w-4xl overflow-y-auto p-5 md:p-7">
                <div class="flex items-start justify-between gap-4">
                    <x-ui.section-heading eyebrow="Agenda Baru" title="Tambah pengajuan agenda" subtitle="Form dibuat dalam popup agar proses input lebih ringkas." />
                    <button type="button" class="rounded-full border border-slate-200 p-2 text-slate-500 hover:text-slate-700" @click="modal = ''">
                        <span class="material-symbols-outlined text-[20px] leading-none">close</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('agendas.store') }}" enctype="multipart/form-data" class="mt-5 space-y-4 md:mt-6">
                    @csrf
                    @include('agendas.partials.form-fields', ['agendaItem' => null, 'modalKey' => 'create'])
                    <div class="flex flex-wrap justify-end gap-3">
                        <button type="button" class="btn-secondary" @click="modal = ''">Batal</button>
                        <button type="submit" class="btn-primary">Kirim Pengajuan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if (auth()->check() && ! auth()->user()->hasRole('opd'))
        <div x-cloak x-show="modal === 'admin-create'" class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/45 p-4" @click.self="modal = ''">
            <div class="card-panel max-h-[90vh] w-full max-w-4xl overflow-y-auto p-5 md:p-7">
                <div class="flex items-start justify-between gap-4">
                    <x-ui.section-heading eyebrow="Agenda Langsung" title="Tambah agenda langsung disetujui" subtitle="Form ini dibuat khusus admin untuk memasukkan agenda tanpa melalui alur usulan." />
                    <button type="button" class="rounded-full border border-slate-200 p-2 text-slate-500 hover:text-slate-700" @click="modal = ''">
                        <span class="material-symbols-outlined text-[20px] leading-none">close</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('agendas.store') }}" enctype="multipart/form-data" class="mt-5 space-y-4 md:mt-6">
                    @csrf
                    <input type="hidden" name="direct_approve" value="1">
                    <label class="form-field">
                        <span>OPD *</span>
                        <select name="opd_id" required>
                            <option value="">Pilih OPD</option>
                            @foreach (($opdOptions ?? collect()) as $opdOption)
                                <option value="{{ $opdOption->id }}">{{ $opdOption->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    @include('agendas.partials.form-fields', ['agendaItem' => null, 'modalKey' => 'admin-create'])
                    <div class="flex flex-wrap justify-end gap-3">
                        <button type="button" class="btn-secondary" @click="modal = ''">Batal</button>
                        <button type="submit" class="btn-primary">Simpan & Setujui</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @foreach ($agendas as $agenda)
        @php
            $previewItems = [];
            if ($agenda->invitation_letter_path) {
                $ext = strtolower(pathinfo($agenda->invitation_letter_path, PATHINFO_EXTENSION));
                $previewItems[] = [
                    'label' => 'Surat Undangan',
                    'url' => $resolveStorageUrl($agenda->invitation_letter_path),
                    'ext' => $ext,
                    'size' => null,
                    'previewable' => in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'], true),
                ];
            }

            if ($agenda->speech_draft_path) {
                $ext = strtolower(pathinfo($agenda->speech_draft_path, PATHINFO_EXTENSION));
                $previewItems[] = [
                    'label' => 'Draft Sambutan',
                    'url' => $resolveStorageUrl($agenda->speech_draft_path),
                    'ext' => $ext,
                    'size' => null,
                    'previewable' => in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'], true),
                ];
            }

            foreach ($agenda->documents as $document) {
                $ext = strtolower(pathinfo($document->file_path, PATHINFO_EXTENSION));
                $previewItems[] = [
                    'label' => $document->title ?: ucfirst(str_replace('_', ' ', $document->category)),
                    'url' => $resolveStorageUrl($document->file_path),
                    'ext' => $ext,
                    'size' => null,
                    'previewable' => in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'], true),
                ];
            }
        @endphp

        <div x-cloak x-show="modal === 'docs-{{ $agenda->id }}'" class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/45 p-4" @click.self="modal = ''">
            <div class="card-panel max-h-[90vh] w-full max-w-5xl overflow-y-auto p-5 md:p-7" x-data="{ items: @js($previewItems), selectedIndex: selectedDocIndex }" x-init="selectedIndex = selectedDocIndex">
                <div class="flex items-start justify-between gap-4">
                    <x-ui.section-heading eyebrow="Preview Dokumen" :title="$agenda->title" subtitle="Klik nama dokumen untuk melihat preview isinya." />
                    <button type="button" class="rounded-full border border-slate-200 p-2 text-slate-500 hover:text-slate-700" @click="modal = ''">
                        <span class="material-symbols-outlined text-[20px] leading-none">close</span>
                    </button>
                </div>

                <template x-if="items.length === 0">
                    <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-500">Dokumen belum tersedia.</div>
                </template>

                <template x-if="items.length > 0">
                    <div class="mt-5 grid gap-4 md:mt-6 md:grid-cols-[280px_1fr]">
                        <div class="space-y-2 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                            <template x-for="(item, index) in items" :key="index">
                                <button type="button" class="w-full rounded-xl border px-3 py-2 text-left text-sm transition"
                                    :class="selectedIndex === index ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-700 hover:border-blue-200'"
                                    @click="selectedIndex = index">
                                    <p class="font-semibold" x-text="item.label"></p>
                                    <p class="mt-1 text-xs text-slate-500" x-text="(item.ext ? item.ext.toUpperCase() : 'FILE') + (item.size ? ' • ' + item.size : '')"></p>
                                </button>
                            </template>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-3">
                            <template x-if="items[selectedIndex] && items[selectedIndex].previewable && items[selectedIndex].ext === 'pdf'">
                                <iframe class="h-[62vh] w-full rounded-xl border border-slate-200" :src="items[selectedIndex].url" title="Preview PDF"></iframe>
                            </template>

                            <template x-if="items[selectedIndex] && items[selectedIndex].previewable && ['jpg','jpeg','png','gif','webp'].includes(items[selectedIndex].ext)">
                                <div class="flex min-h-[62vh] items-center justify-center rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <img :src="items[selectedIndex].url" alt="Preview gambar" class="max-h-[58vh] w-auto rounded-lg shadow-sm">
                                </div>
                            </template>

                            <template x-if="items[selectedIndex] && !items[selectedIndex].previewable">
                                <div class="flex min-h-[62vh] flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                                    <p class="text-sm font-semibold text-slate-700">Preview tidak tersedia untuk format ini.</p>
                                    <p class="mt-1 text-xs text-slate-500">Silakan buka file untuk melihat isinya.</p>
                                </div>
                            </template>

                            <div class="mt-3 flex justify-end">
                                <a :href="items[selectedIndex] ? items[selectedIndex].url : '#'" target="_blank" rel="noopener" class="btn-primary" :class="!items[selectedIndex] ? 'pointer-events-none opacity-50' : ''">Buka File</a>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div x-cloak x-show="modal === 'detail-{{ $agenda->id }}'" class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/45 p-4" @click.self="modal = ''">
            <div class="card-panel max-h-[90vh] w-full max-w-4xl overflow-y-auto p-5 md:p-7">
                <div class="flex items-start justify-between gap-4">
                    <x-ui.section-heading eyebrow="Detail Agenda" :title="$agenda->title" subtitle="Ringkasan lengkap agenda dalam popup." />
                    <button type="button" class="rounded-full border border-slate-200 p-2 text-slate-500 hover:text-slate-700" @click="modal = ''">
                        <span class="material-symbols-outlined text-[20px] leading-none">close</span>
                    </button>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 md:mt-6 md:gap-4">
                    <div class="detail-card"><span>Tanggal</span><strong>{{ $agenda->event_date->translatedFormat('d F Y') }}</strong></div>
                    <div class="detail-card"><span>Waktu</span><strong>{{ $agenda->time_range_display }}</strong></div>
                    <div class="detail-card"><span>Lokasi</span><strong>{{ $agenda->location }}</strong></div>
                    <div class="detail-card"><span>Penyelenggara</span><strong>{{ $agenda->opd->name }}</strong></div>
                    <div class="detail-card"><span>Jenis Kegiatan</span><strong>{{ $agenda->type->name }}</strong></div>
                    <div class="detail-card"><span>Pengaju</span><strong>{{ $agenda->submitter->name }}</strong></div>
                    <div class="detail-card"><span>Target Kehadiran</span><strong>{{ config('agpim.dispositions')[$agenda->leader_target] ?? 'Belum dipilih' }}</strong></div>
                    <div class="detail-card"><span>PIC</span><strong>{{ $agenda->person_in_charge ?: '-' }} {{ $agenda->pic_phone ? '• '.$agenda->pic_phone : '' }}</strong></div>
                </div>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 md:mt-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Deskripsi</p>
                    <p class="mt-2 leading-7">{{ $agenda->description }}</p>
                </div>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:mt-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Dokumen</p>
                    <div class="mt-3 grid gap-2 text-sm text-slate-700">
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-3">
                            <div>
                                <p>Surat Undangan</p>
                            </div>
                            @if ($agenda->invitation_letter_path)
                                <a href="{{ $resolveStorageUrl($agenda->invitation_letter_path) }}" target="_blank" rel="noopener" class="font-medium text-blue-700 hover:text-blue-800">Buka file</a>
                            @else
                                <span class="text-slate-400">Tidak ada</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-3">
                            <div>
                                <p>Draft Sambutan</p>
                            </div>
                            @if ($agenda->speech_draft_path)
                                <a href="{{ $resolveStorageUrl($agenda->speech_draft_path) }}" target="_blank" rel="noopener" class="font-medium text-blue-700 hover:text-blue-800">Buka file</a>
                            @else
                                <span class="text-slate-400">Tidak ada</span>
                            @endif
                        </div>
                        @foreach ($agenda->documents as $document)
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-3">
                                <div>
                                    <p>{{ $document->title ?: ucfirst(str_replace('_', ' ', $document->category)) }}</p>
                                </div>
                                <a href="{{ $resolveStorageUrl($document->file_path) }}" target="_blank" rel="noopener" class="font-medium text-blue-700 hover:text-blue-800">Buka file</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        @if (! $isVerificationMode && $agenda->status === 'submitted')
            <div x-cloak x-show="modal === 'edit-{{ $agenda->id }}'" class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/45 p-4" @click.self="modal = ''">
                <div class="card-panel max-h-[90vh] w-full max-w-4xl overflow-y-auto p-5 md:p-7">
                    <div class="flex items-start justify-between gap-4">
                        <x-ui.section-heading eyebrow="Ubah Agenda" :title="'Perbarui: '.$agenda->title" subtitle="Edit data pengajuan tanpa keluar dari tabel." />
                        <button type="button" class="rounded-full border border-slate-200 p-2 text-slate-500 hover:text-slate-700" @click="modal = ''">
                            <span class="material-symbols-outlined text-[20px] leading-none">close</span>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('agendas.update', $agenda) }}" enctype="multipart/form-data" class="mt-5 space-y-4 md:mt-6">
                        @csrf
                        @method('PUT')
                        @include('agendas.partials.form-fields', ['agendaItem' => $agenda, 'modalKey' => 'edit-'.$agenda->id])
                        <div class="flex flex-wrap justify-end gap-3">
                            <button type="button" class="btn-secondary" @click="modal = ''">Batal</button>
                            <button type="submit" class="btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        @if ($isVerificationMode)
            @php
                $verifyModalKey = 'verify-'.$agenda->id;
                $sendInvitationModalKey = 'send-invitation-'.$agenda->id;
                $useVerifyOld = old('modal') === $verifyModalKey;
                $defaultAction = match ($agenda->status) {
                    'awaiting_disposition', 'approved', 'completed' => 'approve',
                    'needs_revision' => 'needs_revision',
                    'rejected' => 'rejected',
                    default => '',
                };
                $attendanceSource = $useVerifyOld ? old('attendance_source', $agenda->attendance_source ?? '') : ($agenda->attendance_source ?? '');
                $selectedAction = $useVerifyOld ? old('action', $defaultAction) : $defaultAction;
                $selectedLeaderTarget = $useVerifyOld ? old('leader_target', $agenda->leader_target ?? '') : ($agenda->leader_target ?? '');
                $selectedInvitationOpdIds = collect(old('opd_ids', [$agenda->opd_id]))->map(fn ($value) => (string) $value)->all();
            @endphp
            <div x-cloak x-show="modal === 'verify-{{ $agenda->id }}'" class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/45 p-4" @click.self="modal = ''">
                <div class="card-panel max-h-[90vh] w-full max-w-3xl overflow-y-auto p-5 md:p-7" x-data="{ attendanceSource: @js($attendanceSource) }">
                    <div class="flex items-start justify-between gap-4">
                        <x-ui.section-heading eyebrow="Verifikasi" :title="'Tinjau: '.$agenda->title" subtitle="Verifikator dapat memperbarui keputusan verifikasi langsung dari popup." />
                        <button type="button" class="rounded-full border border-slate-200 p-2 text-slate-500 hover:text-slate-700" @click="modal = ''">
                            <span class="material-symbols-outlined text-[20px] leading-none">close</span>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('agendas.verify', $agenda) }}" class="mt-5 grid gap-3 md:grid-cols-2 md:mt-6">
                        @csrf
                        <input type="hidden" name="modal" value="{{ $verifyModalKey }}">
                        <label class="form-field md:col-span-2">
                            <span>Aksi verifikasi</span>
                            <div class="mt-2 grid gap-2 sm:grid-cols-3">
                                <label class="block cursor-pointer">
                                    <input type="radio" name="action" value="approve" class="peer sr-only" @checked($selectedAction === 'approve') required>
                                    <span class="flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition peer-checked:border-emerald-600 peer-checked:bg-emerald-600 peer-checked:text-white">Setujui</span>
                                </label>
                                <label class="block cursor-pointer">
                                    <input type="radio" name="action" value="needs_revision" class="peer sr-only" @checked($selectedAction === 'needs_revision') required>
                                    <span class="flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition peer-checked:border-amber-600 peer-checked:bg-amber-600 peer-checked:text-white">Perlu revisi</span>
                                </label>
                                <label class="block cursor-pointer">
                                    <input type="radio" name="action" value="rejected" class="peer sr-only" @checked($selectedAction === 'rejected') required>
                                    <span class="flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition peer-checked:border-rose-600 peer-checked:bg-rose-600 peer-checked:text-white">Tolak</span>
                                </label>
                            </div>
                            @error('action')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                        </label>
                        <label class="form-field md:col-span-2">
                            <span>Siapa yang akan berangkat? *</span>
                            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                @foreach (config('agpim.attendance_sources') as $sourceKey => $sourceLabel)
                                    <label class="block cursor-pointer">
                                        <input type="radio" name="attendance_source" value="{{ $sourceKey }}" x-model="attendanceSource" class="peer sr-only" @checked($attendanceSource === $sourceKey) required>
                                        <span class="flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 transition peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white">{{ $sourceLabel }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('attendance_source')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                        </label>
                        <template x-if="attendanceSource === 'proposed'">
                            <label class="form-field md:col-span-2">
                                <span>Disposisi ke siapa? *</span>
                                @if ($agenda->leader_target)
                                    <input type="hidden" name="leader_target" value="{{ $agenda->leader_target }}">
                                    <div class="mt-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700">
                                        {{ config('agpim.dispositions')[$agenda->leader_target] ?? $agenda->leader_target }}
                                    </div>
                                @else
                                    <div class="mt-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                                        Target pengajuan OPD belum tersedia.
                                    </div>
                                @endif
                                @error('leader_target')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                            </label>
                        </template>
                        <template x-if="attendanceSource !== 'proposed'">
                            <label class="form-field md:col-span-2">
                                <span>Disposisi ke siapa? *</span>
                                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                    @foreach (config('agpim.dispositions') as $leaderKey => $leaderLabel)
                                        @if ($leaderKey !== 'rejected' && $leaderKey !== $agenda->leader_target)
                                            <label class="block cursor-pointer">
                                                <input type="radio" name="leader_target" value="{{ $leaderKey }}" class="peer sr-only" @checked($selectedLeaderTarget === $leaderKey) required>
                                                <span class="flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 transition peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white">{{ $leaderLabel }}</span>
                                            </label>
                                        @endif
                                    @endforeach
                                </div>
                                @error('leader_target')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                            </label>
                        </template>
                        <label class="form-field">
                            <span>Nama pejabat disposisi</span>
                            <input type="text" name="delegate_name" value="{{ $useVerifyOld ? old('delegate_name', $agenda->delegate_name ?? '') : ($agenda->delegate_name ?? '') }}" placeholder="Nama pejabat yang didisposisikan">
                            @error('delegate_name')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                        </label>
                        <label class="form-field">
                            <span>Jabatan pejabat disposisi</span>
                            <input type="text" name="delegate_title" value="{{ $useVerifyOld ? old('delegate_title', $agenda->delegate_title ?? '') : ($agenda->delegate_title ?? '') }}" placeholder="Jabatan pejabat yang didisposisikan">
                            @error('delegate_title')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                        </label>
                        <label class="form-field md:col-span-2">
                            <span>Catatan verifikasi</span>
                            <input type="text" name="note" value="{{ $useVerifyOld ? old('note', $agenda->verification_note ?? '') : ($agenda->verification_note ?? '') }}" placeholder="Catatan untuk OPD / internal Prokopim">
                            @error('note')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                        </label>
                        <div class="md:col-span-2 flex flex-wrap justify-end gap-3">
                            <button type="button" class="btn-secondary" @click="modal = ''">Batal</button>
                            <button type="submit" class="btn-primary">Simpan Verifikasi</button>
                        </div>
                    </form>
                </div>
            </div>

            @if ($agenda->verified_at && $agenda->invitation_letter_path)
                <div x-cloak x-show="modal === 'send-invitation-{{ $agenda->id }}'" class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/45 p-4" @click.self="modal = ''">
                    <div class="card-panel max-h-[90vh] w-full max-w-3xl overflow-y-auto p-5 md:p-7">
                        <div class="flex items-start justify-between gap-4">
                            <x-ui.section-heading eyebrow="Kirim Undangan" :title="$agenda->title" subtitle="Pilih satu atau beberapa OPD tujuan yang akan menerima notifikasi undangan." />
                            <button type="button" class="rounded-full border border-slate-200 p-2 text-slate-500 hover:text-slate-700" @click="modal = ''">
                                <span class="material-symbols-outlined text-[20px] leading-none">close</span>
                            </button>
                        </div>

                        <form method="POST" action="{{ route('agendas.send-invitation', $agenda) }}" class="mt-5 space-y-4 md:mt-6">
                            @csrf
                            <input type="hidden" name="modal" value="{{ $sendInvitationModalKey }}">

                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach (($opdOptions ?? collect()) as $opdOption)
                                    <label class="block cursor-pointer">
                                        <input type="checkbox" name="opd_ids[]" value="{{ $opdOption->id }}" class="peer sr-only" @checked(in_array((string) $opdOption->id, $selectedInvitationOpdIds, true))>
                                        <span class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm text-slate-700 transition peer-checked:border-emerald-600 peer-checked:bg-emerald-600 peer-checked:text-white">
                                            <span>{{ $opdOption->name }}</span>
                                            @if ($agenda->opd_id === $opdOption->id)
                                                <span class="text-[10px] font-semibold uppercase tracking-[0.16em] opacity-80">Pengaju</span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('opd_ids')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                            @error('opd_ids.*')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                                Notifikasi akan dikirim ke seluruh user yang terdaftar pada OPD yang dipilih, beserta tautan file undangan.
                            </div>

                            <div class="flex flex-wrap justify-end gap-3">
                                <button type="button" class="btn-secondary" @click="modal = ''">Batal</button>
                                <button type="submit" class="btn-primary">Kirim Sekarang</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        @endif
    @endforeach
</div>

<script>
    // Simpan posisi scroll saat meninggalkan halaman
    document.addEventListener('DOMContentLoaded', function() {
        // Restore scroll position saat halaman dimuat
        const savedScrollTop = sessionStorage.getItem('agendasPageScrollTop');
        const savedScrollLeft = sessionStorage.getItem('agendasPageScrollLeft');
        
        if (savedScrollTop !== null && savedScrollLeft !== null) {
            window.scrollTo(parseInt(savedScrollLeft), parseInt(savedScrollTop));
            sessionStorage.removeItem('agendasPageScrollTop');
            sessionStorage.removeItem('agendasPageScrollLeft');
        }

        // Simpan posisi scroll sebelum meninggalkan halaman
        document.querySelectorAll('a[href]').forEach(link => {
            // Jangan simpan untuk link dengan target="_blank" atau yang membuka modal
            if (!link.target && !link.dataset.statusForm && !link.hasAttribute('@click')) {
                link.addEventListener('click', function(e) {
                    // Cek apakah ini link keluar dari halaman agenda
                    const href = this.getAttribute('href');
                    if (href && !href.startsWith('#') && !href.includes('agendas.index')) {
                        sessionStorage.setItem('agendasPageScrollTop', window.scrollY);
                        sessionStorage.setItem('agendasPageScrollLeft', window.scrollX);
                    }
                });
            }
        });
    });
</script>

<script src="https://cdn.tiny.cloud/1/6h1pwrr2zi4ne4tchyvkcm0104lb59echyx2238r8slilvh5/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    (function () {
        // Initialize TinyMCE untuk semua textarea material content
        document.addEventListener('DOMContentLoaded', function() {
            initializeTinyMCE();
        });

        function initializeTinyMCE() {
            // Cari semua textarea dengan id visualContent*/videoContent* (draft & publish)
            document.querySelectorAll('textarea[id^="visualContent"], textarea[id^="videoContent"]').forEach(textarea => {
                if (tinymce.get(textarea.id)) {
                    tinymce.remove(textarea.id);
                }
                
                tinymce.init({
                    target: textarea,
                    height: 400,
                    menubar: 'file edit view insert format tools table help',
                    branding: false,
                    promotion: false,
                    plugins: [
                        'advlist autolink lists link image charmap preview anchor',
                        'searchreplace visualblocks code fullscreen',
                        'insertdatetime media table help wordcount',
                        'emoticons codesample quickbars'
                    ],
                    toolbar: [
                        'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor',
                        'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table | codesample blockquote',
                        'removeformat | fullscreen preview code | help'
                    ],
                    quickbars_selection_toolbar: 'bold italic underline | blocks | forecolor backcolor | quicklink blockquote',
                    quickbars_insert_toolbar: 'quickimage quicktable',
                    content_style: 'body { font-family: Calibri, Arial, sans-serif; font-size: 15px; line-height: 1.6; margin: 1rem; }',
                    setup: function (editor) {
                        const form = textarea.closest('form');
                        if (!form) {
                            return;
                        }

                        form.addEventListener('submit', function () {
                            editor.save();
                        });
                    }
                });
            });
        }
    })();
</script>
<script>
    // AJAX submit for status selects and dynamic cell color update
    document.addEventListener('DOMContentLoaded', function () {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : null;

        const akClasses = {
            '-': 'border-rose-500 bg-rose-200 text-rose-900',
            'Belum': 'border-rose-500 bg-rose-200 text-rose-900',
            'menunggu': 'border-amber-500 bg-amber-200 text-amber-900',
            'sedang dikerjakan': 'border-sky-500 bg-sky-200 text-sky-900',
            'selesai': 'border-emerald-500 bg-emerald-200 text-emerald-900'
        };

        const prokopimClasses = {
            '-': 'border-rose-500 bg-rose-200 text-rose-900',
            'belum ada bahan': 'border-slate-500 bg-slate-200 text-slate-900',
            'sudah ada bahan dokumentasi': 'border-emerald-500 bg-emerald-200 text-emerald-900',
            'belum diperiksa': 'border-blue-500 bg-blue-200 text-blue-900',
            'perlu revisi': 'border-amber-500 bg-amber-200 text-amber-900',
            'ok tayang': 'border-emerald-500 bg-emerald-200 text-emerald-900'
        };

        // Build a set of all possible status class tokens from mappings
        const statusTokens = (function () {
            const s = new Set();
            Object.values(akClasses).forEach(str => str.split(/\s+/).forEach(t => s.add(t)));
            Object.values(prokopimClasses).forEach(str => str.split(/\s+/).forEach(t => s.add(t)));
            return Array.from(s);
        })();

        function updateElementStatusClasses(el, classes) {
            if (!el) return;
            // remove any known status tokens
            statusTokens.forEach(token => el.classList.remove(token));
            // add new tokens
            if (!classes) return;
            classes.split(/\s+/).forEach(t => {
                if (t) el.classList.add(t);
            });
        }

        document.body.addEventListener('change', function (e) {
            try {
                const el = e.target;
                let select = null;

                if (el && typeof el.matches === 'function' && el.matches('select[data-status-select]')) {
                    select = el;
                } else if (el && typeof el.closest === 'function') {
                    select = el.closest('select[data-status-select]');
                } else {
                    // fallback traversal
                    let p = el;
                    while (p && p !== document && p.nodeType === 1) {
                        if (p.tagName === 'SELECT' && p.hasAttribute('data-status-select')) { select = p; break; }
                        p = p.parentElement;
                    }
                }

                if (!select) return;

                const form = (typeof select.closest === 'function') ? select.closest('form[data-status-form]') : null;
                if (!form) return;

                const actionUrl = form.getAttribute('action') || form.action;
                if (!actionUrl) {
                    console.error('Status update aborted: form action missing', form);
                    return;
                }

            // build FormData from form (includes _token)
            const formData = new FormData(form);

            // If CSRF meta not present, ensure token from hidden input
            if (!csrfToken && form.querySelector('input[name="_token"]')) {
                // browser will include it in FormData automatically
            }

            // prepare headers with fallback CSRF token from hidden input
            const tokenFromInput = form.querySelector('input[name="_token"]') ? form.querySelector('input[name="_token"]').value : null;
            const headers = Object.assign({
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }, csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : (tokenFromInput ? { 'X-CSRF-TOKEN': tokenFromInput } : {}));

            fetch(form.action, {
                method: 'POST',
                headers: headers,
                body: formData,
                credentials: 'same-origin'
            }).then(async res => {
                const status = res.status;
                if (status === 419) {
                    alert('Sesi Anda kedaluwarsa. Silakan login ulang dan coba lagi.');
                    return;
                }
                if (status === 403) {
                    alert('Akses ditolak: Anda tidak memiliki izin untuk melakukan aksi ini.');
                    return;
                }
                if (!res.ok) {
                    let text = '';
                    try { text = await res.text(); } catch (_) { }
                    alert('Terjadi kesalahan saat menyimpan perubahan. ' + (text ? '\n\n' + text : ''));
                    return;
                }

                // success
                let data = null;
                try { data = await res.json(); } catch (_) { /* ignore non-json */ }

                const name = select.dataset.statusName || select.name;
                const value = select.value;
                const td = select.closest('td');
                if (!td) return;

                const isAk = name && name.endsWith('_ak');
                const resolveClass = (map, val) => {
                    if (!val && val !== 0) return null;
                    if (map.hasOwnProperty(val)) return map[val];
                    const lower = String(val).toLowerCase();
                    for (const k in map) {
                        if (k.toLowerCase() === lower) return map[k];
                    }
                    return null;
                };

                const classes = isAk
                    ? (resolveClass(akClasses, value) || 'border-slate-300 bg-slate-100 text-slate-700')
                    : (resolveClass(prokopimClasses, value) || 'border-slate-300 bg-slate-100 text-slate-700');

                updateElementStatusClasses(td, classes);
                // also update the select element itself so background changes immediately
                try { updateElementStatusClasses(select, classes); } catch (_) { /* ignore if not applicable */ }

                // If AK visual and status is selesai, ensure view link exists
                const mediaUrl = form.dataset.mediaUrl;
                if (isAk && value === 'selesai' && mediaUrl) {
                    if (!td.querySelector('a.view-media-link')) {
                        const a = document.createElement('a');
                        a.href = mediaUrl;
                        a.target = '_blank';
                        a.rel = 'noopener';
                        a.className = 'inline-flex rounded-full border border-slate-300 bg-white px-3 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-100 mt-2 view-media-link';
                        a.textContent = 'View Hasil Editing';
                        td.appendChild(a);
                    }
                } else {
                    const existing = td.querySelector('a.view-media-link');
                    if (existing) existing.remove();
                }

            } catch (err) {
                console.error('Status update handler error', err);
                // avoid throwing up to other libs (e.g. Alpine)
            }
        });
    });
</script>
</script>
@endsection
