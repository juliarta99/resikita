<?php

namespace App\Livewire\Admin;

use App\Models\BanjarDinas;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\User;
use App\Services\Domain\AccountProvisioningService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class KelurahanManager extends Component
{
    public bool $showForm = false;
    public bool $showDelete = false;
    public ?int $deleteId = null;

    public ?int $editingId = null;
    public ?int $officialId = null;

    public string $kecamatan_id = '';
    public string $nama = '';
    public string $lurah_name = '';
    public string $lurah_email = '';
    public string $lurah_nip = '';
    public string $lurah_jk = '';
    public string $lurah_password = '';

    protected function rules(): array
    {
        return [
            'kecamatan_id'   => 'required|exists:kecamatan,id',
            'nama'           => 'required|string|max:255',
            'lurah_name'     => 'required|string|max:255',
            'lurah_email'    => ['required', 'email', Rule::unique('users', 'email')->ignore($this->officialId)],
            'lurah_nip'      => ['nullable', 'string', 'max:30', Rule::unique('users', 'nip')->ignore($this->officialId)],
            'lurah_jk'       => 'nullable|in:L,P',
            'lurah_password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:8'],
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
            'name'          => $data['lurah_name'],
            'email'         => $data['lurah_email'],
            'nip'           => $data['lurah_nip'] ?: null,
            'jenis_kelamin' => $data['lurah_jk'] ?: null,
            'password'      => $data['lurah_password'] ?: null,
        ];

        if ($this->editingId) {
            Kelurahan::findOrFail($this->editingId)->update(['kecamatan_id' => $data['kecamatan_id'], 'nama' => $data['nama']]);
            $provisioning->updateAccount(User::findOrFail($this->officialId), $payload);
            $pesan = 'Kelurahan diperbarui.';
        } else {
            $kelurahan = Kelurahan::create(['kecamatan_id' => $data['kecamatan_id'], 'nama' => $data['nama']]);
            $provisioning->createLurah($kelurahan, $payload);
            $pesan = 'Kelurahan dan akun Lurah berhasil dibuat.';
        }

        $this->batal();
        session()->flash('ok', $pesan);
    }

    public function edit(int $id)
    {
        $kelurahan = Kelurahan::findOrFail($id);
        $lurah = User::role('lurah')->where('kelurahan_id', $kelurahan->id)->first();

        $this->editingId = $kelurahan->id;
        $this->kecamatan_id = (string) $kelurahan->kecamatan_id;
        $this->nama = $kelurahan->nama;
        $this->officialId = $lurah?->id;
        $this->lurah_name = $lurah?->name ?? '';
        $this->lurah_email = $lurah?->email ?? '';
        $this->lurah_nip = $lurah?->nip ?? '';
        $this->lurah_jk = $lurah?->jenis_kelamin ?? '';
        $this->lurah_password = '';

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

        if (BanjarDinas::where('kelurahan_id', $id)->exists()) {
            session()->flash('err', 'Tidak bisa dihapus: masih ada banjar dinas di kelurahan ini.');
            return;
        }

        User::role('lurah')->where('kelurahan_id', $id)->first()?->delete();
        Kelurahan::findOrFail($id)->delete();

        $this->deleteId = null;
        session()->flash('ok', 'Kelurahan dihapus.');
    }

    public function batal()
    {
        $this->reset('showForm', 'editingId', 'officialId', 'kecamatan_id', 'nama', 'lurah_name', 'lurah_email', 'lurah_nip', 'lurah_jk', 'lurah_password');
    }

    public function render()
    {
        return view('livewire.admin.kelurahan-manager', [
            'kecamatanList' => Kecamatan::orderBy('nama')->get(),
            'daftar'        => Kelurahan::with('kecamatan')->withCount('banjarDinas')->orderBy('nama')->get(),
            'lurahs'        => User::role('lurah')->get()->keyBy('kelurahan_id'),
        ]);
    }
}