<?php

declare(strict_types=1);

namespace App\Services\Laporan;

use App\Enums\AlasanRouting;
use App\Enums\Role;
use App\Enums\StatusLaporan;
use App\Enums\StatusRegistrasiWilayah;
use App\Exceptions\AturanBisnisException;
use App\Models\Laporan;
use App\Models\LaporanTindakLanjut;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Pendampingan wilayah yang belum bergabung dengan Resikita.
 *
 * ## Kenapa bagian ini ada
 *
 * Laporan dari wilayah yang belum terjangkau tidak punya pemerintah
 * daerah yang bisa menerimanya. Tanpa mekanisme apa pun, laporan itu
 * berakhir sebagai data mati, warga sudah melapor, tapi tidak ada yang
 * menindaklanjuti, dan pengalaman itu justru menghancurkan kepercayaan.
 *
 * Fasilitator Wilayah menjembatani celah tersebut: mengontak dinas
 * terkait di luar sistem, lalu mencatat hasilnya di sini. Dua manfaatnya
 * berjalan bersamaan. Warga mendapat kepastian bahwa laporannya
 * diteruskan, dan Resikita mengumpulkan bukti konkret untuk mengajak
 * wilayah itu bergabung: "ada 47 laporan warga dari kecamatan Anda,
 * ini catatan komunikasi kami dengan dinas Anda."
 *
 * `wilayah.skor_prioritas` adalah bentuk terukur dari bukti itu.
 */
class TindakLanjutService
{
    /**
     * Catat kontak fasilitator ke dinas atas sebuah laporan.
     */
    public function catat(Laporan $laporan, User $fasilitator, array $data): LaporanTindakLanjut
    {
        if (! $fasilitator->hasRole(Role::FasilitatorWilayah->value)) {
            throw AturanBisnisException::tidakBerwenang(
                'Hanya Fasilitator Wilayah yang dapat mencatat tindak lanjut ke dinas.',
            );
        }

        if ($laporan->alasan_routing !== AlasanRouting::WilayahBelumTerjangkau) {
            throw AturanBisnisException::karena(
                'Laporan ini sudah ditangani pemerintah wilayah, tindak lanjut fasilitator tidak diperlukan.',
            );
        }

        return LaporanTindakLanjut::create([
            'laporan_id' => $laporan->id,
            'fasilitator_id' => $fasilitator->id,
            'nama_dinas' => $data['nama_dinas'],
            'kontak_dinas' => $data['kontak_dinas'] ?? null,
            'tanggal_kontak' => $data['tanggal_kontak'],
            'hasil' => $data['hasil'],
            'lampiran_path' => $data['lampiran_path'] ?? null,
        ]);
    }

    /**
     * Naikkan skor prioritas perluasan untuk wilayah sebuah laporan.
     *
     * Dipanggil sekali saat laporan dibuat dan jatuh ke fasilitator.
     * Kenaikan diterapkan ke seluruh rantai wilayah yang diketahui,
     * desa, kecamatan, kabupaten, provinsi, karena keputusan perluasan
     * bisa diambil di tingkat mana pun, dan yang dibutuhkan adalah
     * gambaran tekanan di setiap tingkat.
     */
    public function naikkanSkorPrioritas(Laporan $laporan): void
    {
        $bobot = (int) config('resikita.wilayah.bobot_skor_prioritas', 1);

        $ids = array_filter([
            $laporan->desa_id,
            $laporan->kecamatan_id,
            $laporan->kabupaten_id,
            $laporan->provinsi_id,
        ]);

        if ($ids === []) {
            return;
        }

        // Wilayah yang sudah terverifikasi dikecualikan: skornya mengukur
        // urgensi untuk diajak bergabung, dan wilayah yang sudah bergabung
        // tidak perlu diajak lagi.
        Wilayah::query()
            ->whereIn('id', $ids)
            ->where('status_registrasi', '!=', StatusRegistrasiWilayah::Terverifikasi)
            ->increment('skor_prioritas', $bobot);
    }

    /**
     * Papan prioritas perluasan wilayah.
     *
     * Diurutkan menurun berdasarkan skor: wilayah dengan tekanan laporan
     * tertinggi yang paling layak didekati lebih dulu.
     *
     * @return LengthAwarePaginator<int, Wilayah>
     */
    public function papanPrioritas(?string $tingkat = null, int $perHalaman = 20): LengthAwarePaginator
    {
        $query = Wilayah::query()
            ->where('status_registrasi', '!=', StatusRegistrasiWilayah::Terverifikasi)
            ->where('skor_prioritas', '>', 0)
            // Induk dimuat utuh: WilayahResource membaca status
            // registrasi dan koordinat induk, dan kolom yang tidak ikut
            // terpilih berubah menjadi galat saat dirender.
            ->with('parent')
            ->prioritasTertinggi();

        if ($tingkat !== null) {
            $query->where('tingkat', $tingkat);
        }

        return $query->paginate($perHalaman);
    }

    /**
     * Laporan yang menunggu pendampingan fasilitator.
     *
     * @return Builder<Laporan>
     */
    public function papanLaporanBelumTerjangkau(?User $fasilitator = null): Builder
    {
        $query = Laporan::query()
            ->belumTerjangkau()
            ->whereIn('status', StatusLaporan::aktif())
            ->untukDaftar()
            ->withCount('tindakLanjut')
            ->latest();

        if ($fasilitator !== null) {
            $query->where('penanggung_jawab_id', $fasilitator->id);
        }

        return $query;
    }

    /**
     * Ringkasan sebuah wilayah untuk bahan pendekatan ke pemerintah
     * daerah: berapa laporan masuk, berapa yang sudah dikontakkan ke
     * dinas, dan sejak kapan.
     *
     * @return array{total_laporan: int, laporan_aktif: int, sudah_ditindaklanjuti: int, laporan_pertama: ?string, skor_prioritas: int}
     */
    public function ringkasanWilayah(Wilayah $wilayah): array
    {
        $kolom = match ($wilayah->tingkat->value) {
            'provinsi' => 'provinsi_id',
            'kabupaten' => 'kabupaten_id',
            'kecamatan' => 'kecamatan_id',
            default => 'desa_id',
        };

        $agregat = Laporan::query()
            ->where($kolom, $wilayah->id)
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when status in (?, ?, ?, ?) then 1 else 0 end) as aktif', StatusLaporan::aktif())
            ->selectRaw('min(created_at) as pertama')
            ->first();

        $ditindaklanjuti = LaporanTindakLanjut::query()
            ->whereHas('laporan', fn (Builder $q) => $q->where($kolom, $wilayah->id))
            ->distinct('laporan_id')
            ->count('laporan_id');

        return [
            'total_laporan' => (int) ($agregat->total ?? 0),
            'laporan_aktif' => (int) ($agregat->aktif ?? 0),
            'sudah_ditindaklanjuti' => $ditindaklanjuti,
            'laporan_pertama' => $agregat->pertama ?? null,
            'skor_prioritas' => $wilayah->skor_prioritas,
        ];
    }

    /**
     * Nol-kan skor prioritas setelah wilayah bergabung.
     *
     * Dipanggil PengajuanWilayahService saat pengajuan disetujui, supaya
     * papan prioritas hanya berisi wilayah yang memang masih perlu
     * didekati.
     */
    public function resetSkor(Wilayah $wilayah): void
    {
        DB::transaction(function () use ($wilayah): void {
            $wilayah->update(['skor_prioritas' => 0]);
        });
    }
}
