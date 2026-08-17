<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Domain Resikita
|--------------------------------------------------------------------------
|
| Satu-satunya tempat `env()` boleh dipanggil untuk parameter domain.
| Service membaca nilai dari sini lewat config(), tidak pernah dari env().
| Lihat CLAUDE.md 13, "env() di luar file config" adalah pelanggaran.
|
*/

return [

    /*
    | Laporan
    |
    | radius_duplikat_m, jarak Haversine untuk mencari kandidat laporan
    | kembar sebelum penyimpanan. Kandidat ditawarkan untuk digabung,
    | tidak pernah dipakai untuk menolak laporan (CLAUDE.md 9.3).
    |
    | prefix_tiket, bagian tetap nomor tiket, format akhir RSK-YYYYMM-XXXXX.
    */
    'laporan' => [
        'radius_duplikat_m' => (int) env('REPORT_DUPLICATE_RADIUS_M', 50),
        'prefix_tiket' => 'RSK',
        'status_aktif' => ['baru', 'diverifikasi', 'ditugaskan', 'dikerjakan'],
    ],

    /*
    | Wilayah
    |
    | radius_resolusi_km, batas pencarian wilayah terdekat saat titik
    | koordinat tidak jatuh di dalam wilayah mana pun yang terdaftar.
    |
    | bobot_skor_prioritas, kenaikan wilayah.skor_prioritas setiap kali
    | ada laporan dari wilayah yang belum terjangkau (CLAUDE.md 9.4).
    */
    'wilayah' => [
        'radius_resolusi_km' => 25,
        'bobot_skor_prioritas' => 1,
    ],

    /*
    | Artikel
    |
    | kata_per_menit, dasar perhitungan artikel.estimasi_baca_menit.
    | 200 kpm adalah angka lazim untuk teks non-teknis bahasa Indonesia.
    */
    'artikel' => [
        'kata_per_menit' => 200,
    ],

    /*
    | Uang
    |
    | Seluruh nilai uang disimpan sebagai integer rupiah (CLAUDE.md 11).
    | Batas di bawah ini juga dalam rupiah penuh.
    */
    'dompet' => [
        'penarikan_minimum' => 10_000,
        'penarikan_maksimum' => 10_000_000,
    ],
];
