<div class="mx-auto max-w-5xl px-4 py-10">
    <a href="{{ route('publik.umkm.index') }}" class="text-sm font-medium text-primary-500 hover:text-primary-700">← Semua UMKM</a>

    <div class="mt-4 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="aspect-[21/9] bg-gray-100">
            @if ($umkm->foto)<img src="{{ asset('storage/' . $umkm->foto) }}" class="h-full w-full object-cover" alt="">@endif
        </div>
        <div class="p-6">
            <h1 class="text-2xl font-bold text-primary-900">{{ $umkm->nama }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $umkm->banjarDinas?->nama }} · {{ $umkm->alamat }}</p>
            @if ($umkm->deskripsi)<p class="mt-4 text-sm leading-relaxed text-gray-700">{{ $umkm->deskripsi }}</p>@endif
            @if ($umkm->no_hp)<p class="mt-3 text-sm text-gray-500">Kontak: <span class="font-medium text-primary-900">{{ $umkm->no_hp }}</span></p>@endif
        </div>
    </div>

    <h2 class="mt-10 text-lg font-semibold text-primary-900">Produk</h2>
    @if ($produk->isEmpty())
        <p class="mt-3 text-sm text-gray-400">Belum ada produk.</p>
    @else
        <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($produk as $p)
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="aspect-square bg-gray-100">
                        @if ($p->images->first())<img src="{{ asset('storage/' . $p->images->first()->path) }}" class="h-full w-full object-cover" alt="">@endif
                    </div>
                    <div class="p-4">
                        <h3 class="text-sm font-semibold text-primary-900">{{ $p->nama }}</h3>
                        <p class="mt-1 font-bold text-primary-700">Rp {{ number_format($p->harga, 0, ',', '.') }}</p>
                        <p class="mt-1 text-xs text-gray-400">Stok: {{ $p->stok }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="mt-4 rounded-lg bg-primary-50 px-4 py-3 text-sm text-primary-700">Pembelian produk dilakukan melalui aplikasi Niti Resik.</p>
    @endif

    @if ($umkm->lat && $umkm->lng)
        <h2 class="mt-10 text-lg font-semibold text-primary-900">Lokasi</h2>
        <div wire:ignore class="mt-4" x-data x-init="
            const map = L.map($refs.map).setView([{{ $umkm->lat }}, {{ $umkm->lng }}], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap'}).addTo(map);
            L.marker([{{ $umkm->lat }}, {{ $umkm->lng }}]).addTo(map).bindPopup(@js($umkm->nama));
            setTimeout(()=>map.invalidateSize(),200);
        "><div x-ref="map" class="h-72 w-full rounded-2xl border border-gray-200"></div></div>
    @endif
</div>