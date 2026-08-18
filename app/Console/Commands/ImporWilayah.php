<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TingkatWilayah;
use App\Models\Wilayah;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Impor data wilayah administrasi resmi dari berkas CSV.
 *
 * ## Kenapa perintah, bukan seeder
 *
 * Data lengkapnya sekitar 91.000 baris dan berubah setiap kali ada
 * pemekaran atau penggabungan daerah. Menempelkannya ke dalam berkas PHP
 * berarti mengubah kode setiap kali Kemendagri memutakhirkan lampiran,
 * dan menaruh salah ketik manusia di antara 91.000 angka yang tidak akan
 * pernah dibaca ulang siapa pun.
 *
 * Sebagai perintah, pemutakhiran tahunan cukup dijalankan ulang dengan
 * berkas baru. Sifatnya idempoten: menjalankan dua kali tidak
 * menggandakan apa pun.
 *
 * ## Bentuk berkas
 *
 * Dua kolom, tanpa baris judul, persis seperti lampiran Kemendagri:
 *
 *     11,ACEH
 *     11.01,KAB. ACEH SELATAN
 *     11.01.01,BAKONGAN
 *     11.01.01.2001,KEUDE BAKONGAN
 *
 * Tingkat ditentukan dari jumlah segmen kode, bukan dari kolom
 * terpisah, itu sudah tersirat pada kodenya dan menambahkan kolom
 * hanya menciptakan kemungkinan keduanya bertentangan.
 */
class ImporWilayah extends Command
{
    protected $signature = 'wilayah:impor
        {berkas : Path berkas CSV dua kolom (kode,nama)}
        {--kosongkan : Hapus seluruh wilayah lebih dulu}';

    protected $description = 'Impor hierarki wilayah administrasi dari berkas CSV Kemendagri';

    public function handle(): int
    {
        $berkas = $this->argument('berkas');

        if (! is_readable($berkas)) {
            $this->error("Berkas tidak dapat dibaca: {$berkas}");

            return self::FAILURE;
        }

        if ($this->option('kosongkan') && ! $this->kosongkan()) {
            return self::FAILURE;
        }

        $pegangan = fopen($berkas, 'r');

        if ($pegangan === false) {
            $this->error('Berkas gagal dibuka.');

            return self::FAILURE;
        }

        /*
         * Peta kode -> id disimpan di memori supaya tiap baris tidak
         * memicu satu query pencarian induk. Untuk 91.000 baris,
         * selisihnya adalah menit versus jam.
         */
        $petaId = Wilayah::query()->pluck('id', 'kode')->all();

        $masuk = 0;
        $dilewati = 0;
        $batch = [];

        $this->info('Membaca berkas…');

        while (($baris = fgetcsv($pegangan, 0, ',', '"', '\\')) !== false) {
            $kode = trim((string) ($baris[0] ?? ''));
            $nama = trim((string) ($baris[1] ?? ''));

            if ($kode === '' || $nama === '') {
                continue;
            }

            $tingkat = $this->tingkatDari($kode);

            if ($tingkat === null) {
                $dilewati++;

                continue;
            }

            $kodeInduk = $this->kodeInduk($kode);

            // Baris yang induknya belum sempat masuk dilewati dengan
            // laporan, bukan diselipkan tanpa induk. Wilayah tanpa induk
            // membuat pemilih bertingkat berhenti di tengah jalan, dan
            // kegagalan itu jauh lebih sulit ditelusuri daripada satu
            // baris yang jelas-jelas dilaporkan hilang.
            if ($kodeInduk !== null && ! isset($petaId[$kodeInduk])) {
                $this->warn("Induk {$kodeInduk} belum ada, baris {$kode} dilewati.");
                $dilewati++;

                continue;
            }

            $batch[] = [
                'kode' => $kode,
                'nama' => $this->rapikanNama($nama),
                'tingkat' => $tingkat->value,
                'parent_id' => $kodeInduk !== null ? $petaId[$kodeInduk] : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 500) {
                $this->simpanBatch($batch, $petaId);
                $masuk += count($batch);
                $batch = [];
            }
        }

        fclose($pegangan);

        if ($batch !== []) {
            $this->simpanBatch($batch, $petaId);
            $masuk += count($batch);
        }

        $this->newLine();
        $this->info("Selesai. {$masuk} wilayah tersimpan, {$dilewati} baris dilewati.");
        $this->comment('Koordinat pusat wilayah tidak ada di berkas Kemendagri dan tetap kosong.');

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array<string, mixed>>  $batch
     * @param  array<string, int>  $petaId
     */
    private function simpanBatch(array $batch, array &$petaId): void
    {
        Wilayah::query()->upsert($batch, ['kode'], ['nama', 'tingkat', 'parent_id', 'updated_at']);

        // Peta disegarkan hanya untuk kode yang baru masuk, sehingga
        // baris turunannya pada batch berikutnya menemukan induknya.
        $kodeBaru = array_column($batch, 'kode');

        foreach (Wilayah::query()->whereIn('kode', $kodeBaru)->pluck('id', 'kode') as $kode => $id) {
            $petaId[$kode] = $id;
        }

        $this->output->write('.');
    }

    /** Tingkat ditentukan dari jumlah segmen kode. */
    private function tingkatDari(string $kode): ?TingkatWilayah
    {
        return match (substr_count($kode, '.')) {
            0 => TingkatWilayah::Provinsi,
            1 => TingkatWilayah::Kabupaten,
            2 => TingkatWilayah::Kecamatan,
            3 => TingkatWilayah::Desa,
            default => null,
        };
    }

    private function kodeInduk(string $kode): ?string
    {
        $posisi = strrpos($kode, '.');

        return $posisi === false ? null : substr($kode, 0, $posisi);
    }

    /**
     * Rapikan nama dari HURUF BESAR SEMUA menjadi Huruf Kapital Awal.
     *
     * Awalan administratif dibuang: `wilayah.tingkat` sudah menyebutnya,
     * dan `Wilayah::namaLengkap()` menyusun sebutannya kembali saat
     * ditampilkan. Menyimpan "KAB. BADUNG" akan menghasilkan
     * "Kabupaten Kab. Badung" di layar.
     */
    private function rapikanNama(string $nama): string
    {
        $bersih = preg_replace('/^(KAB\.|KABUPATEN|KOTA ADM\.|KOTA)\s+/iu', '', $nama) ?? $nama;

        return mb_convert_case(trim($bersih), MB_CASE_TITLE, 'UTF-8');
    }

    private function kosongkan(): bool
    {
        if (! $this->confirm('Hapus SELURUH data wilayah lebih dulu? Laporan dan akun yang menunjuk wilayah akan kehilangan kaitannya.')) {
            $this->comment('Dibatalkan.');

            return false;
        }

        DB::table('wilayah')->delete();
        $this->warn('Seluruh wilayah dihapus.');

        return true;
    }
}
