<?php

declare(strict_types=1);

namespace App\Services\Konten;

use App\Enums\GayaSampul;
use App\Enums\PaletSampul;
use App\Exceptions\AturanBisnisException;
use App\Models\KontenPromosi;
use App\Models\Produk;
use App\Support\PreferensiSampul;
use App\Support\Rupiah;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Sampul produk untuk unggahan media sosial UMKM.
 *
 * ## Foto aslinya tidak pernah diganti
 *
 * Ini aturan yang tidak boleh dilanggar (CLAUDE.md 10.3). Sampul disusun
 * sebagai **komposisi template di atas foto produk yang benar-benar
 * difoto penjualnya**, bingkai, bidang warna, dan teks ditempelkan di
 * atasnya. Resikita tidak pernah membuat citra produk dari model
 * penghasil gambar.
 *
 * Alasannya perlindungan konsumen, bukan estetika. Marketplace produk
 * daur ulang menjual barang yang wujudnya memang tidak seragam: satu
 * tas dari sachet bekas tidak pernah persis sama dengan tas berikutnya.
 * Gambar hasil generate akan selalu terlihat lebih rapi daripada
 * barangnya, dan selisih itu ditanggung pembeli.
 *
 * ## Spesifikasi, bukan nama gaya
 *
 * Yang dikirim ke peramban bukan "gambarlah gaya pita samping",
 * melainkan daftar lapisan siap gambar: bidang ini di koordinat ini
 * dengan warna ini, tumpukan teks ini berjangkar di titik ini. Peramban
 * tidak pernah menerjemahkan sebuah gaya menjadi tata letak.
 *
 * Bedanya penting. Kalau peramban ikut memutuskan, aturan tata letak
 * bercabang dua: satu versi di PHP untuk apa pun yang butuh diperiksa,
 * satu versi di JavaScript untuk yang benar-benar tergambar. Begitu
 * keduanya berbeda sedikit saja, yang dilihat penjual berbeda dari yang
 * dianggap peladen tersimpan.
 *
 * Satu hal memang harus diselesaikan di peramban: berapa baris sebuah
 * kalimat setelah dipatahkan pada lebar tertentu. Itu bergantung pada
 * metrik huruf yang hanya ada setelah Plus Jakarta Sans termuat. Karena
 * itu tumpukan teks dikirim dengan titik jangkar dan arah tumbuhnya,
 * peramban mengukur, lalu mengalirkan; ia tidak memilih posisi.
 *
 * ## Label AI tidak digambar ke sampulnya
 *
 * `is_ai_generated` tetap selalu true dan lencananya tetap tampil di
 * layar penyusun (CLAUDE.md 10.3). Yang tidak lagi dilakukan adalah
 * membakar tulisan itu ke dalam berkas gambarnya. Yang disusun mesin
 * adalah teksnya, dan teks itu selalu disunting dan disetujui penjual
 * sebelum dipakai; foto produknya sendiri tidak pernah dibuat mesin.
 */
class SampulService
{
    /**
     * Lebar kanvas, tetap pada ketiga rasio.
     *
     * Tingginya mengikuti RasioSampul, jadi tidak ada lagi konstanta
     * tunggal untuk ukuran gambar.
     */
    public const LEBAR = 1080;

    /**
     * Batas ukuran berkas hasil.
     *
     * Kanvas 1080x1920 berisi foto asli menghasilkan PNG yang jauh lebih
     * besar daripada kanvas persegi, jadi batasnya dihitung dari bentuk
     * terbesar yang bisa dipilih, bukan dari yang paling umum.
     */
    private const MAKS_BYTE = 8 * 1024 * 1024;

    /**
     * Spesifikasi lapisan yang digambar peramban.
     *
     * @return array<string, mixed>
     */
    public function template(
        Produk $produk,
        string $judul,
        string $pendukung,
        ?PreferensiSampul $preferensi = null,
    ): array {
        $pref = $preferensi ?? PreferensiSampul::bawaan();

        $foto = $produk->fotoUtama ?? $produk->foto->first();

        if ($foto === null) {
            throw AturanBisnisException::karena(
                'Produk ini belum punya foto. Unggah foto produk asli lebih dulu, '
                .'sampul disusun di atas foto Anda, bukan dari gambar buatan.',
            );
        }

        $lebar = $pref->rasio->lebar();
        $tinggi = $pref->rasio->tinggi();

        return [
            'lebar' => $lebar,
            'tinggi' => $tinggi,
            'foto_url' => $foto->url(),
            'gaya' => $pref->gaya->value,
            'palet' => $pref->palet->value,
            'rasio' => $pref->rasio->value,
        ] + $this->susunGaya($pref, $produk, $judul, $pendukung, $lebar, $tinggi);
    }

