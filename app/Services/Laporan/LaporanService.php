<?php

declare(strict_types=1);

namespace App\Services\Laporan;

use App\Enums\Role;
use App\Enums\StatusLaporan;
use App\Enums\StatusPenugasan;
use App\Enums\StatusProgres;
use App\Enums\SumberInput;
use App\Exceptions\AturanBisnisException;
use App\Models\Laporan;
use App\Models\LaporanFoto;
use App\Models\LaporanPenugasan;
use App\Models\LaporanProgres;
use App\Models\User;
use App\Services\Wilayah\Data\HasilResolusiWilayah;
use App\Services\Wilayah\WilayahResolverService;
use App\Services\Wilayah\WilayahScopeService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Alur hidup laporan sampah, dari masuk sampai tuntas.
 *
 * Service ini yang dipanggil komponen Livewire maupun controller API.
 * Keduanya memanggil method yang sama persis, sehingga aturan
 * perpindahan status, resolusi wilayah, dan routing tidak mungkin
 * bercabang antara web dan mobile.
 */
class LaporanService
{
    public function __construct(
        private readonly WilayahResolverService $resolver,
        private readonly LaporanRoutingService $routing,
        private readonly DuplikatDetectorService $duplikat,
        private readonly TindakLanjutService $tindakLanjut,
        private readonly WilayahScopeService $scope,
    ) {}

    /**
     * Periksa kemungkinan laporan kembar sebelum pengguna menyimpan.
     *
     * Dipanggil terpisah dari buat() supaya antarmuka bisa menampilkan
     * kandidat dan membiarkan pengguna memilih: gabungkan, atau tetap
     * kirim sebagai laporan tersendiri. Sistem tidak memutuskan sendiri.
     *
     * @return Collection<int, Laporan>
     */
    public function cekDuplikat(float $latitude, float $longitude, ?int $kategoriId = null): Collection
    {
        return $this->duplikat->cariKandidat($latitude, $longitude, kategoriId: $kategoriId);
    }

    /**
     * Buat laporan baru.
     *
     * Seluruh langkah dibungkus satu transaction: resolusi wilayah,
     * penentuan penanggung jawab, penyimpanan foto, dan kenaikan skor
     * prioritas harus berhasil bersama-sama. Laporan tanpa penanggung
     * jawab, atau skor yang naik untuk laporan yang gagal tersimpan,
     * sama-sama meninggalkan data yang tidak konsisten.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $fotoPaths
     */
    public function buat(User $pelapor, array $data, array $fotoPaths = [], ?int $gabungKeId = null): Laporan
    {
        $latitude = (float) $data['latitude'];
        $longitude = (float) $data['longitude'];

        $wilayah = $this->resolver->resolve($latitude, $longitude);
        $hasilRouting = $this->routing->tentukanPenanggungJawab($wilayah);

        return DB::transaction(function () use ($pelapor, $data, $fotoPaths, $gabungKeId, $latitude, $longitude, $wilayah, $hasilRouting): Laporan {
            $laporan = $this->simpanDenganTiket([
                'pelapor_id' => $pelapor->id,
                'kategori_id' => $data['kategori_id'],
                'judul' => $data['judul'],
                'deskripsi' => $data['deskripsi'],
                'deskripsi_sumber' => $data['deskripsi_sumber'] ?? SumberInput::Ketik,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'alamat' => $data['alamat'] ?? null,
                'status' => StatusLaporan::Baru,
                ...$wilayah->toKolomLaporan(),
                ...$hasilRouting->toKolomLaporan(),
            ]);

            foreach (array_values($fotoPaths) as $urutan => $path) {
                LaporanFoto::create([
                    'laporan_id' => $laporan->id,
                    'path' => $path,
                    'urutan' => $urutan,
                ]);
            }

            // Skor prioritas hanya naik untuk wilayah yang belum bergabung.
            // Inilah yang mengubah laporan tak tertangani menjadi bukti
            // terukur untuk mengajak daerah itu masuk.
            if ($hasilRouting->butuhPendampingan()) {
                $this->tindakLanjut->naikkanSkorPrioritas($laporan);
            }

            if ($gabungKeId !== null) {
                $induk = Laporan::findOrFail($gabungKeId);
                $this->duplikat->gabungkan($laporan, $induk);
                $laporan->refresh();
            }

            return $laporan->load(['kategori', 'foto', 'desa', 'kabupaten']);
        });
    }

