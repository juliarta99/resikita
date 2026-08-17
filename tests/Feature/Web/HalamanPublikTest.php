<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\StatusPengajuanWilayah;
use App\Enums\StatusRegistrasiWilayah;
use App\Enums\StatusUmkm;
use App\Livewire\Publik\ArtikelShow;
use App\Livewire\Publik\PendaftaranUmkm;
use App\Livewire\Publik\PengajuanWilayah;
use App\Models\Artikel;
use App\Models\LaporanKategori;
use App\Models\PengajuanWilayah as ModelPengajuan;
use App\Models\Produk;
use App\Models\ProdukKategori;
use App\Models\Umkm;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\Laporan\LaporanService;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Halaman publik (CLAUDE.md 8, folder Publik).
 *
 * Dua sifat yang paling penting diuji di sini: halaman ini benar-benar
 * terbuka tanpa akun, dan keterbukaan itu tidak membocorkan apa pun
 * yang seharusnya tertutup, identitas pelapor, koordinat tepat, dan
 * produk dari toko yang belum diverifikasi.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $this->provinsi = Wilayah::factory()->create(['kode' => '51', 'nama' => 'Bali']);
    $this->kabupaten = Wilayah::factory()->anakDari($this->provinsi, '03')->create(['nama' => 'Badung']);

    $this->kategori = LaporanKategori::factory()->create();

    User::factory()->withRole(Role::FasilitatorWilayah)->create();
});

describe('akses tanpa akun', function (): void {
    it('membuka seluruh halaman publik', function (string $path): void {
        $this->get($path)->assertOk();
    })->with([
        '/',
        '/artikel',
        '/direktori',
        '/produk',
        '/laporan',
        '/daftarkan-wilayah',
        '/daftarkan-umkm',
    ]);

    it('mengalihkan /beranda ke halaman muka', function (): void {
        $this->get('/beranda')->assertRedirect(route('publik.beranda'));
    });
});

describe('literasi', function (): void {
    it('menampilkan artikel terbit dan menyembunyikan draf', function (): void {
        $terbit = Artikel::factory()->terbit()->create(['judul' => 'Memilah dari Dapur']);
        $draf = Artikel::factory()->create(['judul' => 'Naskah Belum Jadi']);

        $this->get('/artikel')
            ->assertOk()
            ->assertSee('Memilah dari Dapur')
            ->assertDontSee('Naskah Belum Jadi');

        $this->get("/artikel/{$terbit->slug}")->assertOk();
        $this->get("/artikel/{$draf->slug}")->assertForbidden();
    });

    it('menyediakan teks bersih markdown untuk pemutar suara', function (): void {
        $artikel = Artikel::factory()->terbit()->create([
            'konten' => "## Kompos **rumahan**\n\nMulailah dari sisa dapur.",
            'teks_baca' => null,
        ]);

        $respons = $this->get("/artikel/{$artikel->slug}")->assertOk();

        // Teks yang diserahkan ke pembaca suara sudah bersih dari
        // penanda markdown; kalau tidak, pembaca layar mengucapkan
        // "tanda pagar tanda pagar Kompos bintang bintang rumahan".
        expect($artikel->fresh()->teks_baca)
            ->toContain('Kompos rumahan')
            ->not->toContain('#')
            ->not->toContain('**');

        $respons->assertSee('Dengarkan artikel');
    });

    it('menghitung pembacaan dan pemutaran suara secara terpisah', function (): void {
        $artikel = Artikel::factory()->terbit()->create();

        $this->get("/artikel/{$artikel->slug}")->assertOk();

        expect($artikel->fresh()->dilihat)->toBe(1)
            ->and($artikel->fresh()->didengarkan)->toBe(0);

        // Baru naik ketika pemutaran benar-benar dimulai di peramban.
        Livewire::test(ArtikelShow::class, ['artikel' => $artikel])->call('catatDidengarkan');

        expect($artikel->fresh()->didengarkan)->toBe(1);
    });
});

