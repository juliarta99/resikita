<?php

declare(strict_types=1);

use App\Models\Artikel;
use App\Services\Konten\TeksBacaService;

beforeEach(function (): void {
    $this->service = app(TeksBacaService::class);
});

it('membuang penanda judul markdown', function (): void {
    $hasil = $this->service->bersihkan("## Pemilahan dari Sumber\n\nMulailah dari dapur.");

    expect($hasil)->toBe("Pemilahan dari Sumber\n\nMulailah dari dapur.");
});

it('membacakan teks tautan, bukan alamatnya', function (): void {
    $hasil = $this->service->bersihkan('Baca [panduan pemilahan](https://resikita.id/panduan) lebih dulu.');

    expect($hasil)->toBe('Baca panduan pemilahan lebih dulu.')
        ->and($hasil)->not->toContain('https');
});

it('mengganti gambar dengan keterangannya', function (): void {
    $hasil = $this->service->bersihkan('![Tempat sampah tiga warna](/img/tempat-sampah.jpg)');

    expect($hasil)->toBe('Tempat sampah tiga warna');
});

it('membuang blok kode seluruhnya', function (): void {
    // Deretan sintaks yang dibacakan huruf demi huruf tidak membantu
    // siapa pun.
    $konten = "Contoh perhitungan:\n\n```php\n\$total = \$berat * \$harga;\n```\n\nSelesai.";

    $hasil = $this->service->bersihkan($konten);

    expect($hasil)->not->toContain('$total')
        ->and($hasil)->toContain('Contoh perhitungan')
        ->and($hasil)->toContain('Selesai');
});

it('membuang tabel karena strukturnya hilang saat diucapkan', function (): void {
    $konten = "Harga terkini:\n\n| Jenis | Harga |\n|---|---|\n| Botol PET | 3000 |\n\nHubungi bank sampah.";

    $hasil = $this->service->bersihkan($konten);

    expect($hasil)->not->toContain('|')
        ->and($hasil)->toContain('Harga terkini')
        ->and($hasil)->toContain('Hubungi bank sampah');
});

it('membuang penanda daftar tanpa membuang isinya', function (): void {
    $konten = "- Pilah organik\n- Pilah anorganik\n1. Cuci dulu\n2. Keringkan";

    $hasil = $this->service->bersihkan($konten);

    expect($hasil)->toBe("Pilah organik\nPilah anorganik\nCuci dulu\nKeringkan");
});

it('membuang penekanan tebal dan miring', function (): void {
    $hasil = $this->service->bersihkan('Limbah **B3** wajib _dipisahkan_ dari sampah lain.');

    expect($hasil)->toBe('Limbah B3 wajib dipisahkan dari sampah lain.');
});

it('membuang tag HTML termasuk video yang tertanam', function (): void {
    $konten = '<p>Tonton videonya.</p><iframe src="https://youtube.com/embed/abc"></iframe><p>Selesai.</p>';

    $hasil = $this->service->bersihkan($konten);

    expect($hasil)->not->toContain('<')
        ->and($hasil)->toContain('Tonton videonya')
        ->and($hasil)->toContain('Selesai');
});

it('mempertahankan jeda paragraf sebagai tempat mengambil napas', function (): void {
    $hasil = $this->service->bersihkan("Paragraf pertama.\n\n\n\nParagraf kedua.");

    expect($hasil)->toBe("Paragraf pertama.\n\nParagraf kedua.");
});

it('mengisi teks_baca dan estimasi waktu saat artikel disiapkan', function (): void {
    // Tepat 400 kata, ditambah penanda judul yang harus ikut terbuang
    // dan karena itu tidak boleh terhitung sebagai kata.
    $artikel = Artikel::factory()->create([
        'konten' => '## '.trim(str_repeat('kata ', 400)),
    ]);

    $this->service->siapkan($artikel);
    $artikel->save();

    expect($artikel->fresh()->teks_baca)->not->toContain('#')
        // 400 kata pada 200 kata per menit menjadi 2 menit.
        ->and($artikel->fresh()->estimasi_baca_menit)->toBe(2);
});

it('memberi estimasi minimal satu menit untuk artikel pendek', function (): void {
    expect($this->service->estimasiMenit('Tiga kata saja'))->toBe(1);
});

it('menghasilkan teks yang sama untuk web dan mobile dari satu kolom', function (): void {
    // Inti keputusan di CLAUDE.md 10.4: pembersihan dilakukan sekali di
    // peladen, bukan diulang oleh tiap klien.
    $artikel = Artikel::factory()->create(['konten' => '## Kompos **rumahan**', 'teks_baca' => null]);

    $untukWeb = $this->service->untukArtikel($artikel);
    $untukMobile = $this->service->untukArtikel($artikel->fresh());

    expect($untukWeb)->toBe($untukMobile)
        ->and($untukWeb)->toBe('Kompos rumahan')
        ->and($artikel->fresh()->teks_baca)->toBe('Kompos rumahan');
});
