<?php

declare(strict_types=1);

use App\Enums\AlasanRouting;
use App\Enums\Role;
use App\Enums\TingkatWilayah;
use App\Models\Artikel;
use App\Models\BankSampahHarga;
use App\Models\Laporan;
use App\Models\LaporanKategori;
use App\Models\Produk;
use App\Models\User;
use App\Models\Wilayah;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\WilayahSeeder;

/**
 * Seeder (CLAUDE.md, Fase 9).
 *
 * Seeder adalah kode yang paling jarang dijalankan dan paling sering
 * rusak diam-diam: ia hanya dipakai saat memasang sistem dari nol,
 * yaitu tepat ketika tidak ada seorang pun yang siap menelusuri galat.
 * Karena itu ia diuji seperti kode lain.
 */
it('menyemai seluruh 38 provinsi dengan titik pusatnya', function (): void {
    $this->seed(WilayahSeeder::class);

    $provinsi = Wilayah::query()->tingkat(TingkatWilayah::Provinsi)->get();

    expect($provinsi)->toHaveCount(38);

    // Tanpa koordinat, WilayahResolverService tidak punya apa pun untuk
    // dibandingkan dan setiap laporan berakhir di tangan fasilitator.
    expect($provinsi->whereNull('latitude'))->toBeEmpty()
        ->and($provinsi->whereNull('longitude'))->toBeEmpty();

    expect($provinsi->pluck('kode')->unique())->toHaveCount(38);
});

it('menyusun hierarki contoh sampai tingkat desa', function (): void {
    $this->seed(WilayahSeeder::class);

    $badung = Wilayah::query()->where('kode', '51.03')->firstOrFail();

    expect($badung->tingkat)->toBe(TingkatWilayah::Kabupaten)
        ->and($badung->parent->kode)->toBe('51');

    $kecamatan = $badung->children()->first();

    expect($kecamatan->tingkat)->toBe(TingkatWilayah::Kecamatan)
        ->and($kecamatan->children()->count())->toBeGreaterThan(0)
        ->and($kecamatan->children()->first()->tingkat)->toBe(TingkatWilayah::Desa);
});

it('bisa dijalankan dua kali tanpa menggandakan apa pun', function (): void {
    $this->seed(WilayahSeeder::class);
    $sebelum = Wilayah::query()->count();

    $this->seed(WilayahSeeder::class);

    expect(Wilayah::query()->count())->toBe($sebelum);
});

describe('data demo', function (): void {
    beforeEach(function (): void {
        $this->seed(DatabaseSeeder::class);
    });

    it('menerbitkan satu akun untuk tiap role', function (): void {
        foreach (Role::cases() as $role) {
            expect(User::query()->denganRole($role)->exists())
                ->toBeTrue("Tidak ada akun demo untuk role {$role->value}.");
        }
    });

    it('mengisi master data yang dibutuhkan alur pertama', function (): void {
        // Tanpa kategori laporan, warga tidak bisa mengirim laporan
        // pertamanya dan aplikasi terlihat rusak di langkah paling awal.
        expect(LaporanKategori::query()->aktif()->count())->toBeGreaterThan(0)
            ->and(BankSampahHarga::query()->aktif()->count())->toBeGreaterThan(0)
            ->and(Produk::query()->tersedia()->count())->toBeGreaterThan(0);
    });

    it('menyiapkan artikel terbit lengkap dengan teks bacanya', function (): void {
        $artikel = Artikel::query()->terbit()->get();

        expect($artikel->count())->toBeGreaterThan(0);

        foreach ($artikel as $item) {
            expect($item->teks_baca)->not->toBeEmpty()
                ->and($item->teks_baca)->not->toContain('**')
                ->and($item->estimasi_baca_menit)->toBeGreaterThan(0);
        }
    });

    it('menghasilkan laporan di kedua jalur routing sekaligus', function (): void {
        // Dua wilayah demo sengaja diverifikasi dan satu dibiarkan belum,
        // supaya dasbor pemerintah daerah maupun papan fasilitator
        // sama-sama punya isi begitu sistem dipasang.
        expect(Laporan::query()->belumTerjangkau()->count())->toBeGreaterThan(0)
            ->and(Laporan::query()
                ->where('alasan_routing', '!=', AlasanRouting::WilayahBelumTerjangkau)
                ->count())->toBeGreaterThan(0);
    });

    it('mencatat sebagian laporan sebagai masukan suara', function (): void {
        // Angka pemakaian fitur suara di dasbor admin harus punya isi
        // sejak awal, bukan nol yang tidak bisa dibedakan dari rusak.
        expect(Laporan::query()->where('deskripsi_sumber', 'suara')->count())
            ->toBeGreaterThan(0);
    });

    it('bisa dijalankan dua kali tanpa menggandakan akun', function (): void {
        $sebelum = User::query()->count();

        $this->seed(DatabaseSeeder::class);

        expect(User::query()->count())->toBe($sebelum);
    });
});
