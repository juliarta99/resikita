<?php

declare(strict_types=1);

use App\Enums\AlasanRouting;
use App\Enums\Role;
use App\Enums\StatusLaporan;
use App\Enums\StatusPengajuanWilayah;
use App\Enums\StatusRegistrasiWilayah;
use App\Exceptions\AturanBisnisException;
use App\Models\LaporanKategori;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\Laporan\LaporanService;
use App\Services\Wilayah\PengajuanWilayahService;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $this->titik = ['latitude' => -8.5830000, 'longitude' => 115.1830000];

    $this->provinsi = Wilayah::factory()->create(['kode' => '51', 'nama' => 'Bali', ...$this->titik]);
    $this->kabupaten = Wilayah::factory()->anakDari($this->provinsi, '03')->create(['nama' => 'Badung']);
    $this->kecamatan = Wilayah::factory()->anakDari($this->kabupaten, '05')->create();
    $this->desa = Wilayah::factory()->anakDari($this->kecamatan, '2001')->create(['nama' => 'Dalung']);

    $this->superAdmin = User::factory()->withRole(Role::SuperAdmin)->create();
    $this->fasilitator = User::factory()->withRole(Role::FasilitatorWilayah)->create();
    $this->kategori = LaporanKategori::factory()->create();

    $this->service = app(PengajuanWilayahService::class);

    $this->berkas = [
        'pemohon_nama' => 'Wayan Sudira',
        'pemohon_jabatan' => 'Kepala Dinas Lingkungan Hidup',
        'pemohon_email' => 'dlh@badungkab.go.id',
        'pemohon_phone' => '081234567890',
        'instansi' => 'Dinas Lingkungan Hidup Kabupaten Badung',
        'surat_path' => 'pengajuan/surat-badung.pdf',
    ];
});

it('menandai wilayah sebagai diajukan saat berkas masuk', function (): void {
    $pengajuan = $this->service->ajukan($this->kabupaten, $this->berkas);

    expect($pengajuan->status)->toBe(StatusPengajuanWilayah::Diajukan)
        ->and($this->kabupaten->fresh()->status_registrasi)->toBe(StatusRegistrasiWilayah::Diajukan);
});

it('menolak pengajuan ganda untuk wilayah yang sama', function (): void {
    $this->service->ajukan($this->kabupaten, $this->berkas);
    $this->service->ajukan($this->kabupaten, $this->berkas);
})->throws(AturanBisnisException::class, 'sedang ditinjau');

it('menolak pengajuan di tingkat kecamatan', function (): void {
    // Kecamatan tetap ada di hierarki wilayah, tapi bukan tingkat
    // kewenangan di Resikita (CLAUDE.md 6.1).
    $this->service->ajukan($this->kecamatan, $this->berkas);
})->throws(AturanBisnisException::class, 'Kecamatan bukan tingkat kewenangan');

it('menolak pengajuan untuk wilayah yang sudah terdaftar', function (): void {
    $this->kabupaten->update(['status_registrasi' => StatusRegistrasiWilayah::Terverifikasi]);

    $this->service->ajukan($this->kabupaten, $this->berkas);
})->throws(AturanBisnisException::class, 'sudah terdaftar');

it('menerbitkan akun admin kabupaten saat pengajuan disetujui', function (): void {
    $pengajuan = $this->service->ajukan($this->kabupaten, $this->berkas);

    $hasil = $this->service->setujui($pengajuan, $this->superAdmin);

    expect($hasil['pengajuan']->status)->toBe(StatusPengajuanWilayah::Disetujui)
        ->and($this->kabupaten->fresh()->status_registrasi)->toBe(StatusRegistrasiWilayah::Terverifikasi)
        ->and($this->kabupaten->fresh()->terverifikasi_at)->not->toBeNull()
        ->and($hasil['akun']->email)->toBe('dlh@badungkab.go.id')
        ->and($hasil['akun']->wilayah_id)->toBe($this->kabupaten->id)
        ->and($hasil['akun']->hasRole(Role::AdminKabupaten->value))->toBeTrue();
});

it('menerbitkan role sesuai tingkat wilayah yang diajukan', function (): void {
    $pengajuanDesa = $this->service->ajukan($this->desa, [
        ...$this->berkas,
        'pemohon_email' => 'perbekel@dalung.desa.id',
    ]);

    $hasil = $this->service->setujui($pengajuanDesa, $this->superAdmin);

    expect($hasil['akun']->hasRole(Role::KepalaDesa->value))->toBeTrue()
        ->and($hasil['akun']->wilayah_id)->toBe($this->desa->id);
});

