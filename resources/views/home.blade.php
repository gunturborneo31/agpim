@extends('layouts.app', ['title' => 'AGPIM • Landing'])

@section('content')
<div class="grid gap-4 md:gap-6 lg:grid-cols-[1.2fr_0.8fr]">
    <x-ui.panel>
        <span class="badge-gold">Transformasi Digital Agenda Pimpinan</span>
        <h2 class="mt-4 text-3xl font-bold leading-tight text-slate-900 md:mt-5 md:text-4xl">Dashboard agenda pimpinan yang cepat, sederhana, dan tetap siap saat jaringan tidak stabil.</h2>
        <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">AGPIM menghubungkan OPD, Prokopim, Bupati, Wakil Bupati, dan Sekretaris Daerah dalam satu alur pengajuan undangan, verifikasi, disposisi, monitoring, tindak lanjut, dan dokumentasi kegiatan.</p>
        <div class="mt-6 grid gap-3 sm:grid-cols-2 md:mt-8 md:gap-4 xl:grid-cols-4">
            <x-ui.metric-card label="Total Agenda" :value="$stats['agendaCount']" />
            <x-ui.metric-card label="Prioritas Tinggi" :value="$stats['highPriorityCount']" />
            <x-ui.metric-card label="OPD Terdaftar" :value="$stats['opdCount']" />
            <x-ui.metric-card label="Jenis Kegiatan" :value="$stats['typeCount']" />
        </div>
        <div class="mt-6 flex flex-wrap gap-3 md:mt-8">
            <a href="{{ route('dashboard') }}" class="btn-primary">Lihat Dashboard</a>
            <a href="{{ route('agendas.index') }}" class="btn-secondary">Lihat Blueprint Modul</a>
        </div>
    </x-ui.panel>

    <x-ui.panel>
        <x-ui.section-heading eyebrow="Workflow Pengajuan" />
        <ol class="mt-5 space-y-3">
            @foreach ($workflow as $key => $label)
                <li class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-blue-700">{{ $loop->iteration }}</span>
                    <div>
                        <p class="font-semibold text-slate-900">{{ $label }}</p>
                        <p class="text-xs text-slate-500">{{ str($key)->replace('_', ' ')->title() }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </x-ui.panel>
</div>

<section class="mt-4 grid gap-4 md:mt-6 md:gap-6 xl:grid-cols-[0.95fr_1.05fr]">
    <x-ui.panel>
        <x-ui.section-heading eyebrow="Fokus Produk" />
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <article class="feature-card">
                <h3>Dashboard Pimpinan</h3>
                <p>Timeline harian, countdown agenda berikutnya, kartu prioritas tinggi, keputusan menunggu disposisi, dan tab cepat hari ini hingga minggu ini.</p>
            </article>
            <article class="feature-card">
                <h3>Verifikasi Prokopim</h3>
                <p>Catatan verifikasi wajib, audit trail perubahan status, rekomendasi bentrok jadwal, dan disposisi ke pimpinan atau pejabat lain.</p>
            </article>
            <article class="feature-card">
                <h3>PWA Offline</h3>
                <p>Banner offline merah, cache halaman utama, service worker, IndexedDB/local snapshot, dan sinkronisasi otomatis saat koneksi kembali.</p>
            </article>
            <article class="feature-card">
                <h3>Analitik & Arsip</h3>
                <p>Agenda per bulan, per OPD, tingkat kehadiran pimpinan, dokumentasi foto/video/dokumen, serta pelaporan hasil kegiatan.</p>
            </article>
        </div>
    </x-ui.panel>

    <x-ui.panel>
        <x-ui.section-heading eyebrow="Master Jenis Kegiatan" />
        <div class="mt-4 flex flex-wrap gap-3">
            @foreach ($agendaTypes as $agendaType)
                <span class="rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-sm text-blue-700">{{ $agendaType->name }}</span>
            @endforeach
        </div>
        <div class="mt-8 rounded-3xl border border-slate-200 bg-slate-50 p-5">
            <h3 class="text-lg font-semibold text-slate-900">Arsitektur siap produksi</h3>
            <ul class="mt-4 space-y-3 text-sm text-slate-600">
                <li>Laravel 12 + Blade + TailwindCSS + AlpineJS</li>
                <li>Schema domain AGPIM: agenda, disposisi, dokumen, audit, notifikasi, snapshot sinkronisasi</li>
                <li>Progressive Web App untuk akses jaringan terbatas</li>
                <li>Role isolation: Super Admin, Prokopim, Bupati, Wakil Bupati, Sekda, OPD</li>
            </ul>
        </div>
    </x-ui.panel>
</section>
@endsection
