<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Livewire
|--------------------------------------------------------------------------
|
| Hanya nilai yang benar-benar berbeda dari bawaan paket yang ditulis di
| sini. Sisanya tetap mengikuti config bawaan Livewire lewat penggabungan
| konfigurasi, sehingga berkas ini tidak perlu ikut diperbarui setiap
| kali paketnya menambah pilihan baru.
|
*/

return [

    /*
    | Layout bawaan komponen halaman penuh.
    |
    | Livewire 4 menunjuk `layouts::app`, sebuah namespace view yang tidak
    | terdaftar di aplikasi ini. Layout panel Resikita hidup sebagai
    | komponen Blade anonim di resources/views/components/layouts/app.blade.php,
    | sehingga bisa dipakai lewat <x-layouts.app> di Blade biasa maupun
    | sebagai layout Livewire, satu berkas, dua cara pakai.
    */
    'component_layout' => 'components.layouts.app',

];
