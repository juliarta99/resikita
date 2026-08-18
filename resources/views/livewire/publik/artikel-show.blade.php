<div>
    <article class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <a href="{{ route('publik.artikel') }}" wire:navigate
           class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-primary-900">
            <x-ui.ikon nama="keluar" class="h-4 w-4 rotate-180"/>
            Kembali ke daftar artikel
        </a>

        <header class="mt-6">
            <div class="flex flex-wrap items-center gap-2">
                @if ($artikel->kategori)
                    <x-ui.lencana warna="hijau" :label="$artikel->kategori->nama"/>
                @endif
                <x-ui.lencana :status="$artikel->tipe"/>
            </div>

            <h1 class="mt-4 text-3xl font-bold leading-tight tracking-tight text-primary-900 sm:text-4xl">
                {{ $artikel->judul }}
            </h1>

            <p class="mt-4 text-sm text-gray-500">
                {{ $artikel->published_at?->translatedFormat('j F Y') }}
                @if ($artikel->penulis) &middot; {{ $artikel->penulis->name }} @endif
                @if ($artikel->estimasi_baca_menit)
                    &middot; {{ $artikel->estimasi_baca_menit }} menit baca
                @endif
                &middot; {{ number_format($artikel->dilihat) }} kali dibaca
            </p>
        </header>

        {{-- Pemutar suara --}}
        <div
            x-data="pembacaArtikel(@js($teksBaca))"
            x-init="siapkan()"
            class="mt-8 rounded-2xl border border-primary-100 bg-primary-50/60 p-4"
        >
            <template x-if="didukung">
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button"
                            @click="putarAtauJeda()"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary-500 px-4 py-2.5 text-sm
                                   font-semibold text-white transition hover:bg-primary-700"
                            x-bind:aria-label="sedangMemutar ? 'Jeda pembacaan artikel' : 'Dengarkan artikel ini'">
                        <x-ui.ikon nama="suara" class="h-4 w-4"/>
                        <span x-text="sedangMemutar ? 'Jeda' : (pernahMulai ? 'Lanjutkan' : 'Dengarkan artikel')"></span>
                    </button>

                    <button type="button"
                            x-show="pernahMulai"
                            @click="hentikan()"
                            class="inline-flex items-center gap-2 rounded-xl border border-primary-200 bg-white
                                   px-3.5 py-2.5 text-sm font-medium text-primary-900 transition hover:bg-primary-50"
                            aria-label="Hentikan pembacaan artikel">
                        <x-ui.ikon nama="silang" class="h-4 w-4"/>
                        Hentikan
                    </button>

                    <label class="ml-auto flex items-center gap-2 text-xs text-gray-600">
                        Kecepatan
                        <select x-model.number="kecepatan" @change="ulangJikaSedangMemutar()"
                                class="rounded-lg border border-primary-200 bg-white px-2 py-1 text-xs"
                                aria-label="Kecepatan pembacaan">
                            <option value="0.8">0,8&times;</option>
                            <option value="1">1&times;</option>
                            <option value="1.25">1,25&times;</option>
                            <option value="1.5">1,5&times;</option>
                        </select>
                    </label>
                </div>
            </template>

            <template x-if="!didukung">
                <p class="text-sm text-gray-600">
                    Peramban ini belum mendukung pembacaan suara. Buka lewat aplikasi Resikita di
                    ponsel untuk mendengarkan artikel ini.
                </p>
            </template>

            <p class="mt-3 text-xs text-gray-500" role="status" aria-live="polite" x-text="keterangan"></p>
        </div>

        {{-- Isi --}}
        <div class="article-content mt-8">
            {!! Str::markdown($artikel->konten) !!}
        </div>

        @if ($artikel->video_url)
            <div class="mt-8">
                <h2 class="text-sm font-semibold text-primary-900">Video pendukung</h2>
                <a href="{{ $artikel->video_url }}" target="_blank" rel="noopener"
                   class="mt-2 inline-flex items-center gap-2 text-sm font-medium text-primary-700 underline">
                    Tonton di sumber aslinya
                </a>
            </div>
        @endif
    </article>

    {{-- Artikel lain --}}
    @if ($lainnya->isNotEmpty())
        <section class="border-t border-gray-100 bg-gray-50 py-12" aria-labelledby="judul-lainnya">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 id="judul-lainnya" class="text-xl font-bold tracking-tight text-primary-900">
                    Bacaan lain yang berkaitan
                </h2>

                <div class="mt-6 grid gap-5 md:grid-cols-3">
                    @foreach ($lainnya as $item)
                        <x-publik.kartu-artikel :artikel="$item" class="bg-white"/>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>
