<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role;
use App\Exceptions\AturanBisnisException;
use App\Models\BankSampah;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\Auth\AkunService;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role as SpatieRole;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('menyemai sepuluh role dan seluruh permission', function (): void {
    expect(SpatieRole::where('guard_name', 'web')->count())->toBe(10)
        ->and(Spatie\Permission\Models\Permission::count())->toBe(count(Permission::cases()));
});

it('memberi super admin seluruh kewenangan', function (): void {
    $superAdmin = User::factory()->withRole(Role::SuperAdmin)->create();

    foreach (Permission::cases() as $permission) {
        expect($superAdmin->can($permission->value))
            ->toBeTrue("super_admin seharusnya punya {$permission->value}");
    }
});

it('menyisakan verifikasi pengajuan wilayah dan konfigurasi AI hanya untuk super admin', function (): void {
    $admin = User::factory()->withRole(Role::Admin)->create();

    // Yang satu memberi orang kewenangan atas satu daerah, yang lain
    // mengubah perilaku model di seluruh sistem. Keduanya terlalu besar
    // untuk operasional harian.
    expect($admin->can(Permission::PengajuanWilayahVerifikasi->value))->toBeFalse()
        ->and($admin->can(Permission::KonfigurasiAiKelola->value))->toBeFalse()
        ->and($admin->can(Permission::LaporanVerifikasi->value))->toBeTrue()
        ->and($admin->can(Permission::ArtikelKelola->value))->toBeTrue();
});

it('memberi tiga role pemerintahan permission yang identik', function (): void {
    // Perbedaan kewenangan mereka bukan soal apa yang boleh dilakukan,
    // melainkan atas wilayah mana, dan itu ditegakkan WilayahScopeService.
    $provinsi = Permission::nilaiUntukRole(Role::AdminProvinsi);
    $kabupaten = Permission::nilaiUntukRole(Role::AdminKabupaten);
    $desa = Permission::nilaiUntukRole(Role::KepalaDesa);

    expect($provinsi)->toEqualCanonicalizing($kabupaten)
        ->and($kabupaten)->toEqualCanonicalizing($desa);
});

it('membatasi petugas pada melihat dan mengerjakan laporan saja', function (): void {
    $petugas = User::factory()->withRole(Role::Petugas)->create();

    expect($petugas->can(Permission::LaporanLihat->value))->toBeTrue()
        ->and($petugas->can(Permission::LaporanKerjakan->value))->toBeTrue()
        ->and($petugas->can(Permission::LaporanVerifikasi->value))->toBeFalse()
        ->and($petugas->can(Permission::LaporanTugaskan->value))->toBeFalse()
        ->and($petugas->can(Permission::PenggunaKelola->value))->toBeFalse();
});

it('tidak memberi fasilitator kewenangan menugaskan petugas', function (): void {
    // Di wilayah belum terjangkau memang belum ada aparat maupun petugas
    // yang bisa ditugaskan; yang bisa dilakukan fasilitator adalah
    // mengontak dinas dan mencatat hasilnya.
    $fasilitator = User::factory()->withRole(Role::FasilitatorWilayah)->create();

    expect($fasilitator->can(Permission::LaporanTindakLanjut->value))->toBeTrue()
        ->and($fasilitator->can(Permission::LaporanTugaskan->value))->toBeFalse()
        ->and($fasilitator->can(Permission::LaporanVerifikasi->value))->toBeFalse();
});

it('mencabut permission yang dihapus dari matriks saat seeder dijalankan ulang', function (): void {
    $role = SpatieRole::where('name', Role::Petugas->value)->first();
    $role->givePermissionTo(Permission::PenggunaKelola->value);

    expect($role->fresh()->hasPermissionTo(Permission::PenggunaKelola->value))->toBeTrue();

    $this->seed(RoleSeeder::class);

    // syncPermissions, bukan givePermissionTo, kewenangan yang tidak
    // ada di matriks harus benar-benar lepas.
    expect($role->fresh()->hasPermissionTo(Permission::PenggunaKelola->value))->toBeFalse();
});

it('memberlakukan role yang sama untuk kanal web dan kanal token', function (): void {
    // Role disemai hanya pada guard web, tapi guard sanctum memakai
    // provider pengguna yang sama sehingga rolenya ikut berlaku.
    $petugas = User::factory()->withRole(Role::Petugas)->create();

    expect($petugas->hasRole(Role::Petugas->value))->toBeTrue()
        ->and($petugas->hasPermissionTo(Permission::LaporanKerjakan->value))->toBeTrue();
});

