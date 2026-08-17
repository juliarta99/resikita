<?php

declare(strict_types=1);

namespace App\Services\Integration;

use App\Enums\KategoriSampah;
use App\Enums\NadaKonten;
use App\Enums\PeranChat;
use App\Enums\TujuanKonten;
use App\Exceptions\AturanBisnisException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Satu-satunya pintu keluar menuju Gemini (CLAUDE.md 10).
 *
 * ## Kenapa dipusatkan
 *
 * Prompt adalah perilaku produk, bukan detail pemanggilan. Kalau setiap
 * pemanggil menulis instruksinya sendiri, chatbot di web dan chatbot di
 * mobile perlahan menjawab dengan aturan berbeda, dan tidak ada tempat
 * tunggal untuk memperbaikinya. Semua instruksi sistem tinggal di kelas
 * ini; pemanggil hanya menyerahkan data.
 *
 * ## Yang berubah dari Niti Resik
 *
 * Versi lama menanam Kabupaten Badung, Pergub Bali 97/2018, dan istilah
 * banjar/desa adat langsung di instruksi sistem. Untuk produk nasional
 * itu bukan sekadar kurang tepat, pengguna di Sumatera akan diarahkan
 * ke aturan yang tidak berlaku di daerahnya. Konteks lokal kini
 * disisipkan per sesi dari `users.wilayah_id`, dan kearifan lokal
 * disebut sebagai contoh keberagaman, bukan sebagai bawaan.
 *
 * Kategori sampah juga tidak lagi daftar string bebas milik kelas ini,
 * melainkan diambil dari enum KategoriSampah supaya skema keluaran AI
 * dan skema basis data tidak mungkin berbeda.
 */
class GeminiService
{
    /**
     * Ambang keyakinan di bawah mana hasil dianggap dugaan.
     *
     * Disimpan di sini karena nilainya menyangkut perilaku model, bukan
     * aturan bisnis Resikita.
     */
    public const AMBANG_KEYAKINAN_RENDAH = 60.0;

    /** Versi model yang dipakai, dicatat ke kolom `model_version`. */
    public function modelVersion(): string
    {
        return (string) config('services.gemini.model');
    }

    public function tersedia(): bool
    {
        return filled(config('services.gemini.key'));
    }

    // ----------------------------------------------------------------
    // Klasifikasi sampah
    // ----------------------------------------------------------------

    /**
     * Klasifikasi satu foto sampah.
     *
     * Teknik: zero-shot image classification lewat LLM multimodal dengan
     * schema-constrained decoding. Keluarannya tetap diperiksa ulang di
     * PHP, `responseSchema` mempersempit ruang jawaban, tapi tidak
     * menjaminnya (CLAUDE.md 10.1).
     *
     * @param  string  $isiGambar  Byte mentah gambar, bukan path
     * @return array<string, mixed> Keluaran mentah model, belum divalidasi
     *
     * @throws AturanBisnisException Bila model tidak menjawab JSON
     */
    public function klasifikasiSampah(string $isiGambar, string $mime): array
    {
        $instruksi = $this->instruksiKlasifikasi();

        $teks = $this->generateContent([
            'systemInstruction' => ['parts' => [['text' => $instruksi]]],
            'contents' => [[
                'role' => 'user',
                'parts' => [
                    ['inline_data' => ['mime_type' => $mime, 'data' => base64_encode($isiGambar)]],
                    ['text' => 'Klasifikasikan sampah pada gambar ini sesuai aturan.'],
                ],
            ]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => $this->skemaKlasifikasi(),
                'temperature' => 0.2,
            ],
        ]);

        $hasil = json_decode($teks, true);

        if (! is_array($hasil)) {
            throw AturanBisnisException::karena(
                'Klasifikasi gagal diproses. Coba ambil ulang foto dengan pencahayaan yang cukup.',
                503,
            );
        }

        return $hasil;
    }

