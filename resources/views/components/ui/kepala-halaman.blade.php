@props(['judul', 'keterangan' => null])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-wrap items-start justify-between gap-3']) }}>
    <div class="min-w-0">
        <h2 class="text-lg font-bold tracking-tight text-primary-900">{{ $judul }}</h2>
        @if ($keterangan)
            <p class="mt-1 max-w-2xl text-sm text-gray-500">{{ $keterangan }}</p>
        @endif
    </div>

    @isset($aksi)
        <div class="flex flex-wrap items-center gap-2">{{ $aksi }}</div>
    @endisset
</div>
