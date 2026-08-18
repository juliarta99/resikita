<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\StatusPesanan;
use App\Exceptions\AturanBisnisException;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Umkm;
use App\Models\User;
use App\Services\Integration\MidtransService;
use App\Services\Marketplace\CheckoutService;
use App\Services\Marketplace\KeranjangService;
use App\Services\Marketplace\PesananService;
use App\Services\Wallet\DompetService;
use App\Services\Wallet\UmkmDompetService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    // RajaOngkir dipalsukan: uji ini menguji aturan checkout, bukan
    // ketersediaan layanan pihak ketiga.
    Http::fake([
        '*/calculate/domestic-cost' => Http::response([
            'data' => [
                ['code' => 'jne', 'service' => 'REG', 'description' => 'Layanan Reguler', 'cost' => 15_000, 'etd' => '2-3'],
                ['code' => 'jnt', 'service' => 'EZ', 'description' => 'Ekonomi', 'cost' => 12_000, 'etd' => '3-4'],
            ],
        ]),
        '*/destination/domestic-destination' => Http::response(['data' => [['id' => 17_473, 'label' => 'Kuta Utara, Badung, Bali']]]),
    ]);

    $this->keranjang = app(KeranjangService::class);
    $this->checkout = app(CheckoutService::class);
    $this->pesananService = app(PesananService::class);
    $this->dompet = app(DompetService::class);
    $this->umkmDompet = app(UmkmDompetService::class);

    // Cadangan origin global sengaja dikosongkan. Kalau .env pengembang
    // kebetulan mengisinya, toko tanpa alamat asal akan tetap lolos dan
    // uji penjagaannya lulus karena alasan yang salah.
    config(['services.rajaongkir.origin_id' => null]);

    $this->pembeli = User::factory()->withRole(Role::Masyarakat)->create();

    // Dua toko dengan titik asal yang berbeda, inti dari marketplace
    // banyak penjual: paket keduanya tidak berangkat dari tempat yang sama.
    $this->tokoA = Umkm::factory()->denganDompet()->create([
        'nama' => 'Kriya Plastik',
        'destination_id' => 17_001,
        'alamat_asal' => 'DENPASAR SELATAN, DENPASAR, BALI',
    ]);
    $this->tokoB = Umkm::factory()->denganDompet()->create([
        'nama' => 'Kompos Nusantara',
        'destination_id' => 18_002,
        'alamat_asal' => 'NGAGLIK, SLEMAN, DI YOGYAKARTA',
    ]);

    $this->produkA = Produk::factory()->harga(50_000)->stok(10)
        ->create(['umkm_id' => $this->tokoA->id, 'nama' => 'Tas Anyaman Sachet', 'berat_gram' => 500]);

    $this->produkB = Produk::factory()->harga(30_000)->stok(10)
        ->create(['umkm_id' => $this->tokoB->id, 'nama' => 'Kompos Organik 5kg', 'berat_gram' => 5000]);

    $this->alamat = [
        'nama_penerima' => 'Ni Kadek Sari',
        'phone_penerima' => '081234567890',
        'alamat_kirim' => 'Jl. Raya Dalung No. 12',
        'destination_id' => 17_473,
        'metode_bayar' => 'saldo',
    ];
});

/** Pilihan kurir untuk setiap toko dalam keranjang. */
function pengirimanUntuk(array $umkm): array
{
    $peta = [];

    foreach ($umkm as $satu) {
        $peta[$satu->id] = ['kurir' => 'jne', 'layanan' => 'REG'];
    }

    return $peta;
}

