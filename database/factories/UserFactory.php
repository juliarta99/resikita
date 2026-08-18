<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\JenisKelamin;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $passwordTerpakai = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$passwordTerpakai ??= Hash::make('password'),
            'phone' => fake()->optional()->numerify('08##########'),
            'tanggal_lahir' => fake()->optional()->dateTimeBetween('-60 years', '-17 years'),
            'jenis_kelamin' => fake()->optional()->randomElement(JenisKelamin::cases()),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function nonaktif(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function belumVerifikasiEmail(): static
    {
        return $this->state(fn (): array => ['email_verified_at' => null]);
    }

    /** Nasabah bank sampah punya kode QR berupa ULID acak, bukan NIK. */
    public function nasabah(): static
    {
        return $this->state(fn (): array => ['kode_qr' => (string) Str::ulid()]);
    }

    public function diWilayah(Wilayah $wilayah): static
    {
        return $this->state(fn (): array => [
            'wilayah_id' => $wilayah->id,
            'latitude' => $wilayah->latitude,
            'longitude' => $wilayah->longitude,
        ]);
    }

    /** Buat pengguna lalu langsung berikan rolenya. */
    public function withRole(RoleEnum $role): static
    {
        return $this->afterCreating(function (User $user) use ($role): void {
            $user->assignRole($role->value);
        });
    }
}
