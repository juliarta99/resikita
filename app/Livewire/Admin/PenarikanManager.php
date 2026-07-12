<?php

namespace App\Livewire\Admin;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Withdrawal;
use App\Services\Domain\WalletService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class PenarikanManager extends Component
{
    use WithPagination;

    public string $statusFilter = 'menunggu';

    public bool $showApprove = false;
    public ?int $approveId = null;

    public bool $showReject = false;
    public ?int $rejectId = null;
    public string $rejectCatatan = '';

    public bool $showFinish = false;
    public ?int $finishId = null;

    public array $statusLabels = [
        'menunggu'  => 'Menunggu',
        'disetujui' => 'Disetujui',
        'ditolak'   => 'Ditolak',
        'selesai'   => 'Selesai',
    ];

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function konfirmSetujui(int $id)
    {
        $this->approveId = $id;
        $this->showApprove = true;
    }

    public function setujui(WalletService $wallet)
    {
        $this->showApprove = false;
        $w = Withdrawal::with('user')->find($this->approveId);
        $this->approveId = null;

        if (! $w || $w->status !== 'menunggu') {
            return;
        }

        try {
            $wallet->debit($w->user, (float) $w->jumlah, 'penarikan', $w, 'Penarikan saldo #' . $w->id);
        } catch (InsufficientBalanceException $e) {
            session()->flash('err', 'Saldo nasabah tidak mencukupi untuk penarikan ini.');
            return;
        }

        $w->update([
            'status'      => 'disetujui',
            'approved_by' => Auth::id(),
        ]);

        session()->flash('ok', 'Penarikan disetujui dan saldo nasabah dipotong.');
    }

    public function konfirmTolak(int $id)
    {
        $this->rejectId = $id;
        $this->rejectCatatan = '';
        $this->showReject = true;
    }

    public function tolak()
    {
        $w = Withdrawal::find($this->rejectId);

        if ($w && $w->status === 'menunggu') {
            $w->update([
                'status'      => 'ditolak',
                'approved_by' => Auth::id(),
                'catatan'     => $this->rejectCatatan ?: null,
            ]);
            session()->flash('ok', 'Penarikan ditolak.');
        }

        $this->reset('showReject', 'rejectId', 'rejectCatatan');
    }

    public function konfirmSelesai(int $id)
    {
        $this->finishId = $id;
        $this->showFinish = true;
    }

    public function selesaikan()
    {
        $this->showFinish = false;
        $w = Withdrawal::find($this->finishId);
        $this->finishId = null;

        if ($w && $w->status === 'disetujui') {
            $w->update(['status' => 'selesai']);
            session()->flash('ok', 'Penarikan ditandai selesai.');
        }
    }

    public function render()
    {
        $query = Withdrawal::with(['user.wallet', 'approver'])->latest();

        if ($this->statusFilter !== 'semua') {
            $query->where('status', $this->statusFilter);
        }

        return view('livewire.admin.penarikan-manager', [
            'daftar' => $query->paginate(12),
        ]);
    }
}