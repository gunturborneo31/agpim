@extends('layouts.app', ['title' => 'AGPIM • Revisi Pengajuan'])

@section('content')
<div class="mx-auto max-w-3xl">
    @if ($done)
        {{-- Revisi berhasil dikirim --}}
        <div class="card-panel p-6 text-center sm:p-8">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full text-[#1d6b40]" style="background: var(--grad-ok)">
                <span class="material-symbols-outlined text-[28px]">task_alt</span>
            </span>
            <h1 class="mt-4 text-xl font-semibold">Revisi terkirim</h1>
            <p class="mx-auto mt-2 max-w-[48ch] text-sm leading-6 text-muted">
                Terima kasih. Pengajuan <span class="font-medium text-ink">{{ $agenda?->title }}</span> sudah diajukan ulang dan masuk antrean verifikasi Prokopim. Link ini tidak dapat digunakan lagi.
            </p>
        </div>
    @elseif ($reason)
        {{-- Link tidak berlaku --}}
        <div class="card-panel p-6 text-center sm:p-8">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full text-[#8e2318]" style="background: var(--grad-bad)">
                <span class="material-symbols-outlined text-[28px]">link_off</span>
            </span>
            <h1 class="mt-4 text-xl font-semibold">Link revisi tidak berlaku</h1>
            <p class="mx-auto mt-2 max-w-[48ch] text-sm leading-6 text-muted">{{ $reason }}</p>
        </div>
    @else
        @php
            $resolveStorageUrl = static fn (?string $path): ?string => $path ? asset('storage/'.$path) : null;
            $value = static fn (string $field, mixed $fallback = null) => old($field, $fallback);
            $start = $agenda->time_unknown ? '' : substr((string) $agenda->start_time, 0, 5);
            $end = $agenda->time_unknown ? '' : substr((string) $agenda->end_time, 0, 5);
        @endphp

        <header>
            <p class="text-sm text-muted">Revisi pengajuan agenda</p>
            <h1 class="text-2xl font-semibold">{{ $agenda->title }}</h1>
            <p class="mt-1 text-sm text-muted">{{ $agenda->opd->name ?? '-' }} · link berlaku sampai {{ $link->expires_at->translatedFormat('j F Y, H:i') }}</p>
        </header>

        @if ($agenda->verification_note)
            <div class="mt-5 rounded-xl px-4 py-3.5 text-sm" style="background: var(--grad-wait)">
                <p class="flex items-center gap-1.5 font-medium text-[#7a4a05]">
                    <span class="material-symbols-outlined text-[18px]">edit_note</span>
                    Catatan revisi dari Prokopim
                </p>
                <p class="mt-1 leading-6 text-[#5c3a07]">{{ $agenda->verification_note }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-5 rounded-xl px-4 py-3 text-sm text-[#8e2318]" style="background: var(--grad-bad)">
                Ada isian yang perlu diperbaiki. Periksa kolom yang ditandai merah.
            </div>
        @endif

        <form method="POST" action="{{ route('revisions.update', $token) }}" enctype="multipart/form-data" class="mt-6 space-y-5">
            @csrf
            @method('PUT')

            <section class="card-panel p-5 md:p-6">
                <h2 class="text-base font-semibold">Detail kegiatan</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="form-field md:col-span-2">
                        <span>Nama kegiatan<em class="req">Wajib diisi</em></span>
                        <input type="text" name="title" value="{{ $value('title', $agenda->title) }}" required>
                        @error('title')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                    <label class="form-field">
                        <span>Jenis kegiatan<em class="req">Wajib diisi</em></span>
                        <select name="agenda_type_id" required>
                            @foreach ($agendaTypes as $agendaType)
                                <option value="{{ $agendaType->id }}" @selected((string) $value('agenda_type_id', $agenda->agenda_type_id) === (string) $agendaType->id)>{{ $agendaType->name }}</option>
                            @endforeach
                        </select>
                        @error('agenda_type_id')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                    <label class="form-field">
                        <span>Tanggal kegiatan<em class="req">Wajib diisi</em></span>
                        <input type="date" name="event_date" value="{{ $value('event_date', $agenda->event_date->toDateString()) }}" required>
                        @error('event_date')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                    <div class="form-field md:col-span-2" x-data="{ unknown: @js((bool) $value('time_unknown', $agenda->time_unknown)) }">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span>Jam mulai - selesai<em class="req">Wajib diisi</em></span>
                            <label class="inline-flex items-center gap-2 text-xs text-muted">
                                <input type="checkbox" name="time_unknown" value="1" x-model="unknown" class="h-4 w-4 rounded">
                                Waktu belum ditentukan
                            </label>
                        </div>
                        <div class="mt-1.5 grid grid-cols-2 gap-3">
                            <input type="time" name="start_time" value="{{ $value('start_time', $start) }}" :disabled="unknown" :required="!unknown">
                            <input type="time" name="end_time" value="{{ $value('end_time', $end) }}" :disabled="unknown" :required="!unknown">
                        </div>
                        @error('start_time')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                        @error('end_time')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <label class="form-field md:col-span-2">
                        <span>Tempat kegiatan<em class="req">Wajib diisi</em></span>
                        <input type="text" name="location" value="{{ $value('location', $agenda->location) }}" required>
                        @error('location')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                    <label class="form-field md:col-span-2">
                        <span>Pejabat yang diharapkan hadir / membuka kegiatan<em class="req">Wajib diisi</em></span>
                        <select name="leader_target" required>
                            @foreach (config('agpim.dispositions') as $leaderKey => $leaderLabel)
                                @continue($leaderKey === 'rejected')
                                <option value="{{ $leaderKey }}" @selected($value('leader_target', $agenda->leader_target) === $leaderKey)>{{ $leaderLabel }}</option>
                            @endforeach
                        </select>
                        @error('leader_target')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                    <x-ui.schedule-check class="md:col-span-2" :exclude-id="$agenda->id" initial />
                    <label class="form-field md:col-span-2">
                        <span>Deskripsi singkat kegiatan<em class="req">Wajib diisi</em></span>
                        <textarea name="description" rows="4" required>{{ $value('description', $agenda->description) }}</textarea>
                        @error('description')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                    <label class="form-field">
                        <span>Penanggung jawab</span>
                        <input type="text" name="person_in_charge" value="{{ $value('person_in_charge', $agenda->person_in_charge) }}">
                        @error('person_in_charge')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                    <label class="form-field">
                        <span>Nomor HP penanggung jawab</span>
                        <input type="text" name="pic_phone" value="{{ $value('pic_phone', $agenda->pic_phone) }}" placeholder="Contoh: 081234567890">
                        @error('pic_phone')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                </div>
            </section>

            <section class="card-panel p-5 md:p-6">
                <h2 class="text-base font-semibold">Dokumen</h2>
                <p class="mt-1 text-sm text-muted">Kosongkan bila dokumen tidak perlu diganti.</p>
                <div class="mt-4 space-y-5">
                    <div>
                        @if ($agenda->invitation_letter_path)
                            <p class="mb-1 text-xs text-muted">File saat ini: <a href="{{ $resolveStorageUrl($agenda->invitation_letter_path) }}" target="_blank" rel="noopener" class="font-medium text-[#2f55d4] hover:underline">{{ basename($agenda->invitation_letter_path) }}</a></p>
                        @endif
                        <x-ui.file-input name="invitation_letter_path" label="Ganti telaahan staf / surat undangan" />
                    </div>
                    <div>
                        @if ($agenda->speech_draft_path)
                            <p class="mb-1 text-xs text-muted">File saat ini: <a href="{{ $resolveStorageUrl($agenda->speech_draft_path) }}" target="_blank" rel="noopener" class="font-medium text-[#2f55d4] hover:underline">{{ basename($agenda->speech_draft_path) }}</a></p>
                        @endif
                        <x-ui.file-input name="speech_draft_path" label="Ganti draft materi / sambutan" />
                    </div>
                    <label class="form-field">
                        <span>Catatan untuk draft materi/sambutan</span>
                        <textarea name="speech_draft_note" rows="3">{{ $value('speech_draft_note', $agenda->speech_draft_note) }}</textarea>
                        @error('speech_draft_note')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                </div>
            </section>

            <div class="flex flex-col-reverse items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-muted">Link ini hanya bisa dipakai satu kali. Setelah dikirim, pengajuan langsung diajukan ulang.</p>
                <button type="submit" class="btn-primary">Kirim revisi</button>
            </div>
        </form>
    @endif
</div>
@endsection
