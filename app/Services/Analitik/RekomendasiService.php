<?php

declare(strict_types=1);

namespace App\Services\Analitik;

use App\Exceptions\AturanBisnisException;
use App\Models\RekomendasiAi;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\Integration\GeminiService;
use App\Support\Rupiah;

/**
 * Rekomendasi prioritas untuk pemerintah wilayah.
 *
 * ## Model tidak pernah menghitung
 *
 * Seluruh angka disusun lebih dulu oleh AnalitikService, yang membacanya
 * dari basis data lewat WilayahScopeService. Model hanya menerima angka
 * yang sudah pasti dan diminta menafsirkannya. Membiarkan model
 * menghitung sendiri berarti menaruh statistik resmi sebuah daerah di
 * tangan sesuatu yang bisa keliru menjumlah, dan keliru itu tidak akan
 * terlihat, karena hasilnya selalu terdengar meyakinkan.
 *
 * ## Kenapa disimpan
 *
 * Satu rekomendasi per wilayah per bulan disimpan ke `rekomendasi_ai`
 * beserta versi modelnya. Dua alasan: permintaan berikutnya di bulan
 * yang sama tidak perlu membayar panggilan baru, dan ketika sebuah
 * keputusan dinas merujuk rekomendasi ini, isinya masih bisa ditelusuri
 * persis seperti saat dibaca.
 */
class RekomendasiService
{
    public function __construct(
        private readonly AnalitikService $analitik,
        private readonly GeminiService $gemini,
    ) {}

    /** Rekomendasi yang sudah ada untuk periode berjalan, kalau ada. */
    public function terbaru(User $user, ?string $periode = null): ?RekomendasiAi
    {
        if ($user->wilayah_id === null) {
            return null;
        }

        return RekomendasiAi::query()
            ->where('scope_type', Wilayah::class)
            ->where('scope_id', $user->wilayah_id)
            ->periode($periode ?? now()->format('Y-m'))
            ->latest('id')
            ->first();
    }

    /**
     * Susun rekomendasi baru untuk wilayah pengguna.
     *
     * @param  bool  $paksaBaru  Abaikan yang sudah ada di periode ini
     */
    public function untukWilayah(User $user, bool $paksaBaru = false): RekomendasiAi
    {
        if ($user->wilayah_id === null) {
            throw AturanBisnisException::karena(
                'Rekomendasi dibuat berdasarkan wilayah kewenangan, dan akun Anda belum terhubung ke wilayah mana pun.',
            );
        }

        $periode = now()->format('Y-m');

        if (! $paksaBaru && ($ada = $this->terbaru($user, $periode)) !== null) {
            return $ada;
        }

        $ringkasan = $this->analitik->ringkasanLaporan($user, now()->subDays(90)->toDateTimeString());

        if ($ringkasan['total'] === 0) {
            throw AturanBisnisException::karena(
                'Belum ada laporan di wilayah Anda dalam 90 hari terakhir, '
                .'sehingga belum ada yang bisa dianalisis.',
            );
        }

        $konten = $this->gemini->rekomendasi(
            $this->susunKonteks($user, $ringkasan),
            $user->roleUtama()?->label() ?? 'pemerintah daerah',
        );

        return RekomendasiAi::create([
            'scope_type' => Wilayah::class,
            'scope_id' => $user->wilayah_id,
            'periode' => $periode,
            'konten' => $konten,
            'raw_response' => ['model_version' => $this->gemini->modelVersion()],
            'dibuat_oleh' => $user->id,
        ]);
    }

    /**
     * Ubah angka analitik menjadi teks yang bisa dibaca model.
     *
     * @param  array{total: int, aktif: int, selesai: int, ditolak: int, rata_respons_jam: ?float}  $ringkasan
     */
    private function susunKonteks(User $user, array $ringkasan): string
    {
        $wilayah = $user->wilayah?->namaLengkap() ?? 'wilayah ini';
        $dampak = $this->analitik->dampakBankSampah($user, now()->subDays(90)->toDateTimeString());

        $baris = [
            "Wilayah: {$wilayah}",
            'Periode data: 90 hari terakhir',
            '',
            'PENANGANAN LAPORAN',
            "Laporan masuk: {$ringkasan['total']}",
            "Masih berjalan: {$ringkasan['aktif']}",
            "Selesai: {$ringkasan['selesai']}",
            "Ditolak: {$ringkasan['ditolak']}",
            'Rata-rata waktu penyelesaian: '.($ringkasan['rata_respons_jam'] !== null
                ? $ringkasan['rata_respons_jam'].' jam'
                : 'belum ada laporan yang selesai'),
            '',
            'BANK SAMPAH',
            "Sampah teralihkan dari TPA: {$dampak['total_berat_kg']} kg",
            'Nilai yang kembali ke warga: '.Rupiah::format($dampak['total_nilai']),
            "Jumlah transaksi setoran: {$dampak['jumlah_transaksi']}",
        ];

        $perKategori = $this->analitik->laporanPerKategori($user, 5);

        if ($perKategori !== []) {
            $baris[] = '';
            $baris[] = 'KATEGORI LAPORAN TERBANYAK';

            foreach ($perKategori as $item) {
                $baris[] = "{$item['kategori']}: {$item['jumlah']} laporan";
            }
        }

        $perWilayah = $this->analitik->laporanPerWilayahAnak($user);

        if ($perWilayah !== []) {
            $baris[] = '';
            $baris[] = 'SEBARAN PER WILAYAH DI BAWAHNYA';

            foreach (array_slice($perWilayah, 0, 10) as $item) {
                $baris[] = "{$item['wilayah']}: {$item['jumlah']} masuk, {$item['selesai']} selesai";
            }
        }

        return implode("\n", $baris);
    }
}
