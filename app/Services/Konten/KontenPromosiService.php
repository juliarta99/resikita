<?php

declare(strict_types=1);

namespace App\Services\Konten;

use App\Enums\NadaKonten;
use App\Enums\TujuanKonten;
use App\Exceptions\AturanBisnisException;
use App\Models\KontenPromosi;
use App\Models\Produk;
use App\Models\Umkm;
use App\Services\Integration\GeminiService;
use App\Support\PreferensiSampul;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

/**
 * Asisten Konten UMKM.
 *
 * Menyusun draf caption, deskripsi produk, dan teks sampul untuk pelaku
 * UMKM produk daur ulang. Sebagian besar dari mereka bukan penulis
 * pemasaran, dan produk daur ulang justru menuntut penjelasan lebih,
 * pembeli perlu tahu bahan asalnya sebelum memutuskan.
 *
 * ## Draf, bukan hasil akhir
 *
 * Keluaran di sini selalu bisa disunting sebelum dipakai, dan kolom
 * `dipakai` hanya menyala ketika UMKM benar-benar memakainya. Itu yang
 * membuat fitur ini bisa dinilai dari pemanfaatan nyata, bukan dari
 * berapa kali tombol "buat" ditekan.
 *
 * ## Label AI tidak opsional
 *
 * `is_ai_generated` selalu true dan wajib ditampilkan ke pengguna
 * (CLAUDE.md 10.3). Konten pemasaran yang disusun mesin lalu disajikan
 * seolah tulisan tangan penjualnya adalah bentuk pengelabuan yang
 * paling mudah dilakukan dan paling sulit dibuktikan setelahnya.
 */
class KontenPromosiService
{
    /** Batas draf per UMKM per hari. */
    private const BATAS_HARIAN = 30;

    public function __construct(
        private readonly GeminiService $gemini,
        private readonly SampulService $sampul,
    ) {}

    /**
     * Susun satu draf konten.
     */
    public function susun(Umkm $umkm, TujuanKonten $tujuan, NadaKonten $nada, ?Produk $produk = null): KontenPromosi
    {
        $this->pastikanProdukMilikUmkm($umkm, $produk);
        $this->pastikanKuotaTersisa($umkm);

        if ($produk === null) {
            throw AturanBisnisException::karena('Pilih produk yang ingin dipromosikan lebih dulu.');
        }

        /*
         * Sampul disusun di atas foto produk asli, jadi produk tanpa
         * foto ditolak sejak awal, bukan setelah model dipanggil dan
         * biayanya sudah keluar.
         */
        if ($tujuan->butuhFotoAsli() && $produk->foto->isEmpty()) {
            throw AturanBisnisException::karena(
                'Produk ini belum punya foto. Unggah foto produk asli lebih dulu, '
                .'sampul disusun di atas foto Anda, bukan dari gambar buatan.',
            );
        }

        $mentah = $this->gemini->kontenPromosi([
            'nama' => $produk->nama,
            'deskripsi' => $produk->deskripsi,
            'bahan_baku' => $produk->bahan_baku,
            'harga' => $tujuan === TujuanKonten::Instagram ? $produk->harga : null,
            'umkm' => $umkm->nama,
        ], $tujuan, $nada);

        return KontenPromosi::create([
            'umkm_id' => $umkm->id,
            'produk_id' => $produk->id,
            'tujuan' => $tujuan,
            'nada' => $nada,
            'hasil_teks' => $this->teksValid($mentah['teks'] ?? null),
            'hasil_hashtag' => $tujuan->menghasilkanHashtag()
                ? $this->hashtagValid($mentah['hashtag'] ?? null)
                : null,
            'is_ai_generated' => true,
            'model_version' => $this->gemini->modelVersion(),
            'raw_response' => $mentah,
            'dipakai' => false,
        ]);
    }

    /** Simpan suntingan UMKM atas draf. */
    public function perbaruiTeks(KontenPromosi $konten, string $teks, ?array $hashtag = null): KontenPromosi
    {
        $bersih = $this->teksValid($teks);

        if ($bersih === null) {
            throw AturanBisnisException::karena('Teks konten tidak boleh kosong.');
        }

        $konten->update([
            'hasil_teks' => $bersih,
            'hasil_hashtag' => $konten->tujuan->menghasilkanHashtag()
                ? $this->hashtagValid($hashtag)
                : $konten->hasil_hashtag,
        ]);

        return $konten->fresh();
    }

    /**
     * Spesifikasi template sampul untuk digambar peramban.
     *
     * Judul dan kalimat pendukung berasal dari pemisahan teks draf,
     * kecuali penjual sudah menuliskan sendiri penggantinya. Penggantian
     * itu diselesaikan di sini, bukan di komponen: keduanya harus
     * bergabung dengan cara yang sama entah spesifikasinya diminta dari
     * panel web atau nanti dari jalur lain.
     */
    public function templateSampul(KontenPromosi $konten, ?PreferensiSampul $preferensi = null): array
    {
        if (! $konten->tujuan->menghasilkanGambar()) {
            throw AturanBisnisException::karena('Konten ini bukan sampul produk.');
        }

        $produk = $konten->produk;

        if ($produk === null) {
            throw AturanBisnisException::karena('Produk untuk sampul ini sudah tidak ada.');
        }

        $pref = $preferensi ?? $this->preferensi($konten);
        $bagian = $this->sampul->pisahkanTeks($konten->hasil_teks);

        return $this->sampul->template(
            $produk,
            $pref->judul ?? $bagian['judul'],
            $pref->pendukung ?? $bagian['pendukung'],
            $pref,
        );
    }

