<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <header class="max-w-2xl">
        <h1 class="text-3xl font-bold tracking-tight text-primary-900">Laporan warga</h1>
        <p class="mt-3 leading-relaxed text-gray-600">
            Setiap laporan yang masuk beserta status penanganannya, terbuka untuk siapa saja.
            Identitas pelapor dan koordinat tepatnya tidak ditampilkan.
        </p>
    </header>

    <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.bidang label="Cari" untuk="cari-laporan-publik"
                     petunjuk="Nomor tiket atau judul laporan.">
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <x-ui.ikon nama="cari" class="h-4 w-4"/>
                </span>
                <x-ui.isian id="cari-laporan-publik" wire:model.live.debounce.400ms="cari" class="pl-9"
                            placeholder="RSK-202608-00001"/>
            </div>
        </x-ui.bidang>

        <x-ui.bidang label="Status" untuk="status-laporan-publik">
            <x-ui.pilihan id="status-laporan-publik" wire:model.live="status" kosong="Semua status"
                          :opsi="$statusTersedia"/>
        </x-ui.bidang>

        <x-ui.bidang label="Kategori" untuk="kategori-laporan-publik">
            <x-ui.pilihan id="kategori-laporan-publik" wire:model.live="kategoriId" kosong="Semua kategori"
                          :opsi="$kategoriTersedia->all()"/>
        </x-ui.bidang>

        <x-ui.bidang label="Kabupaten/kota" untuk="wilayah-laporan-publik">
            <x-ui.pilihan id="wilayah-laporan-publik" wire:model.live="wilayahId" kosong="Seluruh Indonesia"
                          :opsi="$wilayahTersedia->all()"/>
        </x-ui.bidang>
    </div>

    <div class="mt-8" wire:loading.class="opacity-50" wire:target="cari,status,kategoriId,wilayahId">
        @if ($daftar->isEmpty())
            <div class="rounded-2xl border border-gray-200">
                <x-ui.kosong
                    ikon="megafon"
                    judul="Tidak ada laporan"
                    pesan="Tidak ada laporan yang cocok dengan penyaring ini."/>
            </div>
        @else
            <p class="text-sm text-gray-500">
                {{ number_format($daftar->total()) }} laporan ditemukan.
            </p>

            <ul class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($daftar as $laporan)
                    <li>
                        <a href="{{ route('publik.laporan.show', $laporan) }}" wire:navigate
                           class="flex h-full flex-col rounded-2xl border border-gray-200 p-5 transition
                                  hover:border-primary-200 hover:shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <span class="font-mono text-xs text-gray-500">{{ $laporan->tiket }}</span>
                                <x-ui.lencana :status="$laporan->status"/>
                            </div>

                            <h2 class="mt-3 line-clamp-2 text-base font-semibold text-primary-900">
                                {{ $laporan->judul }}
                            </h2>

                            <p class="mt-2 line-clamp-2 text-sm text-gray-600">{{ $laporan->deskripsi }}</p>

                            <p class="mt-auto pt-4 text-xs text-gray-500">
                                {{ $laporan->kategori?->nama }}
                                @if ($laporan->kabupaten) &middot; {{ $laporan->kabupaten->nama }} @endif
                                &middot; {{ $laporan->created_at->translatedFormat('j M Y') }}
                            </p>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-8">{{ $daftar->links() }}</div>
        @endif
    </div>
</div>
