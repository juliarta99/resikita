<?php

declare(strict_types=1);

use App\Enums\GayaSampul;
use App\Enums\NadaKonten;
use App\Enums\PaletSampul;
use App\Enums\RasioSampul;
use App\Enums\Role;
use App\Enums\StatusUmkm;
use App\Enums\TujuanKonten;
use App\Exceptions\AturanBisnisException;
use App\Livewire\Umkm\KontenPromosi as KomponenKonten;
use App\Models\KontenPromosi;
use App\Models\Produk;
use App\Models\ProdukFoto;
use App\Models\ProdukKategori;
use App\Models\Umkm;
use App\Models\User;
use App\Services\Konten\KontenPromosiService;
use App\Services\Konten\SampulService;
use App\Support\PreferensiSampul;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Asisten Konten UMKM (CLAUDE.md 10.3).
 *
 * Dua aturan yang paling penting dijaga di sini, dan keduanya soal
 * kejujuran kepada pembeli: konten selalu ditandai dibuat AI, dan foto
 * produk asli tidak pernah digantikan citra buatan.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    Storage::fake('public');

    $this->umkm = Umkm::factory()->create([
        'nama' => 'Bengkel Daur Ulang Sari',
        'status' => StatusUmkm::Aktif,
        'is_verified' => true,
    ]);

    $this->pemilik = User::factory()->withRole(Role::Umkm)->create(['umkm_id' => $this->umkm->id]);

    $this->kategori = ProdukKategori::factory()->create();

    $this->produk = Produk::factory()->create([
        'umkm_id' => $this->umkm->id,
        'kategori_id' => $this->kategori->id,
        'nama' => 'Tas Dari Sachet Bekas',
        'bahan_baku' => 'Kemasan sachet bekas',
        'harga' => 85_000,
    ]);

    $this->service = app(KontenPromosiService::class);
});

/** Palsukan satu jawaban Gemini untuk generator konten. */
function kontenDijawab(array $isi): void
{
    Http::fake([
        '*generativelanguage*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [['text' => json_encode($isi)]]],
                'finishReason' => 'STOP',
            ]],
        ]),
    ]);
}

describe('penyusunan draf', function (): void {
    it('menyimpan draf beserta label AI dan jejak versi modelnya', function (): void {
        kontenDijawab([
            'teks' => 'Tas dari sachet bekas, dijahit tangan di Bantul.',
            'hashtag' => ['daurulang', 'umkmlokal', 'zerowaste'],
        ]);

        $konten = $this->service->susun(
            $this->umkm,
            TujuanKonten::Instagram,
            NadaKonten::Hangat,
            $this->produk,
        );

        expect($konten->is_ai_generated)->toBeTrue()
            ->and($konten->model_version)->toBe(config('services.gemini.model'))
            ->and($konten->dipakai)->toBeFalse()
            ->and($konten->hasil_teks)->toContain('sachet bekas');
    });

    it('menyeragamkan tagar apa pun bentuk jawaban modelnya', function (): void {
        kontenDijawab([
            'teks' => 'Contoh caption.',
            // Bentuk yang benar-benar keluar dari model: campur tanda
            // pagar, spasi di tengah, dan duplikat.
            'hashtag' => ['#daurulang', 'umkm lokal', '#daurulang', 'zero-waste', '!!'],
        ]);

        $konten = $this->service->susun(
            $this->umkm,
            TujuanKonten::Instagram,
            NadaKonten::Persuasif,
            $this->produk,
        );

        expect($konten->hasil_hashtag)->toBe(['#daurulang', '#umkmlokal', '#zerowaste']);
    });

    it('tidak menyimpan tagar untuk tujuan selain caption', function (): void {
        kontenDijawab(['teks' => 'Deskripsi produk.', 'hashtag' => ['abaikan']]);

        $konten = $this->service->susun(
            $this->umkm,
            TujuanKonten::DeskripsiProduk,
            NadaKonten::Informatif,
            $this->produk,
        );

        expect($konten->hasil_hashtag)->toBeNull();
    });

    it('menolak produk milik toko lain', function (): void {
        kontenDijawab(['teks' => 'apa pun']);

        $tokoLain = Umkm::factory()->create(['status' => StatusUmkm::Aktif]);
        $produkOrangLain = Produk::factory()->create([
            'umkm_id' => $tokoLain->id,
            'kategori_id' => $this->kategori->id,
        ]);

        expect(fn () => $this->service->susun(
            $this->umkm,
            TujuanKonten::Instagram,
            NadaKonten::Hangat,
            $produkOrangLain,
        ))->toThrow(AturanBisnisException::class);
    });

    it('menolak sampul untuk produk yang belum punya foto asli', function (): void {
        kontenDijawab(['teks' => 'apa pun']);

        expect(fn () => $this->service->susun(
            $this->umkm,
            TujuanKonten::SampulProduk,
            NadaKonten::Hangat,
            $this->produk,
        ))->toThrow(AturanBisnisException::class);

        // Ditolak sebelum model dipanggil: biayanya nyata, dan
        // penolakannya sudah bisa dipastikan dari data yang ada.
        Http::assertNothingSent();
    });
});

