<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\StatusUmkm;
use App\Exceptions\AturanBisnisException;
use App\Jobs\KirimNotifikasiUmkmJob;
use App\Livewire\Admin\UmkmManager;
use App\Livewire\Umkm\StatusPendaftaran;
use App\Models\Notifikasi;
use App\Models\Umkm;
use App\Models\User;
use App\Services\Auth\AkunService;
use App\Support\Navigasi;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

/**
 * Daur hidup verifikasi toko: menunggu → ditolak → diperbaiki → aktif.
 *
 * Yang diuji di sini bukan sekadar berpindahnya status, melainkan bahwa
 * alurnya tidak pernah menjadi jalan buntu. Sebelum perbaikan ini
 * penolakan mematikan akun pemiliknya, sehingga ia tidak bisa masuk,
 * tidak pernah tahu apa yang salah, dan satu-satunya jalan keluar adalah
 * mendaftar ulang dengan email lain.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    Queue::fake();

    $this->akun = app(AkunService::class);
    $this->admin = User::factory()->withRole(Role::Admin)->create();

    $hasil = $this->akun->daftarUmkmMandiri([
        'nama' => 'Kriya Plastik Nusantara',
        'alamat' => 'Jl. Raya Dalung No. 12',
        'pemilik_nama' => 'Ni Kadek Sari',
        'pemilik_email' => 'kadek@kriya.id',
        'pemilik_phone' => '081234567890',
        'password' => 'kata-sandi-panjang',
    ]);

    $this->umkm = $hasil['umkm'];
    $this->pemilik = $hasil['akun'];
});

describe('gerbang panel penjual', function (): void {
    it('menutup panel selama toko masih ditinjau', function (): void {
        $this->actingAs($this->pemilik)->get(route('umkm.dashboard'))
            ->assertRedirect(route('umkm.status'));

        $this->actingAs($this->pemilik)->get(route('umkm.produk'))
            ->assertRedirect(route('umkm.status'));
    });

    it('membuka halaman status dan profil meski toko belum terverifikasi', function (): void {
        // Justru dua halaman inilah yang dibutuhkan pemilik toko yang
        // pendaftarannya belum lolos. Menjaganya dengan gerbang yang
        // menuntut toko sudah lolos membuat keduanya mustahil dibuka.
        $this->actingAs($this->pemilik)->get(route('umkm.status'))->assertOk();
        $this->actingAs($this->pemilik)->get(route('umkm.profil'))->assertOk();
    });

    it('membuka seluruh panel setelah toko disetujui', function (): void {
        $this->akun->setujuiUmkm($this->umkm, $this->admin);

        $this->actingAs($this->pemilik->fresh())->get(route('umkm.dashboard'))->assertOk();
        $this->actingAs($this->pemilik->fresh())->get(route('umkm.produk'))->assertOk();
    });

    it('mengantar toko yang sudah aktif dari halaman status ke dasbornya', function (): void {
        $this->akun->setujuiUmkm($this->umkm, $this->admin);

        $this->actingAs($this->pemilik->fresh())->get(route('umkm.status'))
            ->assertRedirect(route('umkm.dashboard'));
    });
});

describe('penolakan', function (): void {
    it('menyimpan alasan dan menampilkannya kepada pemilik', function (): void {
        $this->akun->tolakUmkm(
            $this->umkm,
            $this->admin,
            'Alamat usaha belum lengkap sampai nama jalan dan nomor.',
        );

        // Pemilik masih bisa masuk, dan alasannya terbaca di dalam
        // aplikasi, bukan hanya di percakapan di luar sistem.
        $this->actingAs($this->pemilik->fresh())->get(route('umkm.status'))
            ->assertOk()
            ->assertSee('Alamat usaha belum lengkap sampai nama jalan dan nomor.');
    });

    it('tidak mematikan akun pemiliknya', function (): void {
        $this->akun->tolakUmkm($this->umkm, $this->admin, 'Data belum lengkap sama sekali.');

        expect($this->pemilik->fresh()->is_active)->toBeTrue();
    });

    it('menolak alasan kosong', function (): void {
        $this->akun->tolakUmkm($this->umkm, $this->admin, '   ');
    })->throws(AturanBisnisException::class, 'Alasan penolakan wajib diisi');

    it('menolak penolakan ganda', function (): void {
        $this->akun->tolakUmkm($this->umkm, $this->admin, 'Alasan pertama yang cukup panjang.');
        $this->akun->tolakUmkm($this->umkm->fresh(), $this->admin, 'Alasan kedua yang cukup panjang.');
    })->throws(AturanBisnisException::class, 'sudah ditolak');

    it('mewajibkan admin menuliskan alasan di panel', function (): void {
        Livewire::actingAs($this->admin)
            ->test(UmkmManager::class)
            ->call('bukaFormTolak', $this->umkm->id)
            ->set('catatanTolak', 'pendek')
            ->call('tolak')
            ->assertHasErrors('catatanTolak');

        expect($this->umkm->fresh()->status)->toBe(StatusUmkm::Menunggu);
    });

    it('menolak lewat panel admin beserta jejak peninjaunya', function (): void {
        Livewire::actingAs($this->admin)
            ->test(UmkmManager::class)
            ->call('bukaFormTolak', $this->umkm->id)
            ->set('catatanTolak', 'Foto usaha tidak menunjukkan produk daur ulang apa pun.')
            ->call('tolak')
            ->assertSet('tolakId', null);

        $ditolak = $this->umkm->fresh();

        expect($ditolak->status)->toBe(StatusUmkm::Ditolak)
            ->and($ditolak->catatan_verifikasi)->toContain('Foto usaha')
            ->and($ditolak->ditinjau_oleh)->toBe($this->admin->id);
    });
});

describe('pengajuan ulang', function (): void {
    beforeEach(function (): void {
        $this->akun->tolakUmkm(
            $this->umkm,
            $this->admin,
            'Alamat usaha belum lengkap sampai nama jalan dan nomor.',
        );
    });

    it('mengembalikan toko ke antrean setelah pemilik memperbaiki datanya', function (): void {
        Livewire::actingAs($this->pemilik->fresh())
            ->test(StatusPendaftaran::class)
            ->set('nama', 'Kriya Plastik Nusantara')
            ->set('alamat', 'Jl. Raya Dalung No. 12, Kuta Utara, Badung')
            ->call('ajukanUlang');

        $diajukan = $this->umkm->fresh();

        expect($diajukan->status)->toBe(StatusUmkm::Menunggu)
            ->and($diajukan->alamat)->toContain('Kuta Utara')
            ->and($diajukan->ditinjau_oleh)->toBeNull()
            ->and($diajukan->ditinjau_at)->toBeNull();
    });

    it('menyisakan catatan penolakan selama menunggu ditinjau ulang', function (): void {
        Livewire::actingAs($this->pemilik->fresh())
            ->test(StatusPendaftaran::class)
            ->set('nama', 'Kriya Plastik Nusantara')
            ->set('alamat', 'Jl. Raya Dalung No. 12, Kuta Utara, Badung')
            ->call('ajukanUlang');

        // Pemilik masih perlu membandingkan perbaikannya dengan
        // permintaan admin selama menunggu.
        expect($this->umkm->fresh()->catatan_verifikasi)->toContain('belum lengkap');
    });

    it('menolak pengajuan ulang untuk toko yang tidak ditolak', function (): void {
        $this->akun->setujuiUmkm($this->umkm->fresh(), $this->admin);

        $this->akun->ajukanUlangUmkm($this->umkm->fresh(), [
            'nama' => 'Apa pun',
            'alamat' => 'Alamat mana pun yang panjang',
        ]);
    })->throws(AturanBisnisException::class, 'hanya untuk pendaftaran yang ditolak');

    it('membuka panel setelah pengajuan ulang disetujui', function (): void {
        Livewire::actingAs($this->pemilik->fresh())
            ->test(StatusPendaftaran::class)
            ->set('nama', 'Kriya Plastik Nusantara')
            ->set('alamat', 'Jl. Raya Dalung No. 12, Kuta Utara, Badung')
            ->call('ajukanUlang');

        $this->akun->setujuiUmkm($this->umkm->fresh(), $this->admin);

        $this->actingAs($this->pemilik->fresh())->get(route('umkm.dashboard'))->assertOk();
    });
});

describe('pemberitahuan', function (): void {
    it('mencatat notifikasi in-app dan mengantre kanal luar saat disetujui', function (): void {
        $this->akun->setujuiUmkm($this->umkm, $this->admin);

        $notifikasi = Notifikasi::where('user_id', $this->pemilik->id)->sole();

        expect($notifikasi->tipe)->toBe('umkm.disetujui')
            ->and($notifikasi->judul)->toContain('aktif');

        Queue::assertPushed(KirimNotifikasiUmkmJob::class);
    });

    it('menyertakan alasan penolakan pada pesan yang dikirim', function (): void {
        $this->akun->tolakUmkm($this->umkm, $this->admin, 'Foto usaha tidak jelas sama sekali.');

        $notifikasi = Notifikasi::where('user_id', $this->pemilik->id)->sole();

        expect($notifikasi->tipe)->toBe('umkm.ditolak')
            ->and($notifikasi->pesan)->toContain('Foto usaha tidak jelas')
            ->and($notifikasi->action_url)->toContain('/umkm/status');
    });

    it('tetap menyimpan keadaan meski antrean tidak jalan', function (): void {
        // Notifikasi hanya pelengkap. Halaman status membaca langsung
        // dari kolom toko, jadi antrean yang mati tidak menghilangkan
        // informasinya dari pemilik.
        Queue::fake();

        $this->akun->tolakUmkm($this->umkm, $this->admin, 'Alasan yang cukup panjang untuk lolos.');

        $this->actingAs($this->pemilik->fresh())->get(route('umkm.status'))
            ->assertOk()
            ->assertSee('Alasan yang cukup panjang untuk lolos.');
    });
});

describe('marketplace', function (): void {
    it('menyembunyikan toko sampai disetujui lalu menampilkannya', function (): void {
        expect(Umkm::query()->aktif()->count())->toBe(0);

        $this->akun->setujuiUmkm($this->umkm, $this->admin);

        expect(Umkm::query()->aktif()->count())->toBe(1);
    });
});

describe('menu panel', function (): void {
    it('menyisakan status dan profil selama toko belum terverifikasi', function (): void {
        $menu = collect(Navigasi::untuk($this->pemilik->fresh()))->pluck('route');

        expect($menu->all())->toBe(['umkm.status', 'umkm.profil']);
    });

    it('membuka menu penuh setelah toko disetujui', function (): void {
        $this->akun->setujuiUmkm($this->umkm, $this->admin);

        $menu = collect(Navigasi::untuk($this->pemilik->fresh()))->pluck('route');

        expect($menu)->toContain('umkm.produk')
            ->and($menu)->toContain('umkm.pesanan')
            ->and($menu)->not->toContain('umkm.status');
    });
});
