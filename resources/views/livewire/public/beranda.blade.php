<div>
    {{-- HERO --}}
    <section class="relative overflow-hidden bg-primary-900">
        <div class="pointer-events-none absolute inset-0 opacity-30" style="background:radial-gradient(600px 300px at 80% -10%, #057D5D, transparent), radial-gradient(500px 300px at 0% 110%, #046A4F, transparent);"></div>
        <div class="relative mx-auto max-w-6xl px-4 py-20 lg:py-28">
            <div class="max-w-2xl">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-primary-100 ring-1 ring-white/15">
                    <span class="h-1.5 w-1.5 rounded-full bg-primary-100"></span> Ekonomi Sirkular · Kabupaten Badung
                </span>
                <h1 class="mt-5 text-4xl font-extrabold leading-tight text-white sm:text-5xl">
                    Kelola Sampah, <span class="text-primary-100">Hasilkan Nilai</span>
                </h1>
                <p class="mt-5 text-base leading-relaxed text-primary-100/80">
                    Satu ekosistem untuk bank sampah digital, pelaporan masalah sampah, dan marketplace produk daur ulang. Pantau fasilitas dan UMKM di sekitar Anda, atau laporkan lewat aplikasi.
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('publik.umkm.index') }}" class="rounded-lg bg-primary-500 px-6 py-3 text-center text-sm font-semibold text-white hover:bg-primary-400">Jelajahi UMKM</a>
                    <a href="#unduh" class="rounded-lg border border-white/25 px-6 py-3 text-center text-sm font-semibold text-white hover:bg-white/10">Unduh Aplikasi</a>
                </div>
            </div>
        </div>
    </section>

    {{-- STAT STRIP --}}
    <section class="border-b border-gray-100 bg-white">
        <div class="mx-auto grid max-w-6xl grid-cols-2 gap-6 px-4 py-8 md:grid-cols-4 lg:grid-cols-5">
            @foreach ([
                ['Bank Sampah', number_format($stat['bankSampah'], 0, ',', '.')],
                ['Titik TPS', number_format($stat['tps'], 0, ',', '.')],
                ['UMKM Aktif', number_format($stat['umkm'], 0, ',', '.')],
                ['Sampah Terkelola', number_format($stat['sampahKg'], 0, ',', '.') . ' kg'],
                ['Laporan Tuntas', number_format($stat['laporanTuntas'], 0, ',', '.')],
            ] as [$label, $val])
                <div>
                    <p class="text-2xl font-bold text-primary-700">{{ $val }}</p>
                    <p class="text-xs text-gray-500">{{ $label }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- FITUR PUBLIK --}}
    <section class="mx-auto max-w-6xl px-4 py-16">
        <h2 class="text-2xl font-bold text-primary-900">Yang bisa Anda lihat di sini</h2>
        <p class="mt-1 text-sm text-gray-500">Informasi terbuka untuk publik — untuk transaksi & pelaporan gunakan aplikasi.</p>
        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['UMKM Daur Ulang', 'Produk kreatif dari bahan daur ulang warga Badung.', route('publik.umkm.index'), '#f59e0b'],
                ['Lokasi TPS', 'Tempat pengelolaan sampah terdekat & tarif layanannya.', route('publik.tps.index'), '#0ea5e9'],
                ['Bank Sampah', 'Titik setor sampah untuk ditukar menjadi saldo.', route('publik.bank-sampah.index'), '#057D5D'],
                ['Laporan Publik', 'Transparansi penanganan laporan masalah sampah.', route('publik.laporan.index'), '#ef4444'],
            ] as [$judul, $desc, $url, $warna])
                <a href="{{ $url }}" class="group rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl" style="background:{{ $warna }}1a;color:{{ $warna }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10"/></svg>
                    </span>
                    <h3 class="mt-4 font-semibold text-primary-900 group-hover:text-primary-700">{{ $judul }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ $desc }}</p>
                </a>
            @endforeach
        </div>
    </section>

    {{-- PETA FASILITAS --}}
    <section class="bg-gray-50 py-16">
        <div class="mx-auto max-w-6xl px-4">
            <h2 class="text-2xl font-bold text-primary-900">Peta Fasilitas</h2>
            <p class="mt-1 text-sm text-gray-500">Sebaran TPS, bank sampah, dan UMKM di Kabupaten Badung.</p>
            <div class="mt-6 flex flex-wrap gap-4 text-xs text-gray-500">
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background:#0ea5e9"></span> TPS</span>
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background:#057D5D"></span> Bank Sampah</span>
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background:#f59e0b"></span> UMKM</span>
            </div>
            <div wire:ignore class="mt-4" x-data x-init="
                const map = L.map($refs.map).setView([-8.6478, 115.1385], 11);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
                const colors = { tps:'#0ea5e9', bank_sampah:'#057D5D', umkm:'#f59e0b' };
                const data = @js($markers); const pts = [];
                data.forEach(m => { const c = colors[m.t]||'#666'; L.circleMarker([m.lat,m.lng],{radius:7,color:c,fillColor:c,fillOpacity:.85,weight:1}).bindPopup(m.n).addTo(map); pts.push([m.lat,m.lng]); });
                if (pts.length) map.fitBounds(pts,{padding:[30,30],maxZoom:13});
                setTimeout(()=>map.invalidateSize(),200);
            ">
                <div x-ref="map" class="h-96 w-full rounded-2xl border border-gray-200"></div>
            </div>
        </div>
    </section>

    {{-- UMKM UNGGULAN --}}
    <section class="mx-auto max-w-6xl px-4 py-16">
        <div class="flex items-end justify-between">
            <div>
                <h2 class="text-2xl font-bold text-primary-900">UMKM Unggulan</h2>
                <p class="mt-1 text-sm text-gray-500">Dukung produk daur ulang warga lokal.</p>
            </div>
            <a href="{{ route('publik.umkm.index') }}" class="text-sm font-semibold text-primary-600 hover:text-primary-700">Lihat semua →</a>
        </div>
        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($umkms as $u)
                <a href="{{ route('publik.umkm.show', $u) }}" class="group overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
                    <div class="aspect-video bg-gray-100">
                        @if ($u->foto)<img src="{{ asset('storage/' . $u->foto) }}" class="h-full w-full object-cover" alt="">@else<div class="flex h-full items-center justify-center text-primary-200"><svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" d="M3 9h18M3 9l2-4h14l2 4M5 9v10h14V9"/></svg></div>@endif
                    </div>
                    <div class="p-5">
                        <h3 class="font-semibold text-primary-900 group-hover:text-primary-700">{{ $u->nama }}</h3>
                        <p class="mt-1 line-clamp-2 text-sm text-gray-500">{{ $u->deskripsi }}</p>
                        <p class="mt-3 text-xs text-gray-400">{{ $u->products_count }} produk</p>
                    </div>
                </a>
            @empty
                <p class="text-gray-400">Belum ada UMKM.</p>
            @endforelse
        </div>
    </section>

    {{-- LAPORAN TERKINI --}}
    @if ($laporans->isNotEmpty())
    <section class="bg-gray-50 py-16">
        <div class="mx-auto max-w-6xl px-4">
            <div class="flex items-end justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-primary-900">Penanganan Laporan Terkini</h2>
                    <p class="mt-1 text-sm text-gray-500">Transparansi tindak lanjut laporan warga.</p>
                </div>
                <a href="{{ route('publik.laporan.index') }}" class="text-sm font-semibold text-primary-600 hover:text-primary-700">Lihat semua →</a>
            </div>
            <div class="mt-8 grid gap-4 md:grid-cols-2">
                @foreach ($laporans as $l)
                    <a href="{{ route('publik.laporan.show', $l) }}" class="flex gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md">
                        <div class="h-16 w-16 flex-none overflow-hidden rounded-lg bg-gray-100">
                            @if ($l->foto)<img src="{{ asset('storage/' . $l->foto) }}" class="h-full w-full object-cover" alt="">@endif
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-primary-500">{{ $l->kategori?->nama }}</span>
                                <x-status-badge :status="$l->status" />
                            </div>
                            <h3 class="mt-0.5 truncate font-semibold text-primary-900">{{ $l->judul }}</h3>
                            <p class="mt-0.5 truncate text-xs text-gray-400">{{ $l->alamat }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- CTA DOWNLOAD --}}
    <section id="unduh" class="mx-auto max-w-6xl px-4 py-20">
        <div class="overflow-hidden rounded-3xl bg-primary-900 px-8 py-14 text-center">
            <h2 class="mx-auto max-w-xl text-3xl font-extrabold text-white">Mulai kelola sampahmu dari genggaman</h2>
            <p class="mx-auto mt-3 max-w-lg text-primary-100/80">Setor sampah, tukar saldo, belanja produk daur ulang, dan laporkan masalah sampah — semua lewat aplikasi Niti Resik.</p>
            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="#" class="inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3 text-sm font-bold text-primary-900 hover:bg-primary-50">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M3 20.5V3.5c0-.6.3-1 .8-1.3L13 12 3.8 21.8c-.5-.3-.8-.7-.8-1.3Zm12.5-7L6 3.9l11.6 6.6-2.1 3Zm3.7 2.1-2.6-1.5-2.3 2.3 2.3 2.3 2.6-1.5c.7-.4.7-1.5 0-1.9ZM6 20.1l9.5-9.5 2.1 3L6 20.1Z"/></svg>
                    Google Play
                </a>
                <a href="#" class="inline-flex items-center gap-2 rounded-xl border border-white/25 px-6 py-3 text-sm font-bold text-white hover:bg-white/10">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M16.4 12.9c0-2 1.6-2.9 1.7-3-1-1.4-2.4-1.6-3-1.6-1.3-.1-2.5.7-3.1.7-.6 0-1.6-.7-2.7-.7-1.4 0-2.7.8-3.4 2-1.4 2.5-.4 6.2 1 8.3.7 1 1.5 2.1 2.5 2 1-.1 1.4-.6 2.6-.6s1.5.6 2.6.6 1.7-1 2.4-2c.7-1.1 1-2.2 1-2.3-.1 0-1.9-.7-2-2.9ZM14.5 6c.5-.7.9-1.6.8-2.5-.8 0-1.8.5-2.3 1.2-.5.6-1 1.5-.8 2.4.9 0 1.8-.4 2.3-1.1Z"/></svg>
                    App Store
                </a>
            </div>
            <p class="mt-4 text-xs text-primary-100/50">Tautan unduh akan aktif saat aplikasi resmi dirilis.</p>
        </div>
    </section>
</div>