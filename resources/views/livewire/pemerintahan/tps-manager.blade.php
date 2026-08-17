@php use App\Support\Rupiah; @endphp

<div>
    <x-ui.kepala-halaman
        judul="TPS dan TPS3R"
        keterangan="Tempat pengelolaan sampah di wilayah Anda. Data ini yang muncul di peta warga dan aplikasi ponsel.">
        <x-slot:aksi>
            <x-ui.tombol wire:click="bukaForm" ikon="plus">Tambah TPS</x-ui.tombol>
        </x-slot:aksi>
    </x-ui.kepala-halaman>

    <div class="mb-5 max-w-sm">
        <label for="cari-tps" class="sr-only">Cari TPS</label>
        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                <x-ui.ikon nama="cari" class="h-4 w-4"/>
            </span>
            <x-ui.isian id="cari-tps" wire:model.live.debounce.400ms="cari" class="pl-9" placeholder="Cari nama TPS"/>
        </div>
    </div>

    @if ($daftar->isEmpty())
        <x-ui.kartu>
            <x-ui.kosong
                ikon="kotak"
                judul="Belum ada TPS terdaftar"
                pesan="Tambahkan TPS atau TPS3R supaya warga bisa menemukannya di peta dan mendaftar sebagai anggota.">
                <x-slot:aksi>
                    <x-ui.tombol wire:click="bukaForm" ikon="plus">Tambah TPS</x-ui.tombol>
                </x-slot:aksi>
            </x-ui.kosong>
        </x-ui.kartu>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($daftar as $tps)
                <x-ui.kartu>
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="truncate text-sm font-semibold text-primary-900">{{ $tps->nama }}</h3>
                            <p class="mt-0.5 truncate text-xs text-gray-500">{{ $tps->wilayah?->namaLengkap() ?? '—' }}</p>
                        </div>
                        <x-ui.lencana :status="$tps->jenis"/>
                    </div>

                    @if ($tps->alamat)
                        <p class="mt-3 line-clamp-2 text-sm text-gray-600">{{ $tps->alamat }}</p>
                    @endif

                    <dl class="mt-4 grid grid-cols-2 gap-3 border-t border-gray-100 pt-4 text-sm">
                        <div>
                            <dt class="text-xs text-gray-500">Anggota</dt>
                            <dd class="font-medium text-primary-900">{{ number_format($tps->anggota_count) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Iuran bulanan</dt>
                            <dd class="font-medium text-primary-900">
                                {{ $tps->is_berbayar ? Rupiah::format($tps->tarif_bulanan) : 'Gratis' }}
                            </dd>
                        </div>
                        @if ($tps->kapasitas_ton)
                            <div>
                                <dt class="text-xs text-gray-500">Kapasitas</dt>
                                <dd class="font-medium text-primary-900">
                                    {{ number_format((float) $tps->kapasitas_ton, 2, ',', '.') }} ton
                                </dd>
                            </div>
                        @endif
                        @if ($tps->phone)
                            <div>
                                <dt class="text-xs text-gray-500">Kontak</dt>
                                <dd class="font-medium text-primary-900">{{ $tps->phone }}</dd>
                            </div>
                        @endif
                    </dl>

                    <div class="mt-4 flex gap-2 border-t border-gray-100 pt-4">
                        <x-ui.tombol jenis="kedua" ukuran="kecil" wire:click="bukaForm({{ $tps->id }})" ikon="pensil">
                            Sunting
                        </x-ui.tombol>
                        <button type="button" wire:click="hapus({{ $tps->id }})"
                                wire:confirm="Hapus TPS {{ $tps->nama }}? Tindakan ini tidak dapat dibatalkan."
                                class="ml-auto rounded-lg p-2 text-red-500 transition hover:bg-red-50 hover:text-red-700"
                                aria-label="Hapus TPS {{ $tps->nama }}">
                            <x-ui.ikon nama="sampah" class="h-4 w-4"/>
                        </button>
                    </div>
                </x-ui.kartu>
            @endforeach
        </div>

        <div class="mt-6">{{ $daftar->links() }}</div>
    @endif

    <x-modal active="formTerbuka" maxWidth="max-w-2xl">
        <form wire:submit="simpan">
            <div class="border-b border-gray-100 px-6 py-4">
                <h2 class="text-base font-semibold text-primary-900">
                    {{ $tpsId ? 'Sunting TPS' : 'Tambah TPS' }}
                </h2>
            </div>

            <div class="max-h-[65vh] space-y-4 overflow-y-auto px-6 py-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.bidang label="Nama" untuk="nama-tps" :wajib="true" :galat="$errors->first('nama')">
                        <x-ui.isian id="nama-tps" wire:model="nama" :galat="$errors->has('nama')"/>
                    </x-ui.bidang>

                    <x-ui.bidang label="Jenis" untuk="jenis-tps" :wajib="true" :galat="$errors->first('jenis')">
                        <x-ui.pilihan id="jenis-tps" wire:model="jenis" :opsi="$jenisTersedia"/>
                    </x-ui.bidang>
                </div>

                <x-ui.bidang label="Alamat" untuk="alamat-tps" :galat="$errors->first('alamat')">
                    <x-ui.isian id="alamat-tps" wire:model="alamat" :galat="$errors->has('alamat')"/>
                </x-ui.bidang>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.bidang label="Wilayah" untuk="wilayah-tps" :wajib="true" :galat="$errors->first('wilayahId')">
                        @if ($wilayahTersedia->isEmpty())
                            <p class="rounded-xl bg-amber-50 p-3 text-xs text-amber-800">
                                Akun Anda belum terhubung ke wilayah mana pun.
                            </p>
                        @else
                            <x-ui.pilihan id="wilayah-tps" wire:model="wilayahId" kosong="Pilih wilayah"
                                          :opsi="$wilayahTersedia->mapWithKeys(fn ($w) => [$w->id => $w->namaLengkap()])->all()"
                                          :galat="$errors->has('wilayahId')"/>
                        @endif
                    </x-ui.bidang>

                    <x-ui.bidang label="Nomor kontak" untuk="phone-tps" :galat="$errors->first('phone')">
                        <x-ui.isian id="phone-tps" wire:model="phone" placeholder="08xxxxxxxxxx"/>
                    </x-ui.bidang>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <x-ui.bidang label="Lintang" untuk="lat-tps" :galat="$errors->first('latitude')">
                        <x-ui.isian id="lat-tps" wire:model="latitude" placeholder="-8.5830000"
                                    :galat="$errors->has('latitude')"/>
                    </x-ui.bidang>

                    <x-ui.bidang label="Bujur" untuk="lng-tps" :galat="$errors->first('longitude')">
                        <x-ui.isian id="lng-tps" wire:model="longitude" placeholder="115.1830000"
                                    :galat="$errors->has('longitude')"/>
                    </x-ui.bidang>

                    <x-ui.bidang label="Kapasitas (ton)" untuk="kapasitas-tps" :galat="$errors->first('kapasitasTon')">
                        <x-ui.isian id="kapasitas-tps" wire:model="kapasitasTon" placeholder="12.5"
                                    :galat="$errors->has('kapasitasTon')"/>
                    </x-ui.bidang>
                </div>

                <div class="rounded-xl border border-gray-200 p-4">
                    <label class="flex items-center gap-2 text-sm font-medium text-primary-900">
                        <input type="checkbox" wire:model.live="isBerbayar"
                               class="h-4 w-4 rounded border-gray-300 text-primary-500 focus:ring-primary-200">
                        TPS ini memungut iuran bulanan
                    </label>

                    @if ($isBerbayar)
                        <div class="mt-4">
                            <x-ui.bidang label="Tarif bulanan" untuk="tarif-tps" :wajib="true"
                                         petunjuk="Rupiah penuh tanpa titik. Contoh: 25000 untuk Rp 25.000."
                                         :galat="$errors->first('tarifBulanan')">
                                <x-ui.isian id="tarif-tps" wire:model="tarifBulanan" inputmode="numeric"
                                            placeholder="25000" :galat="$errors->has('tarifBulanan')"/>
                            </x-ui.bidang>
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-100 px-6 py-4">
                <x-ui.tombol jenis="kedua" wire:click="tutupForm">Batal</x-ui.tombol>
                <x-ui.tombol tipe="submit" wire:loading.attr="disabled" wire:target="simpan">
                    {{ $tpsId ? 'Simpan perubahan' : 'Tambah TPS' }}
                </x-ui.tombol>
            </div>
        </form>
    </x-modal>
</div>
