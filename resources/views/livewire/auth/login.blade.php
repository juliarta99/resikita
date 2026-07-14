<div class="min-h-screen flex items-center justify-center bg-primary-50 px-4">
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-3 flex w-18 h-18 p-3 items-center justify-center rounded-xl bg-primary-500">
                <img src="{{ asset('images/logo.png') }}" class="w-16" alt="Niti Resik">
            </div>
            <h1 class="text-2xl font-bold text-primary-900">Niti Resik</h1>
            <p class="text-sm text-primary-500">Bersama Wujudkan Bumi Bersih</p>
        </div>

        <div class="rounded-2xl border border-primary-100 bg-white p-6 shadow-sm">
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
            Masyarakat &amp; petugas lapangan masuk lewat aplikasi mobile.
        </p>
    </div>
</div>