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

        {{-- Blob & bentuk primary di kanan header --}}
        <div class="pointer-events-none absolute -right-16 -top-16 hidden h-64 w-64 rounded-full bg-primary-500/15 blur-3xl nr-pulse lg:block"></div>
        <div class="pointer-events-none absolute right-[12%] top-[24%] hidden h-16 w-16 rounded-2xl bg-primary-500/15 nr-drift lg:block"></div>

        {{-- Ikon bank / gedung setor melayang --}}
        <div class="pointer-events-none absolute right-[6%] bottom-[10%] hidden text-primary-500/25 nr-float lg:block" style="animation-delay:1s">
            <svg class="h-24 w-24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M4 21V10h16v11M3 10l9-6 9 6M9 21v-5h6v5M9 13h.01M15 13h.01"/></svg>
        </div>

        <div class="relative z-10 mx-auto max-w-7xl px-4 py-12">
            <h1 class="mt-4 text-3xl font-bold text-primary-900">Bank Sampah</h1>
            <p class="mt-2 max-w-2xl text-gray-600">Titik setor sampah untuk ditukar menjadi saldo digital.</p>
            <input wire:model.live.debounce.300ms="search" placeholder="Cari bank sampah / alamat…" class="mt-6 w-full max-w-sm rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10">
        @if ($daftar->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 py-16 text-center text-gray-400">Bank sampah tidak ditemukan.</div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($daftar as $b)
                    <a href="{{ route('publik.bank-sampah.show', $b) }}" class="group overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
                        <div class="aspect-video bg-gray-100">
                            @if ($b->foto)<img src="{{ asset('storage/' . $b->foto) }}" class="h-full w-full object-cover" alt="">@else<div class="flex h-full items-center justify-center text-primary-200"><svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" d="M3 7l9-4 9 4-9 4-9-4Zm0 5l9 4 9-4M3 17l9 4 9-4"/></svg></div>@endif
                        </div>
                        <div class="p-5">
                            <h3 class="font-semibold text-primary-900 group-hover:text-primary-700">{{ $b->nama }}</h3>
                            <p class="mt-1 line-clamp-2 text-sm text-gray-500">{{ $b->alamat }}</p>
                            <p class="mt-3 text-xs text-gray-400">{{ $b->banjarDinas?->nama }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-8">{{ $daftar->links() }}</div>
        @endif
    </section>
</div>