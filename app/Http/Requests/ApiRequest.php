<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Induk seluruh Form Request kanal API.
 *
 * Menyediakan pesan validasi berbahasa Indonesia yang berlaku umum,
 * supaya tiap Request tidak perlu mengulangnya. Pesan spesifik tetap
 * bisa ditimpa di kelas turunan.
 *
 * Ingat pembagian tugasnya: Form Request memeriksa **bentuk** masukan,
 * tipe, panjang, keberadaan. Aturan **bisnis**, saldo cukup, status
 * boleh berpindah, wilayah dalam kewenangan, tetap divalidasi di
 * Service, karena jalur web memakai aturan yang sama dan tidak melewati
 * kelas ini.
 */
abstract class ApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'Kolom :attribute wajib diisi.',
            'required_if' => 'Kolom :attribute wajib diisi.',
            'string' => 'Kolom :attribute harus berupa teks.',
            'integer' => 'Kolom :attribute harus berupa angka bulat.',
            'numeric' => 'Kolom :attribute harus berupa angka.',
            'boolean' => 'Kolom :attribute harus bernilai ya atau tidak.',
            'array' => 'Kolom :attribute harus berupa daftar.',
            'email' => 'Format email tidak valid.',
            'unique' => ':attribute sudah terdaftar.',
            'exists' => ':attribute yang dipilih tidak ditemukan.',
            'confirmed' => 'Konfirmasi :attribute tidak cocok.',
            'min' => [
                'string' => ':attribute minimal :min karakter.',
                'numeric' => ':attribute minimal :min.',
                'array' => ':attribute minimal :min item.',
            ],
            'max' => [
                'string' => ':attribute maksimal :max karakter.',
                'numeric' => ':attribute maksimal :max.',
                'file' => 'Ukuran :attribute maksimal :max kilobyte.',
                'array' => ':attribute maksimal :max item.',
            ],
            'between' => [
                'numeric' => ':attribute harus antara :min dan :max.',
            ],
            'image' => ':attribute harus berupa gambar.',
            'mimes' => ':attribute harus berformat :values.',
            'in' => 'Pilihan :attribute tidak valid.',
            'date' => 'Format tanggal :attribute tidak valid.',
            'enum' => 'Pilihan :attribute tidak valid.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'email' => 'email',
            'password' => 'kata sandi',
            'phone' => 'nomor telepon',
            'kategori_id' => 'kategori',
            'wilayah_id' => 'wilayah',
            'produk_id' => 'produk',
            'latitude' => 'titik lokasi',
            'longitude' => 'titik lokasi',
            'no_rekening' => 'nomor rekening',
            'atas_nama' => 'nama pemilik rekening',
            'foto' => 'foto',
            'judul' => 'judul',
            'deskripsi' => 'deskripsi',
            'jumlah' => 'jumlah',
            'qty' => 'jumlah',
            'rating' => 'rating',
        ];
    }
}
