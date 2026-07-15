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

        {{-- Blob & bentuk aksen merah (khas laporan) di kanan header --}}
        <div class="pointer-events-none absolute -right-16 -top-16 hidden h-64 w-64 rounded-full bg-red-400/12 blur-3xl nr-pulse lg:block"></div>
        <div class="pointer-events-none absolute right-[12%] top-[24%] hidden h-16 w-16 rounded-2xl bg-red-400/12 nr-drift lg:block"></div>

        {{-- Ikon peringatan melayang --}}
        <div class="pointer-events-none absolute right-[6%] bottom-[10%] hidden text-red-500/25 nr-float lg:block" style="animation-delay:1s">
            <svg class="h-24 w-24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        </div>

        <div class="relative z-10 mx-auto max-w-7xl px-4 py-12">
            <h1 class="mt-4 text-3xl font-bold text-primary-900">Laporan Publik</h1>
            <p class="mt-2 max-w-2xl text-gray-600">Transparansi penanganan laporan masalah sampah dari warga.</p>
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <select wire:model.live="kategoriFilter" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    <option value="">Semua kategori</option>
                    @foreach ($kategoris as $k)<option value="{{ $k->id }}">{{ $k->nama }}</option>@endforeach
                </select>
                <div class="flex flex-wrap gap-2">
                    @foreach (['semua' => 'Semua', 'proses' => 'Diproses', 'selesai' => 'Selesai'] as $key => $label)
                        <button wire:click="$set('statusFilter', '{{ $key }}')" class="rounded-lg px-3 py-1.5 text-sm font-medium {{ $statusFilter === $key ? 'bg-primary-500 text-white' : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10">
        @if ($daftar->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 py-16 text-center text-gray-400">Belum ada laporan.</div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($daftar as $l)
                    <a href="{{ route('publik.laporan.show', $l) }}" class="group overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
                        <div class="aspect-video bg-gray-100">
                            @if ($l->foto)<img src="{{ asset('storage/' . $l->foto) }}" class="h-full w-full object-cover" alt="">@endif
                        </div>
                        <div class="p-5">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-primary-500">{{ $l->kategori?->nama }}</span>
                                <x-status-badge :status="$l->status" />
                            </div>
                            <h3 class="mt-1 font-semibold text-primary-900 group-hover:text-primary-700">{{ $l->judul }}</h3>
                            <p class="mt-1 line-clamp-1 text-xs text-gray-400">{{ $l->alamat }}</p>
                            <p class="mt-2 text-xs text-gray-400">{{ $l->created_at->translatedFormat('d F Y') }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-8">{{ $daftar->links() }}</div>
        @endif
    </section>
</div>