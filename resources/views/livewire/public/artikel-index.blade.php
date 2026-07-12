<div>
    <section class="border-b border-gray-100 bg-primary-50/40">
        <div class="mx-auto max-w-4xl px-4 py-12">
            <h1 class="text-3xl font-semibold text-primary-900">Edukasi & Panduan</h1>
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

    <section class="mx-auto max-w-4xl px-4 py-10">
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