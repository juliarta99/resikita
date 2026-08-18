<?php

declare(strict_types=1);

namespace App\Livewire\Fasilitator;

use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Models\Laporan;
use App\Services\Laporan\TindakLanjutService;
use App\Support\Unggahan;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Pencatatan tindak lanjut fasilitator atas satu laporan.
 *
 * Yang dicatat di sini adalah komunikasi yang terjadi di luar sistem,
 * telepon, surat, atau kunjungan ke dinas setempat. Resikita tidak bisa
 * memaksa dinas yang belum bergabung untuk bertindak; yang bisa
 * dilakukan adalah meneruskan laporan warga dan mencatat hasilnya.
 *
 * Catatan ini punya dua pembaca. Warga, yang perlu tahu laporannya tidak
 * hilang begitu saja. Dan Resikita sendiri, yang memakainya sebagai
 * bukti konkret saat mengajak wilayah itu bergabung.
 */
#[Title('Tindak Lanjut Laporan')]
class LaporanDetail extends Component
{
    use MemberiUmpanBalik;
    use WithFileUploads;

    public Laporan $laporan;

    public bool $formTerbuka = false;

    public string $namaDinas = '';

    public string $kontakDinas = '';

    public string $tanggalKontak = '';

    public string $hasil = '';

    public $lampiran;

    public function mount(Laporan $laporan): void
    {
        $this->authorize('view', $laporan);

        $this->laporan = $laporan;
        $this->tanggalKontak = now()->toDateString();
    }

    public function bukaForm(): void
    {
        $this->resetValidation();
        $this->formTerbuka = true;
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'namaDinas' => ['required', 'string', 'min:3', 'max:191'],
            'kontakDinas' => ['nullable', 'string', 'max:191'],
            'tanggalKontak' => ['required', 'date', 'before_or_equal:today'],
            'hasil' => ['required', 'string', 'min:10', 'max:2000'],
            'lampiran' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'namaDinas' => 'nama dinas',
            'kontakDinas' => 'kontak dinas',
            'tanggalKontak' => 'tanggal kontak',
            'hasil' => 'hasil komunikasi',
        ];
    }

    public function simpan(TindakLanjutService $service): void
    {
        $this->authorize('tindakLanjut', $this->laporan);

        $this->validate();

        $data = [
            'nama_dinas' => $this->namaDinas,
            'kontak_dinas' => $this->kontakDinas ?: null,
            'tanggal_kontak' => $this->tanggalKontak,
            'hasil' => $this->hasil,
        ];

        if ($this->lampiran !== null) {
            // Surat dan bukti kontak disimpan di disk privat: isinya
            // bisa memuat nama dan nomor pejabat yang tidak semestinya
            // bisa dibuka siapa saja lewat tebakan URL.
            $data['lampiran_path'] = Unggahan::simpan($this->lampiran, 'tindak-lanjut', 'local');
        }

        $hasil = $this->jalankan(
            fn () => $service->catat($this->laporan, auth()->user(), $data),
            'Tindak lanjut dicatat. Pelapor bisa melihat bahwa laporannya sudah diteruskan.',
        );

        if ($hasil !== null) {
            $this->reset(['formTerbuka', 'namaDinas', 'kontakDinas', 'hasil', 'lampiran']);
            $this->tanggalKontak = now()->toDateString();
        }
    }

    public function render()
    {
        $this->laporan->loadMissing([
            'kategori', 'pelapor', 'foto', 'desa', 'kecamatan', 'kabupaten', 'provinsi',
            'tindakLanjut.fasilitator',
        ]);

        return view('livewire.fasilitator.laporan-detail');
    }
}
