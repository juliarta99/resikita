@php use App\Enums\StatusUmkm; @endphp

<div>
    <x-ui.kepala-halaman
        judul="Manajemen UMKM"
        keterangan="Toko produk daur ulang. Verifikasi sebelum berjualan adalah perlindungan bagi pembeli."/>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:max-w-2xl">
        <x-ui.bidang label="Cari" untuk="cari-umkm">
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <x-ui.ikon nama="cari" class="h-4 w-4"/>
                </span>
                <x-ui.isian id="cari-umkm" wire:model.live.debounce.400ms="cari" class="pl-9"
                            placeholder="Nama toko atau email"/>
            </div>
        </x-ui.bidang>

        <x-ui.bidang label="Status" untuk="status-umkm">
            <x-ui.pilihan id="status-umkm" wire:model.live="status" kosong="Semua status" :opsi="$statusTersedia"/>
        </x-ui.bidang>
    </div>

    @if ($jumlahMenunggu > 0 && $status !== 'menunggu')
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4">
            <p class="text-sm text-amber-900">
                {{ number_format($jumlahMenunggu) }} toko masih menunggu verifikasi.
                <button type="button" wire:click="$set('status', 'menunggu')" class="font-medium underline">
                    Lihat antreannya.
                </button>
            </p>
        </div>
    @endif

    @if ($daftar->isEmpty())
        <x-ui.kartu>
            <x-ui.kosong ikon="toko" judul="Tidak ada UMKM"
                         pesan="Tidak ada toko yang cocok dengan penyaring ini."/>
        </x-ui.kartu>
    @else
        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($daftar as $umkm)
                <x-ui.kartu>
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="truncate text-sm font-semibold text-primary-900">{{ $umkm->nama }}</h3>
                            <p class="truncate text-xs text-gray-500">
                                {{ $umkm->wilayah?->namaLengkap() ?? 'Wilayah belum diisi' }}
                            </p>
                        </div>
                        <x-ui.lencana :status="$umkm->status"/>
                    </div>

                    @if ($umkm->deskripsi)
                        <p class="mt-3 line-clamp-2 text-sm text-gray-600">{{ $umkm->deskripsi }}</p>
                    @endif

                    <dl class="mt-4 grid grid-cols-2 gap-3 border-t border-gray-100 pt-4 text-sm">
                        <div>
                            <dt class="text-xs text-gray-500">Kontak</dt>
                            <dd class="truncate text-gray-700">{{ $umkm->email ?? $umkm->phone ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Produk</dt>
                            <dd class="text-gray-700">{{ number_format($umkm->produk_count) }}</dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-xs text-gray-500">Alamat</dt>
                            <dd class="text-gray-700">{{ $umkm->alamat ?? '—' }}</dd>
                        </div>
                    </dl>

                    @if ($umkm->catatan_verifikasi && $umkm->status === StatusUmkm::Ditolak)
                        <div class="mt-4 rounded-xl bg-red-50 p-3 text-sm">
                            <p class="text-xs font-semibold text-red-900">Alasan penolakan</p>
                            <p class="mt-1 whitespace-pre-line text-red-800">{{ $umkm->catatan_verifikasi }}</p>
                            @if ($umkm->ditinjau_at)
                                <p class="mt-1.5 text-xs text-red-700">
                                    {{ $umkm->ditinjau_at->translatedFormat('j M Y, H:i') }}
                                    @if ($umkm->peninjau) · {{ $umkm->peninjau->name }} @endif
                                </p>
                            @endif
                        </div>
                    @endif

                    @if ($umkm->status === StatusUmkm::Menunggu)
                        @if ($tolakId === $umkm->id)
                            {{--
                                Alasan diketik di tempat, bukan lewat konfirmasi
                                peramban. Pemilik usaha membacanya utuh di halaman
                                statusnya, jadi ia butuh ruang untuk kalimat yang
                                benar-benar bisa ditindaklanjuti.
                            --}}
                            <form wire:submit="tolak" class="mt-4 space-y-3 border-t border-gray-100 pt-4">
                                <x-ui.bidang label="Alasan penolakan" untuk="catatan-{{ $umkm->id }}" :wajib="true"
                                             petunjuk="Dibaca langsung oleh pemilik usaha. Sebutkan apa yang harus diperbaiki."
                                             :galat="$errors->first('catatanTolak')">
                                    <textarea id="catatan-{{ $umkm->id }}" wire:model="catatanTolak" rows="3"
                                              placeholder="Contoh: Alamat usaha belum lengkap sampai nama jalan dan nomor, sehingga lokasinya tidak bisa dipastikan."
                                              class="block w-full rounded-xl border px-3.5 py-2.5 text-sm text-gray-900 shadow-sm
                                                     transition placeholder:text-gray-400 focus:outline-none focus:ring-4
                                                     {{ $errors->has('catatanTolak')
                                                        ? 'border-red-300 focus:border-red-500 focus:ring-red-200'
                                                        : 'border-gray-300 focus:border-primary-500 focus:ring-primary-100' }}"></textarea>
                                </x-ui.bidang>

                                <div class="flex gap-2">
                                    <x-ui.tombol jenis="bahaya" ukuran="kecil" tipe="submit"
                                                 wire:loading.attr="disabled" wire:target="tolak">
                                        Kirim penolakan
                                    </x-ui.tombol>
                                    <x-ui.tombol jenis="kedua" ukuran="kecil" type="button" wire:click="batalTolak">
                                        Batal
                                    </x-ui.tombol>
                                </div>
                            </form>
                        @else
                            <div class="mt-4 flex gap-2 border-t border-gray-100 pt-4">
                                <x-ui.tombol ukuran="kecil" wire:click="setujui({{ $umkm->id }})" ikon="centang"
                                             wire:confirm="Setujui {{ $umkm->nama }}? Produknya langsung bisa tampil di marketplace.">
                                    Setujui
                                </x-ui.tombol>
                                <x-ui.tombol jenis="bahaya" ukuran="kecil" ikon="silang"
                                             wire:click="bukaFormTolak({{ $umkm->id }})">
                                    Tolak
                                </x-ui.tombol>
                            </div>
                        @endif
                    @elseif ($umkm->status === StatusUmkm::Ditolak)
                        <div class="mt-4 border-t border-gray-100 pt-4">
                            <x-ui.tombol jenis="kedua" ukuran="kecil" wire:click="setujui({{ $umkm->id }})"
                                         wire:confirm="Aktifkan kembali {{ $umkm->nama }}?">
                                Aktifkan kembali
                            </x-ui.tombol>
                            <p class="mt-2 text-xs text-gray-500">
                                Pemiliknya juga bisa memperbaiki datanya sendiri lalu mengajukan
                                ulang, dan tokonya akan kembali muncul di daftar "menunggu".
                            </p>
                        </div>
                    @endif
                </x-ui.kartu>
            @endforeach
        </div>

        <div class="mt-6">{{ $daftar->links() }}</div>
    @endif
</div>