describe('keranjang', function (): void {
    it('menaikkan qty saat produk yang sama ditambahkan lagi', function (): void {
        $this->keranjang->tambah($this->pembeli, $this->produkA, 2);
        $this->keranjang->tambah($this->pembeli, $this->produkA, 3);

        expect($this->keranjang->isi($this->pembeli))->toHaveCount(1)
            ->and($this->keranjang->jumlahItem($this->pembeli))->toBe(5);
    });

    it('menolak penambahan melebihi stok', function (): void {
        $this->keranjang->tambah($this->pembeli, $this->produkA, 8);
        $this->keranjang->tambah($this->pembeli, $this->produkA, 5);
    })->throws(AturanBisnisException::class, 'Stok');

    it('menolak produk dari toko yang tidak aktif', function (): void {
        $this->tokoA->update(['status' => 'menunggu']);

        $this->keranjang->tambah($this->pembeli, $this->produkA->fresh(), 1);
    })->throws(AturanBisnisException::class, 'sedang tidak aktif');

    it('mengelompokkan isi keranjang per toko', function (): void {
        $this->keranjang->tambah($this->pembeli, $this->produkA, 2);
        $this->keranjang->tambah($this->pembeli, $this->produkB, 1);

        $grup = $this->keranjang->isiTerkelompok($this->pembeli);

        expect($grup)->toHaveCount(2)
            ->and($grup->pluck('subtotal')->sort()->values()->all())->toBe([30_000, 100_000]);
    });

    it('menurunkan qty mengikuti sisa stok, bukan membuang barisnya', function (): void {
        $this->keranjang->tambah($this->pembeli, $this->produkA, 8);
        $this->produkA->update(['stok' => 3]);

        $this->keranjang->bersihkanYangTidakTersedia($this->pembeli);

        expect($this->keranjang->jumlahItem($this->pembeli))->toBe(3);
    });

    it('mengeluarkan produk yang stoknya habis', function (): void {
        $this->keranjang->tambah($this->pembeli, $this->produkA, 2);
        $this->produkA->update(['stok' => 0]);

        $dikeluarkan = $this->keranjang->bersihkanYangTidakTersedia($this->pembeli);

        expect($dikeluarkan)->toContain('Tas Anyaman Sachet')
            ->and($this->keranjang->isi($this->pembeli))->toBeEmpty();
    });
});

describe('asal pengiriman', function (): void {
    it('menghitung ongkir dari titik asal masing-masing toko', function (): void {
        $this->keranjang->tambah($this->pembeli, $this->produkA, 1);
        $this->keranjang->tambah($this->pembeli, $this->produkB, 1);

        $this->checkout->pratinjau($this->pembeli, 17_473);

        // Inti perbaikannya: origin diambil dari toko, bukan dari satu
        // titik milik platform. Kalau keduanya sama, tarif salah satu
        // toko pasti dihitung dari tempat yang tidak pernah dilewati
        // paketnya.
        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'domestic-cost')
            && (int) $request['origin'] === 17_001);

        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'domestic-cost')
            && (int) $request['origin'] === 18_002);
    });

    it('menyertakan asal tiap toko pada pratinjau', function (): void {
        $this->keranjang->tambah($this->pembeli, $this->produkA, 1);

        $baris = $this->checkout->pratinjau($this->pembeli, 17_473)->first();

        expect($baris['asal'])->toBe([
            'destination_id' => 17_001,
            'alamat' => 'DENPASAR SELATAN, DENPASAR, BALI',
        ]);
    });

    it('menolak memasukkan produk toko yang belum menetapkan asal ke keranjang', function (): void {
        $this->tokoA->update(['destination_id' => null, 'alamat_asal' => null]);

        $this->keranjang->tambah($this->pembeli, $this->produkA->fresh(), 1);
    })->throws(AturanBisnisException::class, 'belum menetapkan alamat asal pengiriman');

    it('mengeluarkan produk dari keranjang ketika tokonya kehilangan asal', function (): void {
        $this->keranjang->tambah($this->pembeli, $this->produkA, 1);

        $this->tokoA->update(['destination_id' => null, 'alamat_asal' => null]);

        $dikeluarkan = $this->keranjang->bersihkanYangTidakTersedia($this->pembeli);

        expect($dikeluarkan)->toContain('Tas Anyaman Sachet')
            ->and($this->keranjang->isi($this->pembeli))->toBeEmpty();
    });

    it('memakai cadangan config selama toko belum punya asal sendiri', function (): void {
        config(['services.rajaongkir.origin_id' => 15_555]);

        $this->tokoA->update(['destination_id' => null, 'alamat_asal' => null]);

        $this->keranjang->tambah($this->pembeli, $this->produkA->fresh(), 1);
        $this->checkout->pratinjau($this->pembeli, 17_473);

        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'domestic-cost')
            && (int) $request['origin'] === 15_555);
    });
});

