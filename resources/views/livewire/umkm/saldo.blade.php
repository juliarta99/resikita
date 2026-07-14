<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-primary-900">Saldo & Penarikan</h1>
        <p class="mt-1 text-sm text-gray-500">Pemasukan dari penjualan produk masuk otomatis ke saldo Anda.</p>
    </div>

    @if (session('ok'))
        <div class="rounded-xl border border-primary-100 bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('ok') }}</div>
    @endif

    {{-- Kartu saldo --}}
    <div class="flex flex-col gap-4 rounded-2xl bg-primary-600 p-6 text-white sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-primary-100">Total Saldo</p>
            <p class="mt-1 text-3xl font-bold">Rp {{ number_format($saldo, 0, ',', '.') }}</p>
        </div>
        <button wire:click="bukaTarik"
                class="rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-primary-700 hover:bg-primary-50">
            Tarik Saldo
        </button>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Riwayat transaksi --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-3">
                <h2 class="font-semibold text-primary-900">Riwayat Transaksi Saldo</h2>
            </div>
            <table class="w-full text-left text-sm">
                <tbody class="divide-y divide-gray-100">
                    @forelse ($transaksi as $t)
                        <tr>
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-800 capitalize">{{ $t->tipe }}</p>
                                <p class="text-xs text-gray-500">{{ $t->keterangan }}</p>
                                <p class="text-xs text-gray-400">{{ $t->created_at->format('d M Y H:i') }}</p>
                            </td>
                            <td class="px-5 py-3 text-right font-semibold {{ $t->jumlah < 0 ? 'text-red-600' : 'text-primary-600' }}">
                                {{ $t->jumlah < 0 ? '-' : '+' }}Rp {{ number_format(abs($t->jumlah), 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr><td class="px-5 py-6 text-center text-sm text-gray-400">Belum ada transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-5 py-3">{{ $transaksi->links() }}</div>
        </div>

        {{-- Riwayat penarikan --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-3">
                <h2 class="font-semibold text-primary-900">Riwayat Penarikan</h2>
            </div>
            <table class="w-full text-left text-sm">
                <tbody class="divide-y divide-gray-100">
                    @forelse ($penarikan as $w)
                        <tr>
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-800">Rp {{ number_format($w->jumlah, 0, ',', '.') }}</p>
                                <p class="text-xs text-gray-500">{{ $w->nama_bank }} • {{ $w->no_rekening }} • a.n {{ $w->atas_nama }}</p>
                                @if ($w->catatan)<p class="text-xs text-red-500">{{ $w->catatan }}</p>@endif
                                <p class="text-xs text-gray-400">{{ $w->created_at->format('d M Y H:i') }}</p>
                            </td>
                            <td class="px-5 py-3 text-right">
                                @php $c = ['menunggu'=>'bg-amber-100 text-amber-700','disetujui'=>'bg-primary-100 text-primary-700','ditolak'=>'bg-red-100 text-red-700'][$w->status] ?? 'bg-gray-100 text-gray-600'; @endphp
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $c }} capitalize">{{ $w->status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="px-5 py-6 text-center text-sm text-gray-400">Belum ada penarikan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal tarik --}}
    @if ($showTarik)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-primary-900">Tarik Saldo</h3>
                    <button wire:click="$set('showTarik', false)" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Jumlah (min. Rp 50.000)</label>
                        <input type="number" wire:model="jumlah" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="0">
                        @error('jumlah')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Nama Bank</label>
                        <input type="text" wire:model="nama_bank" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="mis. BCA">
                        @error('nama_bank')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Nomor Rekening</label>
                        <input type="text" wire:model="no_rekening" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        @error('no_rekening')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Atas Nama</label>
                        <input type="text" wire:model="atas_nama" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        @error('atas_nama')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button wire:click="$set('showTarik', false)" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Batal</button>
                    <button wire:click="ajukan" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">Ajukan Penarikan</button>
                </div>
            </div>
        </div>
    @endif
</div>