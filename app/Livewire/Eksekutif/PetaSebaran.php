<?php

namespace App\Livewire\Eksekutif;

use App\Models\BankSampah;
use App\Models\Product;
use App\Models\Report;
use App\Models\Tps;
use App\Models\TpsMember;
use App\Models\TpsSubscription;
use App\Models\Umkm;
use App\Models\WasteDeposit;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.eksekutif')]
class PetaSebaran extends Component
{
    use ResolvesScope;

    public string $dari = '';
    public string $sampai = '';
    public array $jenis = ['tps', 'bank_sampah', 'umkm', 'laporan'];
    public array $markers = [];

    public bool $showDetail = false;
    public string $detailType = '';
    public ?int $detailId = null;

    public function mount()
    {
        $this->dari = now()->startOfYear()->toDateString();
        $this->sampai = now()->toDateString();
        $this->markers = $this->computeMarkers();
    }

    private function computeMarkers(): array
    {
        $scope = $this->scope();
        $banjarIds = $scope['banjarIds'];
        $out = [];

        if (in_array('tps', $this->jenis, true)) {
            foreach (Tps::whereIn('banjar_id', $banjarIds)->whereNotNull('lat')->whereNotNull('lng')->get() as $t) {
                $out[] = ['t' => 'tps', 'id' => $t->id, 'n' => $t->nama, 'lat' => (float) $t->lat, 'lng' => (float) $t->lng];
            }
        }
        if (in_array('bank_sampah', $this->jenis, true)) {
            foreach (BankSampah::whereIn('banjar_id', $banjarIds)->whereNotNull('lat')->whereNotNull('lng')->get() as $b) {
                $out[] = ['t' => 'bank_sampah', 'id' => $b->id, 'n' => $b->nama, 'lat' => (float) $b->lat, 'lng' => (float) $b->lng];
            }
        }
        if (in_array('umkm', $this->jenis, true)) {
            foreach (Umkm::whereIn('banjar_id', $banjarIds)->where('status', 'aktif')->whereNotNull('lat')->whereNotNull('lng')->get() as $m) {
                $out[] = ['t' => 'umkm', 'id' => $m->id, 'n' => $m->nama, 'lat' => (float) $m->lat, 'lng' => (float) $m->lng];
            }
        }
        if (in_array('laporan', $this->jenis, true)) {
            $s = Carbon::parse($this->dari)->startOfDay();
            $e = Carbon::parse($this->sampai)->endOfDay();
            foreach (Report::whereIn('banjar_id', $banjarIds)->whereBetween('created_at', [$s, $e])->whereNotNull('lat')->whereNotNull('lng')->get() as $r) {
                $out[] = ['t' => 'laporan', 'id' => $r->id, 'n' => $r->judul, 'lat' => (float) $r->lat, 'lng' => (float) $r->lng];
            }
        }

        return $out;
    }

    public function terapkan()
    {
        $this->markers = $this->computeMarkers();
        $this->dispatch('peta-updated', markers: $this->markers);
    }

    public function lihatDetail(string $t, int $id)
    {
        // Pastikan entitas berada dalam cakupan wilayah pengguna
        $banjarIds = $this->scope()['banjarIds'];
        $model = match ($t) {
            'tps'         => Tps::whereIn('banjar_id', $banjarIds)->find($id),
            'bank_sampah' => BankSampah::whereIn('banjar_id', $banjarIds)->find($id),
            'umkm'        => Umkm::whereIn('banjar_id', $banjarIds)->find($id),
            'laporan'     => Report::whereIn('banjar_id', $banjarIds)->find($id),
            default       => null,
        };

        if (! $model) {
            return;
        }

        $this->detailType = $t;
        $this->detailId = $id;
        $this->showDetail = true;
    }

    private function buildDetail(): ?array
    {
        if (! $this->showDetail || ! $this->detailId) {
            return null;
        }

        return match ($this->detailType) {
            'laporan'     => $this->detailLaporan(),
            'umkm'        => $this->detailUmkm(),
            'tps'         => $this->detailTps(),
            'bank_sampah' => $this->detailBankSampah(),
            default       => null,
        };
    }

