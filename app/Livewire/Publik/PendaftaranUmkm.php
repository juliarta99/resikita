<?php

declare(strict_types=1);

namespace App\Livewire\Publik;

use App\Enums\TingkatWilayah;
use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Models\Wilayah;
use App\Services\Auth\AkunService;
use App\Support\Unggahan;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Formulir pendaftaran UMKM, terbuka tanpa akun.
 *
 * Sama seperti pengajuan wilayah, pendaftarnya justru orang yang belum
 * punya akun Resikita, mewajibkan login lebih dulu membuat pintu ini
 * mustahil dilewati oleh orang yang dituju. Yang menjaga mutunya bukan
 * autentikasi, melainkan verifikasi admin sebelum toko boleh berjualan.
 *
 * Perbedaannya dengan pengajuan wilayah: di sini pendaftar menentukan
 * kata sandinya sendiri. Pemilik usaha bukan pejabat yang identitasnya
 * sudah terverifikasi lewat surat berkop dinas, dan memaksanya melewati
 * alur lupa kata sandi hanya untuk masuk pertama kali menambah satu
 * langkah gagal tanpa menambah jaminan apa pun.
 *
 * Seluruh pembuatan data ada di AkunService::daftarUmkmMandiri(),
 * komponen ini hanya mengumpulkan masukan, menyimpan berkas, dan
 * menampilkan hasilnya.
 */
#[Layout('components.layouts.publik')]
#[Title('Daftarkan UMKM')]
class PendaftaranUmkm extends Component
{
    use MemberiUmpanBalik;
    use WithFileUploads;

    // Wilayah
    public ?int $provinsiId = null;

    public ?int $kabupatenId = null;

    public ?int $kecamatanId = null;

    public ?int $desaId = null;

    // Toko
    public string $nama = '';

    public string $deskripsi = '';

    public string $alamat = '';

    public string $phone = '';

    public $foto;

    // Pemilik
    public string $pemilikNama = '';

    public string $pemilikEmail = '';

    public string $pemilikPhone = '';

    public string $password = '';

    public string $passwordKonfirmasi = '';

    public bool $setuju = false;

    public bool $terkirim = false;

    public string $namaTerdaftar = '';

    // ----------------------------------------------------------------
    // Pemilihan wilayah bertingkat
    // ----------------------------------------------------------------

    public function updatedProvinsiId(): void
    {
        $this->reset(['kabupatenId', 'kecamatanId', 'desaId']);
    }

    public function updatedKabupatenId(): void
    {
        $this->reset(['kecamatanId', 'desaId']);
    }

    public function updatedKecamatanId(): void
    {
        $this->reset('desaId');
    }

