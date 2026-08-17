<?php

declare(strict_types=1);

use App\Enums\AlasanRouting;
use App\Enums\PenanggungJawabType;
use App\Enums\Role;
use App\Enums\StatusLaporan;
use App\Models\LaporanKategori;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\Laporan\LaporanService;
use Database\Seeders\RoleSeeder;

/**
 * Waterfall penanggung jawab laporan (CLAUDE.md 9.2).
 *
 * Urutan kabupaten → provinsi → desa → fasilitator berdasar UU No.
 * 18/2008. Berkas ini yang menjaga urutan itu tidak bergeser tanpa
 * disadari saat kode di sekitarnya berubah.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    // Titik acuan seluruh uji: satu koordinat di dalam desa uji.
    $this->titik = ['latitude' => -8.5830000, 'longitude' => 115.1830000];

    $this->provinsi = Wilayah::factory()->create([
        'kode' => '51',
        'nama' => 'Bali',
        ...$this->titik,
    ]);

    $this->kabupaten = Wilayah::factory()
        ->anakDari($this->provinsi, '03')
        ->create(['nama' => 'Badung']);

    $this->kecamatan = Wilayah::factory()
        ->anakDari($this->kabupaten, '05')
        ->create(['nama' => 'Kuta Utara']);

    $this->desa = Wilayah::factory()
        ->anakDari($this->kecamatan, '2001')
        ->create(['nama' => 'Dalung']);

    $this->kategori = LaporanKategori::factory()->create();
    $this->pelapor = User::factory()->withRole(Role::Masyarakat)->create();
});

/** Buat laporan lewat Service, persis seperti yang dilakukan web dan API. */
function buatLaporanUji(array $titik, LaporanKategori $kategori, User $pelapor)
{
    return app(LaporanService::class)->buat($pelapor, [
        'kategori_id' => $kategori->id,
        'judul' => 'Tumpukan sampah di pinggir jalan',
        'deskripsi' => 'Sudah menumpuk sekitar tiga hari dan mulai berbau.',
        ...$titik,
    ]);
}

it('menyerahkan laporan ke admin kabupaten ketika kabupaten sudah terverifikasi', function (): void {
    // Ketiganya terverifikasi sekaligus, kabupaten harus tetap menang.
    $this->provinsi->update(['status_registrasi' => 'terverifikasi']);
    $this->kabupaten->update(['status_registrasi' => 'terverifikasi']);
    $this->desa->update(['status_registrasi' => 'terverifikasi']);

    User::factory()->withRole(Role::AdminProvinsi)->create(['wilayah_id' => $this->provinsi->id]);
    $adminKabupaten = User::factory()->withRole(Role::AdminKabupaten)->create(['wilayah_id' => $this->kabupaten->id]);
    User::factory()->withRole(Role::KepalaDesa)->create(['wilayah_id' => $this->desa->id]);

    $laporan = buatLaporanUji($this->titik, $this->kategori, $this->pelapor);

    expect($laporan->penanggung_jawab_id)->toBe($adminKabupaten->id)
        ->and($laporan->penanggung_jawab_type)->toBe(PenanggungJawabType::AdminKabupaten)
        ->and($laporan->alasan_routing)->toBe(AlasanRouting::KabupatenTerverifikasi);
});

it('turun ke admin provinsi ketika kabupaten belum terverifikasi', function (): void {
    $this->provinsi->update(['status_registrasi' => 'terverifikasi']);
    $this->desa->update(['status_registrasi' => 'terverifikasi']);

    $adminProvinsi = User::factory()->withRole(Role::AdminProvinsi)->create(['wilayah_id' => $this->provinsi->id]);
    User::factory()->withRole(Role::KepalaDesa)->create(['wilayah_id' => $this->desa->id]);

    $laporan = buatLaporanUji($this->titik, $this->kategori, $this->pelapor);

    expect($laporan->penanggung_jawab_id)->toBe($adminProvinsi->id)
        ->and($laporan->alasan_routing)->toBe(AlasanRouting::ProvinsiTerverifikasi);
});

it('turun ke kepala desa ketika hanya desa yang terverifikasi', function (): void {
    $this->desa->update(['status_registrasi' => 'terverifikasi']);

    $kepalaDesa = User::factory()->withRole(Role::KepalaDesa)->create(['wilayah_id' => $this->desa->id]);

    $laporan = buatLaporanUji($this->titik, $this->kategori, $this->pelapor);

    expect($laporan->penanggung_jawab_id)->toBe($kepalaDesa->id)
        ->and($laporan->alasan_routing)->toBe(AlasanRouting::DesaTerverifikasi);
});

it('jatuh ke fasilitator wilayah ketika tidak ada wilayah terverifikasi', function (): void {
    $fasilitator = User::factory()->withRole(Role::FasilitatorWilayah)->create();

    $laporan = buatLaporanUji($this->titik, $this->kategori, $this->pelapor);

    expect($laporan->penanggung_jawab_id)->toBe($fasilitator->id)
        ->and($laporan->penanggung_jawab_type)->toBe(PenanggungJawabType::FasilitatorWilayah)
        ->and($laporan->alasan_routing)->toBe(AlasanRouting::WilayahBelumTerjangkau);
});

