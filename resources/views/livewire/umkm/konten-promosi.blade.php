<div>
    <x-ui.kepala-halaman
        judul="Asisten konten"
        keterangan="Draf caption, deskripsi produk, dan sampul untuk unggahan media sosial. Semuanya bisa disunting sebelum dipakai.">
        @if ($umkm !== null)
            <x-slot:aksi>
                <x-ui.lencana :warna="$sisaKuota > 5 ? 'abu' : 'kuning'"
                              :label="'Sisa '.$sisaKuota.' draf hari ini'"/>
            </x-slot:aksi>
        @endif
    </x-ui.kepala-halaman>

    @if ($umkm === null)
        <x-ui.kartu>
            <x-ui.kosong ikon="peringatan" judul="Akun belum terhubung ke UMKM"
                         pesan="Hubungi admin Resikita untuk menghubungkan akun Anda."/>
        </x-ui.kartu>
    @else
        <div class="grid gap-6 lg:grid-cols-3">

            {{-- Penyusun --}}
            <x-ui.kartu judul="Susun draf baru">
                <form wire:submit="susun" class="space-y-4">
                    <x-ui.bidang label="Produk" untuk="produk-konten" :wajib="true"
                                 :galat="$errors->first('produkId')">
                        @if ($produkTersedia->isEmpty())
                            <p class="rounded-xl bg-amber-50 p-3 text-xs text-amber-800">
                                Belum ada produk aktif. Tambahkan produk lebih dulu di menu Produk.
                            </p>
                        @else
                            <x-ui.pilihan id="produk-konten" wire:model="produkId" kosong="Pilih produk"
                                          :opsi="$produkTersedia->all()" :galat="$errors->has('produkId')"/>
                        @endif
                    </x-ui.bidang>

                    <fieldset>
                        <legend class="mb-2 block text-sm font-medium text-primary-900">Keluaran yang diinginkan</legend>
                        <div class="space-y-2">
                            @foreach ($tujuanTersedia as $pilihan)
                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition
                                              {{ $tujuan === $pilihan->value
                                                  ? 'border-primary-300 bg-primary-50'
                                                  : 'border-gray-200 hover:bg-gray-50' }}">
                                    <input type="radio" wire:model.live="tujuan" value="{{ $pilihan->value }}"
                                           class="mt-0.5 h-4 w-4 border-gray-300 text-primary-500 focus:ring-primary-200">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-medium text-primary-900">{{ $pilihan->label() }}</span>
                                        <span class="mt-0.5 block text-xs text-gray-500">{{ $pilihan->deskripsi() }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <x-ui.bidang label="Nada bahasa" untuk="nada-konten" :wajib="true">
                        <x-ui.pilihan id="nada-konten" wire:model="nada"
                                      :opsi="collect($nadaTersedia)->mapWithKeys(fn ($n) => [$n->value => $n->label()])->all()"/>
                    </x-ui.bidang>

                    <x-ui.tombol tipe="submit" class="w-full"
                                 :disabled="$produkTersedia->isEmpty() || $sisaKuota === 0"
                                 wire:loading.attr="disabled" wire:target="susun">
                        <span wire:loading.remove wire:target="susun">Susun draf</span>
                        <span wire:loading wire:target="susun">Menyusun…</span>
                    </x-ui.tombol>

                    @if ($sisaKuota === 0)
                        <p class="text-xs text-amber-800">
                            Batas draf harian sudah tercapai. Kuota kembali besok.
                        </p>
                    @endif
                </form>

                <p class="mt-5 flex items-start gap-2 border-t border-gray-100 pt-4 text-xs text-gray-500">
                    <x-ui.ikon nama="info" class="h-4 w-4 flex-none text-gray-400"/>
                    Sampul disusun di atas foto produk asli Anda. Resikita tidak pernah mengganti
                    foto produk dengan gambar buatan, itu perlindungan bagi pembeli.
                </p>
            </x-ui.kartu>

            {{-- Draf terbuka --}}
            <div class="space-y-6 lg:col-span-2">
                @if ($konten === null)
                    <x-ui.kartu>
                        <x-ui.kosong
                            ikon="pensil"
                            judul="Belum ada draf terbuka"
                            pesan="Susun draf baru di sebelah kiri, atau buka salah satu draf dari riwayat di bawah."/>
                    </x-ui.kartu>
                @else
                    <x-ui.kartu :judul="$konten->tujuan->label()"
                                :keterangan="'Untuk produk '.($konten->produk?->nama ?? 'yang sudah dihapus').' · nada '.$konten->nada->label()">
                        <x-slot:aksi>
                            <x-ui.lencana warna="ungu" label="Dibuat dengan bantuan AI"/>
                            <x-ui.tombol jenis="polos" ukuran="kecil" wire:click="tutupDraf" ikon="silang">
                                Tutup
                            </x-ui.tombol>
                        </x-slot:aksi>

                        <form wire:submit="simpanSuntingan" class="space-y-4">
                            <x-ui.bidang label="Teks" untuk="teks-konten" :wajib="true"
                                         petunjuk="Sunting sesukanya. Yang tersimpan adalah versi Anda, bukan versi mesinnya."
                                         :galat="$errors->first('teksSuntingan')">
                                <textarea id="teks-konten" wire:model="teksSuntingan" rows="8"
                                          class="block w-full rounded-xl border px-3.5 py-2.5 text-sm shadow-sm
                                                 focus:outline-none focus:ring-4
                                                 {{ $errors->has('teksSuntingan')
                                                     ? 'border-red-300 focus:border-red-500 focus:ring-red-200'
                                                     : 'border-gray-300 focus:border-primary-500 focus:ring-primary-100' }}"></textarea>
                            </x-ui.bidang>

                            @if ($konten->tujuan->menghasilkanHashtag())
                                <x-ui.bidang label="Tagar" untuk="hashtag-konten"
                                             petunjuk="Dipisahkan spasi. Tanda pagar ditambahkan otomatis.">
                                    <x-ui.isian id="hashtag-konten" wire:model="hashtagSuntingan"/>
                                </x-ui.bidang>
                            @endif

                            <div class="flex flex-wrap gap-2 border-t border-gray-100 pt-4">
                                <x-ui.tombol tipe="submit" wire:loading.attr="disabled" wire:target="simpanSuntingan">
                                    Simpan suntingan
                                </x-ui.tombol>

                                <div x-data="{
                                    salin() {
                                        const teks = @js(trim(($konten->hasil_teks ?? '')."\n\n".collect($konten->hasil_hashtag ?? [])->implode(' ')));
                                        navigator.clipboard.writeText(teks).then(() => this.tersalin = true)
                                        setTimeout(() => this.tersalin = false, 2000)
                                    },
                                    tersalin: false,
                                }">
                                    <x-ui.tombol jenis="kedua" @click="salin()">
                                        <span x-show="!tersalin">Salin teks</span>
                                        <span x-show="tersalin" x-cloak>Tersalin</span>
                                    </x-ui.tombol>
                                </div>

                                <x-ui.tombol jenis="{{ $konten->dipakai ? 'halus' : 'polos' }}"
                                             wire:click="tandaiDipakai({{ $konten->id }})"
                                             ikon="{{ $konten->dipakai ? 'centang' : 'plus' }}">
                                    {{ $konten->dipakai ? 'Sudah dipakai' : 'Tandai sudah dipakai' }}
                                </x-ui.tombol>
                            </div>
                        </form>
                    </x-ui.kartu>

                    {{-- Penyusun sampul --}}
                    @if ($templateSampul !== null)
                        <x-ui.kartu judul="Sampul produk"
                                    keterangan="Disusun di atas foto produk asli Anda. Atur gayanya sesuka Anda, lalu unduh dan unggah ke media sosial.">
                            {{--
                                Pratinjau dan panel pengaturan berdampingan di layar
                                lebar, bertumpuk di layar sempit. Pratinjau lebih dulu
                                di urutan markup supaya perubahan pilihan langsung
                                terlihat tanpa perlu menggulir balik ke atas.
                            --}}
                            <div class="grid gap-6 xl:grid-cols-5">
                                <div class="xl:col-span-2">
                                    {{--
                                        Kunci ikut berubah setiap kali spesifikasinya
                                        berubah, sehingga Livewire mengganti simpul ini
                                        dan Alpine menggambar ulang. Tanpa itu DOM cuma
                                        dimorf dan kanvas tetap menampilkan gaya lama.
                                    --}}
                                    <div wire:key="sampul-{{ md5(json_encode($templateSampul)) }}"
                                         x-data="penyusunSampul(@js($templateSampul))" x-init="gambar()">
                                        <div class="mx-auto overflow-hidden rounded-2xl border border-gray-200 bg-gray-50"
                                             style="max-width: {{ $rasio === 'cerita' ? '15rem' : '22rem' }}">
                                            <canvas x-ref="kanvas"
                                                    class="block h-auto w-full"
                                                    role="img"
                                                    aria-label="Pratinjau sampul produk {{ $konten->produk?->nama }}"></canvas>
                                        </div>

                                        <p class="mt-3 text-xs text-gray-500" role="status" aria-live="polite"
                                           x-text="keterangan"></p>

                                        <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                                            <x-ui.tombol @click="kirim()" ikon="centang"
                                                         x-bind:disabled="!siap">
                                                Simpan sampul
                                            </x-ui.tombol>

                                            <x-ui.tombol jenis="kedua" @click="unduh()" ikon="unduh"
                                                         x-bind:disabled="!siap">
                                                Unduh gambar
                                            </x-ui.tombol>
                                        </div>
                                    </div>
                                </div>

                                {{-- Pengaturan tampilan --}}
                                <div class="space-y-5 xl:col-span-3">
                                    <fieldset>
                                        <legend class="mb-2 block text-sm font-medium text-primary-900">Tata letak</legend>
                                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                            @foreach ($gayaTersedia as $pilihan)
                                                <label class="cursor-pointer rounded-xl border p-2.5 transition
                                                              {{ $gaya === $pilihan->value
                                                                  ? 'border-primary-400 bg-primary-50 ring-1 ring-primary-200'
                                                                  : 'border-gray-200 hover:bg-gray-50' }}">
                                                    <input type="radio" wire:model.live="gaya" value="{{ $pilihan->value }}"
                                                           class="sr-only">
                                                    <x-umkm.rangka-sampul :gaya="$pilihan->value"/>
                                                    <span class="mt-1.5 block text-xs font-medium text-primary-900">
                                                        {{ $pilihan->label() }}
                                                    </span>
                                                    <span class="mt-0.5 block text-[0.68rem] leading-snug text-gray-500">
                                                        {{ $pilihan->deskripsi() }}
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </fieldset>

                                    <fieldset>
                                        <legend class="mb-2 block text-sm font-medium text-primary-900">Warna</legend>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($paletTersedia as $pilihan)
                                                <label class="flex cursor-pointer items-center gap-2 rounded-full border py-1.5 pl-1.5 pr-3 transition
                                                              {{ $palet === $pilihan->value
                                                                  ? 'border-primary-400 bg-primary-50'
                                                                  : 'border-gray-200 hover:bg-gray-50' }}">
                                                    <input type="radio" wire:model.live="palet" value="{{ $pilihan->value }}"
                                                           class="sr-only">
                                                    <span class="h-5 w-5 flex-none rounded-full border border-black/10"
                                                          style="background: {{ $pilihan->contoh() }}"></span>
                                                    <span class="text-xs font-medium text-primary-900">{{ $pilihan->label() }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </fieldset>

                                    <fieldset>
                                        <legend class="mb-2 block text-sm font-medium text-primary-900">Bentuk kanvas</legend>
                                        <div class="grid grid-cols-3 gap-2">
                                            @foreach ($rasioTersedia as $pilihan)
                                                <label class="cursor-pointer rounded-xl border px-3 py-2 text-center transition
                                                              {{ $rasio === $pilihan->value
                                                                  ? 'border-primary-400 bg-primary-50'
                                                                  : 'border-gray-200 hover:bg-gray-50' }}">
                                                    <input type="radio" wire:model.live="rasio" value="{{ $pilihan->value }}"
                                                           class="sr-only">
                                                    <span class="block text-xs font-medium text-primary-900">{{ $pilihan->label() }}</span>
                                                    <span class="mt-0.5 block text-[0.68rem] text-gray-500">{{ $pilihan->deskripsi() }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </fieldset>

                                    <fieldset>
                                        <legend class="mb-2 block text-sm font-medium text-primary-900">Keterangan yang ikut tampil</legend>
                                        <div class="space-y-2">
                                            @foreach ([
                                                ['prop' => 'tampilkanBahan', 'label' => 'Bahan baku', 'petunjuk' => 'Asal bahan justru yang paling menjelaskan nilai produk daur ulang.'],
                                                ['prop' => 'tampilkanHarga', 'label' => 'Harga', 'petunjuk' => 'Diambil dari harga produk saat sampul digambar.'],
                                                ['prop' => 'tampilkanToko', 'label' => 'Nama toko', 'petunjuk' => 'Berguna kalau sampulnya dibagikan ulang orang lain.'],
                                            ] as $saklar)
                                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-3 transition hover:bg-gray-50">
                                                    <input type="checkbox" wire:model.live="{{ $saklar['prop'] }}"
                                                           class="mt-0.5 h-4 w-4 rounded border-gray-300 text-primary-500 focus:ring-primary-200">
                                                    <span class="min-w-0">
                                                        <span class="block text-sm font-medium text-primary-900">{{ $saklar['label'] }}</span>
                                                        <span class="mt-0.5 block text-xs text-gray-500">{{ $saklar['petunjuk'] }}</span>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </fieldset>

                                    <div class="space-y-4 border-t border-gray-100 pt-4">
                                        <x-ui.bidang label="Judul di sampul" untuk="judul-sampul"
                                                     petunjuk="Kosongkan untuk memakai kalimat pertama dari teks draf.">
                                            <x-ui.isian id="judul-sampul" wire:model.blur="judulSampul"
                                                        maxlength="200" placeholder="Ikut teks draf"/>
                                        </x-ui.bidang>

                                        <x-ui.bidang label="Kalimat pendukung" untuk="pendukung-sampul"
                                                     petunjuk="Baris kecil di bawah judul. Boleh dikosongkan.">
                                            <x-ui.isian id="pendukung-sampul" wire:model.blur="pendukungSampul"
                                                        maxlength="200" placeholder="Ikut teks draf"/>
                                        </x-ui.bidang>
                                    </div>
                                </div>
                            </div>

                            @if ($konten->urlSampul())
                                <div class="mt-5 border-t border-gray-100 pt-5">
                                    <p class="text-sm font-medium text-primary-900">Sampul tersimpan</p>
                                    <a href="{{ $konten->urlSampul() }}" target="_blank" rel="noopener"
                                       class="mt-2 block w-48 overflow-hidden rounded-xl border border-gray-200">
                                        <img src="{{ $konten->urlSampul() }}"
                                             alt="Sampul tersimpan untuk {{ $konten->produk?->nama }}"
                                             class="w-full">
                                    </a>
                                </div>
                            @endif
                        </x-ui.kartu>
                    @elseif ($konten->tujuan->menghasilkanGambar())
                        <x-ui.kartu>
                            <x-ui.kosong
                                ikon="peringatan"
                                judul="Sampul belum bisa disusun"
                                pesan="Produk ini belum punya foto asli. Unggah foto produk lebih dulu di menu Produk."/>
                        </x-ui.kartu>
                    @endif
                @endif

                {{-- Riwayat --}}
                <x-ui.kartu padat judul="Riwayat draf"
                            keterangan="Yang ditandai sudah dipakai adalah ukuran nyata kegunaan fitur ini.">
                    @if ($riwayat->isEmpty())
                        <x-ui.kosong ikon="jejak" judul="Belum ada draf"
                                     pesan="Draf yang Anda susun akan tersimpan di sini."/>
                    @else
                        <ul class="divide-y divide-gray-100">
                            @foreach ($riwayat as $item)
                                <li class="flex flex-wrap items-start justify-between gap-3 px-5 py-4">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <x-ui.lencana warna="hijau" :label="$item->tujuan->label()"/>
                                            @if ($item->dipakai)
                                                <x-ui.lencana warna="biru" label="Sudah dipakai"/>
                                            @endif
                                        </div>

                                        <p class="mt-1.5 line-clamp-2 text-sm text-gray-600">
                                            {{ Str::limit($item->hasil_teks ?? '—', 140) }}
                                        </p>

                                        <p class="mt-1 text-xs text-gray-400">
                                            {{ $item->produk?->nama ?? 'Produk terhapus' }}
                                            &middot; {{ $item->created_at->translatedFormat('j M Y, H:i') }}
                                        </p>
                                    </div>

                                    <div class="flex flex-none gap-1">
                                        <button type="button" wire:click="bukaDraf({{ $item->id }})"
                                                class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-primary-900"
                                                aria-label="Buka draf {{ $item->tujuan->label() }}">
                                            <x-ui.ikon nama="pensil" class="h-4 w-4"/>
                                        </button>

                                        <button type="button" wire:click="hapus({{ $item->id }})"
                                                wire:confirm="Hapus draf ini? Tindakan ini tidak dapat dibatalkan."
                                                class="rounded-lg p-2 text-red-500 transition hover:bg-red-50"
                                                aria-label="Hapus draf {{ $item->tujuan->label() }}">
                                            <x-ui.ikon nama="sampah" class="h-4 w-4"/>
                                        </button>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        <div class="border-t border-gray-100 px-5 py-3">{{ $riwayat->links() }}</div>
                    @endif
                </x-ui.kartu>
            </div>
        </div>
    @endif
</div>
