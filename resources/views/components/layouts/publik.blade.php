@props(['title' => null, 'deskripsi' => null])

@php
    $menu = [
        ['label' => 'Beranda', 'route' => 'publik.beranda'],
        ['label' => 'Peta & Direktori', 'route' => 'publik.direktori'],
        ['label' => 'Marketplace', 'route' => 'publik.produk'],
        ['label' => 'Literasi', 'route' => 'publik.artikel'],
        ['label' => 'Laporan Warga', 'route' => 'publik.laporan'],
    ];
@endphp

<!DOCTYPE html>
<html lang="id" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title.' · '.config('app.name') : config('app.name').' · Platform Ekonomi Sirkular Pengelolaan Sampah' }}</title>

    <meta name="description" content="{{ $deskripsi ?? 'Resikita menghubungkan warga, pemerintah daerah, bank sampah, dan UMKM dalam satu alur pengelolaan sampah: melapor, memilah, menyetor, dan menjual kembali.' }}">
    <meta name="theme-color" content="#057D5D">
    <link rel="icon" type="image/png" href="{{ asset('images/icon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col bg-white font-sans text-gray-800 antialiased">

<a href="#konten-utama"
   class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg
          focus:bg-primary-700 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
    Lewati ke konten utama
</a>

<header x-data="{ laciTerbuka: false }"
        class="sticky top-0 z-40 border-b border-gray-100 bg-white/90 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3.5 sm:px-6 lg:px-8">
        <a href="{{ route('publik.beranda') }}" wire:navigate class="flex flex-none items-center gap-2.5">
            <x-ui.logo/>
            <span class="text-lg font-bold tracking-tight text-primary-700">{{ config('app.name') }}</span>
        </a>

        <nav class="ml-auto hidden items-center gap-1 lg:flex" aria-label="Navigasi utama">
            @foreach ($menu as $butir)
                @php $aktif = request()->routeIs($butir['route'].'*'); @endphp
                <a href="{{ route($butir['route']) }}" wire:navigate
                   @if ($aktif) aria-current="page" @endif
                   class="rounded-lg px-3 py-2 text-sm font-medium transition
                          {{ $aktif ? 'text-primary-700' : 'text-gray-600 hover:text-primary-900' }}">
                    {{ $butir['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="ml-auto flex items-center gap-2 lg:ml-0">
            @auth
                <x-ui.tombol ukuran="kecil" :tautan="route(auth()->user()->roleUtama()?->routeDasbor() ?? 'publik.beranda')">
                    Buka panel
                </x-ui.tombol>
            @else
                <x-ui.tombol jenis="kedua" ukuran="kecil" :tautan="route('masuk')" class="hidden sm:inline-flex">
                    Masuk
                </x-ui.tombol>
                <x-ui.tombol ukuran="kecil" :tautan="route('publik.pengajuan-wilayah')">
                    Daftarkan wilayah
                </x-ui.tombol>
            @endauth

            <button type="button" @click="laciTerbuka = !laciTerbuka"
                    class="rounded-lg p-2 text-gray-600 hover:bg-gray-100 lg:hidden"
                    :aria-expanded="laciTerbuka.toString()"
                    aria-label="Buka navigasi">
                <x-ui.ikon nama="menu"/>
            </button>
        </div>
    </div>

    {{-- Navigasi layar kecil --}}
    <nav x-show="laciTerbuka" x-collapse x-cloak
         class="border-t border-gray-100 lg:hidden" aria-label="Navigasi utama layar kecil">
        <div class="space-y-1 px-4 py-3">
            @foreach ($menu as $butir)
                <a href="{{ route($butir['route']) }}" wire:navigate
                   class="block rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    {{ $butir['label'] }}
                </a>
            @endforeach

            @guest
                {{--
                    Dua pintu pendaftaran ditaruh lengkap di sini karena
                    bilah atas layar sempit hanya memuat satu tombol, dan
                    yang tampil di sana adalah pendaftaran wilayah. Tanpa
                    baris ini, pendaftaran UMKM hanya bisa ditemukan di
                    kaki halaman.
                --}}
                <div class="mt-2 space-y-1 border-t border-gray-100 pt-2">
                    <a href="{{ route('publik.pengajuan-wilayah') }}" wire:navigate
                       class="block rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Daftarkan wilayah
                    </a>
                    <a href="{{ route('publik.pendaftaran-umkm') }}" wire:navigate
                       class="block rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Daftarkan UMKM
                    </a>
                    <a href="{{ route('masuk') }}"
                       class="block rounded-lg px-3 py-2.5 text-sm font-medium text-primary-700 hover:bg-primary-50">
                        Masuk ke panel
                    </a>
                </div>
            @endguest
        </div>
    </nav>
</header>

<main id="konten-utama" class="flex-1">
    {{ $slot }}
</main>

<footer class="border-t border-gray-100 bg-gray-50">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-8 md:grid-cols-4">
            <div class="md:col-span-2">
                <div class="flex items-center gap-2.5">
                    <x-ui.logo ukuran="kecil"/>
                    <span class="font-bold text-primary-700">{{ config('app.name') }}</span>
                </div>
                <p class="mt-3 max-w-sm text-sm leading-relaxed text-gray-600">
                    Platform ekonomi sirkular pengelolaan sampah. Menghubungkan warga, pemerintah
                    daerah, bank sampah, dan UMKM dalam satu alur: melapor, memilah, menyetor,
                    dan menjual kembali.
                </p>
            </div>

            <div>
                <h2 class="text-sm font-semibold text-primary-900">Jelajahi</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($menu as $butir)
                        <li>
                            <a href="{{ route($butir['route']) }}" wire:navigate
                               class="text-gray-600 hover:text-primary-700">{{ $butir['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h2 class="text-sm font-semibold text-primary-900">Untuk institusi</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    <li>
                        <a href="{{ route('publik.pengajuan-wilayah') }}" wire:navigate
                           class="text-gray-600 hover:text-primary-700">Daftarkan wilayah</a>
                    </li>
                    <li>
                        <a href="{{ route('publik.pendaftaran-umkm') }}" wire:navigate
                           class="text-gray-600 hover:text-primary-700">Daftarkan UMKM</a>
                    </li>
                    <li>
                        <a href="{{ route('masuk') }}" class="text-gray-600 hover:text-primary-700">
                            Masuk ke panel
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <p class="mt-8 border-t border-gray-200 pt-6 text-xs text-gray-500">
            &copy; {{ now()->year }} {{ config('app.name') }}. Cakupan nasional.
        </p>
    </div>
</footer>

</body>
</html>
