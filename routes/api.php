<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Controllers\Api\V1\ArtikelController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BankSampahController;
use App\Http\Controllers\Api\V1\ChatbotController;
use App\Http\Controllers\Api\V1\DirektoriController;
use App\Http\Controllers\Api\V1\DompetController;
use App\Http\Controllers\Api\V1\KeranjangController;
use App\Http\Controllers\Api\V1\KlasifikasiController;
use App\Http\Controllers\Api\V1\LaporanController;
use App\Http\Controllers\Api\V1\NotifikasiController;
use App\Http\Controllers\Api\V1\PembayaranController;
use App\Http\Controllers\Api\V1\PengajuanWilayahController;
use App\Http\Controllers\Api\V1\PesananController;
use App\Http\Controllers\Api\V1\PetugasController;
use App\Http\Controllers\Api\V1\ProdukController;
use App\Http\Controllers\Api\V1\ProfilController;
use App\Http\Controllers\Api\V1\PublikController;
use App\Http\Controllers\Api\V1\TpsController;
use App\Http\Controllers\Api\V1\WilayahController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1, kanal untuk aplikasi resikita-mobile
|--------------------------------------------------------------------------
|
| Amplop response mengikuti CLAUDE.md 12 dan ditegakkan ApiResponse.
| Autentikasi memakai token Sanctum murni lewat header
| Authorization: Bearer. SPA mode tidak dipakai.
|
| Perubahan pada berkas ini adalah perubahan kontrak. Perbarui
| API-DOCS.md dan aplikasi mobile bersamaan.
|
*/

