<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-primary-900">Kecamatan</h1>
            <p class="mt-1 text-sm text-gray-500">Kelola kecamatan dan akun Camat (jenis kelamin & NIP).</p>
        </div>
        <button wire:click="tambah"
                class="flex-none rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">
            + Tambah
        </button>
    </div>

    @if (session('ok'))
        <div class="rounded-xl border border-gray-200 bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('ok') }}</div>
    @endif
    @if (session('err'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ session('err') }}</div>
    @endif

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-6 py-3 font-semibold">Kecamatan</th>
                    <th class="px-6 py-3 font-semibold">Camat</th>
                    <th class="px-6 py-3 font-semibold">NIP</th>
                    <th class="px-6 py-3 font-semibold">JK</th>
                    <th class="px-6 py-3 font-semibold">Kelurahan</th>
                    <th class="px-6 py-3 text-right font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($daftar as $k)
                    @php $camat = $camats[$k->id] ?? null; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-primary-900">{{ $k->nama }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ $camat?->name ?? '—' }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ $camat?->nip ?? '—' }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ $camat?->jenis_kelamin ?: '—' }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ $k->kelurahan_count }}</td>
                        <td class="px-6 py-3 text-right">
                            <button wire:click="edit({{ $k->id }})" class="text-sm font-medium text-primary-500 hover:text-primary-700">Ubah</button>
                            <button wire:click="konfirmHapus({{ $k->id }})" class="ml-3 text-sm font-medium text-red-600 hover:text-red-700">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">Belum ada kecamatan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal tambah / ubah --}}
    <x-modal active="showForm" max-width="max-w-2xl">
        <form wire:submit="simpan">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h2 class="text-base font-semibold text-primary-900">{{ $editingId ? 'Ubah Kecamatan' : 'Tambah Kecamatan' }}</h2>
                <button type="button" wire:click="batal" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>

            <div class="grid gap-4 px-6 py-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-primary-900">Nama Kecamatan</label>
                    <input wire:model="nama" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    @error('nama') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2 border-t border-gray-200 pt-4">
                    <p class="text-xs font-semibold text-gray-400">Akun Camat</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-primary-900">Nama Camat</label>
                    <input wire:model="camat_name" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    @error('camat_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-primary-900">NIP</label>
                    <input wire:model="camat_nip" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    @error('camat_nip') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-primary-900">Email</label>
                    <input type="email" wire:model="camat_email" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    @error('camat_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-primary-900">Jenis Kelamin</label>
                    <select wire:model="camat_jk" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                        <option value="">—</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-primary-900">
                        Kata sandi @if ($editingId) <span class="font-normal text-gray-400">(kosongkan jika tidak diubah)</span> @endif
                    </label>
                    <input type="password" wire:model="camat_password" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    @error('camat_password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">
                <button type="button" wire:click="batal" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-primary-900 hover:bg-gray-50">Batal</button>
                <button type="submit" class="rounded-lg bg-primary-500 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700">
                    {{ $editingId ? 'Perbarui' : 'Simpan' }}
                </button>
            </div>
        </form>
    </x-modal>

    {{-- Konfirmasi hapus --}}
    <x-confirm active="showDelete" action="hapus" title="Hapus kecamatan?"
               message="Kecamatan dan akun Camat-nya akan dihapus permanen." />
</div>