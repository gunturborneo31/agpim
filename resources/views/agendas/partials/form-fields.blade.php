@php
    $agendaItem = $agendaItem ?? null;
    $modalKey = $modalKey ?? 'create';
    $useOld = old('modal') === $modalKey;
    $currentValue = static fn (string $field, mixed $fallback = null) => $useOld ? old($field, $fallback) : $fallback;

    $invitationPath = $agendaItem?->invitation_letter_path;
    $speechPath = $agendaItem?->speech_draft_path;
    $visualContentSelection = array_filter((array) $currentValue('visual_content', $agendaItem?->visual_content ?? []));
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

<div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm font-semibold text-slate-900">Konten Visual</p>
        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Materi · Status AK · Status Prokopim</p>
    </div>
    <div class="mt-3 grid gap-3 text-xs uppercase tracking-[0.18em] text-slate-500 sm:grid-cols-3">
        <span>Materi</span>
        <span>Status AK</span>
        <span>Status Prokopim</span>
    </div>
    <div class="mt-3 grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <p class="text-sm font-medium text-slate-700">Materi konten visual</p>
            <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-2">
                @foreach (['visual ucapan', 'ucapan duka', 'spanduk', 'baliho', 'foto kegiatan/carosel', 'visual berita/infografis', 'quotws pipinan', 'informasi lainnya', 'tidak ada', 'lainnya(catatan)'] as $option)
                    <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                        <input type="checkbox" name="visual_content[]" value="{{ $option }}" @checked(in_array($option, $visualContentSelection, true)) class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span>{{ $option }}</span>
                    </label>
                @endforeach
            </div>
            @error('visual_content')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            @error('visual_content.*')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            <label class="form-field mt-4">
                <span>Catatan konten visual</span>
                <input type="text" name="visual_other_note" value="{{ $currentValue('visual_other_note', $agendaItem?->visual_other_note) }}" placeholder="Catatan tambahan visual">
                @error('visual_other_note')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </label>
        </div>

        <label class="form-field">
            <span>Status AK</span>
            <select name="visual_status_ak">
                <option value="">Pilih status</option>
                @foreach (['Belum', 'menunggu', 'sedang dikerjakan', 'selesai'] as $statusOption)
                    <option value="{{ $statusOption }}" @selected($currentValue('visual_status_ak', $agendaItem?->visual_status_ak) === $statusOption)>{{ $statusOption }}</option>
                @endforeach
            </select>
            @error('visual_status_ak')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
        </label>

        <label class="form-field">
            <span>Status Prokopim</span>
            <select name="visual_status_prokopim">
                <option value="">Pilih status</option>
                @foreach (['belum ada bahan', 'sudah ada bahan dokumentasi', 'belum diperiksa', 'perlu revisi', 'ok tayang'] as $statusOption)
                    <option value="{{ $statusOption }}" @selected($currentValue('visual_status_prokopim', $agendaItem?->visual_status_prokopim) === $statusOption)>{{ $statusOption }}</option>
                @endforeach
            </select>
            @error('visual_status_prokopim')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
        </label>
    </div>
</div>

<div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 mt-4">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm font-semibold text-slate-900">Konten Video</p>
        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Materi · Status AK · Status Prokopim</p>
    </div>
    <div class="mt-3 grid gap-3 text-xs uppercase tracking-[0.18em] text-slate-500 sm:grid-cols-3">
        <span>Materi</span>
        <span>Status AK</span>
        <span>Status Prokopim</span>
    </div>
    <div class="mt-3 grid gap-3 sm:grid-cols-3">
        <label class="form-field">
            <span>Materi konten video</span>
            <input type="text" name="video_content" value="{{ $currentValue('video_content', $agendaItem?->video_content) }}" placeholder="Judul atau jenis video yang dibutuhkan">
            @error('video_content')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
        </label>

        <label class="form-field">
            <span>Status AK</span>
            <select name="video_status_ak">
                <option value="">Pilih status</option>
                @foreach (['Belum', 'menunggu', 'sedang dikerjakan', 'selesai'] as $statusOption)
                    <option value="{{ $statusOption }}" @selected($currentValue('video_status_ak', $agendaItem?->video_status_ak) === $statusOption)>{{ $statusOption }}</option>
                @endforeach
            </select>
            @error('video_status_ak')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
        </label>

        <label class="form-field">
            <span>Status Prokopim</span>
            <select name="video_status_prokopim">
                <option value="">Pilih status</option>
                @foreach (['belum ada bahan', 'sudah ada bahan dokumentasi', 'belum diperiksa', 'perlu revisi', 'ok tayang'] as $statusOption)
                    <option value="{{ $statusOption }}" @selected($currentValue('video_status_prokopim', $agendaItem?->video_status_prokopim) === $statusOption)>{{ $statusOption }}</option>
                @endforeach
            </select>
            @error('video_status_prokopim')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
        </label>
    </div>
</div>