describe('marketplace', function (): void {
    it('menyembunyikan produk dari toko yang belum diverifikasi', function (): void {
        $kategori = ProdukKategori::factory()->create();

        $tokoAktif = Umkm::factory()->create(['status' => StatusUmkm::Aktif, 'is_verified' => true]);
        $tokoMenunggu = Umkm::factory()->create(['status' => StatusUmkm::Menunggu, 'is_verified' => false]);

        Produk::factory()->create([
            'umkm_id' => $tokoAktif->id,
            'kategori_id' => $kategori->id,
            'nama' => 'Tas Dari Sachet',
            'stok' => 5,
            'is_active' => true,
        ]);

        Produk::factory()->create([
            'umkm_id' => $tokoMenunggu->id,
            'kategori_id' => $kategori->id,
            'nama' => 'Produk Toko Belum Ditinjau',
            'stok' => 5,
            'is_active' => true,
        ]);

        $this->get('/produk')
            ->assertOk()
            ->assertSee('Tas Dari Sachet')
            ->assertDontSee('Produk Toko Belum Ditinjau');
    });

    it('membuka halaman detail produk tanpa akun', function (): void {
        $kategori = ProdukKategori::factory()->create();
        $toko = Umkm::factory()->create(['status' => StatusUmkm::Aktif, 'is_verified' => true]);

        $produk = Produk::factory()->create([
            'umkm_id' => $toko->id,
            'kategori_id' => $kategori->id,
            'nama' => 'Pot Dari Ban Bekas',
            'bahan_baku' => 'Ban dalam bekas',
            'stok' => 3,
        ]);

        $this->get("/produk/{$produk->slug}")
            ->assertOk()
            ->assertSee('Pot Dari Ban Bekas')
            ->assertSee('Ban dalam bekas');
    });
});

describe('transparansi laporan', function (): void {
    it('menampilkan status laporan tanpa membocorkan pelapor dan koordinat', function (): void {
        $pelapor = User::factory()->withRole(Role::Masyarakat)->create(['name' => 'Ni Made Rahasia']);

        $laporan = app(LaporanService::class)->buat($pelapor, [
            'kategori_id' => $this->kategori->id,
            'judul' => 'Tumpukan sampah di bahu jalan',
            'deskripsi' => 'Sudah menumpuk sejak seminggu lalu dan mulai berbau.',
            'latitude' => -8.5830000,
            'longitude' => 115.1830000,
        ]);

        $respons = $this->get("/laporan/{$laporan->id}")->assertOk();

        $respons->assertSee('Tumpukan sampah di bahu jalan')
            ->assertSee($laporan->tiket)
            // Identitas pelapor tidak boleh muncul: melapor tidak boleh
            // menjadi tindakan yang berisiko di lingkungan sendiri.
            ->assertDontSee('Ni Made Rahasia')
            // Koordinat tepatnya juga tidak.
            ->assertDontSee('115.183');
    });

    it('mendaftar laporan lintas wilayah untuk umum', function (): void {
        $pelapor = User::factory()->withRole(Role::Masyarakat)->create();

        app(LaporanService::class)->buat($pelapor, [
            'kategori_id' => $this->kategori->id,
            'judul' => 'Pembakaran sampah',
            'deskripsi' => 'Asapnya masuk ke rumah warga tiap sore.',
            'latitude' => -8.5830000,
            'longitude' => 115.1830000,
        ]);

        $this->get('/laporan')->assertOk()->assertSee('Pembakaran sampah');
    });
});

