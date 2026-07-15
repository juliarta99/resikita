<div class="mx-auto max-w-4xl px-4 py-10">
    <a href="{{ route('publik.tps.index') }}" class="text-sm font-medium text-primary-500 hover:text-primary-700">← Semua TPS</a>

    <div class="mt-4 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="aspect-[21/9] bg-gray-100">
            @if ($tps->foto)<img src="{{ asset('storage/' . $tps->foto) }}" class="h-full w-full object-cover" alt="">@endif
        </div>
        <div class="p-6">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-primary-900">{{ $tps->nama }}</h1>
                @if ($tps->is_berbayar)<span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700">Berbayar</span>@else<span class="rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-700">Gratis</span>@endif
            </div>
            <p class="mt-1 text-sm text-gray-500">{{ $tps->banjarDinas?->nama }} · {{ $tps->alamat }}</p>
            <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                @if ($tps->is_berbayar)
                    <div class="rounded-lg border border-gray-200 p-4"><dt class="text-xs text-gray-400">Tarif Layanan</dt><dd class="mt-1 text-lg font-semibold text-primary-700">Rp {{ number_format($tps->tarif, 0, ',', '.') }}<span class="text-sm font-normal text-gray-400"> / bulan</span></dd></div>
                @endif
                @if ($tps->no_hp)
                    <div class="rounded-lg border border-gray-200 p-4"><dt class="text-xs text-gray-400">Kontak</dt><dd class="mt-1 text-lg font-semibold text-primary-900">{{ $tps->no_hp }}</dd></div>
                @endif
            </dl>
            <p class="mt-5 rounded-lg bg-primary-50 px-4 py-3 text-sm text-primary-700">Pendaftaran sebagai nasabah TPS dilakukan melalui aplikasi Niti Resik.</p>
        </div>
    </div>

    @if ($tps->lat && $tps->lng)
        <div wire:ignore class="mt-6" x-data x-init="
            const map = L.map($refs.map).setView([{{ $tps->lat }}, {{ $tps->lng }}], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap'}).addTo(map);
            L.marker([{{ $tps->lat }}, {{ $tps->lng }}]).addTo(map).bindPopup(@js($tps->nama));
            setTimeout(()=>map.invalidateSize(),200);
        "><div x-ref="map" class="h-72 w-full rounded-2xl border border-gray-200"></div></div>
    @endif
</div>