<?php

declare(strict_types=1);

namespace App\Services\Wallet;

use App\Enums\TipeTransaksiDompet;
use App\Exceptions\AturanBisnisException;
use App\Models\Umkm;
use App\Models\UmkmDompet;
use App\Models\UmkmDompetTransaksi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Pintu tunggal perubahan saldo UMKM.
 *
 * Strukturnya mencerminkan DompetService, tapi kelasnya dipisah karena
 * pemiliknya berbeda jenis. Dompet warga milik seorang `User`; dompet
 * UMKM milik badan usaha, sehingga satu UMKM bisa berganti pengelola
 * tanpa saldonya ikut berpindah tangan.
 *
 * Menyatukan keduanya lewat relasi polimorfik pernah dipertimbangkan
 * dan ditolak: query saldo warga dan saldo penjual hampir tidak pernah
 * dijalankan bersamaan, sementara polimorfisme akan membuat setiap
 * pembacaan saldo membawa kolom tipe yang selalu bernilai sama.
 */
class UmkmDompetService
{
    public function untuk(Umkm $umkm): UmkmDompet
    {
        return UmkmDompet::firstOrCreate(['umkm_id' => $umkm->id], ['saldo' => 0]);
    }

    public function saldo(Umkm $umkm): int
    {
        return $this->untuk($umkm)->saldo;
    }

    /** Tambah saldo: hasil penjualan yang sudah tuntas. */
    public function kredit(
        Umkm $umkm,
        int $jumlah,
        TipeTransaksiDompet $tipe = TipeTransaksiDompet::Setor,
        ?Model $reference = null,
        ?string $keterangan = null,
    ): UmkmDompetTransaksi {
        if (! $tipe->isPemasukan()) {
            throw AturanBisnisException::karena("Tipe transaksi \"{$tipe->label()}\" bukan pemasukan.");
        }

        return $this->terapkan($umkm, $jumlah, $tipe, $reference, $keterangan);
    }

    /** Kurangi saldo: penarikan dana ke rekening. */
    public function debit(
        Umkm $umkm,
        int $jumlah,
        TipeTransaksiDompet $tipe = TipeTransaksiDompet::Penarikan,
        ?Model $reference = null,
        ?string $keterangan = null,
    ): UmkmDompetTransaksi {
        if ($tipe->isPemasukan()) {
            throw AturanBisnisException::karena("Tipe transaksi \"{$tipe->label()}\" bukan pengeluaran.");
        }

        return $this->terapkan($umkm, $jumlah, $tipe, $reference, $keterangan);
    }

    public function cukup(Umkm $umkm, int $jumlah): bool
    {
        return $this->saldo($umkm) >= $jumlah;
    }

    /** @return Builder<UmkmDompetTransaksi> */
    public function mutasi(Umkm $umkm)
    {
        return UmkmDompetTransaksi::query()
            ->where('umkm_dompet_id', $this->untuk($umkm)->id)
            ->latest('id');
    }

    private function terapkan(
        Umkm $umkm,
        int $jumlah,
        TipeTransaksiDompet $tipe,
        ?Model $reference,
        ?string $keterangan,
    ): UmkmDompetTransaksi {
        $jumlah = abs($jumlah);

        if ($jumlah === 0) {
            throw AturanBisnisException::karena('Jumlah transaksi tidak boleh nol.');
        }

        return DB::transaction(function () use ($umkm, $jumlah, $tipe, $reference, $keterangan): UmkmDompetTransaksi {
            $this->untuk($umkm);

            $dompet = UmkmDompet::query()
                ->where('umkm_id', $umkm->id)
                ->lockForUpdate()
                ->firstOrFail();

            $saldoSebelum = $dompet->saldo;
            $saldoSesudah = $tipe->terapkan($saldoSebelum, $jumlah);

            if ($saldoSesudah < 0) {
                throw AturanBisnisException::karena(sprintf(
                    'Saldo UMKM tidak mencukupi. Saldo Rp %s, dibutuhkan Rp %s.',
                    number_format($saldoSebelum, 0, ',', '.'),
                    number_format($jumlah, 0, ',', '.'),
                ));
            }

            $dompet->update(['saldo' => $saldoSesudah]);

            return UmkmDompetTransaksi::create([
                'umkm_dompet_id' => $dompet->id,
                'tipe' => $tipe,
                'jumlah' => $jumlah,
                'saldo_sebelum' => $saldoSebelum,
                'saldo_sesudah' => $saldoSesudah,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'keterangan' => $keterangan,
            ]);
        });
    }
}
