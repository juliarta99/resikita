<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/** Daur hidup satu laporan sampah, dari masuk sampai tuntas. */
enum StatusLaporan: string implements HasLabel
{
    use ProvidesOptions;

    case Baru = 'baru';
    case Diverifikasi = 'diverifikasi';
    case Ditugaskan = 'ditugaskan';
    case Dikerjakan = 'dikerjakan';
    case Selesai = 'selesai';
    case Ditolak = 'ditolak';
    case Digabung = 'digabung';

    public function label(): string
    {
        return match ($this) {
            self::Baru => 'Baru masuk',
            self::Diverifikasi => 'Terverifikasi',
            self::Ditugaskan => 'Ditugaskan',
            self::Dikerjakan => 'Sedang dikerjakan',
            self::Selesai => 'Selesai',
            self::Ditolak => 'Ditolak',
            self::Digabung => 'Digabung ke laporan lain',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::Baru => 'blue',
            self::Diverifikasi => 'cyan',
            self::Ditugaskan => 'amber',
            self::Dikerjakan => 'orange',
            self::Selesai => 'green',
            self::Ditolak => 'red',
            self::Digabung => 'gray',
        };
    }

    /**
     * Laporan yang masih berjalan. Hanya status ini yang ikut
     * dicek saat mencari kandidat duplikat (CLAUDE.md 9.3),
     * laporan yang sudah selesai tidak menghalangi laporan baru
     * di titik yang sama.
     */
    public function isAktif(): bool
    {
        return match ($this) {
            self::Baru, self::Diverifikasi, self::Ditugaskan, self::Dikerjakan => true,
            default => false,
        };
    }

    /** Status akhir yang tidak bisa berpindah lagi. */
    public function isFinal(): bool
    {
        return match ($this) {
            self::Selesai, self::Ditolak, self::Digabung => true,
            default => false,
        };
    }

    /** @return array<int, string> Nilai status aktif, untuk klausa whereIn. */
    public static function aktif(): array
    {
        return array_values(array_map(
            static fn (self $case): string => $case->value,
            array_filter(self::cases(), static fn (self $case): bool => $case->isAktif()),
        ));
    }

    /** Perpindahan status yang diizinkan; dipakai Service saat mengubah status. */
    public function bolehPindahKe(self $tujuan): bool
    {
        return in_array($tujuan, $this->transisiSah(), strict: true);
    }

    /** @return array<int, self> */
    public function transisiSah(): array
    {
        return match ($this) {
            self::Baru => [self::Diverifikasi, self::Ditolak, self::Digabung],
            self::Diverifikasi => [self::Ditugaskan, self::Ditolak, self::Digabung],
            self::Ditugaskan => [self::Dikerjakan, self::Diverifikasi],
            self::Dikerjakan => [self::Selesai, self::Ditugaskan],
            self::Selesai, self::Ditolak, self::Digabung => [],
        };
    }
}
