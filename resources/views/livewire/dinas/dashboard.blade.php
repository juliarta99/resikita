<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-primary-900">Dashboard Dinas</h1>
        <p class="mt-1 text-sm text-gray-500">Ringkasan penanganan laporan sampah Kabupaten Badung.</p>
    </div>

    {{-- Statistik utama: 2 kolom di HP, 4 di desktop --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        @foreach ([
            ['Total Laporan', $stat['total'], 'text-primary-900'],
            ['Menunggu', $stat['menunggu'], 'text-amber-600'],
            ['Diproses', $stat['proses'], 'text-blue-600'],
            ['Selesai', $stat['selesai'], 'text-primary-700'],
        ] as [$label, $val, $cls])
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
                <p class="text-xs text-gray-400">{{ $label }}</p>
                <p class="mt-1 text-xl font-semibold {{ $cls }} sm:text-2xl">{{ $val }}</p>
            </div>
        @endforeach
    </div>

    {{-- Statistik sekunder: 2 kolom di HP, 3 di desktop --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5"><p class="text-xs text-gray-400">Laporan Bulan Ini</p><p class="mt-1 text-lg font-semibold text-primary-900 sm:text-xl">{{ $stat['bulanIni'] }}</p></div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5"><p class="text-xs text-gray-400">Ditolak</p><p class="mt-1 text-lg font-semibold text-red-600 sm:text-xl">{{ $stat['ditolak'] }}</p></div>
        <div class="col-span-2 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:col-span-1 sm:p-5"><p class="text-xs text-gray-400">Petugas Lapangan</p><p class="mt-1 text-lg font-semibold text-primary-900 sm:text-xl">{{ $stat['petugas'] }}</p></div>
    </div>

    {{-- Grafik --}}
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5 lg:col-span-2">
            <p class="text-sm font-semibold text-primary-900">Laporan Masuk 6 Bulan</p>
            <div wire:ignore class="mt-3 h-56 sm:h-64" x-data x-init="new Chart($refs.bar,{type:'bar',data:{labels:@js($trenLabels),datasets:[{label:'Laporan',data:@js($trenData),backgroundColor:'#057D5D',borderRadius:6}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});"><canvas x-ref="bar"></canvas></div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
            <p class="text-sm font-semibold text-primary-900">Status Laporan</p>
            <div wire:ignore class="mt-3 h-56 sm:h-64" x-data x-init="new Chart($refs.d,{type:'doughnut',data:{labels:['Menunggu','Proses','Selesai','Ditolak'],datasets:[{data:@js($lapData),backgroundColor:['#f59e0b','#3b82f6','#057D5D','#ef4444']}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}}});"><canvas x-ref="d"></canvas></div>
        </div>
    </div>

    {{-- Laporan terbaru --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-4 py-4 sm:px-6"><p class="text-sm font-semibold text-primary-900">Laporan Terbaru</p></div>

        {{-- Tabel (desktop) --}}
        <table class="hidden w-full text-left text-sm sm:table">
            <tbody class="divide-y divide-gray-100">
                @forelse ($terbaru as $r)
                    <tr>
                        <td class="px-6 py-3 text-primary-900">{{ $r->judul }}</td>
                        <td class="px-6 py-3 text-gray-500">{{ $r->kategori?->nama }}</td>
                        <td class="px-6 py-3"><x-status-badge :status="$r->status" /></td>
                        <td class="px-6 py-3 text-right text-xs text-gray-400">{{ $r->created_at->format('d M H:i') }}</td>
                    </tr>
                @empty
                    <tr><td class="px-6 py-8 text-center text-gray-400">Belum ada laporan.</td></tr>
                @endforelse
            </tbody>
        </table>

        {{-- Kartu (mobile) --}}
        <div class="divide-y divide-gray-100 sm:hidden">
            @forelse ($terbaru as $r)
                <div class="px-4 py-3">
                    <div class="flex items-start justify-between gap-2">
                        <p class="min-w-0 flex-1 truncate font-medium text-primary-900">{{ $r->judul }}</p>
                        <x-status-badge :status="$r->status" />
                    </div>
                    <div class="mt-1 flex items-center justify-between text-xs text-gray-400">
                        <span class="truncate">{{ $r->kategori?->nama }}</span>
                        <span class="shrink-0">{{ $r->created_at->format('d M H:i') }}</span>
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-gray-400">Belum ada laporan.</div>
            @endforelse
        </div>
    </div>
</div>