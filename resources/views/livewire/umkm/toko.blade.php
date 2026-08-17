<div>
    <x-ui.kepala-halaman
        judul="Toko"
        keterangan="Identitas toko yang dilihat pembeli, dan titik asal pengiriman paketnya."/>

    @if ($umkm === null)
        <x-ui.kartu>
            <x-ui.kosong ikon="peringatan" judul="Akun belum terhubung ke UMKM"
                         pesan="Hubungi admin Resikita untuk menghubungkan akun Anda."/>
        </x-ui.kartu>
    @else
        @unless ($siapKirim)
            <div class="mb-6 flex gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                <x-ui.ikon nama="peringatan" class="mt-0.5 h-5 w-5 flex-none text-amber-600"/>
                <div class="text-sm text-amber-900">
                    <p class="font-semibold">Alamat asal pengiriman belum ditetapkan</p>
                    <p class="mt-1 text-amber-800">
                        Ongkos kirim dihitung dari lokasi toko Anda, bukan dari satu titik
                        milik platform. Selama ini kosong, produk Anda tidak bisa dimasukkan
                        ke keranjang pembeli.
                    </p>
                </div>
            </div>
        @endunless

        <form wire:submit="simpan" class="grid gap-6 lg:grid-cols-3">
            {{-- Identitas toko --}}
            <x-ui.kartu class="lg:col-span-2" judul="Identitas toko"
                        keterangan="Nama dan keterangan ini yang tampil di marketplace.">
                <div class="space-y-4 px-5 py-5">
                    <x-ui.bidang label="Nama toko" untuk="nama-toko" :wajib="true"
                                 :galat="$errors->first('nama')">
                        <x-ui.isian id="nama-toko" wire:model="nama" :galat="$errors->has('nama')"/>
                    </x-ui.bidang>

                    <x-ui.bidang label="Deskripsi" untuk="deskripsi-toko"
                                 petunjuk="Ceritakan bahan daur ulang yang Anda olah, itu alasan pembeli datang ke sini."
                                 :galat="$errors->first('deskripsi')">
                        <textarea id="deskripsi-toko" wire:model="deskripsi" rows="4"
                                  class="block w-full rounded-xl border border-gray-300 px-3.5 py-2.5 text-sm
                                         text-gray-900 shadow-sm transition placeholder:text-gray-400
                                         focus:border-primary-500 focus:outline-none focus:ring-4 focus:ring-primary-100"></textarea>
                    </x-ui.bidang>

                    <x-ui.bidang label="Alamat toko" untuk="alamat-toko"
                                 petunjuk="Alamat yang ditampilkan ke pembeli. Titik hitung ongkir diatur terpisah di samping."
                                 :galat="$errors->first('alamat')">
                        <x-ui.isian id="alamat-toko" wire:model="alamat" :galat="$errors->has('alamat')"/>
                    </x-ui.bidang>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.bidang label="Nomor telepon" untuk="phone-toko" :galat="$errors->first('phone')">
                            <x-ui.isian id="phone-toko" wire:model="phone" inputmode="tel"
                                        placeholder="08xxxxxxxxxx" :galat="$errors->has('phone')"/>
                        </x-ui.bidang>

                        <x-ui.bidang label="Email toko" untuk="email-toko" :galat="$errors->first('email')">
                            <x-ui.isian id="email-toko" tipe="email" wire:model="email"
                                        :galat="$errors->has('email')"/>
                        </x-ui.bidang>
                    </div>

                    <x-ui.bidang label="Foto toko" untuk="foto-toko"
                                 petunjuk="JPG, PNG, atau WebP, maksimal 2 MB."
                                 :galat="$errors->first('fotoBaru')">
                        <div class="flex items-center gap-4">
                            @if ($umkm->foto)
                                <img src="{{ Storage::url($umkm->foto) }}" alt="Foto toko {{ $umkm->nama }}"
                                     class="h-16 w-16 flex-none rounded-xl object-cover">
                            @endif

                            <input id="foto-toko" type="file" wire:model="fotoBaru" accept="image/*"
                                   class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0
                                          file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium
                                          file:text-primary-700 hover:file:bg-primary-100">
                        </div>
                    </x-ui.bidang>
                </div>
            </x-ui.kartu>

            {{-- Asal pengiriman --}}
            <div class="space-y-6">
                <x-ui.kartu judul="Asal pengiriman"
                            keterangan="Titik keberangkatan paket. Dipakai menghitung ongkir setiap pesanan.">
                    <div class="space-y-4 px-5 py-5">
                        @if ($alamatAsal !== null)
                            <div class="rounded-xl border border-primary-200 bg-primary-50 p-3.5">
                                <p class="text-xs font-medium text-primary-700">Alamat asal saat ini</p>
                                <p class="mt-1 text-sm font-semibold text-primary-900">{{ $alamatAsal }}</p>

                                <button type="button" wire:click="hapusAlamatAsal"
                                        class="mt-2 text-xs font-medium text-red-600 hover:text-red-700">
                                    Ganti alamat
                                </button>
                            </div>
                        @else
                            <p class="rounded-xl bg-gray-50 p-3.5 text-xs text-gray-600">
                                Belum ada alamat asal. Cari kelurahan atau kecamatan tempat paket
                                Anda dikirim, lalu pilih dari hasilnya.
                            </p>
                        @endif

                        <x-ui.bidang label="Cari wilayah asal" untuk="cari-asal"
                                     petunjuk="Minimal 3 huruf, mis. nama kelurahan."
                                     :galat="$errors->first('cariAsal')">
                            <div class="flex gap-2">
                                <x-ui.isian id="cari-asal" wire:model="cariAsal"
                                            wire:keydown.enter.prevent="cariAlamatAsal"
                                            placeholder="Kerobokan" :galat="$errors->has('cariAsal')"/>
                                <x-ui.tombol jenis="kedua" ikon="cari" wire:click="cariAlamatAsal"
                                             wire:loading.attr="disabled" wire:target="cariAlamatAsal"
                                             aria-label="Cari wilayah asal"/>
                            </div>
                        </x-ui.bidang>

                        @if ($hasilAsal !== [])
                            <ul class="max-h-72 divide-y divide-gray-100 overflow-y-auto rounded-xl border border-gray-200">
                                @foreach ($hasilAsal as $baris)
                                    <li>
                                        <button type="button"
                                                wire:click="pilihAlamatAsal({{ (int) ($baris['id'] ?? 0) }}, @js($baris['label'] ?? ''))"
                                                class="block w-full px-3.5 py-2.5 text-left text-xs text-gray-700 hover:bg-primary-50">
                                            {{ $baris['label'] ?? '—' }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </x-ui.kartu>

                <x-ui.kartu>
                    <div class="px-5 py-5">
                        <p class="text-xs font-medium text-gray-500">Status verifikasi</p>
                        <div class="mt-2">
                            <x-ui.lencana :status="$umkm->status"/>
                        </div>
                        <p class="mt-3 text-xs text-gray-500">
                            Status dan verifikasi ditentukan admin Resikita, bukan disunting dari
                            halaman ini.
                        </p>
                    </div>
                </x-ui.kartu>

                <x-ui.tombol tipe="submit" class="w-full justify-center"
                             wire:loading.attr="disabled" wire:target="simpan">
                    Simpan perubahan
                </x-ui.tombol>
            </div>
        </form>
    @endif
</div>
