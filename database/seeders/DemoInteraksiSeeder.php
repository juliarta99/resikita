<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ChannelNotifikasi;
use App\Enums\KategoriSampah;
use App\Enums\NadaKonten;
use App\Enums\PeranChat;
use App\Enums\StatusNotifikasi;
use App\Enums\SumberInput;
use App\Enums\TujuanKonten;
use App\Models\ChatPesan;
use App\Models\ChatSesi;
use App\Models\KlasifikasiSampah;
use App\Models\KontenPromosi;
use App\Models\LogAktivitas;
use App\Models\Notifikasi;
use App\Models\Produk;
use App\Models\RekomendasiAi;
use App\Models\Umkm;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Database\Seeder;

/**
 * Jejak pemakaian fitur: klasifikasi, chatbot, konten, dan notifikasi.
 *
 * Bukan pelengkap tampilan. Tiga kolom di skema ini ada khusus untuk
 * mengukur klaim inklusivitas, `laporan.deskripsi_sumber`,
 * `chat_pesan.sumber_input`, dan `artikel.didengarkan`. Tanpa data yang
 * mengisinya, angka yang seharusnya membuktikan klaim itu selalu nol.
 *
 * Seluruh isi yang seolah berasal dari model AI ditandai apa adanya:
 * `is_ai_generated` bernilai true dan `model_version` diisi penanda demo,
 * bukan nama model sungguhan. Data demo tidak boleh menyamar sebagai
 * keluaran yang benar-benar pernah dihasilkan.
 */
class DemoInteraksiSeeder extends Seeder
{
    private const MODEL_DEMO = 'demo-seeder';

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoInteraksiSeeder tidak boleh dijalankan di produksi.');

