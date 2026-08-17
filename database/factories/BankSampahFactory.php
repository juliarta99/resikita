<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StatusAktif;
use App\Models\BankSampah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankSampah>
 */
class BankSampahFactory extends Factory
{
    protected $model = BankSampah::class;

    public function definition(): array
    {
        return [
            'nama' => 'Bank Sampah '.fake()->unique()->city(),
            'alamat' => fake()->address(),
            'latitude' => fake()->latitude(-11, 6),
            'longitude' => fake()->longitude(95, 141),
            'phone' => fake()->numerify('08##########'),
            'status' => StatusAktif::Aktif,
            'is_verified' => true,
        ];
    }
}
