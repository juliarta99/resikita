<?php

declare(strict_types=1);

namespace App\Services\Wilayah;

use App\Enums\Role;
use App\Enums\StatusPengajuanWilayah;
use App\Enums\StatusRegistrasiWilayah;
use App\Enums\TingkatWilayah;
use App\Exceptions\AturanBisnisException;
use App\Models\PengajuanWilayah;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\Laporan\LaporanRoutingService;
use App\Services\Laporan\TindakLanjutService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Pendaftaran wilayah ke Resikita.
 *
 * ## Kenapa lewat pengajuan, bukan kolom di tabel pengguna
 *
 * Skema lama mengakui kewenangan pemerintahan dari `users.nip`, sebuah
 * kolom teks bebas yang bisa diisi siapa saja dengan angka apa saja.
 * Untuk sistem yang memberi orang akses ke laporan warga satu kabupaten,
 * itu tidak memadai.
 *
 * Sekarang kewenangan lahir dari berkas: pemohon mengunggah surat bukti,
 * super_admin meninjau, dan baru setelah disetujui akun pemerintahan
 * terbit. Persetujuan itu satu tindakan yang mengubah tiga hal sekaligus
 *, status wilayah, akun pengelolanya, dan nasib laporan lama yang
 * selama ini ditangani fasilitator.
 */
class PengajuanWilayahService
{
    public function __construct(
        private readonly LaporanRoutingService $routing,
        private readonly TindakLanjutService $tindakLanjut,
    ) {}

    /**
     * Ajukan sebuah wilayah untuk bergabung.
     *
     * @param  array<string, mixed>  $data
     */
    public function ajukan(Wilayah $wilayah, array $data): PengajuanWilayah
    {
        if ($wilayah->status_registrasi === StatusRegistrasiWilayah::Terverifikasi) {
            throw AturanBisnisException::karena(
                $wilayah->namaLengkap().' sudah terdaftar di Resikita.',
            );
        }

        if (! $wilayah->tingkat->isTingkatKewenangan()) {
            throw AturanBisnisException::karena(
                'Kecamatan bukan tingkat kewenangan di Resikita. Ajukan pendaftaran melalui kabupaten/kota atau desa/kelurahan.',
            );
        }

        if ($wilayah->pengajuan()->menunggu()->exists()) {
            throw AturanBisnisException::karena(
                'Sudah ada pengajuan untuk wilayah ini yang sedang ditinjau.',
            );
        }

        return DB::transaction(function () use ($wilayah, $data): PengajuanWilayah {
            $pengajuan = PengajuanWilayah::create([
                'wilayah_id' => $wilayah->id,
                'pemohon_nama' => $data['pemohon_nama'],
                'pemohon_jabatan' => $data['pemohon_jabatan'],
                'pemohon_email' => $data['pemohon_email'],
                'pemohon_phone' => $data['pemohon_phone'] ?? null,
                'instansi' => $data['instansi'],
                'surat_path' => $data['surat_path'],
                'status' => StatusPengajuanWilayah::Diajukan,
            ]);

            $wilayah->update(['status_registrasi' => StatusRegistrasiWilayah::Diajukan]);

            return $pengajuan;
        });
    }

    /**
     * Setujui pengajuan.
     *
     * Empat akibat dalam satu transaction:
     *   1. wilayah menjadi terverifikasi
     *   2. akun pemerintahan terbit dengan role sesuai tingkat wilayah
     *   3. skor prioritas perluasan dinolkan
     *   4. laporan lama dari wilayah itu diserahkan dari fasilitator
     *      kepada pemerintah daerah yang baru bergabung
     *
     * Langkah keempat yang paling mudah terlupakan, dan justru paling
     * terasa: tanpa itu, daerah yang baru mendaftar melihat dasbor
     * kosong padahal warganya sudah melapor berbulan-bulan.
     *
     * @return array{pengajuan: PengajuanWilayah, akun: User, laporan_dialihkan: int}
     */
    public function setujui(PengajuanWilayah $pengajuan, User $peninjau): array
    {
        if ($pengajuan->sudahDitinjau()) {
            throw AturanBisnisException::karena(
                'Pengajuan ini sudah '.$pengajuan->status->label().'.',
            );
        }

        if (! $peninjau->hasRole(Role::SuperAdmin->value)) {
            throw AturanBisnisException::tidakBerwenang(
                'Hanya Super Admin yang dapat memverifikasi pengajuan wilayah.',
            );
        }

        return DB::transaction(function () use ($pengajuan, $peninjau): array {
            $wilayah = $pengajuan->wilayah;

            $wilayah->update([
                'status_registrasi' => StatusRegistrasiWilayah::Terverifikasi,
                'terverifikasi_at' => now(),
            ]);

            $akun = $this->terbitkanAkunPemerintahan($pengajuan, $wilayah);

            $pengajuan->update([
                'status' => StatusPengajuanWilayah::Disetujui,
                'ditinjau_oleh' => $peninjau->id,
                'ditinjau_at' => now(),
            ]);

            $this->tindakLanjut->resetSkor($wilayah);

            $dialihkan = $this->routing->serahkanUlangUntukWilayah($wilayah);

            return [
                'pengajuan' => $pengajuan->fresh(),
                'akun' => $akun,
                'laporan_dialihkan' => $dialihkan,
            ];
        });
    }

