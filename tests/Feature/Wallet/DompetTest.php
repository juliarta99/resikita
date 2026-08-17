<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\StatusPenarikan;
use App\Enums\TipeTransaksiDompet;
use App\Exceptions\AturanBisnisException;
use App\Models\BankSampah;
use App\Models\BankSampahHarga;
use App\Models\User;
use App\Services\Wallet\DompetService;
use App\Services\Wallet\PenarikanService;
use App\Services\Wallet\SetoranService;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $this->dompet = app(DompetService::class);
    $this->setoran = app(SetoranService::class);
    $this->penarikan = app(PenarikanService::class);

    $this->nasabah = User::factory()->withRole(Role::Masyarakat)->nasabah()->create();
});

describe('dompet', function (): void {
    it('mencatat saldo sebelum dan sesudah pada setiap mutasi', function (): void {
        $this->dompet->kredit($this->nasabah, 50_000);
        $transaksi = $this->dompet->debit($this->nasabah, 12_500);

        expect($transaksi->saldo_sebelum)->toBe(50_000)
            ->and($transaksi->saldo_sesudah)->toBe(37_500)
            ->and($transaksi->jumlah)->toBe(12_500)
            ->and($this->dompet->saldo($this->nasabah))->toBe(37_500);
    });

    it('menolak pengeluaran yang melebihi saldo', function (): void {
        $this->dompet->kredit($this->nasabah, 10_000);

        $this->dompet->debit($this->nasabah, 15_000);
    })->throws(AturanBisnisException::class, 'Saldo tidak mencukupi');

    it('menyimpan jumlah sebagai nilai positif dan menentukan arah dari tipe', function (): void {
        $this->dompet->kredit($this->nasabah, 100_000);

        // Pemanggil mengirim nilai negatif; arah tetap ditentukan tipe,
        // sehingga tidak mungkin sebuah kredit diam-diam mengurangi saldo.
        $transaksi = $this->dompet->debit($this->nasabah, -30_000, TipeTransaksiDompet::Belanja);

        expect($transaksi->jumlah)->toBe(30_000)
            ->and($this->dompet->saldo($this->nasabah))->toBe(70_000);
    });

    it('menolak tipe transaksi yang arahnya tidak sesuai', function (): void {
        $this->dompet->kredit($this->nasabah, 10_000, TipeTransaksiDompet::Belanja);
    })->throws(AturanBisnisException::class, 'bukan pemasukan');

    it('menolak mutasi bernilai nol', function (): void {
        $this->dompet->kredit($this->nasabah, 0);
    })->throws(AturanBisnisException::class, 'tidak boleh nol');

    it('menyimpan saldo sebagai integer rupiah, bukan desimal', function (): void {
        $this->dompet->kredit($this->nasabah, 12_500);

        expect($this->dompet->untuk($this->nasabah)->saldo)->toBeInt()->toBe(12_500);
    });
});

describe('setoran sampah', function (): void {
    beforeEach(function (): void {
        $this->bankSampah = BankSampah::factory()->create();
        $this->petugas = User::factory()->withRole(Role::BankSampah)
            ->create(['bank_sampah_id' => $this->bankSampah->id]);

        $this->hargaBotol = BankSampahHarga::factory()
            ->harga(3_000)
            ->create(['bank_sampah_id' => $this->bankSampah->id, 'jenis_sampah' => 'Botol PET']);
    });

    it('menghitung nilai setoran dari berat dikali harga', function (): void {
        $setoran = $this->setoran->mulai($this->bankSampah, $this->petugas, $this->nasabah);
        $this->setoran->tambahItem($setoran, $this->hargaBotol, 2.5);

        expect($setoran->fresh()->total_nilai)->toBe(7_500)
            ->and((float) $setoran->fresh()->total_berat)->toBe(2.5);
    });

    it('membekukan harga saat transaksi meski katalog berubah kemudian', function (): void {
        $setoran = $this->setoran->mulai($this->bankSampah, $this->petugas, $this->nasabah);
        $this->setoran->tambahItem($setoran, $this->hargaBotol, 2.0);
        $this->setoran->selesaikan($setoran);

        // Bank sampah menaikkan harga setelah transaksi selesai.
        $this->hargaBotol->update(['harga_per_satuan' => 9_000]);

        $item = $setoran->fresh()->item->first();

        // Riwayat nasabah tidak boleh ikut berubah.
        expect($item->harga_snapshot)->toBe(3_000)
            ->and($item->jenis_snapshot)->toBe('Botol PET')
            ->and($setoran->fresh()->total_nilai)->toBe(6_000);
    });

    it('mengkreditkan saldo nasabah hanya saat setoran diselesaikan', function (): void {
        $setoran = $this->setoran->mulai($this->bankSampah, $this->petugas, $this->nasabah);
        $this->setoran->tambahItem($setoran, $this->hargaBotol, 3.0);

        // Masih ditimbang: saldo belum bergerak.
        expect($this->dompet->saldo($this->nasabah))->toBe(0);

        $this->setoran->selesaikan($setoran);

        expect($this->dompet->saldo($this->nasabah))->toBe(9_000);
    });

    it('menghitung ulang total saat item dihapus', function (): void {
        $setoran = $this->setoran->mulai($this->bankSampah, $this->petugas, $this->nasabah);
        $item = $this->setoran->tambahItem($setoran, $this->hargaBotol, 2.0);
        $this->setoran->tambahItem($setoran, $this->hargaBotol, 1.0);

        expect($setoran->fresh()->total_nilai)->toBe(9_000);

        $this->setoran->hapusItem($setoran->fresh(), $item);

        expect($setoran->fresh()->total_nilai)->toBe(3_000);
    });

    it('menolak perubahan setelah setoran diselesaikan', function (): void {
        $setoran = $this->setoran->mulai($this->bankSampah, $this->petugas, $this->nasabah);
        $this->setoran->tambahItem($setoran, $this->hargaBotol, 1.0);
        $this->setoran->selesaikan($setoran);

        $this->setoran->tambahItem($setoran->fresh(), $this->hargaBotol, 1.0);
    })->throws(AturanBisnisException::class, 'tidak bisa diubah lagi');

    it('menolak setoran kosong', function (): void {
        $setoran = $this->setoran->mulai($this->bankSampah, $this->petugas, $this->nasabah);

        $this->setoran->selesaikan($setoran);
    })->throws(AturanBisnisException::class, 'tanpa item');

    it('mencegah petugas mencatat setoran atas namanya sendiri', function (): void {
        $this->setoran->mulai($this->bankSampah, $this->petugas, $this->petugas);
    })->throws(AturanBisnisException::class, 'atas namanya sendiri');

    it('menolak jenis sampah dari katalog bank sampah lain', function (): void {
        $bankLain = BankSampah::factory()->create();
        $hargaLain = BankSampahHarga::factory()->create(['bank_sampah_id' => $bankLain->id]);

        $setoran = $this->setoran->mulai($this->bankSampah, $this->petugas, $this->nasabah);

        $this->setoran->tambahItem($setoran, $hargaLain, 1.0);
    })->throws(AturanBisnisException::class, 'bukan bagian dari katalog');

    it('menemukan nasabah lewat kode QR berisi ULID acak', function (): void {
        $ditemukan = $this->setoran->cariNasabah($this->nasabah->kode_qr);

        expect($ditemukan->id)->toBe($this->nasabah->id)
            // Kode QR tidak diturunkan dari data kependudukan mana pun.
            ->and($this->nasabah->kode_qr)->toHaveLength(26);
    });

    it('menolak kode QR yang tidak dikenali', function (): void {
        $this->setoran->cariNasabah('KODE-YANG-TIDAK-ADA');
    })->throws(AturanBisnisException::class, 'tidak dikenali');
});

