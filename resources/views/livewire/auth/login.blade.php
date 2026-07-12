<div class="min-h-screen flex items-center justify-center bg-emerald-50 px-4">
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-bold text-emerald-900">Niti Resik</h1>
            <p class="text-sm text-emerald-700">Panel Pengelolaan Sampah Terpadu</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-emerald-100 p-6">
            <form wire:submit="login" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" wire:model="email" autofocus
                           class="mt-1 w-full rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Kata sandi</label>
                    <input type="password" wire:model="password"
                           class="mt-1 w-full rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                    @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" wire:model="remember"
                           class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                    Ingat saya
                </label>

                <button type="submit" wire:loading.attr="disabled"
                        class="w-full rounded-lg bg-emerald-600 py-2.5 text-white font-medium hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                    <span wire:loading.remove wire:target="login">Masuk</span>
                    <span wire:loading wire:target="login">Memproses...</span>
                </button>
            </form>
        </div>

        <p class="mt-4 text-center text-xs text-gray-500">
            Masyarakat &amp; petugas lapangan masuk lewat aplikasi mobile.
        </p>
    </div>
</div>
