@props(['gaya'])

{{--
    Rangka kecil yang menunjukkan bentuk sebuah tata letak sampul.

    Digambar dengan bidang polos, bukan gambar contoh: yang perlu dikenali
    penjual dari daftar ini adalah ke mana fotonya pergi dan di mana
    teksnya berdiri. Gambar contoh justru menutupi perbedaan itu, karena
    yang lebih dulu tertangkap mata adalah isi fotonya.

    Bentuknya sengaja tidak mengikuti rasio yang sedang dipilih. Tata
    letak dan bentuk kanvas adalah dua pilihan terpisah, dan mencampurnya
    di satu pratinjau kecil membuat keduanya terlihat saling terikat.
--}}
@php
    $foto = 'absolute bg-gray-300';
@endphp

<span aria-hidden="true"
      class="relative block aspect-square w-full overflow-hidden rounded-lg bg-gray-200">
    @switch ($gaya)
        @case ('tirai_bawah')
            <span class="{{ $foto }} inset-0"></span>
            <span class="absolute inset-x-0 bottom-0 h-2/5 bg-gradient-to-t from-primary-700 to-transparent"></span>
            <span class="absolute inset-x-1.5 bottom-2 h-1.5 rounded-sm bg-white/90"></span>
            @break

        @case ('kartu_mengambang')
            <span class="{{ $foto }} inset-0"></span>
            <span class="absolute inset-x-1.5 bottom-1.5 h-1/3 rounded bg-primary-500"></span>
            @break

        @case ('pita_samping')
            <span class="{{ $foto }} inset-y-0 right-0 w-3/5"></span>
            <span class="absolute inset-y-0 left-0 w-2/5 bg-primary-500"></span>
            <span class="absolute bottom-2 left-1 h-1 w-1/4 rounded-sm bg-white/90"></span>
            @break

        @case ('blok_atas')
            <span class="{{ $foto }} inset-x-0 bottom-0 h-2/3"></span>
            <span class="absolute inset-x-0 top-0 h-1/3 bg-primary-500"></span>
            <span class="absolute left-1.5 top-2 h-1 w-1/2 rounded-sm bg-white/90"></span>
            @break

        @case ('bingkai_penuh')
            <span class="absolute inset-0 bg-primary-500"></span>
            <span class="{{ $foto }} inset-x-1 top-1 h-3/5"></span>
            <span class="absolute bottom-2 left-1.5 h-1 w-1/2 rounded-sm bg-white/90"></span>
            @break

        @case ('sorot_tengah')
            <span class="{{ $foto }} inset-0"></span>
            <span class="absolute inset-0 bg-primary-900/60"></span>
            <span class="absolute left-1/2 top-1/2 h-1.5 w-2/3 -translate-x-1/2 -translate-y-1/2 rounded-sm bg-white/90"></span>
            @break
    @endswitch
</span>
