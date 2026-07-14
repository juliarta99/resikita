<div class="relative min-h-screen overflow-hidden bg-primary-50 px-4 py-10">

    {{-- ==== Background pattern (halus) ==== --}}
    <div class="pointer-events-none absolute inset-0"
         style="opacity:.55;background-image:radial-gradient(#057d5d1a 1.2px, transparent 1.2px);background-size:22px 22px;"></div>

    {{-- ==== Dekorasi beranimasi (desktop only, tema UMKM) ==== --}}
    <style>
        @keyframes nr-float { 0%,100% { transform: translateY(0) } 50% { transform: translateY(-18px) } }
        @keyframes nr-float-slow { 0%,100% { transform: translateY(0) } 50% { transform: translateY(-12px) } }
        @keyframes nr-drift { 0%,100% { transform: translate(0,0) rotate(0deg) } 50% { transform: translate(12px,-16px) rotate(8deg) } }
        @keyframes nr-pulse { 0%,100% { transform: scale(1); opacity:.35 } 50% { transform: scale(1.12); opacity:.55 } }
        .nr-float      { animation: nr-float 6s ease-in-out infinite }
        .nr-float-slow { animation: nr-float-slow 8s ease-in-out infinite }
        .nr-drift      { animation: nr-drift 9s ease-in-out infinite }
        .nr-pulse      { animation: nr-pulse 6s ease-in-out infinite }
        @media (prefers-reduced-motion: reduce) { .nr-float,.nr-float-slow,.nr-drift,.nr-pulse { animation: none } }
    </style>

    {{-- Blob primary di sudut --}}
    <div class="pointer-events-none absolute -left-28 -top-28 hidden h-80 w-80 rounded-full bg-primary-500/15 blur-3xl nr-pulse lg:block"></div>
    <div class="pointer-events-none absolute -right-28 -bottom-24 hidden h-96 w-96 rounded-full bg-primary-700/12 blur-3xl nr-pulse lg:block" style="animation-delay:2s"></div>

    {{-- Kartu mini melayang di kiri --}}
    <div class="pointer-events-none absolute inset-y-0 left-0 hidden w-[22%] opacity-70 xl:block">
        <div class="nr-float absolute left-[18%] top-[24%] flex items-center gap-3 rounded-2xl border border-primary-100 bg-white/70 p-3 shadow-lg backdrop-blur">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-500">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9l1.5-5h15L21 9M4 9h16v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9z"/></svg>
            </span>
            <div>
                <p class="text-xs font-bold text-primary-900">Produk daur ulang</p>
                <p class="text-[11px] text-primary-500/70">Tas dari plastik bekas</p>
            </div>
        </div>
        <div class="nr-drift absolute left-[24%] top-[64%] h-14 w-14 rounded-2xl bg-primary-500/12"></div>
    </div>

    {{-- Kartu mini melayang di kanan --}}
    <div class="pointer-events-none absolute inset-y-0 right-0 hidden w-[22%] opacity-70 xl:block">
        <div class="nr-float-slow absolute right-[18%] top-[30%] flex items-center gap-3 rounded-2xl border border-primary-100 bg-white/70 p-3 shadow-lg backdrop-blur">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-500">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 4h12"/></svg>
            </span>
            <div>
                <p class="text-xs font-bold text-primary-900">Pesanan masuk</p>
                <p class="text-[11px] text-primary-500/70">Jangkau lebih luas</p>
            </div>
        </div>
        {{-- Ikon toko besar melayang --}}
        <div class="nr-float absolute right-[16%] bottom-[16%] text-primary-500/20" style="animation-delay:1s">
            <svg class="h-24 w-24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l1.5-5h15L21 9M4 9h16v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9zM3 9a2.5 2.5 0 0 0 5 0 2.5 2.5 0 0 0 4 0 2.5 2.5 0 0 0 4 0 2.5 2.5 0 0 0 5 0M9 20v-5h6v5"/></svg>
        </div>
    </div>

    {{-- ==== Konten utama ==== --}}
    <div class="relative z-10 mx-auto max-w-lg">
        <div class="mb-6 text-center">
            <a href="/" class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-500 hover:text-primary-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 0 0 1 1h3v-6h6v6h3a1 1 0 0 0 1-1V10"/></svg>
                Kembali ke Beranda
            </a>
            <div class="mx-auto mt-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-500 shadow-lg shadow-primary-500/25">
                <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9l1.5-5h15L21 9M4 9h16v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9zM9 20v-5h6v5"/></svg>
            </div>
            <h1 class="mt-3 text-2xl font-bold text-primary-900">Daftar sebagai UMKM</h1>
            <p class="mt-1 text-sm text-primary-900/60">Jual produk ramah lingkungan di marketplace Niti Resik.</p>
        </div>
        @if ($submitted)
            <div class="rounded-2xl border border-primary-100 bg-white p-8 text-center shadow-xl shadow-primary-900/5">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary-500" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h2 class="mt-4 text-lg font-semibold text-primary-900">Pendaftaran terkirim</h2>
                <p class="mt-2 text-sm text-primary-900/60">
                    Pendaftaran Anda sedang menunggu verifikasi admin. Setelah disetujui, akun Anda dapat digunakan untuk masuk.
                </p>
                <a href="/" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-primary-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 0 0 1 1h3v-6h6v6h3a1 1 0 0 0 1-1V10"/></svg>
                    Selesai
                </a>
            </div>
        @else
            <form wire:submit="daftar" class="space-y-5 rounded-2xl border border-primary-100 bg-white p-6 shadow-xl shadow-primary-900/5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-primary-900/50">Data Usaha</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-primary-900">Nama UMKM</label>
                    <input wire:model="nama"
                           class="mt-1 w-full rounded-lg border border-primary-100 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    @error('nama') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-primary-900">Deskripsi singkat</label>
                    <textarea wire:model="deskripsi" rows="2"
                              class="mt-1 w-full rounded-lg border border-primary-100 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"></textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-primary-900">Alamat</label>
                        <input wire:model="alamat"
                               class="mt-1 w-full rounded-lg border border-primary-100 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-900">No. HP Usaha</label>
                        <input wire:model="no_hp"
                               class="mt-1 w-full rounded-lg border border-primary-100 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    </div>
                </div>
                <div class="border-t border-primary-100 pt-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-primary-900/50">Akun Pemilik</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-primary-900">Nama pemilik</label>
                        <input wire:model="pemilik_name"
                               class="mt-1 w-full rounded-lg border border-primary-100 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                        @error('pemilik_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-900">No. HP pemilik</label>
                        <input wire:model="pemilik_phone"
                               class="mt-1 w-full rounded-lg border border-primary-100 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-primary-900">Email</label>
                    <input type="email" wire:model="pemilik_email"
                           class="mt-1 w-full rounded-lg border border-primary-100 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    @error('pemilik_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-primary-900">Kata sandi</label>
                        <input type="password" wire:model="password"
                               class="mt-1 w-full rounded-lg border border-primary-100 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                        @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-900">Ulangi kata sandi</label>
                        <input type="password" wire:model="password_confirmation"
                               class="mt-1 w-full rounded-lg border border-primary-100 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    </div>
                </div>
                <button type="submit" wire:loading.attr="disabled"
                        class="w-full rounded-lg bg-primary-500 py-2.5 font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                    <span wire:loading.remove wire:target="daftar">Kirim Pendaftaran</span>
                    <span wire:loading wire:target="daftar">Mengirim...</span>
                </button>
            </form>
        @endif
    </div>
</div>