{{--
    Email berisi kode OTP.

    Kodenya ditaruh sebagai teks biasa berukuran besar, bukan gambar.
    Kode dalam bentuk gambar tidak bisa disalin, tidak terbaca pembaca
    layar, dan hilang sama sekali ketika klien surel memblokir gambar —
    tiga kegagalan sekaligus pada satu-satunya isi yang penting di sini.
--}}
<x-email.kerangka :pesan-surel="$message ?? null"
                  :pratinjau="$tujuan->subjekEmail().': '.$kode">

    <p style="margin:0 0 16px 0; font-size:17px; font-weight:bold; color:#023628;">
        Halo, {{ $namaPengguna }}
    </p>

    <p style="margin:0 0 24px 0;">
        {{ $tujuan->pembukaPesan() }}
    </p>

    {{-- Kode --}}
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
            <td align="center" style="background-color:#e0f8f1; border-radius:10px; padding:22px 16px;">
                <div style="font-family:'Courier New',Courier,monospace; font-size:34px; font-weight:bold;
                            letter-spacing:8px; color:#023628;">
                    {{ $kode }}
                </div>
                <div style="font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#046A4F; padding-top:8px;">
                    Berlaku {{ $tujuan->masaBerlakuMenit() }} menit
                </div>
            </td>
        </tr>
    </table>

    <p style="margin:24px 0 0 0;">
        Masukkan kode ini di aplikasi {{ config('app.name') }} untuk melanjutkan.
    </p>

    {{-- Peringatan keamanan --}}
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
           style="margin-top:24px;">
        <tr>
            <td style="background-color:#fef2f2; border-left:3px solid #dc2626; border-radius:6px; padding:14px 16px;
                       font-size:13px; line-height:1.6; color:#991b1b;">
                <strong style="display:block; margin-bottom:4px;">Jangan bagikan kode ini kepada siapa pun.</strong>
                Petugas {{ config('app.name') }} tidak akan pernah menanyakannya, termasuk lewat telepon
                atau WhatsApp. Kalau Anda tidak meminta kode ini, abaikan saja email ini dan kata
                sandi Anda tetap aman.
            </td>
        </tr>
    </table>

</x-email.kerangka>
