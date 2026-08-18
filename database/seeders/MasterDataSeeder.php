<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ArtikelKategori;
use App\Models\LaporanKategori;
use App\Models\ProdukKategori;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Kategori dasar yang harus ada sebelum sistem bisa dipakai sama sekali.
 *
 * Kategori laporan yang paling penting: tanpa satu pun baris di sini,
 * warga tidak bisa mengirim laporan pertamanya, dan aplikasi terlihat
 * rusak pada langkah paling awal.
 *
 * Isinya sengaja tidak menyebut daerah mana pun. Kategori yang ditulis
 * untuk satu kabupaten akan terasa asing di kabupaten berikutnya.
 */
class MasterDataSeeder extends Seeder
{
    /** @var array<int, array{0: string, 1: string, 2: string}> */
    private const KATEGORI_LAPORAN = [
        ['Tumpukan sampah liar', 'Sampah menumpuk di lahan kosong, bahu jalan, atau saluran air.', 'sampah'],
        ['Pembakaran sampah', 'Sampah dibakar terbuka sehingga asapnya mengganggu warga sekitar.', 'api'],
        ['TPS penuh atau tidak terangkut', 'Tempat penampungan sementara melebihi kapasitas atau terlambat diangkut.', 'kotak'],
        ['Sampah di sungai atau laut', 'Sampah mengapung atau menyangkut di badan air.', 'air'],
        ['Limbah B3 rumah tangga', 'Baterai, lampu, obat kedaluwarsa, atau kemasan pestisida dibuang sembarangan.', 'peringatan'],
        ['Sampah elektronik', 'Perangkat elektronik bekas dibuang bersama sampah biasa.', 'perangkat'],
        ['Saluran air tersumbat sampah', 'Got atau drainase mampet karena sampah dan berpotensi menyebabkan genangan.', 'air'],
        ['Fasilitas kebersihan rusak', 'Tempat sampah umum, gerobak, atau bangunan TPS dalam kondisi rusak.', 'gear'],
    ];

    /** @var array<int, string> */
    private const KATEGORI_ARTIKEL = [
        'Pemilahan',
        'Kompos',
        'Bank Sampah',
        'Daur Ulang',
        'Limbah B3',
        'Regulasi',
        'Ekonomi Sirkular',
    ];

    /** @var array<int, array{0: string, 1: string}> */
    private const KATEGORI_PRODUK = [
        ['Tas dan Dompet', 'kantong'],
        ['Perabot Rumah', 'rumah'],
        ['Dekorasi', 'bintang'],
        ['Pot dan Berkebun', 'daun'],
        ['Kerajinan', 'gunting'],
        ['Kompos dan Pupuk', 'daun'],
    ];

    public function run(): void
    {
        foreach (self::KATEGORI_LAPORAN as [$nama, $deskripsi, $ikon]) {
            LaporanKategori::updateOrCreate(
                ['nama' => $nama],
                ['deskripsi' => $deskripsi, 'ikon' => $ikon, 'is_active' => true],
            );
        }

        foreach (self::KATEGORI_ARTIKEL as $nama) {
            ArtikelKategori::updateOrCreate(
                ['slug' => Str::slug($nama)],
                ['nama' => $nama],
            );
        }

        foreach (self::KATEGORI_PRODUK as [$nama, $ikon]) {
            ProdukKategori::updateOrCreate(
                ['slug' => Str::slug($nama)],
                ['nama' => $nama, 'ikon' => $ikon],
            );
        }
    }
}
