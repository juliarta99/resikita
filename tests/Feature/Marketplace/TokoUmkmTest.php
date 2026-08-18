<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Livewire\Umkm\ProdukManager;
use App\Livewire\Umkm\Toko;
use App\Models\Produk;
use App\Models\ProdukKategori;
use App\Models\Umkm;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/**
 * Panel toko UMKM, khususnya penetapan titik asal pengiriman.
 *
 * Asal pengiriman adalah milik penjual, bukan milik platform. Berkas ini
 * menjaga dua hal: penjual benar-benar punya jalan menetapkannya, dan
 * barangnya tidak bisa sampai ke etalase sebelum itu dilakukan.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    // Tanpa ini, cadangan origin global dari .env pengembang membuat
    // seluruh penjagaan di bawah lolos tanpa pernah benar-benar diuji.
    config(['services.rajaongkir.origin_id' => null]);

    Http::fake([
        '*/destination/domestic-destination' => Http::response(['data' => [
            ['id' => 17_473, 'label' => 'KEROBOKAN KELOD, KUTA UTARA, BADUNG, BALI, 80361'],
        ]]),
    ]);

    $this->umkm = Umkm::factory()->tanpaAsalPengiriman()->create(['nama' => 'Kriya Plastik']);
    $this->penjual = User::factory()->withRole(Role::Umkm)->create(['umkm_id' => $this->umkm->id]);

    $this->actingAs($this->penjual);
});

describe('penetapan asal pengiriman', function (): void {
    it('menyimpan id wilayah beserta labelnya saat dipilih dari hasil pencarian', function (): void {
        Livewire::test(Toko::class)
            ->set('cariAsal', 'kerobokan')
            ->call('cariAlamatAsal')
            ->call('pilihAlamatAsal', 17_473, 'KEROBOKAN KELOD, KUTA UTARA, BADUNG, BALI, 80361')
            ->call('simpan');

        expect($this->umkm->fresh())
            ->destination_id->toBe(17_473)
            ->alamat_asal->toBe('KEROBOKAN KELOD, KUTA UTARA, BADUNG, BALI, 80361');
    });

    it('menolak kata kunci yang terlalu pendek untuk dicarikan', function (): void {
        Livewire::test(Toko::class)
            ->set('cariAsal', 'ke')
            ->call('cariAlamatAsal')
            ->assertHasErrors('cariAsal');

        Http::assertNothingSent();
    });

    it('membiarkan penjual menghapus asal pengirimannya', function (): void {
        $this->umkm->update(['destination_id' => 17_473, 'alamat_asal' => 'KEROBOKAN KELOD']);

        Livewire::test(Toko::class)
            ->call('hapusAlamatAsal')
            ->call('simpan');

        expect($this->umkm->fresh())
            ->destination_id->toBeNull()
            ->alamat_asal->toBeNull();
    });

    it('tidak membiarkan penjual mengubah status verifikasinya sendiri', function (): void {
        Livewire::test(Toko::class)
            ->set('nama', 'Kriya Plastik Nusantara')
            ->call('simpan');

        expect($this->umkm->fresh())
            ->nama->toBe('Kriya Plastik Nusantara')
            ->is_verified->toBeTrue()
            ->status->toBe($this->umkm->status);
    });
});

describe('penjagaan produk', function (): void {
    beforeEach(function (): void {
        $this->kategori = ProdukKategori::factory()->create();
    });

    it('menolak produk tampil di marketplace sebelum asal pengiriman ditetapkan', function (): void {
        Livewire::test(ProdukManager::class)
            ->call('bukaForm')
            ->set('nama', 'Tas Anyaman Sachet')
            ->set('kategoriId', (string) $this->kategori->id)
            ->set('harga', '50000')
            ->set('stok', '10')
            ->set('beratGram', '500')
            ->set('isActive', true)
            ->call('simpan');

        expect(Produk::count())->toBe(0);
    });

    it('menerima produk begitu asal pengiriman terisi', function (): void {
        $this->umkm->update([
            'destination_id' => 17_473,
            'alamat_asal' => 'KEROBOKAN KELOD, KUTA UTARA, BADUNG, BALI, 80361',
        ]);

        Livewire::test(ProdukManager::class)
            ->call('bukaForm')
            ->set('nama', 'Tas Anyaman Sachet')
            ->set('kategoriId', (string) $this->kategori->id)
            ->set('harga', '50000')
            ->set('stok', '10')
            ->set('beratGram', '500')
            ->set('isActive', true)
            ->call('simpan');

        expect(Produk::query()->where('umkm_id', $this->umkm->id)->count())->toBe(1);
    });

    it('menolak mengaktifkan kembali produk yang tersimpan saat asal masih kosong', function (): void {
        $produk = Produk::factory()->create([
            'umkm_id' => $this->umkm->id,
            'kategori_id' => $this->kategori->id,
            'is_active' => false,
        ]);

        Livewire::test(ProdukManager::class)->call('ubahAktif', $produk->id);

        expect($produk->fresh()->is_active)->toBeFalse();
    });
});
