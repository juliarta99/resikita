<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-primary-900">Harga Sampah</h1>
            <p class="mt-1 text-sm text-gray-500">Harga berlaku sama untuk seluruh bank sampah.</p>
        </div>
        <button wire:click="tambah" class="flex-none rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">+ Tambah</button>
    </div>

    @if (session('ok'))
        <div class="rounded-xl border border-gray-200 bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('ok') }}</div>
    @endif

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-6 py-3 font-semibold">Jenis Sampah</th>
                    <th class="px-6 py-3 font-semibold">Harga</th>
                    <th class="px-6 py-3 font-semibold">Status</th>
                    <th class="px-6 py-3 text-right font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($daftar as $w)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-primary-900">{{ $w->jenis_sampah }}</td>
                        <td class="px-6 py-3 text-gray-600">Rp {{ number_format($w->harga_per_kg, 0, ',', '.') }} / {{ $w->satuan }}</td>
                        <td class="px-6 py-3">
                            <button wire:click="toggleAktif({{ $w->id }})"
                                    class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $w->is_active ? 'bg-primary-50 text-primary-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $w->is_active ? 'Aktif' : 'Nonaktif' }}
                            </button>
                        </td>
                        <td class="px-6 py-3 text-right">
                            <button wire:click="edit({{ $w->id }})" class="text-sm font-medium text-primary-500 hover:text-primary-700">Ubah</button>
                            <button wire:click="konfirmHapus({{ $w->id }})" class="ml-3 text-sm font-medium text-red-600 hover:text-red-700">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">Belum ada data harga.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-modal active="showForm" max-width="max-w-xl">
        <form wire:submit="simpan">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h2 class="text-base font-semibold text-primary-900">{{ $editingId ? 'Ubah Harga' : 'Tambah Harga' }}</h2>
                <button type="button" wire:click="batal" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <div class="grid gap-4 px-6 py-5 sm:grid-cols-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-primary-900">Jenis Sampah</label>
                    <input wire:model="jenis_sampah" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    @error('jenis_sampah') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-primary-900">Satuan</label>
                    <input wire:model="satuan" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-primary-900">Harga (Rp)</label>
                    <input type="number" wire:model="harga_per_kg" min="0" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    @error('harga_per_kg') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-4">
                    <label class="flex items-center gap-2 text-sm text-primary-900">
                        <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-primary-500 focus:ring-primary-500">
                        Aktif
                    </label>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">
                <button type="button" wire:click="batal" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-primary-900 hover:bg-gray-50">Batal</button>
                <button type="submit" class="rounded-lg bg-primary-500 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700">{{ $editingId ? 'Perbarui' : 'Simpan' }}</button>
            </div>
        </form>
    </x-modal>

    <x-confirm active="showDelete" action="hapus" title="Hapus harga sampah?"
               message="Data harga ini akan dihapus permanen." />
</div>