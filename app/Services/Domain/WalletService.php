<?php

namespace App\Services\Domain;

use App\Exceptions\InsufficientBalanceException;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya pintu perubahan saldo. Semua mutasi lewat transaksi DB
 * dengan penguncian baris, dan selalu mencatat wallet_transactions
 * beserta saldo_after. Kolom wallets.saldo tidak pernah diubah manual.
 */
class WalletService
{
    public function walletFor(User $user): Wallet
    {
        return Wallet::firstOrCreate(['user_id' => $user->id], ['saldo' => 0]);
    }

    public function saldo(User $user): float
    {
        return (float) ($this->walletFor($user)->saldo);
    }

    /**
     * Tambah saldo (setor / refund).
     */
    public function credit(User $user, float $amount, string $tipe = 'setor', ?Model $reference = null, ?string $keterangan = null): WalletTransaction
    {
        return $this->apply($user, abs($amount), $tipe, $reference, $keterangan);
    }

    /**
     * Kurangi saldo (belanja / penarikan). Melempar jika saldo kurang.
     */
    public function debit(User $user, float $amount, string $tipe = 'belanja', ?Model $reference = null, ?string $keterangan = null): WalletTransaction
    {
        return $this->apply($user, -abs($amount), $tipe, $reference, $keterangan);
    }

    protected function apply(User $user, float $delta, string $tipe, ?Model $reference, ?string $keterangan): WalletTransaction
    {
        return DB::transaction(function () use ($user, $delta, $tipe, $reference, $keterangan) {
            $wallet = Wallet::firstOrCreate(['user_id' => $user->id], ['saldo' => 0]);
            $wallet = Wallet::whereKey($wallet->getKey())->lockForUpdate()->first();

            $saldoBaru = (float) $wallet->saldo + $delta;

            if ($saldoBaru < 0) {
                throw new InsufficientBalanceException('Saldo tidak mencukupi.');
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