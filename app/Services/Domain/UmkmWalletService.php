<?php

namespace App\Services\Domain;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Umkm;
use App\Models\UmkmWallet;
use App\Models\UmkmWalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Pintu tunggal perubahan saldo UMKM. Semua mutasi tercatat + saldo_after.
 */
class UmkmWalletService
{
    public function walletFor(int $umkmId): UmkmWallet
    {
        return UmkmWallet::firstOrCreate(['umkm_id' => $umkmId], ['saldo' => 0]);
    }

    public function saldo(int $umkmId): float
    {
        return (float) $this->walletFor($umkmId)->saldo;
    }

    /** Tambah saldo (penjualan / refund masuk). */
    public function credit(int $umkmId, float $amount, string $tipe = 'penjualan', ?Model $reference = null, ?string $keterangan = null): UmkmWalletTransaction
    {
        return $this->apply($umkmId, abs($amount), $tipe, $reference, $keterangan);
    }

    /** Kurangi saldo (penarikan). Melempar jika saldo kurang. */
    public function debit(int $umkmId, float $amount, string $tipe = 'penarikan', ?Model $reference = null, ?string $keterangan = null): UmkmWalletTransaction
    {
        return $this->apply($umkmId, -abs($amount), $tipe, $reference, $keterangan);
    }

    protected function apply(int $umkmId, float $delta, string $tipe, ?Model $reference, ?string $keterangan): UmkmWalletTransaction
    {
        return DB::transaction(function () use ($umkmId, $delta, $tipe, $reference, $keterangan) {
            $wallet = UmkmWallet::firstOrCreate(['umkm_id' => $umkmId], ['saldo' => 0]);
            $wallet = UmkmWallet::whereKey($wallet->getKey())->lockForUpdate()->first();

            $saldoBaru = (float) $wallet->saldo + $delta;
            if ($saldoBaru < 0) {
                throw new InsufficientBalanceException('Saldo UMKM tidak mencukupi.');
            }

            $wallet->update(['saldo' => $saldoBaru]);

            return $wallet->transactions()->create([
                'tipe'           => $tipe,
                'jumlah'         => $delta,
                'saldo_after'    => $saldoBaru,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id'   => $reference?->getKey(),
                'keterangan'     => $keterangan,
            ]);
        });
    }
}