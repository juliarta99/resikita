<?php

declare(strict_types=1);

namespace App\Livewire\Pemerintahan;

use App\Enums\JenisTps;
use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Models\Tps;
use App\Models\Wilayah;
use App\Services\Wilayah\WilayahScopeService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Pengelolaan TPS dan TPS3R di wilayah kewenangan.
 *
 * Role `admin_tps` dihapus pada migrasi ke Resikita; TPS kini dikelola
 * pemerintah wilayah yang membawahinya (CLAUDE.md 6.1). Halaman inilah
 * penggantinya.
 *
 * Tarif bulanan disimpan sebagai integer rupiah. Formulir menerima
 * angka rupiah penuh dan tidak pernah membaginya seratus, tidak ada
 * satuan sen dalam mata uang ini, dan memperkenalkannya hanya akan
 * melahirkan galat pembulatan.
 */
#[Title('TPS dan TPS3R')]
class TpsManager extends Component
{
    use MemberiUmpanBalik;
    use WithPagination;

    public string $cari = '';

    public bool $formTerbuka = false;

    public ?int $tpsId = null;

    public string $nama = '';

    public string $jenis = 'tps';

    public string $alamat = '';

    public string $phone = '';

    public string $wilayahId = '';

    public string $latitude = '';

    public string $longitude = '';

    public bool $isBerbayar = false;

    public string $tarifBulanan = '';

    public string $kapasitasTon = '';

    public function updatedCari(): void
    {
        $this->resetPage();
    }

    public function bukaForm(?int $id = null): void
    {
        $this->resetValidation();
        $this->reset([
            'tpsId', 'nama', 'jenis', 'alamat', 'phone', 'wilayahId',
            'latitude', 'longitude', 'isBerbayar', 'tarifBulanan', 'kapasitasTon',
        ]);

        if ($id !== null) {
            $tps = $this->dalamCakupan()->findOrFail($id);

            $this->tpsId = $tps->id;
            $this->nama = $tps->nama;
            $this->jenis = $tps->jenis->value;
            $this->alamat = $tps->alamat ?? '';
            $this->phone = $tps->phone ?? '';
            $this->wilayahId = (string) ($tps->wilayah_id ?? '');
            $this->latitude = (string) ($tps->latitude ?? '');
            $this->longitude = (string) ($tps->longitude ?? '');
            $this->isBerbayar = $tps->is_berbayar;
            $this->tarifBulanan = (string) ($tps->tarif_bulanan ?? '');
            $this->kapasitasTon = (string) ($tps->kapasitas_ton ?? '');
        } else {
            $this->wilayahId = (string) (auth()->user()->wilayah_id ?? '');
        }

        $this->formTerbuka = true;
    }

    public function tutupForm(): void
    {
        $this->formTerbuka = false;
        $this->resetValidation();
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'min:3', 'max:191'],
            'jenis' => ['required', 'in:'.implode(',', JenisTps::values())],
            'alamat' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'wilayahId' => ['required', 'integer'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'tarifBulanan' => [$this->isBerbayar ? 'required' : 'nullable', 'integer', 'min:0', 'max:100000000'],
            'kapasitasTon' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'nama' => 'nama TPS', 'wilayahId' => 'wilayah',
            'tarifBulanan' => 'tarif bulanan', 'kapasitasTon' => 'kapasitas',
        ];
    }

    public function simpan(WilayahScopeService $scope): void
    {
        $this->validate();

        if (! $scope->berwenangAtas(auth()->user(), (int) $this->wilayahId)) {
            $this->pesanGalat('Wilayah tersebut berada di luar kewenangan Anda.');

            return;
        }

        $atribut = [
            'nama' => $this->nama,
            'jenis' => $this->jenis,
            'alamat' => $this->alamat ?: null,
            'phone' => $this->phone ?: null,
            'wilayah_id' => (int) $this->wilayahId,
            'latitude' => $this->latitude !== '' ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== '' ? (float) $this->longitude : null,
            'is_berbayar' => $this->isBerbayar,
            'tarif_bulanan' => $this->isBerbayar && $this->tarifBulanan !== '' ? (int) $this->tarifBulanan : null,
            'kapasitas_ton' => $this->kapasitasTon !== '' ? (float) $this->kapasitasTon : null,
        ];

        if ($this->tpsId === null) {
            Tps::create($atribut);
            $this->pesanSukses('TPS ditambahkan dan langsung tampil di peta warga.');
        } else {
            $this->dalamCakupan()->findOrFail($this->tpsId)->update($atribut);
            $this->pesanSukses('Data TPS diperbarui.');
        }

        $this->tutupForm();
    }

    public function hapus(int $id): void
    {
        $tps = $this->dalamCakupan()->withCount('anggota')->findOrFail($id);

        if ($tps->anggota_count > 0) {
            $this->pesanGalat(
                "TPS ini masih punya {$tps->anggota_count} anggota terdaftar. "
                .'Pindahkan keanggotaannya lebih dulu sebelum menghapus.',
            );

            return;
        }

        $tps->delete();

        $this->pesanSukses('TPS dihapus.');
    }

    /** @return Builder<Tps> */
    private function dalamCakupan()
    {
        return app(WilayahScopeService::class)->applyWilayah(Tps::query(), auth()->user());
    }

    public function render()
    {
        $query = $this->dalamCakupan()
            ->with('wilayah:id,nama,tingkat')
            ->withCount('anggota')
            ->orderBy('nama');

        if (trim($this->cari) !== '') {
            $query->where('nama', 'like', '%'.trim($this->cari).'%');
        }

        $ids = app(WilayahScopeService::class)->idDalamCakupan(auth()->user());

        return view('livewire.pemerintahan.tps-manager', [
            'daftar' => $query->paginate(12),
            'jenisTersedia' => JenisTps::options(),
            'wilayahTersedia' => $ids === []
                ? collect()
                : Wilayah::query()->whereIn('id', $ids)->orderBy('tingkat')->orderBy('nama')->get(),
        ]);
    }
}
