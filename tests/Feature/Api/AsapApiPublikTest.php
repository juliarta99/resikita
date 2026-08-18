<?php

declare(strict_types=1);

use App\Enums\KategoriSampah;
use App\Enums\Role;
use App\Enums\StatusAktif;
use App\Enums\StatusUmkm;
use App\Models\Artikel;
use App\Models\BankSampah;
use App\Models\BankSampahHarga;
use App\Models\LaporanKategori;
use App\Models\Produk;
use App\Models\ProdukFoto;
use App\Models\ProdukKategori;
use App\Models\Tps;
use App\Models\Umkm;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\Laporan\LaporanService;
use Database\Seeders\RoleSeeder;

/**
 * Uji asap seluruh endpoint API publik.
 *
 * ## Kelas bug yang dijaga berkas ini
 *
 * Relasi yang dimuat sebagian kolom, `with('umkm:id,nama')`, lalu
 * dirender oleh API Resource yang membaca kolom bertipe enum. Kolom yang
 * tidak ikut terpilih bernilai null, `null->value` melempar galat, dan
 * seluruh endpoint mati dengan pesan "Attempt to read property value on
 * null" yang tidak menyebut satu pun nama kolom.
 *
 * Kegagalannya khas: tidak muncul saat tabelnya kosong, karena Resource
 * tidak pernah dipanggil. Karena itu tiap endpoint di sini diberi data
 * lebih dulu, endpoint yang hanya diuji dalam keadaan kosong akan
 * lolos justru pada kasus yang paling penting.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $provinsi = Wilayah::factory()->terverifikasi()->create(['kode' => '51', 'nama' => 'Bali']);
    $this->kabupaten = Wilayah::factory()->anakDari($provinsi, '03')->terverifikasi()->create(['nama' => 'Badung']);

    // Direktori
    $this->bankSampah = BankSampah::factory()->create([
        'wilayah_id' => $this->kabupaten->id,
        'status' => StatusAktif::Aktif,
        'is_verified' => true,
    ]);

    BankSampahHarga::factory()->create([
        'bank_sampah_id' => $this->bankSampah->id,
        'kategori' => KategoriSampah::Anorganik,
    ]);

    $this->tps = Tps::factory()->create(['wilayah_id' => $this->kabupaten->id]);

    // Marketplace
    $this->umkm = Umkm::factory()->create([
        'wilayah_id' => $this->kabupaten->id,
        'status' => StatusUmkm::Aktif,
        'is_verified' => true,
    ]);

    $kategoriProduk = ProdukKategori::factory()->create();

    $this->produk = Produk::factory()->create([
        'umkm_id' => $this->umkm->id,
        'kategori_id' => $kategoriProduk->id,
        'stok' => 10,
        'is_active' => true,
    ]);

    ProdukFoto::create([
        'produk_id' => $this->produk->id,
        'path' => 'produk/contoh.jpg',
        'urutan' => 1,
        'is_utama' => true,
    ]);

    // Literasi
    $this->artikel = Artikel::factory()->terbit()->create();

    // Laporan
    $kategoriLaporan = LaporanKategori::factory()->create();
    User::factory()->withRole(Role::FasilitatorWilayah)->create();
    $pelapor = User::factory()->withRole(Role::Masyarakat)->create();

    $this->laporan = app(LaporanService::class)->buat($pelapor, [
        'kategori_id' => $kategoriLaporan->id,
        'judul' => 'Tumpukan sampah',
        'deskripsi' => 'Deskripsi yang cukup panjang untuk lolos validasi bentuk.',
        'latitude' => -8.5830000,
        'longitude' => 115.1830000,
    ]);
});

it('melayani seluruh endpoint publik dengan data terisi', function (string $path): void {
    $this->getJson($path)
        ->assertOk()
        ->assertJsonPath('success', true);
})->with(fn (): array => [
    '/api/v1/produk',
    '/api/v1/produk/kategori',
    '/api/v1/artikel',
    '/api/v1/artikel/kategori',
    '/api/v1/direktori/tps',
    '/api/v1/direktori/bank-sampah',
    '/api/v1/direktori/umkm',
    '/api/v1/harga-sampah',
    '/api/v1/wilayah',
    '/api/v1/wilayah/provinsi',
    '/api/v1/publik/statistik',
    '/api/v1/publik/peta',
    '/api/v1/publik/wilayah-terdaftar',
    '/api/v1/laporan/kategori',
]);

it('melayani endpoint detail yang merender relasi bersarang', function (): void {
    $this->getJson("/api/v1/produk/{$this->produk->slug}")
        ->assertOk()
        // Relasi bersarang inilah yang meledak ketika kolomnya dimuat
        // sebagian: produk → umkm → wilayah.
        ->assertJsonPath('data.umkm.status', 'aktif')
        ->assertJsonPath('data.umkm.is_verified', true);

    $this->getJson("/api/v1/produk/{$this->produk->slug}/ulasan")->assertOk();

    $this->getJson("/api/v1/direktori/tps/{$this->tps->id}")
        ->assertOk()
        ->assertJsonPath('data.wilayah.status_registrasi', 'terverifikasi');

    $this->getJson("/api/v1/direktori/bank-sampah/{$this->bankSampah->id}")
        ->assertOk()
        ->assertJsonPath('data.bank_sampah.status', 'aktif');

    $this->getJson("/api/v1/direktori/umkm/{$this->umkm->id}")
        ->assertOk()
        ->assertJsonPath('data.wilayah.status_registrasi', 'terverifikasi');

    $this->getJson("/api/v1/artikel/{$this->artikel->slug}")->assertOk();
    $this->getJson("/api/v1/artikel/{$this->artikel->slug}/teks-baca")->assertOk();
    $this->getJson("/api/v1/laporan/{$this->laporan->id}")->assertOk();
});

it('menyertakan wilayah lengkap pada daftar direktori', function (): void {
    // Bukan sekadar tidak galat: nilainya harus benar-benar terisi.
    // Kolom yang hilang bernilai null tanpa melempar apa pun, sehingga
    // "tidak galat" saja bukan bukti bahwa datanya utuh.
    $this->getJson('/api/v1/direktori/bank-sampah')
        ->assertOk()
        ->assertJsonPath('data.0.wilayah.nama', 'Badung')
        ->assertJsonPath('data.0.wilayah.status_registrasi', 'terverifikasi')
        ->assertJsonPath('data.0.status', 'aktif');

    $this->getJson('/api/v1/harga-sampah')
        ->assertOk()
        ->assertJsonPath('data.0.bank_sampah.status', 'aktif');
});

it('tetap melayani endpoint yang datanya kosong', function (): void {
    Produk::query()->delete();
    Artikel::query()->delete();
    Tps::query()->delete();

    $this->getJson('/api/v1/produk')->assertOk();
    $this->getJson('/api/v1/artikel')->assertOk();
    $this->getJson('/api/v1/direktori/tps')->assertOk();
});
