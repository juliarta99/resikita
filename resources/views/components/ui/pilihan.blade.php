@props(['galat' => false, 'kosong' => null, 'opsi' => []])

@php
    $garis = $galat
        ? 'border-red-300 focus:border-red-500 focus:ring-red-200'
        : 'border-gray-300 focus:border-primary-500 focus:ring-primary-100';
@endphp

<select @if ($galat) aria-invalid="true" @endif
        {{ $attributes->merge([
            'class' => 'block w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm
                        transition focus:outline-none focus:ring-4 disabled:bg-gray-50 '.$garis,
        ]) }}>
    @if ($kosong !== null)
        <option value="">{{ $kosong }}</option>
    @endif

    @foreach ($opsi as $nilai => $teks)
        <option value="{{ $nilai }}">{{ $teks }}</option>
    @endforeach

    {{ $slot }}
</select>
