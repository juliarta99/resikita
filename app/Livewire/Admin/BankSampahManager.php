<?php

namespace App\Livewire\Admin;

use App\Models\BankSampah;
use App\Models\BanjarDinas;
use App\Models\User;
use App\Models\WasteDeposit;
use App\Services\Domain\AccountProvisioningService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class BankSampahManager extends Component
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
            BankSampah::findOrFail($this->editingId)->update($attrs);
            $provisioning->updateAccount(User::findOrFail($this->officialId), $payload);
            $pesan = 'Bank Sampah diperbarui.';
        } else {
            $bankSampah = BankSampah::create($attrs);
            $provisioning->createAdminBankSampah($bankSampah, $payload);
            $pesan = 'Bank Sampah dan akun Admin berhasil dibuat.';
        }

        $this->batal();
        session()->flash('ok', $pesan);
    }

    public function edit(int $id)
    {
        $bs = BankSampah::findOrFail($id);
        $admin = User::role('admin_bank_sampah')->where('bank_sampah_id', $bs->id)->first();

        $this->editingId = $bs->id;
        $this->nama = $bs->nama;
        $this->banjar_id = (string) $bs->banjar_id;
        $this->alamat = $bs->alamat ?? '';
        $this->no_hp = $bs->no_hp ?? '';
        $this->lat = $bs->lat ? (string) $bs->lat : '';
        $this->lng = $bs->lng ? (string) $bs->lng : '';
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

        if (WasteDeposit::where('bank_sampah_id', $id)->exists()) {
            session()->flash('err', 'Tidak bisa dihapus: bank sampah ini sudah memiliki riwayat setor sampah.');
            return;
        }

        User::where('bank_sampah_id', $id)->delete();
        BankSampah::findOrFail($id)->delete();

        $this->deleteId = null;
        session()->flash('ok', 'Bank Sampah dihapus.');
    }

    public function batal()
    {
        $this->reset('showForm', 'editingId', 'officialId', 'nama', 'banjar_id', 'alamat', 'no_hp', 'lat', 'lng', 'admin_name', 'admin_email', 'admin_password');
    }

    public function render()
    {
        return view('livewire.admin.bank-sampah-manager', [
            'banjarList' => BanjarDinas::with('kelurahan.kecamatan')->orderBy('nama')->get(),
            'daftar'     => BankSampah::with('banjarDinas')->latest()->get(),
        ]);
    }
}