    /**
     * Bidang foto dan daftar lapisan untuk satu gaya.
     *
     * @return array{foto: array<string, mixed>, lapisan: array<int, array<string, mixed>>}
     */
    private function susunGaya(
        PreferensiSampul $pref,
        Produk $produk,
        string $judul,
        string $pendukung,
        int $lebar,
        int $tinggi,
    ): array {
        $palet = $pref->palet;
        $tepi = (int) round($lebar * 0.06);
        $pil = $this->pil($pref, $produk);

        return match ($pref->gaya) {
            GayaSampul::TiraiBawah => $this->gayaTiraiBawah(
                $palet, $judul, $pendukung, $pil, $lebar, $tinggi, $tepi,
            ),
            GayaSampul::KartuMengambang => $this->gayaKartuMengambang(
                $palet, $judul, $pendukung, $pil, $lebar, $tinggi, $tepi,
            ),
            GayaSampul::PitaSamping => $this->gayaPitaSamping(
                $palet, $judul, $pendukung, $pil, $lebar, $tinggi, $tepi,
            ),
            GayaSampul::BlokAtas => $this->gayaBlokAtas(
                $palet, $judul, $pendukung, $pil, $lebar, $tinggi, $tepi,
            ),
            GayaSampul::BingkaiPenuh => $this->gayaBingkaiPenuh(
                $palet, $judul, $pendukung, $pil, $lebar, $tinggi, $tepi,
            ),
            GayaSampul::SorotTengah => $this->gayaSorotTengah(
                $palet, $judul, $pendukung, $pil, $lebar, $tinggi, $tepi,
            ),
        };
    }

    // ------------------------------------------------------------------
    // Gaya
    // ------------------------------------------------------------------

    /** Foto memenuhi kanvas, teks berdiri di atas gradasi gelap dari bawah. */
    private function gayaTiraiBawah(
        PaletSampul $palet,
        string $judul,
        string $pendukung,
        array $pil,
        int $lebar,
        int $tinggi,
        int $tepi,
    ): array {
        $garis = (int) round($tinggi * 0.012);

        return [
            'foto' => ['kotak' => [0, 0, $lebar, $tinggi]],
            'lapisan' => [
                [
                    'jenis' => 'gradasi',
                    'kotak' => [0, (int) round($tinggi * 0.42), $lebar, (int) round($tinggi * 0.58)],
                    'dari' => $this->rgba($palet->gelap(), 0),
                    'ke' => $this->rgba($palet->gelap(), 0.94),
                ],
                [
                    'jenis' => 'kotak',
                    'kotak' => [0, $tinggi - $garis, $lebar, $garis],
                    'warna' => $palet->utama(),
                ],
                $this->tumpukan(
                    x: $tepi,
                    y: $tinggi - $garis - (int) round($tepi * 0.9),
                    lebarMaks: $lebar - $tepi * 2,
                    jangkar: 'bawah',
                    blok: $this->blokTeks(
                        $judul, $pendukung, $pil,
                        ukuranJudul: $this->ukuran($lebar - $tepi * 2, 0.098, 40, 82),
                        warnaTeks: $palet->teksGelap(),
                        warnaPil: $this->rgba($palet->utama(), 0.92),
                        warnaTeksPil: '#FFFFFF',
                    ),
                ),
            ],
        ];
    }

