<div>
    <section class="border-b border-gray-100 bg-primary-50/40">
        <div class="mx-auto max-w-6xl px-4 py-12">
            <h1 class="text-3xl font-bold text-primary-900">Direktori UMKM</h1>
            <p class="mt-2 max-w-2xl text-gray-600">Produk kreatif dari bahan daur ulang oleh pelaku usaha di Kabupaten Badung.</p>
            <input wire:model.live.debounce.300ms="search" placeholder="Cari UMKM…" class="mt-6 w-full max-w-sm rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
        </div>
    </section>
    <section class="mx-auto max-w-6xl px-4 py-10">
        @if ($umkms->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 py-16 text-center text-gray-400">UMKM tidak ditemukan.</div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($umkms as $u)
                    <a href="{{ route('publik.umkm.show', $u) }}" class="group overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
                        <div class="aspect-video bg-gray-100">
                            @if ($u->foto)<img src="{{ asset('storage/' . $u->foto) }}" class="h-full w-full object-cover" alt="">@else<div class="flex h-full items-center justify-center text-primary-200"><svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" d="M3 9h18M3 9l2-4h14l2 4M5 9v10h14V9"/></svg></div>@endif
                        </div>
                        <div class="p-5">
                            <h3 class="font-semibold text-primary-900 group-hover:text-primary-700">{{ $u->nama }}</h3>
                            <p class="mt-1 line-clamp-2 text-sm text-gray-500">{{ $u->deskripsi }}</p>
                            <div class="mt-3 flex items-center justify-between text-xs text-gray-400">
                                <span>{{ $u->banjarDinas?->nama }}</span>
                                <span>{{ $u->products_count }} produk</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-8">{{ $umkms->links() }}</div>
        @endif
    </section>
</div>