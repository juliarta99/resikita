<div class="space-y-6">
    <div>
        <h1 class="text-xl font-semibold text-gray-900">Manajemen Kecamatan</h1>
        <p class="text-sm text-gray-500">Menambah kecamatan sekaligus membuat akun Camat.</p>
    </div>

    @if (session('ok'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('ok') }}
        </div>
    @endif

    <form wire:submit="simpan" class="bg-white rounded-xl border border-gray-200 p-5 grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700">Nama Kecamatan</label>
            <input wire:model="nama" class="mt-1 w-full rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
            @error('nama') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2 border-t border-gray-100 pt-4 text-sm font-medium text-gray-500">Akun Camat</div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Nama Camat</label>
            <input wire:model="camat_name" class="mt-1 w-full rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
            @error('camat_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Email Camat</label>
            <input type="email" wire:model="camat_email" class="mt-1 w-full rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
            @error('camat_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700">Kata sandi Camat</label>
            <input type="password" wire:model="camat_password" class="mt-1 w-full rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
            @error('camat_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2">
            <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-white text-sm font-medium hover:bg-emerald-700">
                Simpan
            </button>
        </div>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
        @forelse ($daftar as $k)
            <div class="px-5 py-3 text-sm text-gray-800">{{ $k->nama }}</div>
        @empty
            <div class="px-5 py-6 text-sm text-gray-500 text-center">Belum ada kecamatan.</div>
        @endforelse
    </div>
</div>
