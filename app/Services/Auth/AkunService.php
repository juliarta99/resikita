<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\ChannelNotifikasi;
use App\Enums\Role;
use App\Enums\StatusNotifikasi;
use App\Enums\StatusUmkm;
use App\Exceptions\AturanBisnisException;
use App\Jobs\KirimNotifikasiUmkmJob;
use App\Models\BankSampah;
use App\Models\Dompet;
use App\Models\Notifikasi;
use App\Models\Umkm;
use App\Models\UmkmDompet;
use App\Models\User;
use App\Services\Wilayah\WilayahScopeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Penerbitan akun untuk role yang tidak mendaftar sendiri.
 *
 * Menggantikan AccountProvisioningService dari skema lama, yang punya
 * satu method pembuat untuk tiap role dan seluruhnya terikat hierarki
 * Bali (`createCamat`, `createKepalaDinasBanjar`, `createAdminTps`).
 * Role-role itu sudah tidak ada.
 *
 * Akun pemerintahan tidak diterbitkan di sini, akun itu lahir dari
 * persetujuan pengajuan wilayah, lihat PengajuanWilayahService.
 * Pemisahan itu disengaja: kewenangan atas satu daerah harus berasal
 * dari berkas yang ditinjau, bukan dari formulir tambah pengguna biasa.
 */
class AkunService
{
    public function __construct(
        private readonly WilayahScopeService $scope,
    ) {}

    /**
     * Terbitkan akun petugas lapangan.
     *
     * Petugas terikat pada wilayah pembuatnya. Pemerintah kabupaten
     * membuat petugas untuk kabupatennya, kepala desa untuk desanya,
     * tidak bisa membuat petugas di wilayah yang bukan kewenangannya.
     *
     * @param  array<string, mixed>  $data
     */
    public function buatPetugas(User $pembuat, array $data): User
    {
        if (! $pembuat->can('petugas.kelola')) {
            throw AturanBisnisException::tidakBerwenang('Anda tidak berwenang membuat akun petugas.');
        }

        $wilayahId = $data['wilayah_id'] ?? $pembuat->wilayah_id;

        if ($wilayahId === null) {
            throw AturanBisnisException::karena('Wilayah petugas wajib ditentukan.');
        }

        if (! $this->scope->berwenangAtas($pembuat, (int) $wilayahId)) {
            throw AturanBisnisException::tidakBerwenang(
                'Wilayah tersebut berada di luar kewenangan Anda.',
            );
        }

        return $this->terbitkan(Role::Petugas, [
            ...$data,
            'wilayah_id' => $wilayahId,
        ]);
    }

    /**
     * Terbitkan akun pengelola bank sampah.
     *
     * Satu role `bank_sampah` menggantikan `admin_bank_sampah` dan
     * `petugas_bank_sampah` dari skema lama. Pembedaan itu tidak
     * menambah nilai tata kelola: keduanya bekerja di unit yang sama dan
     * sama-sama melayani setoran warga.
     *
     * @param  array<string, mixed>  $data
     */
    public function buatPengelolaBankSampah(BankSampah $bankSampah, array $data): User
    {
        return $this->terbitkan(Role::BankSampah, [
            ...$data,
            'bank_sampah_id' => $bankSampah->id,
            'wilayah_id' => $data['wilayah_id'] ?? $bankSampah->wilayah_id,
        ]);
    }

