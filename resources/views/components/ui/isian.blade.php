@props(['tipe' => 'text', 'galat' => false])

@php
    $garis = $galat
        ? 'border-red-300 focus:border-red-500 focus:ring-red-200'
        : 'border-gray-300 focus:border-primary-500 focus:ring-primary-100';
@endphp

<input type="{{ $tipe }}"
       @if ($galat) aria-invalid="true" @endif
       {{ $attributes->merge([
           'class' => 'block w-full rounded-xl border px-3.5 py-2.5 text-sm text-gray-900 shadow-sm
                       transition placeholder:text-gray-400 focus:outline-none focus:ring-4
                       disabled:bg-gray-50 disabled:text-gray-500 '.$garis,
       ]) }}>
