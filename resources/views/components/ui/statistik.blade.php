@props([
    'label',
    'nilai',
    'satuan' => null,
    'ikon' => 'grafik',
    'warna' => 'primary',
    'keterangan' => null,
])

@php
    $warnaIkon = [
        'primary' => 'bg-primary-50 text-primary-600',
        'biru' => 'bg-blue-50 text-blue-600',
        'kuning' => 'bg-amber-50 text-amber-600',
        'merah' => 'bg-red-50 text-red-600',
        'abu' => 'bg-gray-100 text-gray-500',
    ][$warna] ?? 'bg-primary-50 text-primary-600';
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white p-5 shadow-sm']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-gray-500">{{ $label }}</p>
            <p class="mt-1.5 text-2xl font-bold tracking-tight text-primary-900">
                {{ $nilai }}@if ($satuan)<span class="ml-1 text-sm font-medium text-gray-500">{{ $satuan }}</span>@endif
            </p>
            @if ($keterangan)
                <p class="mt-1 text-xs text-gray-500">{{ $keterangan }}</p>
            @endif
        </div>

        <span class="flex h-10 w-10 flex-none items-center justify-center rounded-xl {{ $warnaIkon }}">
            <x-ui.ikon :nama="$ikon"/>
        </span>
    </div>
</div>
