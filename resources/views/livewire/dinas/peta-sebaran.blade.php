<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-primary-900">Peta Sebaran Laporan</h1>
        <p class="mt-1 text-sm text-gray-500">Lokasi laporan pada rentang waktu & status tertentu.</p>
    </div>

    <div class="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div>
            <label class="block text-xs font-medium text-gray-500">Dari</label>
            <input type="date" wire:model="dari" class="mt-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500">Sampai</label>
            <input type="date" wire:model="sampai" class="mt-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500">Status</label>
            <select wire:model="statusFilter" class="mt-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                <option value="semua">Semua</option>
                <option value="menunggu">Menunggu</option>
                <option value="proses">Diproses</option>
                <option value="selesai">Selesai</option>
                <option value="ditolak">Ditolak</option>
            </select>
        </div>
        <button wire:click="terapkan" class="rounded-lg bg-primary-500 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700">Terapkan</button>
    </div>

    <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ([
            ['Total', $counts['total'], 'text-primary-900'],
            ['Menunggu', $counts['menunggu'], 'text-amber-600'],
            ['Diproses', $counts['proses'], 'text-blue-600'],
            ['Selesai', $counts['selesai'], 'text-primary-700'],
            ['Ditolak', $counts['ditolak'], 'text-red-600'],
        ] as [$label, $val, $cls])
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"><p class="text-xs text-gray-400">{{ $label }}</p><p class="mt-1 text-xl font-semibold {{ $cls }}">{{ $val }}</p></div>
        @endforeach
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap gap-4 text-xs text-gray-500">
            <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background:#f59e0b"></span> Menunggu</span>
            <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background:#3b82f6"></span> Diproses</span>
            <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background:#057D5D"></span> Selesai</span>
            <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background:#ef4444"></span> Ditolak</span>
        </div>
        <div wire:ignore class="mt-3" x-data="{ map:null, layer:null }" x-init="
            const pin = (c) => L.divIcon({ className:'', iconSize:[26,36], iconAnchor:[13,36], popupAnchor:[0,-32], html:`<svg width='26' height='36' viewBox='0 0 26 36' xmlns='http://www.w3.org/2000/svg'><path d='M13 0C5.8 0 0 5.8 0 13c0 9.2 13 23 13 23s13-13.8 13-23C26 5.8 20.2 0 13 0z' fill='${c}'/><circle cx='13' cy='13' r='5' fill='white'/></svg>` });
            const colorFor = (s) => s==='selesai' ? '#057D5D' : (s==='ditolak' ? '#ef4444' : (s==='menunggu' ? '#f59e0b' : '#3b82f6'));
            const draw = (data) => {
                layer.clearLayers(); const pts=[];
                data.forEach(m => { L.marker([m.lat,m.lng],{icon:pin(colorFor(m.s))}).bindPopup(m.n).addTo(layer); pts.push([m.lat,m.lng]); });
                if (pts.length) map.fitBounds(pts,{padding:[30,30],maxZoom:14});
            };
            map = L.map($refs.map).setView([-8.6478,115.1385], 11);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap'}).addTo(map);
            layer = L.layerGroup().addTo(map);
            draw(@js($markers));
            setTimeout(()=>map.invalidateSize(),200);
            window.addEventListener('peta-updated', (e) => draw(e.detail.markers));
        "><div x-ref="map" class="h-[28rem] w-full rounded-lg border border-gray-200"></div></div>
    </div>
</div>