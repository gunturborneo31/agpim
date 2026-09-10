@extends('layouts.app', ['title' => 'AGPIM • WhatsApp Blasting'])

@section('content')
<x-ui.panel>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-ui.section-heading eyebrow="Komunikasi" title="WhatsApp Blasting" />
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ url('/whatsapp') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:border-blue-200 hover:text-blue-700">
                <span class="material-symbols-outlined text-[18px] leading-none">apps</span>
                Buka Panel Package
            </a>
            <span class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-semibold uppercase tracking-[0.12em] text-slate-600">{{ $eligibleUsersCount }} penerima siap</span>
        </div>
    </div>

    @if ($errors->has('blast'))
        <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ $errors->first('blast') }}
        </div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900">Pengaturan Akun WA</h2>
            <p class="mt-1 text-sm text-slate-600">Atur backend aktif, sesi Web sidecar, dan ritme blast per penerima.</p>

            <form action="{{ route('wa-blasts.settings.update') }}" method="POST" class="mt-5 space-y-4">
                @csrf
                @method('PUT')

                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700">
                        <input type="checkbox" name="cloud_enabled" value="1" @checked(old('cloud_enabled', $settings->cloud_enabled)) class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        Aktifkan Cloud API
                    </label>
                    <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700">
                        <input type="checkbox" name="web_enabled" value="1" @checked(old('web_enabled', $settings->web_enabled)) class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        Aktifkan Web Sidecar
                    </label>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Backend Default</label>
                    <select name="default_backend" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach (['auto' => 'Auto', 'cloud' => 'Cloud API', 'web' => 'Web Sidecar'] as $key => $label)
                            <option value="{{ $key }}" @selected(old('default_backend', $settings->default_backend) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nama Session Web</label>
                        <input type="text" name="web_session_name" value="{{ old('web_session_name', $settings->web_session_name) }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="main">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Jeda Kirim (detik)</label>
                        <input type="number" min="0" max="120" name="send_delay_seconds" value="{{ old('send_delay_seconds', $settings->send_delay_seconds) }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Footer Otomatis Pesan (opsional)</label>
                    <textarea name="message_footer" rows="2" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Contoh: Balas pesan ini jika butuh bantuan.">{{ old('message_footer', $settings->message_footer) }}</textarea>
                </div>

                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                    <span class="material-symbols-outlined text-[18px] leading-none">save</span>
                    Simpan Pengaturan WA
                </button>
            </form>

            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                <p class="font-semibold text-slate-900">Checklist Kredensial</p>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    @foreach ($credentials as $label => $ready)
                        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2">
                            <span class="text-xs uppercase tracking-[0.1em] text-slate-500">{{ str_replace('_', ' ', $label) }}</span>
                            <span class="text-xs font-semibold {{ $ready ? 'text-emerald-600' : 'text-rose-600' }}">{{ $ready ? 'Terisi' : 'Kosong' }}</span>
                        </div>
                    @endforeach
                </div>
                <p class="mt-3 text-xs text-slate-500">Periksa env + proses sidecar jika mode web digunakan.</p>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900">Buat Blast Baru</h2>
            <p class="mt-1 text-sm text-slate-600">Kirim pengumuman massal ke user AGPIM yang punya nomor WhatsApp.</p>

            <form action="{{ route('wa-blasts.store') }}" method="POST" class="mt-5 space-y-4">
                @csrf

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Judul Blast</label>
                    <input type="text" name="title" value="{{ old('title') }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Contoh: Reminder Agenda Besok">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Isi Pesan</label>
                    <textarea name="message" rows="6" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Tulis pesan blast...">{{ old('message') }}</textarea>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Mode Pengiriman</label>
                        <select name="backend" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach (['auto' => 'Auto', 'cloud' => 'Cloud API', 'web' => 'Web Sidecar'] as $key => $label)
                                <option value="{{ $key }}" @selected(old('backend', 'auto') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Target</label>
                        <select name="target" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="all_users" @selected(old('target', 'all_users') === 'all_users')>Semua User Bertelepon</option>
                            <option value="role" @selected(old('target') === 'role')>Filter Role</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Role Tujuan (jika target = role)</label>
                    <select name="target_role" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Pilih Role</option>
                        @foreach (['opd' => 'OPD', 'bupati' => 'Bupati', 'wakil_bupati' => 'Wakil Bupati', 'sekda' => 'Sekda', 'admin_prokopim' => 'Admin Prokopim'] as $key => $label)
                            <option value="{{ $key }}" @selected(old('target_role') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    <span class="material-symbols-outlined text-[18px] leading-none">send</span>
                    Jadwalkan Blast WA
                </button>
            </form>

            <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800">
                <p class="font-semibold">Operasional Queue</p>
                <p class="mt-1">Jalankan worker: <span class="font-mono">php artisan queue:work</span></p>
                <p class="mt-1">Status sidecar: <span class="font-mono">{{ $webStatusCommand }}</span></p>
                <p class="mt-1">Listener event web: <span class="font-mono">{{ $listenCommand }}</span></p>
            </div>
        </section>
    </div>

    <section class="mt-6 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-semibold text-slate-900">Riwayat Blast</h2>

        <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-200">
            <table class="min-w-full text-sm text-slate-700">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-[0.12em] text-slate-500">
                        <th class="px-4 py-3">Judul</th>
                        <th class="px-4 py-3">Target</th>
                        <th class="px-4 py-3">Backend</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Hasil</th>
                        <th class="px-4 py-3">Dibuat</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($blasts as $blast)
                        <tr class="border-b border-slate-100">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $blast->title }}</td>
                            <td class="px-4 py-3">{{ $blast->target === 'role' ? 'Role: '.$blast->target_role : 'Semua User' }}</td>
                            <td class="px-4 py-3 uppercase">{{ $blast->backend }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $blast->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : ($blast->status === 'failed' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">{{ strtoupper($blast->status) }}</span>
                            </td>
                            <td class="px-4 py-3">{{ $blast->success_count }}/{{ $blast->total_recipients }} berhasil, {{ $blast->failed_count }} gagal</td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ $blast->created_at->translatedFormat('d M Y H:i') }}<br>{{ $blast->creator?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">Belum ada blast WhatsApp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-ui.panel>
@endsection
