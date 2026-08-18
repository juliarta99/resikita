<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Artikel;
use App\Models\LaporanKategori;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\Auth\AuthService;
use Database\Seeders\RoleSeeder;

/**
 * Kontrak response API (CLAUDE.md 12).
 *
 * Bentuk amplop adalah janji kepada `resikita-mobile`. Berkas ini yang
 * menjaga janji itu tidak berubah diam-diam ketika kode di sekitarnya
 * dirapikan.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $this->provinsi = Wilayah::factory()->create([
        'kode' => '51', 'nama' => 'Bali',
        'latitude' => -8.58, 'longitude' => 115.18,
    ]);
    $this->kabupaten = Wilayah::factory()->anakDari($this->provinsi, '03')->create(['nama' => 'Badung']);

    $this->kategori = LaporanKategori::factory()->create();
    User::factory()->withRole(Role::FasilitatorWilayah)->create();
});

/**
 * Terbitkan token untuk seorang pengguna.
 *
 * `forgetGuards()` bukan hiasan. Guard Sanctum menyimpan pengguna yang
 * sudah diselesaikan di dalam instansi guard, dan instansi itu hidup
 * selama satu uji, bukan selama satu permintaan seperti di produksi.
 * Tanpa ini, permintaan kedua dengan token berbeda di uji yang sama
 * tetap dilayani sebagai pengguna pertama, sehingga uji kebocoran data
 * antar pengguna justru lolos karena alasan yang salah.
 */
function tokenUntuk(User $user): string
{
    app('auth')->forgetGuards();

    return app(AuthService::class)->terbitkanToken($user, 'uji');
}

describe('amplop response', function (): void {
    it('membungkus keberhasilan dengan success dan data', function (): void {
        $this->getJson('/api/v1/laporan/kategori')
            ->assertOk()
            ->assertJsonStructure(['success', 'data'])
            ->assertJson(['success' => true]);
    });

    it('membungkus daftar berhalaman dengan meta lima kunci', function (): void {
        Artikel::factory()->terbit()->count(3)->create();

        $this->getJson('/api/v1/artikel')
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    });

    it('membungkus galat validasi dengan success false dan errors', function (): void {
        $this->postJson('/api/v1/auth/daftar', [])
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['success', 'message', 'errors'])
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    });

    it('membungkus permintaan tanpa token sebagai 401 dalam amplop yang sama', function (): void {
        $this->getJson('/api/v1/dompet/saldo')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Anda harus masuk terlebih dahulu.',
            ]);
    });

    it('membungkus data yang tidak ditemukan sebagai 404 dalam amplop yang sama', function (): void {
        $this->getJson('/api/v1/laporan/999999')
            ->assertStatus(404)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['success', 'message']);
    });

    it('memakai pesan galat berbahasa Indonesia', function (): void {
        $response = $this->postJson('/api/v1/auth/daftar', ['email' => 'bukan-email']);

        expect($response->json('errors.email.0'))->toBe('Format email tidak valid.')
            ->and($response->json('errors.name.0'))->toBe('Kolom nama wajib diisi.');
    });
});

