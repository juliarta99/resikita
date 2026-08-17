<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gerbang panel penjual: status toko, bukan status akun.
 *
 * Sebelumnya pemisahan ini dilakukan dengan mematikan akun pemiliknya,
 * pendaftar dikunci di luar sistem sampai disetujui, dan dikunci
 * selamanya bila ditolak. Akibatnya ia tidak pernah bisa melihat
 * statusnya sendiri, tidak pernah membaca alasan penolakan, dan tidak
 * punya jalan memperbaikinya.
 *
 * Yang dipisahkan sebenarnya dua hal berbeda: hak seseorang memakai
 * Resikita, dan kelayakan tokonya tampil di marketplace. Yang pertama
 * milik `users.is_active`, yang kedua milik `umkm.status`. Middleware ini
 * menegakkan yang kedua, dan hanya untuk halaman yang benar-benar
 * mengubah dagangan, halaman status pendaftaran sengaja di luar
 * cakupannya.
 */
class PastikanTokoTerverifikasi
{
    public function handle(Request $request, Closure $next): Response
    {
        $umkm = $request->user()?->umkm;

        // Akun berrole umkm tanpa toko sama sekali. Bisa terjadi pada
        // akun yang dibuat admin secara manual; diantar ke halaman status
        // supaya melihat penjelasan, bukan dasbor yang seluruh angkanya
        // kosong tanpa sebab yang terbaca.
        if ($umkm === null || ! $umkm->bolehBerjualan()) {
            return redirect()->route('umkm.status');
        }

        return $next($request);
    }
}
