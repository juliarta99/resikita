<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StatusUmkm;
use App\Models\Umkm;
use App\Models\UmkmDompet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Umkm>
 */
class UmkmFactory extends Factory
{
    protected $model = Umkm::class;

    public function definition(): array
    {
        return [
            'nama' => fake()->company(),
            'deskripsi' => fake()->sentence(12),
            'alamat' => fake()->address(),
            'phone' => fake()->numerify('08##########'),
            'email' => fake()->unique()->companyEmail(),

            // Toko baku sudah siap mengirim. Toko tanpa asal pengiriman
            // adalah keadaan khusus yang diuji lewat state di bawah, bukan
            // keadaan bawaan, kalau dibalik, hampir setiap uji marketplace
            // harus mengisi kolom ini lebih dulu.
            'destination_id' => fake()->numberBetween(10_000, 20_000),
            'alamat_asal' => fake()->city().', '.fake()->state(),

            'status' => StatusUmkm::Aktif,
            'is_verified' => true,
        ];
    }

    /** Toko yang belum menetapkan titik asal pengiriman. */
    public function tanpaAsalPengiriman(): static
    {
        return $this->state(fn (): array => [
            'destination_id' => null,
            'alamat_asal' => null,
        ]);
    }

    public function menunggu(): static
    {
        return $this->state(fn (): array => [
            'status' => StatusUmkm::Menunggu,
            'is_verified' => false,
        ]);
    }

    /** Sertakan dompet, seperti UMKM yang lahir lewat AkunService. */
    public function denganDompet(int $saldo = 0): static
    {
        return $this->afterCreating(function (Umkm $umkm) use ($saldo): void {
            UmkmDompet::create(['umkm_id' => $umkm->id, 'saldo' => $saldo]);
        });
    }
}
