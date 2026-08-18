<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

/**
 * Pembantu umum untuk enum berbasis nilai string.
 *
 * Dipakai oleh Form Request (aturan Rule::enum), komponen Livewire
 * (isi dropdown), dan API Resource (pasangan nilai + label).
 */
trait ProvidesOptions
{
    /** @return array<int, string> Daftar nilai mentah, untuk validasi dan definisi kolom enum. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return array<string, string> Peta nilai => label, untuk dropdown dan filter. */
    public static function options(): array
    {
        $out = [];

        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }

    /** @return array<int, array{value: string, label: string}> Bentuk daftar untuk response API. */
    public static function toArray(): array
    {
        return array_map(
            static fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }

    /**
     * Versi longgar dari tryFrom yang menerima variasi huruf besar/kecil
     * dan spasi. Dipakai khusus untuk menormalkan keluaran model AI,
     * lihat CLAUDE.md 10.1: skema boleh dilanggar, jadi selalu divalidasi
     * ulang di PHP sebelum disimpan.
     */
    public static function tryNormalize(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        $needle = str_replace([' ', '-'], '_', mb_strtolower(trim($value)));

        foreach (self::cases() as $case) {
            if ($case->value === $needle) {
                return $case;
            }
        }

        return null;
    }
}
