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

    // Ada nomor WhatsApp valid (PIC pengajuan atau akun pengaju)?
    $hasWhatsappFor = static fn ($agenda): bool => collect([$agenda->pic_phone, $agenda->submitter?->phone])
        ->contains(fn ($phone) => $phone && \App\Support\WaPhoneFormatter::normalizeIndonesian((string) $phone));
@endphp
<div x-data="{ modal: @js(old('modal', '')), selectedDocIndex: 0, selectedAgendaId: null, showFilters: false }" x-init="$watch('modal', value => document.body.classList.toggle('popup-open', Boolean(value)))" @keydown.escape.window="modal = ''" class="space-y-4 md:space-y-6">
    <x-ui.panel plain>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                @if ($isVerificationMode)
                    @php $waiting = $statusCounts['submitted'] ?? 0; @endphp
                    <p class="text-sm text-muted">Agenda</p>
                    <h1 class="text-2xl font-semibold">Verifikasi pengajuan agenda</h1>
                    <p class="mt-1 text-sm text-muted">
                        @if ($waiting > 0)
                            <span class="font-medium text-[#9a5c06]">{{ $waiting }} pengajuan</span> menunggu keputusan Anda.
                        @else
                            Tidak ada pengajuan yang menunggu verifikasi.
                        @endif
                    </p>
                @else
                    <p class="text-sm text-muted">Agenda</p>
                    <h1 class="text-2xl font-semibold">Pengajuan agenda saya</h1>
                    <p class="mt-1 text-sm text-muted">Pantau status pengajuan dan perbaiki yang perlu direvisi.</p>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if ($isVerificationMode)
                    {{-- Notifikasi otomatis usulan masuk (bisa dimatikan) --}}
                    <div x-data="incomingWatcher({ endpoint: @js(route('agendas.incoming')), since: @js(now()->toIso8601String()) })" class="contents">
                        <button type="button" @click="toggle()" class="btn-secondary" role="switch" :aria-checked="enabled"
                                :title="enabled ? 'Mengecek usulan baru setiap 20 detik. Klik untuk mematikan.' : 'Klik untuk menyalakan notifikasi usulan masuk.'">
                            <span class="material-symbols-outlined text-[18px]" :class="enabled && 'nav-icon-active text-[#2f55d4]'" x-text="enabled ? 'notifications_active' : 'notifications_off'">notifications_active</span>
                            <span>Notifikasi otomatis</span>
                            <span class="relative inline-flex h-4 w-7 flex-none items-center rounded-full transition-colors" :class="enabled ? 'grad-blue' : 'bg-slate-300'">
                                <span class="absolute h-3 w-3 rounded-full bg-white shadow transition-transform" :class="enabled ? 'translate-x-3.5' : 'translate-x-0.5'"></span>
                            </span>
                        </button>

                        <template x-teleport="#incoming-banner">
                            <div x-show="newItems.length > 0" x-transition class="mt-4 flex flex-wrap items-center gap-3 rounded-xl px-4 py-3 text-sm text-[#23377a]" style="background: var(--grad-nav-active)" role="status">
                                <span class="material-symbols-outlined text-[20px] text-[#2f55d4]">mark_email_unread</span>
                                <p class="min-w-0 flex-1">
                                    <span class="font-semibold" x-text="newItems.length + ' usulan baru masuk'"></span>
                                    <span class="text-[#3d4f8f]" x-text="'· terbaru: ' + (newItems.at(-1)?.title || '')"></span>
                                </p>
                                <a href="{{ route('agendas.index', ['status' => 'submitted']) }}" class="btn-primary !px-3 !py-1.5 !text-xs">Tampilkan</a>
                                <button type="button" class="text-xs font-medium text-muted hover:text-ink" @click="newItems = []">Tutup</button>
                            </div>
                        </template>
                    </div>
                @endif
                <button type="button" class="btn-secondary" @click="showFilters = !showFilters" aria-controls="agenda-filter" :aria-pressed="showFilters">
                    <span class="material-symbols-outlined text-[18px]">tune</span>
                    <span x-text="showFilters ? 'Tutup filter' : 'Filter'"></span>
                </button>
                @if (! $isVerificationMode && $canSubmitAgenda)
                    <button type="button" class="btn-primary" @click="modal = 'create'">Tambah Agenda</button>
                @endif
                @if (auth()->check() && ! auth()->user()->hasRole('opd'))
                    <button type="button" class="btn-secondary" @click="modal = 'admin-create'">Tambah Agenda Langsung Setujui</button>
                @endif
            </div>
        </div>

        <div id="incoming-banner"></div>

        {{-- Tab status: antrean verifikasi lebih dulu --}}
        @php
            $statusTabs = $isVerificationMode
                ? ['submitted' => 'Perlu verifikasi', 'needs_revision' => 'Perlu revisi', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan', 'all' => 'Semua']
                : ['all' => 'Semua', 'submitted' => 'Diajukan', 'needs_revision' => 'Perlu revisi', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan'];
            $activeTab = ($filters['status'] ?? '') === '' ? 'all' : $filters['status'];
            $tabQuery = request()->except(['page', 'status']);
        @endphp
        <nav class="mt-5 flex gap-1 overflow-x-auto border-b border-line" aria-label="Status pengajuan">
            @foreach ($statusTabs as $tabKey => $tabLabel)
                @php
                    $count = $tabKey === 'all' ? $statusCounts->sum() : ($statusCounts[$tabKey] ?? 0);
                    $isActive = $activeTab === $tabKey;
                @endphp
                <a href="{{ route('agendas.index', array_merge($tabQuery, ['status' => $tabKey])) }}"
                   @class(['-mb-px flex items-center gap-1.5 whitespace-nowrap border-b-2 px-3 py-2 text-sm transition-colors', 'border-[#5a7ef1] font-medium text-ink' => $isActive, 'border-transparent text-muted hover:text-ink' => ! $isActive])
                   @if ($isActive) aria-current="page" @endif>
                    {{ $tabLabel }}
                    <span @if ($tabKey === 'submitted' && $isVerificationMode) data-pending-count @endif @class(['rounded-full px-1.5 font-mono text-[11px]', 'soft-amber text-[#9a5c06]' => $tabKey === 'submitted' && $count > 0 && $isVerificationMode, 'bg-hover text-muted' => ! ($tabKey === 'submitted' && $count > 0 && $isVerificationMode)])>{{ $count }}</span>
                </a>
            @endforeach
        </nav>

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

        {{-- HP: daftar kartu --}}
        <div class="mt-4 space-y-3 md:hidden">
            @forelse ($agendas as $agenda)
                <article class="card-panel cursor-pointer p-4 transition-colors hover:border-[#cfd6ea]" @click="modal = 'agenda-{{ $agenda->id }}'">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs text-muted">{{ $agenda->event_date->translatedFormat('D, d M Y') }} · <span class="font-mono">{{ $agenda->time_range_display }}</span></p>
                            <h3 class="mt-1 font-medium leading-snug">{{ $agenda->title }}</h3>
                            <p class="mt-0.5 text-xs text-muted">{{ $agenda->opd->alias ?? $agenda->opd->name ?? '-' }} · {{ $agenda->location }}</p>
                        </div>
                        <span data-agenda-status="{{ $agenda->id }}"><x-ui.status-pill :status="$agenda->status" :text="config('agpim.statuses')[$agenda->status] ?? $agenda->status" /></span>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-1.5 border-t border-line pt-3" @click.stop>
                        @if ($isVerificationMode && $agenda->status === 'submitted')
                            <button type="button" class="btn-primary !px-3 !py-1.5 !text-xs" @click="modal = 'agenda-{{ $agenda->id }}'">Verifikasi</button>
                        @endif
                        @if (! $isVerificationMode && in_array($agenda->status, ['submitted', 'needs_revision'], true))
                            <button type="button" class="btn-secondary !px-3 !py-1.5 !text-xs" @click="modal = 'edit-{{ $agenda->id }}'">{{ $agenda->status === 'needs_revision' ? 'Revisi' : 'Edit' }}</button>
                        @endif
                        @if ($isVerificationMode && $hasWhatsappFor($agenda))
                            @include('agendas.partials.wa-button', ['agenda' => $agenda])
                        @endif
                        @if ($isVerificationMode && $agenda->verified_at && $agenda->invitation_letter_path && auth()->user()?->hasRole('super_admin', 'admin_prokopim', 'bupati', 'wakil_bupati', 'sekda'))
                            <button type="button" class="btn-secondary !px-3 !py-1.5 !text-xs" @click="modal = 'send-invitation-{{ $agenda->id }}'">Undangan</button>
                        @endif
                    </div>
                </article>
            @empty
                <p class="py-8 text-center text-sm text-muted">
                    {{ $isVerificationMode ? 'Tidak ada pengajuan pada status ini.' : 'Belum ada agenda yang cocok dengan filter saat ini.' }}
                </p>
            @endforelse
        </div>

        {{-- Desktop: tabel --}}
        <div class="mt-5 hidden w-full overflow-x-auto rounded-xl border border-slate-200 bg-white md:mt-6 md:block">
            <style>
                .agenda-table {
                    border-collapse: collapse;
                    width: 100%;
                    table-layout: auto;
                }
                .agenda-table th,
                .agenda-table td {
                    border: 1px solid #ecebe7;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
                .agenda-table tr > :first-child { border-left: 0; }
                .agenda-table tr > :last-child { border-right: 0; }
                .agenda-table thead tr:first-child > * { border-top: 0; }
                .agenda-table tbody tr:last-child > * { border-bottom: 0; }
                .agenda-table th {
                    background: #fafaf9;
                    font-weight: 500;
                    color: #71706b;
                }
                .agenda-table tbody tr:hover td { background-color: #fbfbfa; }
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
                        .agenda-table tr.selected-row td { background: linear-gradient(120deg, #edf2fe, #f4f0fd); }
                    </style>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs text-slate-500">
                        <th class="px-3 py-3 text-center">No</th>
                        <th class="min-w-[170px] px-3 py-3">Jadwal</th>
                        <th class="min-w-[240px] px-3 py-3">Nama Kegiatan</th>
                        <th class="min-w-[140px] px-3 py-3">Status</th>
                        <th class="min-w-[400px] px-3 py-3">Aksi</th>
                        <th class="min-w-[200px] px-3 py-3">Tempat Kegiatan/Acara</th>
                        <th class="min-w-[200px] px-3 py-3">Pejabat Yang Menghadiri</th>
                        <th class="px-3 py-3">Catatan Prokopim</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($agendas as $agenda)
                        @php
                            $prokopimLabel = config('agpim.statuses')[$agenda->status] ?? $agenda->status;
                            $prokopimNote = $agenda->disposition_note ?: $agenda->verification_note;
                        @endphp
                        <tr @click="modal = 'agenda-{{ $agenda->id }}'" title="Klik untuk melihat detail" class="cursor-pointer border-b border-slate-100 align-top last:border-b-0">
                            <td class="px-3 py-4 text-center text-sm text-muted">{{ $loop->iteration + (($agendas->currentPage() - 1) * $agendas->perPage()) }}</td>
                            <td class="px-3 py-4">
                                <p class="font-medium text-ink">{{ $agenda->event_date->translatedFormat('D, d M Y') }}</p>
                                <p class="mt-0.5 font-mono text-xs text-muted">{{ $agenda->time_range_display }}</p>
                            </td>
                            <td class="px-3 py-4">
                                <p class="font-medium text-ink">{{ $agenda->title }}</p>
                                <p class="mt-0.5 text-xs text-muted">{{ $agenda->type->name ?? '-' }} · {{ $agenda->opd->alias ?? $agenda->opd->name ?? '-' }}</p>
                            </td>
                            <td class="px-3 py-4">
                                <span data-agenda-status="{{ $agenda->id }}"><x-ui.status-pill :status="$agenda->status" :text="$prokopimLabel" /></span>
                            </td>
                            <td class="px-3 py-4" @click.stop>
                                <div class="flex flex-nowrap gap-1.5">
                                    @if ($isVerificationMode && $agenda->status === 'submitted')
                                        <button type="button" class="btn-primary !px-2.5 !py-1 !text-xs" @click="modal = 'agenda-{{ $agenda->id }}'">Verifikasi</button>
                                    @endif
                                    @if (! $isVerificationMode && in_array($agenda->status, ['submitted', 'needs_revision'], true))
                                        <button type="button" class="btn-secondary !px-2.5 !py-1 !text-xs" @click="modal = 'edit-{{ $agenda->id }}'">{{ $agenda->status === 'needs_revision' ? 'Revisi' : 'Edit' }}</button>
                                    @endif
                                    @if ($isVerificationMode && $hasWhatsappFor($agenda))
                                        @include('agendas.partials.wa-button', ['agenda' => $agenda, 'compact' => true])
                                    @endif
                                    @if ($isVerificationMode && $agenda->verified_at && $agenda->invitation_letter_path && auth()->user()?->hasRole('super_admin', 'admin_prokopim', 'bupati', 'wakil_bupati', 'sekda'))
                                        <button type="button" class="btn-secondary !px-2.5 !py-1 !text-xs" @click="modal = 'send-invitation-{{ $agenda->id }}'">Undangan</button>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-4">
                                <p class="text-ink">{{ $agenda->location }}</p>
                            </td>
                            <td class="px-3 py-4">
                                <p class="text-ink">{{ $agenda->person_in_charge ?: '-' }}</p>
                                <p class="mt-0.5 text-xs text-muted">{{ $agenda->leader_target ? (config('agpim.dispositions')[$agenda->leader_target] ?? $agenda->leader_target) : 'Belum ditentukan' }}</p>
                            </td>
                            <td class="px-3 py-4 text-sm text-muted">
                                {{ $prokopimNote ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">
                                @if($isVerificationMode)
                                    Belum ada agenda dari pengaju OPD yang cocok dengan filter saat ini.
                                @else
                                    Belum ada agenda yang cocok dengan filter saat ini.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

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
            $agendaModalKey = 'agenda-'.$agenda->id;
            $verifyModalKey = $agendaModalKey;
            $sendInvitationModalKey = 'send-invitation-'.$agenda->id;
            $canVerify = $isVerificationMode && in_array($agenda->status, ['submitted', 'approved', 'needs_revision', 'rejected', 'cancelled'], true);
            $isDecided = $agenda->status !== 'submitted';
            $canCancel = $agenda->status === 'approved';
            $hasWhatsapp = collect([$agenda->pic_phone, $agenda->submitter?->phone])->contains(fn ($phone) => $phone && \App\Support\WaPhoneFormatter::normalizeIndonesian((string) $phone));
            $canInvite = $isVerificationMode && $agenda->verified_at && $agenda->invitation_letter_path
                && auth()->user()?->hasRole('super_admin', 'admin_prokopim', 'bupati', 'wakil_bupati', 'sekda');
            $canEdit = ! $isVerificationMode && in_array($agenda->status, ['submitted', 'needs_revision'], true);

            // Dokumen yang bisa dipreview di popup.
            $previewItems = collect([
                ['label' => 'Telaahan / surat undangan', 'path' => $agenda->invitation_letter_path],
                ['label' => 'Draft sambutan', 'path' => $agenda->speech_draft_path],
            ])->filter(fn ($doc) => filled($doc['path']))->map(fn ($doc) => [
                'label' => $doc['label'],
                'url' => $resolveStorageUrl($doc['path']),
                'ext' => strtolower(pathinfo($doc['path'], PATHINFO_EXTENSION)),
                'name' => basename($doc['path']),
            ])->values()->all();

            // State form verifikasi (dipulihkan dari old() bila validasi gagal).
            $useVerifyOld = old('modal') === $verifyModalKey;
            $defaultAction = match ($agenda->status) {
                'approved', 'completed' => 'approve',
                'needs_revision' => 'needs_revision',
                'rejected' => 'rejected',
                'cancelled' => 'cancel',
                default => '',
            };
            $attendanceSource = $useVerifyOld ? old('attendance_source', $agenda->attendance_source ?? '') : ($agenda->attendance_source ?? '');
            $selectedAction = $useVerifyOld ? old('action', $defaultAction) : $defaultAction;
            $selectedLeaderTarget = $useVerifyOld ? old('leader_target', $agenda->leader_target ?? '') : ($agenda->leader_target ?? '');
            $selectedInvitationOpdIds = collect(old('opd_ids', [$agenda->opd_id]))->map(fn ($value) => (string) $value)->all();
        @endphp

        {{-- Popup terpadu: detail + dokumen (dengan preview) + verifikasi --}}
        <div x-cloak x-show="modal === '{{ $agendaModalKey }}'" x-transition.opacity class="fixed inset-0 z-[80] flex items-end justify-center bg-black/30 sm:items-center sm:p-4" @click.self="modal = ''">
            <div class="flex max-h-[94vh] w-full max-w-6xl flex-col overflow-hidden rounded-t-2xl border border-line bg-white shadow-[0_24px_60px_rgba(28,27,25,0.22)] sm:rounded-xl" role="dialog" aria-modal="true" aria-label="{{ $agenda->title }}">
                {{-- Kepala --}}
                <div class="flex items-start justify-between gap-4 border-b border-line px-5 py-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span data-agenda-status="{{ $agenda->id }}"><x-ui.status-pill :status="$agenda->status" :text="config('agpim.statuses')[$agenda->status] ?? $agenda->status" /></span>
                            <span class="priority-chip priority-{{ $agenda->priority }}">{{ config('agpim.priorities')[$agenda->priority]['label'] ?? $agenda->priority }}</span>
                        </div>
                        <h2 class="mt-2 text-lg font-semibold leading-snug sm:text-xl">{{ $agenda->title }}</h2>
                        <p class="mt-0.5 text-sm text-muted">Diajukan oleh {{ $agenda->submitter?->name ?? '-' }} · {{ $agenda->opd->name ?? '-' }}</p>
                    </div>
                    <button type="button" class="flex h-8 w-8 flex-none items-center justify-center rounded-md text-muted hover:bg-hover hover:text-ink" @click="modal = ''" aria-label="Tutup">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <div class="block min-h-0 flex-1 overflow-y-auto lg:grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)] lg:overflow-hidden">
                    {{-- Dokumen + preview (kanan di desktop, atas di HP) --}}
                    <section class="max-h-[60vh] min-w-0 overflow-y-auto border-b border-line bg-canvas lg:order-2 lg:max-h-none lg:border-b-0 lg:border-l"
                             x-data="docViewer(@js($previewItems))"
                             x-init="$watch('modal', value => { if (value === '{{ $agendaModalKey }}') $nextTick(() => render()) }); if (modal === '{{ $agendaModalKey }}') $nextTick(() => render())">
                        <template x-if="items.length === 0">
                            <p class="px-5 py-10 text-center text-sm text-muted">Belum ada dokumen yang dilampirkan.</p>
                        </template>
                        <div x-show="items.length > 0">
                            <div class="sticky top-0 z-10 flex items-center gap-1 overflow-x-auto border-b border-line bg-canvas/95 px-3 py-2 backdrop-blur">
                                <template x-for="(item, i) in items" :key="i">
                                    <button type="button" class="flex items-center gap-1.5 whitespace-nowrap rounded-md px-2.5 py-1.5 text-sm transition-colors"
                                            :class="index === i ? 'bg-white font-medium text-ink shadow-[0_1px_2px_rgba(0,0,0,0.08)]' : 'text-muted hover:text-ink'"
                                            @click="select(i)">
                                        <span class="rounded px-1 font-mono text-[10px] uppercase" :class="item.ext === 'pdf' ? 'soft-red text-[#c4321f]' : 'soft-blue text-[#2f4fbf]'" x-text="item.ext || 'file'"></span>
                                        <span x-text="item.label"></span>
                                    </button>
                                </template>
                                <a :href="current ? current.url : '#'" target="_blank" rel="noopener" class="ml-auto flex items-center gap-1 whitespace-nowrap rounded-md px-2.5 py-1.5 text-xs font-medium text-[#2f55d4] hover:bg-hover">
                                    <span class="material-symbols-outlined text-[16px]">open_in_new</span> Buka
                                </a>
                            </div>

                            <div class="p-3 sm:p-4">
                                <p x-show="loading" class="py-10 text-center text-sm text-muted">Menyiapkan pratinjau…</p>
                                <div x-show="kind === 'pdf' && !loading && !failed" x-ref="pages" class="space-y-3"></div>
                                <p x-show="kind === 'pdf' && !loading && !failed && pageCount > pagesShown" class="pt-3 text-center text-xs text-muted"
                                   x-text="'Menampilkan ' + pagesShown + ' dari ' + pageCount + ' halaman. Klik Buka untuk melihat semuanya.'"></p>
                                <template x-if="kind === 'image'">
                                    <img :src="current.url" :alt="current.label" class="mx-auto max-h-[70vh] w-auto rounded-md border border-line bg-white">
                                </template>
                                <div x-show="kind === 'docx' && !loading && !failed" class="docx-preview rounded-md border border-line bg-white px-5 py-4 text-sm leading-6 text-ink" x-html="html"></div>
                                <div x-show="(kind === 'other' || failed) && !loading" class="rounded-md border border-dashed border-line bg-white px-5 py-10 text-center text-sm text-muted">
                                    Pratinjau tidak tersedia untuk file ini. Klik <span class="font-medium text-ink">Buka</span> untuk melihat isinya.
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- Detail + keputusan (kiri di desktop) --}}
                    <section class="min-w-0 px-5 py-4 lg:order-1 lg:overflow-y-auto" x-data="verifyForm({ action: @js($selectedAction), attendanceSource: @js($attendanceSource), agendaId: {{ $agenda->id }}, waEndpoint: @js(route('agendas.whatsapp-status', $agenda)), hasPhone: @js($hasWhatsapp) })">
                        <dl class="grid gap-x-6 sm:grid-cols-2">
                            <div class="detail-card"><span>Tanggal</span><strong>{{ $agenda->event_date->translatedFormat('l, j F Y') }}</strong></div>
                            <div class="detail-card"><span>Waktu</span><strong class="font-mono">{{ $agenda->time_range_display }}</strong></div>
                            <div class="detail-card"><span>Tempat</span><strong>{{ $agenda->location }}</strong></div>
                            <div class="detail-card"><span>Jenis kegiatan</span><strong>{{ $agenda->type->name ?? '-' }}</strong></div>
                            <div class="detail-card"><span>Pejabat yang diminta</span><strong>{{ config('agpim.dispositions')[$agenda->leader_target] ?? 'Belum dipilih' }}</strong></div>
                            <div class="detail-card"><span>PIC</span><strong>{{ $agenda->person_in_charge ?: '-' }}@if ($agenda->pic_phone) · {{ $agenda->pic_phone }}@endif</strong></div>
                            <div class="detail-card sm:col-span-2"><span>Deskripsi</span><strong class="font-normal leading-6">{{ $agenda->description }}</strong></div>
                            @if ($agenda->speech_draft_note)
                                <div class="detail-card sm:col-span-2"><span>Catatan draft sambutan</span><strong class="font-normal leading-6">{{ $agenda->speech_draft_note }}</strong></div>
                            @endif
                            @if (! $canVerify && ($agenda->verification_note || $agenda->disposition_note))
                                <div class="detail-card sm:col-span-2"><span>Catatan Prokopim</span><strong class="font-normal leading-6">{{ $agenda->disposition_note ?: $agenda->verification_note }}</strong></div>
                            @endif
                        </dl>

                        {{-- Keputusan yang sudah ada: ringkasan + tombol ubah --}}
                        @if ($canVerify && $isDecided)
                            <div class="mt-2 rounded-lg border border-line bg-canvas p-3.5" x-show="!editing">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0 text-sm">
                                        <p class="text-xs text-muted">Keputusan saat ini</p>
                                        <p class="mt-1 flex flex-wrap items-center gap-2">
                                            <span data-agenda-status="{{ $agenda->id }}"><x-ui.status-pill :status="$agenda->status" :text="config('agpim.statuses')[$agenda->status] ?? $agenda->status" /></span>
                                            @if ($agenda->status === 'approved' && $agenda->leader_target)
                                                <span class="text-ink">{{ config('agpim.dispositions')[$agenda->leader_target] ?? $agenda->leader_target }}{{ $agenda->delegate_name ? ' · '.$agenda->delegate_name : '' }}</span>
                                            @endif
                                        </p>
                                        @if ($agenda->verification_note)
                                            <p class="mt-1.5 text-[13px] text-muted">{{ $agenda->verification_note }}</p>
                                        @endif
                                        <p class="mt-1.5 text-xs text-muted">
                                            Oleh {{ $agenda->reviewer?->name ?? '-' }}@if ($agenda->verified_at) · {{ $agenda->verified_at->translatedFormat('j M Y, H:i') }}@endif
                                        </p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" class="btn-secondary !px-3 !py-1.5 !text-xs" @click="editing = true">
                                            <span class="material-symbols-outlined text-[16px]">edit</span> Ubah keputusan
                                        </button>
                                        @if ($hasWhatsapp)
                                            <button type="button" @click="sendWhatsapp()" :disabled="sendingWa" class="btn-primary !px-3 !py-1.5 !text-xs" style="background: linear-gradient(135deg, #1fa855 0%, #6fd49a 100%)">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.2-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 11.9 11.9 0 0 0 4.6 4c1.7.7 2.4.8 3.2.7.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.2-.2-.4-.3Z"/></svg>
                                                <span x-text="sendingWa ? 'Menyiapkan…' : 'Kirim status ke WhatsApp'">Kirim status ke WhatsApp</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                                @if ($agenda->status === 'needs_revision' && $hasWhatsapp)
                                    <p class="mt-2.5 border-t border-line pt-2.5 text-xs text-muted">
                                        Pesan WhatsApp akan berisi link revisi sekali pakai. Link yang pernah dikirim sebelumnya otomatis tidak berlaku.
                                    </p>
                                @endif
                                @unless ($hasWhatsapp)
                                    <p class="mt-2.5 border-t border-line pt-2.5 text-xs text-muted">Nomor WhatsApp pengaju tidak tersedia, jadi status tidak bisa dikirim lewat WhatsApp.</p>
                                @endunless
                            </div>
                        @endif

                        @if ($canVerify && ! $isDecided && $hasWhatsapp)
                            <div class="mt-2 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-line bg-canvas px-3.5 py-2.5">
                                <p class="text-xs text-muted">Beri tahu pengaju bahwa pengajuannya sedang diverifikasi.</p>
                                @include('agendas.partials.wa-button', ['agenda' => $agenda, 'compact' => true])
                            </div>
                        @endif

                        @if ($canVerify)
                            <form method="POST" action="{{ route('agendas.verify', $agenda) }}" @submit.prevent="submit($el)" class="mt-2 grid gap-4 border-t border-line pt-4 md:grid-cols-2" @if ($isDecided) x-cloak x-show="editing" @endif>
                                @csrf
                                <input type="hidden" name="modal" value="{{ $verifyModalKey }}">
                                <h3 class="text-sm font-semibold md:col-span-2">{{ $isDecided ? 'Ubah keputusan' : 'Keputusan verifikasi' }}</h3>
                                <div class="form-field md:col-span-2">
                                    <span>Keputusan<em class="req">Wajib diisi</em></span>
                                    <div @class(['mt-2 grid gap-2', 'sm:grid-cols-4' => $canCancel, 'sm:grid-cols-3' => ! $canCancel])>
                                        <label class="block cursor-pointer">
                                            <input type="radio" name="action" value="approve" x-model="action" class="peer sr-only" @checked($selectedAction === 'approve') required>
                                            <span class="flex items-center justify-center rounded-lg border border-line bg-white px-3 py-2 text-sm font-medium text-ink transition peer-checked:border-transparent peer-checked:grad-green peer-checked:text-white">Setujui</span>
                                        </label>
                                        <label class="block cursor-pointer">
                                            <input type="radio" name="action" value="needs_revision" x-model="action" class="peer sr-only" @checked($selectedAction === 'needs_revision') required>
                                            <span class="flex items-center justify-center rounded-lg border border-line bg-white px-3 py-2 text-sm font-medium text-ink transition peer-checked:border-transparent peer-checked:grad-amber peer-checked:text-white">Perlu revisi</span>
                                        </label>
                                        <label class="block cursor-pointer">
                                            <input type="radio" name="action" value="rejected" x-model="action" class="peer sr-only" @checked($selectedAction === 'rejected') required>
                                            <span class="flex items-center justify-center rounded-lg border border-line bg-white px-3 py-2 text-sm font-medium text-ink transition peer-checked:border-transparent peer-checked:grad-red peer-checked:text-white">Tolak</span>
                                        </label>
                                        @if ($canCancel)
                                            <label class="block cursor-pointer">
                                                <input type="radio" name="action" value="cancel" x-model="action" class="peer sr-only" required>
                                                <span class="flex items-center justify-center rounded-lg border border-line bg-white px-3 py-2 text-sm font-medium text-ink transition peer-checked:border-transparent peer-checked:grad-ink peer-checked:text-white">Batalkan</span>
                                            </label>
                                        @endif
                                    </div>
                                    <p x-cloak x-show="action === 'cancel'" class="mt-2 text-xs text-muted">Agenda tidak lagi tampil di jadwal pimpinan. Data disposisi disimpan sebagai riwayat.</p>
                                    @error('action')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror<p x-cloak x-show="err('action')" x-text="err('action')" class="mt-2 text-xs text-red-600"></p>
                                </div>
                                <template x-if="action === 'approve'">
                                <div class="grid gap-4 md:col-span-2 md:grid-cols-2">
                                <label class="form-field md:col-span-2">
                                    <span>Siapa yang akan berangkat?<em class="req">Wajib diisi</em></span>
                                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                        @foreach (config('agpim.attendance_sources') as $sourceKey => $sourceLabel)
                                            <label class="block cursor-pointer">
                                                <input type="radio" name="attendance_source" value="{{ $sourceKey }}" x-model="attendanceSource" class="peer sr-only" @checked($attendanceSource === $sourceKey) required>
                                                <span class="flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 transition peer-checked:border-blue-600 peer-checked:grad-blue peer-checked:text-white">{{ $sourceLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('attendance_source')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror<p x-cloak x-show="err('attendance_source')" x-text="err('attendance_source')" class="mt-2 text-xs text-red-600"></p>
                                </label>
                                <template x-if="attendanceSource === 'proposed'">
                                    <label class="form-field md:col-span-2">
                                        <span>Disposisi ke siapa?<em class="req">Wajib diisi</em></span>
                                        @if ($agenda->leader_target)
                                            <input type="hidden" name="leader_target" value="{{ $agenda->leader_target }}">
                                            <div class="mt-2 rounded-xl border border-blue-200 soft-blue px-3 py-2 text-sm font-semibold text-blue-700">
                                                {{ config('agpim.dispositions')[$agenda->leader_target] ?? $agenda->leader_target }}
                                            </div>
                                        @else
                                            <div class="mt-2 rounded-xl border border-rose-200 soft-red px-3 py-2 text-sm text-rose-700">
                                                Target pengajuan OPD belum tersedia.
                                            </div>
                                        @endif
                                        @error('leader_target')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror<p x-cloak x-show="err('leader_target')" x-text="err('leader_target')" class="mt-2 text-xs text-red-600"></p>
                                    </label>
                                </template>
                                <template x-if="attendanceSource !== 'proposed'">
                                    <label class="form-field md:col-span-2">
                                        <span>Disposisi ke siapa?<em class="req">Wajib diisi</em></span>
                                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                            @foreach (config('agpim.dispositions') as $leaderKey => $leaderLabel)
                                                @if ($leaderKey !== 'rejected' && $leaderKey !== $agenda->leader_target)
                                                    <label class="block cursor-pointer">
                                                        <input type="radio" name="leader_target" value="{{ $leaderKey }}" class="peer sr-only" @checked($selectedLeaderTarget === $leaderKey) required>
                                                        <span class="flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 transition peer-checked:border-blue-600 peer-checked:grad-blue peer-checked:text-white">{{ $leaderLabel }}</span>
                                                    </label>
                                                @endif
                                            @endforeach
                                        </div>
                                        @error('leader_target')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror<p x-cloak x-show="err('leader_target')" x-text="err('leader_target')" class="mt-2 text-xs text-red-600"></p>
                                    </label>
                                </template>
                                <label class="form-field">
                                    <span>Nama pejabat disposisi</span>
                                    <input type="text" name="delegate_name" value="{{ $useVerifyOld ? old('delegate_name', $agenda->delegate_name ?? '') : ($agenda->delegate_name ?? '') }}" placeholder="Nama pejabat yang didisposisikan">
                                    @error('delegate_name')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror<p x-cloak x-show="err('delegate_name')" x-text="err('delegate_name')" class="mt-2 text-xs text-red-600"></p>
                                </label>
                                <label class="form-field">
                                    <span>Jabatan pejabat disposisi</span>
                                    <input type="text" name="delegate_title" value="{{ $useVerifyOld ? old('delegate_title', $agenda->delegate_title ?? '') : ($agenda->delegate_title ?? '') }}" placeholder="Jabatan pejabat yang didisposisikan">
                                    @error('delegate_title')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror<p x-cloak x-show="err('delegate_title')" x-text="err('delegate_title')" class="mt-2 text-xs text-red-600"></p>
                                </label>
                                </div>
                                </template>
                                <label class="form-field md:col-span-2">
                                    <span x-text="action === 'approve' ? 'Catatan verifikasi' : (action === 'cancel' ? 'Alasan pembatalan' : 'Alasan untuk OPD')">Catatan verifikasi</span>
                                    <input type="text" name="note" value="{{ $useVerifyOld ? old('note', $agenda->verification_note ?? '') : ($agenda->verification_note ?? '') }}" placeholder="Catatan untuk OPD / internal Prokopim">
                                    @error('note')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror<p x-cloak x-show="err('note')" x-text="err('note')" class="mt-2 text-xs text-red-600"></p>
                                </label>
                                <div class="flex flex-wrap items-center justify-end gap-2 md:col-span-2">
                                    <p x-cloak x-show="saved && !hasPhone" class="mr-auto text-xs text-muted">Nomor WhatsApp pengaju tidak tersedia.</p>
                                    <button type="button" class="btn-secondary" @click="if (saved || !{{ $isDecided ? 'true' : 'false' }}) { modal = '' } else { editing = false }" x-text="saved ? 'Tutup' : 'Batal'">Batal</button>
                                    <button type="submit" class="btn-primary" :disabled="saving" :class="saving && 'opacity-70'">
                                        <span x-show="saving" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                                        <span x-text="saving ? 'Menyimpan…' : (saved ? 'Simpan perubahan' : 'Simpan keputusan')">Simpan keputusan</span>
                                    </button>
                                    <button type="button" x-cloak x-show="saved && hasPhone" @click="sendWhatsapp()" :disabled="sendingWa" class="btn-primary" style="background: linear-gradient(135deg, #1fa855 0%, #6fd49a 100%)">
                                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.2-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 11.9 11.9 0 0 0 4.6 4c1.7.7 2.4.8 3.2.7.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.2-.2-.4-.3Z"/></svg>
                                        <span x-text="sendingWa ? 'Menyiapkan…' : 'Kirim ke WhatsApp'">Kirim ke WhatsApp</span>
                                    </button>
                                </div>
                            </form>
                        @endif

                        @if ($canEdit || $canInvite)
                            <div class="mt-2 flex flex-wrap justify-end gap-2 border-t border-line pt-4">
                                @if ($canEdit)
                                    <button type="button" class="btn-primary" @click="modal = 'edit-{{ $agenda->id }}'">{{ $agenda->status === 'needs_revision' ? 'Revisi pengajuan' : 'Edit pengajuan' }}</button>
                                @endif
                                @if ($canInvite)
                                    <button type="button" class="btn-secondary" @click="modal = '{{ $sendInvitationModalKey }}'">Kirim undangan</button>
                                @endif
                            </div>
                        @endif
                    </section>
                </div>
            </div>
        </div>

        @if (! $isVerificationMode && in_array($agenda->status, ['submitted', 'needs_revision'], true))
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

        @if ($canInvite)
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
                                    <span class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm text-slate-700 transition peer-checked:border-emerald-600 peer-checked:grad-green peer-checked:text-white">
                                        <span>{{ $opdOption->name }}</span>
                                        @if ($agenda->opd_id === $opdOption->id)
                                            <span class="text-[10px] font-semibold opacity-80">Pengaju</span>
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
@endsection
