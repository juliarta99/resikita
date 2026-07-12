<div class="min-h-screen bg-primary-50 px-4 py-10">
    <div class="mx-auto max-w-lg">
        <div class="mb-6 text-center">
            <a href="/" class="text-sm text-primary-500 hover:text-primary-700">&larr; Kembali</a>
            <h1 class="mt-3 text-2xl font-bold text-primary-900">Daftar sebagai UMKM</h1>
            <p class="mt-1 text-sm text-primary-900/60">Jual produk ramah lingkungan di marketplace Niti Resik.</p>
        </div>

        @if ($submitted)
            <div class="rounded-2xl border border-primary-100 bg-white p-8 text-center shadow-sm">
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
                <a href="/" class="mt-6 inline-block rounded-lg bg-primary-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">
                    Selesai
                </a>
            </div>
        @else
            <form wire:submit="daftar" class="space-y-5 rounded-2xl border border-primary-100 bg-white p-6 shadow-sm">
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