describe('checkout', function (): void {
    it('memecah keranjang lintas toko menjadi satu pesanan per toko', function (): void {
        $this->dompet->kredit($this->pembeli, 500_000);

        $this->keranjang->tambah($this->pembeli, $this->produkA, 2);
        $this->keranjang->tambah($this->pembeli, $this->produkB, 1);

        $pesanan = $this->checkout->checkout($this->pembeli, [
            ...$this->alamat,
            'pengiriman' => pengirimanUntuk([$this->tokoA, $this->tokoB]),
        ]);

        expect($pesanan)->toHaveCount(2)
            ->and($pesanan->pluck('umkm_id')->sort()->values()->all())
            ->toBe([$this->tokoA->id, $this->tokoB->id]);

        // Setiap pesanan membawa ongkirnya sendiri, karena paketnya
        // berangkat dari alamat yang berbeda.
        expect($pesanan->every(fn ($p): bool => $p->ongkir === 15_000))->toBeTrue();
    });

    it('membekukan nama dan harga produk pada item pesanan', function (): void {
        $this->dompet->kredit($this->pembeli, 500_000);
        $this->keranjang->tambah($this->pembeli, $this->produkA, 2);

        $pesanan = $this->checkout->checkout($this->pembeli, [
            ...$this->alamat,
            'pengiriman' => pengirimanUntuk([$this->tokoA]),
        ])->first();

        // UMKM mengubah harga dan nama setelah pesanan terbit.
        $this->produkA->update(['harga' => 99_000, 'nama' => 'Nama Baru']);

        $item = $pesanan->fresh()->item->first();

        expect($item->harga_snapshot)->toBe(50_000)
            ->and($item->nama_snapshot)->toBe('Tas Anyaman Sachet')
            ->and($item->subtotal)->toBe(100_000);
    });

    it('memotong stok saat checkout, bukan saat pembayaran lunas', function (): void {
        $this->dompet->kredit($this->pembeli, 500_000);
        $this->keranjang->tambah($this->pembeli, $this->produkA, 3);

        $this->checkout->checkout($this->pembeli, [
            ...$this->alamat,
            'pengiriman' => pengirimanUntuk([$this->tokoA]),
        ]);

        expect($this->produkA->fresh()->stok)->toBe(7);
    });

    it('membayar lunas seketika ketika memakai saldo', function (): void {
        $this->dompet->kredit($this->pembeli, 500_000);
        $this->keranjang->tambah($this->pembeli, $this->produkA, 2);

        $pesanan = $this->checkout->checkout($this->pembeli, [
            ...$this->alamat,
            'pengiriman' => pengirimanUntuk([$this->tokoA]),
        ])->first();

        expect($pesanan->status)->toBe(StatusPesanan::Dibayar)
            ->and($pesanan->total)->toBe(115_000)
            ->and($this->dompet->saldo($this->pembeli))->toBe(385_000);
    });

    it('memeriksa saldo untuk seluruh belanja sebelum satu pesanan pun dibuat', function (): void {
        // Cukup untuk toko pertama tapi tidak untuk keduanya. Tanpa
        // pemeriksaan di muka, pembeli akan menerima setengah belanjaan.
        $this->dompet->kredit($this->pembeli, 120_000);

        $this->keranjang->tambah($this->pembeli, $this->produkA, 2);
        $this->keranjang->tambah($this->pembeli, $this->produkB, 1);

        try {
            $this->checkout->checkout($this->pembeli, [
                ...$this->alamat,
                'pengiriman' => pengirimanUntuk([$this->tokoA, $this->tokoB]),
            ]);
        } catch (AturanBisnisException) {
            // diharapkan
        }

        expect(Pesanan::count())->toBe(0)
            ->and($this->produkA->fresh()->stok)->toBe(10)
            ->and($this->dompet->saldo($this->pembeli))->toBe(120_000);
    });

    it('mengosongkan keranjang setelah checkout berhasil', function (): void {
        $this->dompet->kredit($this->pembeli, 500_000);
        $this->keranjang->tambah($this->pembeli, $this->produkA, 1);

        $this->checkout->checkout($this->pembeli, [
            ...$this->alamat,
            'pengiriman' => pengirimanUntuk([$this->tokoA]),
        ]);

        expect($this->keranjang->isi($this->pembeli))->toBeEmpty();
    });

    it('menolak checkout tanpa pilihan pengiriman', function (): void {
        $this->dompet->kredit($this->pembeli, 500_000);
        $this->keranjang->tambah($this->pembeli, $this->produkA, 1);

        $this->checkout->checkout($this->pembeli, [...$this->alamat, 'pengiriman' => []]);
    })->throws(AturanBisnisException::class, 'belum ditentukan');

    it('menolak checkout keranjang kosong', function (): void {
        $this->checkout->checkout($this->pembeli, [...$this->alamat, 'pengiriman' => []]);
    })->throws(AturanBisnisException::class, 'Keranjang Anda kosong');
});

