<div>
    <section class="border-b border-gray-100 bg-primary-50/40">
        <div class="mx-auto max-w-6xl px-4 py-12">
            <h1 class="text-3xl font-bold text-primary-900">Lokasi TPS</h1>
            <p class="mt-2 max-w-2xl text-gray-600">Tempat pengelolaan sampah di Kabupaten Badung beserta status layanannya.</p>
            <input wire:model.live.debounce.300ms="search" placeholder="Cari TPS / alamat…" class="mt-6 w-full max-w-sm rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
        </div>
    </section>
    <section class="mx-auto max-w-6xl px-4 py-10">
        @if ($daftar->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 py-16 text-center text-gray-400">TPS tidak ditemukan.</div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($daftar as $t)
                    <a href="{{ route('publik.tps.show', $t) }}" class="group overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
                        <div class="aspect-video bg-gray-100">
                            @if ($t->foto)<img src="{{ asset('storage/' . $t->foto) }}" class="h-full w-full object-cover" alt="">@else<div class="flex h-full items-center justify-center text-sky-200"><svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" d="M3 7h18M6 7v13h12V7M9 11h6"/></svg></div>@endif
                        </div>
                        <div class="p-5">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="font-semibold text-primary-900 group-hover:text-primary-700">{{ $t->nama }}</h3>
                                @if ($t->is_berbayar)<span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">Berbayar</span>@else<span class="rounded-full bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700">Gratis</span>@endif
                            </div>
                            <p class="mt-1 line-clamp-2 text-sm text-gray-500">{{ $t->alamat }}</p>
                            <p class="mt-3 text-xs text-gray-400">{{ $t->banjarDinas?->nama }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-8">{{ $daftar->links() }}</div>
        @endif
    </section>
</div>