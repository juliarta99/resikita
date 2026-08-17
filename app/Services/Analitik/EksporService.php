<?php

declare(strict_types=1);

namespace App\Services\Analitik;

use App\Exports\TableExport;
use App\Models\Laporan;
use App\Models\User;
use App\Services\Wilayah\WilayahScopeService;
use App\Support\Rupiah;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ekspor tabel untuk pemerintah wilayah dan bank sampah.
 *
 * ## Cakupan tetap berlaku pada berkas unduhan
 *
 * Query ekspor melewati `WilayahScopeService` persis seperti tampilan di
 * layar. Ini bagian yang paling mudah terlupakan: orang mengingat
 * membatasi apa yang **terlihat**, lalu membiarkan tombol unduh
 * mengambil seluruh tabel. Berkasnya lalu berpindah tangan lewat surel,
 * dan kebocoran lintas daerah itu tidak meninggalkan jejak apa pun di
 * sistem.
 *
 * ## Kenapa hanya satu baris per laporan
 *
 * Berkas ekspor dipakai untuk rekapitulasi dan lampiran laporan dinas,
 * bukan untuk memindahkan basis data. Foto, riwayat penugasan, dan
 * catatan progres sengaja tidak ikut, semuanya tetap ada di panel,
 * tempat kewenangan masih dijaga.
 */
class EksporService
{
    public function __construct(
        private readonly WilayahScopeService $scope,
    ) {}

    /**
     * Rekap laporan dalam cakupan pengguna.
     *
     * @param  callable(Builder<Laporan>): void|null  $penyaring
     *                                                            Penyaring tambahan dari layar pemanggil, supaya berkas yang
     *                                                            diunduh berisi persis apa yang sedang dilihat, bukan
     *                                                            sesuatu yang lebih luas.
     */
    public function laporan(User $pengguna, ?callable $penyaring = null): StreamedResponse
    {
        $query = $this->scope
            ->applyLaporan(Laporan::query(), $pengguna)
            ->with(['kategori:id,nama', 'pelapor:id,name', 'desa:id,nama', 'kecamatan:id,nama', 'kabupaten:id,nama', 'provinsi:id,nama'])
            ->latest('id');

        if ($penyaring !== null) {
            $penyaring($query);
        }

        $baris = $query->limit(10_000)->get()->map(fn (Laporan $l): array => [
            $l->tiket,
            $l->created_at->format('Y-m-d H:i'),
            $l->judul,
            $l->kategori?->nama ?? '',
            $l->status->label(),
            $l->desa?->nama ?? '',
            $l->kecamatan?->nama ?? '',
            $l->kabupaten?->nama ?? '',
            $l->provinsi?->nama ?? '',
            $l->alamat ?? '',
            $l->deskripsi_sumber->label(),
            $l->diverifikasi_at?->format('Y-m-d H:i') ?? '',
            $l->selesai_at?->format('Y-m-d H:i') ?? '',
            $l->waktuResponsJam() !== null ? round($l->waktuResponsJam(), 1) : '',
        ])->all();

        return (new TableExport(
            [
                'Tiket', 'Masuk', 'Judul', 'Kategori', 'Status',
                'Desa', 'Kecamatan', 'Kabupaten', 'Provinsi', 'Alamat',
                'Sumber Deskripsi', 'Diverifikasi', 'Selesai', 'Waktu Respons (jam)',
            ],
            $baris,
            'Laporan',
        ))->download($this->namaBerkas('laporan', $pengguna));
    }

    /**
     * Rekap dampak per wilayah anak, untuk lampiran laporan dinas.
     */
    public function ringkasanWilayah(User $pengguna, AnalitikService $analitik): StreamedResponse
    {
        $baris = collect($analitik->laporanPerWilayahAnak($pengguna))
            ->map(fn (array $w): array => [
                $w['wilayah'],
                $w['jumlah'],
                $w['selesai'],
                $w['jumlah'] > 0 ? round($w['selesai'] / $w['jumlah'] * 100, 1) : 0,
            ])
            ->all();

        $dampak = $analitik->dampakBankSampah($pengguna);

        // Baris ringkasan ditaruh setelah tabel utama, dipisahkan baris
        // kosong, supaya berkasnya tetap bisa dipakai sebagai tabel biasa
        // di pengolah angka tanpa perlu menghapus apa pun lebih dulu.
        $baris[] = ['', '', '', ''];
        $baris[] = ['Total sampah teralihkan (kg)', $dampak['total_berat_kg'], '', ''];
        $baris[] = ['Nilai kembali ke warga', Rupiah::format($dampak['total_nilai']), '', ''];
        $baris[] = ['Jumlah transaksi setoran', $dampak['jumlah_transaksi'], '', ''];

        return (new TableExport(
            ['Wilayah', 'Laporan Masuk', 'Selesai', 'Tingkat Penyelesaian (%)'],
            $baris,
            'Ringkasan Wilayah',
        ))->download($this->namaBerkas('ringkasan', $pengguna));
    }

    /**
     * Nama berkas menyebut wilayah dan tanggalnya.
     *
     * Berkas ekspor berakhir di folder unduhan bersama puluhan berkas
     * lain. `laporan.xls` tidak memberi tahu apa pun tentang isinya
     * seminggu kemudian.
     */
    private function namaBerkas(string $jenis, User $pengguna): string
    {
        $wilayah = $pengguna->wilayah?->nama ?? 'nasional';

        return sprintf(
            'resikita-%s-%s-%s.xls',
            $jenis,
            str($wilayah)->slug()->toString(),
            now()->format('Ymd'),
        );
    }
}
