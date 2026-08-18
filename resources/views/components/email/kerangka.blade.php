@props(['pratinjau' => null, 'pesanSurel' => null])

{{--
    Kerangka seluruh email Resikita.

    ## Kenapa tabel, bukan flexbox

    Klien surel bukan peramban. Outlook di Windows masih menggambar HTML
    dengan mesin Word, yang tidak mengenal flexbox, grid, maupun sebagian
    besar CSS modern. Tata letak berbasis tabel dengan lebar tetap adalah
    satu-satunya yang tampil sama di Gmail, Outlook, Apple Mail, dan
    aplikasi bawaan ponsel.

    ## Kenapa gaya ditulis sebaris

    Gmail membuang blok <style> pada sebagian tampilan, terutama versi
    web yang diteruskan. Gaya yang ditulis di atribut style tidak pernah
    ikut terbuang.

    ## Kenapa logonya disematkan, bukan ditautkan

    Tautan ke gambar di peladen sendiri hanya bisa dibuka kalau
    APP_URL benar-benar terjangkau dari internet, dan banyak klien surel
    memblokir gambar jarak jauh secara bawaan. Logo di sini disematkan ke
    dalam berkas surelnya sendiri lewat $message->embed(), sehingga tetap
    tampil tanpa koneksi keluar dan tanpa izin tambahan dari penerima.

    Warna teks tetap diberikan pada tiap sel: sebagian klien memaksa mode
    gelap dan membalik warna latar tanpa membalik warna teks.
--}}
@php
    /*
     * $pesanSurel adalah $message milik Laravel, diteruskan sebagai prop
     * dari templat surel pemanggil.
     *
     * Ia tidak bisa dibaca langsung di sini: komponen anonim punya
     * cakupan variabelnya sendiri, jadi $message yang tersedia di templat
     * pemanggil tidak ikut masuk ke dalam komponen. Kelalaian itu tidak
     * memunculkan galat apa pun — logonya hanya diam-diam tidak pernah
     * tersemat, dan baru ketahuan saat memeriksa isi surelnya sendiri.
     *
     * Nilainya null saat templat dirender untuk pratinjau di peramban.
     * Di situ logo ditautkan biasa, karena tidak ada surel yang bisa
     * disisipi apa pun.
     */
    $logo = $pesanSurel !== null
        ? $pesanSurel->embed(public_path('images/logo.png'))
        : asset('images/logo.png');
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="color-scheme" content="light"/>
    <meta name="supported-color-schemes" content="light"/>
    <title>{{ config('app.name') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; -webkit-text-size-adjust:100%;">

@if ($pratinjau)
    {{-- Cuplikan yang tampil di daftar kotak masuk, sebelum email dibuka. --}}
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:transparent; height:0; width:0;">
        {{ $pratinjau }}
    </div>
@endif

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
       style="background-color:#f3f4f6;">
    <tr>
        <td align="center" style="padding:24px 12px;">

            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600"
                   style="width:100%; max-width:600px; background-color:#ffffff; border-radius:12px; overflow:hidden;">

                {{-- Kop --}}
                <tr>
                    <td style="background-color:#057D5D; padding:24px 28px;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="padding-right:12px; vertical-align:middle;">
                                    <img src="{{ $logo }}"
                                         width="36" height="36" alt=""
                                         style="display:block; width:36px; height:36px; border:0;"/>
                                </td>
                                <td style="vertical-align:middle;">
                                    <span style="font-family:Arial,Helvetica,sans-serif; font-size:20px; font-weight:bold; color:#ffffff; letter-spacing:-0.2px;">
                                        {{ config('app.name') }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Isi --}}
                <tr>
                    <td style="padding:32px 28px; font-family:Arial,Helvetica,sans-serif; font-size:15px; line-height:1.65; color:#374151;">
                        {{ $slot }}
                    </td>
                </tr>

                {{-- Kaki --}}
                <tr>
                    <td style="background-color:#f9fafb; padding:20px 28px; border-top:1px solid #e5e7eb;
                               font-family:Arial,Helvetica,sans-serif; font-size:12px; line-height:1.6; color:#6b7280;">
                        <p style="margin:0 0 6px 0;">
                            Email ini dikirim otomatis oleh {{ config('app.name') }}, platform ekonomi
                            sirkular pengelolaan sampah. Mohon tidak membalas surel ini.
                        </p>
                        <p style="margin:0; color:#9ca3af;">
                            &copy; {{ now()->year }} {{ config('app.name') }}
                        </p>
                    </td>
                </tr>
            </table>

        </td>
    </tr>
</table>

</body>
</html>
