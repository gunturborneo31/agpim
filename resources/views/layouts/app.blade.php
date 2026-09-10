<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1247a6">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="AGPIM - Agenda Pimpinan Kabupaten Mahakam Ulu">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" sizes="180x180" href="/icon-192x192.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400;500;600&display=swap" rel="stylesheet">
    <title>{{ $title ?? 'AGPIM' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .bottom-popup-panel {
            opacity: 1;
            transform: translate(-50%, 0) scale(1);
            pointer-events: auto;
            box-shadow: 0 18px 44px rgba(29, 78, 216, 0.38);
        }

        .user-action-panel {
            opacity: 0;
            transform: translateY(0.5rem);
            pointer-events: none;
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .user-action-panel[data-open="true"] {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        body.popup-open > header.glass-header,
        body.popup-open > div.fixed.bottom-4.left-1\/2.z-40 {
            display: none !important;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <div id="offline-banner" class="hidden border-b border-rose-300/40 bg-rose-600 px-4 py-3 text-sm font-semibold text-white">
        Anda sedang offline. Data terakhir diperbarui pada: <span id="last-sync-label">-</span>
    </div>

    <header class="glass-header">
        <div class="mx-auto w-full max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-blue-100 bg-blue-50 text-blue-700">
                        <span class="material-symbols-outlined text-[22px] leading-none">account_balance</span>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-blue-800">AGPIM</h1>
                    </div>
                </div>

                    

                @auth
                    <div class="hidden items-center gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2 text-right shadow-sm sm:flex">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">{{ auth()->user()->role->label() }}</p>
                        </div>
                        <div class="relative flex h-10 w-10 items-center justify-center rounded-full border border-blue-200 bg-blue-50 text-sm font-bold text-blue-700">
                            {{ str(auth()->user()->name)->substr(0, 1)->upper() }}
                            <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-white bg-emerald-500"></span>
                        </div>
                    </div>
                @else
                    <a class="btn-primary nav-interactive inline-flex items-center gap-2" href="{{ route('login') }}">
                        <span class="material-symbols-outlined text-[18px] leading-none nav-icon-active">login</span>
                        Login
                    </a>
                @endauth
            </div>

        </div>
    </header>

    <div class="mx-auto max-w-7xl px-4 py-6 pb-28 sm:px-6 md:pb-28 lg:px-8">
        <!-- @auth
            <div class="mb-6 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600 shadow-[0_4px_20px_rgba(0,0,0,0.04)]">
                Login sebagai <span class="font-semibold text-slate-900">{{ auth()->user()->name }}</span>
            </div>
        @endauth -->

        @if (session('status'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <main>
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>

    <div class="fixed bottom-4 left-1/2 z-40 -translate-x-1/2 shadow-lg shadow-xl w-full">
        <nav id="bottomPopupMenu"
            class="bottom-popup-panel absolute bottom-0 left-1/2 w-[86vw] max-w-2xl md:w-1/4 rounded-full border border-blue-400/60 bg-blue-700/95 p-1.5 backdrop-blur-lg"
            data-open="true">
            <div class="flex items-center justify-around gap-1 px-1 py-1 text-[10px] md:text-[11px]">
                <a href="{{ route('home') }}" class="nav-interactive flex flex-col items-center gap-1 rounded-lg px-2 py-1 {{ request()->routeIs('home') ? 'bg-white text-blue-700' : 'text-blue-100 hover:bg-blue-600 hover:text-white' }}">
                    <span class="material-symbols-outlined text-[18px] leading-none {{ request()->routeIs('home') ? 'nav-icon-active' : 'nav-icon-inactive' }}">home</span>
                    <span>Home</span>
                </a>

                @auth
                    <a href="{{ route('dashboard') }}" class="nav-interactive flex flex-col items-center gap-1 rounded-lg px-2 py-1 {{ request()->routeIs('dashboard') ? 'bg-white text-blue-700' : 'text-blue-100 hover:bg-blue-600 hover:text-white' }}">
                        <span class="material-symbols-outlined text-[18px] leading-none {{ request()->routeIs('dashboard') ? 'nav-icon-active' : 'nav-icon-inactive' }}">grid_view</span>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('agendas.index') }}" class="nav-interactive flex flex-col items-center gap-1 rounded-lg px-2 py-1 {{ request()->routeIs('agendas.*') ? 'bg-white text-blue-700' : 'text-blue-100 hover:bg-blue-600 hover:text-white' }}">
                        <span class="material-symbols-outlined text-[18px] leading-none {{ request()->routeIs('agendas.*') ? 'nav-icon-active' : 'nav-icon-inactive' }}">event</span>
                        <span>Agenda</span>
                    </a>
                    <a href="{{ route('public-agendas.index') }}" class="nav-interactive flex flex-col items-center gap-1 rounded-lg px-2 py-1 {{ request()->routeIs('public-agendas.*') ? 'bg-white text-blue-700' : 'text-blue-100 hover:bg-blue-600 hover:text-white' }}">
                        <span class="material-symbols-outlined text-[18px] leading-none {{ request()->routeIs('public-agendas.*') ? 'nav-icon-active' : 'nav-icon-inactive' }}">description</span>
                        <span>Resume</span>
                    </a>
                    <button id="bottomUserMenuTrigger" type="button" class="nav-interactive flex flex-col items-center gap-1 rounded-lg px-2 py-1 text-blue-100 hover:bg-blue-600 hover:text-white">
                        <span class="material-symbols-outlined text-[18px] leading-none">person</span>
                        <span>User</span>
                    </button>
                @else
                    <a href="{{ route('public-proposals.create') }}" class="nav-interactive flex flex-col items-center gap-1 rounded-lg px-2 py-1 {{ request()->routeIs('public-proposals.*') ? 'bg-white text-blue-700' : 'text-blue-100 hover:bg-blue-600 hover:text-white' }}">
                        <span class="material-symbols-outlined text-[18px] leading-none {{ request()->routeIs('public-proposals.*') ? 'nav-icon-active' : 'nav-icon-inactive' }}">edit_document</span>
                        <span>Usulan</span>
                    </a>
                    <a href="{{ route('login') }}" class="nav-interactive flex flex-col items-center gap-1 rounded-lg px-2 py-1 {{ request()->routeIs('login') ? 'bg-white text-blue-700' : 'text-blue-100 hover:bg-blue-600 hover:text-white' }}">
                        <span class="material-symbols-outlined text-[18px] leading-none {{ request()->routeIs('login') ? 'nav-icon-active' : 'nav-icon-inactive' }}">login</span>
                        <span>Login</span>
                    </a>
                @endauth
            </div>

            @auth
                <div id="bottomUserActionPanel" class="user-action-panel absolute -top-36 right-2 w-52 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl" data-open="false">
                    @if (auth()->user()->hasRole('super_admin', 'admin_prokopim'))
                        <a href="{{ route('wa-blasts.index') }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                            <span class="material-symbols-outlined text-[18px] leading-none">campaign</span>
                            WhatsApp Blast
                        </a>
                    @endif
                    <a href="{{ route('account.password.edit') }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                        <span class="material-symbols-outlined text-[18px] leading-none">lock_reset</span>
                        Ganti Password
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="mt-1">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50">
                            <span class="material-symbols-outlined text-[18px] leading-none">logout</span>
                            Keluar
                        </button>
                    </form>
                </div>
            @endauth
        </nav>
    </div>

    <script>
        (function () {
            const KEY = 'agpimFontSizePx';
            const MIN = 12;
            const MAX = 22;
            const STEP = 1;

            function getSize() {
                const raw = localStorage.getItem(KEY);
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
                localStorage.setItem(KEY, String(size));
                applySize(size);
            }

            document.addEventListener('DOMContentLoaded', function () {
                applySize(getSize());

                const inc = document.getElementById('font-increase');
                const dec = document.getElementById('font-decrease');

                if (inc) inc.addEventListener('click', function () {
                    const next = getSize() + STEP;
                    setSize(next);
                });

                if (dec) dec.addEventListener('click', function () {
                    const next = getSize() - STEP;
                    setSize(next);
                });
            });
        })();
    </script>

    @auth
        <script>
            (() => {
                const trigger = document.getElementById('bottomUserMenuTrigger');
                const panel = document.getElementById('bottomUserActionPanel');

                if (!trigger || !panel) {
                    return;
                }

                const setOpen = (open) => {
                    panel.dataset.open = open ? 'true' : 'false';
                    trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
                };

                trigger.addEventListener('click', () => {
                    setOpen(panel.dataset.open !== 'true');
                });

                document.addEventListener('click', (event) => {
                    if (panel.dataset.open !== 'true') {
                        return;
                    }

                    if (panel.contains(event.target) || trigger.contains(event.target)) {
                        return;
                    }

                    setOpen(false);
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        setOpen(false);
                    }
                });
            })();
        </script>
    @endauth
</body>
</html>