    /** Preferensi tampilan sampul yang tersimpan pada draf. */
    public function preferensi(KontenPromosi $konten): PreferensiSampul
    {
        return PreferensiSampul::dariArray($konten->preferensi_sampul);
    }

    /**
     * Simpan pilihan tampilan sampul.
     *
     * Disimpan terpisah dari berkas gambarnya supaya penjual yang
     * menutup halaman di tengah penyusunan tidak kehilangan pilihannya,
     * dan supaya draf berikutnya bisa dibuka dengan gaya yang sama.
     */
    public function simpanPreferensi(KontenPromosi $konten, PreferensiSampul $preferensi): KontenPromosi
    {
        if (! $konten->tujuan->menghasilkanGambar()) {
            throw AturanBisnisException::karena('Konten ini bukan sampul produk.');
        }

        $konten->update(['preferensi_sampul' => $preferensi->toArray()]);

        return $konten->fresh();
    }

    public function simpanSampul(KontenPromosi $konten, string $dataUri): KontenPromosi
    {
        if (! $konten->tujuan->menghasilkanGambar()) {
            throw AturanBisnisException::karena('Konten ini bukan sampul produk.');
        }

        return $this->sampul->simpan($konten, $dataUri);
    }

    /**
     * Tandai konten benar-benar dipakai.
     *
     * Dipisahkan dari penyusunan karena inilah angka yang bermakna.
     * Jumlah draf yang dibuat hanya mengukur rasa penasaran; jumlah yang
     * dipakai mengukur kegunaan.
     */
    public function tandaiDipakai(KontenPromosi $konten, bool $dipakai = true): KontenPromosi
    {
        $konten->update(['dipakai' => $dipakai]);

        return $konten->fresh();
    }

    public function hapus(KontenPromosi $konten): void
    {
        if ($konten->sampul_path !== null) {
            Storage::disk('public')->delete($konten->sampul_path);
        }

        $konten->delete();
    }

    /** Riwayat draf milik satu UMKM. */
    public function riwayat(Umkm $umkm, ?TujuanKonten $tujuan = null, int $perHalaman = 10): LengthAwarePaginator
    {
        return KontenPromosi::query()
            ->where('umkm_id', $umkm->id)
            ->when($tujuan !== null, fn ($q) => $q->tujuan($tujuan))
            ->with('produk:id,nama,slug')
            ->latest('id')
            ->paginate($perHalaman);
    }

    /**
     * Sisa kuota hari ini.
     *
     * Kuota ada karena tiap draf adalah panggilan berbayar ke penyedia
     * model. Tanpa batas, satu akun yang menekan tombol berulang kali
     * menghabiskan anggaran seluruh platform.
     */
    public function sisaKuota(Umkm $umkm): int
    {
        return max(0, self::BATAS_HARIAN - $this->dipakaiHariIni($umkm));
    }

    private function dipakaiHariIni(Umkm $umkm): int
    {
        return KontenPromosi::query()
            ->where('umkm_id', $umkm->id)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
    }

    private function pastikanKuotaTersisa(Umkm $umkm): void
    {
        if ($this->dipakaiHariIni($umkm) >= self::BATAS_HARIAN) {
            throw AturanBisnisException::karena(sprintf(
                'Batas %d draf per hari sudah tercapai. Coba lagi besok.',
                self::BATAS_HARIAN,
            ), 429);
        }
    }

    private function pastikanProdukMilikUmkm(Umkm $umkm, ?Produk $produk): void
    {
        if ($produk !== null && $produk->umkm_id !== $umkm->id) {
            throw AturanBisnisException::tidakBerwenang('Produk itu bukan milik toko Anda.');
        }
    }

    private function teksValid(mixed $nilai): ?string
    {
        if (! is_string($nilai)) {
            return null;
        }

        $teks = trim($nilai);

        return $teks === '' ? null : mb_substr($teks, 0, 5000);
    }

    /**
     * Rapikan tagar dari keluaran model.
     *
     * Model kadang menyertakan tanda pagar, kadang tidak, dan sesekali
     * menyisipkan spasi di tengah. Penyeragaman dilakukan di sini supaya
     * tampilan dan penyalinan ke media sosial selalu sama bentuknya.
     *
     * @return array<int, string>
     */
    private function hashtagValid(mixed $nilai): array
    {
        if (! is_array($nilai)) {
            return [];
        }

        return collect($nilai)
            ->filter(fn ($t): bool => is_string($t))
            ->map(fn (string $t): string => '#'.preg_replace('/[^\p{L}\p{N}_]/u', '', $t))
            ->filter(fn (string $t): bool => mb_strlen($t) > 1)
            ->unique()
            ->take(12)
            ->values()
            ->all();
    }
}
