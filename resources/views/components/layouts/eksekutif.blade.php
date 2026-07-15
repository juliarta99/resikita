<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }} — Niti Resik</title>
    <style>[x-cloak]{display:none!important}</style>
    <link rel="icon" type="image/png" href="{{ asset('images/icon.png') }}">
    <meta name="theme-color" content="#057D5D">
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
        ['route' => 'eksekutif.dashboard', 'label' => 'Dashboard', 'match' => 'eksekutif.dashboard',
         'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3v-6h6v6h3a1 1 0 001-1V10"/>'],
        ['route' => 'eksekutif.peta', 'label' => 'Peta Sebaran', 'match' => 'eksekutif.peta',
         'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>'],
    ];
@endphp
<div class="min-h-full">

    {{-- ======== HEADER + NAV (fixed) ======== --}}
    <header class="fixed inset-x-0 top-0 z-40 border-b border-gray-200 bg-white/90 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-500 text-white">
                    <img src="{{ asset('images/logo.png') }}" class="w-6" alt="Niti Resik">
                </div>
                <div>
                    <p class="text-sm font-semibold leading-tight text-primary-900">Niti Resik</p>
                    <p class="text-xs text-gray-500 leading-tight">Dashboard Eksekutif</p>
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
            <div class="mx-auto flex max-w-6xl gap-1 overflow-x-auto px-4">
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
    <main class="mx-auto max-w-6xl px-4 pb-24 pt-16 md:pb-6 md:pt-28">
        {{ $slot }}
    </main>

    {{-- ======== BOTTOM NAV (mobile only) ======== --}}
    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white md:hidden">
        <div class="mx-auto flex max-w-6xl items-stretch">
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