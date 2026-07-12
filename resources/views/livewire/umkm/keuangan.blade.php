<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-primary-900">Laporan Keuangan</h1>
        <p class="mt-1 text-sm text-gray-500">Ringkasan pemasukan & penjualan pada rentang waktu tertentu.</p>
    </div>

    <div class="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div><label class="block text-xs font-medium text-gray-500">Dari</label><input type="date" wire:model.live="dari" class="mt-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"></div>
        <div><label class="block text-xs font-medium text-gray-500">Sampai</label><input type="date" wire:model.live="sampai" class="mt-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"></div>
        <div class="ml-auto flex gap-2">
            <button wire:click="exportLabaRugi" class="rounded-lg border border-primary-500 px-4 py-2 text-sm font-semibold text-primary-600 hover:bg-primary-50">Export Laba Rugi</button>
            <button wire:click="exportPenjualan" class="rounded-lg border border-primary-500 px-4 py-2 text-sm font-semibold text-primary-600 hover:bg-primary-50">Export Penjualan</button>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:col-span-2 lg:col-span-1"><p class="text-xs text-gray-400">Pendapatan</p><p class="mt-1 text-2xl font-semibold text-primary-700">Rp {{ number_format($ringkas['pendapatan'], 0, ',', '.') }}</p></div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><p class="text-xs text-gray-400">Pesanan Terbayar</p><p class="mt-1 text-2xl font-semibold text-primary-900">{{ $ringkas['pesanan'] }}</p></div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><p class="text-xs text-gray-400">Rata-rata Pesanan</p><p class="mt-1 text-xl font-semibold text-primary-900">Rp {{ number_format($ringkas['rata'], 0, ',', '.') }}</p></div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><p class="text-xs text-gray-400">Dibatalkan</p><p class="mt-1 text-xl font-semibold text-red-600">{{ $ringkas['dibatalkan'] }}</p></div>
    </div>

    {{-- Laba rugi sederhana --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <p class="text-sm font-semibold text-primary-900">Laba Rugi Sederhana</p>
        <dl class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-gray-500">Pendapatan Penjualan</dt><dd class="font-medium text-primary-900">Rp {{ number_format($ringkas['pendapatan'], 0, ',', '.') }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Ongkos Kirim Diterima</dt><dd class="font-medium text-primary-900">Rp {{ number_format($ringkas['ongkir'], 0, ',', '.') }}</dd></div>
            <div class="flex justify-between border-t border-gray-100 pt-2"><dt class="text-gray-500">Harga Pokok Penjualan</dt><dd class="text-gray-400">belum dicatat</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Beban Operasional</dt><dd class="text-gray-400">belum dicatat</dd></div>
            <div class="flex justify-between border-t border-gray-200 pt-2 text-base"><dt class="font-semibold text-primary-900">Laba Bersih</dt><dd class="font-bold text-primary-700">Rp {{ number_format($ringkas['pendapatan'], 0, ',', '.') }}</dd></div>
        </dl>
        <p class="mt-3 text-xs text-gray-400">HPP & beban belum tercatat pada sistem, sehingga laba bersih dihitung setara laba kotor penjualan.</p>
    </div>

    {{-- Penjualan per produk --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-6 py-4"><p class="text-sm font-semibold text-primary-900">Penjualan per Produk</p></div>
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500"><tr><th class="px-6 py-3 font-semibold">Produk</th><th class="px-6 py-3 text-right font-semibold">Qty</th><th class="px-6 py-3 text-right font-semibold">Pendapatan</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($perProduk as $p)
                    <tr><td class="px-6 py-3 text-primary-900">{{ $p->nama_snapshot }}</td><td class="px-6 py-3 text-right text-gray-600">{{ $p->qty }}</td><td class="px-6 py-3 text-right font-medium text-primary-700">Rp {{ number_format($p->revenue, 0, ',', '.') }}</td></tr>
                @empty
                    <tr><td colspan="3" class="px-6 py-8 text-center text-gray-400">Belum ada penjualan pada rentang ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>