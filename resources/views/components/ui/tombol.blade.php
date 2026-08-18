@props(['jenis' => 'utama', 'tipe' => 'button', 'tautan' => null, 'ikon' => null, 'ukuran' => 'sedang'])

@php
    $gaya = [
        'utama' => 'bg-primary-500 text-white shadow-sm hover:bg-primary-700 focus-visible:outline-primary-700',
        'kedua' => 'border border-gray-300 bg-white text-primary-900 hover:bg-gray-50 focus-visible:outline-gray-400',
        'halus' => 'bg-primary-50 text-primary-700 hover:bg-primary-100 focus-visible:outline-primary-500',
        'bahaya' => 'bg-red-600 text-white hover:bg-red-700 focus-visible:outline-red-700',
        'polos' => 'text-gray-600 hover:bg-gray-100 hover:text-primary-900 focus-visible:outline-gray-400',
    ][$jenis] ?? 'bg-primary-500 text-white hover:bg-primary-700';

    $besar = [
        'kecil' => 'px-3 py-1.5 text-xs gap-1.5',
        'sedang' => 'px-4 py-2.5 text-sm gap-2',
    ][$ukuran] ?? 'px-4 py-2.5 text-sm gap-2';

    $kelas = "inline-flex items-center justify-center rounded-xl font-semibold transition
              focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2
              disabled:cursor-not-allowed disabled:opacity-60 $gaya $besar";
@endphp

@if ($tautan)
    <a href="{{ $tautan }}" {{ $attributes->merge(['class' => $kelas]) }}>
        @if ($ikon)<x-ui.ikon :nama="$ikon" class="h-4 w-4 flex-none"/>@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $tipe }}" {{ $attributes->merge(['class' => $kelas]) }}>
        @if ($ikon)<x-ui.ikon :nama="$ikon" class="h-4 w-4 flex-none"/>@endif
        {{ $slot }}
    </button>
@endif
