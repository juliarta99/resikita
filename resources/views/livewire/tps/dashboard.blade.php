<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-primary-900">Dashboard</h1>
        <p class="mt-1 text-sm text-gray-500">Ringkasan {{ $tps?->nama }}.</p>
    </div>

    {{-- Statistik: 2 kolom HP, 4 desktop --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5"><p class="text-xs text-gray-400">Nasabah Aktif</p><p class="mt-1 text-xl font-semibold text-primary-900 sm:text-2xl">{{ $stat['nasabahAktif'] }}</p><p class="text-xs text-gray-400">dari {{ $stat['nasabahTotal'] }} total</p></div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5"><p class="text-xs text-gray-400">Iuran Bulan Ini</p><p class="mt-1 text-lg font-semibold text-primary-700 sm:text-2xl">Rp {{ number_format($stat['lunasBln'], 0, ',', '.') }}</p><p class="text-xs text-gray-400">{{ $stat['lunasBlnCount'] }} lunas</p></div>
        <a href="{{ route('tps.iuran') }}" class="rounded-xl border border-amber-200 bg-amber-50 p-4 hover:bg-amber-100/60 sm:p-5"><p class="text-xs text-amber-700/80">Tagihan Menunggu</p><p class="mt-1 text-xl font-semibold text-amber-700 sm:text-2xl">{{ $stat['menungguBln'] }}</p></a>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5"><p class="text-xs text-gray-400">Tarif</p><p class="mt-1 text-lg font-semibold text-primary-900 sm:text-xl">{{ $tps?->is_berbayar ? 'Rp ' . number_format($tps->tarif, 0, ',', '.') : 'Gratis' }}</p></div>
    </div>

    {{-- Tren --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
        <p class="text-sm font-semibold text-primary-900">Iuran Terkumpul 6 Bulan (Rp)</p>
        <div wire:ignore class="mt-3 h-56 sm:h-64" x-data x-init="new Chart($refs.bar,{type:'bar',data:{labels:@js($trenLabels),datasets:[{label:'Iuran',data:@js($trenData),backgroundColor:'#057D5D',borderRadius:6}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});"><canvas x-ref="bar"></canvas></div>
    </div>
</div>