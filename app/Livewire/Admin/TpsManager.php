<?php

namespace App\Livewire\Admin;

use App\Models\BanjarDinas;
use App\Models\Tps;
use App\Models\User;
use App\Services\Domain\AccountProvisioningService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class TpsManager extends Component
{
    public bool $showForm = false;
    public bool $showDelete = false;
    public ?int $deleteId = null;

    public ?int $editingId = null;
    public ?int $officialId = null;

    public string $nama = '';
    public string $banjar_id = '';
    public string $alamat = '';
    public string $no_hp = '';
    public bool $is_berbayar = false;
    public string $tarif = '';
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
            'alamat'         => 'nullable|string|max:255',
            'no_hp'          => 'nullable|string|max:30',
            'is_berbayar'    => 'boolean',
            'tarif'          => 'nullable|numeric|min:0',
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
            'nama'        => $data['nama'],
            'banjar_id'   => $data['banjar_id'],
            'alamat'      => $data['alamat'] ?? null,
            'no_hp'       => $data['no_hp'] ?? null,
            'is_berbayar' => $data['is_berbayar'],
            'tarif'       => $data['is_berbayar'] ? ($data['tarif'] ?: 0) : null,
            'lat'         => $data['lat'] ?: null,
            'lng'         => $data['lng'] ?: null,
        ];

        $payload = [
            'name'     => $data['admin_name'],
            'email'    => $data['admin_email'],
            'password' => $data['admin_password'] ?: null,
        ];

        if ($this->editingId) {
            Tps::findOrFail($this->editingId)->update($attrs);
            $provisioning->updateAccount(User::findOrFail($this->officialId), $payload);
            $pesan = 'TPS diperbarui.';
        } else {
            $tps = Tps::create($attrs);
            $provisioning->createAdminTps($tps, $payload);
            $pesan = 'TPS dan akun Admin TPS berhasil dibuat.';
        }

        $this->batal();
        session()->flash('ok', $pesan);
    }

    public function edit(int $id)
    {
        $tps = Tps::findOrFail($id);
        $admin = User::role('admin_tps')->where('tps_id', $tps->id)->first();

        $this->editingId = $tps->id;
        $this->nama = $tps->nama;
        $this->banjar_id = (string) $tps->banjar_id;
        $this->alamat = $tps->alamat ?? '';
        $this->no_hp = $tps->no_hp ?? '';
        $this->is_berbayar = (bool) $tps->is_berbayar;
        $this->tarif = $tps->tarif ? (string) $tps->tarif : '';
        $this->lat = $tps->lat ? (string) $tps->lat : '';
        $this->lng = $tps->lng ? (string) $tps->lng : '';
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

        User::where('tps_id', $id)->delete();
        Tps::findOrFail($id)->delete();

        $this->deleteId = null;
        session()->flash('ok', 'TPS dihapus.');
    }

    public function batal()
    {
        $this->reset('showForm', 'editingId', 'officialId', 'nama', 'banjar_id', 'alamat', 'no_hp', 'is_berbayar', 'tarif', 'lat', 'lng', 'admin_name', 'admin_email', 'admin_password');
    }

    public function render()
    {
        return view('livewire.admin.tps-manager', [
            'banjarList' => BanjarDinas::with('kelurahan.kecamatan')->orderBy('nama')->get(),
            'daftar'     => Tps::with('banjarDinas')->latest()->get(),
        ]);
    }
}