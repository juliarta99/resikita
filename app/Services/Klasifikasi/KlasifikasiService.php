<?php

declare(strict_types=1);

namespace App\Services\Klasifikasi;

use App\Enums\KategoriSampah;
use App\Exceptions\AturanBisnisException;
use App\Models\BankSampahHarga;
use App\Models\KlasifikasiSampah;
use App\Models\User;
use App\Services\Integration\GeminiService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

/**
 * Klasifikasi sampah dari foto, dipakai web dan mobile.
 *
 * ## Kenapa keluaran model divalidasi ulang di sini
 *
 * `responseSchema` Gemini mempersempit ruang jawaban, tapi tidak
 * menjaminnya. Kolom `kategori` di skema lama berupa varchar bebas,
 * sehingga apa pun yang dijawab model langsung tersimpan, dan begitu
 * ada satu baris berisi "anorganik_plastik" di samping "anorganik",
 * seluruh statistik pemilahan menjadi tidak bisa dijumlahkan. Sekarang
 * nilai di luar KategoriSampah ditolak, bukan disimpan apa adanya
 * (CLAUDE.md 10.1).
 *
 * ## Kenapa nilai rupiah tidak diambil dari model
 *
 * Harga sampah berbeda jauh antar kota dan berubah tiap bulan. Angka
 * yang diarang model akan dibaca warga sebagai janji, lalu meleset di
 * meja bank sampah. Karena itu estimasi nilai diambil dari katalog
 * harga bank sampah yang benar-benar terdaftar bila ada; tebakan model
 * hanya dipakai ketika tidak ada satu pun harga nyata yang bisa
 * dirujuk, dan pemanggil bisa membedakannya lewat `sumber_nilai`.
 */
class KlasifikasiService
{
    public function __construct(
        private readonly GeminiService $gemini,
    ) {}

