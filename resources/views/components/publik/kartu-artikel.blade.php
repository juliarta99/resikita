@props(['artikel'])

<article {{ $attributes->merge(['class' => 'group flex flex-col overflow-hidden rounded-2xl border border-gray-200 transition hover:border-primary-200 hover:shadow-sm']) }}>
    <a href="{{ route('publik.artikel.show', $artikel) }}" wire:navigate class="flex flex-1 flex-col">
        <div class="aspect-16/9 overflow-hidden bg-gray-100">
            @if ($artikel->thumbnail)
                <img src="{{ Storage::url($artikel->thumbnail) }}" alt=""
                     loading="lazy"
                     class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
            @else
                <div class="flex h-full items-center justify-center text-gray-300">
                    <x-ui.ikon nama="buku" class="h-8 w-8"/>
                </div>
            @endif
        </div>

        <div class="flex flex-1 flex-col p-5">
            <div class="flex flex-wrap items-center gap-2">
                @if ($artikel->kategori)
                    <x-ui.lencana warna="hijau" :label="$artikel->kategori->nama"/>
                @endif
                <x-ui.lencana warna="abu" :label="$artikel->tipe->label()"/>
            </div>

            <h3 class="mt-3 line-clamp-2 text-base font-semibold text-primary-900 group-hover:text-primary-700">
                {{ $artikel->judul }}
            </h3>

            <p class="mt-auto pt-4 text-xs text-gray-500">
                {{ $artikel->published_at?->translatedFormat('j F Y') }}
                @if ($artikel->estimasi_baca_menit)
                    &middot; {{ $artikel->estimasi_baca_menit }} menit baca
                @endif
                &middot; bisa didengarkan
            </p>
        </div>
    </a>
</article>
