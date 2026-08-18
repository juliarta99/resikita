<?php

declare(strict_types=1);

namespace App\Services\Laporan;

use App\Enums\AlasanRouting;
use App\Enums\PenanggungJawabType;
use App\Enums\Role;
use App\Enums\StatusLaporan;
use App\Enums\StatusRegistrasiWilayah;
use App\Models\Laporan;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\Laporan\Data\HasilRouting;
use App\Services\Wilayah\Data\HasilResolusiWilayah;
use Illuminate\Database\Eloquent\Builder;

/**
 * Menentukan siapa yang bertanggung jawab atas sebuah laporan.
 *
 * ## Urutan waterfall, jangan diubah
 *
 *   1. Kabupaten/kota terverifikasi  → admin_kabupaten
 *   2. Provinsi terverifikasi        → admin_provinsi
 *   3. Desa terverifikasi            → kepala_desa
 *   4. Tidak ada satu pun            → fasilitator_wilayah
 *
 * Urutan ini bukan preferensi teknis. UU No. 18 Tahun 2008 menempatkan
 * pengelolaan sampah sebagai urusan wajib pemerintah kabupaten/kota,
 * sehingga kabupaten harus diperiksa lebih dulu meski desa lebih dekat
 * secara geografis. Provinsi mendahului desa karena kewenangan
 * pembinaan dan pengawasan ada di tingkat provinsi ketika kabupaten
 * belum bergabung.
 */
class LaporanRoutingService
{
    /**
     * Jalankan waterfall atas hasil resolusi wilayah.
     *
     * Sebuah tingkat hanya dipilih kalau wilayahnya terverifikasi DAN
     * ada akun aktif yang memegangnya. Wilayah terverifikasi tanpa akun
     * pengelola dilewati, bukan dijadikan tujuan buntu, laporan yang
     * ditujukan kepada pihak yang tidak ada sama saja dengan laporan
     * yang tidak ditangani.
     */
    public function tentukanPenanggungJawab(HasilResolusiWilayah $wilayah): HasilRouting
    {
        $urutan = [
            [$wilayah->kabupatenId, Role::AdminKabupaten, AlasanRouting::KabupatenTerverifikasi],
            [$wilayah->provinsiId, Role::AdminProvinsi, AlasanRouting::ProvinsiTerverifikasi],
            [$wilayah->desaId, Role::KepalaDesa, AlasanRouting::DesaTerverifikasi],
        ];

        foreach ($urutan as [$wilayahId, $role, $alasan]) {
            if ($wilayahId === null || ! $this->wilayahTerverifikasi($wilayahId)) {
                continue;
            }

            $penanggungJawab = $this->pejabatDi($wilayahId, $role);

            if ($penanggungJawab === null) {
                continue;
            }

            return new HasilRouting(
                type: $alasan->penanggungJawab(),
                userId: $penanggungJawab,
                alasan: $alasan,
                wilayahId: $wilayahId,
            );
        }

        return $this->keFasilitator($wilayah);
    }

    /**
     * Terapkan hasil routing ke sebuah laporan.
     *
     * Tidak menyimpan sendiri, pemanggil yang mengendalikan transaction,
     * karena penyimpanan laporan menyentuh beberapa tabel sekaligus.
     */
    public function terapkan(Laporan $laporan, HasilRouting $hasil): Laporan
    {
        $laporan->fill($hasil->toKolomLaporan());

        return $laporan;
    }

    /**
     * Hitung ulang penanggung jawab sebuah laporan.
     *
     * Dipakai setelah sebuah wilayah baru diverifikasi: laporan lama
     * dari wilayah itu masih dipegang fasilitator, dan sekarang bisa
     * diserahkan kepada pemerintah daerah yang baru bergabung.
     */
    public function hitungUlang(Laporan $laporan): HasilRouting
    {
        return $this->tentukanPenanggungJawab(new HasilResolusiWilayah(
            desaId: $laporan->desa_id,
            kecamatanId: $laporan->kecamatan_id,
            kabupatenId: $laporan->kabupaten_id,
            provinsiId: $laporan->provinsi_id,
        ));
    }

    /**
     * Serahkan seluruh laporan tertunda di sebuah wilayah kepada
     * penanggung jawab barunya.
     *
     * Dipanggil PengajuanWilayahService setelah pengajuan disetujui.
     * Hanya laporan aktif yang dipindahkan; laporan yang sudah selesai
     * atau ditolak tetap memegang jejak routing aslinya, karena riwayat
     * penanganan tidak boleh berubah surut.
     *
     * @return int Jumlah laporan yang berpindah tangan.
     */
    public function serahkanUlangUntukWilayah(Wilayah $wilayah): int
    {
        $kolom = match ($wilayah->tingkat->value) {
            'provinsi' => 'provinsi_id',
            'kabupaten' => 'kabupaten_id',
            'desa' => 'desa_id',
            default => null,
        };

        if ($kolom === null) {
            return 0;
        }

        $berpindah = 0;

        Laporan::query()
            ->where($kolom, $wilayah->id)
            ->whereIn('status', StatusLaporan::aktif())
            ->where('alasan_routing', AlasanRouting::WilayahBelumTerjangkau)
            ->chunkById(200, function ($laporanBatch) use (&$berpindah): void {
                foreach ($laporanBatch as $laporan) {
                    $hasil = $this->hitungUlang($laporan);

                    if ($hasil->butuhPendampingan()) {
                        continue;
                    }

                    $this->terapkan($laporan, $hasil)->save();
                    $berpindah++;
                }
            });

        return $berpindah;
    }

    /**
     * Jalur terakhir waterfall.
     *
     * Fasilitator dipilih yang beban laporan aktifnya paling ringan.
     * Tanpa ini, seluruh laporan dari wilayah belum terjangkau,
     * bagian terbesar di masa awal, akan menumpuk di satu orang.
     */
    private function keFasilitator(HasilResolusiWilayah $wilayah): HasilRouting
    {
        $fasilitator = User::query()
            ->whereHas('roles', fn (Builder $q) => $q->where('name', Role::FasilitatorWilayah->value))
            ->where('is_active', true)
            ->withCount([
                'laporanDitangani as beban' => fn (Builder $q) => $q->whereIn('status', StatusLaporan::aktif()),
            ])
            ->orderBy('beban')
            ->orderBy('id')
            ->first();

        return new HasilRouting(
            type: PenanggungJawabType::FasilitatorWilayah,
            userId: $fasilitator?->id,
            alasan: AlasanRouting::WilayahBelumTerjangkau,
            wilayahId: $wilayah->kabupatenId ?? $wilayah->provinsiId ?? $wilayah->desaId,
        );
    }

    private function wilayahTerverifikasi(int $wilayahId): bool
    {
        return Wilayah::query()
            ->whereKey($wilayahId)
            ->where('status_registrasi', StatusRegistrasiWilayah::Terverifikasi)
            ->exists();
    }

    /**
     * Akun aktif yang memegang sebuah wilayah pada role tertentu.
     *
     * Kalau ada lebih dari satu, yang tertua dipilih supaya keputusan
     * routing bisa diulang dan menghasilkan jawaban yang sama.
     */
    private function pejabatDi(int $wilayahId, Role $role): ?int
    {
        return User::query()
            ->where('wilayah_id', $wilayahId)
            ->where('is_active', true)
            ->whereHas('roles', fn (Builder $q) => $q->where('name', $role->value))
            ->orderBy('id')
            ->value('id');
    }
}
