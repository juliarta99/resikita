<div class="mx-auto max-w-4xl px-4 py-10">
    <a href="{{ route('publik.bank-sampah.index') }}" class="text-sm font-medium text-primary-500 hover:text-primary-700">← Semua Bank Sampah</a>

    <div class="mt-4 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="aspect-[21/9] bg-gray-100">
            @if ($bankSampah->foto)<img src="{{ asset('storage/' . $bankSampah->foto) }}" class="h-full w-full object-cover" alt="">@endif
        </div>
        <div class="p-6">
            <h1 class="text-2xl font-bold text-primary-900">{{ $bankSampah->nama }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $bankSampah->banjarDinas?->nama }} · {{ $bankSampah->alamat }}</p>
            @if ($bankSampah->no_hp)<p class="mt-3 text-sm text-gray-500">Kontak: <span class="font-medium text-primary-900">{{ $bankSampah->no_hp }}</span></p>@endif
            <p class="mt-5 rounded-lg bg-primary-50 px-4 py-3 text-sm text-primary-700">Setor sampah & penukaran saldo dilakukan melalui aplikasi Niti Resik.</p>
        </div>
    </div>

    <h2 class="mt-10 text-lg font-semibold text-primary-900">Harga Sampah Terkini</h2>
    <div class="mt-4 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                <tr><th class="px-6 py-3 font-semibold">Jenis</th><th class="px-6 py-3 font-semibold">Satuan</th><th class="px-6 py-3 text-right font-semibold">Harga</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($harga as $h)
                    <tr><td class="px-6 py-3 text-primary-900">{{ $h->jenis_sampah }}</td><td class="px-6 py-3 text-gray-500">{{ $h->satuan }}</td><td class="px-6 py-3 text-right font-medium text-primary-700">Rp {{ number_format($h->harga_per_kg, 0, ',', '.') }}</td></tr>
                @empty
                    <tr><td colspan="3" class="px-6 py-8 text-center text-gray-400">Belum ada data harga.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($bankSampah->lat && $bankSampah->lng)
        <div wire:ignore class="mt-6" x-data x-init="
            const map = L.map($refs.map).setView([{{ $bankSampah->lat }}, {{ $bankSampah->lng }}], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap'}).addTo(map);
            L.marker([{{ $bankSampah->lat }}, {{ $bankSampah->lng }}]).addTo(map).bindPopup(@js($bankSampah->nama));
            setTimeout(()=>map.invalidateSize(),200);
        "><div x-ref="map" class="h-72 w-full rounded-2xl border border-gray-200"></div></div>
    @endif
</div>