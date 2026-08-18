<?php

declare(strict_types=1);

namespace App\Livewire\Umkm;

use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Models\Umkm;
use App\Services\Auth\AkunService;
use App\Support\Unggahan;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Status pendaftaran toko, untuk pemilik yang belum lolos verifikasi.
 *
 * Halaman ini yang menutup jalan buntu lama. Dulu pendaftar dikunci di
 * luar sistem: akunnya dimatikan sampai disetujui, dan dimatikan
 * selamanya bila ditolak, sehingga ia tidak pernah tahu apa yang
 * kurang, dan satu-satunya jalan keluar adalah mendaftar ulang dengan
 * email lain, meninggalkan toko mati di basis data.
 *
 * Sekarang penolakan selalu membawa alasan, dan alasan itu dibaca di
 * sini, di dalam aplikasi, oleh orang yang harus menindaklanjutinya.
 *
 * Sengaja berada di luar middleware `toko.terverifikasi`, halaman yang
 * hanya berguna bagi toko yang belum terverifikasi tidak boleh dijaga
 * gerbang yang menuntut toko sudah terverifikasi.
 */
#[Title('Status Pendaftaran')]
class StatusPendaftaran extends Component
{
    use MemberiUmpanBalik;
    use WithFileUploads;

    public string $nama = '';

    public string $deskripsi = '';

    public string $alamat = '';

    public string $phone = '';

    public $fotoBaru;

    public function mount()
    {
        $umkm = $this->toko();

        // Toko yang sudah aktif tidak punya urusan di sini. Diantar ke
        // dasbornya, bukan dibiarkan membaca halaman status yang isinya
        // sudah tidak berlaku.
        if ($umkm?->bolehBerjualan()) {
            return redirect()->route('umkm.dashboard');
        }

        if ($umkm !== null) {
            $this->nama = $umkm->nama;
            $this->deskripsi = $umkm->deskripsi ?? '';
            $this->alamat = $umkm->alamat ?? '';
            $this->phone = $umkm->phone ?? '';
        }

        return null;
    }

    private function toko(): ?Umkm
    {
        return auth()->user()->umkm;
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'min:3', 'max:191'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'alamat' => ['required', 'string', 'min:10', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^0[0-9]{8,13}$/'],
            'fotoBaru' => ['nullable', 'image', 'max:2048'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'nama' => 'nama usaha',
            'deskripsi' => 'deskripsi usaha',
            'alamat' => 'alamat usaha',
            'phone' => 'nomor telepon',
            'fotoBaru' => 'foto usaha',
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'alamat.min' => 'Tulis alamat selengkap mungkin agar peninjau bisa memastikan lokasinya.',
            'phone.regex' => 'Nomor telepon diawali 0 dan terdiri dari 9 sampai 14 angka.',
        ];
    }

    public function ajukanUlang(AkunService $akun): void
    {
        $umkm = $this->toko();

        if ($umkm === null) {
            $this->pesanGalat('Akun Anda belum tertaut ke toko mana pun. Hubungi admin Resikita.');

            return;
        }

        $this->validate();

        $data = [
            'nama' => $this->nama,
            'deskripsi' => $this->deskripsi ?: null,
            'alamat' => $this->alamat,
            'phone' => $this->phone ?: null,
            'email' => $umkm->email,
        ];

        if ($this->fotoBaru !== null) {
            $data['foto'] = Unggahan::simpan($this->fotoBaru, 'umkm');
        }

        $hasil = $this->jalankan(
            fn (): Umkm => $akun->ajukanUlangUmkm($umkm, $data),
            'Pengajuan ulang terkirim. Toko Anda kembali masuk antrean peninjauan.',
        );

        if ($hasil !== null) {
            $this->reset('fotoBaru');
        }
    }

    public function render()
    {
        return view('livewire.umkm.status-pendaftaran', [
            'umkm' => $this->toko()?->loadMissing(['wilayah', 'peninjau:id,name']),
        ]);
    }
}
