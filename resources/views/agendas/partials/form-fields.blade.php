@php
    $agendaItem = $agendaItem ?? null;
    $modalKey = $modalKey ?? 'create';
    $useOld = old('modal') === $modalKey;
    $currentValue = static fn (string $field, mixed $fallback = null) => $useOld ? old($field, $fallback) : $fallback;

    $invitationPath = $agendaItem?->invitation_letter_path;
    $speechPath = $agendaItem?->speech_draft_path;
@endphp

<input type="hidden" name="modal" value="{{ $modalKey }}">

<div class="grid gap-3 sm:grid-cols-2 md:gap-4">
    <label class="form-field">
        <span>Nama kegiatan *</span>
        <input type="text" name="title" value="{{ $currentValue('title', $agendaItem?->title) }}" placeholder="Rapat Infrastruktur" required>
        @error('title')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
    </label>
    <label class="form-field">
        <span>Jenis kegiatan *</span>
        <select name="agenda_type_id" required>
            <option value="">Pilih jenis kegiatan</option>
            @foreach ($agendaTypes as $agendaType)
                <option value="{{ $agendaType->id }}" @selected((string) $currentValue('agenda_type_id', $agendaItem?->agenda_type_id) === (string) $agendaType->id)>{{ $agendaType->name }}</option>
            @endforeach
        </select>
        @error('agenda_type_id')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
    </label>
    <label class="form-field">
        <span>Tanggal kegiatan *</span>
        <input type="date" name="event_date" value="{{ $currentValue('event_date', optional($agendaItem?->event_date)->format('Y-m-d')) }}" required>
        @error('event_date')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
    </label>
    <label class="form-field sm:col-span-2">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <span>Jam Mulai - Selesai *</span>
            <label class="inline-flex items-center gap-2 text-xs font-normal normal-case tracking-normal text-slate-600">
                <input type="checkbox" id="time_unknown_{{ $modalKey }}" name="time_unknown" value="1" @checked($currentValue('time_unknown', $agendaItem?->time_unknown)) class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                Waktu belum ditentukan (P.M)
            </label>
        </div>
        <div class="mt-2 grid grid-cols-2 gap-3">
            <input type="time" id="start_time_{{ $modalKey }}" name="start_time" value="{{ $currentValue('start_time', $agendaItem?->start_time) }}" required>
            <input type="time" id="end_time_{{ $modalKey }}" name="end_time" value="{{ $currentValue('end_time', $agendaItem?->end_time) }}" required>
        </div>
        @error('start_time')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
        @error('end_time')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
    </label>
    <label class="form-field">
        <span>Lokasi kegiatan *</span>
        <input type="text" name="location" value="{{ $currentValue('location', $agendaItem?->location) }}" placeholder="Ruang Rapat Utama" required>
        @error('location')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
    </label>
    <label class="form-field">
        <span>Prioritas kegiatan *</span>
        <select name="priority" required>
            <option value="">Pilih prioritas</option>
            @foreach (config('agpim.priorities') as $key => $priority)
                <option value="{{ $key }}" @selected($currentValue('priority', $agendaItem?->priority) === $key)>{{ $priority['label'] }}</option>
            @endforeach
        </select>
        @error('priority')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
    </label>
    <label class="form-field sm:col-span-2">
        <span>Pejabat yang diharapkan Menghadiri / Membuka Kegiatan *</span>
        <select name="leader_target" required>
            <option value="">Pilih pihak yang diajukan OPD</option>
            @foreach (config('agpim.dispositions') as $leaderKey => $leaderLabel)
                @if ($leaderKey !== 'rejected')
                    <option value="{{ $leaderKey }}" @selected($currentValue('leader_target', $agendaItem?->leader_target) === $leaderKey)>{{ $leaderLabel }}</option>
                @endif
            @endforeach
        </select>
        @error('leader_target')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
    </label>
    <x-ui.schedule-check class="sm:col-span-2" :exclude-id="$agendaItem?->id" />
    <label class="form-field sm:col-span-2">
        <span>Telaahan Staf Persetujuan Kegiatan {{ $agendaItem ? '' : '*' }}</span>
        <input type="file" name="invitation_letter_path" accept=".pdf,.doc,.docx" {{ $agendaItem ? '' : 'required' }}>
        @if ($agendaItem && $invitationPath)
            <p class="mt-2 text-xs text-slate-500">File saat ini: {{ basename($invitationPath) }}</p>
        @endif
        @error('invitation_letter_path')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
    </label>
    <label class="form-field sm:col-span-2">
        <span>Deskripsi singkat kegiatan *</span>
        <textarea name="description" rows="4" placeholder="Ringkasan tujuan dan output kegiatan" required>{{ $currentValue('description', $agendaItem?->description) }}</textarea>
        @error('description')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
    </label>
    <label class="form-field">
        <span>Penanggung jawab</span>
        <input type="text" name="person_in_charge" value="{{ $currentValue('person_in_charge', $agendaItem?->person_in_charge) }}" placeholder="Nama PIC">
        @error('person_in_charge')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
    </label>
    <label class="form-field">
        <span>Nomor HP PIC</span>
        <input type="text" name="pic_phone" value="{{ $currentValue('pic_phone', $agendaItem?->pic_phone) }}" placeholder="Contoh: 081234567890">
        @error('pic_phone')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
    </label>
    <div class="form-field sm:col-span-2">
        <span>Draft Materi/Sambutan & catatan</span>
        <input type="file" name="speech_draft_path" accept=".pdf,.doc,.docx" class="mb-3">
        @if ($agendaItem && $speechPath)
            <p class="mt-2 text-xs text-slate-500">File saat ini: {{ basename($speechPath) }}</p>
        @endif
        @error('speech_draft_path')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
        <textarea name="speech_draft_note" rows="3" class="mt-3" placeholder="Catatan tambahan terkait draft materi/sambutan">{{ $currentValue('speech_draft_note', $agendaItem?->speech_draft_note) }}</textarea>
        @error('speech_draft_note')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const timeUnknown = document.getElementById('time_unknown_{{ $modalKey }}');
        const startTime = document.getElementById('start_time_{{ $modalKey }}');
        const endTime = document.getElementById('end_time_{{ $modalKey }}');

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

