<!DOCTYPE html>
<html lang="id" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Niti Resik — Ekonomi Sirkular Sampah Kabupaten Badung' }}</title>
    <meta name="description" content="Platform ekonomi sirkular pengelolaan sampah Kabupaten Badung: bank sampah digital, pelaporan, direktori UMKM daur ulang, dan edukasi.">
    <link rel="icon" type="image/png" href="{{ asset('images/icon.png') }}">
    <meta name="theme-color" content="#057D5D">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>[x-cloak]{display:none!important}</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-white text-primary-900 antialiased">
@php
    $panelRoute = null;
    if (auth()->check()) {
        $u = auth()->user();
        $panelRoute = match (true) {
            $u->hasAnyRole(['super_admin', 'admin'])                                   => route('admin.dashboard'),
            $u->hasRole('admin_dinas')                                                 => route('dinas.dashboard'),
            $u->hasAnyRole(['bupati', 'camat', 'lurah', 'kepala_dinas_banjar'])         => route('eksekutif.dashboard'),
            $u->hasRole('admin_tps')                                                    => route('tps.dashboard'),
            $u->hasAnyRole(['admin_bank_sampah', 'petugas_bank_sampah'])                => route('bank-sampah.dashboard'),
            $u->hasRole('umkm')                                                         => route('umkm.dashboard'),
            $u->hasRole('petugas_lapangan')                                             => route('petugas.dashboard'),
            default                                                                     => null,
        };
    }
    $navLinks = [
        ['Beranda', route('beranda'), request()->routeIs('beranda')],
        ['UMKM', route('publik.umkm.index'), request()->routeIs('publik.umkm.*')],
        ['TPS', route('publik.tps.index'), request()->routeIs('publik.tps.*')],
        ['Bank Sampah', route('publik.bank-sampah.index'), request()->routeIs('publik.bank-sampah.*')],
        ['Laporan', route('publik.laporan.index'), request()->routeIs('publik.laporan.*')],
        ['Edukasi', route('artikel.index'), request()->routeIs('artikel.*')],
    ];

    // Di beranda: navbar transparan menimpa hero. Halaman lain: navbar solid + beri jarak atas.
    $isBeranda = request()->routeIs('beranda');
@endphp
<div class="min-h-full" x-data="{ open: false, scrolled: false }"
     x-init="scrolled = window.scrollY > 10;
             window.addEventListener('scroll', () => { scrolled = window.scrollY > 10 }, { passive: true });">

    <header
        class="fixed inset-x-0 top-0 z-9999 transition-all duration-300"
        :class="(scrolled || open || {{ $isBeranda ? 'false' : 'true' }})
            ? 'border-b border-gray-200 bg-white/90 backdrop-blur'
            : 'border-b border-transparent bg-transparent'">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
            <a href="{{ route('beranda') }}" class="flex items-center gap-2.5">
                <img src="{{ asset('images/logo-primary.png') }}" class="w-10" alt="Niti Resik">
                <span class="text-lg font-bold text-primary tracking-tight">Niti Resik</span>
            </a>
            <nav class="hidden items-center gap-1 md:flex">
                @foreach ($navLinks as [$label, $url, $isActive])
                    <a href="{{ $url }}" class="rounded-lg px-3 py-2 text-sm font-medium {{ $isActive ? 'text-primary-700' : 'text-gray-600 hover:text-primary-900' }}">{{ $label }}</a>
                @endforeach
            </nav>
            <div class="hidden items-center gap-2 md:flex">
                @auth
                    @if ($panelRoute)
                        <a href="{{ $panelRoute }}" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">Buka Panel</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Keluar</button>
                    </form>
                @else
                    <a href="/daftar-umkm" class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Daftar UMKM</a>
                    <a href="/login" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">Masuk</a>
                @endauth
            </div>
            <button @click="open = !open" class="md:hidden rounded-lg p-2 text-gray-600 hover:bg-gray-100" aria-label="Menu">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
        <div x-show="open" x-cloak x-transition class="border-t border-gray-200 bg-white md:hidden">
            <div class="space-y-1 px-4 py-3">
                @foreach ($navLinks as [$label, $url, $isActive])
                    <a href="{{ $url }}" class="block rounded-lg px-3 py-2 text-sm font-medium {{ $isActive ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">{{ $label }}</a>
                @endforeach
                <div class="mt-2 flex flex-col gap-2 border-t border-gray-100 pt-3">
                    @auth
                        @if ($panelRoute)<a href="{{ $panelRoute }}" class="rounded-lg bg-primary-500 px-4 py-2 text-center text-sm font-semibold text-white">Buka Panel</a>@endif
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600">Keluar</button></form>
                    @else
                        <a href="/daftar-umkm" class="rounded-lg border border-gray-200 px-4 py-2 text-center text-sm font-medium text-gray-600">Daftar UMKM</a>
                        <a href="/login" class="rounded-lg bg-primary-500 px-4 py-2 text-center text-sm font-semibold text-white">Masuk</a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    {{-- Beranda: konten mulai di bawah navbar (hero sudah punya padding sendiri).
         Halaman lain: beri jarak setinggi navbar agar tidak tertutup. --}}
    <main class="{{ $isBeranda ? '' : 'pt-16' }}">{{ $slot }}</main>

    <footer class="mt-20 border-t border-gray-200 bg-gray-50">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <div class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo-primary.png') }}" class="w-10" alt="Niti Resik">
                    <span class="text-lg font-bold text-primary tracking-tight">Niti Resik</span>
                </div>
                <p class="mt-3 max-w-sm text-sm text-gray-500">Ekonomi sirkular pengelolaan sampah dari warga, bank sampah, hingga UMKM daur ulang dalam satu ekosistem.</p>
            </div>
            <div>
                <p class="text-sm font-semibold text-primary-900">Jelajahi</p>
                <ul class="mt-3 space-y-2 text-sm text-gray-500">
                    <li><a href="{{ route('publik.umkm.index') }}" class="hover:text-primary-700">Direktori UMKM</a></li>
                    <li><a href="{{ route('publik.tps.index') }}" class="hover:text-primary-700">Lokasi TPS</a></li>
                    <li><a href="{{ route('publik.bank-sampah.index') }}" class="hover:text-primary-700">Bank Sampah</a></li>
                    <li><a href="{{ route('publik.laporan.index') }}" class="hover:text-primary-700">Laporan Publik</a></li>
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold text-primary-900">Unduh Aplikasi</p>
                <p class="mt-3 text-sm text-gray-500">Untuk masyarakat</p>
                <div class="mt-3 flex flex-col gap-2">
                    <a href="#unduh" class="inline-flex items-center gap-2 rounded-lg bg-primary-900 px-3 py-2 text-xs font-semibold text-white hover:bg-primary-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M3 20.5V3.5c0-.6.3-1 .8-1.3L13 12 3.8 21.8c-.5-.3-.8-.7-.8-1.3Zm12.5-7L6 3.9l11.6 6.6-2.1 3Zm3.7 2.1-2.6-1.5-2.3 2.3 2.3 2.3 2.6-1.5c.7-.4.7-1.5 0-1.9ZM6 20.1l9.5-9.5 2.1 3L6 20.1Z"/></svg>
                        Unduh Aplikasi
                    </a>
                </div>
            </div>
        </div>
        <div class="border-t border-gray-200 py-6">
            <p class="mx-auto max-w-6xl px-4 text-xs text-gray-400">© {{ date('Y') }} Niti Resik</p>
        </div>
    </footer>
</div>
</body>
</html>