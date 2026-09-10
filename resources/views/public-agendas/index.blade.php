@extends('layouts.app', ['title' => 'AGPIM • Resume Agenda'])

@section('content')
@php
    $resolveStorageUrl = static function (?string $path): ?string {
        if (! $path) {
            return null;
        }

        return asset('storage/'.$path);
    };
@endphp
<div x-data="{ modal: '' }" @keydown.escape.window="modal = ''" class="space-y-4 md:space-y-6">
<x-ui.panel>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <x-ui.section-heading eyebrow="Resume" title="Ringkasan Pengajuan OPD" subtitle="Pantau jumlah agenda diajukan, ditolak, dan disetujui per OPD." />
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="window.print()" class="btn-secondary no-print">Cetak Resume</button>
            <form method="GET" action="{{ route('public-agendas.index') }}" class="grid gap-3 sm:grid-cols-3">
            <label class="form-field">
                <span>Bulan</span>
                <select name="month" onchange="this.form.submit()">
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @selected($month === $m)>{{ \Illuminate\Support\Carbon::createFromDate($year, $m, 1)->translatedFormat('F') }}</option>
                    @endfor
                </select>
            </label>
            <label class="form-field">
                <span>Tahun</span>
                <select name="year" onchange="this.form.submit()">
                    @foreach ($yearOptions as $yearOption)
                        <option value="{{ $yearOption }}" @selected($year === $yearOption)>{{ $yearOption }}</option>
                    @endforeach
                </select>
            </label>
            <div class="hidden sm:block"></div>
            @if ($selectedOpdId)
                <input type="hidden" name="opd_id" value="{{ $selectedOpdId }}">
            @endif
            <input type="hidden" name="status" value="{{ $selectedStatus }}">
            </form>
        </div>
    </div>

    <div class="mt-5 rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-700 md:mt-6">
        Periode aktif: {{ $monthLabel }}
    </div>

    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5 md:mt-6">
        <div class="rounded-3xl border border-blue-200 bg-blue-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Total Semua</p>
            <p class="mt-2 text-3xl font-bold text-blue-800">{{ $totals['all'] }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Total Mengajukan</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $totals['submitted'] }}</p>
        </div>
        <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Disetujui</p>
            <p class="mt-2 text-3xl font-bold text-emerald-800">{{ $totals['approved'] }}</p>
        </div>
        <div class="rounded-3xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Menunggu Disposisi</p>
            <p class="mt-2 text-3xl font-bold text-amber-800">{{ $totals['pending'] }}</p>
        </div>
        <div class="rounded-3xl border border-rose-200 bg-rose-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">Ditolak</p>
            <p class="mt-2 text-3xl font-bold text-rose-800">{{ $totals['rejected'] }}</p>
        </div>
    </div>

    <div class="mt-5 overflow-x-auto rounded-[1.75rem] border border-slate-200 bg-white md:mt-6">
        <table class="min-w-full text-sm text-slate-700">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-[0.18em] text-slate-500">
                    <th class="px-4 py-3">OPD</th>
                    <th class="px-4 py-3">Total Semua</th>
                    <th class="px-4 py-3">Sudah Mengajukan</th>
                    <th class="px-4 py-3">Ditolak</th>
                    <th class="px-4 py-3">Disetujui</th>
                    <th class="px-4 py-3">Menunggu Disposisi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($resumeRows as $row)
                    <tr class="border-b border-slate-100 align-top last:border-b-0">
                        <td class="px-4 py-4 font-semibold text-slate-900">{{ $row['opd_name'] }}</td>
                        <td class="px-4 py-4">
                            <a href="{{ route('public-agendas.index', ['month' => $month, 'year' => $year, 'opd_id' => $row['opd_id'], 'status' => 'all']) }}" class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] {{ $selectedOpdId === $row['opd_id'] && $selectedStatus === 'all' ? 'border-slate-800 bg-slate-800 text-white' : 'border-slate-300 bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                                {{ $row['all'] }} agenda
                            </a>
                        </td>
                        <td class="px-4 py-4">
                            <a href="{{ route('public-agendas.index', ['month' => $month, 'year' => $year, 'opd_id' => $row['opd_id'], 'status' => 'submitted']) }}" class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] {{ $selectedOpdId === $row['opd_id'] && $selectedStatus === 'submitted' ? 'border-blue-700 bg-blue-700 text-white' : 'border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                                {{ $row['submitted'] }} agenda
                            </a>
                        </td>
                        <td class="px-4 py-4">
                            <a href="{{ route('public-agendas.index', ['month' => $month, 'year' => $year, 'opd_id' => $row['opd_id'], 'status' => 'rejected']) }}" class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] {{ $selectedOpdId === $row['opd_id'] && $selectedStatus === 'rejected' ? 'border-rose-700 bg-rose-700 text-white' : 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100' }}">
                                {{ $row['rejected'] }} agenda
                            </a>
                        </td>
                        <td class="px-4 py-4">
                            <a href="{{ route('public-agendas.index', ['month' => $month, 'year' => $year, 'opd_id' => $row['opd_id'], 'status' => 'approved']) }}" class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] {{ $selectedOpdId === $row['opd_id'] && $selectedStatus === 'approved' ? 'border-emerald-700 bg-emerald-700 text-white' : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                                {{ $row['approved'] }} agenda
                            </a>
                        </td>
                        <td class="px-4 py-4">
                            <a href="{{ route('public-agendas.index', ['month' => $month, 'year' => $year, 'opd_id' => $row['opd_id'], 'status' => 'awaiting_disposition']) }}" class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] {{ $selectedOpdId === $row['opd_id'] && $selectedStatus === 'awaiting_disposition' ? 'border-amber-700 bg-amber-700 text-white' : 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100' }}">
                                {{ $row['pending'] }} agenda
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">Belum ada data resume OPD pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6 rounded-3xl border border-slate-200 bg-slate-50 p-4 md:p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-lg font-bold text-slate-900">Daftar Agenda OPD Terpilih</h3>
            <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-600">
                {{ $selectedOpdName ? $selectedOpdName : 'Belum memilih OPD' }}
            </span>
        </div>

        <div class="mt-4 space-y-3">
            @forelse ($agendaList as $agenda)
                <article class="rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-[0.18em] text-blue-700">{{ $agenda->type->name }}</p>
                            <h4 class="mt-2 text-lg font-semibold text-slate-900">{{ $agenda->title }}</h4>
                            <p class="mt-2 text-sm text-slate-500">{{ $agenda->event_date->translatedFormat('d M Y') }} • {{ $agenda->time_range_display }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $agenda->location }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-ui.status-pill :status="$agenda->status" :text="config('agpim.statuses')[$agenda->status] ?? $agenda->status" />
                            <button type="button" class="btn-secondary" @click="modal = 'detail-{{ $agenda->id }}'">Preview Detail</button>
                        </div>
                    </div>
                </article>
            @empty
                <div class="empty-state">Belum ada agenda untuk filter OPD dan status yang dipilih.</div>
            @endforelse
        </div>
    </div>

    @foreach ($agendaList as $agenda)
        @php
            $previewItems = [];

            if ($agenda->invitation_letter_path) {
                $ext = strtolower(pathinfo($agenda->invitation_letter_path, PATHINFO_EXTENSION));
                $previewItems[] = [
                    'label' => 'Surat Undangan',
                    'url' => $resolveStorageUrl($agenda->invitation_letter_path),
                    'ext' => $ext,
                    'previewable' => in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'], true),
                ];
            }

            if ($agenda->speech_draft_path) {
                $ext = strtolower(pathinfo($agenda->speech_draft_path, PATHINFO_EXTENSION));
                $previewItems[] = [
                    'label' => 'Draft Sambutan',
                    'url' => $resolveStorageUrl($agenda->speech_draft_path),
                    'ext' => $ext,
                    'previewable' => in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'], true),
                ];
            }

            foreach ($agenda->documents as $document) {
                $ext = strtolower(pathinfo($document->file_path, PATHINFO_EXTENSION));
                $previewItems[] = [
                    'label' => $document->title ?: ucfirst(str_replace('_', ' ', $document->category)),
                    'url' => $resolveStorageUrl($document->file_path),
                    'ext' => $ext,
                    'previewable' => in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'], true),
                ];
            }
        @endphp
        <div x-cloak x-show="modal === 'detail-{{ $agenda->id }}'" class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/45 p-4" @click.self="modal = ''">
            <div class="card-panel max-h-[90vh] w-full max-w-5xl overflow-y-auto p-5 md:p-7" x-data="{ items: @js($previewItems), selectedIndex: 0 }">
                <div class="flex items-start justify-between gap-4">
                    <x-ui.section-heading eyebrow="Preview Detail" :title="$agenda->title" subtitle="Detail agenda berdasarkan item resume yang dipilih." />
                    <button type="button" class="rounded-full border border-slate-200 p-2 text-slate-500 hover:text-slate-700" @click="modal = ''">
                        <span class="material-symbols-outlined text-[20px] leading-none">close</span>
                    </button>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 md:mt-6 md:gap-4">
                    <div class="detail-card"><span>Tanggal</span><strong>{{ $agenda->event_date->translatedFormat('d F Y') }}</strong></div>
                    <div class="detail-card"><span>Waktu</span><strong>{{ $agenda->time_range_display }}</strong></div>
                    <div class="detail-card"><span>Lokasi</span><strong>{{ $agenda->location }}</strong></div>
                    <div class="detail-card"><span>OPD</span><strong>{{ $agenda->opd->name }}</strong></div>
                    <div class="detail-card"><span>Jenis</span><strong>{{ $agenda->type->name }}</strong></div>
                    <div class="detail-card"><span>Status</span><strong>{{ config('agpim.statuses')[$agenda->status] ?? $agenda->status }}</strong></div>
                </div>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 md:mt-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Deskripsi</p>
                    <p class="mt-2 leading-7">{{ $agenda->description }}</p>
                </div>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:mt-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Preview Dokumen</p>

                    <template x-if="items.length === 0">
                        <div class="mt-3 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-500">Dokumen belum tersedia.</div>
                    </template>

                    <template x-if="items.length > 0">
                        <div class="mt-3 grid gap-3 md:grid-cols-[260px_1fr]">
                            <div class="space-y-2 rounded-2xl border border-slate-200 bg-white p-3">
                                <template x-for="(item, index) in items" :key="index">
                                    <button type="button" class="w-full rounded-xl border px-3 py-2 text-left text-sm transition"
                                        :class="selectedIndex === index ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-700 hover:border-blue-200'"
                                        @click="selectedIndex = index">
                                        <p class="font-semibold" x-text="item.label"></p>
                                        <p class="mt-1 text-xs text-slate-500" x-text="item.ext ? item.ext.toUpperCase() : 'FILE'"></p>
                                    </button>
                                </template>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white p-3">
                                <template x-if="items[selectedIndex] && items[selectedIndex].previewable && items[selectedIndex].ext === 'pdf'">
                                    <iframe class="h-[52vh] w-full rounded-xl border border-slate-200" :src="items[selectedIndex].url" title="Preview PDF"></iframe>
                                </template>

                                <template x-if="items[selectedIndex] && items[selectedIndex].previewable && ['jpg','jpeg','png','gif','webp'].includes(items[selectedIndex].ext)">
                                    <div class="flex min-h-[52vh] items-center justify-center rounded-xl border border-slate-200 bg-slate-50 p-4">
                                        <img :src="items[selectedIndex].url" alt="Preview gambar" class="max-h-[48vh] w-auto rounded-lg shadow-sm">
                                    </div>
                                </template>

                                <template x-if="items[selectedIndex] && !items[selectedIndex].previewable">
                                    <div class="flex min-h-[52vh] flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                                        <p class="text-sm font-semibold text-slate-700">Preview tidak tersedia untuk format ini.</p>
                                        <p class="mt-1 text-xs text-slate-500">Buka file untuk melihat dokumen.</p>
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
        </div>
    @endforeach
</x-ui.panel>
</div>
@endsection
