<?php

namespace App\Livewire\Admin;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\User;
use App\Services\Domain\AccountProvisioningService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class KecamatanManager extends Component
{
    public bool $showForm = false;
    public bool $showDelete = false;
    public ?int $deleteId = null;

    public ?int $editingId = null;
    public ?int $officialId = null;

    public string $nama = '';
    public string $camat_name = '';
    public string $camat_email = '';
    public string $camat_nip = '';
    public string $camat_jk = '';
    public string $camat_password = '';

    protected function rules(): array
    {
        return [
            'nama'           => 'required|string|max:255',
            'camat_name'     => 'required|string|max:255',
            'camat_email'    => ['required', 'email', Rule::unique('users', 'email')->ignore($this->officialId)],
            'camat_nip'      => ['nullable', 'string', 'max:30', Rule::unique('users', 'nip')->ignore($this->officialId)],
            'camat_jk'       => 'nullable|in:L,P',
            'camat_password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:8'],
        ];
    }

    public function tambah()
    {
        $this->batal();
        $this->showForm = true;
    }

    public function simpan(AccountProvisioningService $provisioning)
    {
        $data = $this->validate();

        $payload = [
            'name'          => $data['camat_name'],
            'email'         => $data['camat_email'],
            'nip'           => $data['camat_nip'] ?: null,
            'jenis_kelamin' => $data['camat_jk'] ?: null,
            'password'      => $data['camat_password'] ?: null,
        ];

        if ($this->editingId) {
            Kecamatan::findOrFail($this->editingId)->update(['nama' => $data['nama']]);
            $provisioning->updateAccount(User::findOrFail($this->officialId), $payload);
            $pesan = 'Kecamatan diperbarui.';
        } else {
            $kecamatan = Kecamatan::create(['nama' => $data['nama']]);
            $provisioning->createCamat($kecamatan, $payload);
            $pesan = 'Kecamatan dan akun Camat berhasil dibuat.';
        }

        $this->batal();
        session()->flash('ok', $pesan);
    }

    public function edit(int $id)
    {
        $kecamatan = Kecamatan::findOrFail($id);
        $camat = User::role('camat')->where('kecamatan_id', $kecamatan->id)->first();

        $this->editingId = $kecamatan->id;
        $this->nama = $kecamatan->nama;
        $this->officialId = $camat?->id;
        $this->camat_name = $camat?->name ?? '';
        $this->camat_email = $camat?->email ?? '';
        $this->camat_nip = $camat?->nip ?? '';
        $this->camat_jk = $camat?->jenis_kelamin ?? '';
        $this->camat_password = '';

        $this->showForm = true;
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

        if (Kelurahan::where('kecamatan_id', $id)->exists()) {
            session()->flash('err', 'Tidak bisa dihapus: masih ada kelurahan di kecamatan ini.');
            return;
        }

        User::role('camat')->where('kecamatan_id', $id)->first()?->delete();
        Kecamatan::findOrFail($id)->delete();

        $this->deleteId = null;
        session()->flash('ok', 'Kecamatan dihapus.');
    }

    public function batal()
    {
        $this->reset('showForm', 'editingId', 'officialId', 'nama', 'camat_name', 'camat_email', 'camat_nip', 'camat_jk', 'camat_password');
    }

    public function render()
    {
        return view('livewire.admin.kecamatan-manager', [
            'daftar' => Kecamatan::withCount('kelurahan')->orderBy('nama')->get(),
            'camats' => User::role('camat')->get()->keyBy('kecamatan_id'),
        ]);
    }
}