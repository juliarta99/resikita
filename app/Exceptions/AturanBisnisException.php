<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Pelanggaran aturan bisnis yang dideteksi di Service.
 *
 * Berbeda dari galat validasi bentuk: validasi bentuk memeriksa apakah
 * masukan berupa data yang benar (Form Request atau rules() Livewire),
 * sedangkan ini memeriksa apakah tindakannya masuk akal menurut keadaan
 * sistem, saldo cukup, status boleh berpindah, wilayah punya
 * penanggung jawab.
 *
 * Pemeriksaan ini hidup di Service, bukan di Controller atau komponen
 * Livewire, supaya jalur web dan jalur mobile ditolak dengan alasan yang
 * sama persis.
 */
class AturanBisnisException extends RuntimeException
{
    /** Kode status HTTP yang pantas untuk galat ini. */
    public int $statusHttp = 422;

    public static function karena(string $pesan, int $statusHttp = 422): self
    {
        $e = new self($pesan);
        $e->statusHttp = $statusHttp;

        return $e;
    }

    /** Tindakan yang tidak berwenang dilakukan pengguna. */
    public static function tidakBerwenang(string $pesan = 'Anda tidak berwenang melakukan tindakan ini.'): self
    {
        return self::karena($pesan, 403);
    }
}