    /**
     * Wilayah yang dicatat untuk toko: yang paling dalam yang dipilih.
     *
     * Berbeda dari pengajuan wilayah, di sini tingkat apa pun sah.
     * `umkm.wilayah_id` adalah domisili usaha, bukan cakupan kewenangan,
     * jadi kecamatan pun bermakna, hanya kurang tepat dibanding desa.
     */
    private function wilayahTerpilih(): ?Wilayah
    {
        $id = $this->desaId ?? $this->kecamatanId ?? $this->kabupatenId;

        return $id !== null ? Wilayah::find($id) : null;
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'kabupatenId' => ['required', 'integer'],

            'nama' => ['required', 'string', 'min:3', 'max:191'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'alamat' => ['required', 'string', 'min:10', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^0[0-9]{8,13}$/'],
            'foto' => ['nullable', 'image', 'max:2048'],

            'pemilikNama' => ['required', 'string', 'min:3', 'max:150'],
            'pemilikEmail' => ['required', 'email:rfc', 'max:191', 'unique:users,email'],
            'pemilikPhone' => ['nullable', 'string', 'regex:/^0[0-9]{8,13}$/'],
            'password' => ['required', 'string', 'min:8', 'same:passwordKonfirmasi'],
            'passwordKonfirmasi' => ['required', 'string'],

            'setuju' => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'kabupatenId' => 'kabupaten/kota',
            'nama' => 'nama usaha',
            'deskripsi' => 'deskripsi usaha',
            'alamat' => 'alamat usaha',
            'phone' => 'nomor telepon usaha',
            'foto' => 'foto usaha',
            'pemilikNama' => 'nama pemilik',
            'pemilikEmail' => 'email pemilik',
            'pemilikPhone' => 'nomor telepon pemilik',
            'password' => 'kata sandi',
            'passwordKonfirmasi' => 'konfirmasi kata sandi',
            'setuju' => 'pernyataan',
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'kabupatenId.required' => 'Pilih kabupaten/kota tempat usaha Anda berada.',
            'alamat.min' => 'Tulis alamat selengkap mungkin agar pembeli tahu paket berangkat dari mana.',
            'phone.regex' => 'Nomor telepon diawali 0 dan terdiri dari 9 sampai 14 angka.',
            'pemilikPhone.regex' => 'Nomor telepon diawali 0 dan terdiri dari 9 sampai 14 angka.',
            'pemilikEmail.unique' => 'Email ini sudah terdaftar di Resikita. Masuk dengan akun yang ada, atau pakai email lain.',
            'password.same' => 'Konfirmasi kata sandi belum sama dengan kata sandi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'foto.image' => 'Foto usaha harus berupa gambar JPG, PNG, atau WEBP.',
            'foto.max' => 'Ukuran foto usaha maksimal 2 MB.',
            'setuju.accepted' => 'Centang pernyataan kebenaran data sebelum mengirim.',
        ];
    }

    public function daftar(AkunService $akun): void
    {
        $this->validate();

        $wilayah = $this->wilayahTerpilih();

        if ($wilayah === null) {
            $this->pesanGalat('Wilayah yang dipilih tidak ditemukan. Muat ulang halaman lalu coba lagi.');

            return;
        }

        // Foto etalase memang untuk dilihat umum, jadi disk publik sudah
        // tepat, berbeda dari surat kewenangan pada pengajuan wilayah.
        $fotoPath = $this->foto !== null ? Unggahan::simpan($this->foto, 'umkm') : null;

        $hasil = $this->jalankan(fn (): array => $akun->daftarUmkmMandiri([
            'nama' => $this->nama,
            'deskripsi' => $this->deskripsi ?: null,
            'alamat' => $this->alamat,
            'phone' => $this->phone ?: null,
            'foto' => $fotoPath,
            'wilayah_id' => $wilayah->id,

            'pemilik_nama' => $this->pemilikNama,
            'pemilik_email' => $this->pemilikEmail,
            'pemilik_phone' => $this->pemilikPhone ?: null,
            'password' => $this->password,
        ]));

        if ($hasil === null) {
            return;
        }

        $this->namaTerdaftar = $hasil['umkm']->nama;

        // Kata sandi tidak dibiarkan menetap di properti komponen setelah
        // dipakai. Livewire mengirim seluruh state kembali ke peramban di
        // setiap permintaan berikutnya.
        $this->reset(['password', 'passwordKonfirmasi', 'foto']);

        $this->terkirim = true;
    }

    public function render()
    {
        return view('livewire.publik.pendaftaran-umkm', [
            'provinsiTersedia' => Wilayah::query()
                ->tingkat(TingkatWilayah::Provinsi)
                ->orderBy('nama')
                ->pluck('nama', 'id'),

            'kabupatenTersedia' => $this->provinsiId === null
                ? collect()
                : Wilayah::query()->where('parent_id', $this->provinsiId)->orderBy('nama')->pluck('nama', 'id'),

            'kecamatanTersedia' => $this->kabupatenId === null
                ? collect()
                : Wilayah::query()->where('parent_id', $this->kabupatenId)->orderBy('nama')->pluck('nama', 'id'),

            'desaTersedia' => $this->kecamatanId === null
                ? collect()
                : Wilayah::query()->where('parent_id', $this->kecamatanId)->orderBy('nama')->pluck('nama', 'id'),

            'wilayahDipilih' => $this->wilayahTerpilih(),
        ]);
    }
}
