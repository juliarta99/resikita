<?php

namespace App\Livewire\Umkm;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Domain\WalletService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.umkm')]
class OrderManager extends Component
{
    use WithPagination;

    public string $statusFilter = 'semua';

    public bool $showDetail = false;
    public ?int $detailId = null;

    public bool $showShip = false;
    public ?int $shipId = null;
    public string $kurir = '';
    public string $no_resi = '';

    public bool $showCancel = false;
    public ?int $cancelId = null;

    public array $statusLabels = [
        'menunggu_bayar' => 'Menunggu Bayar',
        'dibayar'        => 'Dibayar',
        'dikemas'        => 'Dikemas',
        'dikirim'        => 'Dikirim',
        'selesai'        => 'Selesai',
        'dibatalkan'     => 'Dibatalkan',
    ];

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    private function umkmId(): int
    {
        return Auth::user()->umkm_id;
    }

    private function find(int $id): ?Order
    {
        return Order::where('umkm_id', $this->umkmId())->find($id);
    }

    public function lihat(int $id)
    {
        $this->detailId = $id;
        $this->showDetail = true;
    }

    public function kemas(int $id)
    {
        $o = $this->find($id);
        if ($o && $o->status === 'dibayar') {
            $o->update(['status' => 'dikemas']);
            session()->flash('ok', 'Pesanan ditandai dikemas.');
        }
    }

    public function bukaKirim(int $id)
    {
        $this->shipId = $id;
        $this->kurir = '';
        $this->no_resi = '';
        $this->showShip = true;
    }

    public function kirim()
    {
        $this->validate([
            'kurir'   => 'required|string|max:50',
            'no_resi' => 'required|string|max:100',
        ]);

        $o = $this->find($this->shipId);
        if ($o && $o->status === 'dikemas') {
            $o->update(['status' => 'dikirim', 'kurir' => $this->kurir, 'no_resi' => $this->no_resi]);
            session()->flash('ok', 'Pesanan ditandai dikirim.');
        }

        $this->reset('showShip', 'shipId', 'kurir', 'no_resi');
    }

    public function selesai(int $id)
    {
        $o = $this->find($id);
        if ($o && $o->status === 'dikirim') {
            $o->update(['status' => 'selesai']);
            session()->flash('ok', 'Pesanan selesai.');
        }
    }

    public function konfirmBatal(int $id)
    {
        $this->cancelId = $id;
        $this->showCancel = true;
    }

    public function batalkan(WalletService $wallet)
    {
        $this->showCancel = false;
        $o = $this->find($this->cancelId);
        $this->cancelId = null;

        if (! $o || in_array($o->status, ['selesai', 'dibatalkan'])) {
            return;
        }

        // Refund bila sudah dibayar via saldo
        $payment = Payment::where('payable_type', $o->getMorphClass())
            ->where('payable_id', $o->id)
            ->where('status', 'paid')
            ->first();

        if ($payment && $o->metode_bayar === 'saldo') {
            $wallet->credit($o->user, (float) $o->total, 'refund', $o, 'Refund pesanan #' . $o->id);
        }

        $o->update(['status' => 'dibatalkan']);
        session()->flash('ok', 'Pesanan dibatalkan.' . ($payment && $o->metode_bayar === 'saldo' ? ' Saldo pembeli dikembalikan.' : ''));
    }

    public function render()
    {
        $query = Order::where('umkm_id', $this->umkmId())->with('user')->latest();
        if ($this->statusFilter !== 'semua') {
            $query->where('status', $this->statusFilter);
        }

        $detail = null;
        if ($this->showDetail && $this->detailId) {
            $detail = Order::where('umkm_id', $this->umkmId())->with('items', 'user')->find($this->detailId);
        }

        return view('livewire.umkm.order-manager', [
            'orders' => $query->paginate(12),
            'detail' => $detail,
        ]);
    }
}