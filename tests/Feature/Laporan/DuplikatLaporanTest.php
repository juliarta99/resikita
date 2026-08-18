<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\StatusLaporan;
use App\Exceptions\AturanBisnisException;
use App\Models\Laporan;
use App\Models\LaporanKategori;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\Laporan\DuplikatDetectorService;
use App\Services\Laporan\LaporanService;
use App\Support\Haversine;
use Database\Seeders\RoleSeeder;

/**
 * Deteksi laporan kembar dalam radius pendek (CLAUDE.md 9.3).
 *
 * Yang diuji bukan hanya "apakah kandidat ditemukan", tapi juga sikap
 * sistem terhadapnya: menawarkan penggabungan, bukan menolak.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $this->lat = -8.5830000;
    $this->lng = 115.1830000;

    $provinsi = Wilayah::factory()->create([
        'kode' => '51',
        'latitude' => $this->lat,
        'longitude' => $this->lng,
    ]);
    Wilayah::factory()->anakDari($provinsi, '03')->create();

    $this->kategori = LaporanKategori::factory()->create();
    $this->pelapor = User::factory()->withRole(Role::Masyarakat)->create();
    User::factory()->withRole(Role::FasilitatorWilayah)->create();

    $this->service = app(LaporanService::class);
    $this->detector = app(DuplikatDetectorService::class);
});

/** Geser sebuah titik ke utara sejauh sekian meter. */
function geserMeter(float $lat, float $meter): float
{
    return $lat + ($meter / 111_320);
}

it('menemukan laporan kembar dalam radius 50 meter', function (): void {
    $this->service->buat($this->pelapor, [
        'kategori_id' => $this->kategori->id,
        'judul' => 'Tumpukan sampah',
        'deskripsi' => 'Menumpuk di bahu jalan.',
        'latitude' => $this->lat,
        'longitude' => $this->lng,
    ]);

    // 30 meter dari titik pertama, masih di dalam radius default.
    $kandidat = $this->detector->cariKandidat(geserMeter($this->lat, 30), $this->lng);

    expect($kandidat)->toHaveCount(1);
});

it('tidak menganggap kembar laporan di luar radius', function (): void {
    $this->service->buat($this->pelapor, [
        'kategori_id' => $this->kategori->id,
        'judul' => 'Tumpukan sampah',
        'deskripsi' => 'Menumpuk di bahu jalan.',
        'latitude' => $this->lat,
        'longitude' => $this->lng,
    ]);

    // 150 meter, jauh di luar radius 50 meter.
    $kandidat = $this->detector->cariKandidat(geserMeter($this->lat, 150), $this->lng);

    expect($kandidat)->toBeEmpty();
});

it('mengabaikan laporan yang sudah selesai saat mencari kembaran', function (): void {
    $lama = $this->service->buat($this->pelapor, [
        'kategori_id' => $this->kategori->id,
        'judul' => 'Tumpukan sampah',
        'deskripsi' => 'Sudah dibersihkan bulan lalu.',
        'latitude' => $this->lat,
        'longitude' => $this->lng,
    ]);

    $lama->update(['status' => StatusLaporan::Selesai, 'selesai_at' => now()]);

    // Tumpukan baru di titik yang sama tidak boleh terhalang laporan
    // yang penanganannya sudah tuntas.
    expect($this->detector->cariKandidat($this->lat, $this->lng))->toBeEmpty();
});

it('tetap menyimpan laporan kedua alih-alih menolaknya', function (): void {
    $pertama = $this->service->buat($this->pelapor, [
        'kategori_id' => $this->kategori->id,
        'judul' => 'Tumpukan sampah',
        'deskripsi' => 'Laporan warga pertama.',
        'latitude' => $this->lat,
        'longitude' => $this->lng,
    ]);

    $pelaporLain = User::factory()->withRole(Role::Masyarakat)->create();

    $kedua = $this->service->buat($pelaporLain, [
        'kategori_id' => $this->kategori->id,
        'judul' => 'Sampah menumpuk',
        'deskripsi' => 'Laporan warga kedua di titik yang sama.',
        'latitude' => $this->lat,
        'longitude' => $this->lng,
    ]);

    expect(Laporan::count())->toBe(2)
        ->and($kedua->exists)->toBeTrue()
        ->and($kedua->pelapor_id)->toBe($pelaporLain->id)
        ->and($pertama->id)->not->toBe($kedua->id);
});

