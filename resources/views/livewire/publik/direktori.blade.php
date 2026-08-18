@php use App\Support\Rupiah; @endphp

<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <header class="max-w-2xl">
        <h1 class="text-3xl font-bold tracking-tight text-primary-900">Peta &amp; direktori</h1>
        <p class="mt-3 leading-relaxed text-gray-600">
            Bank sampah, TPS, dan UMKM daur ulang yang sudah terdaftar. Titik laporan warga
            sengaja tidak ditampilkan di peta terbuka.
        </p>
    </header>

    {{-- Peta --}}
    @if ($titik !== [])
        <div class="mt-8 overflow-hidden rounded-2xl border border-gray-200">
            <div wire:ignore
                 x-data="petaFasilitas(@js($titik))"
                 x-init="gambar()"
                 class="h-80 w-full sm:h-96"
                 role="application"
                 aria-label="Peta fasilitas pengelolaan sampah">
                <div x-ref="peta" class="h-full w-full"></div>
            </div>

            <p class="border-t border-gray-100 bg-gray-50 px-4 py-2.5 text-xs text-gray-500">
                {{ count($titik) }} titik fasilitas. Daftar lengkapnya ada di bawah peta.
            </p>
        </div>
    @endif

    {{-- Penyaring --}}
    <div class="mt-8 flex flex-wrap items-end justify-between gap-4">
        <div class="inline-flex rounded-xl border border-gray-300 bg-white p-1" role="group"
             aria-label="Jenis fasilitas">
            @foreach (['bank_sampah' => 'Bank sampah', 'tps' => 'TPS', 'umkm' => 'UMKM'] as $nilai => $teks)
                <button type="button" wire:click="$set('jenis', '{{ $nilai }}')"
                        @if ($jenis === $nilai) aria-current="true" @endif
                        class="rounded-lg px-3.5 py-1.5 text-sm font-medium transition
                               {{ $jenis === $nilai ? 'bg-primary-500 text-white' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ $teks }}
                </button>
            @endforeach
        </div>

        <div class="flex flex-1 flex-wrap items-end gap-3 sm:justify-end">
            <div class="min-w-52 flex-1 sm:max-w-xs sm:flex-none">
                <label for="cari-direktori" class="sr-only">Cari fasilitas</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <x-ui.ikon nama="cari" class="h-4 w-4"/>
                    </span>
                    <x-ui.isian id="cari-direktori" wire:model.live.debounce.400ms="cari" class="pl-9"
                                placeholder="Nama atau alamat"/>
                </div>
            </div>

            @if ($latitude === null)
                <div x-data="{
                    minta() {
                        if (! navigator.geolocation) {
                            alert('Peramban ini tidak mendukung deteksi lokasi.')
                            return
                        }
                        navigator.geolocation.getCurrentPosition(
                            (p) => $wire.pakaiLokasi(p.coords.latitude, p.coords.longitude),
                            () => alert('Lokasi tidak bisa dibaca. Izinkan akses lokasi di peramban Anda.'),
                        )
                    }
                }">
                    <x-ui.tombol jenis="kedua" ikon="peta" @click="minta()">Urutkan dari terdekat</x-ui.tombol>
                </div>
            @else
                <x-ui.tombol jenis="polos" ikon="silang" wire:click="lupakanLokasi">
                    Urut abjad lagi
                </x-ui.tombol>
            @endif
        </div>
    </div>

    {{-- Daftar --}}
    <div class="mt-6" wire:loading.class="opacity-50" wire:target="jenis,cari,pakaiLokasi,lupakanLokasi">
        @if ($daftar->isEmpty())
            <div class="rounded-2xl border border-gray-200">
                <x-ui.kosong
                    ikon="peta"
                    judul="Belum ada yang terdaftar"
                    pesan="Belum ada fasilitas yang cocok dengan pencarian ini. Wilayah yang belum bergabung memang belum punya data di sini."/>
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($daftar as $item)
                    <article class="flex flex-col rounded-2xl border border-gray-200 p-5">
                        <div class="flex items-start justify-between gap-3">
                            <h2 class="min-w-0 text-base font-semibold text-primary-900">{{ $item->nama }}</h2>

                            @if ($jenis === 'tps')
                                <x-ui.lencana :status="$item->jenis"/>
                            @elseif ($jenis === 'bank_sampah' && $item->is_verified)
                                <x-ui.lencana warna="hijau" label="Terverifikasi"/>
                            @elseif ($jenis === 'umkm' && $item->is_verified)
                                <x-ui.lencana warna="hijau" label="Terverifikasi"/>
                            @endif
                        </div>

                        <p class="mt-1 text-xs text-gray-500">{{ $item->wilayah?->namaLengkap() ?? 'Wilayah belum diisi' }}</p>

                        @if ($item->alamat)
                            <p class="mt-3 line-clamp-2 text-sm text-gray-600">{{ $item->alamat }}</p>
                        @endif

                        <dl class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm">
                            @if ($jenis === 'bank_sampah')
                                <div>
                                    <dt class="text-xs text-gray-500">Jenis diterima</dt>
                                    <dd class="font-medium text-primary-900">{{ number_format($item->harga_count) }}</dd>
                                </div>
                            @elseif ($jenis === 'tps')
                                <div>
                                    <dt class="text-xs text-gray-500">Anggota</dt>
                                    <dd class="font-medium text-primary-900">{{ number_format($item->anggota_count) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">Iuran bulanan</dt>
                                    <dd class="font-medium text-primary-900">
                                        {{ $item->is_berbayar ? Rupiah::format($item->tarif_bulanan) : 'Gratis' }}
                                    </dd>
                                </div>
                            @else
                                <div>
                                    <dt class="text-xs text-gray-500">Produk</dt>
                                    <dd class="font-medium text-primary-900">{{ number_format($item->produk_count) }}</dd>
                                </div>
                                @if ($item->rating_rata)
                                    <div>
                                        <dt class="text-xs text-gray-500">Rating</dt>
                                        <dd class="font-medium text-primary-900">
                                            {{ number_format((float) $item->rating_rata, 1, ',', '.') }}
                                        </dd>
                                    </div>
                                @endif
                            @endif

                            @if (isset($item->jarak_km))
                                <div>
                                    <dt class="text-xs text-gray-500">Jarak</dt>
                                    <dd class="font-medium text-primary-700">
                                        {{ number_format((float) $item->jarak_km, 1, ',', '.') }} km
                                    </dd>
                                </div>
                            @endif
                        </dl>

                        <div class="mt-auto flex flex-wrap gap-2 pt-4">
                            @if ($item->phone)
                                <a href="tel:{{ $item->phone }}"
                                   class="text-sm font-medium text-primary-700 hover:underline">
                                    {{ $item->phone }}
                                </a>
                            @endif

                            @if ($item->latitude && $item->longitude)
                                <a href="https://www.google.com/maps?q={{ $item->latitude }},{{ $item->longitude }}"
                                   target="_blank" rel="noopener"
                                   class="ml-auto text-sm font-medium text-primary-700 hover:underline">
                                    Buka di peta
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-8">{{ $daftar->links() }}</div>
        @endif
    </div>
</div>
