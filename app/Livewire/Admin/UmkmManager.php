<?php

namespace App\Livewire\Admin;

use App\Models\BanjarDinas;
use App\Models\Order;
use App\Models\Umkm;
use App\Models\User;
use App\Services\Domain\AccountProvisioningService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class UmkmManager extends Component
{
    public bool $showForm = false;
    public bool $showDelete = false;
    public bool $showReject = false;
    public ?int $deleteId = null;
    public ?int $rejectId = null;

    public ?int $editingId = null;
    public ?int $officialId = null;

    public string $nama = '';
    public string $banjar_id = '';
    public string $deskripsi = '';
    public string $alamat = '';
    public string $no_hp = '';
    public string $lat = '';
    public string $lng = '';

    public string $admin_name = '';
    public string $admin_email = '';
    public string $admin_password = '';

    protected function rules(): array
    {
        return [
            'nama'           => 'required|string|max:255',
            'banjar_id'      => 'required|exists:banjar_dinas,id',
            'deskripsi'      => 'nullable|string|max:1000',
            'alamat'         => 'nullable|string|max:255',
            'no_hp'          => 'nullable|string|max:30',
            'lat'            => 'nullable|numeric|between:-90,90',
            'lng'            => 'nullable|numeric|between:-180,180',
            'admin_name'     => 'required|string|max:255',
            'admin_email'    => ['required', 'email', Rule::unique('users', 'email')->ignore($this->officialId)],
            'admin_password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:8'],
        ];
    }

    public function tambah()
    {
        $this->batal();
        $this->showForm = true;
        $this->dispatch('form-opened', lat: null, lng: null);
    }

    public function simpan(AccountProvisioningService $provisioning)
    {
        $data = $this->validate();

        $attrs = [
            'nama'      => $data['nama'],
            'banjar_id' => $data['banjar_id'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'alamat'    => $data['alamat'] ?? null,
            'no_hp'     => $data['no_hp'] ?? null,
            'lat'       => $data['lat'] ?: null,
            'lng'       => $data['lng'] ?: null,
        ];

        $payload = [
            'name'     => $data['admin_name'],
            'email'    => $data['admin_email'],
            'password' => $data['admin_password'] ?: null,
        ];

        if ($this->editingId) {
            Umkm::findOrFail($this->editingId)->update($attrs);
            $provisioning->updateAccount(User::findOrFail($this->officialId), $payload);
            $pesan = 'UMKM diperbarui.';
        } else {
            $umkm = Umkm::create($attrs + ['status' => 'aktif']);
            $provisioning->createAdminUmkm($umkm, $payload);
            $pesan = 'UMKM dan akun Admin UMKM berhasil dibuat.';
        }

        $this->batal();
        session()->flash('ok', $pesan);
    }

    public function edit(int $id)
    {
        $umkm = Umkm::findOrFail($id);
        $admin = User::role('umkm')->where('umkm_id', $umkm->id)->first();

        $this->editingId = $umkm->id;
        $this->nama = $umkm->nama;
        $this->banjar_id = (string) $umkm->banjar_id;
        $this->deskripsi = $umkm->deskripsi ?? '';
        $this->alamat = $umkm->alamat ?? '';
        $this->no_hp = $umkm->no_hp ?? '';
        $this->lat = $umkm->lat ? (string) $umkm->lat : '';
        $this->lng = $umkm->lng ? (string) $umkm->lng : '';
        $this->officialId = $admin?->id;
        $this->admin_name = $admin?->name ?? '';
        $this->admin_email = $admin?->email ?? '';
        $this->admin_password = '';

        $this->showForm = true;
        $this->dispatch('form-opened', lat: $this->lat ?: null, lng: $this->lng ?: null);
    }

    public function konfirmHapus(int $id)
    {
        $this->deleteId = $id;
        $this->showDelete = true;
    }

    public function hapus()
    {
        $id = $this->deleteId;
        $this->showDelete = false;

        if (! $id) {
            return;
        }

        if (Order::where('umkm_id', $id)->exists()) {
            session()->flash('err', 'Tidak bisa dihapus: UMKM ini sudah memiliki pesanan.');
            return;
        }

        User::where('umkm_id', $id)->delete();
        Umkm::findOrFail($id)->delete();

        $this->deleteId = null;
        session()->flash('ok', 'UMKM dihapus.');
    }

    public function setujui(int $id, AccountProvisioningService $provisioning)
    {
        $provisioning->approveUmkm(Umkm::findOrFail($id));
        session()->flash('ok', 'Pendaftaran UMKM disetujui.');
    }

    public function konfirmTolak(int $id)
    {
        $this->rejectId = $id;
        $this->showReject = true;
    }

    public function tolak(AccountProvisioningService $provisioning)
    {
        $this->showReject = false;

        if ($this->rejectId) {
            $provisioning->rejectUmkm(Umkm::findOrFail($this->rejectId));
            $this->rejectId = null;
            session()->flash('ok', 'Pendaftaran UMKM ditolak.');
        }
    }

    public function batal()
    {
        $this->reset('showForm', 'editingId', 'officialId', 'nama', 'banjar_id', 'deskripsi', 'alamat', 'no_hp', 'lat', 'lng', 'admin_name', 'admin_email', 'admin_password');
    }

    public function render()
    {
        return view('livewire.admin.umkm-manager', [
            'banjarList' => BanjarDinas::with('kelurahan.kecamatan')->orderBy('nama')->get(),
            'pending'    => Umkm::where('status', 'menunggu')->with('admins')->latest()->get(),
            'daftar'     => Umkm::whereIn('status', ['aktif', 'ditolak'])->latest()->get(),
        ]);
    }
}