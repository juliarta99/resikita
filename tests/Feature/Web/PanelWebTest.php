<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\StatusLaporan;
use App\Livewire\Auth\Login;
use App\Livewire\Pemerintahan\LaporanDetail;
use App\Livewire\Pemerintahan\LaporanManager;
use App\Models\Laporan;
use App\Models\LaporanKategori;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\Laporan\LaporanService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

/**
 * Panel web (CLAUDE.md 8).
 *
 * Dua hal yang paling penting diuji di sini:
 *
 * 1. Pintu tiap panel benar-benar terkunci untuk role lain. Menu yang
 *    disembunyikan bukan otorisasi; yang menjaga adalah middleware role.
 *
 * 2. Komponen pemerintahan yang dipakai bersama tiga role tetap
 *    menghormati cakupan wilayah masing-masing. Justru karena
 *    komponennya satu, kebocoran di sini akan berlaku untuk ketiganya
 *    sekaligus.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $this->provinsi = Wilayah::factory()->terverifikasi()->create(['kode' => '51', 'nama' => 'Bali']);
    $this->kabupatenA = Wilayah::factory()->anakDari($this->provinsi, '03')->terverifikasi()->create(['nama' => 'Badung']);
    $this->kabupatenB = Wilayah::factory()->anakDari($this->provinsi, '04')->terverifikasi()->create(['nama' => 'Gianyar']);

    $this->kategori = LaporanKategori::factory()->create();

    User::factory()->withRole(Role::FasilitatorWilayah)->create();
});

/** Buat laporan yang jatuh di sebuah kabupaten. */
function laporanDi(Wilayah $kabupaten, string $judul = 'Tumpukan sampah'): Laporan
{
    $pelapor = User::factory()->withRole(Role::Masyarakat)->create();

    $laporan = app(LaporanService::class)->buat($pelapor, [
        'kategori_id' => LaporanKategori::query()->value('id'),
        'judul' => $judul,
        'deskripsi' => 'Deskripsi yang cukup panjang untuk lolos validasi bentuk.',
        'latitude' => -8.5830000,
        'longitude' => 115.1830000,
    ]);

    // Kolom wilayah didenormalisasi, jadi cukup disetel langsung untuk
    // menempatkan laporan di kabupaten yang diuji.
    $laporan->forceFill([
        'provinsi_id' => $kabupaten->parent_id,
        'kabupaten_id' => $kabupaten->id,
    ])->save();

    return $laporan->fresh();
}

describe('masuk', function (): void {
    it('mengantar tiap role ke dasbornya sendiri', function (): void {
        $kepalaDesa = User::factory()->withRole(Role::KepalaDesa)->create([
            'password' => Hash::make('rahasia-sekali'),
        ]);

        Livewire::test(Login::class)
            ->set('email', $kepalaDesa->email)
            ->set('password', 'rahasia-sekali')
            ->call('masuk')
            ->assertRedirect(route('desa.dashboard'));

        expect(auth()->id())->toBe($kepalaDesa->id);
    });

    it('menolak masyarakat dengan penjelasan, bukan dengan pesan kredensial salah', function (): void {
        $warga = User::factory()->withRole(Role::Masyarakat)->create([
            'password' => Hash::make('rahasia-sekali'),
        ]);

        $komponen = Livewire::test(Login::class)
            ->set('email', $warga->email)
            ->set('password', 'rahasia-sekali')
            ->call('masuk')
            ->assertHasErrors('email');

        expect($komponen->errors()->first('email'))->toContain('aplikasi Resikita di ponsel');
        expect(auth()->check())->toBeFalse();
    });

    it('menolak kata sandi salah', function (): void {
        $admin = User::factory()->withRole(Role::Admin)->create([
            'password' => Hash::make('rahasia-sekali'),
        ]);

        Livewire::test(Login::class)
            ->set('email', $admin->email)
            ->set('password', 'salah-total')
            ->call('masuk')
            ->assertHasErrors('email');

        expect(auth()->check())->toBeFalse();
    });
});

