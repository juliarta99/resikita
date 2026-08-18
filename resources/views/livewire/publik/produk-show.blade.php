@php use App\Support\Rupiah; @endphp

<div>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <a href="{{ route('publik.produk') }}" wire:navigate
           class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-primary-900">
            <x-ui.ikon nama="keluar" class="h-4 w-4 rotate-180"/>
            Kembali ke marketplace
        </a>

        <div class="mt-6 grid gap-10 lg:grid-cols-2">

            {{-- Galeri --}}
            <div x-data="{ aktif: 0 }">
                @php $foto = $produk->foto->sortByDesc('is_utama')->values(); @endphp

                <div class="aspect-square overflow-hidden rounded-2xl bg-gray-100">
                    @if ($foto->isNotEmpty())
                        @foreach ($foto as $gambar)
                            <img x-show="aktif === {{ $loop->index }}"
                                 src="{{ $gambar->url() }}"
                                 alt="Foto produk {{ $produk->nama }} nomor {{ $loop->iteration }}"
                                 class="h-full w-full object-cover">
                        @endforeach
                    @else
                        <div class="flex h-full items-center justify-center text-gray-300">
                            <x-ui.ikon nama="kotak" class="h-12 w-12"/>
                        </div>
                    @endif
                </div>

                @if ($foto->count() > 1)
                    <div class="mt-3 grid grid-cols-4 gap-2 sm:grid-cols-5">
                        @foreach ($foto as $gambar)
                            <button type="button" @click="aktif = {{ $loop->index }}"
                                    class="overflow-hidden rounded-lg ring-2 transition"
                                    :class="aktif === {{ $loop->index }} ? 'ring-primary-500' : 'ring-transparent'"
                                    aria-label="Lihat foto nomor {{ $loop->iteration }}">
                                <img src="{{ $gambar->url() }}" alt=""
                                     class="aspect-square w-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Keterangan --}}
            <div>
                @if ($produk->kategori)
                    <x-ui.lencana warna="hijau" :label="$produk->kategori->nama"/>
                @endif

                <h1 class="mt-3 text-3xl font-bold tracking-tight text-primary-900">{{ $produk->nama }}</h1>

                <p class="mt-4 text-3xl font-bold text-primary-900">{{ Rupiah::format($produk->harga) }}</p>

                @if ($produk->ulasan_count > 0)
                    <p class="mt-2 flex items-center gap-1.5 text-sm text-gray-600">
                        <x-ui.ikon nama="centang" class="h-4 w-4 text-amber-500"/>
                        <span class="font-medium text-primary-900">
                            {{ number_format((float) $produk->rating_rata, 1, ',', '.') }}
                        </span>
                        dari {{ number_format($produk->ulasan_count) }} ulasan
                    </p>
                @endif

                @if ($produk->bahan_baku)
                    <div class="mt-6 rounded-2xl border border-primary-100 bg-primary-50/60 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-primary-700">Bahan baku</p>
                        <p class="mt-1 text-sm text-primary-900">{{ $produk->bahan_baku }}</p>
                        <p class="mt-2 text-xs text-gray-600">
                            Bahan ini tadinya berakhir di TPA. Membeli produknya membuat siklusnya tertutup.
                        </p>
                    </div>
                @endif

                @if ($produk->deskripsi)
                    <div class="mt-6">
                        <h2 class="text-sm font-semibold text-primary-900">Deskripsi</h2>
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-gray-600">
                            {{ $produk->deskripsi }}
                        </p>
                    </div>
                @endif

                <dl class="mt-6 grid grid-cols-2 gap-4 border-t border-gray-100 pt-6 text-sm">
                    <div>
                        <dt class="text-xs text-gray-500">Stok tersedia</dt>
                        <dd class="mt-0.5 font-medium text-primary-900">{{ number_format($produk->stok) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500">Berat</dt>
                        <dd class="mt-0.5 font-medium text-primary-900">
                            {{ number_format($produk->berat_gram) }} gram
                        </dd>
                    </div>
                </dl>

                {{-- Penjual --}}
                <div class="mt-6 rounded-2xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500">Dijual oleh</p>
                    <p class="mt-0.5 font-semibold text-primary-900">{{ $produk->umkm?->nama }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $produk->umkm?->wilayah?->namaLengkap() ?? 'Wilayah belum diisi' }}
                    </p>
                </div>

                <div class="mt-6 rounded-2xl bg-gray-50 p-4">
                    <p class="text-sm text-gray-600">
                        Pembelian dilakukan lewat aplikasi Resikita di ponsel, tempat alamat pengiriman
                        dan pembayaran bisa diproses dengan aman.
                    </p>
                </div>
            </div>
        </div>

        {{-- Ulasan --}}
        @if ($ulasan->isNotEmpty())
            <section class="mt-14" aria-labelledby="judul-ulasan">
                <h2 id="judul-ulasan" class="text-xl font-bold tracking-tight text-primary-900">
                    Ulasan pembeli
                </h2>

                <ul class="mt-6 grid gap-4 md:grid-cols-2">
                    @foreach ($ulasan as $item)
                        <li class="rounded-2xl border border-gray-200 p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-primary-900">
                                        {{ $item->user?->name ?? 'Pembeli' }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ $item->created_at->translatedFormat('j F Y') }}
                                    </p>
                                </div>
                                <span class="flex flex-none items-center gap-1 text-sm font-medium text-amber-600"
                                      aria-label="Rating {{ $item->rating }} dari 5">
                                    <x-ui.ikon nama="centang" class="h-4 w-4"/>
                                    {{ $item->rating }}/5
                                </span>
                            </div>

                            @if ($item->komentar)
                                <p class="mt-3 text-sm leading-relaxed text-gray-600">{{ $item->komentar }}</p>
                            @endif

                            @if ($item->urlFoto())
                                <img src="{{ $item->urlFoto() }}" alt="Foto dari ulasan pembeli"
                                     loading="lazy"
                                     class="mt-3 w-32 rounded-lg object-cover">
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>

    {{-- Produk lain dari toko yang sama --}}
    @if ($lainnya->isNotEmpty())
        <section class="border-t border-gray-100 bg-gray-50 py-12" aria-labelledby="judul-lain-toko">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 id="judul-lain-toko" class="text-xl font-bold tracking-tight text-primary-900">
                    Produk lain dari {{ $produk->umkm?->nama }}
                </h2>

                <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($lainnya as $item)
                        <x-publik.kartu-produk :produk="$item" class="bg-white"/>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>