    /**
     * Klasifikasikan satu foto dan simpan hasilnya.
     *
     * @param  string  $fotoPath  Path relatif pada disk publik, hasil unggahan
     */
    public function klasifikasikan(User $user, string $fotoPath): KlasifikasiSampah
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($fotoPath)) {
            throw AturanBisnisException::karena('Foto yang diunggah tidak dapat dibaca ulang oleh peladen.');
        }

        try {
            $mentah = $this->gemini->klasifikasiSampah(
                (string) $disk->get($fotoPath),
                $disk->mimeType($fotoPath) ?: 'image/jpeg',
            );
        } catch (AturanBisnisException $e) {
            // Foto yang tidak jadi dipakai tidak boleh menumpuk di disk.
            $disk->delete($fotoPath);

            throw $e;
        }

        $kategori = $this->kategoriValid($mentah['kategori'] ?? null);
        $berat = $this->beratValid($mentah['estimasi_berat_kg'] ?? null);
        $nilai = $this->estimasiNilai($user, $kategori, $berat, $mentah['estimasi_nilai_rupiah'] ?? null);

        return KlasifikasiSampah::create([
            'user_id' => $user->id,
            'foto_path' => $fotoPath,
            'jenis' => $this->teksValid($mentah['jenis'] ?? null, 150) ?? 'Sampah tidak dikenali',
            'kategori' => $kategori,
            'material' => $this->teksValid($mentah['material'] ?? null, 100),
            'confidence' => $this->confidenceValid($mentah['confidence'] ?? null),
            'dapat_didaur_ulang' => (bool) ($mentah['dapat_didaur_ulang'] ?? false),
            'estimasi_berat_kg' => $berat,
            'estimasi_nilai' => $nilai,
            'langkah_pengolahan' => $this->langkahValid($mentah['langkah_pengolahan'] ?? null),
            'rekomendasi_daur_ulang' => $this->teksValid($mentah['rekomendasi_daur_ulang'] ?? null, 1000),
            'catatan' => $this->catatan($mentah, $kategori),
            'model_version' => $this->gemini->modelVersion(),
            'raw_response' => $mentah,
        ]);
    }

    /** Riwayat klasifikasi milik satu pengguna. */
    public function riwayat(User $user, ?KategoriSampah $kategori = null, int $perPage = 15): LengthAwarePaginator
    {
        return KlasifikasiSampah::query()
            ->where('user_id', $user->id)
            ->when($kategori !== null, fn ($q) => $q->kategori($kategori))
            ->latest('id')
            ->paginate($perPage);
    }

    /** Ringkasan pemilahan pengguna, dipakai kartu statistik di beranda. */
    public function ringkasan(User $user): array
    {
        $perKategori = KlasifikasiSampah::query()
            ->where('user_id', $user->id)
            ->selectRaw('kategori, count(*) as jumlah, coalesce(sum(estimasi_nilai), 0) as nilai')
            ->groupBy('kategori')
            ->get();

        return [
            'total' => (int) $perKategori->sum('jumlah'),
            'estimasi_nilai_total' => (int) $perKategori->sum('nilai'),
            'per_kategori' => collect(KategoriSampah::cases())
                ->map(function (KategoriSampah $k) use ($perKategori): array {
                    $baris = $perKategori->firstWhere('kategori', $k);

                    return [
                        'kategori' => $k->value,
                        'label' => $k->label(),
                        'warna' => $k->warna(),
                        'jumlah' => (int) ($baris->jumlah ?? 0),
                    ];
                })
                ->all(),
        ];
    }

    public function hapus(KlasifikasiSampah $klasifikasi): void
    {
        Storage::disk('public')->delete($klasifikasi->foto_path);

        $klasifikasi->delete();
    }

    // ----------------------------------------------------------------
    // Validasi keluaran model
    // ----------------------------------------------------------------

    private function kategoriValid(mixed $nilai): KategoriSampah
    {
        $kategori = is_string($nilai) ? KategoriSampah::tryFrom($nilai) : null;

        if ($kategori !== null) {
            return $kategori;
        }

        /*
         * Kategori tak dikenal jatuh ke residu, bukan ditolak sebagai
         * galat. Pengguna sudah memotret sampahnya; menolak seluruh
         * hasil karena satu kolom menyimpang membuang usaha itu, dan
         * residu adalah tebakan yang paling tidak berbahaya, ia
         * mengarahkan ke TPA, tidak pernah ke aliran daur ulang.
         */
        return KategoriSampah::Residu;
    }

    private function confidenceValid(mixed $nilai): float
    {
        if (! is_numeric($nilai)) {
            return 0.0;
        }

        $angka = (float) $nilai;

        // Sebagian jawaban memakai skala 0–1 meski diminta 0–100.
        if ($angka > 0 && $angka <= 1) {
            $angka *= 100;
        }

        return round(max(0.0, min(100.0, $angka)), 2);
    }

    private function beratValid(mixed $nilai): ?float
    {
        if (! is_numeric($nilai)) {
            return null;
        }

        $berat = round((float) $nilai, 3);

        // Di atas satu kuintal jelas bukan objek yang muat dalam satu
        // foto genggam; nilainya dibuang daripada mencemari statistik.
        return $berat > 0 && $berat <= 100 ? $berat : null;
    }

    /** @return array<int, string> */
    private function langkahValid(mixed $nilai): array
    {
        if (! is_array($nilai)) {
            return [];
        }

        return collect($nilai)
            ->filter(fn ($l): bool => is_string($l) && trim($l) !== '')
            ->map(fn (string $l): string => trim($l))
            ->take(6)
            ->values()
            ->all();
    }

    private function teksValid(mixed $nilai, int $maks): ?string
    {
        if (! is_string($nilai)) {
            return null;
        }

        $teks = trim($nilai);

        return $teks === '' ? null : mb_substr($teks, 0, $maks);
    }

    /**
     * Catatan yang tampil ke pengguna.
     *
     * Peringatan penanganan untuk B3 dan elektronik ditambahkan di sini,
     * tidak diserahkan kepada model. Kalau model lupa menyebutnya sekali
     * saja, ada orang yang membuang baterai ke tempat sampah biasa.
     */
    private function catatan(array $mentah, KategoriSampah $kategori): ?string
    {
        $catatan = $this->teksValid($mentah['catatan'] ?? null, 500);

        if (! $kategori->butuhPenangananKhusus()) {
            return $catatan;
        }

        $peringatan = $kategori === KategoriSampah::B3
            ? 'Jangan dicampur dengan sampah rumah tangga biasa. Simpan terpisah dalam wadah tertutup, '
                .'lalu serahkan ke fasilitas penampungan limbah B3 atau dinas lingkungan hidup setempat.'
            : 'Jangan dibuang bersama sampah biasa. Serahkan ke gerai penerima sampah elektronik '
                .'atau tanyakan titik pengumpulan ke dinas lingkungan hidup setempat.';

        return $catatan !== null ? $peringatan.' '.$catatan : $peringatan;
    }

    // ----------------------------------------------------------------
    // Estimasi nilai
    // ----------------------------------------------------------------

    /**
     * Perkiraan nilai jual objek dalam rupiah penuh.
     *
     * Urutannya: harga nyata di wilayah pengguna, lalu harga nyata di
     * mana pun, baru tebakan model. Kategori yang memang tidak bernilai
     * ekonomi tidak pernah diberi angka, berapa pun yang dijawab model.
     */
    private function estimasiNilai(User $user, KategoriSampah $kategori, ?float $berat, mixed $tebakanModel): ?int
    {
        if (! $kategori->bernilaiEkonomi()) {
            return null;
        }

        if ($berat !== null) {
            $hargaPerKg = $this->hargaRujukan($user, $kategori);

            if ($hargaPerKg !== null) {
                return (int) round($hargaPerKg * $berat);
            }
        }

        if (is_numeric($tebakanModel) && (int) $tebakanModel > 0) {
            return (int) $tebakanModel;
        }

        return null;
    }

    /**
     * Harga rata-rata per kilogram untuk sebuah kategori.
     *
     * Dicari lebih dulu di wilayah pengguna supaya angkanya menyerupai
     * yang akan ia temui di bank sampah terdekat, bukan rata-rata
     * nasional yang tidak berlaku di mana pun.
     */
    private function hargaRujukan(User $user, KategoriSampah $kategori): ?float
    {
        $dasar = fn () => BankSampahHarga::query()
            ->aktif()
            ->where('kategori', $kategori)
            ->where('satuan', 'kg');

        if ($user->wilayah_id !== null) {
            $lokal = $dasar()
                ->whereHas('bankSampah', fn ($q) => $q->where('wilayah_id', $user->wilayah_id))
                ->avg('harga_per_satuan');

            if ($lokal !== null) {
                return (float) $lokal;
            }
        }

        $nasional = $dasar()->avg('harga_per_satuan');

        return $nasional !== null ? (float) $nasional : null;
    }
}
