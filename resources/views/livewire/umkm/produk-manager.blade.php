@php use App\Support\Rupiah; @endphp

<div>
    <x-ui.kepala-halaman
        judul="Produk"
        keterangan="Katalog toko Anda di marketplace Resikita.">
        <x-slot:aksi>
            <x-ui.tombol wire:click="bukaForm" ikon="plus" :disabled="$daftar === null">Tambah produk</x-ui.tombol>
        </x-slot:aksi>
    </x-ui.kepala-halaman>

    @if ($daftar === null)
        <x-ui.kartu>
            <x-ui.kosong ikon="peringatan" judul="Akun belum terhubung ke UMKM"
                         pesan="Hubungi admin Resikita untuk menghubungkan akun Anda."/>
        </x-ui.kartu>
    @else
        @unless ($siapKirim)
            <div class="mb-5 flex flex-wrap items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                <x-ui.ikon nama="peringatan" class="mt-0.5 h-5 w-5 flex-none text-amber-600"/>
                <div class="min-w-0 flex-1 text-sm text-amber-900">
                    <p class="font-semibold">Produk belum bisa tampil di marketplace</p>
                    <p class="mt-1 text-amber-800">
                        Ongkos kirim dihitung dari lokasi toko Anda, dan alamat asal pengiriman
                        belum ditetapkan. Tetapkan lebih dulu, baru produk bisa diaktifkan.
                    </p>
                </div>
                <x-ui.tombol jenis="kedua" ukuran="kecil" :tautan="route('umkm.toko')">
                    Buka halaman Toko
                </x-ui.tombol>
            </div>
        @endunless

        <div class="mb-5 max-w-sm">
            <label for="cari-produk" class="sr-only">Cari produk</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <x-ui.ikon nama="cari" class="h-4 w-4"/>
                </span>
                <x-ui.isian id="cari-produk" wire:model.live.debounce.400ms="cari" class="pl-9"
                            placeholder="Cari nama produk"/>
            </div>
        </div>

        @if ($daftar->isEmpty())
            <x-ui.kartu>
                <x-ui.kosong
                    ikon="kotak"
                    judul="Belum ada produk"
                    pesan="Tambahkan produk daur ulang Anda supaya bisa dibeli warga lewat aplikasi.">
                    <x-slot:aksi>
                        <x-ui.tombol wire:click="bukaForm" ikon="plus">Tambah produk pertama</x-ui.tombol>
                    </x-slot:aksi>
                </x-ui.kosong>
            </x-ui.kartu>
        @else
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($daftar as $produk)
                    @php $sampul = $produk->foto->firstWhere('is_utama') ?? $produk->foto->first(); @endphp

                    <x-ui.kartu padat class="{{ $produk->is_active ? '' : 'opacity-70' }}">
                        <div class="aspect-4/3 overflow-hidden rounded-t-2xl bg-gray-100">
                            @if ($sampul)
                                <img src="{{ $sampul->url() }}" alt="Foto produk {{ $produk->nama }}"
                                     loading="lazy" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full items-center justify-center text-gray-300">
                                    <x-ui.ikon nama="kotak" class="h-10 w-10"/>
                                </div>
                            @endif
                        </div>

                        <div class="p-5">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="min-w-0 flex-1 truncate text-sm font-semibold text-primary-900">
                                    {{ $produk->nama }}
                                </h3>
                                <x-ui.lencana :warna="$produk->is_active ? 'hijau' : 'abu'"
                                              :label="$produk->is_active ? 'Tampil' : 'Disembunyikan'"/>
                            </div>

                            <p class="mt-0.5 truncate text-xs text-gray-500">{{ $produk->kategori?->nama }}</p>

                            @if ($produk->bahan_baku)
                                <p class="mt-2 truncate text-xs text-primary-700">
                                    Dari {{ $produk->bahan_baku }}
                                </p>
                            @endif

                            <dl class="mt-3 flex flex-wrap items-baseline justify-between gap-2">
                                <dd class="text-lg font-bold text-primary-900">{{ Rupiah::format($produk->harga) }}</dd>
                                <dd class="text-xs text-gray-500">
                                    Stok {{ number_format($produk->stok) }} &middot;
                                    {{ number_format($produk->berat_gram) }} g
                                </dd>
                            </dl>

                            <div class="mt-4 flex gap-2 border-t border-gray-100 pt-4">
                                <x-ui.tombol jenis="kedua" ukuran="kecil" wire:click="bukaForm({{ $produk->id }})"
                                             ikon="pensil">
                                    Sunting
                                </x-ui.tombol>
                                <button type="button" wire:click="ubahAktif({{ $produk->id }})"
                                        class="ml-auto rounded-lg p-2 text-gray-500 transition hover:bg-gray-100"
                                        aria-label="{{ $produk->is_active ? 'Sembunyikan' : 'Tampilkan' }} produk {{ $produk->nama }}">
                                    <x-ui.ikon :nama="$produk->is_active ? 'mata-tutup' : 'mata'" class="h-4 w-4"/>
                                </button>
                            </div>
                        </div>
                    </x-ui.kartu>
                @endforeach
            </div>

            <div class="mt-6">{{ $daftar->links() }}</div>
        @endif
    @endif

    <x-modal active="formTerbuka" maxWidth="max-w-3xl">
        <form wire:submit="simpan">
            <div class="border-b border-gray-100 px-6 py-4">
                <h2 class="text-base font-semibold text-primary-900">
                    {{ $produkId ? 'Sunting produk' : 'Tambah produk' }}
                </h2>
            </div>

            <div class="max-h-[65vh] space-y-4 overflow-y-auto px-6 py-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.bidang label="Nama produk" untuk="nama-produk" :wajib="true" :galat="$errors->first('nama')">
                        <x-ui.isian id="nama-produk" wire:model="nama" :galat="$errors->has('nama')"/>
                    </x-ui.bidang>

                    <x-ui.bidang label="Kategori" untuk="kategori-produk" :wajib="true"
                                 :galat="$errors->first('kategoriId')">
                        <x-ui.pilihan id="kategori-produk" wire:model="kategoriId" kosong="Pilih kategori"
                                      :opsi="$kategoriTersedia->all()" :galat="$errors->has('kategoriId')"/>
                    </x-ui.bidang>
                </div>

                <x-ui.bidang label="Bahan baku" untuk="bahan-baku"
                             petunjuk="Sebutkan asal bahannya, mis. &quot;Kemasan sachet bekas&quot;. Inilah alasan utama orang membeli produk daur ulang."
                             :galat="$errors->first('bahanBaku')">
                    <x-ui.isian id="bahan-baku" wire:model="bahanBaku" :galat="$errors->has('bahanBaku')"/>
                </x-ui.bidang>

                <x-ui.bidang label="Deskripsi" untuk="deskripsi-produk" :galat="$errors->first('deskripsi')">
                    <textarea id="deskripsi-produk" wire:model="deskripsi" rows="4"
                              class="block w-full rounded-xl border border-gray-300 px-3.5 py-2.5 text-sm shadow-sm
                                     focus:border-primary-500 focus:outline-none focus:ring-4 focus:ring-primary-100"></textarea>
                </x-ui.bidang>

                <div class="grid gap-4 sm:grid-cols-3">
                    <x-ui.bidang label="Harga" untuk="harga-produk" :wajib="true"
                                 petunjuk="Rupiah penuh tanpa titik."
                                 :galat="$errors->first('harga')">
                        <x-ui.isian id="harga-produk" wire:model="harga" inputmode="numeric" placeholder="45000"
                                    :galat="$errors->has('harga')"/>
                    </x-ui.bidang>

                    <x-ui.bidang label="Stok" untuk="stok-produk" :wajib="true" :galat="$errors->first('stok')">
                        <x-ui.isian id="stok-produk" wire:model="stok" inputmode="numeric"
                                    :galat="$errors->has('stok')"/>
                    </x-ui.bidang>

                    <x-ui.bidang label="Berat (gram)" untuk="berat-produk" :wajib="true"
                                 petunjuk="Dipakai menghitung ongkir."
                                 :galat="$errors->first('beratGram')">
                        <x-ui.isian id="berat-produk" wire:model="beratGram" inputmode="numeric" placeholder="300"
                                    :galat="$errors->has('beratGram')"/>
                    </x-ui.bidang>
                </div>

                <x-ui.bidang label="Tambah foto" untuk="foto-produk"
                             petunjuk="JPG, PNG, atau WebP. Maksimal 2 MB per berkas."
                             :galat="$errors->first('fotoBaru.*')">
                    <input id="foto-produk" type="file" wire:model="fotoBaru" accept="image/*" multiple
                           class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0
                                  file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium
                                  file:text-primary-700 hover:file:bg-primary-100">
                    <div wire:loading wire:target="fotoBaru" class="text-xs text-gray-500">Mengunggah…</div>
                </x-ui.bidang>

                @if ($produkTerbuka && $produkTerbuka->foto->isNotEmpty())
                    <div>
                        <p class="mb-2 text-sm font-medium text-primary-900">Foto tersimpan</p>
                        <div class="grid grid-cols-3 gap-2 sm:grid-cols-4">
                            @foreach ($produkTerbuka->foto as $foto)
                                <div class="relative overflow-hidden rounded-xl bg-gray-100">
                                    <img src="{{ $foto->url() }}" alt="Foto produk {{ $produkTerbuka->nama }}"
                                         class="aspect-square w-full object-cover">

                                    @if ($foto->is_utama)
                                        <span class="absolute left-1.5 top-1.5 rounded-md bg-primary-500 px-1.5 py-0.5 text-[10px] font-semibold text-white">
                                            Sampul
                                        </span>
                                    @endif

                                    <div class="absolute inset-x-0 bottom-0 flex justify-end gap-1 bg-linear-to-t from-black/60 to-transparent p-1.5">
                                        @unless ($foto->is_utama)
                                            <button type="button" wire:click="jadikanUtama({{ $foto->id }})"
                                                    class="rounded bg-white/90 p-1 text-primary-700"
                                                    aria-label="Jadikan foto sampul">
                                                <x-ui.ikon nama="centang" class="h-3 w-3"/>
                                            </button>
                                        @endunless
                                        <button type="button" wire:click="hapusFoto({{ $foto->id }})"
                                                class="rounded bg-white/90 p-1 text-red-600"
                                                aria-label="Hapus foto ini">
                                            <x-ui.ikon nama="sampah" class="h-3 w-3"/>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" wire:model="isActive"
                           class="h-4 w-4 rounded border-gray-300 text-primary-500 focus:ring-primary-200">
                    Tampilkan di marketplace
                </label>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-100 px-6 py-4">
                <x-ui.tombol jenis="kedua" wire:click="tutupForm">Batal</x-ui.tombol>
                <x-ui.tombol tipe="submit" wire:loading.attr="disabled" wire:target="simpan,fotoBaru">
                    {{ $produkId ? 'Simpan perubahan' : 'Tambah produk' }}
                </x-ui.tombol>
            </div>
        </form>
    </x-modal>
</div>
