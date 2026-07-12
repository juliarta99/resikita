<?php

namespace App\Livewire\Tps;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Tps;
use App\Models\TpsMember;
use App\Models\TpsSubscription;
use App\Services\Domain\WalletService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.tps')]
class IuranManager extends Component
{
    use WithPagination;

    public string $periodeFilter = '';
    public string $statusFilter = 'semua';

    public bool $showPay = false;
    public ?int $payId = null;

    public bool $showDelete = false;
    public ?int $deleteId = null;

    public function mount()
    {
        $this->periodeFilter = now()->format('Y-m');
    }

    private function tpsId(): int
    {
        return Auth::user()->tps_id;
    }

    public function updatingPeriodeFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function buatTagihanBulanIni()
    {
        $tps = Tps::find($this->tpsId());
        if (! $tps || ! $tps->is_berbayar) {
            session()->flash('err', 'TPS ini tidak berbayar, tidak ada tagihan.');
            return;
        }

        $periode = now()->format('Y-m');
        $anggota = TpsMember::where('tps_id', $tps->id)->where('status', 'aktif')->get();
        $dibuat = 0;

        foreach ($anggota as $m) {
            $ada = TpsSubscription::where('tps_member_id', $m->id)->where('periode', $periode)->exists();
            if (! $ada) {
                TpsSubscription::create([
                    'tps_member_id' => $m->id,
                    'periode'       => $periode,
                    'jumlah'        => $tps->tarif,
                    'status'        => 'menunggu',
                ]);
                $dibuat++;
            }
        }

        session()->flash('ok', "Tagihan {$periode} dibuat untuk {$dibuat} nasabah.");
    }

    public function konfirmBayarSaldo(int $id)
    {
        $this->payId = $id;
        $this->showPay = true;
    }

    public function bayarSaldo(WalletService $wallet)
    {
        $this->showPay = false;
        $sub = TpsSubscription::with('member.user')
            ->whereHas('member', fn ($q) => $q->where('tps_id', $this->tpsId()))
            ->find($this->payId);
        $this->payId = null;

        if (! $sub || $sub->status !== 'menunggu' || ! $sub->member?->user) {
            return;
        }

        try {
            $wallet->debit($sub->member->user, (float) $sub->jumlah, 'belanja', $sub, 'Iuran TPS ' . $sub->periode);
        } catch (InsufficientBalanceException $e) {
            session()->flash('err', 'Saldo nasabah tidak mencukupi.');
            return;
        }

        $sub->update(['status' => 'lunas', 'metode_bayar' => 'saldo', 'paid_at' => now()]);
        session()->flash('ok', 'Iuran dibayar dari saldo nasabah.');
    }

    public function tandaiLunas(int $id)
    {
        $sub = TpsSubscription::whereHas('member', fn ($q) => $q->where('tps_id', $this->tpsId()))->find($id);
        if ($sub && $sub->status === 'menunggu') {
            $sub->update(['status' => 'lunas', 'metode_bayar' => null, 'paid_at' => now()]);
            session()->flash('ok', 'Iuran ditandai lunas (tunai).');
        }
    }

    public function konfirmHapus(int $id)
    {
        $this->deleteId = $id;
        $this->showDelete = true;
    }

    public function hapus()
    {
        $this->showDelete = false;
        $sub = TpsSubscription::whereHas('member', fn ($q) => $q->where('tps_id', $this->tpsId()))->find($this->deleteId);
        $this->deleteId = null;
        if ($sub && $sub->status === 'menunggu') {
            $sub->delete();
            session()->flash('ok', 'Tagihan dihapus.');
        }
    }

    public function render()
    {
        $tps = Tps::find($this->tpsId());

        $query = TpsSubscription::with('member.user')
            ->whereHas('member', fn ($q) => $q->where('tps_id', $this->tpsId()))
            ->latest();

        if ($this->periodeFilter !== '') {
            $query->where('periode', $this->periodeFilter);
        }
        if ($this->statusFilter !== 'semua') {
            $query->where('status', $this->statusFilter);
        }

        $periodeList = TpsSubscription::whereHas('member', fn ($q) => $q->where('tps_id', $this->tpsId()))
            ->distinct()->orderByDesc('periode')->pluck('periode');

        return view('livewire.tps.iuran-manager', [
            'tps'         => $tps,
            'iuran'       => $query->paginate(15),
            'periodeList' => $periodeList,
        ]);
    }
}