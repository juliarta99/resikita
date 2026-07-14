<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Admin TPS' }} — Niti Resik</title>
    <style>[x-cloak]{display:none!important}</style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-[#f5f6f8] text-primary-900 antialiased">
@php
    // Definisikan tab sekali, dipakai di desktop (tab bar) & mobile (bottom nav)
    $tabs = [
        ['route' => 'tps.dashboard', 'label' => 'Dashboard', 'match' => 'tps.dashboard',
         'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3v-6h6v6h3a1 1 0 001-1V10"/>'],
        ['route' => 'tps.nasabah', 'label' => 'Nasabah', 'match' => 'tps.nasabah',
         'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a3 3 0 00-5.36-1.87M17 20H7m10 0v-1a5 5 0 00-.9-2.87M7 20H2v-1a3 3 0 015.36-1.87M7 20v-1a5 5 0 01.9-2.87m0 0A5 5 0 0112 12a5 5 0 014.1 2.13M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>'],
        ['route' => 'tps.iuran', 'label' => 'Iuran', 'match' => 'tps.iuran',
         'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.1 0-2 .9-2 2s.9 2 2 2 2 .9 2 2-.9 2-2 2m0-8V6m0 12v-2M4 6h16v12H4z"/>'],
        ['route' => 'tps.info', 'label' => 'Info TPS', 'match' => 'tps.info',
         'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
    ];
@endphp
<div class="min-h-full">

    {{-- ======== HEADER + NAV (fixed) ======== --}}
    <header class="fixed inset-x-0 top-0 z-40 border-b border-gray-200 bg-white/90 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-500 text-white">
                    <img src="{{ asset('images/logo.png') }}" class="w-6" alt="Niti Resik">
                </div>
                <div>
                    <p class="text-sm font-semibold leading-tight text-primary-900">Niti Resik | TPS</p>
                    <p class="text-xs text-gray-500 leading-tight">{{ auth()->user()?->tps?->nama ?? 'TPS' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3" x-data="{ confirmLogout: false }">
                <span class="hidden text-sm text-gray-500 sm:inline">{{ auth()->user()?->name }}</span>
                <button type="button" @click="confirmLogout = true"
                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-primary-900 hover:bg-gray-50">Keluar</button>
                <div x-show="confirmLogout" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div x-show="confirmLogout" x-transition.opacity class="fixed inset-0 bg-primary-900/40"></div>
                    <div x-show="confirmLogout" x-transition @click.outside="confirmLogout = false"
                         @keydown.escape.window="confirmLogout = false"
                         class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                        <h3 class="text-base font-semibold text-primary-900">Keluar dari akun?</h3>
                        <p class="mt-1 text-sm text-gray-500">Anda akan keluar dari sesi ini.</p>
                        <div class="mt-5 flex justify-end gap-2">
                            <button type="button" @click="confirmLogout = false" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-primary-900 hover:bg-gray-50">Batal</button>
                            <form method="POST" action="/logout">@csrf
                                <button type="submit" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">Keluar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab bar (desktop only) --}}
        <nav class="hidden border-t border-gray-100 bg-white md:block">
            <div class="mx-auto flex max-w-5xl gap-1 overflow-x-auto px-4">
                @php $tab = 'whitespace-nowrap border-b-2 px-3 py-3 text-sm font-medium'; @endphp
                @foreach ($tabs as $t)
                    <a href="{{ route($t['route']) }}"
                       class="{{ $tab }} {{ request()->routeIs($t['match']) ? 'border-primary-500 text-primary-700' : 'border-transparent text-gray-500 hover:text-primary-900' }}">{{ $t['label'] }}</a>
                @endforeach
            </div>
        </nav>
    </header>

    {{-- ======== KONTEN ========
         Padding-top: mobile hanya header (pt-16); desktop header + tab bar (pt-28).
         Padding-bottom mobile: ruang untuk bottom nav (pb-24). --}}
    <main class="mx-auto max-w-5xl px-4 pb-24 pt-16 md:pb-6 md:pt-28">
        {{ $slot }}
    </main>

    {{-- ======== BOTTOM NAV (mobile only) ======== --}}
    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white md:hidden">
        <div class="mx-auto flex max-w-5xl items-stretch">
            @foreach ($tabs as $t)
                @php $active = request()->routeIs($t['match']); @endphp
                <a href="{{ route($t['route']) }}"
                   class="flex flex-1 flex-col items-center gap-1 py-2.5 text-[11px] font-medium {{ $active ? 'text-primary-700' : 'text-gray-500' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">{!! $t['icon'] !!}</svg>
                    {{ $t['label'] }}
                </a>
            @endforeach
        </div>
    </nav>
</div>
</body>
</html>