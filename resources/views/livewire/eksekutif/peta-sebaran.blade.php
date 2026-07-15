<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-primary-900">Peta Sebaran {{ $scopeLabel }}</h1>
        <p class="mt-1 text-sm text-gray-500">Sebaran TPS, bank sampah, UMKM, dan laporan di wilayah Anda.</p>
    </div>

    {{-- Filter --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
            <div class="grid grid-cols-2 gap-3 sm:flex sm:gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500">Rentang laporan — Dari</label>
                    <input type="date" wire:model="dari" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500">Sampai</label>
                    <input type="date" wire:model="sampai" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                </div>
            </div>
            <button wire:click="terapkan" class="rounded-lg bg-primary-500 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700 sm:ml-auto">Terapkan</button>
        </div>

        {{-- Toggle jenis marker --}}
        <div class="mt-4 grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:gap-4">
            @foreach ([
                ['tps', 'TPS', '#0ea5e9', $jumlah['tps']],
                ['bank_sampah', 'Bank Sampah', '#057D5D', $jumlah['bank_sampah']],
                ['umkm', 'UMKM', '#f59e0b', $jumlah['umkm']],
                ['laporan', 'Laporan', '#ef4444', $jumlah['laporan']],
            ] as [$key, $label, $warna, $n])
                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    <input type="checkbox" value="{{ $key }}" wire:model="jenis" class="rounded border-gray-300 text-primary-500 focus:ring-primary-500">
                    <span class="h-2.5 w-2.5 rounded-full" style="background:{{ $warna }}"></span>
                    <span class="text-primary-900">{{ $label }}</span>
                    <span class="text-xs text-gray-400">({{ $n }})</span>
                </label>
            @endforeach
        </div>
    </div>

    {{-- Peta --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
        <div wire:ignore x-data="{ map:null, layer:null }" x-init="
            const pin = (c) => L.divIcon({ className:'', iconSize:[26,36], iconAnchor:[13,36], popupAnchor:[0,-32], html:`<svg width='26' height='36' viewBox='0 0 26 36' xmlns='http://www.w3.org/2000/svg'><path d='M13 0C5.8 0 0 5.8 0 13c0 9.2 13 23 13 23s13-13.8 13-23C26 5.8 20.2 0 13 0z' fill='${c}'/><circle cx='13' cy='13' r='5' fill='white'/></svg>` });
            const colors = { tps:'#0ea5e9', bank_sampah:'#057D5D', umkm:'#f59e0b', laporan:'#ef4444' };
            const wire = $wire;
            const draw = (data) => {
                layer.clearLayers(); const pts=[];
                data.forEach(m => {
                    const mk = L.marker([m.lat,m.lng],{icon:pin(colors[m.t]||'#666')})
                        .bindPopup(m.n + '<br><span style=\'font-size:11px;color:#6b7280\'>Klik untuk detail</span>')
                        .addTo(layer);
                    mk.on('click', () => { if (m.id) wire.lihatDetail(m.t, m.id); });
                    pts.push([m.lat,m.lng]);
                });
                if (pts.length) map.fitBounds(pts,{padding:[30,30],maxZoom:14});
            };
            map = L.map($refs.map).setView([-8.6478,115.1385], 11);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap'}).addTo(map);
            layer = L.layerGroup().addTo(map);
            draw(@js($markers));
            setTimeout(()=>map.invalidateSize(),200);
            window.addEventListener('peta-updated', (e) => draw(e.detail.markers));
        "><div x-ref="map" class="h-80 w-full rounded-lg border border-gray-200 sm:h-[30rem]"></div></div>
    </div>

    {{-- Modal detail marker --}}
    <x-modal active="showDetail" max-width="max-w-2xl">
        @if ($detail)
            @php
                $meta = [
                    'tps'         => ['TPS', '#0ea5e9'],
                    'bank_sampah' => ['Bank Sampah', '#057D5D'],
                    'umkm'        => ['UMKM', '#f59e0b'],
                    'laporan'     => ['Laporan', '#ef4444'],
                ][$detail['jenis']];
            @endphp
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $meta[1] }}"></span>
                    <h2 class="text-base font-semibold text-primary-900">Detail {{ $meta[0] }}</h2>
                </div>
                <button type="button" wire:click="$set('showDetail', false)" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <div class="max-h-[70vh] overflow-y-auto px-4 py-5 sm:px-6">
                @if ($detail['jenis'] === 'laporan')
                    <p class="text-lg font-semibold text-primary-900">{{ $detail['judul'] }}</p>
                    <div class="mt-1 flex items-center gap-2 text-xs"><x-status-badge :status="$detail['status']" /><span class="text-gray-400">{{ $detail['tanggal'] }}</span></div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                        <div><span class="text-gray-400">Kategori:</span> <span class="text-primary-900">{{ $detail['kategori'] ?? '—' }}</span></div>
                        <div class="col-span-2"><span class="text-gray-400">Alamat:</span> <span class="text-primary-900">{{ $detail['alamat'] ?: '—' }}</span></div>
                    </div>
                    @if ($detail['deskripsi'])<p class="mt-3 text-sm leading-relaxed text-gray-700">{{ $detail['deskripsi'] }}</p>@endif
                    @if ($detail['foto'] || $detail['images']->isNotEmpty())
                        <div class="mt-3 grid grid-cols-3 gap-2">
                            @if ($detail['foto'])<a href="{{ asset('storage/' . $detail['foto']) }}" target="_blank"><img src="{{ asset('storage/' . $detail['foto']) }}" class="aspect-square w-full rounded-lg border border-gray-200 object-cover" alt=""></a>@endif
                            @foreach ($detail['images'] as $img)<a href="{{ asset('storage/' . $img->path) }}" target="_blank"><img src="{{ asset('storage/' . $img->path) }}" class="aspect-square w-full rounded-lg border border-gray-200 object-cover" alt=""></a>@endforeach
                        </div>
                    @endif
                @elseif ($detail['jenis'] === 'umkm')
                    <div class="flex items-start gap-4">
                        @if ($detail['foto'])<img src="{{ asset('storage/' . $detail['foto']) }}" class="h-20 w-20 flex-none rounded-lg border border-gray-200 object-cover" alt="">@endif
                        <div>
                            <p class="text-lg font-semibold text-primary-900">{{ $detail['nama'] }}</p>
                            @if ($detail['alamat'])<p class="text-xs text-gray-500">{{ $detail['alamat'] }}</p>@endif
                            @if ($detail['no_hp'])<p class="text-xs text-gray-500">{{ $detail['no_hp'] }}</p>@endif
                        </div>
                    </div>
                    @if ($detail['deskripsi'])<p class="mt-3 text-sm leading-relaxed text-gray-700">{{ $detail['deskripsi'] }}</p>@endif
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-lg border border-gray-200 p-3"><p class="text-xs text-gray-400">Total Produk</p><p class="text-lg font-semibold text-primary-900">{{ $detail['produkTotal'] }}</p></div>
                        <div class="rounded-lg border border-gray-200 p-3"><p class="text-xs text-gray-400">Produk Aktif</p><p class="text-lg font-semibold text-primary-700">{{ $detail['produkAktif'] }}</p></div>
                    </div>
                    <p class="mt-4 text-sm font-semibold text-primary-900">Daftar Produk</p>
                    <div class="mt-2 overflow-x-auto rounded-lg border border-gray-200">
                        <table class="w-full min-w-[420px] text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500"><tr><th class="px-3 py-2 font-semibold">Produk</th><th class="px-3 py-2 text-right font-semibold">Harga</th><th class="px-3 py-2 text-right font-semibold">Stok</th><th class="px-3 py-2 text-center font-semibold">Status</th></tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($detail['produk'] as $p)
                                    <tr>
                                        <td class="px-3 py-2 text-primary-900">{{ $p->nama }}</td>
                                        <td class="px-3 py-2 text-right text-gray-600">Rp {{ number_format($p->harga, 0, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-right text-gray-600">{{ $p->stok }}</td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="rounded-full px-2 py-0.5 text-xs {{ $p->is_active ? 'bg-primary-50 text-primary-700' : 'bg-gray-100 text-gray-500' }}">{{ $p->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-3 py-6 text-center text-gray-400">Belum ada produk.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @elseif ($detail['jenis'] === 'tps')
                    <p class="text-lg font-semibold text-primary-900">{{ $detail['nama'] }}</p>
                    @if ($detail['alamat'])<p class="text-xs text-gray-500">{{ $detail['alamat'] }}</p>@endif
                    @if ($detail['no_hp'])<p class="text-xs text-gray-500">{{ $detail['no_hp'] }}</p>@endif
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-lg border border-gray-200 p-3"><p class="text-xs text-gray-400">Nasabah Aktif</p><p class="text-lg font-semibold text-primary-900">{{ $detail['nasabahAktif'] }}</p><p class="text-xs text-gray-400">dari {{ $detail['nasabahTotal'] }}</p></div>
                        <div class="rounded-lg border border-gray-200 p-3"><p class="text-xs text-gray-400">Iuran Bulan Ini</p><p class="text-lg font-semibold text-primary-700">Rp {{ number_format($detail['iuranBln'], 0, ',', '.') }}</p></div>
                        <div class="rounded-lg border border-gray-200 p-3"><p class="text-xs text-gray-400">Tagihan Menunggu</p><p class="text-lg font-semibold text-amber-600">{{ $detail['tagihanMenunggu'] }}</p></div>
                        <div class="rounded-lg border border-gray-200 p-3"><p class="text-xs text-gray-400">Tarif</p><p class="text-lg font-semibold text-primary-900">{{ $detail['berbayar'] ? 'Rp ' . number_format($detail['tarif'], 0, ',', '.') : 'Gratis' }}</p></div>
                    </div>
                @elseif ($detail['jenis'] === 'bank_sampah')
                    <p class="text-lg font-semibold text-primary-900">{{ $detail['nama'] }}</p>
                    @if ($detail['alamat'])<p class="text-xs text-gray-500">{{ $detail['alamat'] }}</p>@endif
                    @if ($detail['no_hp'])<p class="text-xs text-gray-500">{{ $detail['no_hp'] }}</p>@endif
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-lg border border-gray-200 p-3"><p class="text-xs text-gray-400">Nasabah</p><p class="text-lg font-semibold text-primary-900">{{ $detail['nasabah'] }}</p></div>
                        <div class="rounded-lg border border-gray-200 p-3"><p class="text-xs text-gray-400">Transaksi Bulan Ini</p><p class="text-lg font-semibold text-primary-900">{{ $detail['transaksiBln'] }}</p></div>
                        <div class="rounded-lg border border-gray-200 p-3"><p class="text-xs text-gray-400">Berat Bulan Ini</p><p class="text-lg font-semibold text-primary-900">{{ number_format($detail['beratBln'], 1, ',', '.') }} kg</p></div>
                        <div class="rounded-lg border border-gray-200 p-3"><p class="text-xs text-gray-400">Nilai Bulan Ini</p><p class="text-lg font-semibold text-primary-700">Rp {{ number_format($detail['nilaiBln'], 0, ',', '.') }}</p></div>
                        <div class="rounded-lg border border-gray-200 p-3"><p class="text-xs text-gray-400">Total Transaksi</p><p class="text-lg font-semibold text-primary-900">{{ $detail['transaksiTotal'] }}</p></div>
                        <div class="rounded-lg border border-gray-200 p-3"><p class="text-xs text-gray-400">Total Nilai</p><p class="text-lg font-semibold text-primary-700">Rp {{ number_format($detail['nilaiTotal'], 0, ',', '.') }}</p></div>
                    </div>
                @endif
            </div>
        @endif
    </x-modal>
</div>