describe('checkout sebagian', function (): void {
    beforeEach(function (): void {
        $this->keranjang->tambah($this->pembeli, $this->produkA, 2);
        $this->keranjang->tambah($this->pembeli, $this->produkB, 1);
    });

    it('menyaring pratinjau ke toko yang diminta saja', function (): void {
        $grup = $this->checkout->pratinjau($this->pembeli, 17_473, [$this->tokoB->id]);

        expect($grup)->toHaveCount(1)
            ->and($grup->first()['umkm']->id)->toBe($this->tokoB->id);

        // Ongkir toko yang tidak dipilih tidak perlu ditanyakan ke
        // penyedia sama sekali.
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'domestic-cost')
            && (int) $request['origin'] === 17_001);
    });

    it('mengembalikan seluruh toko ketika penyaring tidak dikirim', function (): void {
        expect($this->checkout->pratinjau($this->pembeli, 17_473))->toHaveCount(2);
    });

    it('hanya membuat pesanan untuk toko yang ada di kunci pengiriman', function (): void {
        $this->dompet->kredit($this->pembeli, 500_000);

        $pesanan = $this->checkout->checkout($this->pembeli, [
            ...$this->alamat,
            'pengiriman' => pengirimanUntuk([$this->tokoA]),
        ]);

        expect($pesanan)->toHaveCount(1)
            ->and($pesanan->first()->umkm_id)->toBe($this->tokoA->id);
    });

    it('menyisakan baris keranjang milik toko yang tidak di-checkout', function (): void {
        $this->dompet->kredit($this->pembeli, 500_000);

        $this->checkout->checkout($this->pembeli, [
            ...$this->alamat,
            'pengiriman' => pengirimanUntuk([$this->tokoA]),
        ]);

        $sisa = $this->keranjang->isi($this->pembeli);

        expect($sisa)->toHaveCount(1)
            ->and($sisa->first()->produk_id)->toBe($this->produkB->id);
    });

    it('memeriksa saldo atas toko terpilih saja', function (): void {
        // Cukup untuk toko A (115.000) tapi tidak untuk A dan B
        // sekaligus (160.000). Menagih seluruh keranjang akan menolak
        // pembelian yang sebenarnya sanggup dibayar.
        $this->dompet->kredit($this->pembeli, 120_000);

        $pesanan = $this->checkout->checkout($this->pembeli, [
            ...$this->alamat,
            'pengiriman' => pengirimanUntuk([$this->tokoA]),
        ]);

        expect($pesanan->first()->status)->toBe(StatusPesanan::Dibayar)
            ->and($this->dompet->saldo($this->pembeli))->toBe(5_000);
    });

    it('tidak memotong stok toko yang tidak di-checkout', function (): void {
        $this->dompet->kredit($this->pembeli, 500_000);

        $this->checkout->checkout($this->pembeli, [
            ...$this->alamat,
            'pengiriman' => pengirimanUntuk([$this->tokoA]),
        ]);

        expect($this->produkA->fresh()->stok)->toBe(8)
            ->and($this->produkB->fresh()->stok)->toBe(10);
    });

    it('tidak dibatalkan karena produk toko lain kehabisan stok', function (): void {
        $this->dompet->kredit($this->pembeli, 500_000);

        // Kompos di toko B habis. Dulu ini menggagalkan seluruh
        // permintaan, termasuk pembelian toko A yang sama sekali tidak
        // bermasalah.
        $this->produkB->update(['stok' => 0]);

        $pesanan = $this->checkout->checkout($this->pembeli, [
            ...$this->alamat,
            'pengiriman' => pengirimanUntuk([$this->tokoA]),
        ]);

        expect($pesanan)->toHaveCount(1)
            ->and($pesanan->first()->umkm_id)->toBe($this->tokoA->id);
    });

    it('tetap membatalkan ketika produk toko terpilih kehabisan stok', function (): void {
        $this->dompet->kredit($this->pembeli, 500_000);

        $this->produkA->update(['stok' => 0]);

        $this->checkout->checkout($this->pembeli, [
            ...$this->alamat,
            'pengiriman' => pengirimanUntuk([$this->tokoA]),
        ]);
    })->throws(AturanBisnisException::class, 'tidak lagi tersedia');

    it('membiarkan baris toko lain diperiksa saat keranjang dibuka utuh', function (): void {
        $this->dompet->kredit($this->pembeli, 500_000);
        $this->produkB->update(['stok' => 0]);

        $this->checkout->checkout($this->pembeli, [
            ...$this->alamat,
            'pengiriman' => pengirimanUntuk([$this->tokoA]),
        ]);

        // Checkout melewatinya, tapi barisnya tidak dibiarkan menetap:
        // pembersihan tak tersaring berikutnya tetap mengeluarkannya.
        $dikeluarkan = $this->keranjang->bersihkanYangTidakTersedia($this->pembeli);

        expect($dikeluarkan)->toContain('Kompos Organik 5kg')
            ->and($this->keranjang->isi($this->pembeli))->toBeEmpty();
    });

    it('tidak membersihkan toko di luar penyaring saat pratinjau', function (): void {
        $this->produkB->update(['stok' => 0]);

        $this->checkout->pratinjau($this->pembeli, 17_473, [$this->tokoA->id]);

        // Pratinjau satu toko tidak boleh diam-diam mengubah isi
        // keranjang toko lain.
        expect($this->keranjang->isi($this->pembeli))->toHaveCount(2);
    });

    it('menolak kunci pengiriman untuk toko yang tidak ada di keranjang', function (): void {
        $this->dompet->kredit($this->pembeli, 500_000);

        $tokoLain = Umkm::factory()->denganDompet()->create();

        $this->checkout->checkout($this->pembeli, [
            ...$this->alamat,
            'pengiriman' => pengirimanUntuk([$this->tokoA, $tokoLain]),
        ]);
    })->throws(AturanBisnisException::class, 'tidak ada di keranjang Anda');
});

