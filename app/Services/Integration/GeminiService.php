<?php
// app/Services/Integration/GeminiService.php

namespace App\Services\Integration;

use Illuminate\Support\Facades\Http;
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

        $systemInstruction = <<<TXT
        Kamu asisten klasifikasi sampah untuk aplikasi Niti Resik di Bali.
        Tugasmu mengklasifikasikan sampah pada gambar ke salah satu kategori yang tersedia,
        lalu memberi langkah pengolahan singkat dan rekomendasi daur ulang.
        Pertimbangkan konteks lokal Bali: bank sampah, TPS3R, dan sampah upakara/canang
        (tergolong organik). Gunakan Bahasa Indonesia yang ringkas dan mudah dipahami warga.
        Jika gambar tidak jelas, kosong, atau bukan sampah, set kategori "tidak_yakin",
        confidence rendah, dan minta pengguna memotret ulang.
        TXT;

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'hasil_jenis' => ['type' => 'STRING', 'description' => 'Nama jenis sampah, mis. "Botol plastik PET"'],
                'kategori' => ['type' => 'STRING', 'enum' => self::KATEGORI],
                'confidence' => ['type' => 'NUMBER', 'description' => '0.0 - 1.0'],
                'langkah_pengolahan' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                'rekomendasi_daur_ulang' => ['type' => 'STRING'],
            ],
            'required' => ['hasil_jenis', 'kategori', 'confidence', 'langkah_pengolahan', 'rekomendasi_daur_ulang'],
        ];

        $result = $this->generateContent([
            'systemInstruction' => ['parts' => [['text' => $systemInstruction]]],
            'contents' => [[
                'role' => 'user',
                'parts' => [
                    ['inline_data' => ['mime_type' => $mime, 'data' => $data]],
                    ['text' => 'Klasifikasikan sampah pada gambar ini.'],
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
     * Chatbot — hanya menjawab topik sampah/lingkungan.
     * $history: [['role' => 'user'|'model', 'text' => '...'], ...]
     */
    public function chat(array $history): string
    {
        $systemInstruction = <<<TXT
        Kamu chatbot Niti Resik, khusus membantu warga Bali soal sampah, daur ulang,
        bank sampah, dan pengelolaan lingkungan. Jawab ringkas dan praktis dalam Bahasa Indonesia.
        Jika pertanyaan di luar topik sampah/lingkungan, tolak dengan sopan dan arahkan kembali
        ke topik pengelolaan sampah. Jangan mengarang informasi.
        TXT;

        $contents = array_map(fn ($m) => [
            'role'  => $m['role'],
            'parts' => [['text' => $m['text']]],
        ], $history);

        return $this->generateContent([
            'systemInstruction' => ['parts' => [['text' => $systemInstruction]]],
            'contents' => $contents,
            'generationConfig' => ['temperature' => 0.7],
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

        $response = Http::withQueryParameters(['key' => config('services.gemini.key')])
            ->timeout(60)
            ->post($url, $payload);

        if ($response->failed()) {
            throw new RuntimeException('Gemini API error: ' . $response->body());
        }

        return $response->json('candidates.0.content.parts.0.text', '');
    }
}