<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Niti Resik' }}</title>
    <style>[x-cloak]{display:none!important}</style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-full bg-[#f5f6f8] text-primary-900 antialiased">
<div x-data="{ open: false }" class="min-h-full">

    {{-- Sidebar --}}
    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-64 transform flex-col bg-primary-900 text-white transition-transform duration-200 lg:translate-x-0"
        :class="open ? 'translate-x-0' : '-translate-x-full'">

        <div class="flex h-16 flex-none items-center gap-2.5 px-6">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-500">
                <img src="{{ asset('images/logo.png') }}" class="w-6" alt="Niti Resik">
            </div>
            <span class="text-lg font-semibold tracking-tight">Niti Resik</span>
        </div>

        @php
            $item = 'block rounded-lg px-3 py-2 text-sm font-medium';
            $active = 'bg-primary-500 text-white';
            $idle = 'text-primary-100 hover:bg-primary-700/60 hover:text-white';
        @endphp

        <nav class="mt-2 flex-1 space-y-6 overflow-y-auto px-3 pb-8">
            <div class="space-y-1">
                <a href="{{ route('admin.dashboard') }}" class="{{ $item }} {{ request()->routeIs('admin.dashboard') ? $active : $idle }}">Dashboard</a>
            </div>

            <div>
                <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-primary-100/60">Wilayah</p>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('admin.kecamatan') }}" class="{{ $item }} {{ request()->routeIs('admin.kecamatan') ? $active : $idle }}">Kecamatan</a>
                    <a href="{{ route('admin.kelurahan') }}" class="{{ $item }} {{ request()->routeIs('admin.kelurahan') ? $active : $idle }}">Kelurahan</a>
                    <a href="{{ route('admin.banjar') }}" class="{{ $item }} {{ request()->routeIs('admin.banjar') ? $active : $idle }}">Banjar Dinas</a>
                </div>
            </div>

            <div>
                <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-primary-100/60">Master Data</p>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('admin.tps') }}" class="{{ $item }} {{ request()->routeIs('admin.tps') ? $active : $idle }}">TPS</a>
                    <a href="{{ route('admin.bank-sampah') }}" class="{{ $item }} {{ request()->routeIs('admin.bank-sampah') ? $active : $idle }}">Bank Sampah</a>
                    <a href="{{ route('admin.umkm') }}" class="{{ $item }} {{ request()->routeIs('admin.umkm') ? $active : $idle }}">UMKM</a>
                    <a href="{{ route('admin.harga-sampah') }}" class="{{ $item }} {{ request()->routeIs('admin.harga-sampah') ? $active : $idle }}">Harga Sampah</a>
                    <a href="{{ route('admin.kategori-laporan') }}" class="{{ $item }} {{ request()->routeIs('admin.kategori-laporan') ? $active : $idle }}">Kategori Laporan</a>
                    <a href="{{ route('admin.kategori-produk') }}" class="{{ $item }} {{ request()->routeIs('admin.kategori-produk') ? $active : $idle }}">Kategori Produk</a>
                </div>
            </div>

            <div>
                <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-primary-100/60">Pengawasan</p>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('admin.laporan') }}" class="{{ $item }} {{ request()->routeIs('admin.laporan') ? $active : $idle }}">Seluruh Laporan</a>
                    <a href="{{ route('admin.produk') }}" class="{{ $item }} {{ request()->routeIs('admin.produk') ? $active : $idle }}">Seluruh Produk</a>
                </div>
            </div>

            <div>
                <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-primary-100/60">Sistem</p>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('admin.pengguna') }}" class="{{ $item }} {{ request()->routeIs('admin.pengguna') ? $active : $idle }}">Manajemen Pengguna</a>
                    <a href="{{ route('admin.penarikan') }}" class="{{ $item }} {{ request()->routeIs('admin.penarikan') ? $active : $idle }}">Penarikan Saldo</a>
                    <a href="{{ route('admin.penarikan-umkm') }}" class="{{ $item }} {{ request()->routeIs('admin.penarikan-umkm') ? $active : $idle }}">Penarikan UMKM</a>
                    <a href="{{ route('admin.artikel') }}" class="{{ $item }} {{ request()->routeIs('admin.artikel') ? $active : $idle }}">Artikel Edukasi</a>
                    <a href="{{ route('admin.profil') }}" class="{{ $item }} {{ request()->routeIs('admin.profil') ? $active : $idle }}">Profil</a>
                </div>
            </div>
        </nav>
    </aside>

    {{-- Overlay mobile --}}
    <div x-show="open" x-cloak @click="open = false"
         class="fixed inset-0 z-40 bg-primary-900/40 lg:hidden"></div>

    {{-- Konten --}}
    <div class="lg:pl-64">
        <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 lg:px-8">
            <button @click="open = true" class="rounded-lg p-2 text-primary-900 hover:bg-gray-100 lg:hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            {{-- Menu user + konfirmasi keluar --}}
            <div class="ml-auto flex items-center gap-3" x-data="{ confirmLogout: false }">
                <span class="text-sm text-gray-500">{{ auth()->user()?->name }}</span>
                <button type="button" @click="confirmLogout = true"
                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-primary-900 hover:bg-gray-50">
                    Keluar
                </button>

                <div x-show="confirmLogout" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div x-show="confirmLogout" x-transition.opacity class="fixed inset-0 bg-primary-900/40"></div>
                    <div x-show="confirmLogout" x-transition @click.outside="confirmLogout = false"
                         @keydown.escape.window="confirmLogout = false"
                         class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                        <h3 class="text-base font-semibold text-primary-900">Keluar dari akun?</h3>
                        <p class="mt-1 text-sm text-gray-500">Anda akan keluar dari sesi ini dan harus masuk kembali.</p>
                        <div class="mt-5 flex justify-end gap-2">
                            <button type="button" @click="confirmLogout = false"
                                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-primary-900 hover:bg-gray-50">
                                Batal
                            </button>
                            <form method="POST" action="/logout">
                                @csrf
                                <button type="submit"
                                        class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="px-4 py-6 lg:px-8 lg:py-8">
            {{ $slot }}
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>