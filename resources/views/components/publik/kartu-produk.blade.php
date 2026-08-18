@props(['produk'])

@php use App\Support\Rupiah; @endphp

<article {{ $attributes->merge(['class' => 'group flex flex-col overflow-hidden rounded-2xl border border-gray-200 transition hover:border-primary-200 hover:shadow-sm']) }}>
    <a href="{{ route('publik.produk.show', $produk) }}" wire:navigate class="flex flex-1 flex-col">
        <div class="aspect-square overflow-hidden bg-gray-100">
            @if ($produk->fotoUtama)
                <img src="{{ $produk->fotoUtama->url() }}" alt=""
                     loading="lazy"
                     class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
            @else
                <div class="flex h-full items-center justify-center text-gray-300">
                    <x-ui.ikon nama="kotak" class="h-8 w-8"/>
                </div>
            @endif
        </div>

        <div class="flex flex-1 flex-col p-4">
            <h3 class="line-clamp-2 text-sm font-semibold text-primary-900 group-hover:text-primary-700">
                {{ $produk->nama }}
            </h3>

            @if ($produk->bahan_baku)
                <p class="mt-1 line-clamp-1 text-xs text-primary-700">Dari {{ $produk->bahan_baku }}</p>
            @endif

            <p class="mt-3 text-base font-bold text-primary-900">{{ Rupiah::format($produk->harga) }}</p>

            <div class="mt-auto flex items-center justify-between gap-2 pt-3">
                <span class="truncate text-xs text-gray-500">{{ $produk->umkm?->nama }}</span>

                @if ($produk->ulasan_count > 0)
                    <span class="flex flex-none items-center gap-1 text-xs text-gray-600">
                        <x-ui.ikon nama="centang" class="h-3 w-3 text-amber-500"/>
                        {{ number_format((float) $produk->rating_rata, 1, ',', '.') }}
                        <span class="text-gray-400">({{ $produk->ulasan_count }})</span>
                    </span>
                @endif
            </div>
        </div>
    </a>
</article>
