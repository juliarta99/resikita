<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\BankSampah;
use App\Models\Laporan;
use App\Models\LaporanKategori;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\Laporan\LaporanService;
use App\Services\Wilayah\WilayahScopeService;
use Database\Seeders\RoleSeeder;

/**
 * Pembatasan cakupan wilayah (CLAUDE.md 9.5).
 *
 * Kebocoran data lintas daerah adalah kegagalan fatal, jadi berkas ini
 * menguji dua arah sekaligus: yang seharusnya terlihat memang terlihat,
 * dan yang seharusnya tidak benar-benar tidak.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $this->scope = app(WilayahScopeService::class);
    $this->kategori = LaporanKategori::factory()->create();

    // Dua kabupaten dalam satu provinsi, masing-masing satu desa.
    $this->provinsi = Wilayah::factory()->create([
        'kode' => '51', 'nama' => 'Bali',
        'latitude' => -8.5, 'longitude' => 115.1,
        'status_registrasi' => 'terverifikasi',
    ]);

    $this->kabA = Wilayah::factory()->anakDari($this->provinsi, '03')
        ->create(['nama' => 'Badung', 'status_registrasi' => 'terverifikasi', 'latitude' => -8.58, 'longitude' => 115.18]);
    $this->kecA = Wilayah::factory()->anakDari($this->kabA, '05')->create(['latitude' => -8.58, 'longitude' => 115.18]);
    $this->desaA = Wilayah::factory()->anakDari($this->kecA, '2001')->create(['latitude' => -8.58, 'longitude' => 115.18]);

    $this->kabB = Wilayah::factory()->anakDari($this->provinsi, '71')
        ->create(['nama' => 'Denpasar', 'status_registrasi' => 'terverifikasi', 'latitude' => -8.67, 'longitude' => 115.21]);
    $this->kecB = Wilayah::factory()->anakDari($this->kabB, '01')->create(['latitude' => -8.67, 'longitude' => 115.21]);
    $this->desaB = Wilayah::factory()->anakDari($this->kecB, '1001')->create(['latitude' => -8.67, 'longitude' => 115.21]);

    $this->adminA = User::factory()->withRole(Role::AdminKabupaten)->create(['wilayah_id' => $this->kabA->id]);
    $this->adminB = User::factory()->withRole(Role::AdminKabupaten)->create(['wilayah_id' => $this->kabB->id]);
    $this->adminProvinsi = User::factory()->withRole(Role::AdminProvinsi)->create(['wilayah_id' => $this->provinsi->id]);
    $this->kepalaDesaA = User::factory()->withRole(Role::KepalaDesa)->create(['wilayah_id' => $this->desaA->id]);

    $pelapor = User::factory()->withRole(Role::Masyarakat)->create();
    $service = app(LaporanService::class);

    $this->laporanA = $service->buat($pelapor, [
        'kategori_id' => $this->kategori->id,
        'judul' => 'Sampah di Badung', 'deskripsi' => 'Tumpukan sampah.',
        'latitude' => -8.58, 'longitude' => 115.18,
    ]);

    $this->laporanB = $service->buat($pelapor, [
        'kategori_id' => $this->kategori->id,
        'judul' => 'Sampah di Denpasar', 'deskripsi' => 'Tumpukan sampah.',
        'latitude' => -8.67, 'longitude' => 115.21,
    ]);
});

it('membatasi admin kabupaten pada laporan kabupatennya sendiri', function (): void {
    $terlihat = $this->scope->applyLaporan(Laporan::query(), $this->adminA)->pluck('id');

    expect($terlihat)->toContain($this->laporanA->id)
        ->and($terlihat)->not->toContain($this->laporanB->id);
});

it('tidak membocorkan laporan kabupaten lain ke admin kabupaten tetangga', function (): void {
    $terlihat = $this->scope->applyLaporan(Laporan::query(), $this->adminB)->pluck('id');

    expect($terlihat)->toContain($this->laporanB->id)
        ->and($terlihat)->not->toContain($this->laporanA->id);
});

it('memberi admin provinsi seluruh laporan dalam provinsinya', function (): void {
    $terlihat = $this->scope->applyLaporan(Laporan::query(), $this->adminProvinsi)->pluck('id');

    expect($terlihat)->toContain($this->laporanA->id)
        ->and($terlihat)->toContain($this->laporanB->id);
});

it('membatasi kepala desa pada desanya sendiri', function (): void {
    $terlihat = $this->scope->applyLaporan(Laporan::query(), $this->kepalaDesaA)->pluck('id');

    expect($terlihat)->toContain($this->laporanA->id)
        ->and($terlihat)->not->toContain($this->laporanB->id);
});

it('memberi role platform akses lintas wilayah', function (): void {
    $admin = User::factory()->withRole(Role::Admin)->create();

    expect($this->scope->applyLaporan(Laporan::query(), $admin)->count())->toBe(2);
});

it('tidak memberi apa pun kepada role pemerintahan tanpa wilayah', function (): void {
    // Keadaan tidak sah: pengajuan wilayahnya belum disetujui. Jawaban
    // yang aman adalah nol baris, bukan seluruh data nasional.
    $tanpaWilayah = User::factory()->withRole(Role::AdminKabupaten)->create(['wilayah_id' => null]);

    expect($this->scope->applyLaporan(Laporan::query(), $tanpaWilayah)->count())->toBe(0);
});

it('membatasi petugas pada laporan yang ditugaskan kepadanya', function (): void {
    $petugas = User::factory()->withRole(Role::Petugas)->create(['wilayah_id' => $this->kabA->id]);

    $this->laporanA->update(['status' => 'diverifikasi']);
    app(LaporanService::class)->tugaskan($this->laporanA->fresh(), $petugas, $this->adminA);

    $terlihat = $this->scope->applyLaporan(Laporan::query(), $petugas)->pluck('id');

    expect($terlihat)->toHaveCount(1)
        ->and($terlihat)->toContain($this->laporanA->id);
});

it('membatasi masyarakat pada laporannya sendiri', function (): void {
    $warga = User::factory()->withRole(Role::Masyarakat)->create();

    expect($this->scope->applyLaporan(Laporan::query(), $warga)->count())->toBe(0);
});

it('membatasi entitas berkolom wilayah_id lewat keturunan kode wilayah', function (): void {
    $bankA = BankSampah::create(['nama' => 'Bank Sampah Dalung', 'wilayah_id' => $this->desaA->id]);
    $bankB = BankSampah::create(['nama' => 'Bank Sampah Denpasar', 'wilayah_id' => $this->desaB->id]);

    // Admin kabupaten A melihat bank sampah di desa-desa bawahannya,
    // meski wilayah_id-nya menunjuk desa, bukan kabupaten.
    $terlihatA = $this->scope->applyWilayah(BankSampah::query(), $this->adminA)->pluck('id');
    expect($terlihatA)->toContain($bankA->id)->and($terlihatA)->not->toContain($bankB->id);

    // Admin provinsi melihat keduanya.
    $terlihatProvinsi = $this->scope->applyWilayah(BankSampah::query(), $this->adminProvinsi)->pluck('id');
    expect($terlihatProvinsi)->toHaveCount(2);
});

it('menyatakan kewenangan atas wilayah dengan benar', function (): void {
    expect($this->scope->berwenangAtas($this->adminA, $this->desaA->id))->toBeTrue()
        ->and($this->scope->berwenangAtas($this->adminA, $this->kabA->id))->toBeTrue()
        ->and($this->scope->berwenangAtas($this->adminA, $this->desaB->id))->toBeFalse()
        ->and($this->scope->berwenangAtas($this->adminA, $this->provinsi->id))->toBeFalse()
        ->and($this->scope->berwenangAtas($this->adminProvinsi, $this->desaB->id))->toBeTrue();
});
