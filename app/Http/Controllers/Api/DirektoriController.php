<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankSampah;
use App\Models\Product;
use App\Models\Report;
use App\Models\Tps;
use App\Models\TpsMember;
use App\Models\TpsSubscription;
use App\Models\Umkm;
use App\Models\WastePrice;
use App\Services\Integration\MidtransService;
use Illuminate\Http\Request;

class DirektoriController extends Controller
{
    /** Normalisasi koordinat: null tetap null, selain itu float. Cegah null → 0.0. */
    private function num($v): ?float
    {
        return ($v === null || $v === '') ? null : (float) $v;
    }

    /** Tambah jarak (km) & urutkan bila query near_lat/near_lng diberikan. */
    private function withNearby(Request $request, $collection)
    {
        $lat = $request->query('near_lat');
        $lng = $request->query('near_lng');
        if ($lat === null || $lng === null) {
            return $collection->values();
        }

        return $collection->map(function ($i) use ($lat, $lng) {
            if ($i['lat'] !== null && $i['lng'] !== null) {
                $i['jarak_km'] = round($this->haversine((float) $lat, (float) $lng, (float) $i['lat'], (float) $i['lng']), 2);
            } else {
                $i['jarak_km'] = null;
            }
            return $i;
        })->sortBy(fn ($i) => $i['jarak_km'] ?? INF)->values();
    }

