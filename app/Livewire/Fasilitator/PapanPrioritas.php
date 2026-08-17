<?php

declare(strict_types=1);

namespace App\Livewire\Fasilitator;

use App\Enums\TingkatWilayah;
use App\Models\Wilayah;
use App\Services\Laporan\TindakLanjutService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Papan prioritas perluasan wilayah.
 *
 * Urutannya menjawab satu pertanyaan: daerah mana yang paling layak
 * didekati lebih dulu. Skor prioritas naik setiap kali ada laporan warga
 * dari wilayah yang belum bergabung, sehingga urutan ini mencerminkan
 * tekanan nyata dari bawah, bukan tebakan tentang daerah mana yang
 * "kelihatannya butuh".
 *
 * Itulah yang membuat percakapan dengan pemerintah daerah berbeda:
 * bukan menawarkan sistem, melainkan menunjukkan bahwa warganya sudah
 * memakainya.
 */
#[Title('Papan Prioritas Perluasan')]
class PapanPrioritas extends Component
{
    #[Url(as: 'tingkat', except: '')]
    public string $tingkat = '';

    public ?int $wilayahDipilih = null;

    public function updatedTingkat(): void
    {
        $this->wilayahDipilih = null;
    }

    public function lihatRingkasan(int $wilayahId): void
    {
        $this->wilayahDipilih = $this->wilayahDipilih === $wilayahId ? null : $wilayahId;
    }

    public function render(TindakLanjutService $tindakLanjut)
    {
        $wilayah = $this->wilayahDipilih !== null
            ? Wilayah::find($this->wilayahDipilih)
            : null;

        return view('livewire.fasilitator.papan-prioritas', [
            'daftar' => $tindakLanjut->papanPrioritas($this->tingkat ?: null),
            'tingkatTersedia' => TingkatWilayah::options(),
            'wilayahTerpilih' => $wilayah,
            'ringkasan' => $wilayah !== null ? $tindakLanjut->ringkasanWilayah($wilayah) : null,
        ]);
    }
}
