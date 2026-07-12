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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-[#f5f6f8] text-primary-900 antialiased">
<div class="min-h-full">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-500 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/>
                        <path d="M2 21c0-3 1.85-5.36 5.08-6"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold leading-tight text-primary-900">Niti Resik · TPS</p>
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
    </header>

    <nav class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-5xl gap-1 px-4">
            @php $tab = 'border-b-2 px-3 py-3 text-sm font-medium'; @endphp
            <a href="{{ route('tps.nasabah') }}"
               class="{{ $tab }} {{ request()->routeIs('tps.nasabah', 'tps.dashboard') ? 'border-primary-500 text-primary-700' : 'border-transparent text-gray-500 hover:text-primary-900' }}">Nasabah</a>
            <a href="{{ route('tps.iuran') }}"
               class="{{ $tab }} {{ request()->routeIs('tps.iuran') ? 'border-primary-500 text-primary-700' : 'border-transparent text-gray-500 hover:text-primary-900' }}">Iuran</a>
        </div>
    </nav>

    <main class="mx-auto max-w-5xl px-4 py-6">
        {{ $slot }}
    </main>
</div>
</body>
</html>