    /**
     * Pendaftaran mandiri UMKM lewat halaman publik.
     *
     * Toko berstatus `menunggu` sampai admin memverifikasi,
     * marketplace yang bisa diisi penjual mana pun tanpa peninjauan
     * adalah masalah perlindungan konsumen, bukan sekadar kualitas data.
     *
     * **Akunnya sendiri dibuat aktif.** Yang ditinjau adalah tokonya,
     * bukan hak orangnya untuk memakai Resikita. Membuat akun nonaktif
     * mengunci pendaftar di luar sistem justru pada saat ia paling perlu
     * masuk: untuk melihat statusnya, membaca alasan penolakan, dan
     * memperbaiki datanya. Yang menjaga marketplace adalah gerbang
     * status toko di panel penjual, bukan matinya akun.
     *
     * @param  array<string, mixed>  $data
     * @return array{umkm: Umkm, akun: User}
     */
    public function daftarUmkmMandiri(array $data): array
    {
        // Diperiksa di sini, bukan hanya di formulir. Tanpa penjagaan di
        // lapis ini, pendaftaran dengan email yang sudah terpakai gagal
        // sebagai galat kunci ganda basis data, pesan yang tidak bisa
        // dipahami pendaftar, dan yang bentuknya berbeda antara kanal web
        // dan kanal mana pun yang memanggil method ini nanti.
        if (User::withTrashed()->where('email', $data['pemilik_email'])->exists()) {
            throw AturanBisnisException::karena(
                'Email ini sudah terdaftar di Resikita. Masuk dengan akun yang ada, '
                .'atau daftarkan toko memakai email lain.',
            );
        }

        return DB::transaction(function () use ($data): array {
            $umkm = Umkm::create([
                'nama' => $data['nama'],
                'deskripsi' => $data['deskripsi'] ?? null,
                'alamat' => $data['alamat'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['pemilik_email'],
                'foto' => $data['foto'] ?? null,
                'wilayah_id' => $data['wilayah_id'] ?? null,
                'status' => StatusUmkm::Menunggu,
                'is_verified' => false,
            ]);

            UmkmDompet::create(['umkm_id' => $umkm->id, 'saldo' => 0]);

            $akun = $this->terbitkan(Role::Umkm, [
                'name' => $data['pemilik_nama'],
                'email' => $data['pemilik_email'],
                'phone' => $data['pemilik_phone'] ?? null,
                'password' => $data['password'] ?? null,
                'umkm_id' => $umkm->id,
                'wilayah_id' => $data['wilayah_id'] ?? null,
                'is_active' => true,
            ]);

            return ['umkm' => $umkm, 'akun' => $akun];
        });
    }

    /**
     * Setujui pendaftaran UMKM.
     *
     * Akun pengelolanya ikut diaktifkan. Untuk toko yang mendaftar lewat
     * jalur mandiri akunnya memang sudah aktif sejak awal, jadi baris itu
     * tidak berbuat apa-apa, yang dipulihkannya adalah akun lama yang
     * telanjur dimatikan penolakan sebelum alur ini diperbaiki.
     */
    public function setujuiUmkm(Umkm $umkm, ?User $peninjau = null): Umkm
    {
        if ($umkm->status === StatusUmkm::Aktif) {
            throw AturanBisnisException::karena('UMKM ini sudah aktif.');
        }

        return DB::transaction(function () use ($umkm, $peninjau): Umkm {
            $umkm->update([
                'status' => StatusUmkm::Aktif,
                'is_verified' => true,
                'catatan_verifikasi' => null,
                'ditinjau_oleh' => $peninjau?->id,
                'ditinjau_at' => now(),
            ]);

            User::where('umkm_id', $umkm->id)->update(['is_active' => true]);

            $this->beritahuPengelola(
                $umkm,
                'umkm.disetujui',
                'Toko Anda sudah aktif',
                "Selamat, \"{$umkm->nama}\" lolos verifikasi dan sudah bisa berjualan di marketplace Resikita. "
                .'Lengkapi alamat asal pengiriman, lalu unggah produk pertama Anda.',
                route('umkm.dashboard'),
            );

            return $umkm->fresh();
        });
    }

    /**
     * Tolak pendaftaran UMKM, dengan alasan yang dibaca pemiliknya.
     *
     * Toko berstatus `ditolak` dan alasannya tersimpan di
     * `catatan_verifikasi`. Tidak ada yang dihapus dan tidak ada akun
     * yang dimatikan: baris toko, akun, dompet, dan produknya tetap utuh
     * sehingga pemilik bisa membaca alasannya, memperbaiki datanya, lalu
     * mengajukan ulang lewat `ajukanUlangUmkm()`.
     *
     * **Penolakan tidak lagi mengunci akun.** Sebelumnya method ini
     * menyetel `is_active = false`, yang membuat pemilik ditolak masuk
     * oleh `AuthService` sebelum kata sandinya diperiksa, persis pada
     * saat ia paling perlu masuk untuk tahu apa yang salah. Yang menahan
     * toko bermasalah dari marketplace adalah status tokonya, ditegakkan
     * middleware `toko.terverifikasi` di panel penjual.
     *
     * Alasan wajib diisi. Penolakan tanpa alasan tidak bisa ditindak
     * lanjuti pemilik usaha, dan hanya memindahkan kebuntuan ke
     * percakapan di luar sistem.
     */
    public function tolakUmkm(Umkm $umkm, User $peninjau, string $catatan): Umkm
    {
        if ($umkm->status === StatusUmkm::Ditolak) {
            throw AturanBisnisException::karena('Pendaftaran UMKM ini sudah ditolak.');
        }

        if (trim($catatan) === '') {
            throw AturanBisnisException::karena(
                'Alasan penolakan wajib diisi agar pemilik usaha tahu apa yang harus diperbaiki.',
            );
        }

        return DB::transaction(function () use ($umkm, $peninjau, $catatan): Umkm {
            $umkm->update([
                'status' => StatusUmkm::Ditolak,
                'is_verified' => false,
                'catatan_verifikasi' => trim($catatan),
                'ditinjau_oleh' => $peninjau->id,
                'ditinjau_at' => now(),
            ]);

            $this->beritahuPengelola(
                $umkm,
                'umkm.ditolak',
                'Pendaftaran toko perlu diperbaiki',
                "Pendaftaran \"{$umkm->nama}\" belum bisa disetujui. Alasannya: ".trim($catatan)
                .' Perbaiki data toko Anda, lalu ajukan ulang.',
                route('umkm.status'),
            );

            return $umkm->fresh();
        });
    }

    /**
     * Ajukan ulang pendaftaran setelah ditolak.
     *
     * Tanpa ini penolakan menjadi jalan buntu permanen: satu-satunya
     * jalan keluar adalah mendaftar lagi dengan email lain, yang
     * meninggalkan toko mati di basis data dan memutus riwayatnya.
     *
     * Catatan penolakan sengaja dibiarkan sampai ditinjau ulang, supaya
     * pemilik masih bisa membandingkan perbaikannya dengan permintaan
     * admin selama menunggu.
     *
     * @param  array<string, mixed>  $data
     */
    public function ajukanUlangUmkm(Umkm $umkm, array $data): Umkm
    {
        if ($umkm->status !== StatusUmkm::Ditolak) {
            throw AturanBisnisException::karena(
                'Pengajuan ulang hanya untuk pendaftaran yang ditolak.',
            );
        }

        return DB::transaction(function () use ($umkm, $data): Umkm {
            $this->perbaruiToko($umkm, $data);

            $umkm->update([
                'status' => StatusUmkm::Menunggu,
                'ditinjau_oleh' => null,
                'ditinjau_at' => null,
            ]);

            return $umkm->fresh();
        });
    }

    /**
     * Catat notifikasi untuk seluruh pengelola toko.
     *
     * Baris in-app ditulis di dalam transaction karena ia bagian dari
     * perubahan keadaan, bukan efek samping. Pengiriman ke kanal luar
     * dilepas ke antrean (CLAUDE.md 3 butir 8), admin yang menekan
     * "setujui" tidak boleh ikut menunggu Fonnte.
     *
     * Notifikasi di sini sifatnya pelengkap, bukan satu-satunya jalur:
     * halaman status pendaftaran menampilkan keadaan dan alasan yang sama
     * secara langsung, sehingga antrean yang mati tidak membuat pemilik
     * toko kehilangan informasinya.
     */
    private function beritahuPengelola(
        Umkm $umkm,
        string $tipe,
        string $judul,
        string $pesan,
        ?string $actionUrl = null,
    ): void {
        foreach ($umkm->pengelola as $pengelola) {
            Notifikasi::create([
                'user_id' => $pengelola->id,
                'tipe' => $tipe,
                'channel' => ChannelNotifikasi::Inapp,
                'judul' => $judul,
                'pesan' => $pesan,
                'action_url' => $actionUrl,
                'status' => StatusNotifikasi::Terkirim,
            ]);

            KirimNotifikasiUmkmJob::dispatch($pengelola->id, $judul, $pesan, $actionUrl);
        }
    }

    /**
     * Perbarui profil toko oleh pemiliknya sendiri.
     *
     * Status dan `is_verified` sengaja tidak ikut: keduanya milik proses
     * peninjauan admin. Penjual yang bisa menyunting statusnya sendiri
     * membuat verifikasi kehilangan artinya.
     *
     * Asal pengiriman disimpan berpasangan, id wilayah penyedia ongkir
     * beserta labelnya. Menyimpan id tanpa label membuat halaman penjual
     * hanya bisa menampilkan angka, dan menyimpan label tanpa id membuat
     * ongkir tidak bisa dihitung sama sekali.
     *
     * @param  array<string, mixed>  $data
     */
    public function perbaruiToko(Umkm $umkm, array $data): Umkm
    {
        $atribut = [
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'alamat' => $data['alamat'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
        ];

        if (array_key_exists('foto', $data) && $data['foto'] !== null) {
            $atribut['foto'] = $data['foto'];
        }

        if (array_key_exists('destination_id', $data)) {
            $destinationId = $data['destination_id'] === null ? null : (int) $data['destination_id'];
            $alamatAsal = $data['alamat_asal'] ?? null;

            if ($destinationId !== null && ($alamatAsal === null || trim((string) $alamatAsal) === '')) {
                throw AturanBisnisException::karena(
                    'Alamat asal pengiriman harus dipilih dari hasil pencarian, bukan diketik bebas.',
                );
            }

            $atribut['destination_id'] = $destinationId;
            $atribut['alamat_asal'] = $destinationId === null ? null : $alamatAsal;
        }

        $umkm->update($atribut);

        return $umkm->fresh();
    }

    /**
     * Perbarui data akun. Kata sandi hanya diganti kalau diisi.
     *
     * @param  array<string, mixed>  $data
     */
    public function perbarui(User $user, array $data): User
    {
        $atribut = array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
            'jenis_kelamin' => $data['jenis_kelamin'] ?? null,
            'avatar_path' => $data['avatar_path'] ?? null,
        ], static fn ($nilai): bool => $nilai !== null);

        if (! empty($data['password'])) {
            $atribut['password'] = Hash::make($data['password']);
        }

        // Mengganti email membatalkan status verifikasinya. Alamat baru
        // belum terbukti milik orang yang sama.
        if (isset($atribut['email']) && $atribut['email'] !== $user->email) {
            $atribut['email_verified_at'] = null;
        }

        if (isset($atribut['phone']) && $atribut['phone'] !== $user->phone) {
            $atribut['phone_verified_at'] = null;
        }

        $user->update($atribut);

        return $user->fresh();
    }