describe('penerbitan akun', function (): void {
    beforeEach(function (): void {
        $this->provinsi = Wilayah::factory()->create(['kode' => '51']);
        $this->kabA = Wilayah::factory()->anakDari($this->provinsi, '03')->create();
        $this->desaAKec = Wilayah::factory()->anakDari($this->kabA, '05')->create();
        $this->desaA = Wilayah::factory()->anakDari($this->desaAKec, '2001')->create();

        $this->kabB = Wilayah::factory()->anakDari($this->provinsi, '71')->create();

        $this->adminKabA = User::factory()->withRole(Role::AdminKabupaten)->create(['wilayah_id' => $this->kabA->id]);
        $this->superAdmin = User::factory()->withRole(Role::SuperAdmin)->create();
        $this->akun = app(AkunService::class);
    });

    it('mengizinkan pemerintah kabupaten membuat petugas di wilayahnya', function (): void {
        $petugas = $this->akun->buatPetugas($this->adminKabA, [
            'name' => 'I Made Petugas',
            'email' => 'petugas@badungkab.go.id',
            'wilayah_id' => $this->desaA->id,
        ]);

        expect($petugas->hasRole(Role::Petugas->value))->toBeTrue()
            ->and($petugas->wilayah_id)->toBe($this->desaA->id)
            ->and($petugas->dompet)->not->toBeNull();
    });

    it('menolak pembuatan petugas di wilayah kabupaten lain', function (): void {
        $this->akun->buatPetugas($this->adminKabA, [
            'name' => 'Petugas Selundupan',
            'email' => 'selundupan@contoh.id',
            'wilayah_id' => $this->kabB->id,
        ]);
    })->throws(AturanBisnisException::class, 'di luar kewenangan Anda');

    it('menolak pembuatan petugas oleh role tanpa kewenangan', function (): void {
        $warga = User::factory()->withRole(Role::Masyarakat)->create();

        $this->akun->buatPetugas($warga, [
            'name' => 'Petugas', 'email' => 'p@contoh.id', 'wilayah_id' => $this->desaA->id,
        ]);
    })->throws(AturanBisnisException::class, 'tidak berwenang');

    it('membuat toko menunggu verifikasi tapi akunnya tetap bisa dipakai', function (): void {
        $hasil = $this->akun->daftarUmkmMandiri([
            'nama' => 'Kriya Plastik Dalung',
            'pemilik_nama' => 'Ni Luh Ayu',
            'pemilik_email' => 'ayu@kriya.id',
            'password' => 'kata-sandi-panjang',
            'wilayah_id' => $this->desaA->id,
        ]);

        // Yang ditinjau tokonya, bukan hak orangnya memakai Resikita.
        // Akun yang mati mengunci pendaftar justru pada saat ia paling
        // perlu masuk: melihat status dan membaca alasan penolakan.
        expect($hasil['umkm']->status->value)->toBe('menunggu')
            ->and($hasil['akun']->is_active)->toBeTrue()
            ->and($hasil['akun']->hasRole(Role::Umkm->value))->toBeTrue()
            ->and($hasil['umkm']->dompet)->not->toBeNull();

        $this->akun->setujuiUmkm($hasil['umkm'], $this->superAdmin);

        expect($hasil['umkm']->fresh()->status->value)->toBe('aktif')
            ->and($hasil['akun']->fresh()->is_active)->toBeTrue();
    });

    it('menyimpan alasan penolakan tanpa mengunci akun pemiliknya', function (): void {
        $hasil = $this->akun->daftarUmkmMandiri([
            'nama' => 'Usaha Uji',
            'pemilik_nama' => 'Pemilik',
            'pemilik_email' => 'tolak@contoh.id',
            'password' => 'kata-sandi-panjang',
        ]);

        $this->akun->tolakUmkm(
            $hasil['umkm'],
            $this->superAdmin,
            'Alamat usaha belum lengkap sampai nama jalan dan nomor.',
        );

        $ditolak = $hasil['umkm']->fresh();

        expect($ditolak->status->value)->toBe('ditolak')
            ->and($ditolak->catatan_verifikasi)->toContain('belum lengkap')
            ->and($ditolak->ditinjau_oleh)->toBe($this->superAdmin->id)
            ->and($ditolak->ditinjau_at)->not->toBeNull()
            // Pemilik tetap bisa masuk, itulah satu-satunya cara ia
            // membaca alasan di atas dan memperbaikinya.
            ->and(User::where('email', 'tolak@contoh.id')->exists())->toBeTrue()
            ->and($hasil['akun']->fresh()->is_active)->toBeTrue();
    });

    it('menolak penolakan tanpa alasan', function (): void {
        $hasil = $this->akun->daftarUmkmMandiri([
            'nama' => 'Usaha Uji',
            'pemilik_nama' => 'Pemilik',
            'pemilik_email' => 'tanpa-alasan@contoh.id',
            'password' => 'kata-sandi-panjang',
        ]);

        $this->akun->tolakUmkm($hasil['umkm'], $this->superAdmin, '   ');
    })->throws(AturanBisnisException::class, 'Alasan penolakan wajib diisi');

    it('membuat pengelola bank sampah terikat pada unitnya', function (): void {
        $bank = BankSampah::create([
            'nama' => 'Bank Sampah Dalung',
            'wilayah_id' => $this->desaA->id,
        ]);

        $pengelola = $this->akun->buatPengelolaBankSampah($bank, [
            'name' => 'Pengelola',
            'email' => 'pengelola@banksampah.id',
        ]);

        expect($pengelola->hasRole(Role::BankSampah->value))->toBeTrue()
            ->and($pengelola->bank_sampah_id)->toBe($bank->id)
            ->and($pengelola->wilayah_id)->toBe($this->desaA->id)
            ->and($pengelola->can(Permission::BankSampahSetor->value))->toBeTrue();
    });
});