it('menggabungkan laporan kembar tanpa menghapusnya', function (): void {
    $induk = $this->service->buat($this->pelapor, [
        'kategori_id' => $this->kategori->id,
        'judul' => 'Tumpukan sampah',
        'deskripsi' => 'Laporan pertama.',
        'latitude' => $this->lat,
        'longitude' => $this->lng,
    ]);

    $pelaporLain = User::factory()->withRole(Role::Masyarakat)->create();

    $kembar = $this->service->buat($pelaporLain, [
        'kategori_id' => $this->kategori->id,
        'judul' => 'Sampah menumpuk',
        'deskripsi' => 'Laporan kedua.',
        'latitude' => $this->lat,
        'longitude' => $this->lng,
    ], gabungKeId: $induk->id);

    expect($kembar->is_duplikat)->toBeTrue()
        ->and($kembar->duplikat_of_id)->toBe($induk->id)
        ->and($kembar->status)->toBe(StatusLaporan::Digabung)
        ->and(Laporan::find($kembar->id))->not->toBeNull()
        ->and($this->detector->jumlahGabungan($induk))->toBe(1);
});

it('memindahkan rantai duplikat ke induk baru agar tidak bertingkat', function (): void {
    $a = $this->service->buat($this->pelapor, [
        'kategori_id' => $this->kategori->id,
        'judul' => 'A', 'deskripsi' => 'Laporan A.',
        'latitude' => $this->lat, 'longitude' => $this->lng,
    ]);
    $b = $this->service->buat($this->pelapor, [
        'kategori_id' => $this->kategori->id,
        'judul' => 'B', 'deskripsi' => 'Laporan B.',
        'latitude' => $this->lat, 'longitude' => $this->lng,
    ]);
    $c = $this->service->buat($this->pelapor, [
        'kategori_id' => $this->kategori->id,
        'judul' => 'C', 'deskripsi' => 'Laporan C.',
        'latitude' => $this->lat, 'longitude' => $this->lng,
    ]);

    // C digabung ke B, lalu B digabung ke A. C harus ikut pindah ke A.
    $this->detector->gabungkan($c, $b);
    $this->detector->gabungkan($b, $a);

    expect($c->fresh()->duplikat_of_id)->toBe($a->id)
        ->and($b->fresh()->duplikat_of_id)->toBe($a->id);
});

it('menolak penggabungan ke laporan yang sendirinya sudah digabung', function (): void {
    $a = $this->service->buat($this->pelapor, [
        'kategori_id' => $this->kategori->id,
        'judul' => 'A', 'deskripsi' => 'Laporan A.',
        'latitude' => $this->lat, 'longitude' => $this->lng,
    ]);
    $b = $this->service->buat($this->pelapor, [
        'kategori_id' => $this->kategori->id,
        'judul' => 'B', 'deskripsi' => 'Laporan B.',
        'latitude' => $this->lat, 'longitude' => $this->lng,
    ]);
    $c = $this->service->buat($this->pelapor, [
        'kategori_id' => $this->kategori->id,
        'judul' => 'C', 'deskripsi' => 'Laporan C.',
        'latitude' => $this->lat, 'longitude' => $this->lng,
    ]);

    $this->detector->gabungkan($b, $a);

    $this->detector->gabungkan($c, $b->fresh());
})->throws(AturanBisnisException::class, 'Pilih laporan induknya');

it('menghitung jarak Haversine dengan benar', function (): void {
    // Satu derajat lintang kira-kira 111,32 km.
    $jarak = Haversine::jarakKm(0, 0, 1, 0);

    expect(round($jarak, 1))->toBe(111.2);

    // Dua titik identik berjarak nol, tanpa galat domain acos.
    expect(Haversine::jarakMeter($this->lat, $this->lng, $this->lat, $this->lng))->toBe(0.0);
});
