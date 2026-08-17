<?php

declare(strict_types=1);

namespace App\Livewire\BankSampah;

use App\Enums\KategoriSampah;
use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Models\BankSampahHarga;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Katalog harga milik satu unit bank sampah.
 *
 * Di skema Niti Resik harga bersifat global: satu daftar berlaku untuk
 * seluruh unit. Itu tidak mencerminkan kenyataan, harga kardus di
 * pengepul kota berbeda dari harga di desa, dan berubah tiap bulan
 * mengikuti pasar. Sejak Resikita, katalog melekat pada unitnya
 * (CLAUDE.md 4.1).
 *
 * Harga yang diubah di sini tidak mengubah transaksi lama. Setiap baris
 * setoran menyimpan `harga_snapshot` sendiri, sehingga riwayat nasabah
 * tetap menunjukkan angka yang benar-benar ia terima saat itu.
 */
#[Title('Katalog Harga')]
class HargaManager extends Component
{
    use MemberiUmpanBalik;

    public string $cari = '';

    public bool $formTerbuka = false;

    public ?int $hargaId = null;

    public string $jenisSampah = '';

    public string $kategori = 'anorganik';

    public string $satuan = 'kg';

    public string $hargaPerSatuan = '';

    public bool $isActive = true;

    public function bukaForm(?int $id = null): void
    {
        $this->resetValidation();
        $this->reset(['hargaId', 'jenisSampah', 'kategori', 'satuan', 'hargaPerSatuan', 'isActive']);

        if ($id !== null) {
            $harga = $this->milikUnit()->findOrFail($id);

            $this->hargaId = $harga->id;
            $this->jenisSampah = $harga->jenis_sampah;
            $this->kategori = $harga->kategori->value;
            $this->satuan = $harga->satuan;
            $this->hargaPerSatuan = (string) $harga->harga_per_satuan;
            $this->isActive = $harga->is_active;
        }

        $this->formTerbuka = true;
    }

    public function tutupForm(): void
    {
        $this->formTerbuka = false;
        $this->resetValidation();
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'jenisSampah' => [
                'required', 'string', 'min:2', 'max:191',
                Rule::unique('bank_sampah_harga', 'jenis_sampah')
                    ->where('bank_sampah_id', auth()->user()->bank_sampah_id)
                    ->ignore($this->hargaId),
            ],
            'kategori' => ['required', Rule::enum(KategoriSampah::class)],
            'satuan' => ['required', 'string', 'max:20'],

            // Rupiah penuh sebagai integer. Tidak ada satuan sen dalam
            // mata uang ini, dan memperkenalkannya hanya melahirkan
            // galat pembulatan pada perkalian berat.
            'hargaPerSatuan' => ['required', 'integer', 'min:0', 'max:10000000'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'jenisSampah' => 'jenis sampah',
            'hargaPerSatuan' => 'harga per satuan',
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'jenisSampah.unique' => 'Jenis sampah itu sudah ada di katalog unit Anda.',
        ];
    }

    public function simpan(): void
    {
        $bankSampahId = auth()->user()->bank_sampah_id;

        if ($bankSampahId === null) {
            $this->pesanGalat('Akun Anda belum terhubung ke unit bank sampah mana pun.');

            return;
        }

        $this->validate();

        $atribut = [
            'bank_sampah_id' => $bankSampahId,
            'jenis_sampah' => $this->jenisSampah,
            'kategori' => $this->kategori,
            'satuan' => $this->satuan,
            'harga_per_satuan' => (int) $this->hargaPerSatuan,
            'is_active' => $this->isActive,
        ];

        if ($this->hargaId === null) {
            BankSampahHarga::create($atribut);
            $this->pesanSukses('Jenis sampah ditambahkan ke katalog.');
        } else {
            $this->milikUnit()->findOrFail($this->hargaId)->update($atribut);
            $this->pesanSukses('Harga diperbarui. Transaksi lama tidak ikut berubah.');
        }

        $this->tutupForm();
    }

    public function ubahAktif(int $id): void
    {
        $harga = $this->milikUnit()->findOrFail($id);

        $harga->update(['is_active' => ! $harga->is_active]);

        $this->pesanSukses($harga->is_active
            ? "\"{$harga->jenis_sampah}\" kembali diterima."
            : "\"{$harga->jenis_sampah}\" berhenti diterima. Transaksi lama tetap utuh.");
    }

    /**
     * Katalog milik unit pengguna.
     *
     * @return Builder<BankSampahHarga>
     */
    private function milikUnit()
    {
        return BankSampahHarga::query()->where('bank_sampah_id', auth()->user()->bank_sampah_id);
    }

    public function render()
    {
        $query = $this->milikUnit()->orderBy('kategori')->orderBy('jenis_sampah');

        if (trim($this->cari) !== '') {
            $query->where('jenis_sampah', 'like', '%'.trim($this->cari).'%');
        }

        return view('livewire.bank-sampah.harga-manager', [
            'daftar' => $query->get()->groupBy(fn (BankSampahHarga $h): string => $h->kategori->label()),
            'kategoriTersedia' => KategoriSampah::options(),
            'punyaUnit' => auth()->user()->bank_sampah_id !== null,
        ]);
    }
}
