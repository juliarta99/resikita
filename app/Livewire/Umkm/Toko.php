<?php

declare(strict_types=1);

namespace App\Livewire\Umkm;

use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Models\Umkm;
use App\Services\Auth\AkunService;
use App\Services\Integration\ShippingService;
use App\Support\Unggahan;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Profil toko, termasuk titik asal pengiriman.
 *
 * ## Kenapa halaman ini ada
 *
 * Sebelumnya UMKM tidak punya satu pun tempat untuk menyunting datanya
 * sendiri: nama toko lahir dari formulir pendaftaran dan setelah itu
 * hanya bisa diubah lewat basis data. Yang membuatnya mendesak adalah
 * ongkos kirim, asal pengiriman dulu dibaca dari satu nilai global di
 * config, yang keliru untuk marketplace banyak penjual, dan penjual
 * tidak punya cara menetapkan asalnya sendiri.
 *
 * ## Asal pengiriman dipilih, bukan diketik
 *
 * Penyedia ongkir hanya mengenali wilayah lewat idnya. Kolom teks bebas
 * akan menghasilkan alamat yang terbaca benar oleh manusia tapi tidak
 * bisa dipakai menghitung apa pun, dan kegagalannya baru muncul di depan
 * pembeli. Karena itu satu-satunya jalan menyimpan asal adalah memilih
 * dari hasil pencarian penyedia.
 */
#[Title('Toko')]
class Toko extends Component
{
    use MemberiUmpanBalik;
    use WithFileUploads;

    public string $nama = '';

    public string $deskripsi = '';

    public string $alamat = '';

    public string $phone = '';

    public string $email = '';

    public $fotoBaru;

    /** Kata kunci pencarian wilayah asal. */
    public string $cariAsal = '';

    /** @var array<int, array<string, mixed>> */
    public array $hasilAsal = [];

    public ?int $destinationId = null;

    public ?string $alamatAsal = null;

    public function mount(): void
    {
        $umkm = $this->toko();

        if ($umkm === null) {
            return;
        }

        $this->nama = $umkm->nama;
        $this->deskripsi = $umkm->deskripsi ?? '';
        $this->alamat = $umkm->alamat ?? '';
        $this->phone = $umkm->phone ?? '';
        $this->email = $umkm->email ?? '';
        $this->destinationId = $umkm->destination_id;
        $this->alamatAsal = $umkm->alamat_asal;
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'min:3', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:191'],
            'fotoBaru' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'nama' => 'nama toko',
            'fotoBaru' => 'foto toko',
            'cariAsal' => 'kata kunci alamat',
        ];
    }

    // ----------------------------------------------------------------
    // Asal pengiriman
    // ----------------------------------------------------------------

    public function cariAlamatAsal(ShippingService $shipping): void
    {
        $this->validate(
            ['cariAsal' => ['required', 'string', 'min:3', 'max:100']],
            attributes: $this->validationAttributes(),
        );

        $hasil = $this->jalankan(fn (): array => $shipping->cariTujuan($this->cariAsal, 15));

        if ($hasil === null) {
            return;
        }

        $this->hasilAsal = $hasil;

        if ($hasil === []) {
            $this->pesanGalat('Tidak ada wilayah yang cocok. Coba nama kecamatan atau kelurahannya.');
        }
    }

    public function pilihAlamatAsal(int $id, string $label): void
    {
        $this->destinationId = $id;
        $this->alamatAsal = $label;
        $this->hasilAsal = [];
        $this->cariAsal = '';
    }

    public function hapusAlamatAsal(): void
    {
        $this->destinationId = null;
        $this->alamatAsal = null;
    }

    // ----------------------------------------------------------------
    // Simpan
    // ----------------------------------------------------------------

    public function simpan(AkunService $akun): void
    {
        $umkm = $this->toko();

        if ($umkm === null) {
            $this->pesanGalat('Akun Anda belum terhubung ke UMKM mana pun.');

            return;
        }

        $this->validate();

        $data = [
            'nama' => $this->nama,
            'deskripsi' => $this->deskripsi ?: null,
            'alamat' => $this->alamat ?: null,
            'phone' => $this->phone ?: null,
            'email' => $this->email ?: null,
            'destination_id' => $this->destinationId,
            'alamat_asal' => $this->alamatAsal,
        ];

        if ($this->fotoBaru !== null) {
            $data['foto'] = Unggahan::simpan($this->fotoBaru, 'umkm');
        }

        $this->jalankan(
            fn (): Umkm => $akun->perbaruiToko($umkm, $data),
            $this->destinationId === null
                ? 'Data toko disimpan. Alamat asal pengiriman masih kosong, jadi produk Anda belum bisa dipesan.'
                : 'Data toko disimpan.',
        );

        $this->reset('fotoBaru');
    }

    private function toko(): ?Umkm
    {
        return auth()->user()->umkm;
    }

    public function render(ShippingService $shipping)
    {
        $umkm = $this->toko();

        return view('livewire.umkm.toko', [
            'umkm' => $umkm,
            'siapKirim' => $umkm !== null && $shipping->punyaOrigin($umkm),
        ]);
    }
}
