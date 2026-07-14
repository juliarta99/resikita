<?php

namespace App\Services\Integration;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiService
{
    /** Kategori sampah yang diizinkan (konteks Bali). */
    private const KATEGORI = [
        'organik',            // sisa makanan, daun, canang/upakara organik
        'anorganik_plastik',
        'anorganik_kertas',
        'anorganik_logam',
        'anorganik_kaca',
        'b3',                 // bahan berbahaya & beracun: baterai, lampu, elektronik
        'residu',             // tidak bisa didaur ulang
        'tidak_yakin',        // gambar tidak jelas / bukan sampah
    ];

    /**
     * Klasifikasi sampah dari foto. Mengembalikan array terstruktur.
     */
    public function classifyWaste(string $imagePath): array
    {
        $mime = mime_content_type($imagePath) ?: 'image/jpeg';
        $data = base64_encode(file_get_contents($imagePath));

        $daftarKategori = implode(', ', self::KATEGORI);

        $systemInstruction = <<<TXT
        Kamu adalah ahli pemilahan sampah untuk aplikasi Niti Resik di Kabupaten Badung, Bali.
        Tugas: klasifikasikan objek sampah pada gambar ke SATU kategori dari daftar berikut,
        lalu beri langkah pengolahan praktis dan rekomendasi daur ulang.

        Kategori yang tersedia: {$daftarKategori}.
        Panduan kategori:
        - organik: sisa makanan, daun, ranting, dan sampah upakara/canang (bunga, janur, lontar) — bisa dikompos.
        - anorganik_plastik: botol PET, kantong kresek, gelas plastik, kemasan sachet.
        - anorganik_kertas: kardus, koran, kertas, karton.
        - anorganik_logam: kaleng, besi, aluminium.
        - anorganik_kaca: botol/pecahan kaca.
        - b3: baterai, lampu, elektronik, kaleng bekas cat/pestisida, medis. WAJIB peringatkan penanganan khusus, jangan dibuang sembarangan.
        - residu: styrofoam, popok, puntung rokok, sampah tercampur yang tak bisa didaur ulang.
        - tidak_yakin: gambar buram, gelap, kosong, atau bukan sampah.

        Konteks lokal Bali yang WAJIB dipertimbangkan:
        - Sampah upakara/canang tergolung organik dan sebaiknya dikompos, bukan dibakar.
        - Rujuk alur nyata: pilah di rumah -> setor ke Bank Sampah terdekat (anorganik bernilai) atau TPS3R.
        - Selaras dengan semangat Tri Hita Karana & pengurangan sampah plastik sekali pakai (Pergub Bali 97/2018).

        Aturan jawaban:
        - langkah_pengolahan: 2-4 langkah singkat, mulai kata kerja (mis. "Bilas botol", "Keringkan", "Pipihkan", "Setor ke bank sampah").
        - rekomendasi_daur_ulang: 1-2 kalimat ide pemanfaatan/daur ulang; jika tidak ada, tulis "Tidak dapat didaur ulang, buang ke TPS sebagai residu."
        - dapat_disetor_bank_sampah: true hanya untuk anorganik bernilai (plastik/kertas/logam/kaca), false untuk organik/b3/residu/tidak_yakin.
        - Jika gambar tidak jelas/bukan sampah: kategori "tidak_yakin", confidence rendah, minta pengguna memotret ulang dengan pencahayaan cukup.
        - Gunakan Bahasa Indonesia ringkas yang mudah dipahami warga. Jangan mengarang.
        TXT;

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'hasil_jenis'             => ['type' => 'STRING', 'description' => 'Nama jenis sampah, mis. "Botol plastik PET"'],
                'deskripsi'               => ['type' => 'STRING', 'description' => '1-2 kalimat menjelaskan objek & bahannya.'],
                'kategori'                => ['type' => 'STRING', 'enum' => self::KATEGORI],
                'material'                => ['type' => 'STRING', 'description' => 'Bahan utama: Plastik|Kertas|Logam|Kaca|Makanan|Daun|Elektronik|Baterai|Tekstil|Lainnya'],
                'confidence'              => ['type' => 'NUMBER', 'description' => '0.0 - 1.0'],
                'dapat_disetor_bank_sampah' => ['type' => 'BOOLEAN'],
                'nilai_jual_per_kg'       => ['type' => 'NUMBER', 'description' => 'Estimasi harga jual Rupiah per kg; 0 bila tidak bernilai.'],
                'estimasi_berat_kg'       => ['type' => 'NUMBER', 'description' => 'Estimasi berat objek pada gambar dalam kilogram (mis. 0.025).'],
                'langkah_pengolahan'      => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                'rekomendasi_daur_ulang'  => ['type' => 'STRING'],
                'catatan'                 => ['type' => 'STRING', 'description' => 'Peringatan/keterangan tambahan bila perlu (mis. B3 butuh penanganan khusus).'],
            ],
            'required' => ['hasil_jenis', 'kategori', 'confidence', 'dapat_disetor_bank_sampah', 'langkah_pengolahan', 'rekomendasi_daur_ulang'],
        ];

        $result = $this->generateContent([
            'systemInstruction' => ['parts' => [['text' => $systemInstruction]]],
            'contents' => [[
                'role' => 'user',
                'parts' => [
                    ['inline_data' => ['mime_type' => $mime, 'data' => $data]],
                    ['text' => 'Klasifikasikan sampah pada gambar ini sesuai aturan.'],
                ],
            ]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema'   => $schema,
                'temperature'      => 0.2,
            ],
        ]);

        $parsed = json_decode($result, true);

        if (! is_array($parsed)) {
            throw new RuntimeException('Respons klasifikasi Gemini tidak valid.');
        }

        return $parsed;
    }

    /**
     * Chatbot — hanya menjawab topik sampah/lingkungan (konteks Bali).
     * $history: [['role' => 'user'|'model', 'text' => '...'], ...]
     */
    public function chat(array $history): string
    {
        $systemInstruction = <<<TXT
        Kamu adalah "Chatbot Niti Resik", asisten aplikasi Niti Resik untuk warga Kabupaten Badung, Bali.
        RUANG LINGKUP: hanya membantu soal sampah, daur ulang, kompos, bank sampah, TPS/TPS3R,
        pemilahan, pengurangan plastik, iuran/retribusi sampah, dan pengelolaan lingkungan di Bali.

        Konteks lokal yang harus kamu pahami:
        - Budaya Bali: sampah upakara/canang (organik) sebaiknya dikompos, bukan dibakar; nilai Tri Hita Karana (harmoni manusia-alam-Tuhan).
        - Regulasi: Pergub Bali No. 97/2018 (pembatasan sampah plastik sekali pakai), semangat Bali bebas sampah plastik.
        - Alur nyata di app: pilah di rumah -> setor ke Bank Sampah (dapat saldo) -> tukar/tarik saldo; laporkan tumpukan/pembakaran sampah lewat fitur Lapor; belanja produk daur ulang di marketplace.
        - Istilah lokal: banjar, desa adat, TPS3R, bank sampah unit.

        GAYA & FORMAT JAWABAN:
        - Bahasa Indonesia ringkas, ramah, dan praktis. Langsung ke inti; hindari basa-basi & pembuka klise ("Tentu!", "Sebagai AI...", "Dengan senang hati").
        - Jangan menyebut dirimu AI/model. Jawab seolah petugas ramah yang paham lapangan.
        - Mudah dibaca: paragraf pendek (maks 2-3 kalimat). Untuk langkah/daftar, gunakan poin "-" atau penomoran "1." "2.".
        - Tebalkan istilah penting seperlunya dengan **teks tebal** (jangan berlebihan).
        - Fokus & tidak bertele-tele (idealnya 3-6 poin/kalimat). Jangan gunakan judul markdown (#), tabel, atau blok kode.

        BATASAN:
        - Jika pertanyaan DI LUAR topik sampah/lingkungan (mis. politik umum, gosip, coding, hal pribadi),
          tolak dengan sopan dalam 1 kalimat dan arahkan kembali ke topik pengelolaan sampah. Jangan menjawab isinya.
        - Jangan mengarang data, angka, atau aturan yang tidak kamu yakini. Jika tidak tahu, katakan jujur dan sarankan menghubungi bank sampah/desa setempat.
        TXT;

        $contents = array_map(fn ($m) => [
            'role'  => $m['role'],
            'parts' => [['text' => $m['text']]],
        ], $history);

        return $this->generateContent([
            'systemInstruction' => ['parts' => [['text' => $systemInstruction]]],
            'contents' => $contents,
            'generationConfig' => ['temperature' => 0.6],
        ]);
    }

    /**
     * Rekomendasi prioritas untuk dashboard eksekutif / UMKM.
     */
    public function recommend(string $context): string
    {
        return $this->generateContent([
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' =>
                    "Berdasarkan data statistik berikut, berikan 3-5 rekomendasi prioritas "
                    . "yang konkret dan bisa ditindaklanjuti dalam Bahasa Indonesia:\n\n{$context}",
                ]],
            ]],
            'generationConfig' => ['temperature' => 0.4],
        ]);
    }

    /**
     * Panggil endpoint generateContent, kembalikan teks jawaban.
     */
    private function generateContent(array $payload): string
    {
        $model = config('services.gemini.model');
        $url   = config('services.gemini.url') . "/models/{$model}:generateContent";

        try {
            $response = Http::withQueryParameters(['key' => config('services.gemini.key')])
                ->timeout(60)
                ->post($url, $payload);
        } catch (\Throwable $e) {
            Log::warning('Gemini request gagal: ' . $e->getMessage());
            return '';
        }

        if ($response->failed()) {
            // Sering: HTTP 400 (riwayat tidak alternating) atau konten diblok.
            // Jangan lempar exception -> biar chat tetap membalas dengan sopan.
            Log::warning('Gemini API error ' . $response->status() . ': ' . $response->body());
            return '';
        }

        return (string) $response->json('candidates.0.content.parts.0.text', '');
    }
}
