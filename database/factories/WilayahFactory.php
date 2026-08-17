<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StatusRegistrasiWilayah;
use App\Enums\TingkatWilayah;
use App\Models\Wilayah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wilayah>
 */
class WilayahFactory extends Factory
{
    protected $model = Wilayah::class;

    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->numerify('##'),
            'nama' => fake()->city(),
            'tingkat' => TingkatWilayah::Provinsi,
            'parent_id' => null,
            'latitude' => fake()->latitude(-11, 6),
            'longitude' => fake()->longitude(95, 141),
            'status_registrasi' => StatusRegistrasiWilayah::BelumTerjangkau,
            'skor_prioritas' => 0,
        ];
    }

    public function terverifikasi(): static
    {
        return $this->state(fn (): array => [
            'status_registrasi' => StatusRegistrasiWilayah::Terverifikasi,
            'terverifikasi_at' => now(),
        ]);
    }

    /**
     * Jadikan wilayah ini anak dari simpul lain.
     *
     * Kode anak dibentuk dari kode induk plus segmen baru, mengikuti
     * pola Kemendagri, supaya pencarian keturunan berbasis awalan kode
     * di WilayahScopeService ikut teruji.
     */
    public function anakDari(Wilayah $induk, ?string $segmen = null): static
    {
        $tingkatAnak = $induk->tingkat->child();

        $segmen ??= $tingkatAnak === TingkatWilayah::Desa
            ? fake()->unique()->numerify('####')
            : fake()->unique()->numerify('##');

        return $this->state(fn (): array => [
            'parent_id' => $induk->id,
            'tingkat' => $tingkatAnak,
            'kode' => $induk->kode.'.'.$segmen,
            'latitude' => $induk->latitude,
            'longitude' => $induk->longitude,
        ]);
    }

    /** Tempatkan wilayah pada koordinat tertentu. */
    public function diTitik(float $latitude, float $longitude): static
    {
        return $this->state(fn (): array => [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }
}
