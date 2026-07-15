<?php

namespace App\Livewire\Umkm;

use App\Models\UmkmWalletTransaction;
use App\Models\UmkmWithdrawal;
use App\Services\Domain\UmkmWalletService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.umkm')]
class Saldo extends Component
{
    use WithPagination;

    public bool $showTarik = false;
    public $jumlah = '';
    public string $nama_bank = '';
    public string $no_rekening = '';
    public string $atas_nama = '';

    private function umkmId(): int
    {
        return (int) Auth::user()->umkm_id;
    }

    public function bukaTarik()
    {
        $this->reset('jumlah', 'nama_bank', 'no_rekening', 'atas_nama');
        $this->resetValidation();
        $this->showTarik = true;
    }

    public function ajukan()
    {
        $wallet = app(UmkmWalletService::class);

        $this->validate([
            'jumlah'      => 'required|numeric|min:50000',
            'nama_bank'   => 'required|string|max:60',
            'no_rekening' => 'required|string|max:60',
            'atas_nama'   => 'required|string|max:100',
        ], [], [
            'nama_bank' => 'nama bank', 'no_rekening' => 'nomor rekening', 'atas_nama' => 'atas nama',
        ]);

        $umkmId = $this->umkmId();

        if ($wallet->saldo($umkmId) < (float) $this->jumlah) {
            $this->addError('jumlah', 'Saldo tidak mencukupi.');
            return;
        }

        // Cegah pengajuan ganda saat masih menunggu
        if (UmkmWithdrawal::where('umkm_id', $umkmId)->where('status', 'menunggu')->exists()) {
            $this->addError('jumlah', 'Masih ada penarikan yang menunggu validasi.');
            return;
        }

        UmkmWithdrawal::create([
            'umkm_id'     => $umkmId,
            'jumlah'      => (float) $this->jumlah,
            'nama_bank'   => $this->nama_bank,
            'no_rekening' => $this->no_rekening,
            'atas_nama'   => $this->atas_nama,
            'status'      => 'menunggu',
        ]);

        $this->reset('showTarik', 'jumlah', 'nama_bank', 'no_rekening', 'atas_nama');
        session()->flash('ok', 'Permintaan penarikan diajukan. Menunggu validasi admin.');
    }

    public function render()
    {
        $wallet = app(UmkmWalletService::class);
        $umkmId = $this->umkmId();

        return view('livewire.umkm.saldo', [
            'saldo'     => $wallet->saldo($umkmId),
            'transaksi' => UmkmWalletTransaction::whereHas('wallet', fn ($q) => $q->where('umkm_id', $umkmId))
                ->latest()->paginate(10),
            'penarikan' => UmkmWithdrawal::where('umkm_id', $umkmId)->latest()->take(10)->get(),
        ]);
    }
}