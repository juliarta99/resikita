<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-primary-900">Dashboard</h1>
        <p class="mt-1 text-sm text-gray-500">Ringkasan penjualan {{ auth()->user()?->umkm?->nama }}.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:col-span-2 lg:col-span-1"><p class="text-xs text-gray-400">Pendapatan Bulan Ini</p><p class="mt-1 text-2xl font-semibold text-primary-700">Rp {{ number_format($stat['pendapatan'], 0, ',', '.') }}</p></div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><p class="text-xs text-gray-400">Pesanan Bulan Ini</p><p class="mt-1 text-2xl font-semibold text-primary-900">{{ $stat['pesananBln'] }}</p></div>
        <a href="{{ route('umkm.pesanan') }}" class="rounded-xl border border-blue-200 bg-blue-50 p-5 hover:bg-blue-100/60"><p class="text-xs text-blue-700/80">Perlu Diproses</p><p class="mt-1 text-2xl font-semibold text-blue-700">{{ $stat['perluProses'] }}</p></a>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><p class="text-xs text-gray-400">Total Produk</p><p class="mt-1 text-xl font-semibold text-primary-900">{{ $stat['produk'] }}</p></div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><p class="text-xs text-gray-400">Produk Aktif</p><p class="mt-1 text-xl font-semibold text-primary-900">{{ $stat['produkAktif'] }}</p></div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><p class="text-xs text-gray-400">Total Pesanan</p><p class="mt-1 text-xl font-semibold text-primary-900">{{ $stat['pesanan'] }}</p></div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
            <p class="text-sm font-semibold text-primary-900">Pendapatan 6 Bulan (Rp)</p>
            <div wire:ignore class="mt-3 h-64" x-data x-init="new Chart($refs.bar,{type:'bar',data:{labels:@js($trenLabels),datasets:[{label:'Pendapatan',data:@js($trenData),backgroundColor:'#057D5D',borderRadius:6}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});"><canvas x-ref="bar"></canvas></div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-primary-900">Produk Terlaris</p>
            <div class="mt-3 space-y-3">
                @forelse ($top as $t)
                    <div class="flex items-center justify-between text-sm"><span class="truncate text-primary-900">{{ $t->nama_snapshot }}</span><span class="flex-none text-gray-500">{{ $t->qty }} terjual</span></div>
                @empty
                    <p class="text-sm text-gray-400">Belum ada penjualan.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-6 py-4"><p class="text-sm font-semibold text-primary-900">Pesanan Terbaru</p></div>
        <table class="w-full text-left text-sm"><tbody class="divide-y divide-gray-100">
            @forelse ($terbaru as $o)
                <tr><td class="px-6 py-3 text-primary-900">#{{ $o->id }}</td><td class="px-6 py-3 text-gray-600">Rp {{ number_format($o->total, 0, ',', '.') }}</td><td class="px-6 py-3 text-gray-500">{{ ucfirst(str_replace('_',' ',$o->status)) }}</td><td class="px-6 py-3 text-right text-xs text-gray-400">{{ $o->created_at->format('d M H:i') }}</td></tr>
            @empty
                <tr><td class="px-6 py-8 text-center text-gray-400">Belum ada pesanan.</td></tr>
            @endforelse
        </tbody></table>
    </div>
</div>