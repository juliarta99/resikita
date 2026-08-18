@props(['title' => null])

@php
    use App\Support\Navigasi;

    $pengguna = auth()->user();
    $menu = $pengguna ? Navigasi::untuk($pengguna) : [];
    $namaPanel = $pengguna ? Navigasi::namaPanel($pengguna) : config('app.name');
    $peran = $pengguna?->roleUtama()?->label();
@endphp

<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · '.config('app.name') : config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50 font-sans text-gray-800 antialiased">

{{--
    Kerangka panel.

    Bilah sisi tetap terlihat mulai lebar lg. Di bawah itu ia menjadi
    laci yang dibuka tombol di bilah atas, bukan disembunyikan begitu
    saja. Sebagian besar pengguna pemerintahan desa membuka panel ini
    dari telepon genggam, jadi navigasi harus tetap sampai di 375px,
    tidak hanya menyusut sampai tak terpakai.
--}}
<div x-data="{ laciTerbuka: false }" class="min-h-full">

    {{-- Lewati navigasi: butir pertama yang diterima papan tik. --}}
    <a href="#konten-utama"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50
              focus:rounded-lg focus:bg-primary-700 focus:px-4 focus:py-2 focus:text-sm
              focus:font-semibold focus:text-white">
        Lewati ke konten utama
    </a>

    {{-- Tirai laci, hanya di layar kecil --}}
    <div x-show="laciTerbuka" x-transition.opacity x-cloak
         @click="laciTerbuka = false"
         class="fixed inset-0 z-30 bg-primary-900/40 lg:hidden"></div>

    <aside
        x-cloak
        :class="laciTerbuka ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-gray-200 bg-white
               transition-transform duration-200 lg:translate-x-0"
    >
        <div class="flex items-center gap-3 border-b border-gray-100 px-5 py-4">
            <x-ui.logo alt="Resikita"/>
            <span class="min-w-0">
                <span class="block truncate text-sm font-semibold text-primary-700">{{ $namaPanel }}</span>
                <span class="block truncate text-xs text-gray-500">{{ $peran }}</span>
            </span>
            <button type="button" @click="laciTerbuka = false"
                    class="ml-auto rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 lg:hidden"
                    aria-label="Tutup navigasi">
                <x-ui.ikon nama="silang"/>
            </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4" aria-label="Navigasi utama">
            @foreach ($menu as $butir)
                @php $aktif = request()->routeIs($butir['cocok']); @endphp
                <a href="{{ route($butir['route']) }}"
                   @if ($aktif) aria-current="page" @endif
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                          {{ $aktif
                              ? 'bg-primary-50 text-primary-700'
                              : 'text-gray-600 hover:bg-gray-50 hover:text-primary-900' }}">
                    <x-ui.ikon :nama="$butir['ikon']" class="h-5 w-5 flex-none"/>
                    <span class="truncate">{{ $butir['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="border-t border-gray-100 p-3">
            <form method="POST" action="{{ route('keluar') }}">
                @csrf
                <button type="submit"
                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium
                               text-gray-600 transition hover:bg-red-50 hover:text-red-700">
                    <x-ui.ikon nama="keluar" class="h-5 w-5 flex-none"/>
                    Keluar
                </button>
            </form>
        </div>
    </aside>

    <div class="lg:pl-72">
        <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-gray-200 bg-white/90 px-4 py-3 backdrop-blur sm:px-6">
            <button type="button" @click="laciTerbuka = true"
                    class="rounded-lg p-2 text-gray-600 hover:bg-gray-100 lg:hidden"
                    aria-label="Buka navigasi">
                <x-ui.ikon nama="menu"/>
            </button>

            <h1 class="min-w-0 flex-1 truncate text-base font-semibold text-primary-900">
                {{ $title ?? 'Resikita' }}
            </h1>

            <span class="hidden text-right sm:block">
                <span class="block text-sm font-medium text-primary-900">{{ $pengguna?->name }}</span>
                <span class="block text-xs text-gray-500">{{ $pengguna?->email }}</span>
            </span>
        </header>

        <main id="konten-utama" class="px-4 py-6 sm:px-6 lg:px-8">
            <x-ui.notifikasi/>
            {{ $slot }}
        </main>
    </div>
</div>

{{-- Livewire menyuntikkan aset dan Alpine-nya sendiri; tidak perlu direktif tambahan. --}}
</body>
</html>