describe('penjagaan pintu panel', function (): void {
    it('menolak role lain membuka panel yang bukan miliknya', function (): void {
        $kabupaten = User::factory()->withRole(Role::AdminKabupaten)->create([
            'wilayah_id' => $this->kabupatenA->id,
        ]);

        $this->actingAs($kabupaten)->get('/admin')->assertForbidden();
        $this->actingAs($kabupaten)->get('/provinsi')->assertForbidden();
        $this->actingAs($kabupaten)->get('/fasilitator')->assertForbidden();
        $this->actingAs($kabupaten)->get('/bank-sampah')->assertForbidden();
        $this->actingAs($kabupaten)->get('/umkm')->assertForbidden();

        $this->actingAs($kabupaten)->get('/kabupaten')->assertOk();
    });

    it('menahan halaman khusus super admin dari admin biasa', function (): void {
        $admin = User::factory()->withRole(Role::Admin)->create();

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/pengajuan-wilayah')->assertForbidden();
        $this->actingAs($admin)->get('/admin/wilayah')->assertForbidden();
        $this->actingAs($admin)->get('/admin/log')->assertForbidden();

        $superAdmin = User::factory()->withRole(Role::SuperAdmin)->create();

        $this->actingAs($superAdmin)->get('/admin/pengajuan-wilayah')->assertOk();
        $this->actingAs($superAdmin)->get('/admin/log')->assertOk();
    });

    it('mengarahkan tamu ke halaman masuk', function (): void {
        $this->get('/kabupaten')->assertRedirect(route('masuk'));
        $this->get('/admin')->assertRedirect(route('masuk'));

        // Halaman muka justru terbuka: pengunjung tanpa akun harus bisa
        // menilai apakah ini berguna sebelum diminta mendaftar.
        $this->get('/')->assertOk();
    });
});

describe('cakupan wilayah pada komponen bersama', function (): void {
    it('membatasi daftar laporan pada kabupaten sendiri', function (): void {
        laporanDi($this->kabupatenA, 'Sampah di Badung');
        laporanDi($this->kabupatenB, 'Sampah di Gianyar');

        $adminBadung = User::factory()->withRole(Role::AdminKabupaten)->create([
            'wilayah_id' => $this->kabupatenA->id,
        ]);

        Livewire::actingAs($adminBadung)
            ->test(LaporanManager::class)
            ->assertSee('Sampah di Badung')
            ->assertDontSee('Sampah di Gianyar');
    });

    it('memberi admin provinsi seluruh kabupaten dalam provinsinya', function (): void {
        laporanDi($this->kabupatenA, 'Sampah di Badung');
        laporanDi($this->kabupatenB, 'Sampah di Gianyar');

        $adminProvinsi = User::factory()->withRole(Role::AdminProvinsi)->create([
            'wilayah_id' => $this->provinsi->id,
        ]);

        Livewire::actingAs($adminProvinsi)
            ->test(LaporanManager::class)
            ->assertSee('Sampah di Badung')
            ->assertSee('Sampah di Gianyar');
    });

    it('tidak menampilkan apa pun untuk role pemerintahan tanpa wilayah', function (): void {
        laporanDi($this->kabupatenA, 'Sampah di Badung');

        $tanpaWilayah = User::factory()->withRole(Role::AdminKabupaten)->create(['wilayah_id' => null]);

        Livewire::actingAs($tanpaWilayah)
            ->test(LaporanManager::class)
            ->assertDontSee('Sampah di Badung');
    });
});

describe('tindakan atas laporan', function (): void {
    it('memverifikasi laporan lewat Service, bukan dengan mengubah kolom', function (): void {
        $laporan = laporanDi($this->kabupatenA);

        $admin = User::factory()->withRole(Role::AdminKabupaten)->create([
            'wilayah_id' => $this->kabupatenA->id,
        ]);

        Livewire::actingAs($admin)
            ->test(LaporanDetail::class, ['laporan' => $laporan])
            ->call('verifikasi');

        $segar = $laporan->fresh();

        expect($segar->status)->toBe(StatusLaporan::Diverifikasi)
            ->and($segar->diverifikasi_oleh)->toBe($admin->id)
            ->and($segar->diverifikasi_at)->not->toBeNull();
    });

    it('menolak verifikasi laporan di luar kewenangan', function (): void {
        $laporanGianyar = laporanDi($this->kabupatenB);

        $adminBadung = User::factory()->withRole(Role::AdminKabupaten)->create([
            'wilayah_id' => $this->kabupatenA->id,
        ]);

        Livewire::actingAs($adminBadung)
            ->test(LaporanDetail::class, ['laporan' => $laporanGianyar])
            ->call('verifikasi')
            ->assertForbidden();

        expect($laporanGianyar->fresh()->status)->toBe(StatusLaporan::Baru);
    });

    it('mewajibkan alasan saat menolak laporan', function (): void {
        $laporan = laporanDi($this->kabupatenA);

        $admin = User::factory()->withRole(Role::AdminKabupaten)->create([
            'wilayah_id' => $this->kabupatenA->id,
        ]);

        Livewire::actingAs($admin)
            ->test(LaporanDetail::class, ['laporan' => $laporan])
            ->set('alasanTolak', 'singkat')
            ->call('tolak')
            ->assertHasErrors('alasanTolak');

        expect($laporan->fresh()->status)->toBe(StatusLaporan::Baru);
    });
});

describe('keluar', function (): void {
    it('memutus sesi dan kembali ke halaman masuk', function (): void {
        $admin = User::factory()->withRole(Role::Admin)->create();

        $this->actingAs($admin)
            ->post('/keluar')
            ->assertRedirect(route('masuk'));

        expect(auth()->check())->toBeFalse();
    });
});
