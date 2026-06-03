@extends('layouts.app', ['title' => 'AGPIM • Landing'])

@section('content')
<div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
    <section class="card-panel p-7">
        <span class="badge-gold">Transformasi Digital Agenda Pimpinan</span>
        <h2 class="mt-5 text-4xl font-semibold leading-tight text-white">Dashboard agenda pimpinan yang cepat, sederhana, dan tetap siap saat jaringan tidak stabil.</h2>
        <p class="mt-4 max-w-2xl text-base leading-7 text-slate-300">AGPIM menghubungkan OPD, Prokopim, Bupati, Wakil Bupati, dan Sekretaris Daerah dalam satu alur pengajuan undangan, verifikasi, disposisi, monitoring, tindak lanjut, dan dokumentasi kegiatan.</p>
        <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="metric-card">
                <span>Total Agenda</span>
                <strong>{{ $stats['agendaCount'] }}</strong>
            </div>
            <div class="metric-card">
                <span>Prioritas Tinggi</span>
                <strong>{{ $stats['highPriorityCount'] }}</strong>
            </div>
            <div class="metric-card">
                <span>OPD Terdaftar</span>
                <strong>{{ $stats['opdCount'] }}</strong>
            </div>
            <div class="metric-card">
                <span>Jenis Kegiatan</span>
                <strong>{{ $stats['typeCount'] }}</strong>
            </div>
        </div>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('dashboard') }}" class="btn-primary">Lihat Dashboard</a>
            <a href="{{ route('agendas.index') }}" class="btn-secondary">Lihat Blueprint Modul</a>
        </div>
    </section>

    <section class="card-panel p-7">
        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-sky-200/80">Workflow Pengajuan</p>
        <ol class="mt-5 space-y-3">
            @foreach ($workflow as $key => $label)
                <li class="flex items-center gap-3 rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-slate-200">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-sky-500/15 text-sky-200">{{ $loop->iteration }}</span>
                    <div>
                        <p class="font-medium text-white">{{ $label }}</p>
                        <p class="text-xs text-slate-400">{{ str($key)->replace('_', ' ')->title() }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>
</div>

<section class="mt-6 grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
    <div class="card-panel p-7">
        <p class="section-label">Fokus Produk</p>
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
    </div>

    <div class="card-panel p-7">
        <p class="section-label">Master Jenis Kegiatan</p>
        <div class="mt-4 flex flex-wrap gap-3">
            @foreach ($agendaTypes as $agendaType)
                <span class="rounded-full border border-sky-300/20 bg-sky-500/10 px-4 py-2 text-sm text-sky-100">{{ $agendaType->name }}</span>
            @endforeach
        </div>
        <div class="mt-8 rounded-3xl border border-amber-300/20 bg-gradient-to-br from-amber-400/10 to-transparent p-5">
            <h3 class="text-lg font-semibold text-white">Arsitektur siap produksi</h3>
            <ul class="mt-4 space-y-3 text-sm text-slate-300">
                <li>Laravel 12 + Blade + TailwindCSS + AlpineJS</li>
                <li>Schema domain AGPIM: agenda, disposisi, dokumen, audit, notifikasi, snapshot sinkronisasi</li>
                <li>Progressive Web App untuk akses jaringan terbatas</li>
                <li>Role isolation: Super Admin, Prokopim, Bupati, Wakil Bupati, Sekda, OPD</li>
            </ul>
        </div>
    </div>
</section>
@endsection
