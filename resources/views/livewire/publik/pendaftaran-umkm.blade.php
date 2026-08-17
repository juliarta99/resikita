<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
    @if ($terkirim)
        <div class="rounded-2xl border border-primary-200 bg-primary-50/60 p-8 text-center">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-500 text-white">
                <x-ui.ikon nama="centang" class="h-7 w-7"/>
            </span>

            <h1 class="mt-5 text-2xl font-bold tracking-tight text-primary-900">Pendaftaran terkirim</h1>

            <p class="mx-auto mt-3 max-w-xl leading-relaxed text-gray-700">
                <span class="font-semibold">{{ $namaTerdaftar }}</span>
                sudah masuk antrean verifikasi admin Resikita, bersama akun
                <span class="font-medium">{{ $pemilikEmail }}</span>.
            </p>

            {{--
                Dikatakan terus terang, bukan disamarkan. Akun memang belum
                bisa dipakai masuk sampai verifikasi selesai; membiarkan
                pendaftar mencobanya lalu menemui pesan "akun dinonaktifkan"
                akan terbaca sebagai kesalahan sistem, padahal itu memang
                keadaan yang benar.
            --}}
            <div class="mx-auto mt-5 max-w-xl rounded-xl bg-white p-4 text-left">
                <h2 class="text-sm font-semibold text-primary-900">Yang terjadi berikutnya</h2>
                <ol class="mt-2.5 space-y-1.5 text-sm text-gray-600">
                    <li>1. Admin Resikita meninjau data usaha dan alamat Anda.</li>
                    <li>2. Setelah disetujui, akun Anda aktif dan bisa dipakai masuk ke panel penjual.</li>
                    <li>3. Anda menetapkan alamat asal pengiriman, lalu produk boleh diunggah.</li>
                </ol>
                <p class="mt-3 text-xs text-gray-500">
                    Kata sandi Anda sudah tersimpan, tapi akun belum bisa masuk sampai verifikasi
                    selesai, jadi tidak perlu mendaftar ulang. Coba masuk secara berkala, atau
                    hubungi admin Resikita bila terlalu lama belum ada kabar.
                </p>
            </div>

            <div class="mt-7 flex flex-wrap justify-center gap-3">
                <x-ui.tombol :tautan="route('publik.beranda')">Kembali ke beranda</x-ui.tombol>
                <x-ui.tombol jenis="kedua" :tautan="route('publik.produk')">
                    Lihat marketplace
                </x-ui.tombol>
            </div>
        </div>
    @else
        <header class="max-w-2xl">
            <h1 class="text-3xl font-bold tracking-tight text-primary-900">
                Daftarkan UMKM Anda
            </h1>
            <p class="mt-3 leading-relaxed text-gray-600">
                Untuk pelaku usaha yang mengolah sampah menjadi produk bernilai dan ingin
                menjualnya di marketplace Resikita.
            </p>
        </header>

        <div class="mt-6 rounded-2xl border border-gray-200 bg-gray-50 p-5">
            <h2 class="text-sm font-semibold text-primary-900">Yang Anda dapatkan setelah disetujui</h2>
            <ol class="mt-3 space-y-2 text-sm text-gray-600">
                <li>1. Etalase toko di marketplace Resikita, terbuka untuk pembeli tanpa akun.</li>
                <li>2. Pengelolaan produk, stok, pesanan, dan nomor resi dalam satu panel.</li>
                <li>3. Dompet penjual dengan penarikan saldo ke rekening bank Anda.</li>
                <li>4. Asisten konten untuk menyusun caption, tagar, dan sampul produk.</li>
            </ol>
        </div>

        <form wire:submit="daftar" class="mt-8 space-y-8">

            {{-- Usaha --}}
            <fieldset class="space-y-4">
                <legend class="text-base font-semibold text-primary-900">Data usaha</legend>

                <x-ui.bidang label="Nama usaha" untuk="nama-usaha" :wajib="true"
                             petunjuk="Nama yang akan tampil di marketplace."
                             :galat="$errors->first('nama')">
                    <x-ui.isian id="nama-usaha" wire:model="nama"
                                placeholder="Contoh: Kriya Plastik Nusantara"
                                :galat="$errors->has('nama')"/>
                </x-ui.bidang>

                <x-ui.bidang label="Deskripsi usaha" untuk="deskripsi-usaha"
                             petunjuk="Ceritakan bahan baku yang Anda olah dan produk yang dihasilkan."
                             :galat="$errors->first('deskripsi')">
                    <textarea id="deskripsi-usaha" wire:model="deskripsi" rows="4"
                              placeholder="Kami mengolah sampah sachet menjadi tas anyaman dan dompet."
                              class="block w-full rounded-xl border px-3.5 py-2.5 text-sm text-gray-900 shadow-sm
                                     transition placeholder:text-gray-400 focus:outline-none focus:ring-4
                                     {{ $errors->has('deskripsi')
                                        ? 'border-red-300 focus:border-red-500 focus:ring-red-200'
                                        : 'border-gray-300 focus:border-primary-500 focus:ring-primary-100' }}"></textarea>
                </x-ui.bidang>

                <x-ui.bidang label="Alamat usaha" untuk="alamat-usaha" :wajib="true"
                             petunjuk="Alamat tempat produk dikemas dan dikirim."
                             :galat="$errors->first('alamat')">
                    <x-ui.isian id="alamat-usaha" wire:model="alamat"
                                placeholder="Jl. Raya Dalung No. 12, Kuta Utara"
                                :galat="$errors->has('alamat')"/>
                </x-ui.bidang>

                <x-ui.bidang label="Nomor telepon usaha" untuk="phone-usaha"
                             petunjuk="Opsional. Ditampilkan di etalase agar pembeli bisa bertanya."
                             :galat="$errors->first('phone')">
                    <x-ui.isian id="phone-usaha" wire:model="phone" placeholder="08xxxxxxxxxx"
                                :galat="$errors->has('phone')"/>
                </x-ui.bidang>

                <x-ui.bidang label="Foto usaha" untuk="foto-usaha"
                             petunjuk="Opsional. JPG, PNG, atau WEBP. Maksimal 2 MB."
                             :galat="$errors->first('foto')">
                    <input id="foto-usaha" type="file" wire:model="foto" accept="image/*"
                           class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0
                                  file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium
                                  file:text-primary-700 hover:file:bg-primary-100">
                    <div wire:loading wire:target="foto" class="text-xs text-gray-500">Mengunggah…</div>
                    @if ($foto)
                        <p class="text-xs text-primary-700">Foto siap dikirim.</p>
                    @endif
                </x-ui.bidang>
            </fieldset>

            {{-- Wilayah --}}
            <fieldset class="space-y-4 border-t border-gray-100 pt-8">
                <legend class="text-base font-semibold text-primary-900">Lokasi usaha</legend>

                <p class="text-sm text-gray-600">
                    Dipakai untuk menampilkan toko Anda di direktori wilayah. Pilih sedalam yang
                    Anda tahu, makin dalam, makin mudah warga sekitar menemukannya.
                </p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.bidang label="Provinsi" untuk="provinsi" :wajib="true">
                        <x-ui.pilihan id="provinsi" wire:model.live="provinsiId" kosong="Pilih provinsi"
                                      :opsi="$provinsiTersedia->all()"/>
                    </x-ui.bidang>

                    <x-ui.bidang label="Kabupaten/kota" untuk="kabupaten" :wajib="true"
                                 :galat="$errors->first('kabupatenId')">
                        <x-ui.pilihan id="kabupaten" wire:model.live="kabupatenId"
                                      kosong="{{ $provinsiId ? 'Pilih kabupaten/kota' : 'Pilih provinsi dulu' }}"
                                      :opsi="$kabupatenTersedia->all()"
                                      :disabled="$provinsiId === null"
                                      :galat="$errors->has('kabupatenId')"/>
                    </x-ui.bidang>

                    <x-ui.bidang label="Kecamatan" untuk="kecamatan">
                        <x-ui.pilihan id="kecamatan" wire:model.live="kecamatanId"
                                      kosong="{{ $kabupatenId ? 'Tidak perlu / pilih kecamatan' : 'Pilih kabupaten dulu' }}"
                                      :opsi="$kecamatanTersedia->all()"
                                      :disabled="$kabupatenId === null"/>
                    </x-ui.bidang>

                    <x-ui.bidang label="Desa/kelurahan" untuk="desa">
                        <x-ui.pilihan id="desa" wire:model.live="desaId"
                                      kosong="{{ $kecamatanId ? 'Tidak perlu / pilih desa' : 'Pilih kecamatan dulu' }}"
                                      :opsi="$desaTersedia->all()"
                                      :disabled="$kecamatanId === null"/>
                    </x-ui.bidang>
                </div>

                @if ($wilayahDipilih)
                    <div class="rounded-xl bg-primary-50 p-4">
                        <p class="text-sm text-primary-900">
                            Toko akan tercatat di
                            <span class="font-semibold">{{ $wilayahDipilih->namaLengkap() }}</span>.
                        </p>
                    </div>
                @endif
            </fieldset>

            {{-- Pemilik --}}
            <fieldset class="space-y-4 border-t border-gray-100 pt-8">
                <legend class="text-base font-semibold text-primary-900">Akun pemilik</legend>

                <p class="text-sm text-gray-600">
                    Akun ini yang nanti Anda pakai masuk ke panel penjual.
                </p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.bidang label="Nama lengkap pemilik" untuk="pemilik-nama" :wajib="true"
                                 :galat="$errors->first('pemilikNama')">
                        <x-ui.isian id="pemilik-nama" wire:model="pemilikNama"
                                    :galat="$errors->has('pemilikNama')"/>
                    </x-ui.bidang>

                    <x-ui.bidang label="Email" untuk="pemilik-email" :wajib="true"
                                 petunjuk="Dipakai untuk masuk ke panel penjual."
                                 :galat="$errors->first('pemilikEmail')">
                        <x-ui.isian id="pemilik-email" tipe="email" wire:model.blur="pemilikEmail"
                                    :galat="$errors->has('pemilikEmail')"/>
                    </x-ui.bidang>

                    <x-ui.bidang label="Nomor telepon pemilik" untuk="pemilik-phone"
                                 petunjuk="Opsional, untuk konfirmasi lewat WhatsApp."
                                 :galat="$errors->first('pemilikPhone')">
                        <x-ui.isian id="pemilik-phone" wire:model="pemilikPhone" placeholder="08xxxxxxxxxx"
                                    :galat="$errors->has('pemilikPhone')"/>
                    </x-ui.bidang>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.bidang label="Kata sandi" untuk="password" :wajib="true"
                                 petunjuk="Minimal 8 karakter."
                                 :galat="$errors->first('password')">
                        <x-ui.isian id="password" tipe="password" wire:model="password"
                                    autocomplete="new-password"
                                    :galat="$errors->has('password')"/>
                    </x-ui.bidang>

                    <x-ui.bidang label="Ulangi kata sandi" untuk="password-konfirmasi" :wajib="true"
                                 :galat="$errors->first('passwordKonfirmasi')">
                        <x-ui.isian id="password-konfirmasi" tipe="password" wire:model="passwordKonfirmasi"
                                    autocomplete="new-password"
                                    :galat="$errors->has('passwordKonfirmasi')"/>
                    </x-ui.bidang>
                </div>
            </fieldset>

            {{-- Pernyataan --}}
            <fieldset class="space-y-3 border-t border-gray-100 pt-8">
                <legend class="sr-only">Pernyataan</legend>

                <label for="setuju" class="flex items-start gap-3 text-sm text-gray-700">
                    <input id="setuju" type="checkbox" wire:model="setuju"
                           class="mt-0.5 h-4 w-4 flex-none rounded border-gray-300 text-primary-600
                                  focus:ring-4 focus:ring-primary-100">
                    <span>
                        Saya menyatakan data di atas benar dan usaha ini benar-benar mengolah
                        atau memanfaatkan bahan daur ulang.
                    </span>
                </label>

                @error('setuju')
                    <p class="text-xs font-medium text-red-600">{{ $message }}</p>
                @enderror
            </fieldset>

            <div class="flex flex-wrap items-center gap-3 border-t border-gray-100 pt-6">
                <x-ui.tombol tipe="submit" wire:loading.attr="disabled" wire:target="daftar,foto">
                    <span wire:loading.remove wire:target="daftar">Kirim pendaftaran</span>
                    <span wire:loading wire:target="daftar">Mengirim…</span>
                </x-ui.tombol>

                <p class="text-xs text-gray-500">
                    Toko baru bisa berjualan setelah diverifikasi admin Resikita.
                </p>
            </div>

            <p class="text-sm text-gray-600">
                Sudah punya akun penjual?
                <a href="{{ route('masuk') }}" class="font-medium text-primary-700 hover:text-primary-900">
                    Masuk ke panel
                </a>
            </p>
        </form>
    @endif
</div>