Route::prefix('v1')->group(function (): void {

    /*
    |----------------------------------------------------------------
    | Publik, tanpa token
    |----------------------------------------------------------------
    */

    Route::post('auth/daftar', [AuthController::class, 'daftar'])->middleware('throttle:6,1');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('auth/lupa-password', [AuthController::class, 'lupaPassword'])->middleware('throttle:6,1');
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:6,1');

    // Callback server-to-server Midtrans. Dijaga tanda tangan sha512,
    // bukan token, pemanggilnya peladen penyedia, bukan pengguna.
    Route::post('pembayaran/midtrans/notifikasi', [PembayaranController::class, 'notifikasiMidtrans']);

    // Pengajuan wilayah terbuka: pemohonnya pejabat daerah yang belum
    // punya akun Resikita.
    Route::post('pengajuan-wilayah', [PengajuanWilayahController::class, 'ajukan'])->middleware('throttle:3,60');
    Route::get('pengajuan-wilayah/lacak', [PengajuanWilayahController::class, 'lacak'])->middleware('throttle:20,1');

    // Literasi
    Route::get('artikel', [ArtikelController::class, 'index']);
    Route::get('artikel/kategori', [ArtikelController::class, 'kategori']);
    Route::get('artikel/{artikel}', [ArtikelController::class, 'show']);
    Route::get('artikel/{artikel}/teks-baca', [ArtikelController::class, 'teksBaca']);

    // Marketplace, mode jelajah
    Route::get('produk', [ProdukController::class, 'index']);
    Route::get('produk/kategori', [ProdukController::class, 'kategori']);
    Route::get('produk/{produk}', [ProdukController::class, 'show']);
    Route::get('produk/{produk}/ulasan', [ProdukController::class, 'ulasan']);

    // Direktori
    Route::get('direktori/tps', [DirektoriController::class, 'tps']);
    Route::get('direktori/tps/{tps}', [DirektoriController::class, 'tpsDetail']);
    Route::get('direktori/bank-sampah', [DirektoriController::class, 'bankSampah']);
    Route::get('direktori/bank-sampah/{bankSampah}', [DirektoriController::class, 'bankSampahDetail']);
    Route::get('direktori/umkm', [DirektoriController::class, 'umkm']);
    Route::get('direktori/umkm/{umkm}', [DirektoriController::class, 'umkmDetail']);
    Route::get('harga-sampah', [DirektoriController::class, 'hargaSampah']);

    /*
    | Wilayah, pemilih bertingkat dan resolusi koordinat.
    |
    | Terbuka tanpa token: pengajuan wilayah dipakai pejabat daerah yang
    | belum punya akun, dan pendaftaran warga membutuhkan pemilih
    | domisili sebelum akunnya ada.
    |
    | Route beruas tetap diletakkan sebelum `{wilayah}` supaya "provinsi"
    | tidak pernah dibaca sebagai id.
    */
    Route::get('wilayah', [DirektoriController::class, 'wilayah']);
    Route::get('wilayah/provinsi', [WilayahController::class, 'provinsi']);
    Route::get('wilayah/cari', [WilayahController::class, 'cari']);
    Route::get('wilayah/terdekat', [WilayahController::class, 'terdekat']);
    Route::post('wilayah/resolusi', [WilayahController::class, 'resolusi'])->middleware('throttle:30,1');
    Route::get('wilayah/{wilayah}', [WilayahController::class, 'show'])->whereNumber('wilayah');
    Route::get('wilayah/{wilayah}/anak', [WilayahController::class, 'anak'])->whereNumber('wilayah');

    // Angka nasional untuk beranda aplikasi, sumbernya sama dengan web.
    Route::get('publik/statistik', [PublikController::class, 'statistik']);
    Route::get('publik/peta', [PublikController::class, 'peta']);
    Route::get('publik/wilayah-terdaftar', [PublikController::class, 'wilayahTerdaftar']);

    // Laporan bersifat terbuka: transparansi yang membuat pelaporan
    // terasa berguna, dan penanganan yang mandek jadi terlihat publik.
    Route::get('laporan/kategori', [LaporanController::class, 'kategori']);
    Route::get('laporan/{laporan}', [LaporanController::class, 'show'])->whereNumber('laporan');

    /*
    |----------------------------------------------------------------
    | Perlu token
    |----------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function (): void {

        // Akun
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/verifikasi-email/kirim', [AuthController::class, 'kirimVerifikasiEmail'])->middleware('throttle:6,1');
        Route::post('auth/verifikasi-email', [AuthController::class, 'verifikasiEmail']);
        Route::post('auth/verifikasi-phone/kirim', [AuthController::class, 'kirimVerifikasiPhone'])->middleware('throttle:6,1');
        Route::post('auth/verifikasi-phone', [AuthController::class, 'verifikasiPhone']);

        Route::put('profil', [ProfilController::class, 'perbarui']);
        Route::put('profil/password', [ProfilController::class, 'gantiPassword']);

        // Notifikasi
        Route::get('notifikasi', [NotifikasiController::class, 'index']);
        Route::get('notifikasi/belum-dibaca', [NotifikasiController::class, 'jumlahBelumDibaca']);
        Route::patch('notifikasi/{notifikasi}/dibaca', [NotifikasiController::class, 'tandaiDibaca']);
        Route::post('notifikasi/baca-semua', [NotifikasiController::class, 'tandaiSemuaDibaca']);
        Route::post('perangkat', [NotifikasiController::class, 'daftarkanPerangkat']);
        Route::delete('perangkat', [NotifikasiController::class, 'hapusPerangkat']);

        /*
        |------------------------------------------------------------
        | Kanal AI
        |
        | Dua endpoint ini sengaja sinkron, bukan lewat antrean
        | (CLAUDE.md 3 butir 8). Pengguna menunggu jawabannya di
        | layar; menjadwalkannya berarti membuang kegunaan fiturnya.
        |
        | Karena itu keduanya dibatasi lebih ketat daripada endpoint
        | biasa: tiap permintaan memegang satu proses peladen selama
        | beberapa detik dan berbiaya nyata di sisi penyedia.
        |------------------------------------------------------------
        */

        Route::get('klasifikasi/riwayat', [KlasifikasiController::class, 'riwayat']);
        Route::get('klasifikasi/ringkasan', [KlasifikasiController::class, 'ringkasan']);
        Route::post('klasifikasi', [KlasifikasiController::class, 'store'])
            ->middleware(['permission:'.Permission::KlasifikasiBuat->value, 'throttle:20,1']);
        Route::get('klasifikasi/{klasifikasi}', [KlasifikasiController::class, 'show'])->whereNumber('klasifikasi');
        Route::delete('klasifikasi/{klasifikasi}', [KlasifikasiController::class, 'destroy'])->whereNumber('klasifikasi');

        Route::prefix('chatbot')
            ->middleware('permission:'.Permission::ChatbotPakai->value)
            ->group(function (): void {
                Route::get('saran-pertanyaan', [ChatbotController::class, 'saranPertanyaan']);
                Route::get('sesi', [ChatbotController::class, 'sesi']);
                Route::post('sesi', [ChatbotController::class, 'buatSesi']);
                Route::get('sesi/{sesi}', [ChatbotController::class, 'detailSesi'])->whereNumber('sesi');
                Route::delete('sesi/{sesi}', [ChatbotController::class, 'hapusSesi'])->whereNumber('sesi');
                Route::post('pesan', [ChatbotController::class, 'kirim'])->middleware('throttle:30,1');
                Route::patch('pesan/{pesan}/dibacakan', [ChatbotController::class, 'tandaiDibacakan'])->whereNumber('pesan');
            });

        // Laporan
        Route::get('laporan', [LaporanController::class, 'index']);
        Route::post('laporan', [LaporanController::class, 'store']);
        Route::post('laporan/cek-duplikat', [LaporanController::class, 'cekDuplikat']);

        // Dompet dan bank sampah digital
        Route::get('dompet/saldo', [DompetController::class, 'saldo']);
        Route::get('dompet/transaksi', [DompetController::class, 'transaksi']);
        Route::get('dompet/setoran', [DompetController::class, 'setoran']);
        Route::get('dompet/penarikan', [DompetController::class, 'riwayatPenarikan']);
        Route::post('dompet/penarikan', [DompetController::class, 'ajukanPenarikan']);

        // TPS
        Route::get('tps/keanggotaan-saya', [TpsController::class, 'keanggotaanSaya']);
        Route::post('tps/{tps}/gabung', [TpsController::class, 'gabung']);
        Route::post('tps/keluar', [TpsController::class, 'keluar']);
        Route::post('tps/iuran/{iuran}/bayar', [TpsController::class, 'bayarIuran']);

        // Keranjang
        Route::get('keranjang', [KeranjangController::class, 'index']);
        Route::post('keranjang', [KeranjangController::class, 'tambah']);
        Route::put('keranjang', [KeranjangController::class, 'ubah']);
        Route::delete('keranjang/{produk}', [KeranjangController::class, 'hapus']);
        Route::delete('keranjang', [KeranjangController::class, 'kosongkan']);

        // Ongkir dan pesanan
        Route::get('ongkir/tujuan', [PesananController::class, 'cariTujuan']);
        Route::post('ongkir/hitung', [PesananController::class, 'hitungOngkir']);
        Route::get('pesanan/pratinjau', [PesananController::class, 'pratinjau']);
        Route::get('pesanan', [PesananController::class, 'index']);
        Route::post('pesanan', [PesananController::class, 'checkout']);
        Route::get('pesanan/{pesanan}', [PesananController::class, 'show']);
        Route::post('pesanan/{pesanan}/batal', [PesananController::class, 'batalkan']);
        Route::post('pesanan/{pesanan}/bayar-ulang', [PesananController::class, 'bayarUlang']);
        Route::post('pesanan/{pesanan}/terima', [PesananController::class, 'terima']);
        Route::post('pesanan/{pesanan}/ulasan', [PesananController::class, 'tulisUlasan']);

        /*
        |------------------------------------------------------------
        | Petugas lapangan
        |------------------------------------------------------------
        */
        Route::middleware('role:'.Role::Petugas->value)->prefix('petugas')->group(function (): void {
            Route::get('penugasan', [PetugasController::class, 'penugasan']);
            Route::get('penugasan/{laporan}', [PetugasController::class, 'detail']);
            Route::post('penugasan/{laporan}/progres', [PetugasController::class, 'catatProgres']);
        });

        /*
        |------------------------------------------------------------
        | Bank sampah
        |------------------------------------------------------------
        */
        Route::middleware('role:'.Role::BankSampah->value)->prefix('bank-sampah')->group(function (): void {
            Route::get('harga', [BankSampahController::class, 'harga']);
            Route::post('harga', [BankSampahController::class, 'simpanHarga']);
            Route::put('harga/{harga}', [BankSampahController::class, 'perbaruiHarga']);

            Route::post('nasabah/cari', [BankSampahController::class, 'cariNasabah']);
            Route::post('setoran', [BankSampahController::class, 'mulaiSetoran']);
            Route::post('setoran/{setoran}/item', [BankSampahController::class, 'tambahItem']);
            Route::post('setoran/{setoran}/selesai', [BankSampahController::class, 'selesaikanSetoran']);
            Route::post('setoran/{setoran}/batal', [BankSampahController::class, 'batalkanSetoran']);

            Route::get('riwayat', [BankSampahController::class, 'riwayat']);
            Route::get('rekap', [BankSampahController::class, 'rekap']);
        });
    });
});