describe('daur hidup pesanan', function (): void {
    beforeEach(function (): void {
        $this->dompet->kredit($this->pembeli, 500_000);
        $this->keranjang->tambah($this->pembeli, $this->produkA, 2);

        $this->pesanan = $this->checkout->checkout($this->pembeli, [
            ...$this->alamat,
            'pengiriman' => pengirimanUntuk([$this->tokoA]),
        ])->first();
    });

    it('mengembalikan stok dan dana saat pesanan dibatalkan', function (): void {
        expect($this->produkA->fresh()->stok)->toBe(8);

        $this->checkout->batalkan($this->pesanan, 'Pembeli berubah pikiran');

        expect($this->pesanan->fresh()->status)->toBe(StatusPesanan::Dibatalkan)
            ->and($this->produkA->fresh()->stok)->toBe(10)
            ->and($this->dompet->saldo($this->pembeli))->toBe(500_000);
    });

    it('mengkreditkan saldo penjual saat pesanan selesai, bukan saat dibayar', function (): void {
        expect($this->umkmDompet->saldo($this->tokoA))->toBe(0);

        $this->pesananService->tandaiDikemas($this->pesanan);
        $this->pesananService->tandaiDikirim($this->pesanan->fresh(), 'JNE1234567890');
        $this->pesananService->tandaiSelesai($this->pesanan->fresh());

        // Penjual menerima subtotal, bukan total: ongkir adalah hak kurir.
        expect($this->umkmDompet->saldo($this->tokoA))->toBe(100_000);
    });

    it('menolak perpindahan status yang melompat', function (): void {
        $this->pesananService->tandaiDikirim($this->pesanan, 'RESI123');
    })->throws(AturanBisnisException::class, 'tidak bisa diubah menjadi');

    it('mewajibkan nomor resi saat mengirim', function (): void {
        $this->pesananService->tandaiDikemas($this->pesanan);
        $this->pesananService->tandaiDikirim($this->pesanan->fresh(), '   ');
    })->throws(AturanBisnisException::class, 'Nomor resi wajib diisi');

    it('menolak pembatalan setelah pesanan dikemas', function (): void {
        $this->pesananService->tandaiDikemas($this->pesanan);

        $this->checkout->batalkan($this->pesanan->fresh());
    })->throws(AturanBisnisException::class, 'tidak bisa dibatalkan');

    it('membuka ulasan hanya setelah pesanan selesai', function (): void {
        $this->pesananService->tulisUlasan($this->pesanan, $this->pembeli, [
            'produk_id' => $this->produkA->id,
            'rating' => 5,
        ]);
    })->throws(AturanBisnisException::class, 'setelah pesanan selesai');

    it('menerima ulasan dari pembeli setelah pesanan selesai', function (): void {
        $this->pesananService->tandaiDikemas($this->pesanan);
        $this->pesananService->tandaiDikirim($this->pesanan->fresh(), 'RESI123');
        $this->pesananService->tandaiSelesai($this->pesanan->fresh());

        $ulasan = $this->pesananService->tulisUlasan($this->pesanan->fresh(), $this->pembeli, [
            'produk_id' => $this->produkA->id,
            'rating' => 5,
            'komentar' => 'Anyamannya rapi, bahannya benar dari sachet bekas.',
        ]);

        expect($ulasan->rating)->toBe(5)
            ->and($ulasan->umkm_id)->toBe($this->tokoA->id);
    });

    it('menolak ulasan ganda untuk produk yang sama', function (): void {
        $this->pesananService->tandaiDikemas($this->pesanan);
        $this->pesananService->tandaiDikirim($this->pesanan->fresh(), 'RESI123');
        $this->pesananService->tandaiSelesai($this->pesanan->fresh());

        $data = ['produk_id' => $this->produkA->id, 'rating' => 5];

        $this->pesananService->tulisUlasan($this->pesanan->fresh(), $this->pembeli, $data);
        $this->pesananService->tulisUlasan($this->pesanan->fresh(), $this->pembeli, $data);
    })->throws(AturanBisnisException::class, 'sudah menulis ulasan');

    it('menolak ulasan dari orang lain', function (): void {
        $orangLain = User::factory()->withRole(Role::Masyarakat)->create();

        $this->pesananService->tulisUlasan($this->pesanan, $orangLain, [
            'produk_id' => $this->produkA->id, 'rating' => 5,
        ]);
    })->throws(AturanBisnisException::class, 'bukan milik Anda');
});

