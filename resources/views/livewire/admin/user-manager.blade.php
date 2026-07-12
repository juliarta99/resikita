<div class="mx-auto max-w-6xl space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-primary-900">Manajemen Pengguna</h1>
        <p class="mt-1 text-sm text-gray-500">Kelola status akun dan reset kata sandi seluruh pengguna.</p>
    </div>

    @if (session('ok'))
        <div class="rounded-xl border border-gray-200 bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('ok') }}</div>
    @endif
    @if (session('err'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ session('err') }}</div>
    @endif

    <div class="flex flex-wrap gap-3">
        <input wire:model.live.debounce.300ms="search" placeholder="Cari nama, email, atau NIK…"
               class="w-full max-w-xs rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
        <select wire:model.live="roleFilter"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
            <option value="">Semua peran</option>
            @foreach ($roleLabels as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-6 py-3 font-semibold">Nama</th>
                    <th class="px-6 py-3 font-semibold">Kontak</th>
                    <th class="px-6 py-3 font-semibold">Peran</th>
                    <th class="px-6 py-3 font-semibold">Unit</th>
                    <th class="px-6 py-3 font-semibold">Status</th>
                    <th class="px-6 py-3 text-right font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($users as $u)
                    @php
                        $unit = $u->umkm?->nama ?? $u->tps?->nama ?? $u->bankSampah?->nama
                            ?? $u->banjarDinas?->nama ?? $u->kelurahan?->nama ?? $u->kecamatan?->nama ?? '—';
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3">
                            <p class="text-primary-900">{{ $u->name }}</p>
                            @if ($u->nip) <p class="text-xs text-gray-400">NIP {{ $u->nip }}</p> @endif
                        </td>
                        <td class="px-6 py-3 text-gray-600">
                            <p>{{ $u->email ?? '—' }}</p>
                            @if ($u->phone) <p class="text-xs text-gray-400">{{ $u->phone }}</p> @endif
                        </td>
                        <td class="px-6 py-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach ($u->roles as $r)
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ $roleLabels[$r->name] ?? $r->name }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-3 text-gray-600">{{ $unit }}</td>
                        <td class="px-6 py-3">
                            @if ($u->is_active)
                                <span class="rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-700">Aktif</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right whitespace-nowrap">
                            @if ($u->is_active)
                                <button wire:click="konfirmNonaktif({{ $u->id }})" class="text-sm font-medium text-amber-600 hover:text-amber-700">Nonaktifkan</button>
                            @else
                                <button wire:click="aktifkan({{ $u->id }})" class="text-sm font-medium text-primary-500 hover:text-primary-700">Aktifkan</button>
                            @endif
                            <button wire:click="bukaReset({{ $u->id }})" class="ml-3 text-sm font-medium text-gray-500 hover:text-gray-700">Reset Sandi</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">Tidak ada pengguna yang cocok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $users->links() }}</div>

    {{-- Modal reset kata sandi --}}
    <x-modal active="showReset" max-width="max-w-md">
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-base font-semibold text-primary-900">Reset Kata Sandi</h2>
        </div>
        <form wire:submit="simpanReset">
            <div class="px-6 py-5">
                <label class="block text-sm font-medium text-primary-900">Kata sandi baru</label>
                <input type="text" wire:model="new_password"
                       class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                @error('new_password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-gray-400">Minimal 8 karakter. Sampaikan kata sandi ini ke pengguna terkait.</p>
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">
                <button type="button" wire:click="$set('showReset', false)" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-primary-900 hover:bg-gray-50">Batal</button>
                <button type="submit" class="rounded-lg bg-primary-500 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700">Simpan</button>
            </div>
        </form>
    </x-modal>

    <x-confirm active="showToggle" action="nonaktifkan" title="Nonaktifkan akun?"
               message="Pengguna tidak akan bisa masuk sampai diaktifkan kembali." confirmLabel="Nonaktifkan" />
</div>