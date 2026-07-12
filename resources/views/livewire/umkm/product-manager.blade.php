<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-primary-900">Produk</h1>
            <p class="mt-1 text-sm text-gray-500">Kelola produk daur ulang yang dijual UMKM Anda.</p>
        </div>
        <button wire:click="tambah" class="flex-none rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">+ Tambah</button>
    </div>

    @if (session('ok'))
        <div class="rounded-xl border border-primary-100 bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('ok') }}</div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($produk as $p)
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="aspect-video bg-gray-100">
                    @if ($p->images->first())
                        <img src="{{ asset('storage/' . $p->images->first()->path) }}" alt="" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full items-center justify-center text-xs text-gray-400">Tanpa gambar</div>
                    @endif
                </div>
                <div class="p-4">
                    <div class="flex items-start justify-between gap-2">
                        <p class="font-medium text-primary-900">{{ $p->nama }}</p>
                        @if (! $p->is_active)
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] text-gray-500">nonaktif</span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-primary-700">Rp {{ number_format($p->harga, 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">Stok: {{ $p->stok }} · {{ $p->berat }} g</p>
                    <div class="mt-3 flex gap-3">
                        <button wire:click="edit({{ $p->id }})" class="text-sm font-medium text-primary-500 hover:text-primary-700">Ubah</button>
                        <button wire:click="konfirmHapus({{ $p->id }})" class="text-sm font-medium text-red-600 hover:text-red-700">Hapus</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-gray-300 bg-white py-12 text-center text-sm text-gray-400">
                Belum ada produk. Klik "Tambah" untuk menambahkan.
            </div>
        @endforelse
    </div>

    <div>{{ $produk->links() }}</div>

    <x-modal active="showForm" max-width="max-w-2xl">
        <form wire:submit="simpan">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h2 class="text-base font-semibold text-primary-900">{{ $editingId ? 'Ubah Produk' : 'Tambah Produk' }}</h2>
                <button type="button" wire:click="batal" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>

            <div class="max-h-[70vh] space-y-4 overflow-y-auto px-6 py-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-primary-900">Nama Produk</label>
                        <input wire:model="nama" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                        @error('nama') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-900">Kategori</label>
                        <select wire:model="kategori_id" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                            <option value="">— Pilih kategori —</option>
                            @foreach ($kategoriList as $k)
                                <option value="{{ $k->id }}">{{ $k->nama }}</option>
                            @endforeach
                        </select>
                        @error('kategori_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-900">Harga (Rp)</label>
                        <input type="number" min="0" wire:model="harga" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                        @error('harga') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-900">Stok</label>
                        <input type="number" min="0" wire:model="stok" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                        @error('stok') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-900">Berat (gram)</label>
                        <input type="number" min="0" wire:model="berat" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                        @error('berat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-primary-900">Deskripsi</label>
                        <textarea wire:model="deskripsi" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"></textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="flex items-center gap-2 text-sm text-primary-900">
                            <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-primary-500 focus:ring-primary-500">
                            Produk aktif (tampil di marketplace)
                        </label>
                    </div>
                </div>

                {{-- Gambar --}}
                <div>
                    <label class="block text-sm font-medium text-primary-900">Gambar Produk</label>

                    @if (! empty($existingImages))
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($existingImages as $im)
                                <div class="relative">
                                    <img src="{{ asset('storage/' . $im['path']) }}" class="h-20 w-20 rounded-lg border border-gray-200 object-cover">
                                    <button type="button" wire:click="hapusGambar({{ $im['id'] }})"
                                            class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-xs text-white">✕</button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <input type="file" wire:model="newImages" multiple accept="image/*"
                           class="mt-2 block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-700 hover:file:bg-primary-100">
                    @error('newImages.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    <div wire:loading wire:target="newImages" class="mt-1 text-xs text-gray-400">Mengunggah…</div>

                    @if (! empty($newImages))
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($newImages as $img)
                                @if (method_exists($img, 'temporaryUrl'))
                                    <img src="{{ $img->temporaryUrl() }}" class="h-20 w-20 rounded-lg border border-gray-200 object-cover">
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">
                <button type="button" wire:click="batal" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-primary-900 hover:bg-gray-50">Batal</button>
                <button type="submit" class="rounded-lg bg-primary-500 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700">{{ $editingId ? 'Perbarui' : 'Simpan' }}</button>
            </div>
        </form>
    </x-modal>

    <x-confirm active="showDelete" action="hapus" title="Hapus produk?"
               message="Produk beserta gambarnya akan dihapus permanen." />
</div>