<?php

declare(strict_types=1);

use App\Enums\TingkatWilayah;
use App\Models\Wilayah;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\WilayahSeeder;

/**
 * Pemilih wilayah bertingkat dan angka publik lewat API.
 *
 * Endpoint di berkas ini sempat dijanjikan API-DOCS tanpa pernah ada di
 * kode. Uji ini yang menjaga janji itu tetap benar.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->seed(WilayahSeeder::class);
    $this->seed(MasterDataSeeder::class);
});

describe('pemilih wilayah bertingkat', function (): void {
    it('membuka daftar provinsi tanpa token', function (): void {
        $respons = $this->getJson('/api/v1/wilayah/provinsi')->assertOk();

        expect($respons->json('data'))->toHaveCount(38)
            ->and($respons->json('data.0.tingkat'))->toBe('provinsi');
    });

    it('menurunkan satu tingkat lewat endpoint anak', function (): void {
        $bali = Wilayah::query()->where('kode', '51')->firstOrFail();

        $respons = $this->getJson("/api/v1/wilayah/{$bali->id}/anak")->assertOk();

        $kode = collect($respons->json('data'))->pluck('kode');

        expect($kode)->toContain('51.03')
            ->and($respons->json('data.0.tingkat'))->toBe('kabupaten');
    });

    it('mencari lintas tingkat tanpa perlu tahu induknya', function (): void {
        $respons = $this->getJson('/api/v1/wilayah/cari?q=Jimbaran')->assertOk();

        expect(collect($respons->json('data'))->pluck('nama'))->toContain('Jimbaran');
    });

    it('menolak pencarian yang terlalu pendek', function (): void {
        $this->getJson('/api/v1/wilayah/cari?q=ba')
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    });

    it('tidak membaca ruas beruas sebagai id wilayah', function (): void {
        // Tanpa urutan route yang benar, "provinsi" akan dicoba dibaca
        // sebagai id dan endpoint pemilih bertingkat mati diam-diam.
        $this->getJson('/api/v1/wilayah/provinsi')->assertOk();
        $this->getJson('/api/v1/wilayah/cari?q=Badung')->assertOk();
    });
});

describe('resolusi koordinat', function (): void {
    it('mengembalikan empat tingkat wilayah dari sepasang koordinat', function (): void {
        $bali = Wilayah::query()->where('kode', '51')->firstOrFail();

        $respons = $this->postJson('/api/v1/wilayah/resolusi', [
            'latitude' => (float) $bali->latitude,
            'longitude' => (float) $bali->longitude,
        ])->assertOk();

        expect($respons->json('data.ditemukan'))->toBeTrue()
            ->and($respons->json('data.provinsi.kode'))->toBe('51');
    });

    it('menolak koordinat di luar rentang yang mungkin', function (): void {
        $this->postJson('/api/v1/wilayah/resolusi', ['latitude' => 200, 'longitude' => 0])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['latitude']]);
    });

    it('mengusulkan wilayah terdekat untuk mengisi domisili', function (): void {
        $bali = Wilayah::query()->where('kode', '51')->firstOrFail();

        $respons = $this->getJson(sprintf(
            '/api/v1/wilayah/terdekat?latitude=%s&longitude=%s&tingkat=%s',
            $bali->latitude,
            $bali->longitude,
            TingkatWilayah::Desa->value,
        ))->assertOk();

        expect($respons->json('data'))->not->toBeEmpty()
            ->and($respons->json('data.0.tingkat'))->toBe('desa');
    });
});

describe('angka publik', function (): void {
    it('menyajikan ringkasan dan pemakaian fitur suara tanpa token', function (): void {
        $respons = $this->getJson('/api/v1/publik/statistik')->assertOk();

        $respons->assertJsonStructure([
            'data' => [
                'ringkasan' => ['wilayah_bergabung', 'total_laporan', 'bank_sampah', 'berat_teralihkan_kg', 'nilai_ke_warga'],
                'fitur_suara' => ['laporan_suara', 'persen_laporan_suara', 'artikel_didengarkan'],
            ],
        ]);
    });

    it('membuka peta fasilitas tanpa menyertakan titik laporan', function (): void {
        $respons = $this->getJson('/api/v1/publik/peta')->assertOk();

        // Jenis yang boleh muncul hanya fasilitas. Koordinat laporan
        // menunjuk tempat yang bisa jadi halaman rumah seseorang.
        foreach ($respons->json('data.titik') as $titik) {
            expect($titik['jenis'])->toBeIn(['bank_sampah', 'tps', 'tps3r']);
        }
    });

    it('mendaftar wilayah yang pemerintahnya sudah bergabung', function (): void {
        Wilayah::query()->where('kode', '51.03')->update([
            'status_registrasi' => 'terverifikasi',
            'terverifikasi_at' => now(),
        ]);

        $respons = $this->getJson('/api/v1/publik/wilayah-terdaftar')->assertOk();

        expect(collect($respons->json('data'))->pluck('kode'))->toContain('51.03');
    });
});
