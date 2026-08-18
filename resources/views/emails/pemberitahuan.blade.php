{{--
    Pemberitahuan umum: satu judul, satu isi, satu ajakan opsional.

    Dipakai hasil verifikasi UMKM, dan disiapkan untuk pemberitahuan lain
    yang menyusul. Bentuknya sengaja seragam supaya penerima mengenali
    surel dari Resikita tanpa harus membaca pengirimnya lebih dulu.
--}}
<x-email.kerangka :pesan-surel="$message ?? null" :pratinjau="$ringkas">

    <p style="margin:0 0 16px 0; font-size:17px; font-weight:bold; color:#023628;">
        {{ $judul }}
    </p>

    <p style="margin:0 0 8px 0;">
        Halo, {{ $namaPengguna }}
    </p>

    @foreach ($paragraf as $baris)
        <p style="margin:0 0 14px 0;">{{ $baris }}</p>
    @endforeach

    @if ($actionUrl !== null)
        {{--
            Tombol berbasis tabel, bukan <a> bergaya blok.

            Outlook mengabaikan padding pada tautan sebaris, sehingga
            tombol yang dibuat begitu menyusut menjadi teks bergaris bawah
            di klien yang justru paling banyak dipakai instansi.
        --}}
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0 8px 0;">
            <tr>
                <td align="center" style="background-color:#057D5D; border-radius:10px;">
                    <a href="{{ $actionUrl }}"
                       style="display:inline-block; padding:13px 26px; font-family:Arial,Helvetica,sans-serif;
                              font-size:14px; font-weight:bold; color:#ffffff; text-decoration:none;">
                        {{ $actionLabel }}
                    </a>
                </td>
            </tr>
        </table>

        <p style="margin:12px 0 0 0; font-size:12px; color:#6b7280;">
            Kalau tombol di atas tidak berfungsi, salin tautan ini ke peramban Anda:<br/>
            <span style="color:#046A4F; word-break:break-all;">{{ $actionUrl }}</span>
        </p>
    @endif

</x-email.kerangka>
