<?php

declare(strict_types=1);

namespace App\Services\Wilayah;

use App\Enums\TingkatWilayah;
use App\Models\Wilayah;
use App\Services\Wilayah\Data\HasilResolusiWilayah;
use App\Support\Haversine;
use Illuminate\Database\Eloquent\Builder;

/**
 * Mengubah sepasang koordinat menjadi empat id wilayah.
 *
 * ## Cara kerja dan batasnya
 *
 * Resikita menyimpan titik pusat wilayah, bukan poligon batasnya.
 * Karena itu resolusi dilakukan dengan mencari simpul terdekat, bukan
 * dengan uji titik-di-dalam-poligon. Konsekuensinya jujur untuk
 * dinyatakan: di dekat batas dua desa, hasilnya bisa menunjuk desa
 * tetangga.
 *
 * Yang membuat ini tetap memadai adalah struktur keputusan di atasnya.
 * Penanggung jawab laporan ditentukan pada tingkat kabupaten lebih
 * dulu (CLAUDE.md 9.2), dan dua desa bertetangga hampir selalu berada
 * di kabupaten yang sama, sehingga ketidaktepatan di tingkat desa
 * jarang mengubah siapa yang menangani laporan.
 *
 * Kalau kelak tersedia data poligon, hanya kelas ini yang perlu
 * diganti; pemanggilnya tidak berubah karena tetap menerima
 * HasilResolusiWilayah yang sama.
 */
class WilayahResolverService
{
    /**
     * Selesaikan koordinat menjadi desa, kecamatan, kabupaten, provinsi.
     *
     * Pencarian menuruni tingkat: desa dulu karena paling spesifik.
     * Kalau tidak ada desa terdaftar dalam radius, lazim terjadi di
     * wilayah yang belum terjangkau, di mana baru provinsi dan kabupaten
     * yang disemai, pencarian mundur ke tingkat yang lebih luas supaya
     * laporan tetap punya konteks wilayah, bukan kosong sama sekali.
     */
    public function resolve(float $latitude, float $longitude): HasilResolusiWilayah
    {
        $radiusKm = (float) config('resikita.wilayah.radius_resolusi_km', 25);

        foreach ([TingkatWilayah::Desa, TingkatWilayah::Kecamatan, TingkatWilayah::Kabupaten, TingkatWilayah::Provinsi] as $tingkat) {
            $simpul = $this->terdekatPadaTingkat($latitude, $longitude, $tingkat, $radiusKm);

            if ($simpul !== null) {
                return HasilResolusiWilayah::dariSimpul(
                    $simpul,
                    isset($simpul->jarak_km) ? round((float) $simpul->jarak_km, 3) : null,
                );
            }
        }

        return HasilResolusiWilayah::kosong();
    }

    /**
     * Simpul terdekat pada satu tingkat, beserta rantai induknya.
     *
     * Induk dimuat sekaligus lewat eager loading berantai agar penelusuran
     * ke atas di HasilResolusiWilayah tidak memicu satu query per tingkat.
     */
    private function terdekatPadaTingkat(
        float $latitude,
        float $longitude,
        TingkatWilayah $tingkat,
        float $radiusKm,
    ): ?Wilayah {
        $query = Wilayah::query()
            ->where('tingkat', $tingkat)
            ->with('parent.parent.parent');

        Haversine::terapkan($query, $latitude, $longitude, $radiusKm);

        return $query->first();
    }

    /**
     * Resolusi memakai wilayah yang sudah diketahui, tanpa menyentuh
     * koordinat. Dipakai ketika pengguna memilih wilayah dari daftar,
     * mis. saat mendaftarkan bank sampah, sehingga pilihan eksplisit
     * pengguna tidak ditimpa tebakan berbasis jarak.
     */
    public function dariWilayah(Wilayah $wilayah): HasilResolusiWilayah
    {
        $wilayah->loadMissing('parent.parent.parent');

        return HasilResolusiWilayah::dariSimpul($wilayah);
    }

    /**
     * Seluruh keturunan sebuah wilayah, dicari lewat awalan kode
     * Kemendagri. Satu query berbasis index, bukan penelusuran rekursif.
     *
     * @return Builder<Wilayah>
     */
    public function keturunan(Wilayah $wilayah, ?TingkatWilayah $hanyaTingkat = null)
    {
        $query = Wilayah::query()->where('kode', 'like', $wilayah->kode.'.%');

        if ($hanyaTingkat !== null) {
            $query->where('tingkat', $hanyaTingkat);
        }

        return $query;
    }

    /**
     * Rantai wilayah dari provinsi sampai simpul ini, untuk remah
     * navigasi. Urutan dari yang terluas ke yang tersempit.
     *
     * @return array<int, Wilayah>
     */
    public function rantaiInduk(Wilayah $wilayah): array
    {
        $wilayah->loadMissing('parent.parent.parent');

        $rantai = [];
        $sekarang = $wilayah;

        while ($sekarang !== null) {
            array_unshift($rantai, $sekarang);
            $sekarang = $sekarang->parent;
        }

        return $rantai;
    }
}