    private function detailLaporan(): ?array
    {
        $r = Report::with('kategori', 'banjarDinas', 'images')->find($this->detailId);
        if (! $r) {
            return null;
        }

        return [
            'jenis'     => 'laporan',
            'judul'     => $r->judul,
            'kategori'  => $r->kategori?->nama,
            'status'    => $r->status,
            'alamat'    => trim(($r->alamat ?? '') . ' ' . ($r->banjarDinas ? '(' . $r->banjarDinas->nama . ')' : '')),
            'deskripsi' => $r->deskripsi,
            'tanggal'   => $r->created_at?->format('d M Y H:i'),
            'foto'      => $r->foto,
            'images'    => $r->images,
        ];
    }

    private function detailUmkm(): ?array
    {
        $u = Umkm::find($this->detailId);
        if (! $u) {
            return null;
        }

        $produk = Product::where('umkm_id', $u->id)->latest()->get();

        return [
            'jenis'       => 'umkm',
            'nama'        => $u->nama,
            'deskripsi'   => $u->deskripsi,
            'alamat'      => $u->alamat,
            'no_hp'       => $u->no_hp,
            'foto'        => $u->foto,
            'produkTotal' => $produk->count(),
            'produkAktif' => $produk->where('is_active', true)->count(),
            'produk'      => $produk,
        ];
    }

    private function detailTps(): ?array
    {
        $t = Tps::find($this->detailId);
        if (! $t) {
            return null;
        }

        $memberIds = TpsMember::where('tps_id', $t->id)->pluck('id');
        $periode = now()->format('Y-m');
        $subBulan = TpsSubscription::whereIn('tps_member_id', $memberIds)->where('periode', $periode);

        return [
            'jenis'           => 'tps',
            'nama'            => $t->nama,
            'alamat'          => $t->alamat,
            'no_hp'           => $t->no_hp,
            'foto'            => $t->foto,
            'berbayar'        => (bool) $t->is_berbayar,
            'tarif'           => (float) $t->tarif,
            'nasabahAktif'    => TpsMember::where('tps_id', $t->id)->where('status', 'aktif')->count(),
            'nasabahTotal'    => TpsMember::where('tps_id', $t->id)->count(),
            'iuranBln'        => (float) (clone $subBulan)->where('status', 'lunas')->sum('jumlah'),
            'tagihanMenunggu' => (clone $subBulan)->where('status', 'menunggu')->count(),
        ];
    }

    private function detailBankSampah(): ?array
    {
        $b = BankSampah::find($this->detailId);
        if (! $b) {
            return null;
        }

        $dep = WasteDeposit::where('bank_sampah_id', $b->id);
        $depBln = (clone $dep)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);

        return [
            'jenis'          => 'bank_sampah',
            'nama'           => $b->nama,
            'alamat'         => $b->alamat,
            'no_hp'          => $b->no_hp,
            'foto'           => $b->foto,
            'nasabah'        => (clone $dep)->distinct('nasabah_id')->count('nasabah_id'),
            'transaksiBln'   => (clone $depBln)->count(),
            'beratBln'       => (float) (clone $depBln)->sum('total_berat'),
            'nilaiBln'       => (float) (clone $depBln)->sum('total_nilai'),
            'transaksiTotal' => (clone $dep)->count(),
            'nilaiTotal'     => (float) (clone $dep)->sum('total_nilai'),
        ];
    }

    public function render()
    {
        $scope = $this->scope();
        $banjarIds = $scope['banjarIds'];
        $s = Carbon::parse($this->dari)->startOfDay();
        $e = Carbon::parse($this->sampai)->endOfDay();

        return view('livewire.eksekutif.peta-sebaran', [
            'scopeLabel' => $scope['label'],
            'jumlah' => [
                'tps'         => Tps::whereIn('banjar_id', $banjarIds)->count(),
                'bank_sampah' => BankSampah::whereIn('banjar_id', $banjarIds)->count(),
                'umkm'        => Umkm::whereIn('banjar_id', $banjarIds)->where('status', 'aktif')->count(),
                'laporan'     => Report::whereIn('banjar_id', $banjarIds)->whereBetween('created_at', [$s, $e])->count(),
            ],
            'detail' => $this->buildDetail(),
        ]);
    }
}