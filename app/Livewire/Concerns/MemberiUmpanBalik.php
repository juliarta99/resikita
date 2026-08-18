<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Exceptions\AturanBisnisException;

/**
 * Umpan balik seragam untuk seluruh komponen Livewire.
 *
 * ## Kenapa `jalankan()` ada
 *
 * Validasi bisnis hidup di Service dan dilemparkan sebagai
 * AturanBisnisException, saldo tidak cukup, status tidak boleh
 * berpindah, wilayah tanpa penanggung jawab. Di kanal API, bootstrap
 * mengubahnya menjadi response 422 yang rapi. Di kanal web tidak ada
 * yang menangkapnya, sehingga penolakan yang sepenuhnya wajar muncul di
 * layar pengguna sebagai halaman galat 500.
 *
 * `jalankan()` menutup celah itu: pesan yang sama persis yang diterima
 * aplikasi mobile ditampilkan sebagai pemberitahuan di web. Satu aturan,
 * dua kanal, satu kalimat penolakan.
 *
 * Trait ini tidak boleh berkembang menjadi tempat menaruh logika. Ia
 * hanya menyampaikan hasil.
 */
trait MemberiUmpanBalik
{
    protected function pesanSukses(string $pesan): void
    {
        $this->dispatch('pesan', pesan: $pesan);
    }

    protected function pesanGalat(string $pesan): void
    {
        $this->dispatch('pesan', pesan: $pesan, jenis: 'galat');
    }

    /**
     * Jalankan sebuah tindakan Service dan tampilkan hasilnya.
     *
     * Mengembalikan `null` ketika tindakan ditolak aturan bisnis,
     * sehingga pemanggil bisa membedakan berhasil dari gagal tanpa
     * menangkap exception sendiri.
     *
     * @template T
     *
     * @param  callable(): T  $tindakan
     * @return T|null
     */
    protected function jalankan(callable $tindakan, ?string $pesanSukses = null): mixed
    {
        try {
            $hasil = $tindakan();
        } catch (AturanBisnisException $e) {
            $this->pesanGalat($e->getMessage());

            return null;
        }

        if ($pesanSukses !== null) {
            $this->pesanSukses($pesanSukses);
        }

        return $hasil;
    }
}