    /** Teks dalam kartu membulat setengah tembus pandang di atas foto. */
    private function gayaKartuMengambang(
        PaletSampul $palet,
        string $judul,
        string $pendukung,
        array $pil,
        int $lebar,
        int $tinggi,
        int $tepi,
    ): array {
        $sisip = (int) round($tepi * 0.7);

        return [
            'foto' => ['kotak' => [0, 0, $lebar, $tinggi]],
            'lapisan' => [
                [
                    'jenis' => 'kotak',
                    'kotak' => [0, 0, $lebar, $tinggi],
                    'warna' => $this->rgba($palet->gelap(), 0.18),
                ],
                $this->tumpukan(
                    x: $tepi + $sisip,
                    y: $tinggi - $tepi - $sisip,
                    lebarMaks: $lebar - ($tepi + $sisip) * 2,
                    jangkar: 'bawah',
                    blok: $this->blokTeks(
                        $judul, $pendukung, $pil,
                        ukuranJudul: $this->ukuran($lebar - ($tepi + $sisip) * 2, 0.095, 36, 72),
                        warnaTeks: $palet->teksPanel(),
                        warnaPil: $this->rgba($palet->utama(), 0.16),
                        warnaTeksPil: $palet->teksPanel(),
                        warnaPendukung: $this->rgba($palet->teksPanel(), 0.78),
                    ),
                    latar: [
                        'warna' => $this->rgba($palet->panel(), 0.93),
                        'radius' => (int) round($tepi * 0.6),
                        'sisip' => $sisip,
                    ],
                ),
            ],
        ];
    }

    /** Panel warna di kiri sebagai bidang teks, foto mengisi sisa kanan. */
    private function gayaPitaSamping(
        PaletSampul $palet,
        string $judul,
        string $pendukung,
        array $pil,
        int $lebar,
        int $tinggi,
        int $tepi,
    ): array {
        $panel = (int) round($lebar * 0.42);
        $kolom = $panel - $tepi * 2;

        return [
            // Foto digeser ke kanan panel dan tetap dipotong tengah, jadi
            // pokok fotonya tidak ikut tergeser keluar bidang.
            'foto' => ['kotak' => [$panel, 0, $lebar - $panel, $tinggi]],
            'lapisan' => [
                [
                    'jenis' => 'kotak',
                    'kotak' => [0, 0, $panel, $tinggi],
                    'warna' => $palet->panel(),
                ],
                [
                    'jenis' => 'kotak',
                    'kotak' => [$panel - 10, 0, 10, $tinggi],
                    'warna' => $palet->utama(),
                ],
                $this->tumpukan(
                    x: $tepi,
                    y: (int) round($tinggi / 2),
                    lebarMaks: $kolom,
                    jangkar: 'tengah',
                    blok: $this->blokTeks(
                        $judul, $pendukung, $pil,
                        ukuranJudul: $this->ukuran($kolom, 0.15, 32, 64),
                        warnaTeks: $palet->teksPanel(),
                        warnaPil: $this->rgba($palet->teksPanel(), 0.18),
                        warnaTeksPil: $palet->teksPanel(),
                        warnaPendukung: $this->rgba($palet->teksPanel(), 0.8),
                        barisJudulMaks: 4,
                    ),
                ),
            ],
        ];
    }

    /** Judul di blok warna atas, foto mengisi bidang bawah. */
    private function gayaBlokAtas(
        PaletSampul $palet,
        string $judul,
        string $pendukung,
        array $pil,
        int $lebar,
        int $tinggi,
        int $tepi,
    ): array {
        $blok = (int) round($tinggi * 0.34);

        return [
            'foto' => ['kotak' => [0, $blok, $lebar, $tinggi - $blok]],
            'lapisan' => [
                [
                    'jenis' => 'kotak',
                    'kotak' => [0, 0, $lebar, $blok],
                    'warna' => $palet->panel(),
                ],
                [
                    'jenis' => 'kotak',
                    'kotak' => [0, $blok - 10, $lebar, 10],
                    'warna' => $palet->utama(),
                ],
                $this->tumpukan(
                    x: $tepi,
                    y: (int) round(($blok - 10) / 2),
                    lebarMaks: $lebar - $tepi * 2,
                    jangkar: 'tengah',
                    blok: $this->blokTeks(
                        $judul, $pendukung, $pil,
                        ukuranJudul: $this->ukuran($lebar - $tepi * 2, 0.082, 34, 68),
                        warnaTeks: $palet->teksPanel(),
                        warnaPil: $this->rgba($palet->teksPanel(), 0.18),
                        warnaTeksPil: $palet->teksPanel(),
                        warnaPendukung: $this->rgba($palet->teksPanel(), 0.8),
                        barisJudulMaks: 2,
                        barisPendukungMaks: 2,
                    ),
                ),
            ],
        ];
    }