    private function instruksiKlasifikasi(): string
    {
        $panduan = collect(KategoriSampah::cases())
            ->map(fn (KategoriSampah $k): string => "- {$k->value}: {$k->deskripsi()}")
            ->implode("\n");

        return <<<TXT
        Kamu memilah sampah untuk Resikita, platform pengelolaan sampah di Indonesia.
        Tugasmu: tentukan SATU kategori untuk objek sampah pada gambar, lalu beri
        langkah penanganan yang bisa dikerjakan hari itu juga di rumah.

        Kategori yang tersedia:
        {$panduan}

        Panduan penilaian:
        - Pilih kategori berdasarkan bahan utama objek, bukan wadah atau latar belakangnya.
        - Baterai, lampu bekas, obat kedaluwarsa, kemasan pestisida, dan kaleng cat masuk b3.
        - Perangkat elektronik bekas beserta kabel dan komponennya masuk elektronik,
          bukan anorganik, meski badannya plastik atau logam.
        - Styrofoam, popok, puntung rokok, dan sampah tercampur yang tidak bisa
          didaur ulang maupun dikompos masuk residu.
        - confidence adalah angka 0 sampai 100. Gambar buram, gelap, terlalu jauh,
          atau bukan sampah wajib diberi confidence di bawah 40, dan catatan berisi
          permintaan memotret ulang.

        Aturan isi jawaban:
        - jenis: nama objek yang lazim dipakai warga, misalnya "Botol plastik PET",
          bukan istilah teknis daur ulang.
        - langkah_pengolahan: dua sampai empat langkah pendek, masing-masing dimulai
          kata kerja. Contoh: "Bilas botol sampai bersih", "Pipihkan badan botol".
        - dapat_didaur_ulang: true hanya bila objek benar-benar diterima aliran daur
          ulang umum. Organik dikompos, bukan didaur ulang, jadi false.
        - estimasi_nilai_rupiah: perkiraan nilai jual objek pada gambar dalam rupiah
          penuh tanpa titik atau koma. Isi 0 bila tidak bernilai jual.
        - rekomendasi_daur_ulang: satu sampai dua kalimat. Bila tidak dapat didaur
          ulang, jelaskan ke mana seharusnya dibuang.
        - catatan: peringatan penanganan bila kategorinya b3 atau elektronik, atau
          permintaan memotret ulang bila gambar tidak jelas. Selain itu kosongkan.

        Batasan:
        - Jangan menyebut nama daerah, peraturan daerah, atau harga pasar tertentu.
          Harga sampah berbeda jauh antar kota dan berubah tiap bulan.
        - Jangan mengarang angka yang tidak bisa kamu perkirakan dari gambar.
        - Bahasa Indonesia ringkas dan lugas. Hindari singkatan yang tidak lazim,
          karena jawaban ini bisa dibacakan lewat pembaca layar.
        TXT;
    }

