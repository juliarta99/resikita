<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LaporanKategori;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LaporanKategori>
 */
class LaporanKategoriFactory extends Factory
{
    protected $model = LaporanKategori::class;

    public function definition(): array
    {
        return [
            'nama' => fake()->randomElement([
                'Tumpukan sampah liar',
                'Pembakaran sampah',
                'TPS meluber',
                'Sampah di saluran air',
                'Limbah B3 dibuang sembarangan',
            ]),
            'deskripsi' => fake()->sentence(),
            'ikon' => null,
            'is_active' => true,
        ];
    }
}
