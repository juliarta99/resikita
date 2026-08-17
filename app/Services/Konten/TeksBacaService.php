<?php

declare(strict_types=1);

namespace App\Services\Konten;

use App\Models\Artikel;

/**
 * Menyiapkan versi artikel yang enak didengar.
 *
 * ## Kenapa dibersihkan di peladen
 *
 * Kalau tiap klien membersihkan markdown sendiri, web dan mobile akan
 * menyimpang perlahan, satu membaca "tanda pagar tanda pagar Pemilahan
 * Sampah", yang lain membaca "Pemilahan Sampah", dan tidak ada yang
 * menyadarinya sampai ada yang mendengarkan keduanya. Membersihkan
 * sekali saat artikel disimpan membuat keduanya membaca teks yang sama
 * persis, karena keduanya membaca kolom yang sama.
 *
 * ## Yang dibuang dan kenapa
 *
 * Pembersihan ini bukan sekadar menghapus tanda baca markdown,
 * melainkan menyiapkan teks untuk dibacakan mesin. Tautan dibaca
 * teksnya saja, bukan alamatnya. Gambar diganti keterangannya. Blok
 * kode dibuang seluruhnya, deretan sintaks yang dibacakan huruf demi
 * huruf tidak membantu siapa pun. Tabel juga dibuang karena struktur
 * kolomnya hilang begitu diucapkan.
 */
class TeksBacaService
{
    /** Perkiraan kecepatan baca, dipakai menghitung estimasi waktu. */
    private function kataPerMenit(): int
    {
        return (int) config('resikita.artikel.kata_per_menit', 200);
    }

    /**
     * Isi `teks_baca` dan `estimasi_baca_menit` sebuah artikel.
     *
     * Dipanggil setiap kali artikel disimpan.
     */
    public function siapkan(Artikel $artikel): Artikel
    {
        $teks = $this->bersihkan($artikel->konten);

        $artikel->forceFill([
            'teks_baca' => $teks,
            'estimasi_baca_menit' => $this->estimasiMenit($teks),
        ]);

        return $artikel;
    }

    /**
     * Ubah konten markdown atau HTML menjadi teks polos siap dibacakan.
     */
    public function bersihkan(string $konten): string
    {
        $teks = $konten;

        // Blok kode dan kode sebaris dibuang lebih dulu, sebelum aturan
        // lain sempat menafsirkan isinya sebagai markdown.
        $teks = preg_replace('/```.*?```/s', '', $teks) ?? $teks;
        $teks = preg_replace('/~~~.*?~~~/s', '', $teks) ?? $teks;
        $teks = preg_replace('/`([^`]*)`/', '$1', $teks) ?? $teks;

        // Baris tabel dibuang seluruhnya; strukturnya tidak bermakna
        // ketika diucapkan.
        $teks = preg_replace('/^\s*\|.*\|\s*$/m', '', $teks) ?? $teks;

        // Gambar dibacakan keterangannya, tautan dibacakan teksnya.
        $teks = preg_replace('/!\[([^\]]*)\]\([^)]*\)/', '$1', $teks) ?? $teks;
        $teks = preg_replace('/\[([^\]]+)\]\([^)]*\)/', '$1', $teks) ?? $teks;

        // Sisa tag HTML, termasuk iframe video yang tertanam di konten.
        $teks = preg_replace('/<script\b.*?<\/script>/is', '', $teks) ?? $teks;
        $teks = preg_replace('/<style\b.*?<\/style>/is', '', $teks) ?? $teks;
        $teks = preg_replace('/<br\s*\/?>/i', "\n", $teks) ?? $teks;
        $teks = preg_replace('/<\/(p|div|li|h[1-6])>/i', "\n", $teks) ?? $teks;
        $teks = strip_tags($teks);

        // Penanda judul dan kutipan.
        $teks = preg_replace('/^\s{0,3}#{1,6}\s*/m', '', $teks) ?? $teks;
        $teks = preg_replace('/^\s{0,3}>\s?/m', '', $teks) ?? $teks;

        // Garis pemisah.
        $teks = preg_replace('/^\s*([-*_])\1{2,}\s*$/m', '', $teks) ?? $teks;

        // Penanda daftar. Butir daftar diubah menjadi kalimat berdiri
        // sendiri, bukan dibacakan sebagai "tanda hubung".
        $teks = preg_replace('/^\s*[-*+]\s+/m', '', $teks) ?? $teks;
        $teks = preg_replace('/^\s*\d+[.)]\s+/m', '', $teks) ?? $teks;

        // Penekanan tebal dan miring.
        $teks = preg_replace('/(\*\*|__)(.*?)\1/s', '$2', $teks) ?? $teks;
        $teks = preg_replace('/(\*|_)(.*?)\1/s', '$2', $teks) ?? $teks;
        $teks = preg_replace('/~~(.*?)~~/s', '$1', $teks) ?? $teks;

        $teks = html_entity_decode($teks, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Rapikan spasi. Baris kosong ganda dipertahankan sebagai satu
        // jeda paragraf, karena pemutar TTS memakainya untuk mengambil
        // napas di tempat yang wajar.
        $teks = preg_replace('/[ \t]+/', ' ', $teks) ?? $teks;
        $teks = preg_replace('/\n{3,}/', "\n\n", $teks) ?? $teks;
        $teks = preg_replace('/[ \t]+\n/', "\n", $teks) ?? $teks;

        return trim($teks);
    }

    /** Estimasi waktu baca dalam menit, minimal satu. */
    public function estimasiMenit(string $teks): int
    {
        $jumlahKata = str_word_count(strip_tags($teks), 0, 'ÀÁÂÃÄÅàáâãäåÈÉÊËèéêëÌÍÎÏìíîïÒÓÔÕÖòóôõöÙÚÛÜùúûü0123456789');

        return max(1, (int) ceil($jumlahKata / $this->kataPerMenit()));
    }

    /**
     * Teks siap bacakan untuk sebuah artikel.
     *
     * Kalau kolomnya belum terisi, artikel lama yang disimpan sebelum
     * fitur ini ada, pembersihan dilakukan saat itu juga dan hasilnya
     * disimpan, sehingga permintaan berikutnya tidak perlu mengulang.
     */
    public function untukArtikel(Artikel $artikel): string
    {
        if ($artikel->teks_baca !== null && trim($artikel->teks_baca) !== '') {
            return $artikel->teks_baca;
        }

        $this->siapkan($artikel);
        $artikel->save();

        return $artikel->teks_baca ?? '';
    }
}
