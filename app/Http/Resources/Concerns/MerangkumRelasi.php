<?php

declare(strict_types=1);

namespace App\Http\Resources\Concerns;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

/**
 * Pembungkus relasi yang boleh kosong.
 *
 * `whenLoaded('parent')` mengembalikan MissingValue ketika relasi belum
 * dimuat, tapi mengembalikan **null** ketika relasi sudah dimuat dan
 * memang tidak ada isinya, provinsi tanpa induk, laporan yang wilayah
 * desanya belum bisa ditentukan, bank sampah yang wilayahnya kosong.
 *
 * Membungkus null itu dengan `new Resource(null)` akan meledak di
 * `toArray()` saat resource mencoba membaca properti dari null. Bug
 * semacam ini tidak muncul di uji dengan data lengkap dan baru terlihat
 * di produksi, tepat pada baris data yang paling tidak lengkap.
 *
 * Helper ini menyatukan ketiga keadaan: belum dimuat, dimuat tapi
 * kosong, dan dimuat berisi.
 */
trait MerangkumRelasi
{
    /**
     * Bungkus sebuah relasi dengan API Resource, aman terhadap null.
     *
     * @param  class-string<JsonResource>  $resourceClass
     */
    protected function relasi(string $nama, string $resourceClass): JsonResource|MissingValue|null
    {
        if (! $this->resource->relationLoaded($nama)) {
            return new MissingValue;
        }

        $isi = $this->resource->getRelation($nama);

        return $isi === null ? null : new $resourceClass($isi);
    }

    /**
     * Ambil satu nilai dari relasi yang boleh kosong, mis. namanya saja.
     */
    protected function nilaiRelasi(string $nama, callable $ambil): mixed
    {
        if (! $this->resource->relationLoaded($nama)) {
            return new MissingValue;
        }

        $isi = $this->resource->getRelation($nama);

        return $isi === null ? null : $ambil($isi);
    }
}