    /**
     * Verifikasi laporan oleh penanggung jawab wilayah.
     */
    public function verifikasi(Laporan $laporan, User $verifikator): Laporan
    {
        $this->pastikanBerwenang($laporan, $verifikator);
        $this->pastikanTransisiSah($laporan, StatusLaporan::Diverifikasi);

        $laporan->update([
            'status' => StatusLaporan::Diverifikasi,
            'diverifikasi_oleh' => $verifikator->id,
            'diverifikasi_at' => now(),
        ]);

        return $laporan;
    }

    /**
     * Tolak laporan, dengan alasan yang wajib diisi.
     */
    public function tolak(Laporan $laporan, User $penolak, string $alasan): Laporan
    {
        $this->pastikanBerwenang($laporan, $penolak);
        $this->pastikanTransisiSah($laporan, StatusLaporan::Ditolak);

        if (trim($alasan) === '') {
            throw AturanBisnisException::karena('Alasan penolakan wajib diisi.');
        }

        return DB::transaction(function () use ($laporan, $penolak, $alasan): Laporan {
            LaporanProgres::create([
                'laporan_id' => $laporan->id,
                'petugas_id' => $penolak->id,
                'catatan' => $alasan,
                'status_progres' => StatusProgres::Selesai,
            ]);

            $laporan->update([
                'status' => StatusLaporan::Ditolak,
                'diverifikasi_oleh' => $penolak->id,
                'diverifikasi_at' => now(),
            ]);

            return $laporan;
        });
    }

    /**
     * Tugaskan petugas untuk menangani laporan.
     *
     * Petugas harus berada dalam cakupan wilayah penugas, pemerintah
     * kabupaten tidak boleh menugaskan petugas milik kabupaten lain.
     */
    public function tugaskan(Laporan $laporan, User $petugas, User $penugas, ?string $catatan = null): LaporanPenugasan
    {
        $this->pastikanBerwenang($laporan, $penugas);
        $this->pastikanTransisiSah($laporan, StatusLaporan::Ditugaskan);

        if (! $petugas->hasRole(Role::Petugas->value)) {
            throw AturanBisnisException::karena('Pengguna yang dipilih bukan petugas.');
        }

        if (! $petugas->is_active) {
            throw AturanBisnisException::karena('Petugas yang dipilih sedang nonaktif.');
        }

        if (! $this->scope->berwenangAtas($penugas, $petugas->wilayah_id)) {
            throw AturanBisnisException::tidakBerwenang(
                'Petugas tersebut berada di luar wilayah kewenangan Anda.',
            );
        }

        return DB::transaction(function () use ($laporan, $petugas, $penugas, $catatan): LaporanPenugasan {
            // Penugasan aktif sebelumnya dibatalkan supaya satu laporan
            // tidak dipegang dua petugas sekaligus.
            $laporan->penugasan()
                ->aktif()
                ->update(['status' => StatusPenugasan::Dibatalkan]);

            $penugasan = LaporanPenugasan::create([
                'laporan_id' => $laporan->id,
                'petugas_id' => $petugas->id,
                'ditugaskan_oleh' => $penugas->id,
                'status' => StatusPenugasan::Ditugaskan,
                'ditugaskan_at' => now(),
                'catatan' => $catatan,
            ]);

            $laporan->update(['status' => StatusLaporan::Ditugaskan]);

            return $penugasan;
        });
    }

    /**
     * Catat progres dari lapangan.
     *
     * Status laporan ikut bergerak mengikuti jenis catatan, sehingga
     * petugas tidak perlu mengubah status secara terpisah, satu
     * tindakan di lapangan, satu pembaruan.
     */
    public function catatProgres(Laporan $laporan, User $petugas, array $data): LaporanProgres
    {
        $penugasan = $laporan->penugasan()
            ->milikPetugas($petugas->id)
            ->aktif()
            ->first();

        if ($penugasan === null) {
            throw AturanBisnisException::tidakBerwenang('Laporan ini tidak ditugaskan kepada Anda.');
        }

        $statusProgres = $data['status_progres'] instanceof StatusProgres
            ? $data['status_progres']
            : StatusProgres::from($data['status_progres']);

        if ($statusProgres->wajibFotoBukti() && empty($data['foto_bukti'])) {
            throw AturanBisnisException::karena('Foto bukti wajib dilampirkan saat menyelesaikan laporan.');
        }

        return DB::transaction(function () use ($laporan, $petugas, $penugasan, $statusProgres, $data): LaporanProgres {
            $progres = LaporanProgres::create([
                'laporan_id' => $laporan->id,
                'petugas_id' => $petugas->id,
                'catatan' => $data['catatan'] ?? null,
                'foto_bukti' => $data['foto_bukti'] ?? null,
                'status_progres' => $statusProgres,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ]);

            $statusPenugasan = $statusProgres === StatusProgres::Selesai
                ? StatusPenugasan::Selesai
                : StatusPenugasan::Dikerjakan;

            $penugasan->update(['status' => $statusPenugasan]);

            $statusLaporan = $statusPenugasan->statusLaporan();

            if ($statusLaporan !== null && $laporan->status->bolehPindahKe($statusLaporan)) {
                $laporan->update([
                    'status' => $statusLaporan,
                    'selesai_at' => $statusLaporan === StatusLaporan::Selesai ? now() : null,
                ]);
            }

            return $progres;
        });
    }

