<div>
    <section class="relative overflow-hidden border-b border-gray-100 bg-primary-50/40">
        {{-- Pattern titik halus --}}
        <div class="pointer-events-none absolute inset-0"
             style="opacity:.6;background-image:radial-gradient(#057d5d1a 1.2px, transparent 1.2px);background-size:22px 22px;"></div>

        {{-- Dekorasi beranimasi (desktop only) --}}
        <style>
            @keyframes nr-float { 0%,100% { transform: translateY(0) } 50% { transform: translateY(-16px) } }
            @keyframes nr-drift { 0%,100% { transform: translate(0,0) rotate(0deg) } 50% { transform: translate(10px,-14px) rotate(8deg) } }
            @keyframes nr-pulse { 0%,100% { transform: scale(1); opacity:.4 } 50% { transform: scale(1.14); opacity:.6 } }
            @keyframes nr-spin  { from { transform: rotate(0) } to { transform: rotate(360deg) } }
            .nr-float { animation: nr-float 7s ease-in-out infinite }
            .nr-drift { animation: nr-drift 9s ease-in-out infinite }
            .nr-pulse { animation: nr-pulse 6s ease-in-out infinite }
            .nr-spin  { animation: nr-spin 30s linear infinite }
            @media (prefers-reduced-motion: reduce) { .nr-float,.nr-drift,.nr-pulse,.nr-spin { animation: none } }
        </style>

        {{-- Blob & bentuk aksen sky (khas TPS) di kanan header --}}
        <div class="pointer-events-none absolute -right-16 -top-16 hidden h-64 w-64 rounded-full bg-sky-400/15 blur-3xl nr-pulse lg:block"></div>
        <div class="pointer-events-none absolute right-[12%] top-[24%] hidden h-16 w-16 rounded-2xl bg-sky-400/15 nr-drift lg:block"></div>

        {{-- Ikon pin lokasi berputar --}}
        <div class="pointer-events-none absolute right-[6%] bottom-[10%] hidden text-sky-500/25 nr-float lg:block" style="animation-delay:1s">
            <svg class="h-24 w-24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"><path d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>

        <div class="relative z-10 mx-auto max-w-7xl px-4 py-12">
            <h1 class="mt-4 text-3xl font-bold text-primary-900">Lokasi TPS</h1>
            <p class="mt-2 max-w-2xl text-gray-600">Tempat pengelolaan sampah di Kabupaten Badung beserta status layanannya.</p>
            <input wire:model.live.debounce.300ms="search" placeholder="Cari TPS / alamat…" class="mt-6 w-full max-w-sm rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10">
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