<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Bank Sampah' }} — Niti Resik</title>
    <style>[x-cloak]{display:none!important}</style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
                    <p class="text-sm font-semibold leading-tight text-primary-900">Niti Resik | Bank Sampah</p>
                    <p class="text-xs text-gray-500 leading-tight">{{ auth()->user()?->bankSampah?->nama ?? 'Bank Sampah' }}</p>
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
        <div class="mx-auto flex max-w-5xl gap-1 overflow-x-auto px-4">
            @php $tab = 'whitespace-nowrap border-b-2 px-3 py-3 text-sm font-medium'; @endphp
            <a href="{{ route('bank-sampah.dashboard') }}"
               class="{{ $tab }} {{ request()->routeIs('bank-sampah.dashboard') ? 'border-primary-500 text-primary-700' : 'border-transparent text-gray-500 hover:text-primary-900' }}">Dashboard</a>

            @role('petugas_bank_sampah')
                <a href="{{ route('bank-sampah.setor') }}"
                   class="{{ $tab }} {{ request()->routeIs('bank-sampah.setor') ? 'border-primary-500 text-primary-700' : 'border-transparent text-gray-500 hover:text-primary-900' }}">Setor Sampah</a>
            @endrole

            <a href="{{ route('bank-sampah.riwayat') }}"
               class="{{ $tab }} {{ request()->routeIs('bank-sampah.riwayat') ? 'border-primary-500 text-primary-700' : 'border-transparent text-gray-500 hover:text-primary-900' }}">Riwayat Setor</a>

            <a href="{{ route('bank-sampah.harga') }}"
               class="{{ $tab }} {{ request()->routeIs('bank-sampah.harga') ? 'border-primary-500 text-primary-700' : 'border-transparent text-gray-500 hover:text-primary-900' }}">Harga</a>

            @role('admin_bank_sampah')
                <a href="{{ route('bank-sampah.petugas') }}"
                   class="{{ $tab }} {{ request()->routeIs('bank-sampah.petugas') ? 'border-primary-500 text-primary-700' : 'border-transparent text-gray-500 hover:text-primary-900' }}">Petugas</a>
                <a href="{{ route('bank-sampah.info') }}"
                   class="{{ $tab }} {{ request()->routeIs('bank-sampah.info') ? 'border-primary-500 text-primary-700' : 'border-transparent text-gray-500 hover:text-primary-900' }}">Info Bank Sampah</a>
            @endrole

            <a href="{{ route('bank-sampah.profil') }}"
               class="{{ $tab }} {{ request()->routeIs('bank-sampah.profil') ? 'border-primary-500 text-primary-700' : 'border-transparent text-gray-500 hover:text-primary-900' }}">Profil</a>
        </div>
    </nav>

    <main class="mx-auto max-w-5xl px-4 py-6">
        {{ $slot }}
    </main>
</div>
</body>
</html>