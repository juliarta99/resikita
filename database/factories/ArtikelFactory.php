<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StatusArtikel;
use App\Enums\TipeArtikel;
use App\Models\Artikel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Artikel>
 */
class ArtikelFactory extends Factory
{
    protected $model = Artikel::class;

    public function definition(): array
    {
        $judul = Str::title(fake()->words(5, true));

        return [
            'judul' => $judul,
            'slug' => Str::slug($judul).'-'.fake()->unique()->numberBetween(1, 999999),
            'tipe' => TipeArtikel::Artikel,
            'konten' => fake()->paragraphs(4, true),
            'status' => StatusArtikel::Draft,
            'dilihat' => 0,
            'didengarkan' => 0,
            'is_unggulan' => false,
        ];
    }

    public function terbit(): static
    {
        return $this->state(fn (): array => [
            'status' => StatusArtikel::Published,
            'published_at' => now()->subDay(),
        ]);
    }
}