describe('pengajuan wilayah', function (): void {
    it('menerima pengajuan dari pemohon tanpa akun', function (): void {
        Storage::fake('local');

        Livewire::test(PengajuanWilayah::class)
            ->set('provinsiId', $this->provinsi->id)
            ->set('kabupatenId', $this->kabupaten->id)
            ->set('pemohonNama', 'I Wayan Sudirta')
            ->set('pemohonJabatan', 'Kepala Dinas Lingkungan Hidup')
            ->set('pemohonEmail', 'dlh@badungkab.go.id')
            ->set('instansi', 'Dinas Lingkungan Hidup Kabupaten Badung')
            ->set('surat', UploadedFile::fake()->create('surat-tugas.pdf', 200, 'application/pdf'))
            ->call('ajukan')
            ->assertSet('terkirim', true);

        $pengajuan = ModelPengajuan::sole();

        expect($pengajuan->wilayah_id)->toBe($this->kabupaten->id)
            ->and($pengajuan->status)->toBe(StatusPengajuanWilayah::Diajukan)
            ->and($this->kabupaten->fresh()->status_registrasi)
            ->toBe(StatusRegistrasiWilayah::Diajukan);

        // Surat menyimpan nama dan nomor pejabat, jadi tidak boleh
        // berada di disk publik yang bisa ditebak alamatnya.
        Storage::disk('local')->assertExists($pengajuan->surat_path);
    });

    it('mewajibkan surat bukti kewenangan', function (): void {
        Livewire::test(PengajuanWilayah::class)
            ->set('provinsiId', $this->provinsi->id)
            ->set('kabupatenId', $this->kabupaten->id)
            ->set('pemohonNama', 'I Wayan Sudirta')
            ->set('pemohonJabatan', 'Kepala Dinas')
            ->set('pemohonEmail', 'dlh@badungkab.go.id')
            ->set('instansi', 'Dinas Lingkungan Hidup')
            ->call('ajukan')
            ->assertHasErrors('surat');

        expect(ModelPengajuan::count())->toBe(0);
    });

    it('menolak wilayah yang sudah terdaftar dengan pesan yang jelas', function (): void {
        Storage::fake('local');

        $this->kabupaten->update([
            'status_registrasi' => StatusRegistrasiWilayah::Terverifikasi,
            'terverifikasi_at' => now(),
        ]);

        Livewire::test(PengajuanWilayah::class)
            ->set('provinsiId', $this->provinsi->id)
            ->set('kabupatenId', $this->kabupaten->id)
            ->set('pemohonNama', 'I Wayan Sudirta')
            ->set('pemohonJabatan', 'Kepala Dinas')
            ->set('pemohonEmail', 'dlh@badungkab.go.id')
            ->set('instansi', 'Dinas Lingkungan Hidup')
            ->set('surat', UploadedFile::fake()->create('surat.pdf', 100, 'application/pdf'))
            ->call('ajukan')
            ->assertSet('terkirim', false)
            ->assertDispatched('pesan');

        expect(ModelPengajuan::count())->toBe(0);
    });
});

