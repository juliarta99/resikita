<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * Daftar tertutup kewenangan di Resikita.
 *
 * Permission menyatakan *apa* yang boleh dilakukan; `WilayahScopeService`
 * menyatakan *atas data siapa*. Keduanya harus dipakai bersama, seorang
 * admin kabupaten punya `laporan.verifikasi`, tapi itu hanya berlaku
 * untuk laporan di kabupatennya. Permission saja tidak pernah cukup
 * untuk role pemerintahan.
 *
 * Enum ini mencegah nama permission ditulis sebagai string mentah yang
 * tersebar dan bisa salah ketik, sebuah salah ketik di pengecekan
 * permission gagal secara diam-diam, dan diam-diam berarti tidak
 * terdeteksi sampai ada yang mengaksesnya.
 */
enum Permission: string implements HasLabel
{
    use ProvidesOptions;

    // ---- Wilayah dan pendaftarannya ----
    case WilayahLihat = 'wilayah.lihat';
    case WilayahKelola = 'wilayah.kelola';
    case PengajuanWilayahAjukan = 'pengajuan_wilayah.ajukan';
    case PengajuanWilayahLihat = 'pengajuan_wilayah.lihat';
    case PengajuanWilayahVerifikasi = 'pengajuan_wilayah.verifikasi';

    // ---- Laporan ----
    case LaporanBuat = 'laporan.buat';
    case LaporanLihat = 'laporan.lihat';
    case LaporanVerifikasi = 'laporan.verifikasi';
    case LaporanTugaskan = 'laporan.tugaskan';
    case LaporanTolak = 'laporan.tolak';
    case LaporanGabungkan = 'laporan.gabungkan';
    case LaporanKerjakan = 'laporan.kerjakan';
    case LaporanTindakLanjut = 'laporan.tindak_lanjut';

    // ---- Operasional wilayah ----
    case PetugasKelola = 'petugas.kelola';
    case TpsKelola = 'tps.kelola';
    case TpsGabung = 'tps.gabung';

    // ---- Bank sampah ----
    case BankSampahKelola = 'bank_sampah.kelola';
    case BankSampahHarga = 'bank_sampah.harga';
    case BankSampahSetor = 'bank_sampah.setor';

    // ---- Dompet ----
    case DompetLihat = 'dompet.lihat';
    case PenarikanAjukan = 'penarikan.ajukan';
    case PenarikanSetujui = 'penarikan.setujui';

    // ---- UMKM dan marketplace ----
    case UmkmKelola = 'umkm.kelola';
    case ProdukKelola = 'produk.kelola';
    case PesananKelola = 'pesanan.kelola';
    case PesananBuat = 'pesanan.buat';
    case UlasanBuat = 'ulasan.buat';
    case KontenPromosiBuat = 'konten_promosi.buat';

    // ---- Literasi dan AI ----
    case ArtikelKelola = 'artikel.kelola';
    case KlasifikasiBuat = 'klasifikasi.buat';
    case ChatbotPakai = 'chatbot.pakai';
    case RekomendasiAiKelola = 'rekomendasi_ai.kelola';
    case KonfigurasiAiKelola = 'konfigurasi_ai.kelola';

    // ---- Sistem ----
    case PenggunaKelola = 'pengguna.kelola';
    case MasterDataKelola = 'master_data.kelola';
    case AnalitikLihat = 'analitik.lihat';
    case LogLihat = 'log.lihat';

    public function label(): string
    {
        return match ($this) {
            self::WilayahLihat => 'Melihat data wilayah',
            self::WilayahKelola => 'Mengelola data wilayah',
            self::PengajuanWilayahAjukan => 'Mengajukan pendaftaran wilayah',
            self::PengajuanWilayahLihat => 'Melihat pengajuan wilayah',
            self::PengajuanWilayahVerifikasi => 'Memverifikasi pengajuan wilayah',

            self::LaporanBuat => 'Membuat laporan',
            self::LaporanLihat => 'Melihat laporan',
            self::LaporanVerifikasi => 'Memverifikasi laporan',
            self::LaporanTugaskan => 'Menugaskan petugas',
            self::LaporanTolak => 'Menolak laporan',
            self::LaporanGabungkan => 'Menggabungkan laporan kembar',
            self::LaporanKerjakan => 'Mengerjakan laporan di lapangan',
            self::LaporanTindakLanjut => 'Mencatat tindak lanjut ke dinas',

            self::PetugasKelola => 'Mengelola petugas',
            self::TpsKelola => 'Mengelola TPS',
            self::TpsGabung => 'Bergabung sebagai anggota TPS',

            self::BankSampahKelola => 'Mengelola bank sampah',
            self::BankSampahHarga => 'Mengatur katalog harga sampah',
            self::BankSampahSetor => 'Melayani setoran sampah',

            self::DompetLihat => 'Melihat saldo dan mutasi',
            self::PenarikanAjukan => 'Mengajukan penarikan saldo',
            self::PenarikanSetujui => 'Menyetujui penarikan saldo',

            self::UmkmKelola => 'Mengelola profil UMKM',
            self::ProdukKelola => 'Mengelola produk',
            self::PesananKelola => 'Mengelola pesanan masuk',
            self::PesananBuat => 'Berbelanja di marketplace',
            self::UlasanBuat => 'Menulis ulasan produk',
            self::KontenPromosiBuat => 'Memakai Asisten Konten UMKM',

            self::ArtikelKelola => 'Mengelola artikel edukasi',
            self::KlasifikasiBuat => 'Memakai klasifikasi sampah AI',
            self::ChatbotPakai => 'Memakai asisten literasi lingkungan',
            self::RekomendasiAiKelola => 'Mengelola rekomendasi AI',
            self::KonfigurasiAiKelola => 'Mengatur konfigurasi AI',

            self::PenggunaKelola => 'Mengelola pengguna',
            self::MasterDataKelola => 'Mengelola master data',
            self::AnalitikLihat => 'Melihat analitik wilayah',
            self::LogLihat => 'Melihat log aktivitas',
        };
    }

