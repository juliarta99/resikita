<?php

namespace App\Livewire\Admin;

use App\Models\BankSampah;
use App\Models\BanjarDinas;
use App\Models\Kelurahan;
use App\Models\Tps;
use App\Models\Umkm;
use App\Models\User;
use App\Services\Domain\AccountProvisioningService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class BanjarDinasManager extends Component
{
    public bool $showForm = false;
    public bool $showDelete = false;
    public ?int $deleteId = null;

    public ?int $editingId = null;
    public ?int $officialId = null;

    public string $kelurahan_id = '';
    public string $nama = '';
    public string $kadis_name = '';
    public string $kadis_email = '';
    public string $kadis_nip = '';
    public string $kadis_jk = '';
    public string $kadis_password = '';

    protected function rules(): array
    {
        return [
            'kelurahan_id'   => 'required|exists:kelurahan,id',
            'nama'           => 'required|string|max:255',
            'kadis_name'     => 'required|string|max:255',
            'kadis_email'    => ['required', 'email', Rule::unique('users', 'email')->ignore($this->officialId)],
            'kadis_nip'      => ['nullable', 'string', 'max:30', Rule::unique('users', 'nip')->ignore($this->officialId)],
            'kadis_jk'       => 'nullable|in:L,P',
            'kadis_password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:8'],
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
            'name'          => $data['kadis_name'],
            'email'         => $data['kadis_email'],
            'nip'           => $data['kadis_nip'] ?: null,
            'jenis_kelamin' => $data['kadis_jk'] ?: null,
            'password'      => $data['kadis_password'] ?: null,
        ];

        if ($this->editingId) {
            BanjarDinas::findOrFail($this->editingId)->update(['kelurahan_id' => $data['kelurahan_id'], 'nama' => $data['nama']]);
            $provisioning->updateAccount(User::findOrFail($this->officialId), $payload);
            $pesan = 'Banjar Dinas diperbarui.';
        } else {
            $banjar = BanjarDinas::create(['kelurahan_id' => $data['kelurahan_id'], 'nama' => $data['nama']]);
            $provisioning->createKepalaDinasBanjar($banjar, $payload);
            $pesan = 'Banjar Dinas dan akun Kepala Dinas Banjar berhasil dibuat.';
        }

        $this->batal();
        session()->flash('ok', $pesan);
    }

    public function edit(int $id)
    {
        $banjar = BanjarDinas::findOrFail($id);
        $kadis = User::role('kepala_dinas_banjar')->where('banjar_id', $banjar->id)->first();

        $this->editingId = $banjar->id;
        $this->kelurahan_id = (string) $banjar->kelurahan_id;
        $this->nama = $banjar->nama;
        $this->officialId = $kadis?->id;
        $this->kadis_name = $kadis?->name ?? '';
        $this->kadis_email = $kadis?->email ?? '';
        $this->kadis_nip = $kadis?->nip ?? '';
        $this->kadis_jk = $kadis?->jenis_kelamin ?? '';
        $this->kadis_password = '';

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

        $terpakai = Tps::where('banjar_id', $id)->exists()
            || BankSampah::where('banjar_id', $id)->exists()
            || Umkm::where('banjar_id', $id)->exists();

        if ($terpakai) {
            session()->flash('err', 'Tidak bisa dihapus: banjar ini masih dipakai oleh TPS / Bank Sampah / UMKM.');
            return;
        }

        User::role('kepala_dinas_banjar')->where('banjar_id', $id)->first()?->delete();
        BanjarDinas::findOrFail($id)->delete();

        $this->deleteId = null;
        session()->flash('ok', 'Banjar Dinas dihapus.');
    }

    public function batal()
    {
        $this->reset('showForm', 'editingId', 'officialId', 'kelurahan_id', 'nama', 'kadis_name', 'kadis_email', 'kadis_nip', 'kadis_jk', 'kadis_password');
    }

    public function render()
    {
        return view('livewire.admin.banjar-dinas-manager', [
            'kelurahanList' => Kelurahan::with('kecamatan')->orderBy('nama')->get(),
            'daftar'        => BanjarDinas::with('kelurahan.kecamatan')->orderBy('nama')->get(),
            'kepalas'       => User::role('kepala_dinas_banjar')->get()->keyBy('banjar_id'),
        ]);
    }
}