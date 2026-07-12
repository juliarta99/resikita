<div class="mx-auto max-w-xl space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-primary-900">Profil</h1>
        <p class="mt-1 text-sm text-gray-500">Perbarui data akun Anda.</p>
    </div>
    @if (session('ok'))
        <div class="rounded-xl border border-gray-200 bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('ok') }}</div>
    @endif
    <form wire:submit="simpan" class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div><label class="block text-sm font-medium text-primary-900">Nama</label><input wire:model="name" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">@error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
        <div><label class="block text-sm font-medium text-primary-900">Email</label><input type="email" wire:model="email" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">@error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
        <div><label class="block text-sm font-medium text-primary-900">No. HP</label><input wire:model="phone" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"></div>
        <div><label class="block text-sm font-medium text-primary-900">Kata sandi baru <span class="font-normal text-gray-400">(kosongkan jika tidak diubah)</span></label><input type="password" wire:model="password" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">@error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
        <div class="flex justify-end"><button type="submit" class="rounded-lg bg-primary-500 px-6 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">Simpan</button></div>
    </form>
</div>