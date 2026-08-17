@props(['label' => null, 'untuk' => null, 'wajib' => false, 'petunjuk' => null, 'galat' => null])

{{--
    Pembungkus satu bidang isian.

    Menyatukan label, petunjuk, dan pesan galat supaya ketiganya selalu
    tersambung ke input yang benar. Label yang tidak terhubung `for` dan
    pesan galat yang tidak terhubung `aria-describedby` membuat formulir
    tetap terlihat benar di layar, tapi tidak terbaca oleh pembaca layar
   , persis kegagalan aksesibilitas yang paling mudah lolos.
--}}
@php
    $idGalat = $untuk ? $untuk.'-galat' : null;
    $idPetunjuk = $untuk ? $untuk.'-petunjuk' : null;
@endphp

<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if ($label)
        <label @if ($untuk) for="{{ $untuk }}" @endif class="block text-sm font-medium text-primary-900">
            {{ $label }}
            @if ($wajib)
                <span class="text-red-600" aria-hidden="true">*</span>
                <span class="sr-only">wajib diisi</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if ($petunjuk && ! $galat)
        <p @if ($idPetunjuk) id="{{ $idPetunjuk }}" @endif class="text-xs text-gray-500">{{ $petunjuk }}</p>
    @endif

    @if ($galat)
        <p @if ($idGalat) id="{{ $idGalat }}" @endif class="text-xs font-medium text-red-600">{{ $galat }}</p>
    @endif
</div>