describe('endpoint ongkir mobile', function (): void {
    it('menghitung dari asal toko yang diminta', function (): void {
        $this->actingAs($this->pembeli, 'sanctum')
            ->postJson('/api/v1/ongkir/hitung', [
                'umkm_id' => $this->tokoB->id,
                'destination_id' => 17_473,
                'berat_gram' => 800,
            ])
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [['kurir', 'layanan', 'deskripsi', 'biaya', 'estimasi']],
            ]);

        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'domestic-cost')
            && (int) $request['origin'] === 18_002);
    });

    it('menolak permintaan tanpa toko pengirim', function (): void {
        $this->actingAs($this->pembeli, 'sanctum')
            ->postJson('/api/v1/ongkir/hitung', [
                'destination_id' => 17_473,
                'berat_gram' => 800,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('umkm_id');
    });

    it('menolak menghitung untuk toko yang belum menetapkan asal', function (): void {
        $tanpaAsal = Umkm::factory()->tanpaAsalPengiriman()->create();

        $this->actingAs($this->pembeli, 'sanctum')
            ->postJson('/api/v1/ongkir/hitung', [
                'umkm_id' => $tanpaAsal->id,
                'destination_id' => 17_473,
                'berat_gram' => 800,
            ])
            ->assertStatus(503)
            ->assertJsonPath('success', false);
    });
});

describe('notifikasi pembayaran', function (): void {
    it('menolak notifikasi dengan tanda tangan yang tidak sah', function (): void {
        app(MidtransService::class)->bacaNotifikasi([
            'order_id' => 'RSK-ORD-PALSU',
            'status_code' => '200',
            'gross_amount' => '115000.00',
            'signature_key' => 'tanda-tangan-karangan',
            'transaction_status' => 'settlement',
        ]);
    })->throws(AturanBisnisException::class, 'Tanda tangan notifikasi tidak sah');

    it('menolak notifikasi yang tidak lengkap', function (): void {
        app(MidtransService::class)->bacaNotifikasi(['order_id' => 'RSK-ORD-1']);
    })->throws(AturanBisnisException::class, 'tidak lengkap');
});
