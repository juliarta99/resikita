<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <header class="max-w-2xl">
        <h1 class="text-3xl font-bold tracking-tight text-primary-900">Produk daur ulang</h1>
        <p class="mt-3 leading-relaxed text-gray-600">
            Dibuat UMKM dari bahan yang tadinya berakhir di TPA. Pembelian dilakukan lewat
            aplikasi Resikita di ponsel.
        </p>
    </header>

    <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:max-w-2xl">
        <x-ui.bidang label="Cari produk" untuk="cari-produk-publik"
                     petunjuk="Nama produk atau bahan bakunya.">
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <x-ui.ikon nama="cari" class="h-4 w-4"/>
                </span>
                <x-ui.isian id="cari-produk-publik" wire:model.live.debounce.400ms="cari" class="pl-9"
                            placeholder="Misalnya: tas, sachet, kardus"/>
            </div>
        </x-ui.bidang>

        <x-ui.bidang label="Urutkan" untuk="urut-produk">
            <x-ui.pilihan id="urut-produk" wire:model.live="urut" :opsi="[
                'terbaru' => 'Terbaru',
                'termurah' => 'Harga terendah',
                'termahal' => 'Harga tertinggi',
                'rating' => 'Rating tertinggi',
            ]"/>
        </x-ui.bidang>
    </div>

    @if ($kategoriTersedia->isNotEmpty())
        <div class="mt-5 flex flex-wrap gap-2" role="group" aria-label="Saring kategori produk">
            <button type="button" wire:click="$set('kategori', '')"
                    @if ($kategori === '') aria-current="true" @endif
                    class="rounded-full px-3.5 py-1.5 text-sm font-medium transition
                           {{ $kategori === '' ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Semua
            </button>

            @foreach ($kategoriTersedia as $item)
                <button type="button" wire:click="$set('kategori', '{{ $item->slug }}')"
                        @if ($kategori === $item->slug) aria-current="true" @endif
                        class="rounded-full px-3.5 py-1.5 text-sm font-medium transition
                               {{ $kategori === $item->slug ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    {{ $item->nama }}
                    <span class="opacity-60">{{ $item->produk_count }}</span>
                </button>
            @endforeach
        </div>
    @endif

    <div class="mt-8" wire:loading.class="opacity-50" wire:target="cari,urut,kategori">
        @if ($daftar->isEmpty())
            <div class="rounded-2xl border border-gray-200">
                <x-ui.kosong
                    ikon="kotak"
                    judul="Belum ada produk"
                    pesan="Tidak ada produk yang cocok dengan penyaring ini, atau belum ada UMKM terverifikasi yang berjualan."/>
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($daftar as $produk)
                    <x-publik.kartu-produk :produk="$produk"/>
                @endforeach
            </div>

            <div class="mt-8">{{ $daftar->links() }}</div>
        @endif
    </div>
</div>
