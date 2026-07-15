<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-primary-900">Kategori Produk UMKM</h1>
            <p class="mt-1 text-sm text-gray-500">Kategori untuk produk di marketplace daur ulang.</p>
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
                    <th class="px-6 py-3 font-semibold">Kategori</th>
                    <th class="px-6 py-3 text-right font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($daftar as $c)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-primary-900">{{ $c->nama }}</td>
                        <td class="px-6 py-3 text-right">
                            <button wire:click="edit({{ $c->id }})" class="text-sm font-medium text-primary-500 hover:text-primary-700">Ubah</button>
                            <button wire:click="konfirmHapus({{ $c->id }})" class="ml-3 text-sm font-medium text-red-600 hover:text-red-700">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="px-6 py-8 text-center text-gray-400">Belum ada kategori.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-modal active="showForm" max-width="max-w-lg">
        <form wire:submit="simpan">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h2 class="text-base font-semibold text-primary-900">{{ $editingId ? 'Ubah Kategori' : 'Tambah Kategori' }}</h2>
                <button type="button" wire:click="batal" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <div class="px-6 py-5">
                <label class="block text-sm font-medium text-primary-900">Nama Kategori</label>
                <input wire:model="nama" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                @error('nama') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">
                <button type="button" wire:click="batal" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-primary-900 hover:bg-gray-50">Batal</button>
                <button type="submit" class="rounded-lg bg-primary-500 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700">{{ $editingId ? 'Perbarui' : 'Simpan' }}</button>
            </div>
        </form>
    </x-modal>

    <x-confirm active="showDelete" action="hapus" title="Hapus kategori?"
               message="Kategori produk ini akan dihapus permanen." />
</div>