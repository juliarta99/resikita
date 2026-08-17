<?php

declare(strict_types=1);

namespace App\Livewire\Umkm;

use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Models\Produk;
use App\Models\ProdukFoto;
use App\Models\ProdukKategori;
use App\Models\Umkm;
use App\Services\Integration\ShippingService;
use App\Support\Unggahan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Katalog produk milik satu UMKM.
 *
 * `bahan_baku` bukan kolom hiasan. Marketplace Resikita menjual produk
 * daur ulang, dan pembeli datang justru karena itu, menyebut bahan
 * asalnya adalah inti nilai jualnya, bukan keterangan tambahan.
 */
#[Title('Produk')]
class ProdukManager extends Component
{
    use MemberiUmpanBalik;
    use WithFileUploads;
    use WithPagination;

    public string $cari = '';

    public bool $formTerbuka = false;

    public ?int $produkId = null;

    public string $nama = '';

    public string $kategoriId = '';

    public string $deskripsi = '';

    public string $bahanBaku = '';

    public string $harga = '';

    public string $stok = '0';

    public string $beratGram = '';

    public bool $isActive = true;

    public $fotoBaru = [];

    public function updatedCari(): void
    {
        $this->resetPage();
    }

    public function bukaForm(?int $id = null): void
    {
        $this->resetValidation();
        $this->reset([
            'produkId', 'nama', 'kategoriId', 'deskripsi', 'bahanBaku',
            'harga', 'stok', 'beratGram', 'isActive', 'fotoBaru',
        ]);

        if ($id !== null) {
            $produk = $this->milikToko()->findOrFail($id);

            $this->produkId = $produk->id;
            $this->nama = $produk->nama;
            $this->kategoriId = (string) $produk->kategori_id;
            $this->deskripsi = $produk->deskripsi ?? '';
            $this->bahanBaku = $produk->bahan_baku ?? '';
            $this->harga = (string) $produk->harga;
            $this->stok = (string) $produk->stok;
            $this->beratGram = (string) $produk->berat_gram;
            $this->isActive = $produk->is_active;
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
            'nama' => ['required', 'string', 'min:3', 'max:191'],
            'kategoriId' => ['required', 'integer', Rule::exists('produk_kategori', 'id')],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'bahanBaku' => ['nullable', 'string', 'max:191'],
            'harga' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'stok' => ['required', 'integer', 'min:0', 'max:1000000'],

            // Berat wajib: ongkir dihitung dari sini, dan produk tanpa
            // berat membuat checkout gagal di langkah terakhir, tempat
            // paling mahal untuk menemukan data yang kurang.
            'beratGram' => ['required', 'integer', 'min:1', 'max:500000'],
            'fotoBaru.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'nama' => 'nama produk', 'kategoriId' => 'kategori',
            'bahanBaku' => 'bahan baku', 'beratGram' => 'berat',
            'fotoBaru.*' => 'foto produk',
        ];
    }

    public function simpan(ShippingService $shipping): void
    {
        $umkm = auth()->user()->umkm;

        if ($umkm === null) {
            $this->pesanGalat('Akun Anda belum terhubung ke UMKM mana pun.');

            return;
        }

        $this->validate();

        // Produk boleh disimpan sebagai arsip tanpa alamat asal, tapi
        // tidak boleh tampil di marketplace tanpa itu. Aturannya ada di
        // ShippingService; di sini hanya dipanggil.
        if ($this->isActive && ! $this->siapMengirim($shipping, $umkm)) {
            return;
        }

        $atribut = [
            'umkm_id' => $umkm->id,
            'kategori_id' => (int) $this->kategoriId,
            'nama' => $this->nama,
            'deskripsi' => $this->deskripsi ?: null,
            'bahan_baku' => $this->bahanBaku ?: null,
            'harga' => (int) $this->harga,
            'stok' => (int) $this->stok,
            'berat_gram' => (int) $this->beratGram,
            'is_active' => $this->isActive,
        ];

        if ($this->produkId === null) {
            $atribut['slug'] = $this->slugUnik($this->nama);
            $produk = Produk::create($atribut);
            $this->pesanSukses('Produk ditambahkan.');
        } else {
            $produk = $this->milikToko()->findOrFail($this->produkId);
            $produk->update($atribut);
            $this->pesanSukses('Produk diperbarui.');
        }

        $this->simpanFoto($produk);
        $this->tutupForm();
    }

