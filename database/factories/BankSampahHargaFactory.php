<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\KategoriSampah;
use App\Models\BankSampah;
use App\Models\BankSampahHarga;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankSampahHarga>
 */
class BankSampahHargaFactory extends Factory
{
    protected $model = BankSampahHarga::class;

    public function definition(): array
    {
        return [
            'bank_sampah_id' => BankSampah::factory(),
            'jenis_sampah' => fake()->unique()->randomElement([
                'Botol PET bening', 'Kardus', 'Kertas HVS', 'Kaleng aluminium',
                'Plastik HDPE', 'Besi', 'Beling', 'Minyak jelantah',
            ]).' '.fake()->unique()->numberBetween(1, 9999),
            'kategori' => KategoriSampah::Anorganik,
            'satuan' => 'kg',
            // Rupiah penuh sebagai integer.
            'harga_per_satuan' => fake()->numberBetween(500, 8000),
            'is_active' => true,
        ];
    }

    public function harga(int $rupiahPerKg): static
    {
        return $this->state(fn (): array => ['harga_per_satuan' => $rupiahPerKg]);
    }

    public function nonaktif(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
