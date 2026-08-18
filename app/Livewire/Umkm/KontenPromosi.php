<?php

declare(strict_types=1);

namespace App\Livewire\Umkm;

use App\Enums\GayaSampul;
use App\Enums\NadaKonten;
use App\Enums\PaletSampul;
use App\Enums\RasioSampul;
use App\Enums\TujuanKonten;
use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Models\KontenPromosi as ModelKonten;
use App\Models\Produk;
use App\Services\Konten\KontenPromosiService;
use App\Support\PreferensiSampul;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Asisten Konten UMKM.
 *
 * Komponen ini tidak menyusun satu pun kalimat instruksi maupun aturan
 * template. Nada, sasaran keluaran, kuota harian, dan tata letak sampul
 * seluruhnya ada di KontenPromosiService, SampulService, dan enum
 * terkait, di sini hanya penyerahan masukan dan penyampaian hasil.
 *
 * Lencana "Dibuat dengan bantuan AI" bukan pilihan tampilan.
 * `is_ai_generated` selalu true dan lencananya wajib terlihat pengguna
 * (CLAUDE.md 10.3). Yang tidak dilakukan lagi adalah membakar tulisan
 * itu ke dalam berkas sampulnya, lihat SampulService.
 */
#[Title('Asisten Konten')]
class KontenPromosi extends Component
{
    use MemberiUmpanBalik;

    #[Url(as: 'tujuan', except: 'instagram')]
    public string $tujuan = 'instagram';

    public string $nada = 'hangat';

    public string $produkId = '';

    /** Draf yang sedang dibuka untuk disunting. */
    public ?int $kontenId = null;

    public string $teksSuntingan = '';

    public string $hashtagSuntingan = '';

    // ----------------------------------------------------------------
    // Pilihan tampilan sampul
    //
    // Disimpan sebagai nilai mentah, bukan objek PreferensiSampul:
    // properti Livewire harus bisa diserialkan bolak-balik ke peramban.
    // Perakitan menjadi objek dan penjatuhan ke bawaan untuk nilai yang
    // tidak dikenali tetap terjadi di PreferensiSampul.
    // ----------------------------------------------------------------

    public string $gaya = 'tirai_bawah';

    public string $palet = 'hijau';

    public string $rasio = 'persegi';

    public bool $tampilkanHarga = true;

    public bool $tampilkanBahan = true;

    public bool $tampilkanToko = false;

    public string $judulSampul = '';

    public string $pendukungSampul = '';

    public function mount(): void
    {
        $this->produkId = (string) (Produk::query()
            ->where('umkm_id', auth()->user()->umkm_id)
            ->aktif()
            ->value('id') ?? '');
    }

