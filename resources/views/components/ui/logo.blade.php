@props(['ukuran' => 'sedang', 'varian' => 'hijau', 'alt' => ''])

{{--
    Tanda gambar Resikita.

    Satu berkas untuk seluruh layout supaya ukurannya tidak berbeda
    antara panel, halaman publik, dan halaman masuk.

    Dua varian berkas, dipilih menurut latar induknya. Daunnya tidak
    diberi ubin berwarna: logo dipasang langsung di atas latar yang ada,
    jadi kontrasnya datang dari memilih berkas yang benar.

        varian="hijau"  logo-primary.png, untuk latar putih atau terang
        varian="putih"  logo.png, untuk latar hijau atau gelap

    Bawaannya dekoratif (alt kosong): di setiap pemakaian saat ini nama
    Resikita sudah tampil sebagai teks di sebelahnya, dan membacakan
    keduanya hanya menggandakan bunyi bagi pembaca layar. Kalau logo
    berdiri sendiri tanpa teks pendamping, isi prop alt.
--}}
@php
    $bentuk = match ($ukuran) {
        'kecil' => 'h-8 w-8',
        'besar' => 'h-11 w-11',
        default => 'h-9 w-9',
    };

    $berkas = $varian === 'putih' ? 'images/logo.png' : 'images/logo-primary.png';
@endphp

<img src="{{ asset($berkas) }}"
     alt="{{ $alt }}"
     @if ($alt === '') aria-hidden="true" @endif
     width="270" height="270"
     {{ $attributes->merge(['class' => 'flex-none object-contain '.$bentuk]) }}>