            return;
        }

        $this->semaiKlasifikasi();
        $this->semaiChat();
        $this->semaiNotifikasi();
        $this->semaiKontenPromosi();
        $this->semaiRekomendasi();
        $this->semaiLogAktivitas();

        $this->command?->info('Jejak pemakaian fitur siap: klasifikasi, chatbot, konten, notifikasi.');
    }

    private function semaiKlasifikasi(): void
    {
        $daftar = [
            [
                'warga.kadek@resikita.id',
                'Botol air mineral 600 ml',
                KategoriSampah::Anorganik,
                'PET',
                96.4,
                true,
                0.024,
                84,
                ['Kosongkan sisa air', 'Lepas label dan tutupnya', 'Bilas lalu keringkan', 'Pipihkan agar hemat tempat'],
                'Botol PET bening termasuk yang paling tinggi nilainya di bank sampah. Kumpulkan terpisah dari botol berwarna.',
            ],
            [
                'warga.kadek@resikita.id',
                'Kemasan sachet kopi',
                KategoriSampah::Residu,
                'Multilayer',
                88.1,
                false,
                0.003,
                0,
                ['Gunting salah satu sisi', 'Bilas sisa bubuk', 'Keringkan sepenuhnya', 'Kumpulkan untuk kerajinan'],
                'Sachet berlapis aluminium hampir tidak bisa didaur ulang secara mesin. Nilainya justru muncul di tangan perajin anyaman.',
            ],
            [
                'warga.wayan@resikita.id',
                'Kardus bekas paket',
                KategoriSampah::Anorganik,
                'Kertas gelombang',
                97.8,
                true,
                0.35,
                700,
                ['Lepas selotip dan label pengiriman', 'Buka lipatannya', 'Simpan di tempat kering'],
                'Kardus basah kehilangan hampir seluruh nilainya. Simpan tidak menumpuk langsung di lantai.',
            ],
            [
                'warga.siti@resikita.id',
                'Baterai AA bekas',
                KategoriSampah::B3,
                'Seng-karbon',
                93.2,
                false,
                0.023,
                0,
                ['Jangan dibuang ke sampah harian', 'Bungkus terminalnya dengan selotip', 'Simpan di wadah tertutup', 'Serahkan ke titik pengumpulan limbah B3'],
                'Satu baterai bocor mencemari tanah jauh melebihi ukurannya. Jangan pernah dibakar.',
            ],
            [
                'warga.budi@resikita.id',
                'Sisa sayur dan kulit buah',
                KategoriSampah::Organik,
                null,
                98.9,
                true,
                1.2,
                0,
                ['Tiriskan airnya', 'Cacah agar cepat terurai', 'Masukkan ke komposter berselang bahan kering'],
                'Sisa dapur adalah bahan kompos paling mudah didapat. Hindari daging dan minyak.',
            ],
            [
                'warga.komang@resikita.id',
                'Charger ponsel rusak',
                KategoriSampah::Elektronik,
                'Campuran plastik dan tembaga',
                91.5,
                true,
                0.08,
                640,
                ['Gulung kabelnya', 'Jangan dibakar untuk mengambil tembaganya', 'Serahkan ke bank sampah penerima elektronik'],
                'Membakar kabel melepaskan dioksin. Tembaganya tetap bernilai bila diserahkan utuh.',
            ],
        ];

        foreach ($daftar as $i => [$email, $jenis, $kategori, $material, $confidence, $daurUlang, $berat, $nilai, $langkah, $rekomendasi]) {
            $user = User::query()->where('email', $email)->first();

            if ($user === null) {
                continue;
            }

            KlasifikasiSampah::firstOrCreate(
                ['user_id' => $user->id, 'jenis' => $jenis],
                [
                    // Berkas fotonya tidak ikut disemai; lihat AKUN-DEMO.md.
                    'foto_path' => 'klasifikasi/contoh-'.($i + 1).'.jpg',
                    'kategori' => $kategori,
                    'material' => $material,
                    'confidence' => $confidence,
                    'dapat_didaur_ulang' => $daurUlang,
                    'estimasi_berat_kg' => $berat,
                    'estimasi_nilai' => $nilai,
                    'langkah_pengolahan' => $langkah,
                    'rekomendasi_daur_ulang' => $rekomendasi,
                    'model_version' => self::MODEL_DEMO,
                ],
            );
        }
    }

    /**
     * Percakapan chatbot, sebagian lewat suara.
     *
     * `sumber_input` dan `dibacakan` yang membedakan demo ini dari
     * tangkapan layar biasa: keduanya kolom yang mengukur pemakaian
     * fitur suara, dan angkanya dipakai untuk membuktikan klaim
     * inklusivitas.
     */
    private function semaiChat(): void
    {
        $percakapan = [
            [
                'warga.kadek@resikita.id',
                'Cara memilah sampah dapur',
                [
                    [PeranChat::User, 'Saya baru mulai memilah. Sebaiknya pakai berapa tempat sampah di rumah?', SumberInput::Ketik, false],
                    [PeranChat::Model, "Dua dulu saja. Satu untuk sisa makanan dan kulit buah, satu lagi untuk kemasan kering yang sudah dibilas.\n\nDua wadah lebih mudah dijaga, dan kebiasaan yang bertahan jauh lebih berguna daripada sistem lengkap yang ditinggalkan setelah sepekan. Setelah terbiasa, tambahkan wadah tertutup khusus baterai dan lampu bekas.\n\nMulai hari ini: sediakan satu wadah kering di dekat tempat cuci piring, lalu bilas kemasan sebelum dimasukkan.", SumberInput::Ketik, false],
                    [PeranChat::User, 'Kalau kemasan sachet kopi bagaimana?', SumberInput::Ketik, false],
                    [PeranChat::Model, "Sachet kopi berlapis aluminium dan plastik sekaligus, sehingga hampir tidak bisa dipisahkan mesin daur ulang. Ia masuk kategori residu.\n\nNilainya justru muncul di tangan perajin. Beberapa UMKM menganyamnya menjadi tas dan tikar.\n\nYang bisa dilakukan sekarang: gunting sisinya, bilas, keringkan, lalu kumpulkan terpisah dan tanyakan ke bank sampah terdekat apakah mereka menerimanya.", SumberInput::Ketik, true],
                ],
            ],
            [
                'warga.budi@resikita.id',
                'Membuang minyak jelantah',
                [
                    [PeranChat::User, 'Minyak jelantah bekas menggoreng sebaiknya diapakan?', SumberInput::Suara, false],
                    [PeranChat::Model, "Jangan dibuang ke wastafel atau saluran air. Minyak membeku di dinding pipa dan menyumbatnya, lalu ikut mencemari badan air.\n\nSaring dulu ampasnya, simpan di botol tertutup, lalu setorkan. Beberapa bank sampah menerima jelantah dan mengolahnya menjadi bahan bakar nabati atau sabun.\n\nLangkah sekarang: siapkan satu botol bekas khusus jelantah di dapur, dan periksa katalog bank sampah terdekat apakah mereka menerimanya.", SumberInput::Suara, true],
                ],
            ],
        ];

        foreach ($percakapan as [$email, $judul, $pesan]) {
            $user = User::query()->where('email', $email)->first();

            if ($user === null) {
                continue;
            }

            $sesi = ChatSesi::firstOrCreate(
                ['user_id' => $user->id, 'judul' => $judul],
                [
                    'wilayah_konteks_id' => $user->wilayah_id,
                    'terakhir_at' => now()->subDays(2),
                ],
            );

            if ($sesi->pesan()->exists()) {
                continue;
            }

            foreach ($pesan as [$peran, $konten, $sumber, $dibacakan]) {
                ChatPesan::create([
                    'sesi_id' => $sesi->id,
                    'role' => $peran,
                    'konten' => $konten,
                    'sumber_input' => $peran === PeranChat::User ? $sumber : null,
                    'dibacakan' => $dibacakan,
                    'model_version' => $peran === PeranChat::Model ? self::MODEL_DEMO : null,
                ]);
            }
        }
    }

    /**
     * Notifikasi in-app.
     *
     * Yang berkaitan dengan verifikasi UMKM tidak ditulis di sini,
     * baris itu sudah terbentuk sendiri saat AkunService menolak
     * pendaftaran di DemoSeeder. Menyalinnya lagi hanya akan
     * menggandakan pemberitahuan yang sama.
     */
    private function semaiNotifikasi(): void
    {
        $daftar = [
            [
                'warga.kadek@resikita.id',
                'laporan.selesai',
                'Laporan Anda sudah ditangani',
                'Laporan "Bangkai kasur dibuang di lahan kosong" ditandai selesai oleh petugas lapangan. Terima kasih sudah melapor.',
                '/laporan',
                StatusNotifikasi::Dibaca,
            ],
            [
                'warga.kadek@resikita.id',
                'setoran.masuk',
                'Saldo bertambah dari setoran',
                'Setoran Anda di Bank Sampah Sari Lestari sudah ditutup dan nilainya masuk ke dompet.',
                null,
                StatusNotifikasi::Terkirim,
            ],
            [
                'warga.wayan@resikita.id',
                'penarikan.selesai',
                'Penarikan saldo selesai',
                'Penarikan Rp 50.000 sudah ditransfer ke rekening BRI atas nama Anda.',
                null,
                StatusNotifikasi::Terkirim,
            ],
            [
                'warga.siti@resikita.id',
                'pesanan.dibayar',
                'Pesanan Anda sedang disiapkan penjual',
                'Pembayaran diterima. Penjual sedang menyiapkan paket Anda.',
                null,
                StatusNotifikasi::Terkirim,
            ],
            [
                'umkm.sleman@resikita.id',
                'pesanan.masuk',
                'Pesanan baru masuk',
                'Ada pesanan baru yang menunggu dikemas. Segera siapkan paketnya agar pembeli tidak menunggu lama.',
                null,
                StatusNotifikasi::Terkirim,
            ],
        ];

        foreach ($daftar as [$email, $tipe, $judul, $pesan, $actionUrl, $status]) {
            $user = User::query()->where('email', $email)->first();

            if ($user === null) {
                continue;
            }

            Notifikasi::firstOrCreate(
                ['user_id' => $user->id, 'tipe' => $tipe, 'judul' => $judul],
                [
                    'channel' => ChannelNotifikasi::Inapp,
                    'pesan' => $pesan,
                    'action_url' => $actionUrl,
                    'status' => $status,
                    'dibaca_at' => $status === StatusNotifikasi::Dibaca ? now()->subDay() : null,
                ],
            );
        }
    }

    /**
     * Hasil Asisten Konten UMKM.
     *
     * `is_ai_generated` wajib true dan labelnya wajib tampil ke pengguna
     * di layar (CLAUDE.md 10.3). Data demo tidak boleh menjadi
     * satu-satunya tempat aturan itu dilanggar.
     */
    private function semaiKontenPromosi(): void
    {
        $rencana = [
            [
                'Rumah Daur Sleman',
                'tas-anyam-sachet-motif-parang',
                TujuanKonten::Instagram,
                NadaKonten::Hangat,
                "Tas ini dulunya seratus lembar sachet kopi yang hampir berakhir di TPA.\n\n".
                "Dianyam tangan oleh ibu-ibu di Ngaglik, satu tas butuh dua hari. Motifnya tidak pernah sama persis, karena bahannya memang tidak pernah seragam.\n\n".
                'Dipakai belanja, dibawa ke kantor, atau sekadar jadi pengingat bahwa sampah punya nilai kalau ada yang mau mengolahnya.',
                ['#daurulang', '#ecofriendly', '#tasanyaman', '#umkmjogja', '#zerowasteindonesia', '#sampahjadiberkah'],
            ],
            [
                'Kriya Sampah Mengwi',
                'lampu-meja-botol-kaca',
                TujuanKonten::DeskripsiProduk,
                NadaKonten::Informatif,
                "Lampu meja dari botol kaca bekas, dipotong dan dihaluskan tangan di Mengwi, Badung.\n\n".
                "Tinggi 28 sentimeter, dudukan kayu jati sisa produksi mebel, kabel dan fitting standar SNI.\n\n".
                'Setiap unit memakai satu botol utuh yang diselamatkan dari aliran sampah kaca. Warna dan gelembung pada kaca berbeda-beda dan bukan cacat produksi.',
                ['#lampuhias', '#daurulangkaca', '#dekorasirumah', '#umkmbali'],
            ],
        ];

        foreach ($rencana as [$namaToko, $slugProduk, $tujuan, $nada, $teks, $hashtag]) {
            $umkm = Umkm::query()->where('nama', $namaToko)->first();
            $produk = Produk::query()->where('slug', $slugProduk)->first();

            if ($umkm === null || $produk === null) {
                continue;
            }

            KontenPromosi::firstOrCreate(
                ['umkm_id' => $umkm->id, 'produk_id' => $produk->id, 'tujuan' => $tujuan],
                [
                    'nada' => $nada,
                    'hasil_teks' => $teks,
                    'hasil_hashtag' => $hashtag,
                    'is_ai_generated' => true,
                    'model_version' => self::MODEL_DEMO,
                    'dipakai' => $tujuan === TujuanKonten::Instagram,
                ],
            );
        }
    }

    private function semaiRekomendasi(): void
    {
        $admin = User::query()->where('email', 'admin@resikita.id')->first();
        $badung = Wilayah::query()->where('kode', '51.03')->first();

        if ($admin === null || $badung === null) {
            return;
        }

        RekomendasiAi::firstOrCreate(
            [
                'scope_type' => 'wilayah',
                'scope_id' => $badung->id,
                'periode' => now()->format('Y-m'),
            ],
            [
                'konten' => "## Ringkasan bulan ini\n\n".
                    "Laporan yang masuk didominasi tumpukan sampah liar di bahu jalan, terkonsentrasi di Mengwi dan Kuta Selatan. Waktu respons rata-rata masih di atas target, terutama pada laporan yang masuk di akhir pekan.\n\n".
                    "## Yang bisa dilakukan\n\n".
                    "- Tambah satu jadwal pengangkutan di Mengwi pada Sabtu pagi, titik paling sering dilaporkan.\n".
                    "- Pasang penanda larangan membuang di lahan kosong yang berulang kali dilaporkan.\n".
                    "- Ajak dua bank sampah aktif memperluas jam layanan akhir pekan, karena setoran menumpuk di hari kerja.\n\n".
                    '*Disusun otomatis dari data laporan dan setoran. Periksa sebelum dijadikan dasar keputusan.*',
                'raw_response' => null,
                'dibuat_oleh' => $admin->id,
            ],
        );
    }

    private function semaiLogAktivitas(): void
    {
        $admin = User::query()->where('email', 'admin@resikita.id')->first();
        $adminKab = User::query()->where('email', 'kabupaten.badung@resikita.id')->first();

        if ($admin === null || $adminKab === null) {
            return;
        }

        $daftar = [
            [$admin, 'umkm.tolak', 'Menolak pendaftaran Daur Ulang Kupang Mandiri karena alamat usaha belum lengkap.'],
            [$admin, 'artikel.terbit', 'Menerbitkan artikel "Ekonomi Sirkular Bukan Sekadar Daur Ulang".'],
            [$adminKab, 'laporan.verifikasi', 'Memverifikasi enam laporan warga di Kabupaten Badung.'],
            [$adminKab, 'petugas.tugaskan', 'Menugaskan petugas lapangan untuk laporan TPS penuh di Mengwi.'],
        ];

        foreach ($daftar as [$user, $aksi, $deskripsi]) {
            LogAktivitas::firstOrCreate(
                ['user_id' => $user->id, 'aksi' => $aksi, 'deskripsi' => $deskripsi],
                [
                    'ip_address' => '103.28.14.'.random_int(10, 250),
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/126.0 Safari/537.36',
                ],
            );
        }
    }
}
