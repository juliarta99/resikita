<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <header class="max-w-2xl">
        <h1 class="text-3xl font-bold tracking-tight text-primary-900">Literasi lingkungan</h1>
        <p class="mt-3 leading-relaxed text-gray-600">
            Panduan praktis memilah, mengompos, dan mengurangi sampah. Setiap artikel bisa
            didengarkan, bukan hanya dibaca.
        </p>
    </header>

    {{-- Penyaring --}}
    <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:max-w-2xl">
        <x-ui.bidang label="Cari artikel" untuk="cari-artikel-publik">
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <x-ui.ikon nama="cari" class="h-4 w-4"/>
                </span>
                <x-ui.isian id="cari-artikel-publik" wire:model.live.debounce.400ms="cari" class="pl-9"
                            placeholder="Kata kunci judul"/>
            </div>
        </x-ui.bidang>

        <x-ui.bidang label="Tipe konten" untuk="tipe-artikel-publik">
            <x-ui.pilihan id="tipe-artikel-publik" wire:model.live="tipe" kosong="Semua tipe"
                          :opsi="$tipeTersedia"/>
        </x-ui.bidang>
    </div>

    {{-- Kategori --}}
    @if ($kategoriTersedia->isNotEmpty())
        <div class="mt-5 flex flex-wrap gap-2" role="group" aria-label="Saring kategori">
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
                    <span class="opacity-60">{{ $item->artikel_count }}</span>
                </button>
            @endforeach
        </div>
    @endif

    {{-- Daftar --}}
    <div class="mt-8" wire:loading.class="opacity-50" wire:target="cari,tipe,kategori">
        @if ($daftar->isEmpty())
            <div class="rounded-2xl border border-gray-200">
                <x-ui.kosong
                    ikon="buku"
                    judul="Belum ada artikel"
                    pesan="Tidak ada artikel yang cocok dengan penyaring ini. Coba hapus sebagian penyaringnya."/>
            </div>
        @else
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($daftar as $artikel)
                    <x-publik.kartu-artikel :artikel="$artikel"/>
                @endforeach
            </div>

            <div class="mt-8">{{ $daftar->links() }}</div>
        @endif
    </div>
</div>