    // ----------------------------------------------------------------
    // Menyusun draf
    // ----------------------------------------------------------------

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'tujuan' => ['required', Rule::enum(TujuanKonten::class)],
            'nada' => ['required', Rule::enum(NadaKonten::class)],
            'produkId' => ['required', 'integer'],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return ['produkId.required' => 'Pilih produk yang ingin dipromosikan.'];
    }

    public function susun(KontenPromosiService $service): void
    {
        $umkm = auth()->user()->umkm;

        if ($umkm === null) {
            $this->pesanGalat('Akun Anda belum terhubung ke UMKM mana pun.');

            return;
        }

        $this->validate();

        $produk = Produk::with('foto')->find((int) $this->produkId);

        $konten = $this->jalankan(
            fn (): ModelKonten => $service->susun(
                $umkm,
                TujuanKonten::from($this->tujuan),
                NadaKonten::from($this->nada),
                $produk,
            ),
            'Draf selesai disusun. Periksa dan sunting sebelum dipakai.',
        );

        if ($konten !== null) {
            $this->bukaDraf($konten->id);
        }
    }

    // ----------------------------------------------------------------
    // Menyunting draf
    // ----------------------------------------------------------------

    public function bukaDraf(int $id, ?KontenPromosiService $service = null): void
    {
        $konten = $this->milikToko()->findOrFail($id);

        $this->kontenId = $konten->id;
        $this->teksSuntingan = $konten->hasil_teks ?? '';
        $this->hashtagSuntingan = collect($konten->hasil_hashtag ?? [])->implode(' ');

        $service ??= app(KontenPromosiService::class);
        $pref = $service->preferensi($konten);

        $this->gaya = $pref->gaya->value;
        $this->palet = $pref->palet->value;
        $this->rasio = $pref->rasio->value;
        $this->tampilkanHarga = $pref->tampilkanHarga;
        $this->tampilkanBahan = $pref->tampilkanBahan;
        $this->tampilkanToko = $pref->tampilkanToko;
        $this->judulSampul = $pref->judul ?? '';
        $this->pendukungSampul = $pref->pendukung ?? '';

        $this->resetValidation();
    }

    public function tutupDraf(): void
    {
        $this->reset([
            'kontenId', 'teksSuntingan', 'hashtagSuntingan',
            'gaya', 'palet', 'rasio',
            'tampilkanHarga', 'tampilkanBahan', 'tampilkanToko',
            'judulSampul', 'pendukungSampul',
        ]);
    }

    /**
     * Setiap perubahan pilihan sampul langsung disimpan.
     *
     * Penjual di sini tidak sedang mengisi formulir yang berakhir dengan
     * tombol simpan, ia sedang menggeser-geser tampilan sambil melihat
     * pratinjaunya. Menuntut satu tombol tambahan setelah tiap percobaan
     * adalah cara tercepat membuat pilihannya hilang begitu halaman
     * ditutup.
     */
    public function updated(string $properti): void
    {
        $pilihanSampul = [
            'gaya', 'palet', 'rasio',
            'tampilkanHarga', 'tampilkanBahan', 'tampilkanToko',
            'judulSampul', 'pendukungSampul',
        ];

        if ($this->kontenId === null || ! in_array($properti, $pilihanSampul, true)) {
            return;
        }

        $konten = $this->milikToko()->find($this->kontenId);

        if ($konten === null || ! $konten->tujuan->menghasilkanGambar()) {
            return;
        }

        app(KontenPromosiService::class)->simpanPreferensi($konten, $this->preferensi());
    }

    /** Rakit pilihan di layar menjadi objek preferensi yang sudah dinormalkan. */
    private function preferensi(): PreferensiSampul
    {
        return PreferensiSampul::dariArray([
            'gaya' => $this->gaya,
            'palet' => $this->palet,
            'rasio' => $this->rasio,
            'tampilkan_harga' => $this->tampilkanHarga,
            'tampilkan_bahan' => $this->tampilkanBahan,
            'tampilkan_toko' => $this->tampilkanToko,
            'judul' => $this->judulSampul,
            'pendukung' => $this->pendukungSampul,
        ]);
    }

    public function simpanSuntingan(KontenPromosiService $service): void
    {
        $konten = $this->milikToko()->findOrFail($this->kontenId);

        $this->validate(
            ['teksSuntingan' => ['required', 'string', 'min:10', 'max:5000']],
            ['teksSuntingan.required' => 'Teks konten tidak boleh kosong.'],
        );

        $this->jalankan(
            fn () => $service->perbaruiTeks(
                $konten,
                $this->teksSuntingan,
                preg_split('/[\s,]+/', $this->hashtagSuntingan) ?: [],
            ),
            'Suntingan disimpan.',
        );
    }

    /**
     * Terima hasil gambar sampul dari peramban.
     *
     * Peramban hanya mengeksekusi spesifikasi yang disusun peladen;
     * hasilnya tetap diperiksa ulang di SampulService sebelum disimpan.
     */
    public function simpanSampul(string $dataUri, KontenPromosiService $service): void
    {
        $konten = $this->milikToko()->findOrFail($this->kontenId);

        // Preferensi disimpan ulang tepat sebelum berkasnya masuk. Ukuran
        // gambar diperiksa terhadap rasio yang tersimpan, jadi keduanya
        // harus berasal dari satu keadaan yang sama, bukan dari pilihan
        // tersimpan yang mungkin tertinggal satu langkah.
        $konten = $service->simpanPreferensi($konten, $this->preferensi());

        $this->jalankan(
            fn () => $service->simpanSampul($konten, $dataUri),
            'Sampul tersimpan. Unduh lalu unggah ke media sosial Anda.',
        );
    }

    public function tandaiDipakai(int $id, KontenPromosiService $service): void
    {
        $konten = $this->milikToko()->findOrFail($id);

        $this->jalankan(
            fn () => $service->tandaiDipakai($konten, ! $konten->dipakai),
            $konten->dipakai
                ? 'Tanda "sudah dipakai" dilepas.'
                : 'Ditandai sudah dipakai. Terima kasih, angka ini yang menunjukkan fitur ini berguna.',
        );
    }

    public function hapus(int $id, KontenPromosiService $service): void
    {
        $konten = $this->milikToko()->findOrFail($id);

        $service->hapus($konten);

        if ($this->kontenId === $id) {
            $this->tutupDraf();
        }

        $this->pesanSukses('Draf dihapus.');
    }

    /** @return Builder<ModelKonten> */
    private function milikToko()
    {
        return ModelKonten::query()->where('umkm_id', auth()->user()->umkm_id);
    }

    public function render(KontenPromosiService $service)
    {
        $umkm = auth()->user()->umkm;

        if ($umkm === null) {
            return view('livewire.umkm.konten-promosi', ['umkm' => null]);
        }

        $konten = $this->kontenId !== null
            ? $this->milikToko()->with(['produk.foto', 'produk.fotoUtama', 'produk.umkm'])->find($this->kontenId)
            : null;

        return view('livewire.umkm.konten-promosi', [
            'umkm' => $umkm,
            'konten' => $konten,
            'templateSampul' => $konten !== null && $konten->tujuan->menghasilkanGambar()
                ? rescue(fn () => $service->templateSampul($konten, $this->preferensi()), null, report: false)
                : null,
            'riwayat' => $service->riwayat($umkm),
            'sisaKuota' => $service->sisaKuota($umkm),
            'produkTersedia' => Produk::query()
                ->where('umkm_id', $umkm->id)
                ->aktif()
                ->orderBy('nama')
                ->pluck('nama', 'id'),
            'tujuanTersedia' => TujuanKonten::cases(),
            'nadaTersedia' => NadaKonten::cases(),
            'gayaTersedia' => GayaSampul::cases(),
            'paletTersedia' => PaletSampul::cases(),
            'rasioTersedia' => RasioSampul::cases(),
        ]);
    }
}