    /**
     * Terbitkan nomor tiket dan simpan laporan.
     *
     * Nomor dibangkitkan acak lalu diuji lewat index unik di basis data,
     * bukan dihitung dari jumlah baris. Menghitung baris rawan menabrak
     * dirinya sendiri ketika dua laporan masuk bersamaan, dan justru
     * jam-jam sibuk itulah yang paling mungkin menghasilkan tabrakan.
     *
     * @param  array<string, mixed>  $atribut
     */
    private function simpanDenganTiket(array $atribut, int $percobaanMaksimal = 5): Laporan
    {
        $prefix = config('resikita.laporan.prefix_tiket', 'RSK');
        $periode = now()->format('Ym');

        for ($percobaan = 1; $percobaan <= $percobaanMaksimal; $percobaan++) {
            $tiket = sprintf('%s-%s-%05d', $prefix, $periode, random_int(0, 99999));

            try {
                return Laporan::create([...$atribut, 'tiket' => $tiket]);
            } catch (QueryException $e) {
                if (! $this->tabrakanTiket($e) || $percobaan === $percobaanMaksimal) {
                    throw $e;
                }
            }
        }

        throw AturanBisnisException::karena('Gagal menerbitkan nomor tiket laporan. Coba lagi.');
    }

    private function tabrakanTiket(QueryException $e): bool
    {
        return ($e->errorInfo[1] ?? null) === 1062
            && str_contains($e->getMessage(), 'tiket');
    }

    /** Penanggung jawab laporan, atau role platform, yang boleh bertindak. */
    /**
     * Pastikan pengguna berwenang menyentuh laporan ini.
     *
     * Kewenangan melekat pada **wilayah**, bukan pada satu akun tertentu.
     * `penanggung_jawab_id` hanya menyebut satu orang yang ditemukan
     * routing saat laporan masuk; sebuah kabupaten bisa punya beberapa
     * akun admin, dan pekerjaan tidak boleh berhenti hanya karena yang
     * kebetulan tertunjuk sedang cuti.
     *
     * Aturan ini sengaja identik dengan LaporanPolicy::memegang().
     * Ketika Policy mengizinkan tapi Service menolak, antarmuka
     * menampilkan tombol yang selalu gagal ketika ditekan, kegagalan
     * yang menyalahkan penggunanya atas keputusan yang tidak pernah ia
     * buat.
     */
    private function pastikanBerwenang(Laporan $laporan, User $user): void
    {
        $role = $user->roleUtama();

        if ($role?->isPlatform() === true) {
            return;
        }

        if ($laporan->penanggung_jawab_id === $user->id) {
            return;
        }

        $wilayahLaporan = match ($user->tingkatKewenangan()?->value) {
            'provinsi' => $laporan->provinsi_id,
            'kabupaten' => $laporan->kabupaten_id,
            'desa' => $laporan->desa_id,
            default => null,
        };

        if ($wilayahLaporan !== null && $this->scope->berwenangAtas($user, $wilayahLaporan)) {
            return;
        }

        throw AturanBisnisException::tidakBerwenang(
            'Laporan ini berada di luar cakupan kewenangan Anda.',
        );
    }

    private function pastikanTransisiSah(Laporan $laporan, StatusLaporan $tujuan): void
    {
        if (! $laporan->status->bolehPindahKe($tujuan)) {
            throw AturanBisnisException::karena(sprintf(
                'Laporan berstatus "%s" tidak bisa diubah menjadi "%s".',
                $laporan->status->label(),
                $tujuan->label(),
            ));
        }
    }

    /**
     * Bantu pemanggil menyusun HasilResolusiWilayah dari laporan yang
     * sudah ada, tanpa perlu tahu nama kolomnya.
     */
    public function wilayahLaporan(Laporan $laporan): HasilResolusiWilayah
    {
        return new HasilResolusiWilayah(
            desaId: $laporan->desa_id,
            kecamatanId: $laporan->kecamatan_id,
            kabupatenId: $laporan->kabupaten_id,
            provinsiId: $laporan->provinsi_id,
        );
    }
}
