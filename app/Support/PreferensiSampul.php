<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\GayaSampul;
use App\Enums\PaletSampul;
use App\Enums\RasioSampul;

/**
 * Pilihan penjual atas tampilan sampulnya.
 *
 * Disimpan sebagai satu kolom json di `konten_promosi.preferensi_sampul`
 * dan dibaca kembali lewat kelas ini. Alasannya bukan kemalasan skema:
 * kumpulan pilihan ini akan bertambah seiring gaya baru ditambahkan, dan
 * tiap penambahan tidak seharusnya menuntut migration pada tabel yang
 * sudah berisi draf pengguna.
 *
 * Yang dijaga di sini adalah pembacaannya. Nilai json dari basis data
 * bisa saja tertinggal versi lama, berisi gaya yang sudah dihapus, atau
 * disunting tangan. `dariArray()` karena itu selalu jatuh ke bawaan
 * ketika sebuah nilai tidak dikenali, bukan melempar galat, sampul yang
 * bentuknya melenceng jauh lebih baik daripada halaman yang gagal muat.
 */
final readonly class PreferensiSampul
{
    public function __construct(
        public GayaSampul $gaya,
        public PaletSampul $palet,
        public RasioSampul $rasio,
        public bool $tampilkanHarga,
        public bool $tampilkanBahan,
        public bool $tampilkanToko,
        /** Penggantian judul oleh penjual; null berarti pakai hasil pemisahan teks draf. */
        public ?string $judul = null,
        public ?string $pendukung = null,
    ) {}

    public static function bawaan(): self
    {
        return new self(
            gaya: GayaSampul::TiraiBawah,
            palet: PaletSampul::Hijau,
            rasio: RasioSampul::Persegi,
            tampilkanHarga: true,
            tampilkanBahan: true,
            tampilkanToko: false,
        );
    }

    /** @param  array<string, mixed>|null  $data */
    public static function dariArray(?array $data): self
    {
        $bawaan = self::bawaan();

        if ($data === null) {
            return $bawaan;
        }

        return new self(
            gaya: GayaSampul::tryFrom((string) ($data['gaya'] ?? '')) ?? $bawaan->gaya,
            palet: PaletSampul::tryFrom((string) ($data['palet'] ?? '')) ?? $bawaan->palet,
            rasio: RasioSampul::tryFrom((string) ($data['rasio'] ?? '')) ?? $bawaan->rasio,
            tampilkanHarga: (bool) ($data['tampilkan_harga'] ?? $bawaan->tampilkanHarga),
            tampilkanBahan: (bool) ($data['tampilkan_bahan'] ?? $bawaan->tampilkanBahan),
            tampilkanToko: (bool) ($data['tampilkan_toko'] ?? $bawaan->tampilkanToko),
            judul: self::teksAtauNull($data['judul'] ?? null),
            pendukung: self::teksAtauNull($data['pendukung'] ?? null),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'gaya' => $this->gaya->value,
            'palet' => $this->palet->value,
            'rasio' => $this->rasio->value,
            'tampilkan_harga' => $this->tampilkanHarga,
            'tampilkan_bahan' => $this->tampilkanBahan,
            'tampilkan_toko' => $this->tampilkanToko,
            'judul' => $this->judul,
            'pendukung' => $this->pendukung,
        ];
    }

    private static function teksAtauNull(mixed $nilai): ?string
    {
        if (! is_string($nilai)) {
            return null;
        }

        $teks = trim($nilai);

        return $teks === '' ? null : mb_substr($teks, 0, 200);
    }
}
