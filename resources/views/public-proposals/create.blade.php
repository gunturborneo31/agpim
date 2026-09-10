@extends('layouts.app', ['title' => 'AGPIM • Usulan Tanpa Login'])

@section('content')
<x-ui.panel>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-ui.section-heading eyebrow="Usulan Publik" title="Kirim Usulan Agenda Tanpa Login" subtitle="Isi data pengusul dan detail agenda secara manual." />
        <span class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-600">Form manual</span>
    </div>

    @if (session('status'))
        <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('public-proposals.store') }}" enctype="multipart/form-data" class="mt-6 space-y-5 md:mt-8">
        @csrf

        <div class="rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-blue-50 p-5 shadow-sm md:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="section-label">Data Pengusul</p>
                    <h3 class="mt-2 text-lg font-semibold text-slate-900">Informasi pengirim usulan</h3>
                    <p class="mt-1 text-sm text-slate-600">Pastikan data Anda lengkap agar tim dapat memproses usulan dengan cepat.</p>
                </div>
                <span class="rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-blue-700">Wajib diisi</span>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <label class="form-field">
                    <span>Nama pengusul *</span>
                    <input type="text" name="submitter_name" value="{{ old('submitter_name') }}" required>
                    @error('submitter_name')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </label>
                <label class="form-field">
                    <span>Nomor HP pengusul *</span>
                    <input type="text" name="submitter_phone" value="{{ old('submitter_phone') }}" placeholder="Contoh: 081234567890" required>
                    @error('submitter_phone')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </label>
                <label class="form-field">
                    <span>OPD pengusul *</span>
                    <select name="opd_option" id="opd_option" required>
                        <option value="">Pilih OPD</option>
                        @foreach ($opdOptions as $opdOption)
                            <option value="{{ $opdOption }}" @selected(old('opd_option') === $opdOption)>{{ $opdOption }}</option>
                        @endforeach
                        <option value="lainnya" @selected(old('opd_option') === 'lainnya')>Lainnya</option>
                    </select>
                    @error('opd_option')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </label>
            </div>

            <div id="custom-opd-wrapper" class="mt-4 {{ old('opd_option') === 'lainnya' ? '' : 'hidden' }}">
                <div class="rounded-2xl border border-blue-200 bg-white/90 p-4 shadow-sm ring-1 ring-blue-100">
                    <label class="form-field">
                        <span>Nama OPD lain *</span>
                        <input type="text" name="opd_custom_name" id="opd_custom_name" value="{{ old('opd_custom_name') }}" placeholder="Tuliskan nama OPD" class="w-full" {{ old('opd_option') === 'lainnya' ? 'required' : '' }}>
                        @error('opd_custom_name')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    </label>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const select = document.getElementById('opd_option');
                    const wrapper = document.getElementById('custom-opd-wrapper');
                    const input = document.getElementById('opd_custom_name');

                    if (!select || !wrapper || !input) {
                        return;
                    }

                    const toggleCustomOpd = () => {
                        const show = select.value === 'lainnya';
                        wrapper.classList.toggle('hidden', !show);
                        input.required = show;
                        input.setAttribute('aria-hidden', show ? 'false' : 'true');
                    };

                    select.addEventListener('change', toggleCustomOpd);
                    toggleCustomOpd();
                });
            </script>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
            <p class="section-label">Detail Kegiatan</p>
            <h3 class="mt-2 text-lg font-semibold text-slate-900">Informasi agenda yang diajukan</h3>
            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <label class="form-field">
                    <span>Nama kegiatan *</span>
                    <input type="text" name="title" value="{{ old('title') }}" required>
                    @error('title')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </label>
                <label class="form-field">
                    <span>Jenis kegiatan *</span>
                    <select name="agenda_type_id" required>
                        <option value="">Pilih jenis kegiatan</option>
                        @foreach ($agendaTypes as $agendaType)
                            <option value="{{ $agendaType->id }}" @selected((string) old('agenda_type_id') === (string) $agendaType->id)>{{ $agendaType->name }}</option>
                        @endforeach
                    </select>
                    @error('agenda_type_id')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </label>
                <label class="form-field">
                    <span>Tanggal kegiatan *</span>
                    <input type="date" name="event_date" value="{{ old('event_date') }}" required>
                    @error('event_date')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </label>
                <label class="form-field sm:col-span-2 lg:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span>Jam Mulai - Selesai *</span>
                        <label class="inline-flex items-center gap-2 text-xs font-normal normal-case tracking-normal text-slate-600">
                            <input type="checkbox" id="time_unknown" name="time_unknown" value="1" @checked(old('time_unknown')) class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            Waktu belum ditentukan (P.M)
                        </label>
                    </div>
                    <div class="mt-2 grid grid-cols-2 gap-3">
                        <input type="time" id="start_time" name="start_time" value="{{ old('start_time') }}" required>
                        <input type="time" id="end_time" name="end_time" value="{{ old('end_time') }}" required>
                    </div>
                    @error('start_time')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    @error('end_time')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </label>
                <label class="form-field">
                    <span>Tempat kegiatan *</span>
                    <input type="text" name="location" value="{{ old('location') }}" required>
                    @error('location')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </label>
                @auth
                    @if (auth()->user() && auth()->user()->hasRole('super_admin', 'admin_prokopim'))
                        <label class="form-field">
                            <span>Prioritas kegiatan *</span>
                            <select name="priority" required>
                                <option value="">Pilih prioritas</option>
                                @foreach (config('agpim.priorities') as $key => $priority)
                                    <option value="{{ $key }}" @selected(old('priority') === $key)>{{ $priority['label'] }}</option>
                                @endforeach
                            </select>
                            @error('priority')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                        </label>
                    @endif
                @endauth
                <label class="form-field lg:col-span-2">
                    <span>Pejabat yang diharapkan Menghadiri / Membuka Kegiatan *</span>
                    <select name="leader_target" required>
                        <option value="">Pilih pihak yang diajukan</option>
                        @foreach (config('agpim.dispositions') as $leaderKey => $leaderLabel)
                            @if ($leaderKey !== 'rejected')
                                <option value="{{ $leaderKey }}" @selected(old('leader_target') === $leaderKey)>{{ $leaderLabel }}</option>
                            @endif
                        @endforeach
                    </select>
                    @error('leader_target')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </label>
                <label class="form-field lg:col-span-2">
                    <span>Telaahan Staf Persetujuan Kegiatan *</span>
                    <input type="file" name="invitation_letter_path" accept=".pdf,.doc,.docx" required>
                    @error('invitation_letter_path')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </label>
                <label class="form-field lg:col-span-2">
                    <span>Deskripsi singkat kegiatan *</span>
                    <textarea name="description" rows="4" required>{{ old('description') }}</textarea>
                    @error('description')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </label>
                <label class="form-field">
                    <span>Penanggung jawab</span>
                    <input type="text" name="person_in_charge" value="{{ old('person_in_charge') }}">
                    @error('person_in_charge')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </label>
                <label class="form-field">
                    <span>Nomor HP PIC</span>
                    <input type="text" name="pic_phone" value="{{ old('pic_phone') }}" placeholder="Contoh: 081234567890">
                    @error('pic_phone')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </label>
                <div class="form-field lg:col-span-2">
                    <span>Draft Materi/Sambutan & catatan *</span>
                    <input type="file" name="speech_draft_path" accept=".pdf,.doc,.docx" required class="mb-3">
                    @error('speech_draft_path')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    <textarea name="speech_draft_note" rows="3" class="mt-3" placeholder="Catatan tambahan terkait draft materi/sambutan">{{ old('speech_draft_note') }}</textarea>
                    @error('speech_draft_note')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
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

        <div class="flex justify-end">
            <button type="submit" class="btn-primary w-full sm:w-auto">Kirim Usulan</button>
        </div>
    </form>
</x-ui.panel>
@endsection
