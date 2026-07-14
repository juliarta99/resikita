<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-primary-900">Dashboard</h1>
        <p class="mt-1 text-sm text-gray-500">Ringkasan penjualan {{ auth()->user()?->umkm?->nama }}.</p>
    </div>

    {{-- Statistik utama --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
        <div class="col-span-2 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5 lg:col-span-1"><p class="text-xs text-gray-400">Pendapatan Bulan Ini</p><p class="mt-1 text-xl font-semibold text-primary-700 sm:text-2xl">Rp {{ number_format($stat['pendapatan'], 0, ',', '.') }}</p></div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5"><p class="text-xs text-gray-400">Pesanan Bulan Ini</p><p class="mt-1 text-xl font-semibold text-primary-900 sm:text-2xl">{{ $stat['pesananBln'] }}</p></div>
        <a href="{{ route('umkm.pesanan') }}" class="rounded-xl border border-blue-200 bg-blue-50 p-4 hover:bg-blue-100/60 sm:p-5"><p class="text-xs text-blue-700/80">Perlu Diproses</p><p class="mt-1 text-xl font-semibold text-blue-700 sm:text-2xl">{{ $stat['perluProses'] }}</p></a>
    </div>

    {{-- Statistik sekunder --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5"><p class="text-xs text-gray-400">Total Produk</p><p class="mt-1 text-lg font-semibold text-primary-900 sm:text-xl">{{ $stat['produk'] }}</p></div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5"><p class="text-xs text-gray-400">Produk Aktif</p><p class="mt-1 text-lg font-semibold text-primary-900 sm:text-xl">{{ $stat['produkAktif'] }}</p></div>
        <div class="col-span-2 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:col-span-1 sm:p-5"><p class="text-xs text-gray-400">Total Pesanan</p><p class="mt-1 text-lg font-semibold text-primary-900 sm:text-xl">{{ $stat['pesanan'] }}</p></div>
    </div>

    {{-- Chart + terlaris --}}
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5 lg:col-span-2">
            <p class="text-sm font-semibold text-primary-900">Pendapatan 6 Bulan (Rp)</p>
            <div wire:ignore class="mt-3 h-56 sm:h-64" x-data x-init="new Chart($refs.bar,{type:'bar',data:{labels:@js($trenLabels),datasets:[{label:'Pendapatan',data:@js($trenData),backgroundColor:'#057D5D',borderRadius:6}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});"><canvas x-ref="bar"></canvas></div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
            <p class="text-sm font-semibold text-primary-900">Produk Terlaris</p>
            <div class="mt-3 space-y-3">
                @forelse ($top as $t)
                    <div class="flex items-center justify-between gap-2 text-sm"><span class="truncate text-primary-900">{{ $t->nama_snapshot }}</span><span class="flex-none text-gray-500">{{ $t->qty }} terjual</span></div>
                @empty
                    <p class="text-sm text-gray-400">Belum ada penjualan.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Pesanan terbaru --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-4 py-4 sm:px-6"><p class="text-sm font-semibold text-primary-900">Pesanan Terbaru</p></div>

        {{-- Tabel (desktop) --}}
        <table class="hidden w-full text-left text-sm sm:table">
            <tbody class="divide-y divide-gray-100">
                @forelse ($terbaru as $o)
                    <tr><td class="px-6 py-3 text-primary-900">#{{ $o->id }}</td><td class="px-6 py-3 text-gray-600">Rp {{ number_format($o->total, 0, ',', '.') }}</td><td class="px-6 py-3 text-gray-500">{{ ucfirst(str_replace('_',' ',$o->status)) }}</td><td class="px-6 py-3 text-right text-xs text-gray-400">{{ $o->created_at->format('d M H:i') }}</td></tr>
                @empty
                    <tr><td class="px-6 py-8 text-center text-gray-400">Belum ada pesanan.</td></tr>
                @endforelse
            </tbody>
        </table>

        {{-- Kartu (mobile) --}}
        <div class="divide-y divide-gray-100 sm:hidden">
            @forelse ($terbaru as $o)
                <div class="px-4 py-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-medium text-primary-900">#{{ $o->id }}</p>
                            <p class="truncate text-xs text-gray-500">{{ ucfirst(str_replace('_',' ',$o->status)) }}</p>
                        </div>
                        <p class="shrink-0 font-medium text-primary-700">Rp {{ number_format($o->total, 0, ',', '.') }}</p>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">{{ $o->created_at->format('d M H:i') }}</p>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-gray-400">Belum ada pesanan.</div>
            @endforelse
        </div>
    </div>
</div>