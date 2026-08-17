<?php

declare(strict_types=1);

namespace App\Services\Wilayah;

use App\Enums\Role;
use App\Enums\TingkatWilayah;
use App\Models\Laporan;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Pembatas cakupan data menurut kewenangan wilayah.
 *
 * Ini satu-satunya tempat filter wilayah boleh ditulis. Kebocoran data
 * lintas daerah adalah kegagalan fatal di sistem pemerintahan, seorang
 * admin kabupaten yang bisa melihat laporan kabupaten lain bukan sekadar
 * bug tampilan, melainkan pelanggaran kewenangan. Menyebar filter ini ke
 * puluhan komponen Livewire dan controller berarti puluhan kesempatan
 * untuk lupa satu klausa. Di sini, ada satu.
 *
 * Dua bentuk pembatasan yang ditangani:
 *
 * 1. `applyLaporan()`, tabel `laporan` menyimpan empat kolom wilayah
 *    hasil denormalisasi, jadi cukup satu perbandingan langsung.
 *
 * 2. `applyWilayah()`, entitas seperti bank sampah, TPS, dan UMKM hanya
 *    punya satu `wilayah_id` di tingkat desa. Untuk admin provinsi,
 *    pembatasannya harus mencakup seluruh keturunan provinsi itu, yang
 *    dicari lewat awalan kode Kemendagri.
 */
class WilayahScopeService
{
    /**
     * Batasi query laporan sesuai kewenangan pengguna.
     *
     * @param  Builder<Laporan>  $query
     * @return Builder<Laporan>
     */
    public function applyLaporan(Builder $query, User $user): Builder
    {
        $role = $user->roleUtama();

        if ($role === null) {
            return $this->tolakSemua($query);
        }

        // Role platform melihat lintas wilayah tanpa pembatasan.
        if ($role->isPlatform()) {
            return $query;
        }

        // Petugas dibatasi oleh penugasan, bukan oleh wilayahnya. Seorang
        // petugas hanya berkepentingan atas laporan yang diberikan
        // kepadanya, bukan atas seluruh laporan di wilayah induknya.
        if ($role === Role::Petugas) {
            return $query->whereHas(
                'penugasan',
                fn (Builder $q) => $q->where('petugas_id', $user->id),
            );
        }

        if ($role->isPemerintahan()) {
            $kolom = $this->kolomLaporanUntuk($role);

            // Role pemerintahan tanpa wilayah adalah keadaan tidak sah:
            // pengajuan wilayahnya belum disetujui atau data akunnya cacat.
            // Menampilkan seluruh laporan nasional jelas keliru, jadi
            // yang benar adalah tidak menampilkan apa pun.
            if ($kolom === null || $user->wilayah_id === null) {
                return $this->tolakSemua($query);
            }

            return $query->where($kolom, $user->wilayah_id);
        }

        // Masyarakat, bank sampah, dan UMKM hanya melihat laporannya sendiri.
        return $query->where('pelapor_id', $user->id);
    }

    /**
     * Batasi query entitas berkolom `wilayah_id` tunggal.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function applyWilayah(Builder $query, User $user, string $kolom = 'wilayah_id'): Builder
    {
        $role = $user->roleUtama();

        if ($role === null) {
            return $this->tolakSemua($query);
        }

        if ($role->isPlatform()) {
            return $query;
        }

        if (! $role->butuhWilayah()) {
            return $query;
        }

        if ($user->wilayah_id === null) {
            return $this->tolakSemua($query);
        }

        $wilayah = $user->relationLoaded('wilayah')
            ? $user->wilayah
            : Wilayah::find($user->wilayah_id);

        if ($wilayah === null) {
            return $this->tolakSemua($query);
        }

        // Kepala desa cukup dicocokkan langsung, desa adalah daun
        // hierarki, tidak punya keturunan.
        if ($wilayah->tingkat === TingkatWilayah::Desa) {
            return $query->where($kolom, $wilayah->id);
        }

        return $query->whereIn(
            $kolom,
            $this->idWilayahDanKeturunan($wilayah),
        );
    }

    /**
     * Batasi query pengguna sesuai kewenangan. Dipakai halaman
     * manajemen petugas, di mana pemerintah wilayah hanya boleh melihat
     * dan mengelola akun di bawah cakupannya.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function applyPengguna(Builder $query, User $user): Builder
    {
        return $this->applyWilayah($query, $user);
    }

    /**
     * Daftar id wilayah yang berada dalam cakupan pengguna, termasuk
     * wilayahnya sendiri. Berguna untuk agregasi analitik.
     *
     * @return array<int, int>
     */
    public function idDalamCakupan(User $user): array
    {
        if ($user->wilayah_id === null) {
            return [];
        }

        $wilayah = Wilayah::find($user->wilayah_id);

        return $wilayah === null ? [] : $this->idWilayahDanKeturunan($wilayah);
    }

    /**
     * Apakah pengguna berwenang atas sebuah wilayah.
     *
     * Dipakai Policy sebelum tindakan yang menyentuh wilayah tertentu,
     * mis. menugaskan petugas ke sebuah laporan.
     */
    public function berwenangAtas(User $user, ?int $wilayahId): bool
    {
        $role = $user->roleUtama();

        if ($role === null || $wilayahId === null) {
            return false;
        }

        if ($role->isPlatform()) {
            return true;
        }

        if (! $role->butuhWilayah() || $user->wilayah_id === null) {
            return false;
        }

        if ($user->wilayah_id === $wilayahId) {
            return true;
        }

        $wilayah = Wilayah::find($user->wilayah_id);

        return $wilayah !== null && in_array($wilayahId, $this->idWilayahDanKeturunan($wilayah), true);
    }

    /**
     * Id wilayah beserta seluruh keturunannya.
     *
     * Pencarian memakai awalan kode Kemendagri, bukan penelusuran
     * rekursif: kode anak selalu diawali kode induknya, sehingga satu
     * `LIKE '51.03.%'` berbasis index menggantikan penelusuran berjenjang.
     *
     * @return array<int, int>
     */
    private function idWilayahDanKeturunan(Wilayah $wilayah): array
    {
        return Wilayah::query()
            ->where('id', $wilayah->id)
            ->orWhere('kode', 'like', $wilayah->kode.'.%')
            ->pluck('id')
            ->all();
    }

    /** Kolom laporan yang dipakai role pemerintahan untuk membatasi cakupan. */
    private function kolomLaporanUntuk(Role $role): ?string
    {
        return match ($role->tingkatWilayah()) {
            TingkatWilayah::Provinsi => 'provinsi_id',
            TingkatWilayah::Kabupaten => 'kabupaten_id',
            TingkatWilayah::Desa => 'desa_id',
            default => null,
        };
    }

    /**
     * Kembalikan query yang dijamin kosong.
     *
     * Sengaja memakai whereRaw('1 = 0') dan bukan sekadar melewatkan
     * query apa adanya: kalau cakupan tidak bisa ditentukan, jawaban
     * yang aman adalah tidak ada data, bukan semua data.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function tolakSemua(Builder $query): Builder
    {
        return $query->whereRaw('1 = 0');
    }
}
