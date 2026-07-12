<?php

namespace App\Livewire\Petugas;

use App\Models\User;
use App\Models\WasteDeposit;
use App\Models\WastePrice;
use App\Services\Domain\WalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.banksampah')]
class SetorSampah extends Component
{
    public string $kode = '';

    public bool $showError = false;
    public string $errorMsg = '';

    public ?int $nasabahId = null;
    public string $nasabahNama = '';
    public string $nasabahKode = '';
    public float $nasabahSaldo = 0;

    public array $items = [];

    public function mount()
    {
        $this->items = [['waste_price_id' => '', 'berat' => '']];
    }

    public function cariNasabah()
    {
        $kode = trim($this->kode);
        $this->resetNasabah();

        if ($kode === '') {
            return;
        }

        $nasabah = User::role('masyarakat')
            ->where(fn ($q) => $q->where('kode_qr', $kode)->orWhere('nik', $kode))
            ->first();

        if (! $nasabah) {
            $this->kode = '';
            $this->errorMsg = 'Kode QR atau NIK tidak dikenali. Pastikan Anda memindai QR nasabah yang benar, lalu coba lagi.';
            $this->showError = true;
            return;
        }

        $this->nasabahId = $nasabah->id;
        $this->nasabahNama = $nasabah->name;
        $this->nasabahKode = $nasabah->kode_qr ?? '';
        $this->nasabahSaldo = (float) (app(WalletService::class)->saldo($nasabah));

        if (empty($this->items)) {
            $this->items = [['waste_price_id' => '', 'berat' => '']];
        }
    }

    public function gantiNasabah()
    {
        $this->resetNasabah();
        $this->kode = '';
        $this->items = [['waste_price_id' => '', 'berat' => '']];
    }

    public function tambahItem()
    {
        $this->items[] = ['waste_price_id' => '', 'berat' => ''];
    }

    public function hapusItem(int $i)
    {
        unset($this->items[$i]);
        $this->items = array_values($this->items);

        if (empty($this->items)) {
            $this->items = [['waste_price_id' => '', 'berat' => '']];
        }
    }

    public function simpan(WalletService $wallet)
    {
        $this->validate([
            'nasabahId'              => 'required|integer',
            'items'                  => 'required|array|min:1',
            'items.*.waste_price_id' => 'required|exists:waste_prices,id',
            'items.*.berat'          => 'required|numeric|gt:0',
        ], [], [
            'items.*.waste_price_id' => 'jenis sampah',
            'items.*.berat'          => 'berat',
        ]);

        $petugas = Auth::user();

        if (! $petugas->bank_sampah_id) {
            session()->flash('err', 'Akun Anda belum terhubung ke bank sampah.');
            return;
        }

        $nasabah = User::findOrFail($this->nasabahId);

        $prices = WastePrice::whereIn('id', collect($this->items)->pluck('waste_price_id'))
            ->get()->keyBy('id');

        $rows = [];
        $totalBerat = 0;
        $totalNilai = 0;

        foreach ($this->items as $it) {
            $price = $prices[$it['waste_price_id']] ?? null;
            $berat = (float) $it['berat'];
            if (! $price || $berat <= 0) {
                continue;
            }
            $subtotal = round($berat * (float) $price->harga_per_kg, 2);
            $rows[] = [
                'waste_price_id' => $price->id,
                'berat'          => $berat,
                'harga_snapshot' => $price->harga_per_kg,
                'subtotal'       => $subtotal,
            ];
            $totalBerat += $berat;
            $totalNilai += $subtotal;
        }

        if (empty($rows)) {
            session()->flash('err', 'Rincian setoran belum valid.');
            return;
        }

        DB::transaction(function () use ($wallet, $petugas, $nasabah, $rows, $totalBerat, $totalNilai) {
            $deposit = WasteDeposit::create([
                'bank_sampah_id' => $petugas->bank_sampah_id,
                'petugas_id'     => $petugas->id,
                'nasabah_id'     => $nasabah->id,
                'total_berat'    => $totalBerat,
                'total_nilai'    => $totalNilai,
                'status'         => 'selesai',
            ]);

            $deposit->items()->createMany($rows);

            $wallet->credit(
                $nasabah,
                $totalNilai,
                'setor',
                $deposit,
                'Setor sampah di ' . ($petugas->bankSampah?->nama ?? 'bank sampah')
            );
        });

        $nama = $nasabah->name;
        $nilai = number_format($totalNilai, 0, ',', '.');

        $this->reset('kode', 'nasabahId', 'nasabahNama', 'nasabahKode', 'nasabahSaldo');
        $this->items = [['waste_price_id' => '', 'berat' => '']];

        session()->flash('ok', "Setoran berhasil. Saldo {$nama} bertambah Rp {$nilai}.");
    }

    protected function resetNasabah()
    {
        $this->reset('nasabahId', 'nasabahNama', 'nasabahKode', 'nasabahSaldo');
    }

    public function render()
    {
        $prices = WastePrice::where('is_active', true)->orderBy('jenis_sampah')->get();
        $priceMap = $prices->keyBy('id');

        $totalBerat = 0;
        $totalNilai = 0;
        foreach ($this->items as $it) {
            $p = $priceMap[$it['waste_price_id'] ?? null] ?? null;
            $berat = (float) ($it['berat'] ?? 0);
            if ($p && $berat > 0) {
                $totalBerat += $berat;
                $totalNilai += $berat * (float) $p->harga_per_kg;
            }
        }

        $riwayat = collect();
        if (Auth::user()->bank_sampah_id) {
            $riwayat = WasteDeposit::with('nasabah')
                ->where('bank_sampah_id', Auth::user()->bank_sampah_id)
                ->whereDate('created_at', today())
                ->latest()->take(5)->get();
        }

        return view('livewire.petugas.setor-sampah', [
            'prices'     => $prices,
            'totalBerat' => $totalBerat,
            'totalNilai' => $totalNilai,
            'riwayat'    => $riwayat,
        ]);
    }
}