    /** Foto berbingkai tebal, teks berdiri di pita bawah. */
    private function gayaBingkaiPenuh(
        PaletSampul $palet,
        string $judul,
        string $pendukung,
        array $pil,
        int $lebar,
        int $tinggi,
        int $tepi,
    ): array {
        $rangka = (int) round($lebar * 0.04);
        $pita = (int) round($tinggi * 0.26);
        $tinggiFoto = $tinggi - $pita - $rangka;

        return [
            'foto' => ['kotak' => [$rangka, $rangka, $lebar - $rangka * 2, $tinggiFoto - $rangka]],
            'lapisan' => [
                // Bingkai digambar sebagai empat bidang di sekeliling
                // foto, bukan satu bidang penuh di belakangnya: foto
                // sudah tergambar lebih dulu, dan menimpanya akan
                // menutupinya.
                ['jenis' => 'kotak', 'kotak' => [0, 0, $lebar, $rangka], 'warna' => $palet->panel()],
                ['jenis' => 'kotak', 'kotak' => [0, 0, $rangka, $tinggi], 'warna' => $palet->panel()],
                ['jenis' => 'kotak', 'kotak' => [$lebar - $rangka, 0, $rangka, $tinggi], 'warna' => $palet->panel()],
                ['jenis' => 'kotak', 'kotak' => [0, $tinggiFoto - $rangka, $lebar, $pita + $rangka], 'warna' => $palet->panel()],
                [
                    'jenis' => 'kotak',
                    'kotak' => [$rangka, $tinggiFoto - $rangka, $lebar - $rangka * 2, 8],
                    'warna' => $palet->utama(),
                ],
                $this->tumpukan(
                    x: $tepi,
                    y: $tinggiFoto - $rangka + (int) round(($pita + $rangka) / 2),
                    lebarMaks: $lebar - $tepi * 2,
                    jangkar: 'tengah',
                    blok: $this->blokTeks(
                        $judul, $pendukung, $pil,
                        ukuranJudul: $this->ukuran($lebar - $tepi * 2, 0.072, 32, 60),
                        warnaTeks: $palet->teksPanel(),
                        warnaPil: $this->rgba($palet->teksPanel(), 0.18),
                        warnaTeksPil: $palet->teksPanel(),
                        warnaPendukung: $this->rgba($palet->teksPanel(), 0.8),
                        barisJudulMaks: 2,
                        barisPendukungMaks: 2,
                    ),
                ),
            ],
        ];
    }

    /** Seluruh foto diredupkan, teks besar berdiri rata tengah. */
    private function gayaSorotTengah(
        PaletSampul $palet,
        string $judul,
        string $pendukung,
        array $pil,
        int $lebar,
        int $tinggi,
        int $tepi,
    ): array {
        $kolom = $lebar - $tepi * 3;

        return [
            'foto' => ['kotak' => [0, 0, $lebar, $tinggi]],
            'lapisan' => [
                [
                    'jenis' => 'kotak',
                    'kotak' => [0, 0, $lebar, $tinggi],
                    'warna' => $this->rgba($palet->gelap(), 0.62),
                ],
                [
                    'jenis' => 'kotak',
                    'kotak' => [$tepi, $tepi, (int) round($lebar * 0.16), 8],
                    'warna' => $palet->utama(),
                ],
                $this->tumpukan(
                    x: (int) round($lebar / 2),
                    y: (int) round($tinggi / 2),
                    lebarMaks: $kolom,
                    jangkar: 'tengah',
                    rata: 'tengah',
                    blok: $this->blokTeks(
                        $judul, $pendukung, $pil,
                        ukuranJudul: $this->ukuran($kolom, 0.115, 44, 96),
                        warnaTeks: $palet->teksGelap(),
                        warnaPil: $this->rgba($palet->utama(), 0.92),
                        warnaTeksPil: '#FFFFFF',
                        barisJudulMaks: 4,
                    ),
                ),
            ],
        ];
    }

    // ------------------------------------------------------------------
    // Penyusun bersama
    // ------------------------------------------------------------------