describe('penarikan saldo', function (): void {
    beforeEach(function (): void {
        $this->dompet->kredit($this->nasabah, 200_000);
        $this->admin = User::factory()->withRole(Role::Admin)->create();

        $this->berkas = [
            'jumlah' => 50_000,
            'nama_bank' => 'BPD Bali',
            'no_rekening' => '0123456789',
            'atas_nama' => $this->nasabah->name,
        ];
    });

    it('memotong saldo sejak pengajuan dibuat', function (): void {
        $this->penarikan->ajukan($this->nasabah, $this->berkas);

        // Tanpa ini, pengguna bisa mengajukan penarikan seluruh saldonya
        // lalu membelanjakannya sambil menunggu persetujuan.
        expect($this->dompet->saldo($this->nasabah))->toBe(150_000);
    });

    it('mengembalikan saldo ketika pengajuan ditolak', function (): void {
        $pengajuan = $this->penarikan->ajukan($this->nasabah, $this->berkas);

        expect($this->dompet->saldo($this->nasabah))->toBe(150_000);

        $ditolak = $this->penarikan->tolak($pengajuan, $this->admin, 'Nomor rekening tidak cocok dengan nama.');

        expect($ditolak->status)->toBe(StatusPenarikan::Ditolak)
            ->and($this->dompet->saldo($this->nasabah))->toBe(200_000);
    });

    it('tidak mengembalikan saldo untuk penarikan yang selesai', function (): void {
        $pengajuan = $this->penarikan->ajukan($this->nasabah, $this->berkas);
        $this->penarikan->setujui($pengajuan, $this->admin);
        $this->penarikan->tandaiSelesai($pengajuan->fresh(), 'Transfer berhasil');

        expect($this->dompet->saldo($this->nasabah))->toBe(150_000);
    });

    it('menolak penarikan melebihi saldo', function (): void {
        $this->penarikan->ajukan($this->nasabah, [...$this->berkas, 'jumlah' => 500_000]);
    })->throws(AturanBisnisException::class, 'Saldo tidak mencukupi');

    it('menolak penarikan di bawah batas minimum', function (): void {
        $this->penarikan->ajukan($this->nasabah, [...$this->berkas, 'jumlah' => 5_000]);
    })->throws(AturanBisnisException::class, 'Penarikan minimum');

    it('menolak pengajuan kedua selama masih ada yang menunggu', function (): void {
        $this->penarikan->ajukan($this->nasabah, $this->berkas);
        $this->penarikan->ajukan($this->nasabah, $this->berkas);
    })->throws(AturanBisnisException::class, 'menunggu persetujuan');

    it('mewajibkan alasan saat menolak', function (): void {
        $pengajuan = $this->penarikan->ajukan($this->nasabah, $this->berkas);

        $this->penarikan->tolak($pengajuan, $this->admin, '  ');
    })->throws(AturanBisnisException::class, 'Alasan penolakan wajib diisi');
});
