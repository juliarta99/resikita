<?php

namespace App\Livewire\BankSampah;

use App\Models\BankSampah;
use App\Models\User;
use App\Models\WasteDeposit;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.banksampah')]
class Dashboard extends Component
{
    public function render()
    {
        $bsId = Auth::user()->bank_sampah_id;
        $bs = BankSampah::find($bsId);

        $today = WasteDeposit::where('bank_sampah_id', $bsId)->whereDate('created_at', today());
        $month = WasteDeposit::where('bank_sampah_id', $bsId)
            ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);

        // Tren 7 hari terakhir
        $trenLabels = [];
        $trenData = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i);
            $trenLabels[] = $d->format('d M');
            $trenData[] = (float) WasteDeposit::where('bank_sampah_id', $bsId)
                ->whereDate('created_at', $d->toDateString())
                ->sum('total_nilai');
        }

        return view('livewire.bank-sampah.dashboard', [
            'bs'           => $bs,
            'todayCount'   => (clone $today)->count(),
            'todayNilai'   => (float) (clone $today)->sum('total_nilai'),
            'monthNilai'   => (float) (clone $month)->sum('total_nilai'),
            'monthBerat'   => (float) (clone $month)->sum('total_berat'),
            'nasabahCount' => WasteDeposit::where('bank_sampah_id', $bsId)->distinct('nasabah_id')->count('nasabah_id'),
            'petugasCount' => User::role('petugas_bank_sampah')->where('bank_sampah_id', $bsId)->count(),
            'recent'       => WasteDeposit::with('nasabah', 'petugas')->where('bank_sampah_id', $bsId)->latest()->take(8)->get(),
            'trenLabels'   => $trenLabels,
            'trenData'     => $trenData,
        ]);
    }
}