    /** Bagian sistem tempat permission ini berlaku, untuk pengelompokan di UI. */
    public function kelompok(): string
    {
        return str_contains($this->value, '.')
            ? explode('.', $this->value)[0]
            : 'lainnya';
    }

    /**
     * Matriks kewenangan Resikita.
     *
     * Ditaruh di enum, bukan di dalam seeder, karena ini pengetahuan
     * domain yang juga dibaca oleh uji dan oleh antarmuka. Seeder hanya
     * menuliskannya ke basis data.
     *
     * Yang perlu diingat saat membaca daftar di bawah: tiga role
     * pemerintahan memegang permission yang sama persis. Perbedaan
     * kewenangan mereka bukan soal *apa* yang boleh dilakukan, melainkan
     * *atas wilayah mana*, dan itu ditegakkan WilayahScopeService, bukan
     * oleh permission.
     *
     * @return array<int, self>
     */
    public static function untukRole(Role $role): array
    {
        $pemerintahan = [
            self::WilayahLihat,
            self::PengajuanWilayahAjukan,
            self::PengajuanWilayahLihat,
            self::LaporanLihat,
            self::LaporanVerifikasi,
            self::LaporanTugaskan,
            self::LaporanTolak,
            self::LaporanGabungkan,
            self::PetugasKelola,
            self::TpsKelola,
            self::BankSampahKelola,
            self::AnalitikLihat,
            self::RekomendasiAiKelola,
            self::ChatbotPakai,
        ];

        return match ($role) {
            // Super admin memegang seluruh kewenangan tanpa kecuali.
            Role::SuperAdmin => self::cases(),

            // Admin menjalankan operasional harian dan moderasi, tapi
            // tidak memverifikasi pengajuan wilayah dan tidak menyentuh
            // konfigurasi AI. Keduanya sengaja disisakan untuk super
            // admin: yang satu memberi orang kewenangan atas satu daerah,
            // yang lain mengubah perilaku model di seluruh sistem.
            Role::Admin => [
                self::WilayahLihat,
                self::WilayahKelola,
                self::PengajuanWilayahLihat,
                self::LaporanLihat,
                self::LaporanVerifikasi,
                self::LaporanTolak,
                self::LaporanGabungkan,
                self::PetugasKelola,
                self::TpsKelola,
                self::BankSampahKelola,
                self::BankSampahHarga,
                self::PenarikanSetujui,
                self::UmkmKelola,
                self::ProdukKelola,
                self::ArtikelKelola,
                self::RekomendasiAiKelola,
                self::PenggunaKelola,
                self::MasterDataKelola,
                self::AnalitikLihat,
                self::LogLihat,
                self::ChatbotPakai,
            ],

            // Fasilitator hanya bekerja pada laporan dari wilayah yang
            // belum terjangkau. Ia tidak memverifikasi dan tidak
            // menugaskan petugas, di wilayah itu memang belum ada
            // aparat maupun petugas yang bisa ditugaskan.
            Role::FasilitatorWilayah => [
                self::WilayahLihat,
                self::PengajuanWilayahLihat,
                self::LaporanLihat,
                self::LaporanTindakLanjut,
                self::LaporanGabungkan,
                self::AnalitikLihat,
                self::ChatbotPakai,
            ],

            Role::AdminProvinsi, Role::AdminKabupaten, Role::KepalaDesa => $pemerintahan,

            Role::Petugas => [
                self::LaporanLihat,
                self::LaporanKerjakan,
            ],

            Role::Masyarakat => [
                self::LaporanBuat,
                self::LaporanLihat,
                self::KlasifikasiBuat,
                self::ChatbotPakai,
                self::DompetLihat,
                self::PenarikanAjukan,
                self::TpsGabung,
                self::PesananBuat,
                self::UlasanBuat,
                self::WilayahLihat,
                self::PengajuanWilayahAjukan,
            ],

            Role::BankSampah => [
                self::BankSampahKelola,
                self::BankSampahHarga,
                self::BankSampahSetor,
                self::DompetLihat,
                self::KlasifikasiBuat,
                self::ChatbotPakai,
                self::WilayahLihat,
                self::AnalitikLihat,
            ],

            Role::Umkm => [
                self::UmkmKelola,
                self::ProdukKelola,
                self::PesananKelola,
                self::KontenPromosiBuat,
                self::DompetLihat,
                self::PenarikanAjukan,
                self::ChatbotPakai,
                self::WilayahLihat,
                self::AnalitikLihat,
            ],
        };
    }

    /** @return array<int, string> Nilai permission untuk sebuah role. */
    public static function nilaiUntukRole(Role $role): array
    {
        return array_map(
            static fn (self $p): string => $p->value,
            self::untukRole($role),
        );
    }
}
