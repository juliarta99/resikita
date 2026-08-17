<?php

declare(strict_types=1);

namespace App\Support;

use App\Exceptions\AturanBisnisException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Penyimpanan berkas unggahan yang gagalnya tidak diam-diam.
 *
 * ## Masalah yang ditutup berkas ini
 *
 * `UploadedFile::store()` mengembalikan `string|false`. Nilai `false`
 * muncul ketika penulisan ke disk gagal, disk penuh, izin folder
 * salah, atau kredensial penyimpanan awan kedaluwarsa.
 *
 * Tanpa pemeriksaan, `false` mengalir apa adanya ke kolom basis data
 * dan tersimpan sebagai string kosong. Yang terjadi kemudian jauh lebih
 * buruk daripada galat: laporan tercatat tanpa foto buktinya, pengajuan
 * wilayah tersimpan tanpa surat kewenangannya, dan semuanya tampak
 * berhasil di layar. Kegagalannya baru ketahuan berbulan-bulan
 * kemudian, ketika ada yang mencoba membuka berkas yang tidak pernah
 * ada.
 *
 * Di sini kegagalan itu berhenti sebagai galat yang terlihat, dengan
 * pesan yang bisa dipahami pengguna, dan jejaknya masuk log untuk
 * ditelusuri operator.
 */
final class Unggahan
{
    /**
     * Simpan satu berkas dan pastikan hasilnya benar-benar sebuah path.
     *
     * @param  string  $disk  `public` untuk yang boleh dibuka umum,
     *                        `local` untuk yang tidak, surat kewenangan
     *                        dan lampiran tindak lanjut memuat nama dan
     *                        nomor pejabat, jadi keduanya tidak pernah
     *                        di disk publik.
     *
     * @throws AturanBisnisException
     */
    public static function simpan(
        UploadedFile|TemporaryUploadedFile $berkas,
        string $folder,
        string $disk = 'public',
    ): string {
        $path = $berkas->store($folder, $disk);

        if (! is_string($path) || $path === '') {
            Log::error('Unggahan gagal disimpan.', [
                'folder' => $folder,
                'disk' => $disk,
                'nama_asli' => $berkas->getClientOriginalName(),
            ]);

            throw AturanBisnisException::karena(
                'Berkas gagal disimpan di peladen. Coba unggah ulang beberapa saat lagi.',
                500,
            );
        }

        return $path;
    }

    /**
     * Simpan beberapa berkas sekaligus.
     *
     * Gagal pada satu berkas menggagalkan seluruhnya, dan berkas yang
     * telanjur tersimpan dibuang. Laporan dengan tiga foto yang hanya
     * dua di antaranya tersimpan lebih menyesatkan daripada laporan
     * yang jelas-jelas gagal dikirim.
     *
     * @param  array<int, UploadedFile|TemporaryUploadedFile>  $berkas
     * @return array<int, string>
     *
     * @throws AturanBisnisException
     */
    public static function simpanBanyak(array $berkas, string $folder, string $disk = 'public'): array
    {
        $tersimpan = [];

        try {
            foreach ($berkas as $satu) {
                $tersimpan[] = self::simpan($satu, $folder, $disk);
            }
        } catch (AturanBisnisException $e) {
            foreach ($tersimpan as $path) {
                Storage::disk($disk)->delete($path);
            }

            throw $e;
        }

        return $tersimpan;
    }
}
