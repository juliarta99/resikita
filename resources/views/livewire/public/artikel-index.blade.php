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

        {{-- Ikon buku edukasi melayang --}}
        <div class="pointer-events-none absolute right-[6%] bottom-[10%] hidden text-primary-500/25 nr-float lg:block" style="animation-delay:1s">
            <svg class="h-24 w-24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6.5A2.5 2.5 0 0 1 14.5 4H20v13h-5.5A2.5 2.5 0 0 0 12 19.5m0-13A2.5 2.5 0 0 0 9.5 4H4v13h5.5A2.5 2.5 0 0 1 12 19.5m0-13v13"/></svg>
        </div>

        <div class="relative z-10 mx-auto max-w-7xl px-4 py-12">
            <h1 class="mt-4 text-3xl font-semibold text-primary-900">Edukasi & Panduan</h1>
            <p class="mt-2 max-w-2xl text-gray-600">Pelajari cara memilah sampah, memanfaatkan bank sampah, dan mendukung ekonomi sirkular di Kabupaten Badung.</p>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <input wire:model.live.debounce.300ms="search" placeholder="Cari artikel…"
                       class="w-full max-w-xs rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                <div class="flex flex-wrap gap-2">
                    <button wire:click="$set('tipeFilter', '')"
                            class="rounded-lg px-3 py-1.5 text-sm font-medium {{ $tipeFilter === '' ? 'bg-primary-500 text-white' : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">Semua</button>
                    @foreach ($tipeList as $key => $label)
                        <button wire:click="$set('tipeFilter', '{{ $key }}')"
                                class="rounded-lg px-3 py-1.5 text-sm font-medium {{ $tipeFilter === $key ? 'bg-primary-500 text-white' : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10">
        @if ($artikel->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 py-16 text-center text-gray-400">Belum ada artikel.</div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($artikel as $a)
                    <a href="{{ route('artikel.show', $a->slug) }}" class="group overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
                        <div class="aspect-video bg-gray-100">
                            @if ($a->thumbnail)
                                <img src="{{ asset('storage/' . $a->thumbnail) }}" alt="" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full items-center justify-center text-primary-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 5h16v14H4zM4 15l4-4 3 3 5-5 4 4"/>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="p-5">
                            <span class="text-xs font-medium uppercase tracking-wide text-primary-500">{{ $tipeList[$a->tipe] ?? $a->tipe }}</span>
                            <h2 class="mt-1 font-semibold text-primary-900 group-hover:text-primary-700">{{ $a->judul }}</h2>
                            <p class="mt-2 text-sm text-gray-500">{{ \Illuminate\Support\Str::limit(strip_tags($a->konten), 90) }}</p>
                            <p class="mt-3 text-xs text-gray-400">{{ $a->published_at?->translatedFormat('d F Y') }}</p>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">{{ $artikel->links() }}</div>
        @endif
    </section>
</div>