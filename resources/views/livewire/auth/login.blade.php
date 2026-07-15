<div class="relative min-h-screen flex items-center justify-center bg-primary-50 px-4 overflow-hidden">

    {{-- ==== Background pattern (halus, tidak mengganggu) ==== --}}
    <div class="pointer-events-none absolute inset-0"
         style="opacity:.5;background-image:radial-gradient(#057d5d1a 1.2px, transparent 1.2px);background-size:22px 22px;"></div>
    {{-- Gradient vignette agar tengah lebih terang, fokus ke kartu --}}
    <div class="pointer-events-none absolute inset-0"
         style="background:radial-gradient(ellipse at center, transparent 0%, transparent 40%, #e0f8f1cc 100%);"></div>

    {{-- ==== Dekorasi beranimasi (desktop only, warna primary, dibuat samar) ==== --}}
    <style>
        @keyframes nr-float { 0%,100% { transform: translateY(0) } 50% { transform: translateY(-18px) } }
        @keyframes nr-float-slow { 0%,100% { transform: translateY(0) } 50% { transform: translateY(-12px) } }
        @keyframes nr-drift { 0%,100% { transform: translate(0,0) rotate(0deg) } 50% { transform: translate(12px,-16px) rotate(8deg) } }
        @keyframes nr-pulse { 0%,100% { transform: scale(1); opacity:.35 } 50% { transform: scale(1.12); opacity:.55 } }
        @keyframes nr-spin { from { transform: rotate(0) } to { transform: rotate(360deg) } }
        .nr-float      { animation: nr-float 6s ease-in-out infinite }
        .nr-float-slow { animation: nr-float-slow 8s ease-in-out infinite }
        .nr-drift      { animation: nr-drift 9s ease-in-out infinite }
        .nr-pulse      { animation: nr-pulse 6s ease-in-out infinite }
        .nr-spin       { animation: nr-spin 30s linear infinite }
        @media (prefers-reduced-motion: reduce) { .nr-float,.nr-float-slow,.nr-drift,.nr-pulse,.nr-spin { animation: none } }
    </style>

    {{-- Blob primary lembut di sudut --}}
    <div class="pointer-events-none absolute -left-28 -top-28 hidden h-80 w-80 rounded-full bg-primary-500/20 blur-3xl nr-pulse lg:block"></div>
    <div class="pointer-events-none absolute -right-28 -bottom-28 hidden h-96 w-96 rounded-full bg-primary-700/15 blur-3xl nr-pulse lg:block" style="animation-delay:2s"></div>

    {{-- KIRI: kartu-kartu melayang (samar, monokrom primary) --}}
    <div class="pointer-events-none absolute inset-y-0 left-0 hidden w-1/3 opacity-70 lg:block">
        <div class="nr-float absolute left-[14%] top-[22%] flex items-center gap-3 rounded-2xl border border-primary-100 bg-white/70 p-3 shadow-lg backdrop-blur">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-500">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </span>
            <div>
                <p class="text-xs font-bold text-primary-900">Sampah terdeteksi</p>
                <p class="text-[11px] text-primary-500/70">Botol PET · Anorganik</p>
            </div>
        </div>

        <div class="nr-float-slow absolute left-[28%] top-[54%] flex items-center gap-3 rounded-2xl border border-primary-100 bg-white/70 p-3 shadow-lg backdrop-blur" style="animation-delay:.8s">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-500">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.1 0-2 .9-2 2s.9 2 2 2 2 .9 2 2-.9 2-2 2m0-8V6m0 12v-2"/></svg>
            </span>
            <div>
                <p class="text-xs font-bold text-primary-900">+Rp 2.500</p>
                <p class="text-[11px] text-primary-500/70">Saldo bank sampah</p>
            </div>
        </div>

        <div class="nr-drift absolute left-[18%] top-[80%] h-14 w-14 rounded-2xl bg-primary-500/15"></div>
    </div>

    {{-- KANAN: kartu-kartu melayang --}}
    <div class="pointer-events-none absolute inset-y-0 right-0 hidden w-1/3 opacity-70 lg:block">
        <div class="nr-float-slow absolute right-[16%] top-[26%] flex items-center gap-3 rounded-2xl border border-primary-100 bg-white/70 p-3 shadow-lg backdrop-blur">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-500">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </span>
            <div>
                <p class="text-xs font-bold text-primary-900">Laporan ditindak</p>
                <p class="text-[11px] text-primary-500/70">Petugas menuju lokasi</p>
            </div>
        </div>

        <div class="nr-float absolute right-[26%] top-[58%] flex items-center gap-3 rounded-2xl border border-primary-100 bg-white/70 p-3 shadow-lg backdrop-blur" style="animation-delay:1.1s">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-500">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 4h12"/></svg>
            </span>
            <div>
                <p class="text-xs font-bold text-primary-900">Produk daur ulang</p>
                <p class="text-[11px] text-primary-500/70">Tas dari plastik bekas</p>
            </div>
        </div>

        {{-- Ikon daun primary berputar pelan --}}
        <div class="nr-spin absolute right-[18%] top-[82%] text-primary-500/40">
            <svg class="h-16 w-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6"/></svg>
        </div>
    </div>

    {{-- ==== Kartu login (fokus utama) ==== --}}
    <div class="relative z-10 w-full max-w-sm">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-3 flex w-18 h-18 p-3 items-center justify-center rounded-xl bg-primary-500 shadow-lg shadow-primary-500/25">
                <img src="{{ asset('images/logo.png') }}" class="w-16" alt="Niti Resik">
            </div>
            <h1 class="text-2xl font-bold text-primary-900">Niti Resik</h1>
            <p class="text-sm text-primary-500">Bersama Wujudkan Bumi Bersih</p>
        </div>

        <div class="rounded-2xl border border-primary-100 bg-white p-6 shadow-xl shadow-primary-900/5">
            <form wire:submit="login" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-primary-900">Email</label>
                    <input type="email" wire:model="email" autofocus
                           class="mt-1 w-full rounded-lg border border-primary-100 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-primary-900">Kata sandi</label>
                    <input type="password" wire:model="password"
                           class="mt-1 w-full rounded-lg border border-primary-100 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-primary-900/70">
                    <input type="checkbox" wire:model="remember"
                           class="rounded border-primary-100 text-primary-500 focus:ring-primary-500">
                    Ingat saya
                </label>

                <button type="submit" wire:loading.attr="disabled"
                        class="w-full rounded-lg bg-primary-500 py-2.5 font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                    <span wire:loading.remove wire:target="login">Masuk</span>
                    <span wire:loading wire:target="login">Memproses...</span>
                </button>
                <div class="w-full">
                    <a href="/"
                        class="block w-full text-center rounded-lg bg-white py-2.5 font-medium border border-gray-200 text-gray-500 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                        Kembali ke Beranda
                    </a>
                </div>
            </form>
        </div>

        <p class="mt-4 text-center text-xs text-primary-900/50">
            Masyarakat masuk lewat aplikasi mobile.
        </p>
    </div>
</div>