describe('sampul produk', function (): void {
    beforeEach(function (): void {
        ProdukFoto::create([
            'produk_id' => $this->produk->id,
            'path' => 'produk/asli.jpg',
            'urutan' => 1,
            'is_utama' => true,
        ]);

        $this->produk->refresh();
    });

    it('menyusun template di atas foto produk asli', function (): void {
        kontenDijawab(['teks' => "Tas Sachet Anyaman\nDijahit tangan dari kemasan bekas."]);

        $konten = $this->service->susun(
            $this->umkm,
            TujuanKonten::SampulProduk,
            NadaKonten::Informatif,
            $this->produk,
        );

        $template = $this->service->templateSampul($konten);

        expect($template['lebar'])->toBe(SampulService::LEBAR)
            ->and($template['tinggi'])->toBe(RasioSampul::Persegi->tinggi())
            // Yang dirujuk adalah foto yang benar-benar diunggah penjual.
            ->and($template['foto_url'])->toContain('produk/asli.jpg')
            ->and(teksTemplate($template))->toContain('Tas Sachet Anyaman')
            ->and(implode(' ', teksTemplate($template)))->toContain('Dijahit tangan');
    });

    it('tidak lagi membakar label AI ke dalam gambar sampulnya', function (): void {
        kontenDijawab(['teks' => "Judul Sampul\nKalimat pendukung."]);

        $konten = $this->service->susun(
            $this->umkm,
            TujuanKonten::SampulProduk,
            NadaKonten::Informatif,
            $this->produk,
        );

        $template = $this->service->templateSampul($konten);

        // Tidak satu pun teks yang tergambar menyebut AI. Kewajiban
        // pelabelan dipenuhi lencana di layar penyusun, bukan tulisan di
        // atas berkas gambarnya.
        expect(implode(' ', teksTemplate($template)))
            ->not->toContain('AI')
            ->and(json_encode($template))->not->toContain('label_ai');

        // Penandanya sendiri tetap menyala di basis data.
        expect($konten->is_ai_generated)->toBeTrue();
    });

    it('mengubah tata letak dan ukuran kanvas mengikuti preferensi penjual', function (): void {
        kontenDijawab(['teks' => "Judul Sampul\nKalimat pendukung."]);

        $konten = $this->service->susun(
            $this->umkm,
            TujuanKonten::SampulProduk,
            NadaKonten::Informatif,
            $this->produk,
        );

        $pref = new PreferensiSampul(
            gaya: GayaSampul::PitaSamping,
            palet: PaletSampul::Terakota,
            rasio: RasioSampul::Cerita,
            tampilkanHarga: true,
            tampilkanBahan: true,
            tampilkanToko: true,
            judul: 'Judul Pilihan Penjual',
        );

        $template = $this->service->templateSampul($konten, $pref);

        expect($template['tinggi'])->toBe(1920)
            ->and($template['gaya'])->toBe('pita_samping')
            // Gaya berpanel menyisakan sebagian kanvas untuk warna padat,
            // jadi fotonya tidak lagi memenuhi seluruh bidang.
            ->and($template['foto']['kotak'][2])->toBeLessThan(SampulService::LEBAR)
            // Judul tulisan penjual mengalahkan hasil pemisahan teks draf.
            ->and(teksTemplate($template))->toContain('Judul Pilihan Penjual');

        $pil = pilTemplate($template);

        expect(implode(' ', $pil))
            ->toContain('Rp 85.000')
            ->toContain('sachet')
            ->toContain($this->umkm->nama);
    });

    it('menghilangkan keterangan yang tidak dipilih penjual', function (): void {
        kontenDijawab(['teks' => 'Judul Sampul']);

        $konten = $this->service->susun(
            $this->umkm,
            TujuanKonten::SampulProduk,
            NadaKonten::Informatif,
            $this->produk,
        );

        $template = $this->service->templateSampul($konten, new PreferensiSampul(
            gaya: GayaSampul::TiraiBawah,
            palet: PaletSampul::Hijau,
            rasio: RasioSampul::Persegi,
            tampilkanHarga: false,
            tampilkanBahan: false,
            tampilkanToko: false,
        ));

        expect(pilTemplate($template))->toBeEmpty();
    });

    it('menyimpan preferensi sampul dan membacanya kembali', function (): void {
        kontenDijawab(['teks' => 'Judul Sampul']);

        $konten = $this->service->susun(
            $this->umkm,
            TujuanKonten::SampulProduk,
            NadaKonten::Informatif,
            $this->produk,
        );

        $this->service->simpanPreferensi($konten, new PreferensiSampul(
            gaya: GayaSampul::SorotTengah,
            palet: PaletSampul::Malam,
            rasio: RasioSampul::Potret,
            tampilkanHarga: false,
            tampilkanBahan: true,
            tampilkanToko: false,
        ));

        $dibaca = $this->service->preferensi($konten->fresh());

        expect($dibaca->gaya)->toBe(GayaSampul::SorotTengah)
            ->and($dibaca->palet)->toBe(PaletSampul::Malam)
            ->and($dibaca->rasio)->toBe(RasioSampul::Potret)
            ->and($dibaca->tampilkanHarga)->toBeFalse();
    });

    it('jatuh ke bawaan ketika preferensi tersimpan tidak dikenali', function (): void {
        // Baris lama, atau baris yang gayanya sudah dihapus dari enum.
        // Yang tidak boleh terjadi adalah halaman gagal muat karenanya.
        $pref = PreferensiSampul::dariArray([
            'gaya' => 'gaya_yang_sudah_tidak_ada',
            'palet' => null,
            'rasio' => 'raksasa',
        ]);

        expect($pref->gaya)->toBe(GayaSampul::TiraiBawah)
            ->and($pref->palet)->toBe(PaletSampul::Hijau)
            ->and($pref->rasio)->toBe(RasioSampul::Persegi);
    });

    it('menolak sampul yang bukan PNG berukuran benar', function (): void {
        $konten = KontenPromosi::create([
            'umkm_id' => $this->umkm->id,
            'produk_id' => $this->produk->id,
            'tujuan' => TujuanKonten::SampulProduk,
            'nada' => NadaKonten::Hangat,
            'hasil_teks' => 'Judul',
        ]);

        $sampul = app(SampulService::class);

        expect(fn () => $sampul->simpan($konten, 'data:image/jpeg;base64,AAAA'))
            ->toThrow(AturanBisnisException::class);

        // PNG sah tapi ukurannya salah juga ditolak: sampul yang rasionya
        // meleset akan terpotong sembarangan di kanal sosial.
        $kecil = 'data:image/png;base64,'.base64_encode(pngKotak(16, 16));

        expect(fn () => $sampul->simpan($konten, $kecil))
            ->toThrow(AturanBisnisException::class);
    });

    it('memeriksa ukuran terhadap rasio yang dipilih, bukan satu ukuran tetap', function (): void {
        $konten = KontenPromosi::create([
            'umkm_id' => $this->umkm->id,
            'produk_id' => $this->produk->id,
            'tujuan' => TujuanKonten::SampulProduk,
            'nada' => NadaKonten::Hangat,
            'hasil_teks' => 'Judul',
            'preferensi_sampul' => ['rasio' => 'cerita'],
        ]);

        $sampul = app(SampulService::class);

        // Persegi tidak lagi otomatis sah: draf ini sedang menyusun 9:16.
        $persegi = 'data:image/png;base64,'.base64_encode(pngKotak(1080, 1080));

        expect(fn () => $sampul->simpan($konten, $persegi))
            ->toThrow(AturanBisnisException::class);

        $cerita = 'data:image/png;base64,'.base64_encode(pngKotak(1080, 1920));

        expect($sampul->simpan($konten, $cerita)->sampul_path)->not->toBeNull();
    });

    it('menyimpan sampul berukuran benar dan mengganti yang lama', function (): void {
        $konten = KontenPromosi::create([
            'umkm_id' => $this->umkm->id,
            'produk_id' => $this->produk->id,
            'tujuan' => TujuanKonten::SampulProduk,
            'nada' => NadaKonten::Hangat,
            'hasil_teks' => 'Judul',
        ]);

        $sampul = app(SampulService::class);
        $dataUri = 'data:image/png;base64,'.base64_encode(pngKotak(1080, 1080));

        $pertama = $sampul->simpan($konten, $dataUri);

        // Disalin sebagai string: simpan() memperbarui atribut pada
        // instansi yang diserahkan padanya, jadi membandingkan objeknya
        // setelah pemanggilan kedua akan selalu tampak sama.
        $pathLama = $pertama->sampul_path;
        Storage::disk('public')->assertExists($pathLama);

        $kedua = $sampul->simpan($pertama, $dataUri);

        expect($kedua->sampul_path)->not->toBe($pathLama);
        Storage::disk('public')->assertMissing($pathLama);
        Storage::disk('public')->assertExists($kedua->sampul_path);
    });
});

