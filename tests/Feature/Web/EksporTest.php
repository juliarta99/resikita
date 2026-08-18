<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Livewire\Pemerintahan\LaporanManager;
use App\Models\Laporan;
use App\Models\LaporanKategori;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\Analitik\EksporService;
use App\Services\Laporan\LaporanService;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Livewire;

/**
 * Ekspor rekap laporan (CLAUDE.md 7.3).
 *
 * Yang diuji bukan bentuk berkasnya, melainkan satu hal yang paling
 * mudah terlupakan: **cakupan wilayah tetap berlaku pada berkas
 * unduhan**. Orang mengingat membatasi apa yang terlihat di layar, lalu
 * membiarkan tombol unduh mengambil seluruh tabel, dan kebocoran itu
 * berpindah tangan lewat surel tanpa meninggalkan jejak di sistem.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $this->provinsi = Wilayah::factory()->terverifikasi()->create(['kode' => '51', 'nama' => 'Bali']);
    $this->kabupatenA = Wilayah::factory()->anakDari($this->provinsi, '03')->terverifikasi()->create(['nama' => 'Badung']);
    $this->kabupatenB = Wilayah::factory()->anakDari($this->provinsi, '04')->terverifikasi()->create(['nama' => 'Gianyar']);

    $this->kategori = LaporanKategori::factory()->create();

    User::factory()->withRole(Role::FasilitatorWilayah)->create();

    $this->ekspor = app(EksporService::class);
});

function laporanUntukEkspor(Wilayah $kabupaten, string $judul): Laporan
{
    $pelapor = User::factory()->withRole(Role::Masyarakat)->create();

    $laporan = app(LaporanService::class)->buat($pelapor, [
        'kategori_id' => LaporanKategori::query()->value('id'),
        'judul' => $judul,
        'deskripsi' => 'Deskripsi yang cukup panjang untuk lolos validasi bentuk.',
        'latitude' => -8.5830000,
        'longitude' => 115.1830000,
    ]);

    $laporan->forceFill([
        'provinsi_id' => $kabupaten->parent_id,
        'kabupaten_id' => $kabupaten->id,
    ])->save();

    return $laporan->fresh();
}

/** Jalankan unduhan dan kembalikan isinya sebagai teks. */
function isiUnduhan($respons): string
{
    ob_start();
    $respons->sendContent();

    return (string) ob_get_clean();
}

it('membatasi berkas unduhan pada cakupan wilayah pengunduhnya', function (): void {
    laporanUntukEkspor($this->kabupatenA, 'Sampah di Badung');
    laporanUntukEkspor($this->kabupatenB, 'Sampah di Gianyar');

    $adminBadung = User::factory()->withRole(Role::AdminKabupaten)->create([
        'wilayah_id' => $this->kabupatenA->id,
    ]);

    $isi = isiUnduhan($this->ekspor->laporan($adminBadung));

    expect($isi)->toContain('Sampah di Badung')
        ->not->toContain('Sampah di Gianyar');
});

it('memberi admin provinsi seluruh kabupaten dalam provinsinya', function (): void {
    laporanUntukEkspor($this->kabupatenA, 'Sampah di Badung');
    laporanUntukEkspor($this->kabupatenB, 'Sampah di Gianyar');

    $adminProvinsi = User::factory()->withRole(Role::AdminProvinsi)->create([
        'wilayah_id' => $this->provinsi->id,
    ]);

    $isi = isiUnduhan($this->ekspor->laporan($adminProvinsi));

    expect($isi)->toContain('Sampah di Badung')->toContain('Sampah di Gianyar');
});

it('menghasilkan berkas kosong untuk role pemerintahan tanpa wilayah', function (): void {
    laporanUntukEkspor($this->kabupatenA, 'Sampah di Badung');

    $tanpaWilayah = User::factory()->withRole(Role::AdminKabupaten)->create(['wilayah_id' => null]);

    // Cakupan yang tidak bisa ditentukan menghasilkan tidak ada data,
    // bukan semua data.
    expect(isiUnduhan($this->ekspor->laporan($tanpaWilayah)))
        ->not->toContain('Sampah di Badung');
});

it('mengikuti penyaring yang diserahkan layar pemanggil', function (): void {
    laporanUntukEkspor($this->kabupatenA, 'Tumpukan sampah liar');
    laporanUntukEkspor($this->kabupatenA, 'Pembakaran sampah malam');

    $admin = User::factory()->withRole(Role::AdminKabupaten)->create([
        'wilayah_id' => $this->kabupatenA->id,
    ]);

    $isi = isiUnduhan($this->ekspor->laporan(
        $admin,
        fn (Builder $q) => $q->where('judul', 'like', '%Pembakaran%'),
    ));

    expect($isi)->toContain('Pembakaran sampah malam')
        ->not->toContain('Tumpukan sampah liar');
});

it('menamai berkas dengan wilayah dan tanggalnya', function (): void {
    $admin = User::factory()->withRole(Role::AdminKabupaten)->create([
        'wilayah_id' => $this->kabupatenA->id,
    ]);

    $respons = $this->ekspor->laporan($admin);

    // `laporan.xls` tidak memberi tahu apa pun tentang isinya seminggu
    // kemudian, ketika berkasnya sudah bercampur di folder unduhan.
    expect($respons->headers->get('content-disposition'))
        ->toContain('resikita-laporan-badung-'.now()->format('Ymd').'.xls');
});

it('menyediakan tombol unduh di layar manajemen laporan', function (): void {
    $admin = User::factory()->withRole(Role::AdminKabupaten)->create([
        'wilayah_id' => $this->kabupatenA->id,
    ]);

    Livewire::actingAs($admin)
        ->test(LaporanManager::class)
        ->assertSee('Unduh rekap')
        ->call('ekspor')
        ->assertFileDownloaded();
});
