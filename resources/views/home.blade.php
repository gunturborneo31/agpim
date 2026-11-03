@extends('layouts.app', ['title' => 'AGPIM • Agenda Pimpinan Mahakam Ulu'])

@section('content')
@php
    $features = [
        ['icon' => 'space_dashboard', 'title' => 'Dashboard pimpinan', 'text' => 'Agenda hari ini, hitung mundur ke agenda berikutnya, prioritas tinggi, dan keputusan yang menunggu disposisi.'],
        ['icon' => 'fact_check', 'title' => 'Verifikasi Prokopim', 'text' => 'Catatan verifikasi, riwayat perubahan status, peringatan jadwal bentrok, dan disposisi ke pejabat lain.'],
        ['icon' => 'wifi_off', 'title' => 'Tetap jalan saat offline', 'text' => 'Halaman utama tersimpan di perangkat dan data tersinkron otomatis saat koneksi kembali.'],
        ['icon' => 'folder_open', 'title' => 'Arsip & laporan', 'text' => 'Rekap agenda per bulan dan per OPD, serta riwayat kehadiran pimpinan.'],
    ];
@endphp

{{-- Pembuka --}}
<section class="grid items-center gap-8 pt-2 lg:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)] lg:gap-12 lg:pt-6">
    <div class="min-w-0">
        <span class="badge-gold">Pemerintah Kabupaten Mahakam Ulu</span>
        <h1 class="mt-4 text-3xl font-semibold leading-[1.15] tracking-tight sm:text-4xl lg:text-[2.75rem]">
            Dashboard agenda pimpinan yang cepat, sederhana, dan tetap siap saat jaringan tidak stabil.
        </h1>
        <p class="mt-4 max-w-[60ch] text-base leading-7 text-muted">
            AGPIM menghubungkan OPD, Prokopim, Bupati, Wakil Bupati, dan Sekretaris Daerah dalam satu alur: pengajuan undangan, verifikasi, disposisi, monitoring, tindak lanjut, dan dokumentasi kegiatan.
        </p>
        <div class="mt-6 flex flex-wrap gap-2.5">
            @auth
                <a href="{{ route('dashboard') }}" class="btn-primary">
                    Buka dashboard
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </a>
            @else
                <a href="{{ route('login') }}" class="btn-primary">
                    Masuk
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </a>
                <a href="{{ route('public-proposals.create') }}" class="btn-secondary">Kirim usulan agenda</a>
            @endauth
        </div>
    </div>

    <div class="highlight-card p-5 sm:p-6">
        <p class="text-sm text-muted">Data AGPIM saat ini</p>
        <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-5">
            @foreach ([
                ['Total agenda', $stats['agendaCount']],
                ['Prioritas tinggi', $stats['highPriorityCount']],
                ['OPD terdaftar', $stats['opdCount']],
                ['Jenis kegiatan', $stats['typeCount']],
            ] as [$label, $value])
                <div>
                    <dt class="text-[13px] text-muted">{{ $label }}</dt>
                    <dd class="mt-0.5 font-mono text-3xl font-medium tracking-tight tabular">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>

{{-- Alur --}}
<section class="mt-14" aria-labelledby="workflow-heading">
    <h2 id="workflow-heading" class="text-lg font-semibold">Alur pengajuan agenda</h2>
    <p class="mt-1 text-sm text-muted">Status yang dilalui setiap undangan, dari draft OPD sampai selesai.</p>
    <ol class="mt-5 grid gap-x-6 gap-y-4 border-t border-line pt-5 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($workflow as $key => $label)
            <li class="flex gap-3">
                <span class="font-mono text-[13px] text-muted tabular">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                <span class="text-sm font-medium">{{ $label }}</span>
            </li>
        @endforeach
    </ol>
</section>

{{-- Fitur --}}
<section class="mt-14" aria-labelledby="features-heading">
    <h2 id="features-heading" class="text-lg font-semibold">Yang bisa dilakukan</h2>
    <div class="mt-5 grid gap-x-8 gap-y-6 border-t border-line pt-5 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($features as $feature)
            <article class="min-w-0">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg text-[#2f55d4] soft-blue">
                    <span class="material-symbols-outlined text-[20px]">{{ $feature['icon'] }}</span>
                </span>
                <h3 class="mt-3 text-[15px] font-semibold">{{ $feature['title'] }}</h3>
                <p class="mt-1 text-sm leading-6 text-muted">{{ $feature['text'] }}</p>
            </article>
        @endforeach
    </div>
</section>

{{-- Jenis kegiatan --}}
<section class="mt-14 mb-4" aria-labelledby="types-heading">
    <h2 id="types-heading" class="text-lg font-semibold">Jenis kegiatan</h2>
    <div class="mt-4 flex flex-wrap gap-2">
        @foreach ($agendaTypes as $agendaType)
            <span class="rounded-full px-3 py-1 text-sm text-[#2f4fbf] soft-blue">{{ $agendaType->name }}</span>
        @endforeach
    </div>
</section>
@endsection
