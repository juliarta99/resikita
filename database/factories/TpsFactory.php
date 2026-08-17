<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\JenisTps;
use App\Models\Tps;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tps>
 */
class TpsFactory extends Factory
{
    protected $model = Tps::class;

    public function definition(): array
    {
        return [
            'nama' => 'TPS '.fake()->unique()->city(),
            'alamat' => fake()->address(),
            'latitude' => fake()->latitude(-11, 6),
            'longitude' => fake()->longitude(95, 141),
            'phone' => fake()->numerify('08##########'),
            'jenis' => JenisTps::Tps,
            'is_berbayar' => false,
            'tarif_bulanan' => null,
            'kapasitas_ton' => fake()->randomFloat(2, 2, 30),
        ];
    }

    public function tps3r(): static
    {
        return $this->state(fn (): array => ['jenis' => JenisTps::Tps3r]);
    }

    /** TPS yang memungut iuran. Tarif dalam rupiah penuh sebagai integer. */
    public function berbayar(int $tarifBulanan = 25_000): static
    {
        return $this->state(fn (): array => [
            'is_berbayar' => true,
            'tarif_bulanan' => $tarifBulanan,
        ]);
    }
}
