<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-primary-900">Nasabah TPS</h1>
            <p class="mt-1 text-sm text-gray-500">Kelola warga yang menjadi pelanggan pengangkutan TPS Anda.</p>
        </div>
        <button wire:click="bukaTambah" class="w-full flex-none rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 sm:w-auto">+ Tambah Nasabah</button>
    </div>
    @if (session('ok'))
        <div class="rounded-xl border border-primary-100 bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('ok') }}</div>
    @endif
    @if (session('err'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ session('err') }}</div>
    @endif

    {{-- Tabel (desktop) --}}
    <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm sm:block">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-6 py-3 font-semibold">Nasabah</th>
                    <th class="px-6 py-3 font-semibold">Kode</th>
                    <th class="px-6 py-3 font-semibold">Iuran Belum Bayar</th>
                    <th class="px-6 py-3 font-semibold">Status</th>
                    <th class="px-6 py-3 text-right font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($members as $m)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-primary-900">{{ $m->user?->name ?? '—' }}</td>
                        <td class="px-6 py-3 text-gray-500">{{ $m->user?->kode_qr ?? '—' }}</td>
                        <td class="px-6 py-3">
                            @if ($m->menunggu_count > 0)
                                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">{{ $m->menunggu_count }} tagihan</span>
                            @else
                                <span class="text-xs text-gray-400">Lunas</span>
                            @endif
                        </td>
                        <td class="px-6 py-3">
                            <button wire:click="toggleStatus({{ $m->id }})"
                                    class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $m->status === 'aktif' ? 'bg-primary-50 text-primary-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ ucfirst($m->status) }}
                            </button>
                        </td>
                        <td class="px-6 py-3 text-right">
                            <button wire:click="konfirmHapus({{ $m->id }})" class="text-sm font-medium text-red-600 hover:text-red-700">Keluarkan</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">Belum ada nasabah.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Kartu (mobile) --}}
    <div class="space-y-3 sm:hidden">
        @forelse ($members as $m)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate font-medium text-primary-900">{{ $m->user?->name ?? '—' }}</p>
                        <p class="truncate text-xs text-gray-400">Kode: {{ $m->user?->kode_qr ?? '—' }}</p>
                    </div>
                    <button wire:click="toggleStatus({{ $m->id }})"
                            class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $m->status === 'aktif' ? 'bg-primary-50 text-primary-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ ucfirst($m->status) }}
                    </button>
                </div>

                <div class="mt-2">
                    @if ($m->menunggu_count > 0)
                        <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">{{ $m->menunggu_count }} tagihan belum bayar</span>
                    @else
                        <span class="text-xs text-gray-400">Iuran lunas</span>
                    @endif
                </div>

                <div class="mt-3 border-t border-gray-100 pt-3">
                    <button wire:click="konfirmHapus({{ $m->id }})" class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-100">Keluarkan</button>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-400">Belum ada nasabah.</div>
        @endforelse
    </div>

    <div>{{ $members->links() }}</div>

    {{-- Modal tambah nasabah --}}
    <x-modal active="showAdd" max-width="max-w-md">
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-base font-semibold text-primary-900">Tambah Nasabah</h2>
        </div>
        <div class="space-y-4 px-4 py-5 sm:px-6">
            <div class="flex flex-col gap-2 sm:flex-row">
                <input wire:model="cari" wire:keydown.enter="cariNasabah" placeholder="Kode QR / NIK / nama"
                       class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                <button type="button" wire:click="cariNasabah" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 sm:flex-none">Cari</button>
            </div>
            @if ($foundId)
                <div class="rounded-lg border border-gray-200 p-4">
                    <p class="text-sm font-medium text-primary-900">{{ $foundNama }}</p>
                    <p class="text-xs text-gray-500">Kode: {{ $foundKode ?: '—' }}</p>
                    @if ($sudahAnggota)
                        <p class="mt-2 text-xs text-amber-600">Sudah menjadi nasabah TPS ini.</p>
                    @else
                        <button wire:click="tambahAnggota" class="mt-3 w-full rounded-lg bg-primary-500 px-4 py-1.5 text-sm font-semibold text-white hover:bg-primary-700 sm:w-auto">Tambahkan</button>
                    @endif
                </div>
            @endif
        </div>
        <div class="flex justify-end border-t border-gray-200 px-4 py-4 sm:px-6">
            <button type="button" wire:click="$set('showAdd', false)" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-primary-900 hover:bg-gray-50">Tutup</button>
        </div>
    </x-modal>

    <x-confirm active="showDelete" action="hapus" title="Keluarkan nasabah?"
               message="Nasabah beserta riwayat tagihannya akan dihapus dari TPS ini." confirmLabel="Keluarkan" />
</div>