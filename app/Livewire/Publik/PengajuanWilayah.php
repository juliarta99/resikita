<?php

declare(strict_types=1);

namespace App\Livewire\Publik;

use App\Enums\TingkatWilayah;
use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Models\PengajuanWilayah as ModelPengajuan;
use App\Models\Wilayah;
use App\Services\Wilayah\PengajuanWilayahService;
use App\Support\Unggahan;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Formulir pengajuan pendaftaran wilayah, terbuka tanpa akun.
 *
 * Pemohonnya pejabat daerah yang justru belum punya akun Resikita,
 * mewajibkan login lebih dulu membuat fitur ini mustahil dipakai oleh
 * orang yang dituju. Yang menjaga mutunya bukan autentikasi, melainkan
 * surat bukti kewenangan yang wajib diunggah dan ditinjau super admin.
 *
 * Pemilihan wilayah bertingkat, bukan satu dropdown berisi seluruh
 * Indonesia. Kecamatan tetap muncul sebagai jalan menuju desa, tapi
 * tidak bisa dipilih sebagai wilayah pengajuan: kecamatan bukan tingkat
 * kewenangan pengelolaan sampah (CLAUDE.md 6.1), dan aturan itu
 * ditegakkan lagi di PengajuanWilayahService.
 */
#[Layout('components.layouts.publik')]
#[Title('Ajukan Pendaftaran Wilayah')]
class PengajuanWilayah extends Component
{
    use MemberiUmpanBalik;
    use WithFileUploads;

    public ?int $provinsiId = null;

    public ?int $kabupatenId = null;

    public ?int $kecamatanId = null;

    public ?int $desaId = null;

    public string $pemohonNama = '';

    public string $pemohonJabatan = '';

    public string $pemohonEmail = '';

    public string $pemohonPhone = '';

    public string $instansi = '';

    public $surat;

    public bool $terkirim = false;

    public string $tiketPengajuan = '';

    // ----------------------------------------------------------------
    // Pemilihan wilayah bertingkat
    // ----------------------------------------------------------------

    public function updatedProvinsiId(): void
    {
        $this->reset(['kabupatenId', 'kecamatanId', 'desaId']);
    }

    public function updatedKabupatenId(): void
    {
        $this->reset(['kecamatanId', 'desaId']);
    }

    public function updatedKecamatanId(): void
    {
        $this->reset('desaId');
    }

    /**
     * Wilayah yang benar-benar diajukan: yang paling dalam yang dipilih,
     * dan hanya kalau tingkatnya memang tingkat kewenangan.
     */
    private function wilayahDiajukan(): ?Wilayah
    {
        $id = $this->desaId ?? $this->kabupatenId;

        return $id !== null ? Wilayah::find($id) : null;
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'kabupatenId' => ['required', 'integer'],
            'pemohonNama' => ['required', 'string', 'min:3', 'max:150'],
            'pemohonJabatan' => ['required', 'string', 'min:3', 'max:150'],
            'pemohonEmail' => ['required', 'email:rfc', 'max:191'],
            'pemohonPhone' => ['nullable', 'string', 'regex:/^0[0-9]{8,13}$/'],
            'instansi' => ['required', 'string', 'min:3', 'max:191'],
            'surat' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'kabupatenId' => 'kabupaten/kota',
            'pemohonNama' => 'nama pemohon',
            'pemohonJabatan' => 'jabatan',
            'pemohonEmail' => 'email',
            'pemohonPhone' => 'nomor telepon',
            'instansi' => 'instansi',
            'surat' => 'surat bukti kewenangan',
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'kabupatenId.required' => 'Pilih kabupaten/kota terlebih dahulu.',
            'surat.required' => 'Surat tugas atau surat keterangan kewenangan wajib diunggah.',
            'surat.mimes' => 'Surat harus berformat PDF, JPG, atau PNG.',
            'pemohonPhone.regex' => 'Nomor telepon diawali 0 dan terdiri dari 9 sampai 14 angka.',
        ];
    }

    public function ajukan(PengajuanWilayahService $service): void
    {
        $this->validate();

        $wilayah = $this->wilayahDiajukan();

        if ($wilayah === null) {
            $this->pesanGalat('Wilayah yang dipilih tidak ditemukan. Muat ulang halaman lalu coba lagi.');

            return;
        }

        /*
         * Surat masuk ke disk privat, bukan publik. Isinya surat berkop
         * dinas lengkap dengan nama dan nomor pejabat; menaruhnya di
         * disk publik berarti siapa pun yang menebak nama berkasnya bisa
         * membacanya.
         */
        $suratPath = Unggahan::simpan($this->surat, 'pengajuan-wilayah', 'local');

        $pengajuan = $this->jalankan(fn (): ModelPengajuan => $service->ajukan($wilayah, [
            'pemohon_nama' => $this->pemohonNama,
            'pemohon_jabatan' => $this->pemohonJabatan,
            'pemohon_email' => $this->pemohonEmail,
            'pemohon_phone' => $this->pemohonPhone ?: null,
            'instansi' => $this->instansi,
            'surat_path' => $suratPath,
        ]));

        if ($pengajuan === null) {
            return;
        }

        $this->tiketPengajuan = $wilayah->kode;
        $this->terkirim = true;
    }

    public function render()
    {
        return view('livewire.publik.pengajuan-wilayah', [
            'provinsiTersedia' => Wilayah::query()
                ->tingkat(TingkatWilayah::Provinsi)
                ->orderBy('nama')
                ->pluck('nama', 'id'),

            'kabupatenTersedia' => $this->provinsiId === null
                ? collect()
                : Wilayah::query()->where('parent_id', $this->provinsiId)->orderBy('nama')->pluck('nama', 'id'),

            'kecamatanTersedia' => $this->kabupatenId === null
                ? collect()
                : Wilayah::query()->where('parent_id', $this->kabupatenId)->orderBy('nama')->pluck('nama', 'id'),

            'desaTersedia' => $this->kecamatanId === null
                ? collect()
                : Wilayah::query()->where('parent_id', $this->kecamatanId)->orderBy('nama')->pluck('nama', 'id'),

            'wilayahTerpilih' => $this->wilayahDiajukan(),
        ]);
    }
}
