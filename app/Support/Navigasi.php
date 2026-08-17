<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Role;
use App\Models\User;

/**
 * Menu navigasi panel web, ditentukan oleh role.
 *
 * ## Kenapa satu berkas, bukan satu partial per panel
 *
 * Menu adalah janji: setiap butir yang tampil harus mengarah ke halaman
 * yang benar-benar boleh dibuka pengguna itu. Ketika daftarnya tersebar
 * di tujuh partial Blade, satu butir yang tertinggal setelah sebuah
 * route diganti akan mengantar pengguna ke halaman 403, dan yang
 * terlihat olehnya bukan "saya tidak berwenang", melainkan "aplikasinya
 * rusak".
 *
 * ## Ini bukan otorisasi
 *
 * Menyembunyikan butir menu tidak menutup halamannya. Penjagaan yang
 * sesungguhnya ada di middleware role pada routes/web.php dan di Policy.
 * Berkas ini hanya menentukan apa yang pantas ditawarkan.
 */
final class Navigasi
{
    /**
     * Butir menu untuk seorang pengguna.
     *
     * @return array<int, array{label: string, route: string, ikon: string, cocok: string}>
     */
    public static function untuk(User $user): array
    {
        $role = $user->roleUtama();

        if ($role === null) {
            return [];
        }

        return match ($role) {
            Role::SuperAdmin, Role::Admin => self::admin($role),
            Role::FasilitatorWilayah => self::fasilitator(),
            Role::AdminProvinsi, Role::AdminKabupaten, Role::KepalaDesa => self::pemerintahan($role),
            Role::BankSampah => self::bankSampah(),
            Role::Umkm => self::umkm($user),
            Role::Petugas, Role::Masyarakat => [],
        };
    }

    /**
     * Awalan route panel seorang pengguna, mis. `kabupaten.`.
     *
     * Dipakai komponen bersama, Profil, misalnya, untuk kembali ke
     * dasbor yang tepat tanpa perlu tahu role pemanggilnya.
     */
    public static function awalanRoute(User $user): string
    {
        $role = $user->roleUtama();

        if ($role === null) {
            return '';
        }

        return match ($role) {
            Role::SuperAdmin, Role::Admin => 'admin.',
            Role::FasilitatorWilayah => 'fasilitator.',
            Role::AdminProvinsi => 'provinsi.',
            Role::AdminKabupaten => 'kabupaten.',
            Role::KepalaDesa => 'desa.',
            Role::BankSampah => 'bank-sampah.',
            Role::Umkm => 'umkm.',
            Role::Petugas, Role::Masyarakat => '',
        };
    }

    /**
     * Nama panel yang tampil di kepala bilah sisi.
     *
     * Untuk role pemerintahan, nama wilayahnya ikut karena itulah yang
     * membedakan satu akun dari akun lain dengan role yang sama.
     */
    public static function namaPanel(User $user): string
    {
        $role = $user->roleUtama();

        if ($role === null) {
            return 'Resikita';
        }

        if ($role->isPemerintahan()) {
            return $user->wilayah?->namaLengkap() ?? $role->label();
        }

        return match ($role) {
            Role::BankSampah => $user->bankSampah?->nama ?? $role->label(),
            Role::Umkm => $user->umkm?->nama ?? $role->label(),
            default => $role->label(),
        };
    }

    /** @return array<int, array{label: string, route: string, ikon: string, cocok: string}> */
    private static function admin(Role $role): array
    {
        $menu = [
            self::butir('Dasbor', 'admin.dashboard', 'rumah'),
            self::butir('Laporan', 'admin.laporan', 'megafon'),
            self::butir('Pengguna', 'admin.pengguna', 'orang'),
            self::butir('UMKM', 'admin.umkm', 'toko'),
            self::butir('Artikel', 'admin.artikel', 'buku'),
            self::butir('Penarikan Saldo', 'admin.penarikan', 'dompet'),
            self::butir('Master Data', 'admin.master', 'kotak'),
        ];

        /*
         * Verifikasi pengajuan wilayah dan log aktivitas hanya untuk
         * super admin. Yang pertama menentukan siapa memegang kewenangan
         * atas sebuah daerah; yang kedua berisi jejak tindakan seluruh
         * pengguna, termasuk sesama admin.
         */
        if ($role === Role::SuperAdmin) {
            array_splice($menu, 1, 0, [self::butir('Pengajuan Wilayah', 'admin.pengajuan-wilayah', 'peta')]);
            $menu[] = self::butir('Wilayah', 'admin.wilayah', 'peta');
            $menu[] = self::butir('Log Aktivitas', 'admin.log', 'jejak');
        }

        $menu[] = self::butir('Profil', 'admin.profil', 'gear');

        return $menu;
    }

