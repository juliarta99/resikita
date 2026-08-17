<?php

declare(strict_types=1);

namespace App\Services\Analitik;

use App\Enums\StatusLaporan;
use App\Enums\StatusSetoran;
use App\Enums\SumberInput;
use App\Models\Artikel;
use App\Models\BankSampah;
use App\Models\Laporan;
use App\Models\SetoranSampah;
use App\Models\Tps;
use App\Models\Umkm;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Support\Facades\Cache;

/**
 * Angka nasional untuk halaman publik.
 *
 * ## Kenapa terpisah dari AnalitikService
 *
 * AnalitikService membatasi setiap query lewat WilayahScopeService, dan
 * memang harus begitu, isinya data pemerintahan. Angka di berkas ini
 * justru sengaja tidak dibatasi: yang ditampilkan adalah gambaran
 * nasional untuk pengunjung tanpa akun.
 *
 * Memisahkannya membuat perbedaan itu terlihat di nama kelasnya, bukan
 * tersembunyi sebagai satu parameter di antara belasan method. Kalau
 * keduanya bercampur, cepat atau lambat ada method tanpa scope yang
 * lolos ke dasbor pemerintahan.
 *
 * ## Kenapa di-cache
 *
 * Halaman beranda adalah halaman yang paling sering dibuka orang yang
 * belum punya akun, dan angkanya berupa agregat lintas seluruh tabel.
 * Menghitungnya ulang tiap kunjungan berarti membebani basis data
 * dengan pekerjaan yang jawabannya tidak berubah dalam hitungan menit.
 *
 * Yang tidak boleh di-cache adalah data yang menyangkut uang seseorang
 * atau status laporannya sendiri. Tidak ada di sini.
 */
class StatistikPublikService
{
    /** Berapa lama angka beranda dianggap masih berlaku. */
    private const MENIT_CACHE = 15;

    /**
     * Ringkasan dampak untuk beranda.
     *
     * @return array<string, int|float>
     */
    public function ringkasan(): array
    {
        return Cache::remember('publik.statistik', now()->addMinutes(self::MENIT_CACHE), function (): array {
            $setoran = SetoranSampah::query()->where('status', StatusSetoran::Selesai);

            return [
                'wilayah_bergabung' => Wilayah::query()->terverifikasi()->count(),
                'total_laporan' => Laporan::query()->count(),
                'laporan_selesai' => Laporan::query()->where('status', StatusLaporan::Selesai)->count(),
                'bank_sampah' => BankSampah::query()->aktif()->count(),
                'tps' => Tps::query()->count(),
                'umkm' => Umkm::query()->aktif()->count(),
                'warga' => User::query()->count(),
                'berat_teralihkan_kg' => round((float) (clone $setoran)->sum('total_berat'), 2),
                'nilai_ke_warga' => (int) (clone $setoran)->sum('total_nilai'),
            ];
        });
    }

    /**
     * Bukti pemakaian jalur suara.
     *
     * Ditampilkan terbuka, bukan disimpan sebagai angka internal.
     * Klaim inklusivitas yang tidak bisa dilihat siapa pun sama saja
     * dengan tidak pernah dibuat.
     *
     * @return array<string, int|float>
     */
    public function fiturSuara(): array
    {
        return Cache::remember('publik.suara', now()->addMinutes(self::MENIT_CACHE), function (): array {
            $total = Laporan::query()->count();
            $suara = Laporan::query()->where('deskripsi_sumber', SumberInput::Suara)->count();

            return [
                'laporan_suara' => $suara,
                'persen_laporan_suara' => $total > 0 ? round($suara / $total * 100, 1) : 0.0,
                'artikel_didengarkan' => (int) Artikel::query()->sum('didengarkan'),
            ];
        });
    }

    /**
     * Titik fasilitas untuk peta publik.
     *
     * Hanya TPS dan bank sampah. Titik laporan sengaja tidak ikut:
     * koordinat laporan menunjuk tempat yang bisa jadi halaman rumah
     * seseorang, dan menyebarnya di peta terbuka mengubah alat pelaporan
     * menjadi alat menunjuk tetangga.
     *
     * @return array<int, array<string, mixed>>
     */
    public function titikFasilitas(?float $latitude = null, ?float $longitude = null, int $batas = 300): array
    {
        $tps = Tps::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('wilayah:id,nama,tingkat')
            ->limit($batas)
            ->get()
            ->map(fn (Tps $t): array => [
                'jenis' => $t->jenis->value,
                'nama' => $t->nama,
                'alamat' => $t->alamat,
                'wilayah' => $t->wilayah?->namaLengkap(),
                'latitude' => (float) $t->latitude,
                'longitude' => (float) $t->longitude,
            ]);

        $bank = BankSampah::query()
            ->aktif()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('wilayah:id,nama,tingkat')
            ->limit($batas)
            ->get()
            ->map(fn (BankSampah $b): array => [
                'jenis' => 'bank_sampah',
                'nama' => $b->nama,
                'alamat' => $b->alamat,
                'wilayah' => $b->wilayah?->namaLengkap(),
                'latitude' => (float) $b->latitude,
                'longitude' => (float) $b->longitude,
            ]);

        return $tps->concat($bank)->values()->all();
    }

    /** Buang cache setelah data yang mendasarinya berubah banyak. */
    public function segarkan(): void
    {
        Cache::forget('publik.statistik');
        Cache::forget('publik.suara');
    }
}
