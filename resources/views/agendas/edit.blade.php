@extends('layouts.app', ['title' => 'AGPIM • Edit Pengajuan Agenda'])

@section('content')
@php
    $resolveStorageUrl = static function (?string $path): ?string {
        if (! $path) {
            return null;
        }

        $url = \Illuminate\Support\Facades\Storage::url($path);
        $relativePath = parse_url($url, PHP_URL_PATH);

        return $relativePath ?: $url;
    };
@endphp

<x-ui.panel>
    <x-ui.section-heading eyebrow="Edit Pengajuan" title="Perbarui data agenda" />
    <p class="mt-3 text-sm text-slate-600">Agenda masih berstatus diajukan, sehingga data dapat diperbarui sebelum diverifikasi.</p>

    <form method="POST" action="{{ route('agendas.update', $agenda) }}" enctype="multipart/form-data" class="mt-6 space-y-4">
        @csrf
        @method('PUT')

        <div class="grid gap-3 sm:grid-cols-2 md:gap-4">
            <label class="form-field">
                <span>Nama kegiatan *</span>
                <input type="text" name="title" value="{{ old('title', $agenda->title) }}" required>
                @error('title')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </label>
            <label class="form-field">
                <span>Jenis kegiatan *</span>
                <select name="agenda_type_id" required>
                    <option value="">Pilih jenis kegiatan</option>
                    @foreach ($agendaTypes as $agendaType)
                        <option value="{{ $agendaType->id }}" @selected((string) old('agenda_type_id', $agenda->agenda_type_id) === (string) $agendaType->id)>{{ $agendaType->name }}</option>
                    @endforeach
                </select>
                @error('agenda_type_id')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </label>
            <label class="form-field">
                <span>Tanggal kegiatan *</span>
                <input type="date" name="event_date" value="{{ old('event_date', $agenda->event_date?->toDateString()) }}" required>
                @error('event_date')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </label>
            <label class="form-field sm:col-span-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span>Jam Mulai - Selesai *</span>
                    <label class="inline-flex items-center gap-2 text-xs font-normal normal-case tracking-normal text-slate-600">
                        <input type="checkbox" id="time_unknown" name="time_unknown" value="1" @checked(old('time_unknown', $agenda->time_unknown)) class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        Waktu belum ditentukan (P.M)
                    </label>
                </div>
                <div class="mt-2 grid grid-cols-2 gap-3">
                    <input type="time" id="start_time" name="start_time" value="{{ old('start_time', $agenda->start_time) }}" required>
                    <input type="time" id="end_time" name="end_time" value="{{ old('end_time', $agenda->end_time) }}" required>
                </div>
                @error('start_time')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                @error('end_time')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </label>
            <label class="form-field">
                <span>Lokasi kegiatan *</span>
                <input type="text" name="location" value="{{ old('location', $agenda->location) }}" required>
                @error('location')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </label>
            <label class="form-field">
                <span>Prioritas kegiatan *</span>
                <select name="priority" required>
                    <option value="">Pilih prioritas</option>
                    @foreach (config('agpim.priorities') as $key => $priority)
                        <option value="{{ $key }}" @selected(old('priority', $agenda->priority) === $key)>{{ $priority['label'] }}</option>
                    @endforeach
                </select>
                @error('priority')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </label>
            <label class="form-field">
                <span>Pejabat yang diharapkan Menghadiri / Membuka Kegiatan *</span>
                <select name="leader_target" required>
                    <option value="">Pilih pihak yang diajukan OPD</option>
                    @foreach (config('agpim.dispositions') as $leaderKey => $leaderLabel)
                        @if ($leaderKey !== 'rejected')
                            <option value="{{ $leaderKey }}" @selected(old('leader_target', $agenda->leader_target) === $leaderKey)>{{ $leaderLabel }}</option>
                        @endif
                    @endforeach
                </select>
                @error('leader_target')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </label>
            <label class="form-field sm:col-span-2">
                <span>Deskripsi singkat kegiatan *</span>
                <textarea name="description" rows="4" required>{{ old('description', $agenda->description) }}</textarea>
                @error('description')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </label>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const timeUnknown = document.getElementById('time_unknown');
                const startTime = document.getElementById('start_time');
                const endTime = document.getElementById('end_time');

                if (!timeUnknown || !startTime || !endTime) {
                    return;
                }

                const toggleTimeFields = () => {
                    const unknown = timeUnknown.checked;
                    startTime.disabled = unknown;
                    endTime.disabled = unknown;
                    startTime.required = !unknown;
                    endTime.required = !unknown;
                };

                timeUnknown.addEventListener('change', toggleTimeFields);
                toggleTimeFields();
            });
        </script>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Dokumen</p>
            <div class="mt-3 grid gap-3 sm:grid-cols-2 md:gap-4">
                <label class="form-field sm:col-span-2">
                    <span>Telaahan Staf Persetujuan Kegiatan (unggah ulang jika ingin ganti)</span>
                    <input type="file" name="invitation_letter_path" accept=".pdf,.doc,.docx">
                    @if ($agenda->invitation_letter_path)
                        <p class="mt-2 text-xs text-slate-500">File saat ini: <a href="{{ $resolveStorageUrl($agenda->invitation_letter_path) }}" target="_blank" rel="noopener" class="font-medium text-blue-700 hover:text-blue-800">Buka file</a></p>
                    @endif
                    @error('invitation_letter_path')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </label>
                <label class="form-field sm:col-span-2">
                    <span>Draft Materi/Sambutan & catatan (opsional, unggah ulang jika ingin ganti)</span>
                    <input type="file" name="speech_draft_path" accept=".pdf,.doc,.docx" class="mb-3">
                    @if ($agenda->speech_draft_path)
                        <p class="mt-2 text-xs text-slate-500">File saat ini: <a href="{{ $resolveStorageUrl($agenda->speech_draft_path) }}" target="_blank" rel="noopener" class="font-medium text-blue-700 hover:text-blue-800">Buka file</a></p>
                    @endif
                    @error('speech_draft_path')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    <textarea name="speech_draft_note" rows="3" class="mt-3" placeholder="Catatan tambahan terkait draft materi/sambutan">{{ old('speech_draft_note', $agenda->speech_draft_note) }}</textarea>
                    @error('speech_draft_note')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </label>
            </div>
        </div>

        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700">
            Simpan perubahan untuk memperbarui data pengajuan.
            <div class="mt-3 flex flex-wrap gap-3">
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
                <a href="{{ route('agendas.index') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Kembali</a>
            </div>
        </div>
    </form>
</x-ui.panel>
@endsection
