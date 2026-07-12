<div>
    <section class="border-b border-gray-100 bg-primary-50/40">
        <div class="mx-auto max-w-6xl px-4 py-12">
            <h1 class="text-3xl font-bold text-primary-900">Laporan Publik</h1>
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
    <section class="mx-auto max-w-6xl px-4 py-10">
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