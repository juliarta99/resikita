<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-primary-900">TPS</h1>
            <p class="mt-1 text-sm text-gray-500">Kelola TPS, titik lokasi, dan akun Admin TPS.</p>
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
                    <th class="px-6 py-3 font-semibold">Nama TPS</th>
                    <th class="px-6 py-3 font-semibold">Banjar Dinas</th>
                    <th class="px-6 py-3 font-semibold">Biaya</th>
                    <th class="px-6 py-3 font-semibold">Lokasi</th>
                    <th class="px-6 py-3 text-right font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($daftar as $tps)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-primary-900">{{ $tps->nama }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ $tps->banjarDinas?->nama ?? '—' }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ $tps->is_berbayar ? 'Rp ' . number_format($tps->tarif, 0, ',', '.') : 'Gratis' }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ $tps->lat && $tps->lng ? 'Ada' : '—' }}</td>
                        <td class="px-6 py-3 text-right">
                            <button wire:click="edit({{ $tps->id }})" class="text-sm font-medium text-primary-500 hover:text-primary-700">Ubah</button>
                            <button wire:click="konfirmHapus({{ $tps->id }})" class="ml-3 text-sm font-medium text-red-600 hover:text-red-700">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">Belum ada TPS.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-modal active="showForm" max-width="max-w-2xl">
        <form wire:submit="simpan">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h2 class="text-base font-semibold text-primary-900">{{ $editingId ? 'Ubah TPS' : 'Tambah TPS' }}</h2>
                <button type="button" wire:click="batal" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>

            <div class="max-h-[70vh] overflow-y-auto px-6 py-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-primary-900">Nama TPS</label>
                        <input wire:model="nama" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                        @error('nama') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-900">Banjar Dinas</label>
                        <select wire:model="banjar_id" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                            <option value="">— Pilih banjar dinas —</option>
                            @foreach ($banjarList as $b)
                                <option value="{{ $b->id }}">{{ $b->nama }} — {{ $b->kelurahan->nama }}</option>
                            @endforeach
                        </select>
                        @error('banjar_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-900">Alamat</label>
                        <input wire:model="alamat" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-900">No. HP</label>
                        <input wire:model="no_hp" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    </div>
                    <div class="sm:col-span-2 flex items-center gap-6">
                        <label class="flex items-center gap-2 text-sm text-primary-900">
                            <input type="checkbox" wire:model.live="is_berbayar" class="rounded border-gray-300 text-primary-500 focus:ring-primary-500">
                            TPS berbayar
                        </label>
                        @if ($is_berbayar)
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-gray-500">Tarif (Rp)</span>
                                <input type="number" wire:model="tarif" min="0" class="w-40 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                            </div>
                        @endif
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-primary-900">Titik Lokasi</label>
                        <div class="mt-1"><x-map-picker :lat="$lat" :lng="$lng" /></div>
                        <div class="mt-2 grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500">Latitude</label>
                                <input wire:model.blur="lat" placeholder="-8.6478" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                @error('lat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500">Longitude</label>
                                <input wire:model.blur="lng" placeholder="115.1385" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                @error('lng') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="sm:col-span-2 border-t border-gray-200 pt-4">
                        <p class="text-xs font-semibold text-gray-400">Akun Admin TPS</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-900">Nama Admin</label>
                        <input wire:model="admin_name" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                        @error('admin_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-900">Email Admin</label>
                        <input type="email" wire:model="admin_email" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                        @error('admin_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-primary-900">
                            Kata sandi Admin @if ($editingId) <span class="font-normal text-gray-400">(kosongkan jika tidak diubah)</span> @endif
                        </label>
                        <input type="password" wire:model="admin_password" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                        @error('admin_password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">
                <button type="button" wire:click="batal" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-primary-900 hover:bg-gray-50">Batal</button>
                <button type="submit" class="rounded-lg bg-primary-500 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700">{{ $editingId ? 'Perbarui' : 'Simpan' }}</button>
            </div>
        </form>
    </x-modal>

    <x-confirm active="showDelete" action="hapus" title="Hapus TPS?"
               message="TPS, data nasabah/langganannya, dan akun Admin TPS akan dihapus permanen." />
</div>