describe('autentikasi', function (): void {
    it('mendaftarkan warga dan mengembalikan token', function (): void {
        $response = $this->postJson('/api/v1/auth/daftar', [
            'name' => 'Ni Kadek Sari',
            'email' => 'sari@contoh.id',
            'password' => 'kata-sandi-panjang',
            'password_confirmation' => 'kata-sandi-panjang',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email', 'role', 'kode_qr'], 'token']]);

        expect($response->json('data.user.role'))->toBe('masyarakat');
    });

    it('tidak pernah mengembalikan NIK atau NIP di profil', function (): void {
        $user = User::factory()->withRole(Role::Masyarakat)->create();

        $response = $this->withToken(tokenUntuk($user))->getJson('/api/v1/auth/me');

        expect(array_keys($response->json('data')))
            ->not->toContain('nik')
            ->not->toContain('nip');
    });

    it('menolak kredensial yang salah dengan 401', function (): void {
        User::factory()->withRole(Role::Masyarakat)->create(['email' => 'ada@contoh.id']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'ada@contoh.id',
            'password' => 'salah-sekali',
        ])->assertStatus(401)->assertJson([
            'success' => false,
            'message' => 'Email atau kata sandi salah.',
        ]);
    });

    it('melaporkan berhasil untuk lupa kata sandi email yang tidak terdaftar', function (): void {
        // Membedakan keduanya akan mengubah endpoint ini jadi alat
        // pemeriksa siapa yang punya akun.
        $this->postJson('/api/v1/auth/lupa-password', ['email' => 'tidak-ada@contoh.id'])
            ->assertOk()
            ->assertJson(['success' => true]);
    });
});

describe('otorisasi', function (): void {
    it('menolak warga mengakses endpoint petugas dengan 403', function (): void {
        $warga = User::factory()->withRole(Role::Masyarakat)->create();

        $this->withToken(tokenUntuk($warga))
            ->getJson('/api/v1/petugas/penugasan')
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    });

    it('menolak warga mengakses endpoint bank sampah dengan 403', function (): void {
        $warga = User::factory()->withRole(Role::Masyarakat)->create();

        $this->withToken(tokenUntuk($warga))
            ->getJson('/api/v1/bank-sampah/harga')
            ->assertStatus(403);
    });

    it('mengizinkan petugas mengakses daftar penugasannya', function (): void {
        $petugas = User::factory()->withRole(Role::Petugas)->create(['wilayah_id' => $this->kabupaten->id]);

        $this->withToken(tokenUntuk($petugas))
            ->getJson('/api/v1/petugas/penugasan')
            ->assertOk()
            ->assertJson(['success' => true]);
    });

    it('memberlakukan role dari guard web pada permintaan bertoken', function (): void {
        // Role hanya disemai pada guard web; guard sanctum memakai
        // provider pengguna yang sama sehingga rolenya ikut berlaku.
        $bankSampah = User::factory()->withRole(Role::BankSampah)->create();

        $this->withToken(tokenUntuk($bankSampah))
            ->getJson('/api/v1/bank-sampah/harga')
            // 403 karena akunnya belum terhubung unit, bukan karena role.
            ->assertStatus(403)
            ->assertJson(['message' => 'Akun Anda belum terhubung dengan unit bank sampah mana pun.']);
    });
});

describe('laporan lewat API', function (): void {
    it('membuat laporan lengkap dengan resolusi wilayah dan routing', function (): void {
        $warga = User::factory()->withRole(Role::Masyarakat)->create();

        $response = $this->withToken(tokenUntuk($warga))->postJson('/api/v1/laporan', [
            'kategori_id' => $this->kategori->id,
            'judul' => 'Tumpukan sampah di pinggir jalan',
            'deskripsi' => 'Sudah menumpuk tiga hari dan mulai berbau menyengat.',
            'latitude' => -8.583,
            'longitude' => 115.183,
        ]);

        $response->assertStatus(201)->assertJson(['success' => true]);

        expect($response->json('data.tiket'))->toMatch('/^RSK-\d{6}-\d{5}$/')
            ->and($response->json('data.status'))->toBe('baru')
            ->and($response->json('data.penanggung_jawab.alasan'))->toBe('wilayah_belum_terjangkau')
            ->and($response->json('data.penanggung_jawab.butuh_pendampingan'))->toBeTrue();
    });

    it('menawarkan penggabungan alih-alih menolak laporan kembar', function (): void {
        $warga = User::factory()->withRole(Role::Masyarakat)->create();
        $token = tokenUntuk($warga);

        $isi = [
            'kategori_id' => $this->kategori->id,
            'judul' => 'Tumpukan sampah',
            'deskripsi' => 'Menumpuk di bahu jalan sejak kemarin.',
            'latitude' => -8.583,
            'longitude' => 115.183,
        ];

        $this->withToken($token)->postJson('/api/v1/laporan', $isi)->assertStatus(201);

        $cek = $this->withToken($token)->postJson('/api/v1/laporan/cek-duplikat', [
            'latitude' => -8.583,
            'longitude' => 115.183,
        ]);

        $cek->assertOk();

        expect($cek->json('data.ada_kandidat'))->toBeTrue()
            ->and($cek->json('data.radius_meter'))->toBe(50)
            ->and($cek->json('data.kandidat'))->toHaveCount(1);

        // Dan laporan kedua tetap diterima, bukan ditolak.
        $this->withToken($token)->postJson('/api/v1/laporan', $isi)->assertStatus(201);
    });

    it('menolak laporan tanpa titik lokasi', function (): void {
        $warga = User::factory()->withRole(Role::Masyarakat)->create();

        $this->withToken(tokenUntuk($warga))->postJson('/api/v1/laporan', [
            'kategori_id' => $this->kategori->id,
            'judul' => 'Tumpukan sampah',
            'deskripsi' => 'Deskripsi yang cukup panjang untuk lolos validasi.',
        ])->assertStatus(422)->assertJsonValidationErrors(['latitude', 'longitude']);
    });

    it('membatasi daftar laporan pada milik warga sendiri', function (): void {
        $wargaA = User::factory()->withRole(Role::Masyarakat)->create();
        $wargaB = User::factory()->withRole(Role::Masyarakat)->create();

        $isi = [
            'kategori_id' => $this->kategori->id,
            'judul' => 'Tumpukan sampah',
            'deskripsi' => 'Deskripsi yang cukup panjang untuk lolos validasi.',
            'latitude' => -8.583, 'longitude' => 115.183,
        ];

        $this->withToken(tokenUntuk($wargaA))->postJson('/api/v1/laporan', $isi)->assertStatus(201);

        $daftar = $this->withToken(tokenUntuk($wargaB))->getJson('/api/v1/laporan');

        $daftar->assertOk();
        expect($daftar->json('data'))->toBeEmpty();
    });
});

describe('jelajah publik', function (): void {
    it('membuka artikel tanpa token', function (): void {
        $artikel = Artikel::factory()->terbit()->create(['judul' => 'Memilah dari Rumah']);

        $this->getJson("/api/v1/artikel/$artikel->slug")
            ->assertOk()
            ->assertJsonPath('data.judul', 'Memilah dari Rumah');
    });

    it('menyembunyikan artikel draf dari publik', function (): void {
        $draf = Artikel::factory()->create();

        $this->getJson("/api/v1/artikel/$draf->slug")->assertStatus(403);
    });

    it('menyediakan teks baca bersih markdown untuk pemutar suara', function (): void {
        $artikel = Artikel::factory()->terbit()->create([
            'konten' => "## Kompos **rumahan**\n\nMulailah dari sisa dapur.",
            'teks_baca' => null,
        ]);

        $response = $this->getJson("/api/v1/artikel/$artikel->slug/teks-baca");

        $response->assertOk();

        expect($response->json('data.teks_baca'))
            ->not->toContain('#')
            ->not->toContain('**')
            ->toContain('Kompos rumahan')
            ->and($response->json('data.bahasa'))->toBe('id-ID');
    });

    it('menghitung pemakaian pemutar suara secara terpisah dari pembacaan biasa', function (): void {
        // Angka inilah yang membuat klaim inklusivitas bisa ditunjukkan.
        $artikel = Artikel::factory()->terbit()->create();

        $this->getJson("/api/v1/artikel/$artikel->slug")->assertOk();
        $this->getJson("/api/v1/artikel/$artikel->slug/teks-baca")->assertOk();

        expect($artikel->fresh()->dilihat)->toBe(1)
            ->and($artikel->fresh()->didengarkan)->toBe(1);
    });

    it('membuka pencarian wilayah tanpa token', function (): void {
        $response = $this->getJson('/api/v1/wilayah?cari=Badung');

        $response->assertOk();
        expect($response->json('data.0.nama'))->toBe('Badung');
    });
});
