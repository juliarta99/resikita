<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\BankSampah;
use App\Models\Umkm;
use App\Models\User;
use App\Models\Wilayah;
use App\Support\Navigasi;
use Database\Seeders\RoleSeeder;

/**
 * Uji asap seluruh halaman panel.
 *
 * Yang diperiksa sederhana tapi mahal kalau terlewat: setiap butir menu
 * yang ditawarkan Navigasi benar-benar membuka halaman yang merender.
 * Menu adalah janji kepada pengguna, butir yang mengantar ke galat 500
 * terbaca sebagai "aplikasinya rusak", bukan sebagai satu route yang
 * belum sempat dibuat.
 *
 * Uji ini juga menangkap kesalahan yang paling sering lolos dari uji
 * satuan komponen: nama komponen Blade yang salah ketik, relasi yang
 * belum dimuat, dan pemanggilan `route()` ke nama yang tidak ada.
 * Semuanya hanya muncul ketika halaman benar-benar dirender utuh.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

/** Buat pengguna sesuai role, lengkap dengan keterkaitan yang diperlukan. */
function penggunaPanel(Role $role): User
{
    $atribut = [];

    if ($role->butuhWilayah() || $role->isPemerintahan()) {
        $provinsi = Wilayah::factory()->terverifikasi()->create();
        $atribut['wilayah_id'] = match ($role) {
            Role::AdminProvinsi => $provinsi->id,
            Role::AdminKabupaten => Wilayah::factory()->anakDari($provinsi)->terverifikasi()->create()->id,
            default => Wilayah::factory()
                ->anakDari(Wilayah::factory()->anakDari($provinsi)->create())
                ->create()->id,
        };
    }

    if ($role === Role::BankSampah) {
        $atribut['bank_sampah_id'] = BankSampah::factory()->create()->id;
    }

    if ($role === Role::Umkm) {
        $atribut['umkm_id'] = Umkm::factory()->create()->id;
    }

    return User::factory()->withRole($role)->create($atribut);
}

$panelWeb = [
    Role::SuperAdmin,
    Role::Admin,
    Role::FasilitatorWilayah,
    Role::AdminProvinsi,
    Role::AdminKabupaten,
    Role::KepalaDesa,
    Role::BankSampah,
    Role::Umkm,
];

it('merender setiap halaman yang ditawarkan menu', function (Role $role): void {
    $pengguna = penggunaPanel($role);
    $menu = Navigasi::untuk($pengguna);

    expect($menu)->not->toBeEmpty("Role {$role->value} punya panel web tapi menunya kosong.");

    foreach ($menu as $butir) {
        $this->actingAs($pengguna)
            ->get(route($butir['route']))
            ->assertOk("Halaman {$butir['route']} untuk role {$role->value} gagal dirender.");
    }
})->with($panelWeb);

it('mengantar tiap role ke dasbor yang bisa dibukanya', function (Role $role): void {
    $pengguna = penggunaPanel($role);

    $this->actingAs($pengguna)
        ->get(route($role->routeDasbor()))
        ->assertOk();
})->with($panelWeb);

it('menyatakan keadaan kosong, bukan galat, untuk akun tanpa keterkaitan', function (): void {
    /*
     * Akun bank sampah dan UMKM tanpa entitas induk adalah keadaan yang
     * benar-benar terjadi: admin membuat akunnya lebih dulu, unitnya
     * menyusul. Halamannya harus menjelaskan itu, bukan pecah.
     */
    $bankSampah = User::factory()->withRole(Role::BankSampah)->create(['bank_sampah_id' => null]);

    $this->actingAs($bankSampah)->get(route('bank-sampah.dashboard'))
        ->assertOk()
        ->assertSee('belum terhubung', false);

    $this->actingAs($bankSampah)->get(route('bank-sampah.harga'))->assertOk();
    $this->actingAs($bankSampah)->get(route('bank-sampah.setoran'))->assertOk();
    $this->actingAs($bankSampah)->get(route('bank-sampah.nasabah'))->assertOk();
    $this->actingAs($bankSampah)->get(route('bank-sampah.riwayat'))->assertOk();

    // Akun berrole umkm tanpa toko sama sekali. Panel penjualnya
    // tertutup dan seluruh halamannya mengantar ke satu tempat yang
    // menjelaskan keadaannya, bukan ke dasbor yang angkanya nol tanpa
    // sebab yang terbaca.
    $tanpaToko = User::factory()->withRole(Role::Umkm)->create(['umkm_id' => null]);

    $this->actingAs($tanpaToko)->get(route('umkm.dashboard'))->assertRedirect(route('umkm.status'));
    $this->actingAs($tanpaToko)->get(route('umkm.toko'))->assertRedirect(route('umkm.status'));

    $this->actingAs($tanpaToko)->get(route('umkm.status'))
        ->assertOk()
        ->assertSee('belum terhubung', false);

    // Toko yang sudah terverifikasi membuka seluruh panelnya.
    $umkm = User::factory()->withRole(Role::Umkm)->create([
        'umkm_id' => Umkm::factory()->create()->id,
    ]);

    $this->actingAs($umkm)->get(route('umkm.dashboard'))->assertOk();
    $this->actingAs($umkm)->get(route('umkm.toko'))->assertOk();
    $this->actingAs($umkm)->get(route('umkm.produk'))->assertOk();
    $this->actingAs($umkm)->get(route('umkm.pesanan'))->assertOk();
    $this->actingAs($umkm)->get(route('umkm.saldo'))->assertOk();

    $kepalaDesa = User::factory()->withRole(Role::KepalaDesa)->create(['wilayah_id' => null]);

    $this->actingAs($kepalaDesa)->get(route('desa.dashboard'))
        ->assertOk()
        ->assertSee('belum terhubung ke wilayah', false);
});

it('membuka halaman tamu tanpa akun', function (string $path): void {
    $this->get($path)->assertOk();
})->with(['/masuk', '/lupa-password', '/reset-password', '/daftarkan-wilayah']);
