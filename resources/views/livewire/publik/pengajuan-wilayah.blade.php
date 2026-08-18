<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
    @if ($terkirim)
        <div class="rounded-2xl border border-primary-200 bg-primary-50/60 p-8 text-center">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-500 text-white">
                <x-ui.ikon nama="centang" class="h-7 w-7"/>
            </span>

            <h1 class="mt-5 text-2xl font-bold tracking-tight text-primary-900">Pengajuan terkirim</h1>

            <p class="mx-auto mt-3 max-w-xl leading-relaxed text-gray-700">
                Permohonan untuk wilayah berkode
                <span class="font-mono font-semibold">{{ $tiketPengajuan }}</span>
                sudah masuk antrean peninjauan. Kami memeriksa surat kewenangan yang Anda unggah,
                lalu menghubungi
                <span class="font-medium">{{ $pemohonEmail }}</span>
                dengan hasilnya.
            </p>

            <p class="mx-auto mt-4 max-w-xl text-sm text-gray-600">
                Setelah disetujui, akun pemerintah daerah diterbitkan otomatis dan seluruh laporan
                warga yang selama ini ditampung fasilitator langsung dialihkan ke dasbor Anda.
            </p>

            <div class="mt-7 flex flex-wrap justify-center gap-3">
                <x-ui.tombol :tautan="route('publik.beranda')">Kembali ke beranda</x-ui.tombol>
                <x-ui.tombol jenis="kedua" :tautan="route('publik.laporan')">
                    Lihat laporan warga
                </x-ui.tombol>
            </div>
        </div>
    @else
        <header class="max-w-2xl">
            <h1 class="text-3xl font-bold tracking-tight text-primary-900">
                Ajukan pendaftaran wilayah
            </h1>
            <p class="mt-3 leading-relaxed text-gray-600">
                Untuk pemerintah kabupaten/kota dan pemerintah desa/kelurahan yang ingin
                daerahnya bergabung dengan Resikita.
            </p>
        </header>

        <div class="mt-6 rounded-2xl border border-gray-200 bg-gray-50 p-5">
            <h2 class="text-sm font-semibold text-primary-900">Yang terjadi setelah disetujui</h2>
            <ol class="mt-3 space-y-2 text-sm text-gray-600">
                <li>1. Wilayah Anda berubah menjadi terverifikasi dan tampil di peta publik.</li>
                <li>2. Akun panel pemerintah daerah diterbitkan untuk email pemohon.</li>
                <li>3. Laporan warga yang selama ini ditampung fasilitator dialihkan ke dasbor Anda.</li>
                <li>4. Anda bisa mendaftarkan petugas lapangan dan mengelola TPS di wilayah itu.</li>
            </ol>
        </div>

        <form wire:submit="ajukan" class="mt-8 space-y-8">

            {{-- Wilayah --}}
            <fieldset class="space-y-4">
                <legend class="text-base font-semibold text-primary-900">Wilayah yang diajukan</legend>

                <p class="text-sm text-gray-600">
                    Pilih sampai tingkat yang Anda wakili. Kecamatan hanya berfungsi sebagai jalan
                    menuju desa, pengelolaan sampah adalah urusan wajib kabupaten/kota menurut
                    Undang-Undang Nomor 18 Tahun 2008, dengan desa/kelurahan sebagai tingkat
                    pelaksana terdekat.
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

                    <x-ui.bidang label="Kecamatan" untuk="kecamatan"
                                 petunjuk="Isi hanya kalau Anda mewakili desa/kelurahan.">
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

                @if ($wilayahTerpilih)
                    <div class="rounded-xl bg-primary-50 p-4">
                        <p class="text-sm text-primary-900">
                            Pengajuan akan dibuat untuk
                            <span class="font-semibold">{{ $wilayahTerpilih->namaLengkap() }}</span>
                            <span class="font-mono text-xs text-primary-700">({{ $wilayahTerpilih->kode }})</span>.
                        </p>
                        @if ($wilayahTerpilih->isTerverifikasi())
                            <p class="mt-1.5 text-sm font-medium text-red-700">
                                Wilayah ini sudah terdaftar di Resikita.
                            </p>
                        @endif
                    </div>
                @endif
            </fieldset>

            {{-- Pemohon --}}
            <fieldset class="space-y-4 border-t border-gray-100 pt-8">
                <legend class="text-base font-semibold text-primary-900">Data pemohon</legend>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.bidang label="Nama lengkap" untuk="pemohon-nama" :wajib="true"
                                 :galat="$errors->first('pemohonNama')">
                        <x-ui.isian id="pemohon-nama" wire:model="pemohonNama"
                                    :galat="$errors->has('pemohonNama')"/>
                    </x-ui.bidang>

                    <x-ui.bidang label="Jabatan" untuk="pemohon-jabatan" :wajib="true"
                                 petunjuk="Contoh: Kepala Dinas Lingkungan Hidup"
                                 :galat="$errors->first('pemohonJabatan')">
                        <x-ui.isian id="pemohon-jabatan" wire:model="pemohonJabatan"
                                    :galat="$errors->has('pemohonJabatan')"/>
                    </x-ui.bidang>

                    <x-ui.bidang label="Email" untuk="pemohon-email" :wajib="true"
                                 petunjuk="Akun panel diterbitkan untuk alamat ini."
                                 :galat="$errors->first('pemohonEmail')">
                        <x-ui.isian id="pemohon-email" tipe="email" wire:model="pemohonEmail"
                                    :galat="$errors->has('pemohonEmail')"/>
                    </x-ui.bidang>

                    <x-ui.bidang label="Nomor telepon" untuk="pemohon-phone"
                                 petunjuk="Opsional, untuk konfirmasi lewat WhatsApp."
                                 :galat="$errors->first('pemohonPhone')">
                        <x-ui.isian id="pemohon-phone" wire:model="pemohonPhone" placeholder="08xxxxxxxxxx"
                                    :galat="$errors->has('pemohonPhone')"/>
                    </x-ui.bidang>
                </div>

                <x-ui.bidang label="Instansi" untuk="instansi" :wajib="true"
                             petunjuk="Nama resmi instansi tempat Anda bertugas."
                             :galat="$errors->first('instansi')">
                    <x-ui.isian id="instansi" wire:model="instansi"
                                placeholder="Dinas Lingkungan Hidup Kabupaten …"
                                :galat="$errors->has('instansi')"/>
                </x-ui.bidang>
            </fieldset>

            {{-- Surat --}}
            <fieldset class="space-y-4 border-t border-gray-100 pt-8">
                <legend class="text-base font-semibold text-primary-900">Bukti kewenangan</legend>

                <x-ui.bidang label="Surat tugas atau surat keterangan" untuk="surat" :wajib="true"
                             petunjuk="PDF, JPG, atau PNG. Maksimal 5 MB. Berkas disimpan tidak publik dan hanya dibuka peninjau."
                             :galat="$errors->first('surat')">
                    <input id="surat" type="file" wire:model="surat" accept=".pdf,image/*"
                           class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0
                                  file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium
                                  file:text-primary-700 hover:file:bg-primary-100">
                    <div wire:loading wire:target="surat" class="text-xs text-gray-500">Mengunggah…</div>
                    @if ($surat)
                        <p class="text-xs text-primary-700">Berkas siap dikirim.</p>
                    @endif
                </x-ui.bidang>
            </fieldset>

            <div class="flex flex-wrap items-center gap-3 border-t border-gray-100 pt-6">
                <x-ui.tombol tipe="submit" wire:loading.attr="disabled" wire:target="ajukan,surat">
                    <span wire:loading.remove wire:target="ajukan">Kirim pengajuan</span>
                    <span wire:loading wire:target="ajukan">Mengirim…</span>
                </x-ui.tombol>

                <p class="text-xs text-gray-500">
                    Peninjauan dilakukan super admin Resikita dan hasilnya dikirim lewat email.
                </p>
            </div>
        </form>
    @endif
</div>