    private function simpanFoto(Produk $produk): void
    {
        if ($this->fotoBaru === []) {
            return;
        }

        $urutanTerakhir = (int) $produk->foto()->max('urutan');
        $adaUtama = $produk->foto()->where('is_utama', true)->exists();

        foreach ($this->fotoBaru as $index => $berkas) {
            ProdukFoto::create([
                'produk_id' => $produk->id,
                'path' => Unggahan::simpan($berkas, 'produk'),
                'urutan' => $urutanTerakhir + $index + 1,
                // Foto pertama yang pernah diunggah otomatis jadi sampul,
                // supaya katalog tidak pernah menampilkan kotak kosong.
                'is_utama' => ! $adaUtama && $index === 0,
            ]);
        }

        $this->reset('fotoBaru');
    }

    public function jadikanUtama(int $fotoId): void
    {
        $foto = ProdukFoto::with('produk')->findOrFail($fotoId);

        if ($foto->produk?->umkm_id !== auth()->user()->umkm_id) {
            $this->pesanGalat('Foto itu bukan milik produk toko Anda.');

            return;
        }

        ProdukFoto::query()->where('produk_id', $foto->produk_id)->update(['is_utama' => false]);
        $foto->update(['is_utama' => true]);

        $this->pesanSukses('Foto sampul diganti.');
    }

    public function hapusFoto(int $fotoId): void
    {
        $foto = ProdukFoto::with('produk')->findOrFail($fotoId);

        if ($foto->produk?->umkm_id !== auth()->user()->umkm_id) {
            $this->pesanGalat('Foto itu bukan milik produk toko Anda.');

            return;
        }

        $foto->delete();

        $this->pesanSukses('Foto dihapus.');
    }

    public function ubahAktif(int $id, ShippingService $shipping): void
    {
        $produk = $this->milikToko()->findOrFail($id);

        if (! $produk->is_active && ! $this->siapMengirim($shipping, auth()->user()->umkm)) {
            return;
        }

        $produk->update(['is_active' => ! $produk->is_active]);

        $this->pesanSukses($produk->is_active
            ? 'Produk kembali tampil di marketplace.'
            : 'Produk disembunyikan dari marketplace. Pesanan lama tidak terpengaruh.');
    }

    /**
     * Apakah toko sudah siap mengirim; pesan penolakan ditampilkan sendiri.
     */
    private function siapMengirim(ShippingService $shipping, ?Umkm $umkm): bool
    {
        if ($umkm === null) {
            $this->pesanGalat('Akun Anda belum terhubung ke UMKM mana pun.');

            return false;
        }

        return $this->jalankan(function () use ($shipping, $umkm): bool {
            $shipping->pastikanSiapMengirim($umkm);

            return true;
        }) === true;
    }

    /** Slug unik lintas seluruh marketplace, bukan hanya dalam satu toko. */
    private function slugUnik(string $nama): string
    {
        $dasar = Str::slug($nama);
        $slug = $dasar;
        $urutan = 1;

        while (Produk::query()->where('slug', $slug)->exists()) {
            $slug = $dasar.'-'.(++$urutan);
        }

        return $slug;
    }

    /** @return Builder<Produk> */
    private function milikToko()
    {
        return Produk::query()->where('umkm_id', auth()->user()->umkm_id);
    }

    public function render(ShippingService $shipping)
    {
        $umkm = auth()->user()->umkm;

        $query = $this->milikToko()
            ->with(['kategori:id,nama', 'foto'])
            ->withCount('ulasan')
            ->latest('id');

        if (trim($this->cari) !== '') {
            $query->where('nama', 'like', '%'.trim($this->cari).'%');
        }

        return view('livewire.umkm.produk-manager', [
            'daftar' => auth()->user()->umkm_id === null ? null : $query->paginate(12),
            'siapKirim' => $umkm !== null && $shipping->punyaOrigin($umkm),
            'kategoriTersedia' => ProdukKategori::query()->orderBy('nama')->pluck('nama', 'id'),
            'produkTerbuka' => $this->produkId !== null
                ? $this->milikToko()->with('foto')->find($this->produkId)
                : null,
        ]);
    }
}