describe('pendaftaran umkm', function (): void {
    /** Isian minimal yang lolos validasi. */
    $isian = fn (array $ganti = []): array => [
        'nama' => 'Kriya Plastik Nusantara',
        'alamat' => 'Jl. Raya Dalung No. 12, Kuta Utara',
        'pemilikNama' => 'Ni Kadek Sari',
        'pemilikEmail' => 'kadek@kriyaplastik.id',
        'password' => 'rahasia-kuat',
        'passwordKonfirmasi' => 'rahasia-kuat',
        'setuju' => true,
        ...$ganti,
    ];

    it('menerima pendaftaran dari pemilik usaha tanpa akun', function () use ($isian): void {
        $komponen = Livewire::test(PendaftaranUmkm::class)
            ->set('provinsiId', $this->provinsi->id)
            ->set('kabupatenId', $this->kabupaten->id);

        foreach ($isian() as $properti => $nilai) {
            $komponen->set($properti, $nilai);
        }

        $komponen->call('daftar')->assertSet('terkirim', true);

        $umkm = Umkm::sole();
        $akun = User::where('email', 'kadek@kriyaplastik.id')->sole();

        expect($umkm->nama)->toBe('Kriya Plastik Nusantara')
            ->and($umkm->wilayah_id)->toBe($this->kabupaten->id)
            ->and($umkm->status)->toBe(StatusUmkm::Menunggu)
            ->and($umkm->is_verified)->toBeFalse()
            // Akun terbit dan langsung bisa dipakai, yang ditinjau
            // tokonya, bukan hak orangnya memakai Resikita. Panel
            // penjualnya sendiri tetap tertutup sampai toko diverifikasi,
            // dijaga middleware `toko.terverifikasi`.
            ->and($akun->umkm_id)->toBe($umkm->id)
            ->and($akun->is_active)->toBeTrue()
            ->and($akun->hasRole(Role::Umkm->value))->toBeTrue()
            // Dompet penjual disiapkan sejak pendaftaran, bukan saat
            // pesanan pertama masuk.
            ->and($umkm->dompet)->not->toBeNull();
    });

    it('memakai wilayah terdalam yang dipilih', function () use ($isian): void {
        $kecamatan = Wilayah::factory()->anakDari($this->kabupaten, '05')->create();
        $desa = Wilayah::factory()->anakDari($kecamatan, '2001')->create(['nama' => 'Dalung']);

        $komponen = Livewire::test(PendaftaranUmkm::class)
            ->set('provinsiId', $this->provinsi->id)
            ->set('kabupatenId', $this->kabupaten->id)
            ->set('kecamatanId', $kecamatan->id)
            ->set('desaId', $desa->id);

        foreach ($isian() as $properti => $nilai) {
            $komponen->set($properti, $nilai);
        }

        $komponen->call('daftar')->assertSet('terkirim', true);

        expect(Umkm::sole()->wilayah_id)->toBe($desa->id);
    });

    it('menolak email yang sudah terdaftar', function () use ($isian): void {
        User::factory()->withRole(Role::Masyarakat)->create(['email' => 'kadek@kriyaplastik.id']);

        $komponen = Livewire::test(PendaftaranUmkm::class)
            ->set('provinsiId', $this->provinsi->id)
            ->set('kabupatenId', $this->kabupaten->id);

        foreach ($isian() as $properti => $nilai) {
            $komponen->set($properti, $nilai);
        }

        $komponen->call('daftar')
            ->assertHasErrors('pemilikEmail')
            ->assertSet('terkirim', false);

        expect(Umkm::count())->toBe(0);
    });

    it('menolak konfirmasi kata sandi yang tidak sama', function () use ($isian): void {
        $komponen = Livewire::test(PendaftaranUmkm::class)
            ->set('provinsiId', $this->provinsi->id)
            ->set('kabupatenId', $this->kabupaten->id);

        foreach ($isian(['passwordKonfirmasi' => 'salah-ketik']) as $properti => $nilai) {
            $komponen->set($properti, $nilai);
        }

        $komponen->call('daftar')->assertHasErrors('password');

        expect(Umkm::count())->toBe(0);
    });

    it('mewajibkan pernyataan kebenaran data dicentang', function () use ($isian): void {
        $komponen = Livewire::test(PendaftaranUmkm::class)
            ->set('provinsiId', $this->provinsi->id)
            ->set('kabupatenId', $this->kabupaten->id);

        foreach ($isian(['setuju' => false]) as $properti => $nilai) {
            $komponen->set($properti, $nilai);
        }

        $komponen->call('daftar')->assertHasErrors('setuju');

        expect(Umkm::count())->toBe(0);
    });

    it('mewajibkan kabupaten dipilih', function () use ($isian): void {
        $komponen = Livewire::test(PendaftaranUmkm::class);

        foreach ($isian() as $properti => $nilai) {
            $komponen->set($properti, $nilai);
        }

        $komponen->call('daftar')->assertHasErrors('kabupatenId');

        expect(Umkm::count())->toBe(0);
    });

    it('tidak menampilkan toko yang belum diverifikasi di marketplace', function () use ($isian): void {
        $komponen = Livewire::test(PendaftaranUmkm::class)
            ->set('provinsiId', $this->provinsi->id)
            ->set('kabupatenId', $this->kabupaten->id);

        foreach ($isian() as $properti => $nilai) {
            $komponen->set($properti, $nilai);
        }

        $komponen->call('daftar');

        $umkm = Umkm::sole();

        // Stok dan status produk sengaja dibuat sehat, supaya satu-satunya
        // alasan produk ini tidak tampil adalah tokonya yang belum
        // diverifikasi, bukan sebab lain yang kebetulan ikut menutupinya.
        Produk::factory()->create([
            'umkm_id' => $umkm->id,
            'kategori_id' => ProdukKategori::factory()->create()->id,
            'nama' => 'Tas Anyaman Sachet',
            'stok' => 5,
            'is_active' => true,
        ]);

        $this->get('/produk')->assertOk()->assertDontSee('Tas Anyaman Sachet');
    });
});
