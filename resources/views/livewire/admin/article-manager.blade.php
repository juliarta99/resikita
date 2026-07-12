<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-primary-900">Artikel Edukasi</h1>
            <p class="mt-1 text-sm text-gray-500">Kelola konten edukasi yang tampil di halaman publik.</p>
        </div>
        <button wire:click="tambah" class="flex-none rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">+ Tulis Artikel</button>
    </div>

    @if (session('ok'))
        <div class="rounded-xl border border-gray-200 bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('ok') }}</div>
    @endif

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-6 py-3 font-semibold">Judul</th>
                    <th class="px-6 py-3 font-semibold">Tipe</th>
                    <th class="px-6 py-3 font-semibold">Penulis</th>
                    <th class="px-6 py-3 font-semibold">Status</th>
                    <th class="px-6 py-3 text-right font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($daftar as $a)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-primary-900">{{ $a->judul }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ $tipeList[$a->tipe] ?? $a->tipe }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ $a->author?->name ?? '—' }}</td>
                        <td class="px-6 py-3">
                            @if ($a->status === 'published')
                                <span class="rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-700">Publish</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500">Draft</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right whitespace-nowrap">
                            @if ($a->status === 'published')
                                <a href="{{ route('artikel.show', $a->slug) }}" target="_blank" class="text-sm font-medium text-gray-500 hover:text-gray-700">Lihat</a>
                            @endif
                            <button wire:click="edit({{ $a->id }})" class="ml-3 text-sm font-medium text-primary-500 hover:text-primary-700">Ubah</button>
                            <button wire:click="konfirmHapus({{ $a->id }})" class="ml-3 text-sm font-medium text-red-600 hover:text-red-700">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">Belum ada artikel.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $daftar->links() }}</div>

    <x-modal active="showForm" max-width="max-w-3xl">
        <form wire:submit="simpan">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h2 class="text-base font-semibold text-primary-900">{{ $editingId ? 'Ubah Artikel' : 'Tulis Artikel' }}</h2>
                <button type="button" wire:click="batal" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>

            <div class="max-h-[72vh] space-y-4 overflow-y-auto px-6 py-5">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-primary-900">Judul</label>
                        <input wire:model="judul" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                        @error('judul') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-900">Tipe</label>
                        <select wire:model="tipe" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                            @foreach ($tipeList as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-primary-900">Konten <span class="font-normal text-gray-400">(mendukung Markdown)</span></label>
                    <textarea wire:model="konten" rows="10" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"></textarea>
                    @error('konten') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-primary-900">Status</label>
                        <select wire:model="status" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                            <option value="draft">Draft</option>
                            <option value="published">Publish</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-900">Thumbnail</label>
                        <div class="mt-1 flex items-center gap-3">
                            @if ($thumbnail && method_exists($thumbnail, 'temporaryUrl'))
                                <img src="{{ $thumbnail->temporaryUrl() }}" class="h-14 w-20 rounded-lg border border-gray-200 object-cover">
                            @elseif ($thumbnailLama)
                                <img src="{{ asset('storage/' . $thumbnailLama) }}" class="h-14 w-20 rounded-lg border border-gray-200 object-cover">
                            @endif
                            <input type="file" wire:model="thumbnail" accept="image/*"
                                   class="text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary-700 hover:file:bg-primary-100">
                        </div>
                        <div wire:loading wire:target="thumbnail" class="mt-1 text-xs text-gray-400">Mengunggah…</div>
                        @error('thumbnail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">
                <button type="button" wire:click="batal" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-primary-900 hover:bg-gray-50">Batal</button>
                <button type="submit" class="rounded-lg bg-primary-500 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700">{{ $editingId ? 'Perbarui' : 'Simpan' }}</button>
            </div>
        </form>
    </x-modal>

    <x-confirm active="showDelete" action="hapus" title="Hapus artikel?"
               message="Artikel ini akan dihapus permanen." />
</div>