describe('pemakaian nyata', function (): void {
    it('memisahkan draf yang dibuat dari draf yang benar-benar dipakai', function (): void {
        kontenDijawab(['teks' => 'Caption contoh.', 'hashtag' => ['daurulang']]);

        $konten = $this->service->susun(
            $this->umkm,
            TujuanKonten::Instagram,
            NadaKonten::Hangat,
            $this->produk,
        );

        expect($konten->dipakai)->toBeFalse();

        $this->service->tandaiDipakai($konten);

        expect($konten->fresh()->dipakai)->toBeTrue()
            ->and(KontenPromosi::query()->dipakai()->count())->toBe(1);
    });
});

describe('halaman panel', function (): void {
    it('menyusun draf lewat komponen dan langsung membukanya', function (): void {
        kontenDijawab(['teks' => 'Caption dari komponen.', 'hashtag' => ['daurulang']]);

        Livewire::actingAs($this->pemilik)
            ->test(KomponenKonten::class)
            ->set('tujuan', 'instagram')
            ->set('nada', 'hangat')
            ->set('produkId', (string) $this->produk->id)
            ->call('susun')
            ->assertSet('kontenId', fn (?int $id): bool => $id !== null)
            ->assertSee('Dibuat dengan bantuan AI');

        expect(KontenPromosi::sole()->umkm_id)->toBe($this->umkm->id);
    });

    it('merender penyusun sampul dan menyimpan pilihan tampilan begitu diubah', function (): void {
        ProdukFoto::create([
            'produk_id' => $this->produk->id,
            'path' => 'produk/asli.jpg',
            'urutan' => 1,
            'is_utama' => true,
        ]);

        $konten = KontenPromosi::create([
            'umkm_id' => $this->umkm->id,
            'produk_id' => $this->produk->id,
            'tujuan' => TujuanKonten::SampulProduk,
            'nada' => NadaKonten::Hangat,
            'hasil_teks' => "Tas Sachet Anyaman\nDijahit tangan.",
        ]);

        $halaman = Livewire::actingAs($this->pemilik)
            ->test(KomponenKonten::class)
            ->call('bukaDraf', $konten->id)
            ->assertSee('Tata letak')
            ->assertSee('Bentuk kanvas')
            // Lencana pelabelan tetap wajib tampil di layar penyusun.
            ->assertSee('Dibuat dengan bantuan AI');

        $halaman->set('gaya', 'sorot_tengah')
            ->set('rasio', 'cerita')
            ->set('tampilkanToko', true);

        // Pilihan tersimpan tanpa tombol simpan terpisah: penjual di sini
        // sedang mencoba-coba tampilan, bukan mengisi formulir.
        $pref = app(KontenPromosiService::class)->preferensi($konten->fresh());

        expect($pref->gaya)->toBe(GayaSampul::SorotTengah)
            ->and($pref->rasio)->toBe(RasioSampul::Cerita)
            ->and($pref->tampilkanToko)->toBeTrue();
    });

    it('menolak membuka draf milik toko lain', function (): void {
        $tokoLain = Umkm::factory()->create();

        $milikOrangLain = KontenPromosi::create([
            'umkm_id' => $tokoLain->id,
            'tujuan' => TujuanKonten::Instagram,
            'nada' => NadaKonten::Hangat,
            'hasil_teks' => 'Rahasia toko sebelah',
        ]);

        // Query komponen dikunci pada `umkm_id` pengguna, sehingga draf
        // toko lain tidak pernah ditemukan sama sekali, bukan ditemukan
        // lalu ditolak.
        expect(fn () => Livewire::actingAs($this->pemilik)
            ->test(KomponenKonten::class)
            ->call('bukaDraf', $milikOrangLain->id))
            ->toThrow(ModelNotFoundException::class);
    });
});

