@props(['judul' => null, 'keterangan' => null, 'padat' => false])

<section {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white shadow-sm']) }}>
    @if ($judul || isset($aksi))
        <header class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-100 px-5 py-4">
            <div class="min-w-0">
                @if ($judul)
                    <h2 class="text-sm font-semibold text-primary-900">{{ $judul }}</h2>
                @endif
                @if ($keterangan)
                    <p class="mt-0.5 text-xs text-gray-500">{{ $keterangan }}</p>
                @endif
            </div>

            @isset($aksi)
                <div class="flex flex-wrap items-center gap-2">{{ $aksi }}</div>
            @endisset
        </header>
    @endif

    <div class="{{ $padat ? '' : 'p-5' }}">
        {{ $slot }}
    </div>
</section>
