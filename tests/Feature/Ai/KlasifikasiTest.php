<?php

declare(strict_types=1);

use App\Enums\KategoriSampah;
use App\Enums\Role;
use App\Models\BankSampah;
use App\Models\BankSampahHarga;
use App\Models\KlasifikasiSampah;
use App\Models\User;
use App\Models\Wilayah;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Klasifikasi sampah lewat kanal API (CLAUDE.md 10.1).
 *
 * Yang diuji bukan kecerdasan modelnya, itu di luar kendali repo ini,
 * melainkan sikap sistem terhadap jawaban model: kategori di luar enum
 * ditolak, nilai rupiah tidak diambil mentah-mentah, dan peringatan
 * penanganan B3 tidak bergantung pada model mengingat menyebutnya.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    Storage::fake('public');

    $this->user = User::factory()->withRole(Role::Masyarakat)->create();
});

/** Palsukan satu jawaban Gemini berbentuk JSON terstruktur. */
function gemininyaMenjawab(array $isi): void
{
    Http::fake([
        '*generativelanguage*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [['text' => json_encode($isi)]]],
                'finishReason' => 'STOP',
            ]],
        ]),
    ]);
}

function fotoSampah(): UploadedFile
{
    return UploadedFile::fake()->image('sampah.jpg', 800, 600);
}

