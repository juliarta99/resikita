<?php

namespace App\Livewire\Tps;

use App\Models\TpsMember;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.tps')]
class MemberManager extends Component
{
    public bool $showAdd = false;
    public string $cari = '';
    public ?int $foundId = null;
    public string $foundNama = '';
    public string $foundKode = '';
    public bool $sudahAnggota = false;

    public bool $showDelete = false;
    public ?int $deleteId = null;

    private function tpsId(): int
    {
        return Auth::user()->tps_id;
    }

    public function bukaTambah()
    {
        $this->reset('cari', 'foundId', 'foundNama', 'foundKode', 'sudahAnggota');
        $this->showAdd = true;
    }

    public function cariNasabah()
    {
        $this->reset('foundId', 'foundNama', 'foundKode', 'sudahAnggota');
        $kode = trim($this->cari);
        if ($kode === '') {
            return;
        }

        $user = User::role('masyarakat')
            ->where(fn ($q) => $q->where('kode_qr', $kode)->orWhere('nik', $kode)->orWhere('name', 'like', "%{$kode}%"))
            ->first();

        if (! $user) {
            session()->flash('err', 'Nasabah tidak ditemukan.');
            return;
        }

        $this->foundId = $user->id;
        $this->foundNama = $user->name;
        $this->foundKode = $user->kode_qr ?? '';
        $this->sudahAnggota = TpsMember::where('tps_id', $this->tpsId())->where('user_id', $user->id)->exists();
    }

    public function tambahAnggota()
    {
        if (! $this->foundId || $this->sudahAnggota) {
            return;
        }

        TpsMember::firstOrCreate(
            ['tps_id' => $this->tpsId(), 'user_id' => $this->foundId],
            ['status' => 'aktif', 'joined_at' => now()]
        );

        $this->reset('showAdd', 'cari', 'foundId', 'foundNama', 'foundKode', 'sudahAnggota');
        session()->flash('ok', 'Nasabah ditambahkan.');
    }

    public function toggleStatus(int $id)
    {
        $m = TpsMember::where('tps_id', $this->tpsId())->find($id);
        if ($m) {
            $m->update(['status' => $m->status === 'aktif' ? 'nonaktif' : 'aktif']);
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
        $m = TpsMember::where('tps_id', $this->tpsId())->find($this->deleteId);
        $this->deleteId = null;
        if ($m) {
            $m->delete();
            session()->flash('ok', 'Nasabah dikeluarkan dari TPS.');
        }
    }

    public function render()
    {
        $members = TpsMember::where('tps_id', $this->tpsId())
            ->with('user')
            ->withCount(['subscriptions as menunggu_count' => fn ($q) => $q->where('status', 'menunggu')])
            ->latest()
            ->paginate(15);

        return view('livewire.tps.member-manager', ['members' => $members]);
    }
}