    /**
     * Tolak pengajuan dengan catatan alasan.
     *
     * Status wilayah dikembalikan ke `belum_terjangkau`, bukan
     * `ditolak`. Yang ditolak adalah berkas pengajuannya, bukan
     * wilayahnya, daerah yang sama harus tetap bisa mengajukan lagi
     * dengan berkas yang diperbaiki.
     */
    public function tolak(PengajuanWilayah $pengajuan, User $peninjau, string $catatan): PengajuanWilayah
    {
        if ($pengajuan->sudahDitinjau()) {
            throw AturanBisnisException::karena(
                'Pengajuan ini sudah '.$pengajuan->status->label().'.',
            );
        }

        if (! $peninjau->hasRole(Role::SuperAdmin->value)) {
            throw AturanBisnisException::tidakBerwenang(
                'Hanya Super Admin yang dapat memverifikasi pengajuan wilayah.',
            );
        }

        if (trim($catatan) === '') {
            throw AturanBisnisException::karena('Alasan penolakan wajib diisi agar pemohon bisa memperbaiki berkasnya.');
        }

        return DB::transaction(function () use ($pengajuan, $peninjau, $catatan): PengajuanWilayah {
            $pengajuan->update([
                'status' => StatusPengajuanWilayah::Ditolak,
                'catatan' => $catatan,
                'ditinjau_oleh' => $peninjau->id,
                'ditinjau_at' => now(),
            ]);

            $pengajuan->wilayah->update([
                'status_registrasi' => StatusRegistrasiWilayah::BelumTerjangkau,
            ]);

            return $pengajuan->fresh();
        });
    }

    /**
     * Terbitkan atau perbarui akun pemerintahan untuk pemohon.
     *
     * Kalau emailnya sudah terdaftar, akun yang ada dipakai kembali dan
     * diberi role baru, bukan ditolak dan bukan dibuat kembar. Orang
     * yang sama bisa saja sudah punya akun masyarakat sebelum menjabat.
     *
     * Kata sandi diisi nilai acak yang tidak pernah dikirimkan ke mana
     * pun. Pemilik akun masuk lewat alur lupa kata sandi, sehingga
     * kredensial pertamanya tidak pernah melintas di email atau
     * WhatsApp.
     */
    private function terbitkanAkunPemerintahan(PengajuanWilayah $pengajuan, Wilayah $wilayah): User
    {
        $role = $this->roleUntukTingkat($wilayah->tingkat);

        $akun = User::withTrashed()->firstWhere('email', $pengajuan->pemohon_email);

        if ($akun === null) {
            $akun = User::create([
                'name' => $pengajuan->pemohon_nama,
                'email' => $pengajuan->pemohon_email,
                'password' => Hash::make(Str::random(40)),
                'phone' => $pengajuan->pemohon_phone,
                'wilayah_id' => $wilayah->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        } else {
            if ($akun->trashed()) {
                $akun->restore();
            }

            $akun->update([
                'wilayah_id' => $wilayah->id,
                'is_active' => true,
                'phone' => $akun->phone ?? $pengajuan->pemohon_phone,
            ]);
        }

        // Role pemerintahan lama dicabut supaya seseorang tidak memegang
        // dua tingkat kewenangan sekaligus setelah berpindah jabatan.
        foreach (Role::pemerintahan() as $rolePemerintahan) {
            if ($rolePemerintahan !== $role->value && $akun->hasRole($rolePemerintahan)) {
                $akun->removeRole($rolePemerintahan);
            }
        }

        if (! $akun->hasRole($role->value)) {
            $akun->assignRole($role->value);
        }

        return $akun->fresh();
    }

    private function roleUntukTingkat(TingkatWilayah $tingkat): Role
    {
        return match ($tingkat) {
            TingkatWilayah::Provinsi => Role::AdminProvinsi,
            TingkatWilayah::Kabupaten => Role::AdminKabupaten,
            TingkatWilayah::Desa => Role::KepalaDesa,
            TingkatWilayah::Kecamatan => throw AturanBisnisException::karena(
                'Kecamatan bukan tingkat kewenangan di Resikita.',
            ),
        };
    }
}
