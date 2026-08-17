<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * Kategori pemilahan sampah (CLAUDE.md 5.4).
 *
 * Di skema lama kolom ini varchar bebas, sehingga model AI boleh
 * mengarang kategori apa pun dan tidak ada yang bisa dihitung.
 * Sekarang tertutup lima nilai. Tiga di antaranya, b3, residu,
 * elektronik, adalah bukti di tingkat skema bahwa Resikita
 * memilah lebih dalam daripada sekadar organik/anorganik.
 *
 * Keluaran Gemini divalidasi terhadap enum ini; nilai di luar daftar
 * ditolak, tidak disimpan apa adanya.
 */
enum KategoriSampah: string implements HasLabel
{
    use ProvidesOptions;

    case Organik = 'organik';
    case Anorganik = 'anorganik';
    case B3 = 'b3';
    case Residu = 'residu';
    case Elektronik = 'elektronik';

    public function label(): string
    {
        return match ($this) {
            self::Organik => 'Organik',
            self::Anorganik => 'Anorganik',
            self::B3 => 'Limbah B3 rumah tangga',
            self::Residu => 'Residu',
            self::Elektronik => 'Sampah elektronik',
        };
    }

    public function deskripsi(): string
    {
        return match ($this) {
            self::Organik => 'Sisa makanan, daun, dan bahan yang bisa terurai. Cocok dikompos.',
            self::Anorganik => 'Plastik, kertas, logam, dan kaca yang masih bernilai jual di bank sampah.',
            self::B3 => 'Baterai, lampu, obat kedaluwarsa, kemasan pestisida. Butuh penanganan khusus, jangan dicampur.',
            self::Residu => 'Sisa yang tidak bisa didaur ulang maupun dikompos. Berakhir di TPA.',
            self::Elektronik => 'Perangkat elektronik bekas. Kandungan logam berharga tinggi, tapi juga mengandung bahan berbahaya.',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::Organik => 'green',
            self::Anorganik => 'blue',
            self::B3 => 'red',
            self::Residu => 'gray',
            self::Elektronik => 'purple',
        };
    }

    /** Kategori yang lazim diterima bank sampah dan punya nilai jual. */
    public function bernilaiEkonomi(): bool
    {
        return match ($this) {
            self::Anorganik, self::Elektronik => true,
            default => false,
        };
    }

    /**
     * Kategori yang tidak boleh masuk aliran daur ulang biasa dan
     * harus diarahkan ke fasilitas penanganan khusus.
     */
    public function butuhPenangananKhusus(): bool
    {
        return match ($this) {
            self::B3, self::Elektronik => true,
            default => false,
        };
    }
}