    /** @return array<int, array{label: string, route: string, ikon: string, cocok: string}> */
    private static function fasilitator(): array
    {
        return [
            self::butir('Dasbor', 'fasilitator.dashboard', 'rumah'),
            self::butir('Laporan Belum Terjangkau', 'fasilitator.laporan', 'megafon'),
            self::butir('Papan Prioritas', 'fasilitator.prioritas', 'grafik'),
            self::butir('Profil', 'fasilitator.profil', 'gear'),
        ];
    }

    /** @return array<int, array{label: string, route: string, ikon: string, cocok: string}> */
    private static function pemerintahan(Role $role): array
    {
        $awalan = match ($role) {
            Role::AdminProvinsi => 'provinsi.',
            Role::AdminKabupaten => 'kabupaten.',
            default => 'desa.',
        };

        return [
            self::butir('Dasbor', $awalan.'dashboard', 'rumah'),
            self::butir('Laporan', $awalan.'laporan', 'megafon'),
            self::butir('Petugas', $awalan.'petugas', 'orang'),
            self::butir('TPS', $awalan.'tps', 'kotak'),
            self::butir('Analitik', $awalan.'analitik', 'grafik'),
            self::butir('Profil', $awalan.'profil', 'gear'),
        ];
    }

    /** @return array<int, array{label: string, route: string, ikon: string, cocok: string}> */
    private static function bankSampah(): array
    {
        return [
            self::butir('Dasbor', 'bank-sampah.dashboard', 'rumah'),
            self::butir('Setoran', 'bank-sampah.setoran', 'timbangan'),
            self::butir('Katalog Harga', 'bank-sampah.harga', 'label'),
            self::butir('Nasabah', 'bank-sampah.nasabah', 'orang'),
            self::butir('Riwayat', 'bank-sampah.riwayat', 'jejak'),
            self::butir('Asisten Lingkungan', 'bank-sampah.asisten', 'suara'),
            self::butir('Profil', 'bank-sampah.profil', 'gear'),
        ];
    }

    /** @return array<int, array{label: string, route: string, ikon: string, cocok: string}> */
    private static function umkm(User $user): array
    {
        /*
         * Toko yang belum lolos verifikasi belum punya apa pun untuk
         * dikelola. Menawarkan tujuh menu yang seluruhnya memantul balik
         * ke halaman status membuat panel terasa rusak, bukan terjaga,
         * dan menyembunyikan satu-satunya halaman yang benar-benar
         * berguna baginya di antara tautan yang tidak bisa dibuka.
         */
        if ($user->umkm?->bolehBerjualan() !== true) {
            return [
                self::butir('Status Pendaftaran', 'umkm.status', 'info'),
                self::butir('Profil', 'umkm.profil', 'gear'),
            ];
        }

        return [
            self::butir('Dasbor', 'umkm.dashboard', 'rumah'),
            self::butir('Toko', 'umkm.toko', 'toko'),
            self::butir('Produk', 'umkm.produk', 'kotak'),
            self::butir('Pesanan', 'umkm.pesanan', 'keranjang'),
            self::butir('Asisten Konten', 'umkm.konten', 'pensil'),
            self::butir('Saldo', 'umkm.saldo', 'dompet'),
            self::butir('Asisten Lingkungan', 'umkm.asisten', 'suara'),
            self::butir('Profil', 'umkm.profil', 'gear'),
        ];
    }

    /**
     * @return array{label: string, route: string, ikon: string, cocok: string}
     */
    private static function butir(string $label, string $route, string $ikon): array
    {
        return [
            'label' => $label,
            'route' => $route,
            'ikon' => $ikon,
            // Pola untuk menandai butir aktif, termasuk halaman turunan
            // seperti `kabupaten.laporan.detail`.
            'cocok' => $route.'*',
        ];
    }
}
