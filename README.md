# Resikita

Platform ekonomi sirkular pengelolaan sampah skala nasional. Menyatukan
empat hal yang selama ini berjalan sendiri-sendiri: **laporan warga**,
**penanganan pemerintah daerah**, **setoran ke bank sampah**, dan
**penjualan produk daur ulang oleh UMKM**.

Hasil evolusi dari **Niti Resik**, yang cakupannya Kabupaten Badung,
menjadi produk nasional. GEMASTIK 2026, Divisi Pengembangan Perangkat
Lunak.

Repositori ini berisi **dua kanal masuk dalam satu aplikasi Laravel**:

| Kanal | Untuk | Berkas route |
|---|---|---|
| Web (Livewire) | Pemerintahan, fasilitator wilayah, bank sampah, UMKM, admin, dan halaman publik | `routes/web.php` |
| API (REST) | Aplikasi mobile `resikita-mobile` | `routes/api.php` |

Keduanya memakai `app/Services` yang sama sebagai satu-satunya pemilik
logika bisnis.

**Stack:** PHP 8.4 · Laravel 13 · Livewire 4 + Alpine.js · Tailwind CSS v4
· MySQL 8 · Sanctum (sesi untuk web, token untuk API) · Spatie Permission
· Leaflet.

---

## Menjalankan secara lokal

**Yang dibutuhkan:** PHP 8.4, Composer, Node 20+, MySQL 8, dan ekstensi
PHP `gd` (dipakai `SampulService` untuk menyusun sampul produk).

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate
```

Buat basis datanya, lalu setel `DB_DATABASE` di `.env`:

```sql
CREATE DATABASE resikita CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE resikita_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Basis data kedua khusus untuk uji, lihat [Menjalankan uji](#menjalankan-uji).

```bash
php artisan migrate:fresh --seed
php artisan storage:link

npm run dev        # jendela terpisah
php artisan serve
```

Buka `http://localhost:8000`.

### Masuk ke panel

Seluruh akun demo berkata sandi `password`. Seeder demonya menolak
berjalan ketika `APP_ENV=production`.

| Email | Peran | Untuk melihat |
|---|---|---|
| `superadmin@resikita.id` | Super admin | Verifikasi pengajuan wilayah, master data |
| `admin@resikita.id` | Admin | Verifikasi UMKM, moderasi laporan dan artikel |
| `kabupaten.badung@resikita.id` | Admin kabupaten | Dasbor dan laporan ter-scope wilayah |
| `umkm.sleman@resikita.id` | UMKM | Panel penjual, produk, pesanan, saldo |

**Daftar akun lengkapnya ada di `database/seeders/DemoSeeder.php`** —
sengaja tidak disalin ke sini, karena daftar yang disalin selalu
tertinggal dari seedernya sendiri.

Petugas lapangan dan masyarakat **tidak punya panel web**; keduanya
memakai aplikasi ponsel lewat `/api/v1`. Halaman masuk menolak keduanya
dengan penjelasan itu, bukan dengan pesan kredensial salah.

---

## Data wilayah

`WilayahSeeder` menyemai **seluruh 38 provinsi** dengan kode Kemendagri
dan titik pusatnya, ditambah **contoh** kabupaten sampai desa untuk tiga
daerah dari tiga pulau berbeda. Cukup untuk menjalankan demo dan uji.

Untuk data resmi yang lengkap, 514 kabupaten/kota, lebih dari 7.000
kecamatan, dan sekitar 84.000 desa, pakai perintah impor:

```bash
php artisan wilayah:impor path/ke/wilayah.csv
```

Berkasnya dua kolom tanpa baris judul, persis seperti lampiran
Kemendagri:

```csv
11,ACEH
11.01,KAB. ACEH SELATAN
11.01.01,BAKONGAN
11.01.01.2001,KEUDE BAKONGAN
```

Perintahnya idempoten, jadi pemutakhiran tahunan tinggal dijalankan
ulang dengan berkas baru.

> Koordinat pusat wilayah tidak ada di lampiran Kemendagri. Tanpa
> koordinat, `WilayahResolverService` tidak punya pembanding dan setiap
> laporan berakhir di tangan fasilitator, jadi isi kolom itu dari
> sumber geospasial terpisah bila memakai data lengkap.

---

## Integrasi luar

Semua opsional saat pengembangan. Yang belum dikonfigurasi akan menolak
dengan pesan yang jelas, bukan gagal diam-diam.

| Layanan | Variabel | Dipakai untuk |
|---|---|---|
| Gemini | `GEMINI_API_KEY`, `GEMINI_MODEL` | Klasifikasi sampah, chatbot, rekomendasi, asisten konten |
| Midtrans | `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY` | Pembayaran pesanan dan iuran TPS |
| RajaOngkir (Komerce) | `RAJAONGKIR_API_KEY`, `RAJAONGKIR_ORIGIN_ID` | Ongkos kirim marketplace |
| Fonnte | `FONNTE_TOKEN` | Notifikasi WhatsApp |
| SMTP | `MAIL_MAILER`, `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD` | Kode OTP dan pemberitahuan lewat surel |

Bawaan `MAIL_MAILER` adalah `log`: surel ditulis ke
`storage/logs/laravel.log` dan tidak dikirim ke mana pun. Karena kode OTP
disimpan sebagai hash, berkas log itulah satu-satunya tempat kodenya
terbaca selama SMTP belum disetel.

Pengiriman surel dan WhatsApp berjalan lewat antrean, jadi jalankan juga:

```bash
php artisan queue:work
```

---

## Menjalankan uji

Uji berjalan di **MySQL**, bukan SQLite. Resolusi wilayah dan deteksi
laporan kembar menghitung jarak Haversine di dalam query
(`radians`, `acos`, `cos`, `sin`); SQLite bawaan PHP tidak menyediakan
fungsi-fungsi itu, sehingga uji di SQLite akan menguji jalur kode yang
berbeda dari yang benar-benar berjalan di produksi.

```bash
php artisan test
```

Panggilan HTTP ke luar selalu dipalsukan. Tidak ada uji yang menghubungi
Gemini, Midtrans, RajaOngkir, atau Fonnte sungguhan.

---

## Perintah yang sering dipakai

```bash
php artisan test                       # seluruh uji
./vendor/bin/pint --dirty              # rapikan gaya kode yang berubah
php artisan migrate:fresh --seed       # pasang ulang dari nol
php artisan wilayah:impor berkas.csv   # impor wilayah resmi
php artisan queue:work                 # proses antrean surel dan WhatsApp
npm run build                          # bangun aset produksi
```