    /**
     * Nonaktifkan akun.
     *
     * Token mobile ikut dicabut. Tanpa itu, akun yang dinonaktifkan di
     * web masih bisa dipakai dari ponsel sampai tokennya kedaluwarsa
     * sendiri.
     */
    public function nonaktifkan(User $user): User
    {
        return DB::transaction(function () use ($user): User {
            $user->update(['is_active' => false]);
            $user->tokens()->delete();

            return $user->fresh();
        });
    }

    public function aktifkan(User $user): User
    {
        $user->update(['is_active' => true]);

        return $user->fresh();
    }

    /**
     * Pembuat akun umum.
     *
     * Kata sandi kosong diisi nilai acak yang tidak pernah dikirimkan.
     * Pemilik akun masuk lewat alur lupa kata sandi, sehingga kredensial
     * pertamanya tidak melintas di email atau WhatsApp, dan pembuat
     * akun tidak pernah mengetahui kata sandi orang yang dibuatkannya.
     *
     * @param  array<string, mixed>  $data
     */
    private function terbitkan(Role $role, array $data): User
    {
        return DB::transaction(function () use ($role, $data): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password'] ?? Str::password(16)),
                'phone' => $data['phone'] ?? null,
                'wilayah_id' => $data['wilayah_id'] ?? null,
                'bank_sampah_id' => $data['bank_sampah_id'] ?? null,
                'umkm_id' => $data['umkm_id'] ?? null,
                'kode_qr' => (string) Str::ulid(),
                'is_active' => $data['is_active'] ?? true,
            ]);

            $user->assignRole($role->value);

            // Setiap pengguna punya dompet, termasuk petugas dan
            // pengelola bank sampah, mereka juga warga yang bisa
            // menyetor sampah pribadinya.
            Dompet::firstOrCreate(['user_id' => $user->id], ['saldo' => 0]);

            return $user;
        });
    }
}
