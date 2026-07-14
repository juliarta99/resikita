<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Petugas Lapangan' }} — Niti Resik</title>
    <style>[x-cloak]{display:none!important}</style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-full bg-[#f5f6f8] text-primary-900 antialiased">
<div class="min-h-full">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-500 text-white">
                    <img src="{{ asset('images/logo.png') }}" class="w-6" alt="Niti Resik">
                </div>
                <div>
                    <p class="text-sm font-semibold leading-tight text-primary-900">Niti Resik | Petugas Lapangan</p>
                    <p class="text-xs text-gray-500 leading-tight">{{ auth()->user()?->name }}</p>
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
    </header>

    <nav class="hidden border-b border-gray-200 bg-white md:block">
        <div class="mx-auto flex max-w-5xl gap-1 overflow-x-auto px-4">
            @php $tab = 'whitespace-nowrap border-b-2 px-3 py-3 text-sm font-medium'; @endphp
            <a href="{{ route('petugas.dashboard') }}"
               class="{{ $tab }} {{ request()->routeIs('petugas.dashboard') ? 'border-primary-500 text-primary-700' : 'border-transparent text-gray-500 hover:text-primary-900' }}">Dashboard</a>
            <a href="{{ route('petugas.tugas') }}"
               class="{{ $tab }} {{ request()->routeIs('petugas.tugas') || request()->routeIs('petugas.tugas.detail') ? 'border-primary-500 text-primary-700' : 'border-transparent text-gray-500 hover:text-primary-900' }}">Tugas Saya</a>
            <a href="{{ route('petugas.peta') }}"
               class="{{ $tab }} {{ request()->routeIs('petugas.peta') ? 'border-primary-500 text-primary-700' : 'border-transparent text-gray-500 hover:text-primary-900' }}">Peta Sebaran</a>
            <a href="{{ route('petugas.profil') }}"
               class="{{ $tab }} {{ request()->routeIs('petugas.profil') ? 'border-primary-500 text-primary-700' : 'border-transparent text-gray-500 hover:text-primary-900' }}">Profil</a>
        </div>
    </nav>

    <main class="mx-auto max-w-5xl px-4 py-6 pb-24 md:pb-6">
        {{ $slot }}
    </main>

    {{-- Bottom navigation (mobile only) --}}
    @php
        $bottom = [
            ['route' => 'petugas.dashboard', 'label' => 'Beranda', 'active' => request()->routeIs('petugas.dashboard'),
             'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10"/>'],
            ['route' => 'petugas.tugas', 'label' => 'Tugas', 'active' => request()->routeIs('petugas.tugas') || request()->routeIs('petugas.tugas.detail'),
             'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>'],
            ['route' => 'petugas.peta', 'label' => 'Peta', 'active' => request()->routeIs('petugas.peta'),
             'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>'],
            ['route' => 'petugas.profil', 'label' => 'Profil', 'active' => request()->routeIs('petugas.profil'),
             'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>'],
        ];
    @endphp
    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white md:hidden">
        <div class="mx-auto grid max-w-5xl grid-cols-4">
            @foreach ($bottom as $b)
                <a href="{{ route($b['route']) }}"
                   class="flex flex-col items-center gap-1 py-2.5 text-[11px] font-medium {{ $b['active'] ? 'text-primary-700' : 'text-gray-500' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">{!! $b['icon'] !!}</svg>
                    {{ $b['label'] }}
                </a>
            @endforeach
        </div>
    </nav>
</div>
@stack('scripts')
</body>
</html>