it('mengalihkan laporan lama dari fasilitator ke pemerintah yang baru bergabung', function (): void {
    // Tiga laporan masuk saat wilayah belum terjangkau.
    $pelapor = User::factory()->withRole(Role::Masyarakat)->create();
    $laporanService = app(LaporanService::class);

    $laporan = collect(range(1, 3))->map(fn (int $i) => $laporanService->buat($pelapor, [
        'kategori_id' => $this->kategori->id,
        'judul' => "Tumpukan sampah $i",
        'deskripsi' => 'Belum ada yang menangani.',
        ...$this->titik,
    ]));

    expect($laporan->first()->penanggung_jawab_id)->toBe($this->fasilitator->id)
        ->and($laporan->first()->alasan_routing)->toBe(AlasanRouting::WilayahBelumTerjangkau);

    // Satu laporan sudah selesai ditangani fasilitator sebelum wilayah
    // bergabung, riwayatnya tidak boleh diubah surut.
    $laporan->last()->update(['status' => StatusLaporan::Selesai, 'selesai_at' => now()]);

    $pengajuan = $this->service->ajukan($this->kabupaten, $this->berkas);
    $hasil = $this->service->setujui($pengajuan, $this->superAdmin);

    expect($hasil['laporan_dialihkan'])->toBe(2)
        ->and($laporan->first()->fresh()->penanggung_jawab_id)->toBe($hasil['akun']->id)
        ->and($laporan->first()->fresh()->alasan_routing)->toBe(AlasanRouting::KabupatenTerverifikasi)
        ->and($laporan->last()->fresh()->penanggung_jawab_id)->toBe($this->fasilitator->id)
        ->and($laporan->last()->fresh()->alasan_routing)->toBe(AlasanRouting::WilayahBelumTerjangkau);
});

it('menolkan skor prioritas setelah wilayah bergabung', function (): void {
    $pelapor = User::factory()->withRole(Role::Masyarakat)->create();

    app(LaporanService::class)->buat($pelapor, [
        'kategori_id' => $this->kategori->id,
        'judul' => 'Tumpukan sampah', 'deskripsi' => 'Belum ditangani.',
        ...$this->titik,
    ]);

    expect($this->kabupaten->fresh()->skor_prioritas)->toBe(1);

    $pengajuan = $this->service->ajukan($this->kabupaten, $this->berkas);
    $this->service->setujui($pengajuan, $this->superAdmin);

    expect($this->kabupaten->fresh()->skor_prioritas)->toBe(0);
});

it('mengembalikan wilayah ke belum terjangkau saat pengajuan ditolak', function (): void {
    $pengajuan = $this->service->ajukan($this->kabupaten, $this->berkas);

    $ditolak = $this->service->tolak($pengajuan, $this->superAdmin, 'Surat tugas belum ditandatangani pejabat berwenang.');

    // Yang ditolak berkasnya, bukan wilayahnya, daerah yang sama harus
    // tetap bisa mengajukan lagi dengan berkas yang diperbaiki.
    expect($ditolak->status)->toBe(StatusPengajuanWilayah::Ditolak)
        ->and($ditolak->catatan)->toContain('belum ditandatangani')
        ->and($this->kabupaten->fresh()->status_registrasi)->toBe(StatusRegistrasiWilayah::BelumTerjangkau);

    // Dan memang bisa mengajukan ulang.
    $ulang = $this->service->ajukan($this->kabupaten->fresh(), $this->berkas);
    expect($ulang->status)->toBe(StatusPengajuanWilayah::Diajukan);
});

it('mewajibkan alasan saat menolak pengajuan', function (): void {
    $pengajuan = $this->service->ajukan($this->kabupaten, $this->berkas);

    $this->service->tolak($pengajuan, $this->superAdmin, '   ');
})->throws(AturanBisnisException::class, 'Alasan penolakan wajib diisi');

it('hanya mengizinkan super admin meninjau pengajuan', function (): void {
    $pengajuan = $this->service->ajukan($this->kabupaten, $this->berkas);
    $adminBiasa = User::factory()->withRole(Role::Admin)->create();

    $this->service->setujui($pengajuan, $adminBiasa);
})->throws(AturanBisnisException::class, 'Hanya Super Admin');

it('memakai ulang akun yang sudah ada alih-alih membuat kembar', function (): void {
    // Pemohon sudah punya akun masyarakat sebelum menjabat.
    $akunLama = User::factory()->withRole(Role::Masyarakat)->create([
        'email' => 'dlh@badungkab.go.id',
    ]);

    $pengajuan = $this->service->ajukan($this->kabupaten, $this->berkas);
    $hasil = $this->service->setujui($pengajuan, $this->superAdmin);

    expect($hasil['akun']->id)->toBe($akunLama->id)
        ->and(User::where('email', 'dlh@badungkab.go.id')->count())->toBe(1)
        ->and($hasil['akun']->hasRole(Role::AdminKabupaten->value))->toBeTrue();
});

it('mencabut role pemerintahan lama saat pejabat berpindah tingkat', function (): void {
    $pengajuanKab = $this->service->ajukan($this->kabupaten, $this->berkas);
    $hasilKab = $this->service->setujui($pengajuanKab, $this->superAdmin);

    expect($hasilKab['akun']->hasRole(Role::AdminKabupaten->value))->toBeTrue();

    // Orang yang sama kemudian mengajukan atas nama provinsi.
    $pengajuanProv = $this->service->ajukan($this->provinsi, $this->berkas);
    $hasilProv = $this->service->setujui($pengajuanProv, $this->superAdmin);

    expect($hasilProv['akun']->hasRole(Role::AdminProvinsi->value))->toBeTrue()
        ->and($hasilProv['akun']->hasRole(Role::AdminKabupaten->value))->toBeFalse();
});

it('menolak peninjauan ulang pengajuan yang sudah final', function (): void {
    $pengajuan = $this->service->ajukan($this->kabupaten, $this->berkas);
    $this->service->setujui($pengajuan, $this->superAdmin);

    $this->service->setujui($pengajuan->fresh(), $this->superAdmin);
})->throws(AturanBisnisException::class, 'sudah Disetujui');
