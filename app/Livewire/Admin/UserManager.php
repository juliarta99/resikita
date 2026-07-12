<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class UserManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $roleFilter = '';

    public bool $showReset = false;
    public ?int $resetUserId = null;
    public string $new_password = '';

    public bool $showToggle = false;
    public ?int $toggleUserId = null;

    // Tambah admin (khusus super_admin)
    public bool $showCreate = false;
    public string $name = '';
    public string $email = '';
    public string $password = '';

    private function me(): User
    {
        /** @var \App\Models\User $u */
        $u = Auth::user();

        return $u;
    }

    public array $roleLabels = [
        'super_admin'         => 'Super Admin',
        'admin'               => 'Admin',
        'admin_dinas'         => 'Admin Dinas',
        'bupati'              => 'Bupati',
        'camat'               => 'Camat',
        'lurah'               => 'Lurah',
        'kepala_dinas_banjar' => 'Kepala Dinas Banjar',
        'umkm'                => 'Admin UMKM',
        'admin_tps'           => 'Admin TPS',
        'admin_bank_sampah'   => 'Admin Bank Sampah',
        'petugas_bank_sampah' => 'Petugas Bank Sampah',
        'masyarakat'          => 'Masyarakat',
        'petugas_lapangan'    => 'Petugas Lapangan',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRoleFilter()
    {
        $this->resetPage();
    }

    public function bukaTambahAdmin()
    {
        abort_unless($this->me()->hasRole('super_admin'), 403);
        $this->reset('name', 'email', 'password');
        $this->resetErrorBag();
        $this->showCreate = true;
    }

    public function simpanAdmin()
    {
        abort_unless($this->me()->hasRole('super_admin'), 403);

        $data = $this->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'email', Rule::unique('users', 'email')],
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'is_active' => true,
        ]);
        $user->assignRole('admin');

        $this->reset('showCreate', 'name', 'email', 'password');
        session()->flash('ok', 'Admin baru berhasil ditambahkan.');
    }

    public function aktifkan(int $id)
    {
        User::findOrFail($id)->update(['is_active' => true]);
        session()->flash('ok', 'Akun diaktifkan.');
    }

    public function konfirmNonaktif(int $id)
    {
        $this->toggleUserId = $id;
        $this->showToggle = true;
    }

    public function nonaktifkan()
    {
        $this->showToggle = false;
        $user = User::find($this->toggleUserId);
        $this->toggleUserId = null;

        if (! $user) {
            return;
        }

        if ($user->id === Auth::id() || $user->hasRole('super_admin')) {
            session()->flash('err', 'Akun ini tidak dapat dinonaktifkan.');
            return;
        }

        $user->update(['is_active' => false]);
        session()->flash('ok', 'Akun dinonaktifkan.');
    }

    public function bukaReset(int $id)
    {
        $this->resetUserId = $id;
        $this->new_password = '';
        $this->showReset = true;
    }

    public function simpanReset()
    {
        $this->validate(['new_password' => 'required|string|min:8']);

        User::findOrFail($this->resetUserId)->update([
            'password' => Hash::make($this->new_password),
        ]);

        $this->reset('showReset', 'resetUserId', 'new_password');
        session()->flash('ok', 'Kata sandi berhasil direset.');
    }

    public function render()
    {
        $query = User::query()->with(['roles', 'kecamatan', 'kelurahan', 'banjarDinas', 'tps', 'bankSampah', 'umkm']);

        if ($this->search !== '') {
            $s = $this->search;
            $query->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('nik', 'like', "%{$s}%");
            });
        }

        if ($this->roleFilter !== '') {
            $query->role($this->roleFilter);
        }

        return view('livewire.admin.user-manager', [
            'users' => $query->orderBy('name')->paginate(15),
        ]);
    }
}