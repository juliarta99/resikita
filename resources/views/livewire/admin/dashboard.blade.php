<div class="mx-auto max-w-6xl space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-primary-900">Dashboard</h1>
        <p class="mt-1 text-sm text-gray-500">Ringkasan menyeluruh ekosistem Niti Resik Kabupaten Badung.</p>
    </div>

    {{-- Perlu tindakan --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <a href="{{ route('admin.umkm') }}" class="flex items-center justify-between rounded-xl border border-amber-200 bg-amber-50 p-5 hover:bg-amber-100/60">
            <div><p class="text-xs text-amber-700/80">UMKM Menunggu Persetujuan</p><p class="mt-1 text-2xl font-semibold text-amber-700">{{ $perluTindakan['umkmMenunggu'] }}</p></div>
            <span class="text-amber-600">→</span>
        </a>
        <a href="{{ route('admin.penarikan') }}" class="flex items-center justify-between rounded-xl border border-blue-200 bg-blue-50 p-5 hover:bg-blue-100/60">
            <div><p class="text-xs text-blue-700/80">Penarikan Menunggu</p><p class="mt-1 text-2xl font-semibold text-blue-700">{{ $perluTindakan['penarikanMenunggu'] }}</p></div>
            <span class="text-blue-600">→</span>
        </a>
        <a href="{{ route('admin.laporan') }}" class="flex items-center justify-between rounded-xl border border-red-200 bg-red-50 p-5 hover:bg-red-100/60">
            <div><p class="text-xs text-red-700/80">Laporan Menunggu</p><p class="mt-1 text-2xl font-semibold text-red-600">{{ $perluTindakan['laporanMenunggu'] }}</p></div>
            <span class="text-red-600">→</span>
        </a>
    </div>

    {{-- Statistik --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['Total Pengguna', number_format($stat['pengguna'], 0, ',', '.')],
            ['Masyarakat', number_format($stat['masyarakat'], 0, ',', '.')],
            ['Kecamatan', $stat['kecamatan']],
            ['Banjar Dinas', $stat['banjar']],
            ['TPS', $stat['tps']],
            ['Bank Sampah', $stat['bankSampah']],
            ['UMKM Aktif', $stat['umkm']],
            ['Setoran Bulan Ini', 'Rp ' . number_format($stat['setoranBln'], 0, ',', '.')],
        ] as [$label, $val])
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs text-gray-400">{{ $label }}</p>
                <p class="mt-1 text-xl font-semibold text-primary-900">{{ $val }}</p>
            </div>
        @endforeach
    </div>

    {{-- Chart --}}
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
            <p class="text-sm font-semibold text-primary-900">Tren Setoran 6 Bulan (Rp)</p>
            <div wire:ignore class="mt-3 h-64" x-data x-init="
                new Chart($refs.bar, { type:'bar', data:{ labels:@js($trenLabels), datasets:[{ label:'Setoran', data:@js($trenData), backgroundColor:'#057D5D', borderRadius:6 }] }, options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true } } } });
            "><canvas x-ref="bar"></canvas></div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-primary-900">Status Laporan</p>
            <div wire:ignore class="mt-3 h-64" x-data x-init="
                new Chart($refs.doughnut, { type:'doughnut', data:{ labels:['Menunggu','Proses','Selesai','Ditolak'], datasets:[{ data:@js($lapData), backgroundColor:['#f59e0b','#3b82f6','#057D5D','#ef4444'] }] }, options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' } } } });
            "><canvas x-ref="doughnut"></canvas></div>
        </div>
    </div>

    {{-- Peta --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm font-semibold text-primary-900">Peta Sebaran</p>
            <div class="flex flex-wrap gap-4 text-xs text-gray-500">
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background:#0ea5e9"></span> TPS</span>
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background:#057D5D"></span> Bank Sampah</span>
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background:#f59e0b"></span> UMKM</span>
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background:#ef4444"></span> Laporan</span>
            </div>
        </div>
        <div wire:ignore class="mt-3" x-data x-init="
            const pin = (c) => L.divIcon({ className:'', iconSize:[26,36], iconAnchor:[13,36], popupAnchor:[0,-32], html:`<svg width='26' height='36' viewBox='0 0 26 36' xmlns='http://www.w3.org/2000/svg'><path d='M13 0C5.8 0 0 5.8 0 13c0 9.2 13 23 13 23s13-13.8 13-23C26 5.8 20.2 0 13 0z' fill='${c}'/><circle cx='13' cy='13' r='5' fill='white'/></svg>` });
            const map = L.map($refs.map).setView([-8.6478,115.1385], 11);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap'}).addTo(map);
            const colors = { tps:'#0ea5e9', bank_sampah:'#057D5D', umkm:'#f59e0b', laporan:'#ef4444' };
            const data = @js($markers); const pts=[];
            data.forEach(m => { L.marker([m.lat,m.lng],{icon:pin(colors[m.t]||'#666')}).bindPopup(m.n).addTo(map); pts.push([m.lat,m.lng]); });
            if (pts.length) map.fitBounds(pts,{padding:[30,30],maxZoom:13});
            setTimeout(()=>map.invalidateSize(),200);
        "><div x-ref="map" class="h-96 w-full rounded-lg border border-gray-200"></div></div>
    </div>
</div>