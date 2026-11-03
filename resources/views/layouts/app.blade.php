@php
    // Mode popup (mis. halaman di dalam modal) tampil tanpa topbar dan menu.
    $bare = request()->query('popup') === '1';
    $user = auth()->user();
    $isAdmin = $user?->hasRole('super_admin', 'admin_prokopim') ?? false;
    $isVerifier = $user && ! $user->hasRole('opd');

    // Jumlah pengajuan yang menunggu verifikasi, ditampilkan sebagai badge di menu Agenda.
    $pendingVerification = $isVerifier
        ? \App\Models\Agenda::query()
            ->where('status', 'submitted')
            ->whereHas('submitter', fn ($q) => $q->where('role', \App\Enums\UserRole::Opd->value))
            ->count()
        : 0;

    // Menu utama di dock (maks. 4) + sisanya di panel "Lainnya".
    $dockItems = $user ? [
        ['route' => 'dashboard', 'match' => 'dashboard', 'icon' => 'space_dashboard', 'label' => 'Dashboard'],
        ['route' => 'agendas.index', 'match' => 'agendas.*', 'icon' => 'event_note', 'label' => $isVerifier ? 'Verifikasi' : 'Agenda', 'badge' => $pendingVerification],
        ['route' => 'public-agendas.index', 'match' => 'public-agendas.*', 'icon' => 'description', 'label' => 'Resume'],
    ] : [
        ['route' => 'home', 'match' => 'home', 'icon' => 'home', 'label' => 'Beranda'],
        ['route' => 'public-proposals.create', 'match' => 'public-proposals.*', 'icon' => 'edit_document', 'label' => 'Usulan'],
        ['route' => 'login', 'match' => 'login', 'icon' => 'login', 'label' => 'Masuk'],
    ];

    $moreItems = $user ? array_values(array_filter([
        $isAdmin ? ['route' => 'wa-blasts.index', 'match' => 'wa-blasts.*', 'icon' => 'campaign', 'label' => 'WhatsApp Blast'] : null,
        $user->hasRole('super_admin') ? ['route' => 'users.index', 'match' => 'users.*', 'icon' => 'group', 'label' => 'Pengguna'] : null,
        ['route' => 'account.password.edit', 'match' => 'account.*', 'icon' => 'lock_reset', 'label' => 'Ganti password'],
    ])) : [];
    $moreActive = collect($moreItems)->contains(fn ($item) => request()->routeIs($item['match']));

    $initials = $user ? str($user->name)->explode(' ')->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') : '';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#fafaf9">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="AGPIM - Agenda Pimpinan Kabupaten Mahakam Ulu">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" sizes="180x180" href="/icon-192x192.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..24,400..500,0..1,0&display=swap" rel="stylesheet">
    <title>{{ $title ?? 'AGPIM' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body.popup-open #app-topbar,
        body.popup-open #app-tabbar {
            display: none !important;
        }
    </style>
</head>
<body class="min-h-screen bg-canvas text-ink antialiased">
    <div id="offline-banner" class="hidden px-4 py-2.5 text-center text-sm font-medium text-[#8e2318]" style="background: var(--grad-bad)">
        Anda sedang offline. Data terakhir diperbarui pada: <span id="last-sync-label">-</span>
    </div>

    @unless ($bare)
        {{-- Top bar: logo + akun --}}
        <header id="app-topbar" class="app-topbar">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-2.5 sm:px-6 lg:px-8" style="padding-top: max(0.625rem, env(safe-area-inset-top))">
                <a href="{{ $user ? route('dashboard') : route('home') }}" class="flex items-center gap-2.5">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg text-[11px] font-bold text-white" style="background: var(--grad-primary)">AG</span>
                    <span class="leading-tight">
                        <span class="block text-sm font-semibold">AGPIM</span>
                        <span class="hidden text-[11px] text-muted sm:block">Pemkab Mahakam Ulu</span>
                    </span>
                </a>
                @auth
                    <div class="flex items-center gap-2.5">
                        <span class="hidden text-right leading-tight sm:block">
                            <span class="block text-sm font-medium">{{ $user->name }}</span>
                            <span class="block text-xs text-muted">{{ $user->role->label() }}</span>
                        </span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold text-[#23377a]" style="background: var(--grad-nav-active)" title="{{ $user->name }}">{{ $initials }}</span>
                    </div>
                @endauth
            </div>
        </header>
    @endunless

    <div class="app-main">
        <div @class(['mx-auto w-full max-w-6xl px-4 py-5 pb-32 sm:px-6 lg:px-8 lg:py-8 lg:pb-32' => ! $bare])>
            @if (session('status'))
                <div class="mb-5 flex items-center gap-2 rounded-lg px-3.5 py-2.5 text-sm text-[#1d6b40]" style="background: var(--grad-ok)">
                    <span class="material-symbols-outlined text-[18px]">check_circle</span>
                    {{ session('status') }}
                </div>
            @endif

            <main>
                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>
    </div>

    @unless ($bare)
        {{-- Menu melayang di bawah, untuk tamu maupun pengguna login, di semua ukuran layar --}}
        <nav id="app-tabbar" @class(['guest-dock', 'guest-dock-wide' => $user]) aria-label="Navigasi" x-data="{ more: false }" @keydown.escape.window="more = false" @click.outside="more = false">
            @foreach ($dockItems as $item)
                @php $active = request()->routeIs($item['match']); @endphp
                <a href="{{ route($item['route']) }}" @class(['dock-link relative', 'dock-link-active' => $active]) @if ($active) aria-current="page" @endif>
                    <span @class(['material-symbols-outlined', 'nav-icon-active' => $active])>{{ $item['icon'] }}</span>
                    {{ $item['label'] }}
                    @if (array_key_exists('badge', $item))
                        {{-- Selalu dirender (disembunyikan saat 0) supaya bisa diperbarui otomatis. --}}
                        <span class="dock-badge" data-pending-count data-hide-zero @if ($item['badge'] === 0) hidden @endif aria-label="pengajuan menunggu">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                    @endif
                </a>
            @endforeach

            @auth
                <button type="button" @class(['dock-link', 'dock-link-active' => $moreActive]) @click="more = !more" :aria-expanded="more">
                    <span class="material-symbols-outlined">more_horiz</span>
                    Lainnya
                </button>

                <div x-cloak x-show="more" x-transition.origin.bottom class="menu-panel absolute bottom-full right-0 mb-2 w-60">
                    <div class="flex items-center gap-2.5 px-2.5 py-2">
                        <span class="flex h-8 w-8 flex-none items-center justify-center rounded-full text-xs font-semibold text-[#23377a]" style="background: var(--grad-nav-active)">{{ $initials }}</span>
                        <span class="min-w-0 leading-tight">
                            <span class="block truncate text-sm font-medium">{{ $user->name }}</span>
                            <span class="block truncate text-xs text-muted">{{ $user->role->label() }}</span>
                        </span>
                    </div>
                    <div class="my-1 border-t border-line"></div>
                    @foreach ($moreItems as $item)
                        <a href="{{ route($item['route']) }}" class="menu-item">
                            <span class="material-symbols-outlined">{{ $item['icon'] }}</span>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="menu-item text-[#c4321f]">
                            <span class="material-symbols-outlined !text-[#c4321f]">logout</span>
                            Keluar
                        </button>
                    </form>
                </div>
            @endauth
        </nav>
    @endunless

    {{-- Notifikasi (toast) --}}
    <div x-data class="pointer-events-none fixed inset-x-0 top-0 z-[120] flex flex-col items-center gap-2 px-4 sm:inset-x-auto sm:right-4 sm:items-end" style="padding-top: max(1rem, env(safe-area-inset-top))" aria-live="polite">
        <template x-for="item in $store.toasts.items" :key="item.id">
            <div x-transition.opacity.duration.200ms
                 class="pointer-events-auto flex w-full max-w-sm items-start gap-2.5 rounded-xl border px-3.5 py-3 text-sm shadow-[0_12px_32px_rgba(28,27,25,0.14)]"
                 :class="item.type === 'error' ? 'border-[#f3c9c1] text-[#8e2318]' : 'border-[#c8e8d4] text-[#1d6b40]'"
                 :style="item.type === 'error' ? 'background: var(--grad-bad)' : 'background: var(--grad-ok)'"
                 :role="item.type === 'error' ? 'alert' : 'status'">
                <span class="material-symbols-outlined text-[20px]" x-text="item.type === 'error' ? 'error' : 'check_circle'"></span>
                <p class="min-w-0 flex-1 leading-5" x-text="item.message"></p>
                <button type="button" class="opacity-60 hover:opacity-100" @click="$store.toasts.dismiss(item.id)" aria-label="Tutup notifikasi">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>
        </template>
    </div>

    <script>
        (function () {
            const KEY = 'agpimFontSizePx';
            const MIN = 12;
            const MAX = 22;
            const STEP = 1;

            function getSize() {
                let raw = null;
                try { raw = localStorage.getItem(KEY); } catch (e) {}
                const parsed = parseInt(raw, 10);
                if (!isNaN(parsed)) return Math.min(MAX, Math.max(MIN, parsed));
                return 16;
            }

            function applySize(size) {
                document.documentElement.style.fontSize = size + 'px';
                const lbl = document.getElementById('font-size-label');
                if (lbl) lbl.textContent = size + 'px';
            }

            function setSize(size) {
                size = Math.min(MAX, Math.max(MIN, size));
                try { localStorage.setItem(KEY, String(size)); } catch (e) {}
                applySize(size);
            }

            document.addEventListener('DOMContentLoaded', function () {
                applySize(getSize());

                const inc = document.getElementById('font-increase');
                const dec = document.getElementById('font-decrease');

                if (inc) inc.addEventListener('click', function () { setSize(getSize() + STEP); });
                if (dec) dec.addEventListener('click', function () { setSize(getSize() - STEP); });
            });
        })();
    </script>
</body>
</html>
