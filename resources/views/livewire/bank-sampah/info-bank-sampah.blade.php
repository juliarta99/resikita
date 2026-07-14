<div class="mx-auto max-w-2xl space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-primary-900">Informasi Bank Sampah</h1>
        <p class="mt-1 text-sm text-gray-500">Perbarui data, lokasi, dan foto bank sampah Anda.</p>
    </div>
    @if (session('ok'))
        <div class="rounded-xl border border-primary-100 bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('ok') }}</div>
    @endif
    <form wire:submit="simpan" class="space-y-5 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
        <div>
            <label class="block text-sm font-medium text-primary-900">Nama Bank Sampah</label>
            <input wire:model="nama" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
            @error('nama') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-primary-900">No. HP</label>
                <input wire:model="no_hp" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-primary-900">Alamat</label>
                <input wire:model="alamat" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-primary-900">Titik Lokasi</label>
            <div class="mt-1"><x-map-picker :lat="$lat" :lng="$lng" /></div>
            <div class="mt-2 grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500">Latitude</label>
                    <input wire:model.blur="lat" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    @error('lat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500">Longitude</label>
                    <input wire:model.blur="lng" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    @error('lng') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-primary-900">Foto</label>
            <div class="mt-2 flex flex-col gap-4 sm:flex-row sm:items-center">
                @if ($foto && method_exists($foto, 'temporaryUrl'))
                    <img src="{{ $foto->temporaryUrl() }}" class="h-24 w-24 shrink-0 rounded-lg border border-gray-200 object-cover">
                @elseif ($fotoLama)
                    <img src="{{ asset('storage/' . $fotoLama) }}" class="h-24 w-24 shrink-0 rounded-lg border border-gray-200 object-cover">
                @else
                    <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-lg border border-dashed border-gray-300 text-xs text-gray-400">Tanpa foto</div>
                @endif
                <input type="file" wire:model="foto" accept="image/*"
                       class="text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-700 hover:file:bg-primary-100">
            </div>
            <div wire:loading wire:target="foto" class="mt-1 text-xs text-gray-400">Mengunggah…</div>
            @error('foto') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="pt-1">
            <button type="submit" class="w-full rounded-lg bg-primary-500 px-6 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 sm:w-auto sm:float-right">Simpan Perubahan</button>
        </div>
    </form>
</div>