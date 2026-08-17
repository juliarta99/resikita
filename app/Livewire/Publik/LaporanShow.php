<?php

declare(strict_types=1);

namespace App\Livewire\Publik;

use App\Models\Laporan;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Pelacakan satu laporan oleh siapa saja yang memegang nomor tiketnya.
 *
 * ## Apa yang sengaja tidak ditampilkan
 *
 * Nama pelapor, koordinat tepat, dan nama petugas yang ditugaskan.
 * Ketiganya menjawab pertanyaan "siapa", sementara yang perlu dijawab
 * halaman ini adalah "sudah sampai mana". Menampilkan nama pelapor akan
 * mengubah pelaporan menjadi tindakan yang berisiko sosial di
 * lingkungan sendiri, dan orang berhenti melapor.
 *
 * Yang ditampilkan: status, waktu tiap perpindahan, foto bukti
 * pengerjaan, dan tingkat pemerintahan yang memegangnya. Itu cukup untuk
 * menagih penanganan tanpa menunjuk orang.
 */
#[Layout('components.layouts.publik')]
class LaporanShow extends Component
{
    public Laporan $laporan;

    public function mount(Laporan $laporan): void
    {
        $this->authorize('view', $laporan);

        $this->laporan = $laporan;
    }

    public function render()
    {
        $this->laporan->loadMissing([
            'kategori', 'foto', 'desa', 'kecamatan', 'kabupaten', 'provinsi',
            'progres' => fn ($q) => $q->orderBy('created_at'),
            'tindakLanjut' => fn ($q) => $q->orderBy('tanggal_kontak'),
        ]);

        return view('livewire.publik.laporan-show')->title('Laporan '.$this->laporan->tiket);
    }
}