    /**
     * Isi tumpukan teks: baris pil, judul, lalu kalimat pendukung.
     *
     * @param  array<int, string>  $pil
     * @return array<int, array<string, mixed>>
     */
    private function blokTeks(
        string $judul,
        string $pendukung,
        array $pil,
        int $ukuranJudul,
        string $warnaTeks,
        string $warnaPil,
        string $warnaTeksPil,
        ?string $warnaPendukung = null,
        int $barisJudulMaks = 3,
        int $barisPendukungMaks = 3,
    ): array {
        $blok = [];

        if ($pil !== []) {
            $blok[] = [
                'jenis' => 'pil',
                'isi' => $pil,
                'ukuran' => max(20, (int) round($ukuranJudul * 0.34)),
                'berat' => '600',
                'warna' => $warnaTeksPil,
                'warna_latar' => $warnaPil,
                'jarak' => 14,
            ];
        }

        if ($judul !== '') {
            $blok[] = [
                'jenis' => 'teks',
                'isi' => $judul,
                'ukuran' => $ukuranJudul,
                'berat' => '800',
                'warna' => $warnaTeks,
                'baris_maks' => $barisJudulMaks,
                'tinggi_baris' => 1.16,
            ];
        }

        if ($pendukung !== '') {
            $blok[] = [
                'jenis' => 'teks',
                'isi' => $pendukung,
                'ukuran' => max(22, (int) round($ukuranJudul * 0.46)),
                'berat' => '400',
                'warna' => $warnaPendukung ?? $this->rgba($warnaTeks, 0.86),
                'baris_maks' => $barisPendukungMaks,
                'tinggi_baris' => 1.34,
            ];
        }

        return $blok;
    }

    /**
     * Keterangan pendek yang dipilih penjual untuk ikut tampil.
     *
     * Bahan baku sengaja bisa ditampilkan: pada produk daur ulang, asal
     * bahannya justru bagian yang paling menjelaskan nilai barangnya, dan
     * itu tidak terbaca dari foto.
     *
     * @return array<int, string>
     */
    private function pil(PreferensiSampul $pref, Produk $produk): array
    {
        $pil = [];

        if ($pref->tampilkanBahan && filled($produk->bahan_baku)) {
            $pil[] = 'Dari '.Str::lower((string) $produk->bahan_baku);
        }

        if ($pref->tampilkanHarga) {
            $pil[] = Rupiah::format($produk->harga);
        }

        if ($pref->tampilkanToko && $produk->umkm !== null) {
            $pil[] = $produk->umkm->nama;
        }

        return array_map(
            static fn (string $teks): string => Str::limit($teks, 34),
            $pil,
        );
    }

    /**
     * Satu tumpukan teks berjangkar.
     *
     * @param  array<int, array<string, mixed>>  $blok
     * @param  array<string, mixed>|null  $latar
     * @return array<string, mixed>
     */
    private function tumpukan(
        int $x,
        int $y,
        int $lebarMaks,
        string $jangkar,
        array $blok,
        string $rata = 'kiri',
        ?array $latar = null,
        int $jarak = 20,
    ): array {
        return array_filter([
            'jenis' => 'tumpukan',
            'x' => $x,
            'y' => $y,
            'lebar_maks' => $lebarMaks,
            'jangkar' => $jangkar,
            'rata' => $rata,
            'jarak' => $jarak,
            'latar' => $latar,
            'blok' => $blok,
        ], static fn (mixed $nilai): bool => $nilai !== null);
    }

    /**
     * Ukuran huruf yang mengikuti lebar kolomnya.
     *
     * Judul dengan ukuran tetap akan tampak wajar di kanvas persegi lalu
     * memenuhi seluruh panel sempit pada gaya pita samping. Karena itu
     * ukurannya dihitung dari lebar bidang tempat teks itu berdiri, lalu
     * dijepit supaya tidak pernah terlalu kecil untuk dibaca di ponsel
     * maupun terlalu besar sampai satu kata memenuhi satu baris.
     */
    private function ukuran(int $lebarKolom, float $rasio, int $minimum, int $maksimum): int
    {
        return (int) round(max($minimum, min($maksimum, $lebarKolom * $rasio)));
    }

    /** Ubah hex atau rgba menjadi rgba dengan alfa tertentu. */
    private function rgba(string $warna, float $alfa): string
    {
        if (! preg_match('/^#([0-9a-f]{6})$/i', $warna, $cocok)) {
            return $warna;
        }

        [$r, $g, $b] = sscanf($cocok[1], '%2x%2x%2x');

        return sprintf('rgba(%d, %d, %d, %.3F)', $r, $g, $b, $alfa);
    }

