<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1247a6">
    <meta name="description" content="AGPIM - Agenda Pimpinan Kabupaten Mahakam Ulu">
    <link rel="manifest" href="/manifest.json">
    <title>{{ $title ?? 'AGPIM' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <div id="offline-banner" class="hidden border-b border-rose-300/40 bg-rose-600 px-4 py-3 text-sm font-semibold text-white">
        Anda sedang offline. Data terakhir diperbarui pada: <span id="last-sync-label">-</span>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <header class="mb-6 rounded-3xl border border-white/10 bg-white/5 p-5 shadow-2xl shadow-slate-950/40 backdrop-blur">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-sky-200/70">Pemerintah Kabupaten Mahakam Ulu</p>
                    <h1 class="text-3xl font-semibold tracking-tight text-white">AGPIM</h1>
                    <p class="mt-2 max-w-3xl text-sm text-slate-300">Agenda Pimpinan untuk pengajuan undangan, disposisi, dashboard pimpinan, notifikasi, dan arsip kegiatan berbasis PWA.</p>
                </div>
                <nav class="flex flex-wrap gap-2 text-sm">
                    <a class="nav-pill" href="{{ route('home') }}">Beranda</a>
                    <a class="nav-pill" href="{{ route('dashboard') }}">Dashboard</a>
                    <a class="nav-pill" href="{{ route('agendas.index') }}">Pengajuan</a>
                    <a class="nav-pill" href="{{ route('public-agendas.index') }}">Agenda Internal</a>
                </nav>
            </div>
        </header>

        @if (session('status'))
            <div class="mb-6 rounded-2xl border border-emerald-400/30 bg-emerald-500/15 px-4 py-3 text-sm text-emerald-100">
                {{ session('status') }}
            </div>
        @endif

        <main>
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>
</body>
</html>