/** PNG polos berukuran bebas, dipakai sebagai muatan uji. */
function pngKotak(int $lebar, int $tinggi): string
{
    $gambar = imagecreatetruecolor($lebar, $tinggi);
    imagefill($gambar, 0, 0, imagecolorallocate($gambar, 5, 125, 93));

    ob_start();
    imagepng($gambar);
    $biner = (string) ob_get_clean();

    imagedestroy($gambar);

    return $biner;
}

/**
 * Seluruh teks yang benar-benar tergambar pada sebuah template.
 *
 * Spesifikasinya berupa daftar lapisan, jadi teks tidak lagi berada di
 * satu kunci tetap. Menelusurinya di sini membuat pengujian bertanya
 * "apa yang tampil di sampulnya", bukan "apa isi kunci nomor sekian",
 * pertanyaan pertama tetap sah ketika tata letaknya berubah.
 *
 * @param  array<string, mixed>  $template
 * @return array<int, string>
 */
function teksTemplate(array $template): array
{
    $teks = [];

    foreach ($template['lapisan'] ?? [] as $lapis) {
        foreach ($lapis['blok'] ?? [] as $blok) {
            if (($blok['jenis'] ?? null) === 'teks' && is_string($blok['isi'] ?? null)) {
                $teks[] = $blok['isi'];
            }
        }
    }

    return $teks;
}

/**
 * Seluruh pil keterangan pada sebuah template.
 *
 * @param  array<string, mixed>  $template
 * @return array<int, string>
 */
function pilTemplate(array $template): array
{
    $pil = [];

    foreach ($template['lapisan'] ?? [] as $lapis) {
        foreach ($lapis['blok'] ?? [] as $blok) {
            if (($blok['jenis'] ?? null) === 'pil') {
                $pil = array_merge($pil, $blok['isi'] ?? []);
            }
        }
    }

    return $pil;
}