it('menyimpan hasil klasifikasi beserta jejak versi modelnya', function (): void {
    gemininyaMenjawab([
        'jenis' => 'Botol plastik PET',
        'kategori' => 'anorganik',
        'material' => 'PET',
        'confidence' => 94.2,
        'dapat_didaur_ulang' => true,
        'estimasi_berat_kg' => 0.025,
        'estimasi_nilai_rupiah' => 125,
        'langkah_pengolahan' => ['Bilas botol sampai bersih', 'Pipihkan badan botol'],
        'rekomendasi_daur_ulang' => 'Dapat disetor ke bank sampah terdekat.',
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/klasifikasi', ['foto' => fotoSampah()]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.jenis', 'Botol plastik PET')
        ->assertJsonPath('data.kategori', 'anorganik')
        ->assertJsonPath('data.confidence', 94.2)
        ->assertJsonPath('data.keyakinan_rendah', false);

    $tersimpan = KlasifikasiSampah::sole();

    expect($tersimpan->user_id)->toBe($this->user->id)
        ->and($tersimpan->kategori)->toBe(KategoriSampah::Anorganik)
        ->and($tersimpan->model_version)->toBe(config('services.gemini.model'))
        ->and($tersimpan->raw_response)->toBeArray();

    Storage::disk('public')->assertExists($tersimpan->foto_path);
});

it('menolak kategori di luar enum dan menjatuhkannya ke residu', function (): void {
    // Nilai khas skema lama yang sudah tidak berlaku. Kalau ini lolos
    // apa adanya, statistik pemilahan berhenti bisa dijumlahkan.
    gemininyaMenjawab([
        'jenis' => 'Kantong kresek',
        'kategori' => 'anorganik_plastik',
        'confidence' => 88,
        'dapat_didaur_ulang' => true,
        'langkah_pengolahan' => ['Keringkan kantong'],
        'rekomendasi_daur_ulang' => 'Kumpulkan terpisah.',
    ]);

    $this->actingAs($this->user)
        ->postJson('/api/v1/klasifikasi', ['foto' => fotoSampah()])
        ->assertCreated()
        ->assertJsonPath('data.kategori', 'residu');
});

it('menambahkan peringatan penanganan untuk limbah B3 meski model tidak menyebutnya', function (): void {
    gemininyaMenjawab([
        'jenis' => 'Baterai bekas',
        'kategori' => 'b3',
        'confidence' => 91,
        'dapat_didaur_ulang' => false,
        'langkah_pengolahan' => ['Simpan dalam wadah tertutup'],
        'rekomendasi_daur_ulang' => 'Tidak masuk daur ulang biasa.',
        'catatan' => null,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/klasifikasi', ['foto' => fotoSampah()]);

    $response->assertCreated()
        ->assertJsonPath('data.butuh_penanganan_khusus', true);

    expect($response->json('data.catatan'))->toContain('limbah B3');
});

it('memakai harga bank sampah di wilayah pengguna, bukan tebakan model', function (): void {
    $provinsi = Wilayah::factory()->create();
    $bank = BankSampah::factory()->create(['wilayah_id' => $provinsi->id]);

    BankSampahHarga::factory()->harga(4_000)->create([
        'bank_sampah_id' => $bank->id,
        'kategori' => KategoriSampah::Anorganik,
    ]);

    $this->user->forceFill(['wilayah_id' => $provinsi->id])->save();

    gemininyaMenjawab([
        'jenis' => 'Kardus bekas',
        'kategori' => 'anorganik',
        'confidence' => 90,
        'dapat_didaur_ulang' => true,
        'estimasi_berat_kg' => 2.0,
        // Tebakan model sengaja dibuat jauh meleset dari harga nyata.
        'estimasi_nilai_rupiah' => 50_000,
        'langkah_pengolahan' => ['Lipat kardus'],
        'rekomendasi_daur_ulang' => 'Setor ke bank sampah.',
    ]);

    $this->actingAs($this->user)
        ->postJson('/api/v1/klasifikasi', ['foto' => fotoSampah()])
        ->assertCreated()
        ->assertJsonPath('data.estimasi_nilai', 8_000);
});

it('tidak memberi nilai rupiah pada kategori yang tidak bernilai ekonomi', function (): void {
    gemininyaMenjawab([
        'jenis' => 'Sisa sayuran',
        'kategori' => 'organik',
        'confidence' => 95,
        'dapat_didaur_ulang' => false,
        'estimasi_berat_kg' => 1.5,
        'estimasi_nilai_rupiah' => 3_000,
        'langkah_pengolahan' => ['Masukkan ke komposter'],
        'rekomendasi_daur_ulang' => 'Cocok dikompos di rumah.',
    ]);

    $this->actingAs($this->user)
        ->postJson('/api/v1/klasifikasi', ['foto' => fotoSampah()])
        ->assertCreated()
        ->assertJsonPath('data.estimasi_nilai', null);
});

it('menandai hasil berkeyakinan rendah supaya tidak ditampilkan sebagai kepastian', function (): void {
    gemininyaMenjawab([
        'jenis' => 'Objek tidak jelas',
        'kategori' => 'residu',
        'confidence' => 22,
        'dapat_didaur_ulang' => false,
        'langkah_pengolahan' => ['Ambil ulang foto dengan cahaya cukup'],
        'rekomendasi_daur_ulang' => 'Belum dapat ditentukan.',
    ]);

    $this->actingAs($this->user)
        ->postJson('/api/v1/klasifikasi', ['foto' => fotoSampah()])
        ->assertCreated()
        ->assertJsonPath('data.keyakinan_rendah', true);
});

it('tidak menyisakan foto di disk ketika layanan AI gagal', function (): void {
    Http::fake(['*generativelanguage*' => Http::response(status: 503)]);

    $this->actingAs($this->user)
        ->postJson('/api/v1/klasifikasi', ['foto' => fotoSampah()])
        ->assertStatus(503)
        ->assertJsonPath('success', false);

    expect(KlasifikasiSampah::count())->toBe(0)
        ->and(Storage::disk('public')->allFiles('klasifikasi'))->toBeEmpty();
});

it('menolak permintaan tanpa token', function (): void {
    $this->postJson('/api/v1/klasifikasi', ['foto' => fotoSampah()])
        ->assertUnauthorized()
        ->assertJsonPath('success', false);
});

it('menolak berkas yang bukan gambar', function (): void {
    $this->actingAs($this->user)
        ->postJson('/api/v1/klasifikasi', [
            'foto' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ])
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['errors' => ['foto']]);
});

it('hanya menampilkan riwayat milik penggunanya sendiri', function (): void {
    gemininyaMenjawab([
        'jenis' => 'Kaleng aluminium',
        'kategori' => 'anorganik',
        'confidence' => 90,
        'dapat_didaur_ulang' => true,
        'langkah_pengolahan' => ['Bilas kaleng'],
        'rekomendasi_daur_ulang' => 'Setor ke bank sampah.',
    ]);

    $this->actingAs($this->user)->postJson('/api/v1/klasifikasi', ['foto' => fotoSampah()]);

    $orangLain = User::factory()->withRole(Role::Masyarakat)->create();

    $this->actingAs($orangLain)
        ->getJson('/api/v1/klasifikasi/riwayat')
        ->assertOk()
        ->assertJsonPath('meta.total', 0);

    $this->actingAs($orangLain)
        ->getJson('/api/v1/klasifikasi/'.KlasifikasiSampah::sole()->id)
        ->assertForbidden();
});