    // ------------------------------------------------------------------
    // Penyimpanan
    // ------------------------------------------------------------------

    /**
     * Simpan hasil gambar dari peramban ke `konten_promosi.sampul_path`.
     *
     * Ukurannya diperiksa terhadap preferensi yang tersimpan pada draf,
     * bukan terhadap satu ukuran tetap. Kalau tidak, kanvas apa pun yang
     * kebetulan sah menurut salah satu rasio akan lolos meski penjual
     * sedang menyusun rasio yang lain.
     *
     * @param  string  $dataUri  data:image/png;base64,...
     */
    public function simpan(KontenPromosi $konten, string $dataUri): KontenPromosi
    {
        $biner = $this->bacaDataUri($dataUri);

        $ukuran = @getimagesizefromstring($biner);

        if ($ukuran === false || $ukuran[2] !== IMAGETYPE_PNG) {
            throw AturanBisnisException::karena('Berkas sampul tidak dikenali sebagai gambar PNG.');
        }

        $rasio = PreferensiSampul::dariArray($konten->preferensi_sampul)->rasio;

        if ($ukuran[0] !== $rasio->lebar() || $ukuran[1] !== $rasio->tinggi()) {
            throw AturanBisnisException::karena(sprintf(
                'Ukuran sampul %s harus %d kali %d piksel.',
                $rasio->label(),
                $rasio->lebar(),
                $rasio->tinggi(),
            ));
        }

        $path = 'sampul/'.Str::ulid().'.png';

        Storage::disk('public')->put($path, $biner);

        // Sampul lama dibuang supaya penyusunan berulang tidak
        // meninggalkan berkas yang tidak lagi ditunjuk apa pun.
        if ($konten->sampul_path !== null) {
            Storage::disk('public')->delete($konten->sampul_path);
        }

        $konten->update(['sampul_path' => $path]);

        return $konten->fresh();
    }

    /** Ubah data URI menjadi byte mentah, dengan pemeriksaan bentuk. */
    private function bacaDataUri(string $dataUri): string
    {
        if (! preg_match('#^data:image/png;base64,#', $dataUri)) {
            throw AturanBisnisException::karena('Sampul harus dikirim sebagai data URI PNG.');
        }

        $base64 = substr($dataUri, strlen('data:image/png;base64,'));

        // Batas diperiksa sebelum decode: string base64 yang sangat
        // panjang bisa menghabiskan memori peladen sebelum sempat
        // ditolak karena ukurannya.
        if (strlen($base64) > self::MAKS_BYTE * 4 / 3 + 1024) {
            throw AturanBisnisException::karena('Ukuran sampul melebihi 8 MB.');
        }

        $biner = base64_decode($base64, strict: true);

        if ($biner === false || $biner === '') {
            throw AturanBisnisException::karena('Isi sampul tidak dapat dibaca.');
        }

        if (strlen($biner) > self::MAKS_BYTE) {
            throw AturanBisnisException::karena('Ukuran sampul melebihi 8 MB.');
        }

        return $biner;
    }

    /**
     * Pisahkan keluaran model menjadi judul dan kalimat pendukung.
     *
     * Model diminta memisahkan keduanya dengan baris baru, tapi kadang
     * mengirim satu paragraf. Pemisahan diulang di sini supaya template
     * tetap terisi benar apa pun bentuk jawabannya.
     *
     * @return array{judul: string, pendukung: string}
     */
    public function pisahkanTeks(?string $teks): array
    {
        $baris = collect(preg_split('/\R+/', (string) $teks) ?: [])
            ->map(fn (string $b): string => trim($b))
            ->filter()
            ->values();

        if ($baris->isEmpty()) {
            return ['judul' => '', 'pendukung' => ''];
        }

        if ($baris->count() === 1) {
            // Satu paragraf: kalimat pertama menjadi judul, sisanya
            // pendukung. Kalau hanya ada satu kalimat, pendukung kosong
            // dan template menyusut dengan sendirinya.
            $kalimat = preg_split('/(?<=[.!?])\s+/', $baris->first(), 2) ?: [];

            return [
                'judul' => trim($kalimat[0] ?? ''),
                'pendukung' => trim($kalimat[1] ?? ''),
            ];
        }

        return [
            'judul' => $baris->first(),
            'pendukung' => $baris->slice(1)->implode(' '),
        ];
    }
}
