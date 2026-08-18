<?php

declare(strict_types=1);

namespace App\Services\Laporan;

use App\Enums\StatusLaporan;
use App\Exceptions\AturanBisnisException;
use App\Models\Laporan;
use App\Support\Haversine;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Mendeteksi laporan kembar di titik yang berdekatan.
 *
 * ## Menawarkan, bukan menolak
 *
 * Tumpukan sampah yang sama sering dilaporkan beberapa warga dalam
 * waktu berdekatan. Menolak laporan kedua akan salah dua kali: warga
 * kedua merasa laporannya diabaikan, dan sistem kehilangan sinyal
 * bahwa masalah itu mengganggu lebih dari satu orang.
 *
 * Karena itu duplikat ditawarkan untuk digabung. Laporan tetap masuk,
 * pelapornya tetap tercatat, dan bobot masalahnya justru terlihat.
 *
 * Hanya laporan berstatus aktif yang diperiksa. Tumpukan yang sudah
 * dibersihkan bulan lalu tidak boleh menghalangi laporan tumpukan baru
 * di titik yang sama.
 */
class DuplikatDetectorService
{
    /**
     * Cari kandidat laporan kembar di sekitar sebuah titik.
     *
     * @param  int|null  $abaikanId  Laporan yang dikecualikan, dipakai saat memeriksa ulang laporan yang sudah ada.
     * @return Collection<int, Laporan>
     */
    public function cariKandidat(
        float $latitude,
        float $longitude,
        ?int $abaikanId = null,
        ?int $kategoriId = null,
        int $batas = 5,
    ): Collection {
        $radiusKm = $this->radiusMeter() / 1000;

        $query = Laporan::query()
            ->whereIn('status', StatusLaporan::aktif())
            ->with(['kategori:id,nama,ikon,deskripsi', 'pelapor:id,name,avatar_path', 'foto']);

        if ($abaikanId !== null) {
            $query->whereKeyNot($abaikanId);
        }

        // Kategori dipakai sebagai penyaring tambahan, bukan syarat.
        // Dua warga bisa memilih kategori berbeda untuk tumpukan yang
        // sama, jadi kategori yang cocok diprioritaskan tapi yang tidak
        // cocok tetap ditampilkan.
        if ($kategoriId !== null) {
            $query->orderByRaw('kategori_id = ? desc', [$kategoriId]);
        }

        Haversine::terapkan($query, $latitude, $longitude, $radiusKm);

        return $query->limit($batas)->get();
    }

    /** Apakah ada kandidat kembar di titik ini. */
    public function adaKandidat(float $latitude, float $longitude, ?int $abaikanId = null): bool
    {
        return $this->cariKandidat($latitude, $longitude, $abaikanId, batas: 1)->isNotEmpty();
    }

    /**
     * Gabungkan sebuah laporan ke laporan induk.
     *
     * Yang digabung tidak dihapus. Barisnya tetap ada dengan status
     * `digabung` dan penunjuk ke induknya, sehingga pelapornya tetap
     * bisa melacak dan tetap terhitung sebagai warga yang melapor.
     */
    public function gabungkan(Laporan $laporan, Laporan $induk): Laporan
    {
        if ($laporan->is($induk)) {
            throw AturanBisnisException::karena('Laporan tidak bisa digabungkan ke dirinya sendiri.');
        }

        if ($induk->is_duplikat) {
            throw AturanBisnisException::karena(
                'Laporan tujuan sudah merupakan gabungan dari laporan lain. Pilih laporan induknya.',
            );
        }

        if ($laporan->status->isFinal()) {
            throw AturanBisnisException::karena(
                'Laporan yang sudah '.$laporan->status->label().' tidak bisa digabungkan.',
            );
        }

        return DB::transaction(function () use ($laporan, $induk): Laporan {
            // Laporan yang sebelumnya sudah digabung ke laporan ini ikut
            // dipindahkan ke induk baru, supaya tidak terbentuk rantai
            // duplikat bertingkat yang menyulitkan penelusuran.
            Laporan::query()
                ->where('duplikat_of_id', $laporan->id)
                ->update(['duplikat_of_id' => $induk->id]);

            $laporan->update([
                'is_duplikat' => true,
                'duplikat_of_id' => $induk->id,
                'status' => StatusLaporan::Digabung,
            ]);

            return $laporan->fresh();
        });
    }

    /**
     * Jumlah laporan yang digabung ke sebuah induk.
     *
     * Angka ini layak ditampilkan ke penanggung jawab: sepuluh warga
     * melaporkan titik yang sama adalah informasi prioritas, bukan
     * sekadar catatan teknis.
     */
    public function jumlahGabungan(Laporan $induk): int
    {
        return $induk->duplikat()->count();
    }

    private function radiusMeter(): int
    {
        return (int) config('resikita.laporan.radius_duplikat_m', 50);
    }
}