    /** @return array<string, mixed> */
    private function skemaKlasifikasi(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'jenis' => ['type' => 'STRING'],
                'kategori' => ['type' => 'STRING', 'enum' => array_column(KategoriSampah::cases(), 'value')],
                'material' => ['type' => 'STRING'],
                'confidence' => ['type' => 'NUMBER'],
                'dapat_didaur_ulang' => ['type' => 'BOOLEAN'],
                'estimasi_berat_kg' => ['type' => 'NUMBER'],
                'estimasi_nilai_rupiah' => ['type' => 'INTEGER'],
                'langkah_pengolahan' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                'rekomendasi_daur_ulang' => ['type' => 'STRING'],
                'catatan' => ['type' => 'STRING'],
            ],
            'required' => [
                'jenis',
                'kategori',
                'confidence',
                'dapat_didaur_ulang',
                'langkah_pengolahan',
                'rekomendasi_daur_ulang',
            ],
        ];
    }

    // ----------------------------------------------------------------
    // Chatbot literasi lingkungan
    // ----------------------------------------------------------------

    /**
     * Jawab satu giliran percakapan.
     *
     * Teknik: domain-scoped prompt engineering dengan context grounding.
     * Bukan RAG, tidak ada pengambilan dokumen di sini, dan istilah itu
     * tidak boleh dipakai selama belum ada (CLAUDE.md 10.2).
     *
     * @param  array<int, array{role: PeranChat|string, konten: string}>  $riwayat
     * @param  string|null  $konteksWilayah  Nama wilayah pengguna, mis. "Kabupaten Sleman"
     */
    public function jawabChat(array $riwayat, ?string $konteksWilayah = null): string
    {
        $contents = array_values(array_map(
            fn (array $p): array => [
                'role' => $p['role'] instanceof PeranChat ? $p['role']->value : (string) $p['role'],
                'parts' => [['text' => $p['konten']]],
            ],
            $riwayat,
        ));

        return $this->generateContent([
            'systemInstruction' => ['parts' => [['text' => $this->instruksiChat($konteksWilayah)]]],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.6,
                // Jawaban dibacakan lewat TTS. Balasan panjang membuat
                // pendengar kehilangan alurnya jauh sebelum selesai.
                'maxOutputTokens' => 800,
            ],
        ]);
    }

    private function instruksiChat(?string $konteksWilayah): string
    {
        /*
         * Konteks wilayah disisipkan sebagai satu blok terpisah, tidak
         * dijahit ke dalam kalimat instruksi dasar. Dengan begitu
         * instruksi dasar tetap netral secara harfiah, tidak ada nama
         * daerah yang tertinggal di sana ketika konteksnya tidak ada.
         */
        $blokWilayah = $konteksWilayah !== null
            ? <<<TXT

            KONTEKS PENGGUNA
            Pengguna berada di {$konteksWilayah}. Sesuaikan contoh dan saran dengan
            keadaan wilayah itu bila kamu yakin. Bila tidak yakin, tetap jawab umum
            dan sarankan menghubungi bank sampah atau pemerintah desa setempat.
            Jangan mengarang nama fasilitas, alamat, atau peraturan daerah di sana.
            TXT
            : <<<'TXT'

            KONTEKS PENGGUNA
            Wilayah pengguna tidak diketahui. Jawab secara umum untuk Indonesia, lalu
            tawarkan menyesuaikan jawaban bila pengguna menyebutkan daerahnya.
            TXT;

        return <<<TXT
        Kamu asisten literasi lingkungan Resikita untuk masyarakat Indonesia.

        PERAN
        Sebisa mungkin setiap jawaban memuat tiga hal: apa persoalannya, kenapa itu
        penting, dan apa yang bisa dilakukan penanya sekarang juga.

        RUANG LINGKUP
        Pemilahan dari sumber, daur ulang, kompos, bank sampah, TPS dan TPS3R, sampah
        elektronik, limbah B3 rumah tangga, pengurangan plastik sekali pakai,
        retribusi sampah, ekonomi sirkular, serta dampak sampah terhadap air, laut,
        udara, dan kesehatan.

        RUJUKAN YANG BOLEH DISEBUT
        Undang-Undang Nomor 18 Tahun 2008 tentang Pengelolaan Sampah.
        Peraturan Pemerintah Nomor 81 Tahun 2012.
        Peraturan Presiden Nomor 97 Tahun 2017 tentang Jakstranas. Sebut persis
        sebagai Peraturan Presiden, bukan Peraturan Pemerintah.
        Peraturan Menteri Lingkungan Hidup dan Kehutanan Nomor 14 Tahun 2021.
        Konsep 3R: kurangi, pakai ulang, daur ulang.
        {$blokWilayah}

        KEARIFAN LOKAL
        Boleh disebut sebagai contoh keberagaman Nusantara ketika relevan: Tri Hita
        Karana dan awig-awig di Bali, sasi di Maluku dan Papua, lubuk larangan di
        Sumatera. Perlakukan semuanya setara. Jangan menjadikan satu daerah sebagai
        rujukan bawaan.

        GAYA
        Bahasa Indonesia ringkas, hangat, praktis. Langsung ke inti tanpa pembuka
        klise. Paragraf pendek, maksimal dua sampai tiga kalimat. Jangan menyebut
        dirimu AI atau model. Jangan memakai judul markdown, tabel, atau blok kode.

        JAWABAN AKAN DIBACAKAN LEWAT PEMBACA SUARA
        Tulis angka dan satuan dengan kata yang wajar diucapkan. Hindari simbol,
        singkatan tidak lazim, dan penomoran bertingkat.

        BATASAN
        Pertanyaan di luar topik lingkungan ditolak sopan dalam satu kalimat, lalu
        arahkan kembali ke pengelolaan sampah. Jangan menjawab isinya.
        Jangan mengarang angka, statistik, atau pasal peraturan. Bila tidak yakin,
        arahkan ke bank sampah, pemerintah desa, atau dinas lingkungan hidup setempat.
        Jangan memberi nasihat medis untuk paparan limbah. Arahkan ke fasilitas
        kesehatan terdekat.
        TXT;
    }

    // ----------------------------------------------------------------
    // Konten promosi UMKM
    // ----------------------------------------------------------------

    /**
     * Draf teks promosi untuk satu produk UMKM.
     *
     * Hasilnya selalu ditandai `is_ai_generated` oleh pemanggil dan
     * label itu wajib tampil ke pengguna (CLAUDE.md 10.3).
     *
     * @param  array{nama: string, deskripsi?: ?string, bahan_baku?: ?string, harga?: ?int, umkm: string}  $produk
     * @return array<string, mixed>
     */
    public function kontenPromosi(array $produk, TujuanKonten $tujuan, NadaKonten $nada): array
    {
        $ringkasan = collect([
            'Nama produk' => $produk['nama'],
            'Penjual' => $produk['umkm'],
            'Bahan baku' => $produk['bahan_baku'] ?? null,
            'Harga rupiah' => isset($produk['harga']) ? (string) $produk['harga'] : null,
            'Keterangan penjual' => $produk['deskripsi'] ?? null,
        ])->filter()->map(fn (string $v, string $k): string => "{$k}: {$v}")->implode("\n");

        $teks = $this->generateContent([
            'systemInstruction' => ['parts' => [['text' => $this->instruksiKonten($tujuan, $nada)]]],
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => "Buat konten promosi untuk produk berikut.\n\n{$ringkasan}"]],
            ]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'teks' => ['type' => 'STRING'],
                        'hashtag' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                    ],
                    'required' => ['teks'],
                ],
                'temperature' => 0.8,
            ],
        ]);

        $hasil = json_decode($teks, true);

        if (! is_array($hasil)) {
            throw AturanBisnisException::karena(
                'Draf konten gagal dibuat. Coba lagi sebentar lagi.',
                503,
            );
        }

        return $hasil;
    }

    /**
     * Instruksi sistem untuk generator konten.
     *
     * Petunjuk nada diambil dari `NadaKonten::instruksi()`, bukan
     * ditulis ulang di sini. Menyalinnya berarti dua tempat yang harus
     * diubah bersamaan setiap kali nada baru ditambahkan, dan yang
     * kedua pasti terlupakan.
     */
    private function instruksiKonten(TujuanKonten $tujuan, NadaKonten $nada): string
    {
        $sasaran = match ($tujuan) {
            TujuanKonten::Instagram => 'Caption Instagram, 3 sampai 5 kalimat, diakhiri ajakan bertindak yang jelas. '
                .'Sertakan 5 sampai 10 hashtag relevan tanpa tanda pagar.',
            TujuanKonten::SampulProduk => 'Teks singkat untuk sampul produk. Baris pertama maksimal 6 kata sebagai '
                .'judul utama, lalu satu kalimat pendukung maksimal 12 kata. Pisahkan keduanya dengan baris baru. '
                .'Tidak perlu hashtag.',
            TujuanKonten::DeskripsiProduk => 'Deskripsi produk untuk halaman katalog, 4 sampai 6 kalimat, '
                .'menjelaskan bahan, ukuran atau kegunaan, dan perawatannya. Tidak perlu hashtag.',
        };

        $gaya = $nada->instruksi();

        return <<<TXT
        Kamu menulis materi promosi untuk pelaku UMKM produk daur ulang di Indonesia.

        SASARAN
        {$sasaran}

        NADA
        {$gaya}

        ATURAN
        Bahasa Indonesia. Jangan mengarang klaim yang tidak ada pada keterangan produk,
        termasuk sertifikasi, jumlah pembeli, penghargaan, atau angka dampak lingkungan.
        Boleh menyebut manfaat daur ulang secara umum, tanpa angka.
        Jangan menyebut harga bila harga tidak diberikan.
        Jangan memakai judul markdown, tabel, atau blok kode.
        TXT;
    }

    // ----------------------------------------------------------------
    // Rekomendasi analitik
    // ----------------------------------------------------------------

    /**
     * Rekomendasi prioritas untuk dasbor pemerintahan atau UMKM.
     *
     * $konteks berisi angka yang sudah dihitung Service pemanggil.
     * Model tidak pernah diminta menghitung sendiri, ia hanya menyusun
     * penafsiran atas angka yang sudah pasti.
     */
    public function rekomendasi(string $konteks, string $peran = 'pemerintah daerah'): string
    {
        $instruksi = <<<TXT
        Kamu penasihat kebijakan pengelolaan sampah untuk {$peran} di Indonesia.
        Berdasarkan data yang diberikan, susun tiga sampai lima rekomendasi prioritas
        yang konkret dan bisa dijalankan dalam tiga bulan ke depan.

        Setiap rekomendasi memuat tindakan yang harus dilakukan dan alasan singkat
        yang menunjuk angka pada data. Urutkan dari yang paling mendesak.

        Jangan mengarang angka di luar data yang diberikan. Bila data tidak cukup
        untuk menyimpulkan sesuatu, katakan begitu dan sebutkan data apa yang perlu
        dikumpulkan lebih dulu. Bahasa Indonesia ringkas, tanpa judul markdown.
        TXT;

        return $this->generateContent([
            'systemInstruction' => ['parts' => [['text' => $instruksi]]],
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => $konteks]],
            ]],
            'generationConfig' => ['temperature' => 0.4],
        ]);
    }

    // ----------------------------------------------------------------
    // Pemanggilan
    // ----------------------------------------------------------------

    /**
     * Panggil generateContent dan kembalikan teks jawabannya.
     *
     * Kegagalan tidak dikembalikan sebagai string kosong seperti di
     * versi lama. String kosong membuat kegagalan jaringan tersimpan ke
     * basis data sebagai jawaban yang sah, pengguna melihat balasan
     * kosong dan tidak ada yang tahu kenapa. Sekarang kegagalan
     * dilemparkan, dan pemanggil memutuskan bagaimana menyampaikannya.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws AturanBisnisException
     */
    private function generateContent(array $payload): string
    {
        if (! $this->tersedia()) {
            throw AturanBisnisException::karena('Layanan AI belum dikonfigurasi pada peladen ini.', 503);
        }

        $model = $this->modelVersion();
        $url = rtrim((string) config('services.gemini.url'), '/')."/models/{$model}:generateContent";

        try {
            $response = Http::withQueryParameters(['key' => config('services.gemini.key')])
                ->timeout(60)
                ->retry(2, 500, throw: false)
                ->post($url, $payload);
        } catch (\Throwable $e) {
            Log::warning('Gemini tidak dapat dihubungi.', ['pesan' => $e->getMessage()]);

            throw AturanBisnisException::karena(
                'Layanan AI sedang tidak dapat dihubungi. Coba lagi beberapa saat lagi.',
                503,
            );
        }

        if ($response->failed()) {
            Log::warning('Gemini menolak permintaan.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw AturanBisnisException::karena(
                'Layanan AI sedang sibuk. Coba lagi beberapa saat lagi.',
                503,
            );
        }

        $teks = trim((string) $response->json('candidates.0.content.parts.0.text', ''));

        if ($teks === '') {
            /*
             * Jawaban kosong hampir selalu berarti konten diblokir
             * penyaring keamanan Gemini, bukan galat jaringan. Alasannya
             * ada di finishReason dan berguna saat menelusuri laporan
             * pengguna yang "chatbotnya diam saja".
             */
            Log::warning('Gemini mengembalikan jawaban kosong.', [
                'finish_reason' => $response->json('candidates.0.finishReason'),
                'prompt_feedback' => $response->json('promptFeedback'),
            ]);

            throw AturanBisnisException::karena(
                'Belum ada jawaban yang bisa diberikan untuk permintaan itu. Coba susun ulang dengan kalimat lain.',
                503,
            );
        }

        return $teks;
    }
}
