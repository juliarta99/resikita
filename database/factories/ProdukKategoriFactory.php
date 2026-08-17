<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProdukKategori;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProdukKategori>
 */
class ProdukKategoriFactory extends Factory
{
    protected $model = ProdukKategori::class;

    public function definition(): array
    {
        $nama = fake()->unique()->randomElement([
            'Kerajinan Plastik', 'Tas Daur Ulang', 'Kompos', 'Ecobrick', 'Furnitur Daur Ulang',
        ]).' '.fake()->unique()->numberBetween(1, 9999);

        return [
            'nama' => $nama,
            'slug' => Str::slug($nama),
            'ikon' => null,
        ];
    }
}
