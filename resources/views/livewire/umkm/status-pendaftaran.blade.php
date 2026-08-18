<div>
    <x-ui.kepala-halaman
        judul="Status pendaftaran"
        keterangan="Keadaan pengajuan toko Anda dan apa yang bisa dilakukan berikutnya."/>

    @if ($umkm === null)
        <x-ui.kartu>
            <x-ui.kosong ikon="peringatan" judul="Akun belum terhubung ke toko"
                         pesan="Akun Anda berperan sebagai UMKM tapi belum tertaut ke toko mana pun. Hubungi admin Resikita."/>
        </x-ui.kartu>
    @else
        {{-- Ringkasan keadaan --}}
        <x-ui.kartu class="mb-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h3 class="text-base font-bold text-primary-900">{{ $umkm->nama }}</h3>
                        <x-ui.lencana :status="$umkm->status"/>
                    </div>

                    @if ($umkm->wilayah)
                        <p class="mt-1.5 text-sm text-gray-500">{{ $umkm->wilayah->namaLengkap() }}</p>
                    @endif
                </div>

                @if ($umkm->ditinjau_at)
                    <p class="text-xs text-gray-500">
                        Ditinjau {{ $umkm->ditinjau_at->translatedFormat('j F Y, H:i') }}
                        @if ($umkm->peninjau)
                            oleh {{ $umkm->peninjau->name }}
                        @endif
                    </p>
                @endif
            </div>
        </x-ui.kartu>

        @if ($umkm->ditolak())
            {{-- Alasan penolakan --}}
            <div class="mb-6 flex gap-3 rounded-2xl border border-red-200 bg-red-50 p-4">
                <x-ui.ikon nama="peringatan" class="mt-0.5 h-5 w-5 flex-none text-red-600"/>
                <div class="min-w-0 text-sm text-red-900">
                    <p class="font-semibold">Pendaftaran belum bisa disetujui</p>

                    @if ($umkm->catatan_verifikasi)
                        <p class="mt-1.5 whitespace-pre-line text-red-800">{{ $umkm->catatan_verifikasi }}</p>
                    @else
                        <p class="mt-1.5 text-red-800">
                            Peninjau tidak mencantumkan alasannya. Hubungi admin Resikita untuk
                            memastikan apa yang perlu diperbaiki.
                        </p>
                    @endif

                    <p class="mt-2.5 text-red-800">
                        Perbaiki data di bawah, lalu ajukan ulang. Toko Anda akan kembali masuk
                        antrean peninjauan.
                    </p>
                </div>
            </div>

            {{-- Perbaikan dan pengajuan ulang --}}
            <x-ui.kartu>
                <form wire:submit="ajukanUlang" class="space-y-5">
                    <x-ui.bidang label="Nama usaha" untuk="nama" :wajib="true"
                                 :galat="$errors->first('nama')">
                        <x-ui.isian id="nama" wire:model="nama" :galat="$errors->has('nama')"/>
                    </x-ui.bidang>

                    <x-ui.bidang label="Deskripsi usaha" untuk="deskripsi"
                                 petunjuk="Ceritakan bahan baku yang Anda olah dan produk yang dihasilkan."
                                 :galat="$errors->first('deskripsi')">
                        <textarea id="deskripsi" wire:model="deskripsi" rows="4"
                                  class="block w-full rounded-xl border px-3.5 py-2.5 text-sm text-gray-900 shadow-sm
                                         transition placeholder:text-gray-400 focus:outline-none focus:ring-4
                                         {{ $errors->has('deskripsi')
                                            ? 'border-red-300 focus:border-red-500 focus:ring-red-200'
                                            : 'border-gray-300 focus:border-primary-500 focus:ring-primary-100' }}"></textarea>
                    </x-ui.bidang>

                    <x-ui.bidang label="Alamat usaha" untuk="alamat" :wajib="true"
                                 :galat="$errors->first('alamat')">
                        <x-ui.isian id="alamat" wire:model="alamat" :galat="$errors->has('alamat')"/>
                    </x-ui.bidang>

                    <x-ui.bidang label="Nomor telepon usaha" untuk="phone"
                                 :galat="$errors->first('phone')">
                        <x-ui.isian id="phone" wire:model="phone" placeholder="08xxxxxxxxxx"
                                    :galat="$errors->has('phone')"/>
                    </x-ui.bidang>

                    <x-ui.bidang label="Ganti foto usaha" untuk="foto"
                                 petunjuk="Opsional. Kosongkan bila foto sekarang sudah sesuai."
                                 :galat="$errors->first('fotoBaru')">
                        <input id="foto" type="file" wire:model="fotoBaru" accept="image/*"
                               class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0
                                      file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium
                                      file:text-primary-700 hover:file:bg-primary-100">
                        <div wire:loading wire:target="fotoBaru" class="text-xs text-gray-500">Mengunggah…</div>
                    </x-ui.bidang>

                    <div class="border-t border-gray-100 pt-5">
                        <x-ui.tombol tipe="submit" wire:loading.attr="disabled" wire:target="ajukanUlang,fotoBaru">
                            <span wire:loading.remove wire:target="ajukanUlang">Ajukan ulang</span>
                            <span wire:loading wire:target="ajukanUlang">Mengirim…</span>
                        </x-ui.tombol>
                    </div>
                </form>
            </x-ui.kartu>
        @else
            {{-- Menunggu peninjauan --}}
            <x-ui.kartu>
                <div class="flex gap-3">
                    <x-ui.ikon nama="info" class="mt-0.5 h-5 w-5 flex-none text-primary-600"/>
                    <div class="text-sm text-gray-700">
                        <p class="font-semibold text-primary-900">Sedang ditinjau admin Resikita</p>
                        <p class="mt-1.5 leading-relaxed">
                            Toko Anda sudah masuk antrean. Selama masa ini panel penjual belum
                            terbuka dan produk belum bisa diunggah, marketplace hanya menampilkan
                            toko yang sudah diperiksa, dan itu yang membuat pembeli mau memesan
                            dari penjual yang belum dikenalnya.
                        </p>
                        <p class="mt-2.5 leading-relaxed">
                            Hasilnya akan muncul di halaman ini. Anda tidak perlu mendaftar ulang.
                        </p>
                    </div>
                </div>
            </x-ui.kartu>
        @endif
    @endif
</div>