    private function haversine($lat1, $lng1, $lat2, $lng2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /** Jarak (km) dari titik near_lat/near_lng query, atau null. */
    private function jarakDari(Request $request, $lat, $lng): ?float
    {
        $nlat = $request->query('near_lat');
        $nlng = $request->query('near_lng');
        if ($nlat === null || $nlng === null || $lat === null || $lng === null) {
            return null;
        }
        return round($this->haversine((float) $nlat, (float) $nlng, (float) $lat, (float) $lng), 2);
    }

    public function tps(Request $request)
    {
        $data = Tps::orderBy('nama')->get()->map(fn ($t) => [
            'id' => $t->id, 'nama' => $t->nama, 'alamat' => $t->alamat, 'no_hp' => $t->no_hp,
            'lat' => $this->num($t->lat), 'lng' => $this->num($t->lng),
            'berbayar' => (bool) $t->is_berbayar, 'tarif' => (float) $t->tarif,
            'foto' => $t->foto ? asset('storage/' . $t->foto) : null,
        ]);

        return response()->json(['data' => $this->withNearby($request, $data)]);
    }

    public function bankSampah(Request $request)
    {
        $data = BankSampah::orderBy('nama')->get()->map(fn ($b) => [
            'id' => $b->id, 'nama' => $b->nama, 'alamat' => $b->alamat, 'no_hp' => $b->no_hp,
            'lat' => $this->num($b->lat), 'lng' => $this->num($b->lng),
            'foto' => $b->foto ? asset('storage/' . $b->foto) : null,
        ]);

        return response()->json(['data' => $this->withNearby($request, $data)]);
    }

    public function umkm(Request $request)
    {
        $data = Umkm::where('status', 'aktif')->withCount('products')->orderBy('nama')->get()->map(fn ($u) => [
            'id' => $u->id, 'nama' => $u->nama, 'deskripsi' => $u->deskripsi, 'alamat' => $u->alamat,
            'lat' => $this->num($u->lat), 'lng' => $this->num($u->lng), 'jumlah_produk' => $u->products_count,
            'foto' => $u->foto ? asset('storage/' . $u->foto) : null,
        ]);

        return response()->json(['data' => $this->withNearby($request, $data)]);
    }

    public function umkmDetail(Umkm $umkm)
    {
        $produk = Product::where('umkm_id', $umkm->id)->where('is_active', true)->with('images')->latest()->get()
            ->map(fn ($p) => [
                'id' => $p->id, 'nama' => $p->nama, 'harga' => (float) $p->harga, 'stok' => $p->stok,
                'deskripsi' => $p->deskripsi,
                'gambar' => $p->images->map(fn ($im) => asset('storage/' . $im->path))->values(),
            ]);

        $stat = \App\Models\Review::where('umkm_id', $umkm->id)
            ->selectRaw('AVG(rating) as avg, COUNT(*) as cnt')->first();

        $ulasan = \App\Models\Review::where('umkm_id', $umkm->id)->with('user')->latest()->take(10)->get()
            ->map(fn ($r) => [
                'id' => $r->id, 'nama' => $r->user?->name ?? 'Pengguna',
                'rating' => (int) $r->rating, 'komentar' => $r->komentar,
                'tanggal' => $r->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => [
            'id' => $umkm->id, 'nama' => $umkm->nama, 'deskripsi' => $umkm->deskripsi,
            'alamat' => $umkm->alamat, 'no_hp' => $umkm->no_hp,
            'lat' => $this->num($umkm->lat), 'lng' => $this->num($umkm->lng),
            'foto' => $umkm->foto ? asset('storage/' . $umkm->foto) : null,
            'rating' => round((float) ($stat->avg ?? 0), 1),
            'jumlah_ulasan' => (int) ($stat->cnt ?? 0),
            'ulasan' => $ulasan,
            'produk' => $produk,
        ]]);
    }

    /** Detail TPS (publik). */
    public function tpsDetail(Request $request, Tps $tps)
    {
        $nasabah = TpsMember::where('tps_id', $tps->id)->where('status', 'aktif')->count();

        return response()->json(['data' => [
            'id' => $tps->id, 'nama' => $tps->nama, 'alamat' => $tps->alamat, 'no_hp' => $tps->no_hp,
            'lat' => $this->num($tps->lat), 'lng' => $this->num($tps->lng),
            'berbayar' => (bool) $tps->is_berbayar, 'tarif' => (float) $tps->tarif,
            'jumlah_nasabah' => $nasabah,
            'foto' => $tps->foto ? asset('storage/' . $tps->foto) : null,
            'jarak_km' => $this->jarakDari($request, $tps->lat, $tps->lng),
        ]]);
    }

    /** Detail Bank Sampah (publik). */
    public function bankSampahDetail(Request $request, BankSampah $bankSampah)
    {
        $nasabah = $bankSampah->deposits()->distinct('nasabah_id')->count('nasabah_id');

        return response()->json(['data' => [
            'id' => $bankSampah->id, 'nama' => $bankSampah->nama, 'alamat' => $bankSampah->alamat, 'no_hp' => $bankSampah->no_hp,
            'lat' => $this->num($bankSampah->lat), 'lng' => $this->num($bankSampah->lng),
            'jumlah_nasabah' => $nasabah,
            'foto' => $bankSampah->foto ? asset('storage/' . $bankSampah->foto) : null,
            'jarak_km' => $this->jarakDari($request, $bankSampah->lat, $bankSampah->lng),
        ]]);
    }

    private function tpsMini(Tps $t): array
    {
        return ['id' => $t->id, 'nama' => $t->nama, 'berbayar' => (bool) $t->is_berbayar, 'tarif' => (float) $t->tarif];
    }

    /**
     * Daftar menjadi nasabah TPS.
     * - TPS gratis  → langsung aktif.
     * - TPS berbayar → buat langganan (menunggu) + Snap token Midtrans; aktif setelah pembayaran terkonfirmasi via webhook.
     */
    public function gabungTps(Request $request, Tps $tps, MidtransService $midtrans)
    {
        $user = $request->user();
        $member = TpsMember::firstOrNew(['tps_id' => $tps->id, 'user_id' => $user->id]);
        $baru = ! $member->exists;

        // Gratis → langsung aktif
        if (! $tps->is_berbayar) {
            $member->status = 'aktif';
            if ($baru) {
                $member->joined_at = now();
            }
            $member->save();

            return response()->json([
                'message' => 'Berhasil terdaftar sebagai nasabah TPS.',
                'data'    => ['berbayar' => false, 'status' => 'aktif', 'tps' => $this->tpsMini($tps)],
            ], $baru ? 201 : 200);
        }

        // Sudah aktif → tidak perlu bayar lagi
        if ($member->exists && $member->status === 'aktif') {
            return response()->json([
                'message' => 'Anda sudah menjadi nasabah aktif TPS ini.',
                'data'    => ['berbayar' => true, 'status' => 'aktif', 'tps' => $this->tpsMini($tps)],
            ], 200);
        }

        // Berbayar → buat membership pending + langganan + Snap token
        if ($baru) {
            $member->joined_at = now();
        }
        $member->status = 'nonaktif'; // aktif setelah pembayaran
        $member->save();

        $sub = TpsSubscription::create([
            'tps_member_id' => $member->id,
            'periode'       => now()->format('Y-m'),
            'jumlah'        => $tps->tarif,
            'status'        => 'menunggu',
            'metode_bayar'  => 'midtrans',
        ]);

        try {
            $snap = $midtrans->createSnapToken(
                'TPSSUB-' . $sub->id . '-' . now()->timestamp,
                (int) round($tps->tarif),
                ['first_name' => $user->name, 'email' => $user->email ?: ('user' . $user->id . '@nitiresik.id'), 'phone' => $user->phone],
                [['id' => 'IURAN', 'price' => (int) round($tps->tarif), 'quantity' => 1, 'name' => 'Iuran TPS ' . mb_substr($tps->nama, 0, 40)]],
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Gagal membuat pembayaran. Coba lagi.'], 503);
        }

        return response()->json([
            'message' => 'Selesaikan pembayaran iuran untuk mengaktifkan keanggotaan.',
            'data'    => [
                'berbayar' => true, 'status' => 'menunggu_bayar', 'tps' => $this->tpsMini($tps),
                'subscription_id' => $sub->id, 'jumlah' => (float) $tps->tarif, 'snap_token' => $snap,
            ],
        ], 201);
    }

    /** Daftar keanggotaan TPS milik pengguna. */
    public function tpsSaya(Request $request)
    {
        $data = TpsMember::with('tps')->where('user_id', $request->user()->id)->get()
            ->map(fn ($m) => [
                'id'       => $m->id,
                'status'   => $m->status,
                'joined_at' => $m->joined_at?->toIso8601String(),
                'tps'      => $m->tps ? [
                    'id' => $m->tps->id, 'nama' => $m->tps->nama, 'alamat' => $m->tps->alamat,
                    'berbayar' => (bool) $m->tps->is_berbayar, 'tarif' => (float) $m->tps->tarif,
                ] : null,
            ]);

        return response()->json(['data' => $data]);
    }

    /** Laporan yang tampil di peta (menyembunyikan yang menunggu/ditolak). */
    public function petaLaporan(Request $request)
    {
        $data = Report::with('kategori')
            ->whereIn('status', ['diverifikasi', 'ditugaskan', 'proses', 'selesai'])
            ->whereNotNull('lat')->whereNotNull('lng')
            ->latest()->limit(300)->get()
            ->map(fn ($r) => [
                'id' => $r->id, 'judul' => $r->judul, 'kategori' => $r->kategori?->nama,
                'status' => $r->status,
                'lat' => $this->num($r->lat), 'lng' => $this->num($r->lng),
                'alamat' => $r->alamat,
            ]);

        return response()->json(['data' => $this->withNearby($request, $data)]);
    }

    public function hargaSampah(Request $request)
    {
        $rows = WastePrice::where('is_active', true)->orderBy('jenis_sampah')->get();

        $map = fn ($h) => [
            'id' => $h->id, 'jenis_sampah' => $h->jenis_sampah, 'satuan' => $h->satuan,
            'harga_per_kg' => (float) $h->harga_per_kg,
            'kategori' => $this->kategoriHarga($h->jenis_sampah),
        ];

        // ?grouped=1 -> dikelompokkan per kategori (untuk katalog Bank Sampah)
        if ($request->boolean('grouped')) {
            $data = $rows->map($map)->groupBy('kategori')
                ->map(fn ($items, $kategori) => [
                    'kategori' => $kategori,
                    'items'    => $items->values(),
                ])->values();

            return response()->json(['data' => $data]);
        }

        return response()->json(['data' => $rows->map($map)->values()]);
    }

    /** Tebak kategori dari nama jenis sampah (waste_prices tak punya kolom kategori). */
    private function kategoriHarga(string $jenis): string
    {
        $j = strtolower($jenis);

        return match (true) {
            str_contains($j, 'kertas') || str_contains($j, 'kardus') || str_contains($j, 'koran')
                || str_contains($j, 'duplex') || str_contains($j, 'majalah') || str_contains($j, 'buku') => 'Kertas',
            str_contains($j, 'plastik') || str_contains($j, 'pet') || str_contains($j, 'botol')
                || str_contains($j, 'gelas') || str_contains($j, 'hdpe') || str_contains($j, 'kresek') => 'Plastik',
            str_contains($j, 'logam') || str_contains($j, 'kaleng') || str_contains($j, 'aluminium')
                || str_contains($j, 'besi') || str_contains($j, 'tembaga') => 'Logam',
            str_contains($j, 'kaca') || str_contains($j, 'beling') => 'Kaca',
            str_contains($j, 'organik') || str_contains($j, 'kompos') => 'Organik',
            str_contains($j, 'b3') || str_contains($j, 'baterai') || str_contains($j, 'elektronik') => 'B3',
            default => 'Lainnya',
        };
    }
}