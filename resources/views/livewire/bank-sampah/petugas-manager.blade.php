<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-primary-900">Manajemen Petugas</h1>
            <p class="mt-1 text-sm text-gray-500">Kelola akun petugas bank sampah Anda.</p>
        </div>
        <button wire:click="tambah" class="flex-none rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">+ Tambah Petugas</button>
    </div>

    @if (session('ok'))
        <div class="rounded-xl border border-primary-100 bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('ok') }}</div>
    @endif

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-6 py-3 font-semibold">Nama</th>
                    <th class="px-6 py-3 font-semibold">Email</th>
                    <th class="px-6 py-3 font-semibold">Status</th>
                    <th class="px-6 py-3 text-right font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($petugas as $p)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-primary-900">{{ $p->name }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ $p->email }}</td>
                        <td class="px-6 py-3">
                            <button wire:click="toggleAktif({{ $p->id }})"
                                    class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $p->is_active ? 'bg-primary-50 text-primary-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $p->is_active ? 'Aktif' : 'Nonaktif' }}
                            </button>
                        </td>
                        <td class="px-6 py-3 text-right whitespace-nowrap">
                            <button wire:click="bukaReset({{ $p->id }})" class="text-sm font-medium text-gray-500 hover:text-gray-700">Reset Sandi</button>
                            <button wire:click="konfirmHapus({{ $p->id }})" class="ml-3 text-sm font-medium text-red-600 hover:text-red-700">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">Belum ada petugas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal tambah petugas --}}
    <x-modal active="showForm" max-width="max-w-md">
        <form wire:submit="simpan">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h2 class="text-base font-semibold text-primary-900">Tambah Petugas</h2>
                <button type="button" wire:click="$set('showForm', false)" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <div class="space-y-4 px-6 py-5">
                <div>
                    <label class="block text-sm font-medium text-primary-900">Nama</label>
                    <input wire:model="name" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-primary-900">Email</label>
                    <input type="email" wire:model="email" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-primary-900">Kata sandi</label>
                    <input type="text" wire:model="password" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">
                <button type="button" wire:click="$set('showForm', false)" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-primary-900 hover:bg-gray-50">Batal</button>
                <button type="submit" class="rounded-lg bg-primary-500 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700">Simpan</button>
            </div>
        </form>
    </x-modal>

    {{-- Modal reset sandi --}}
    <x-modal active="showReset" max-width="max-w-md">
        <form wire:submit="simpanReset">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-base font-semibold text-primary-900">Reset Kata Sandi</h2>
            </div>
            <div class="px-6 py-5">
                <label class="block text-sm font-medium text-primary-900">Kata sandi baru</label>
                <input type="text" wire:model="new_password" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                @error('new_password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">
                <button type="button" wire:click="$set('showReset', false)" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-primary-900 hover:bg-gray-50">Batal</button>
                <button type="submit" class="rounded-lg bg-primary-500 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700">Simpan</button>
            </div>
        </form>
    </x-modal>

    <x-confirm active="showDelete" action="hapus" title="Hapus petugas?"
               message="Akun petugas ini akan dihapus permanen." />
</div>