<?php

declare(strict_types=1);

namespace App\Services\Analitik;

use App\Enums\KategoriSampah;
use App\Enums\StatusLaporan;
use App\Enums\StatusSetoran;
use App\Enums\SumberInput;
use App\Models\Artikel;
use App\Models\Laporan;
use App\Models\SetoranSampah;
use App\Models\User;
use App\Services\Wilayah\WilayahScopeService;
use Illuminate\Support\Facades\DB;

/**
 * Angka-angka untuk dasbor pemerintahan dan platform.
 *
 * Setiap query di sini melewati WilayahScopeService. Analitik justru
 * tempat kebocoran lintas daerah paling mudah lolos: sebuah agregat
 * tidak menampilkan baris satu per satu, jadi angka yang mencakup
 * kabupaten tetangga terlihat wajar sampai ada yang membandingkannya
 * dengan data resmi.
 */
class AnalitikService
{
    public function __construct(
        private readonly WilayahScopeService $scope,
    ) {}

    /**
     * Ringkasan laporan dalam cakupan pengguna.
     *
     * @return array{total: int, aktif: int, selesai: int, ditolak: int, rata_respons_jam: ?float}
     */
    public function ringkasanLaporan(User $user, ?string $sejak = null): array
    {
        $query = $this->scope->applyLaporan(Laporan::query(), $user);

        if ($sejak !== null) {
            $query->where('created_at', '>=', $sejak);
        }

        $aktif = StatusLaporan::aktif();

        $agregat = (clone $query)
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when status in ("'.implode('","', $aktif).'") then 1 else 0 end) as aktif')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as selesai', [StatusLaporan::Selesai->value])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as ditolak', [StatusLaporan::Ditolak->value])
            ->selectRaw('avg(case when selesai_at is not null then timestampdiff(minute, created_at, selesai_at) end) as respons_menit')
            ->first();

        $responsMenit = $agregat->respons_menit ?? null;

        return [
            'total' => (int) ($agregat->total ?? 0),
            'aktif' => (int) ($agregat->aktif ?? 0),
            'selesai' => (int) ($agregat->selesai ?? 0),
            'ditolak' => (int) ($agregat->ditolak ?? 0),
            'rata_respons_jam' => $responsMenit !== null ? round((float) $responsMenit / 60, 1) : null,
        ];
    }

    /**
     * Sebaran laporan per status, untuk grafik donat.
     *
     * @return array<string, int>
     */
    public function laporanPerStatus(User $user): array
    {
        $baris = $this->scope->applyLaporan(Laporan::query(), $user)
            ->select('status', DB::raw('count(*) as jumlah'))
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $hasil = [];

        foreach (StatusLaporan::cases() as $status) {
            $hasil[$status->value] = (int) ($baris[$status->value] ?? 0);
        }

        return $hasil;
    }

    /**
     * Tren laporan bulanan.
     *
     * @return array<int, array{periode: string, jumlah: int, selesai: int}>
     */
    public function trenLaporanBulanan(User $user, int $jumlahBulan = 12): array
    {
        return $this->scope->applyLaporan(Laporan::query(), $user)
            ->where('created_at', '>=', now()->subMonths($jumlahBulan)->startOfMonth())
            ->selectRaw("date_format(created_at, '%Y-%m') as periode")
            ->selectRaw('count(*) as jumlah')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as selesai', [StatusLaporan::Selesai->value])
            ->groupBy('periode')
            ->orderBy('periode')
            ->get()
            ->map(fn ($baris): array => [
                'periode' => $baris->periode,
                'jumlah' => (int) $baris->jumlah,
                'selesai' => (int) $baris->selesai,
            ])
            ->all();
    }

    /**
     * Laporan terbanyak per kategori.
     *
     * @return array<int, array{kategori: string, jumlah: int}>
     */
    public function laporanPerKategori(User $user, int $batas = 10): array
    {
        return $this->scope->applyLaporan(Laporan::query(), $user)
            ->join('laporan_kategori', 'laporan.kategori_id', '=', 'laporan_kategori.id')
            ->select('laporan_kategori.nama as kategori', DB::raw('count(*) as jumlah'))
            ->groupBy('laporan_kategori.nama')
            ->orderByDesc('jumlah')
            ->limit($batas)
            ->get()
            ->map(fn ($baris): array => [
                'kategori' => $baris->kategori,
                'jumlah' => (int) $baris->jumlah,
            ])
            ->all();
    }

    /**
     * Sebaran laporan per wilayah anak, untuk membandingkan kinerja
     * antar kabupaten dalam satu provinsi atau antar desa dalam satu
     * kabupaten.
     *
     * @return array<int, array{wilayah: string, jumlah: int, selesai: int}>
     */
    public function laporanPerWilayahAnak(User $user): array
    {
        $tingkat = $user->tingkatKewenangan();
        $tingkatAnak = $tingkat?->child();

        if ($tingkatAnak === null) {
            return [];
        }

        $kolom = match ($tingkatAnak->value) {
            'kabupaten' => 'kabupaten_id',
            'kecamatan' => 'kecamatan_id',
            'desa' => 'desa_id',
            default => null,
        };

        if ($kolom === null) {
            return [];
        }

        return $this->scope->applyLaporan(Laporan::query(), $user)
            ->join('wilayah', "laporan.$kolom", '=', 'wilayah.id')
            ->select('wilayah.nama as wilayah', DB::raw('count(*) as jumlah'))
            ->selectRaw('sum(case when laporan.status = ? then 1 else 0 end) as selesai', [StatusLaporan::Selesai->value])
            ->groupBy('wilayah.nama')
            ->orderByDesc('jumlah')
            ->get()
            ->map(fn ($baris): array => [
                'wilayah' => $baris->wilayah,
                'jumlah' => (int) $baris->jumlah,
                'selesai' => (int) $baris->selesai,
            ])
            ->all();
    }

    /**
     * Titik laporan untuk peta sebaran.
     *
     * Hanya kolom yang benar-benar dipakai penanda peta yang diambil.
     * Peta bisa memuat ribuan titik sekaligus, dan membawa deskripsi
     * lengkap tiap laporan ke sisi klien adalah pemborosan yang paling
     * terasa di jaringan seluler.
     *
     * @return array<int, array<string, mixed>>
     */
    public function titikPetaLaporan(User $user, int $batas = 1000): array
    {
        return $this->scope->applyLaporan(Laporan::query(), $user)
            ->select('id', 'tiket', 'judul', 'latitude', 'longitude', 'status', 'kategori_id')
            ->with('kategori:id,nama,ikon')
            ->whereIn('status', StatusLaporan::aktif())
            ->limit($batas)
            ->get()
            ->map(fn (Laporan $l): array => [
                'id' => $l->id,
                'tiket' => $l->tiket,
                'judul' => $l->judul,
                'latitude' => (float) $l->latitude,
                'longitude' => (float) $l->longitude,
                'status' => $l->status->value,
                'warna' => $l->status->warna(),
                'kategori' => $l->kategori?->nama,
            ])
            ->all();
    }

    /**
     * Dampak lingkungan terukur: berat sampah yang berhasil dialihkan
     * dari TPA lewat bank sampah, beserta nilai ekonominya.
     *
     * @return array{total_berat_kg: float, total_nilai: int, jumlah_transaksi: int, per_kategori: array<int, array{kategori: string, berat: float}>}
     */
    public function dampakBankSampah(User $user, ?string $sejak = null): array
    {
        $wilayahIds = $this->scope->idDalamCakupan($user);

        $query = SetoranSampah::query()->where('status', StatusSetoran::Selesai);

        // Role platform melihat angka nasional; role berwilayah dibatasi
        // pada bank sampah dalam cakupannya.
        if (! ($user->roleUtama()?->isPlatform() ?? false)) {
            if ($wilayahIds === []) {
                return ['total_berat_kg' => 0.0, 'total_nilai' => 0, 'jumlah_transaksi' => 0, 'per_kategori' => []];
            }

            $query->whereHas('bankSampah', fn ($q) => $q->whereIn('wilayah_id', $wilayahIds));
        }

        if ($sejak !== null) {
            // Kolom disebut lengkap dengan nama tabelnya: query yang sama
            // dipakai ulang di bawah dengan join ke setoran_sampah_item,
            // dan `created_at` telanjang menjadi ambigu di sana.
            $query->where('setoran_sampah.created_at', '>=', $sejak);
        }

        $agregat = (clone $query)
            ->selectRaw('count(*) as jumlah, sum(total_berat) as berat, sum(total_nilai) as nilai')
            ->first();

        $perKategori = (clone $query)
            ->join('setoran_sampah_item', 'setoran_sampah.id', '=', 'setoran_sampah_item.setoran_id')
            ->join('bank_sampah_harga', 'setoran_sampah_item.harga_id', '=', 'bank_sampah_harga.id')
            ->select('bank_sampah_harga.kategori', DB::raw('sum(setoran_sampah_item.berat) as berat'))
            ->groupBy('bank_sampah_harga.kategori')
            ->get()
            ->map(fn ($baris): array => [
                'kategori' => KategoriSampah::tryFrom($baris->kategori)?->label() ?? $baris->kategori,
                'berat' => round((float) $baris->berat, 2),
            ])
            ->all();

        return [
            'total_berat_kg' => round((float) ($agregat->berat ?? 0), 2),
            'total_nilai' => (int) ($agregat->nilai ?? 0),
            'jumlah_transaksi' => (int) ($agregat->jumlah ?? 0),
            'per_kategori' => $perKategori,
        ];
    }

    /**
     * Pemakaian fitur suara.
     *
     * Angka inilah yang mengubah klaim inklusivitas dari pernyataan di
     * proposal menjadi sesuatu yang bisa ditunjukkan.
     *
     * @return array{laporan_suara: int, laporan_total: int, persen_laporan_suara: float, artikel_didengarkan: int, artikel_dilihat: int}
     */
    public function pemakaianFiturSuara(?string $sejak = null): array
    {
        $laporan = Laporan::query();
        $artikel = Artikel::query();

        if ($sejak !== null) {
            $laporan->where('created_at', '>=', $sejak);
        }

        $total = (clone $laporan)->count();
        $suara = (clone $laporan)->where('deskripsi_sumber', SumberInput::Suara)->count();

        return [
            'laporan_suara' => $suara,
            'laporan_total' => $total,
            'persen_laporan_suara' => $total > 0 ? round($suara / $total * 100, 1) : 0.0,
            'artikel_didengarkan' => (int) $artikel->sum('didengarkan'),
            'artikel_dilihat' => (int) Artikel::query()->sum('dilihat'),
        ];
    }
}
