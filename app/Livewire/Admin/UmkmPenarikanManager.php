<?php

namespace App\Livewire\Admin;

use App\Exceptions\InsufficientBalanceException;
use App\Models\UmkmWithdrawal;
use App\Services\Domain\UmkmWalletService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class UmkmPenarikanManager extends Component
{
    use WithPagination;

    public string $statusFilter = 'menunggu';

    public bool $showReject = false;
    public ?int $rejectId = null;
    public string $rejectCatatan = '';

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function setujui(int $id)
    {
        $wallet = app(UmkmWalletService::class);
        $w = UmkmWithdrawal::find($id);

        if (! $w || $w->status !== 'menunggu') {
            return;
        }

        try {
            $wallet->debit($w->umkm_id, (float) $w->jumlah, 'penarikan', $w, 'Penarikan saldo UMKM #' . $w->id);
        } catch (InsufficientBalanceException $e) {
            session()->flash('err', 'Saldo UMKM tidak mencukupi untuk penarikan ini.');
            return;
        }

        $w->update(['status' => 'disetujui', 'approved_by' => Auth::id()]);
        session()->flash('ok', 'Penarikan UMKM disetujui dan saldo dipotong.');
    }

    public function konfirmTolak(int $id)
    {
        $this->rejectId = $id;
        $this->rejectCatatan = '';
        $this->showReject = true;
    }

    public function tolak()
    {
        $w = UmkmWithdrawal::find($this->rejectId);

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

    public function render()
    {
        $query = UmkmWithdrawal::with(['umkm', 'approver'])->latest();

        if ($this->statusFilter !== 'semua') {
            $query->where('status', $this->statusFilter);
        }

        return view('livewire.admin.umkm-penarikan-manager', [
            'daftar' => $query->paginate(12),
        ]);
    }
}