<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Produk;
use App\Models\ProdukKategori;
use App\Models\Umkm;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Produk>
 */
class ProdukFactory extends Factory
{
    protected $model = Produk::class;

    public function definition(): array
    {
        $nama = fake()->words(3, true);

        return [
            'umkm_id' => Umkm::factory(),
            'kategori_id' => ProdukKategori::factory(),
            'nama' => Str::title($nama),
            'slug' => Str::slug($nama).'-'.fake()->unique()->numberBetween(1, 999999),
            'deskripsi' => fake()->paragraph(),
            // Rupiah penuh sebagai integer.
            'harga' => fake()->numberBetween(15, 500) * 1000,
            'stok' => fake()->numberBetween(5, 50),
            'berat_gram' => fake()->numberBetween(200, 2000),
            'bahan_baku' => fake()->randomElement([
                'Plastik PET daur ulang', 'Kemasan sachet bekas', 'Kertas bekas', 'Kain perca',
            ]),
            'is_active' => true,
        ];
    }

    public function stok(int $jumlah): static
    {
        return $this->state(fn (): array => ['stok' => $jumlah]);
    }

    public function harga(int $rupiah): static
    {
        return $this->state(fn (): array => ['harga' => $rupiah]);
    }

    public function nonaktif(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