it('melewati tingkat yang terverifikasi tapi tidak punya akun pengelola', function (): void {
    // Kabupaten terverifikasi tapi belum ada admin_kabupaten yang dibuat.
    // Laporan tidak boleh berhenti di tujuan buntu.
    $this->kabupaten->update(['status_registrasi' => 'terverifikasi']);
    $this->provinsi->update(['status_registrasi' => 'terverifikasi']);

    $adminProvinsi = User::factory()->withRole(Role::AdminProvinsi)->create(['wilayah_id' => $this->provinsi->id]);

    $laporan = buatLaporanUji($this->titik, $this->kategori, $this->pelapor);

    expect($laporan->penanggung_jawab_id)->toBe($adminProvinsi->id)
        ->and($laporan->alasan_routing)->toBe(AlasanRouting::ProvinsiTerverifikasi);
});

it('mengabaikan pejabat yang nonaktif', function (): void {
    $this->kabupaten->update(['status_registrasi' => 'terverifikasi']);

    User::factory()->withRole(Role::AdminKabupaten)->nonaktif()->create(['wilayah_id' => $this->kabupaten->id]);
    $fasilitator = User::factory()->withRole(Role::FasilitatorWilayah)->create();

    $laporan = buatLaporanUji($this->titik, $this->kategori, $this->pelapor);

    expect($laporan->penanggung_jawab_id)->toBe($fasilitator->id)
        ->and($laporan->alasan_routing)->toBe(AlasanRouting::WilayahBelumTerjangkau);
});

it('menyimpan keempat kolom wilayah hasil denormalisasi', function (): void {
    User::factory()->withRole(Role::FasilitatorWilayah)->create();

    $laporan = buatLaporanUji($this->titik, $this->kategori, $this->pelapor);

    expect($laporan->desa_id)->toBe($this->desa->id)
        ->and($laporan->kecamatan_id)->toBe($this->kecamatan->id)
        ->and($laporan->kabupaten_id)->toBe($this->kabupaten->id)
        ->and($laporan->provinsi_id)->toBe($this->provinsi->id);
});

it('menaikkan skor prioritas wilayah yang belum terjangkau', function (): void {
    User::factory()->withRole(Role::FasilitatorWilayah)->create();

    buatLaporanUji($this->titik, $this->kategori, $this->pelapor);
    buatLaporanUji($this->titik, $this->kategori, $this->pelapor);

    expect($this->desa->fresh()->skor_prioritas)->toBe(2)
        ->and($this->kabupaten->fresh()->skor_prioritas)->toBe(2)
        ->and($this->provinsi->fresh()->skor_prioritas)->toBe(2);
});

it('tidak menaikkan skor prioritas wilayah yang sudah terverifikasi', function (): void {
    // Provinsi sudah bergabung, kabupaten dan desa belum. Hanya yang
    // belum bergabung yang perlu didekati, jadi hanya itu yang naik.
    $this->provinsi->update(['status_registrasi' => 'terverifikasi']);
    User::factory()->withRole(Role::FasilitatorWilayah)->create();

    buatLaporanUji($this->titik, $this->kategori, $this->pelapor);

    expect($this->provinsi->fresh()->skor_prioritas)->toBe(0)
        ->and($this->kabupaten->fresh()->skor_prioritas)->toBe(1)
        ->and($this->desa->fresh()->skor_prioritas)->toBe(1);
});

it('memberi nomor tiket berformat RSK-YYYYMM-XXXXX', function (): void {
    User::factory()->withRole(Role::FasilitatorWilayah)->create();

    $laporan = buatLaporanUji($this->titik, $this->kategori, $this->pelapor);

    expect($laporan->tiket)->toMatch('/^RSK-\d{6}-\d{5}$/')
        ->and($laporan->tiket)->toContain(now()->format('Ym'))
        ->and($laporan->status)->toBe(StatusLaporan::Baru);
});

it('membagi beban laporan ke fasilitator yang paling ringan', function (): void {
    $sibuk = User::factory()->withRole(Role::FasilitatorWilayah)->create();
    $luang = User::factory()->withRole(Role::FasilitatorWilayah)->create();

    // Laporan pertama jatuh ke salah satu; berikutnya harus ke yang lain.
    $pertama = buatLaporanUji($this->titik, $this->kategori, $this->pelapor);
    $kedua = buatLaporanUji($this->titik, $this->kategori, $this->pelapor);

    expect($pertama->penanggung_jawab_id)->not->toBe($kedua->penanggung_jawab_id)
        ->and([$pertama->penanggung_jawab_id, $kedua->penanggung_jawab_id])
        ->toEqualCanonicalizing([$sibuk->id, $luang->id]);
});
