@php use App\Support\Rupiah; @endphp

<div>
    <x-ui.kepala-halaman
        judul="Katalog harga"
        keterangan="Harga milik unit Anda sendiri. Mengubah harga di sini tidak mengubah nilai setoran yang sudah tercatat.">
        <x-slot:aksi>
            <x-ui.tombol wire:click="bukaForm" ikon="plus" :disabled="! $punyaUnit">Tambah jenis</x-ui.tombol>
        </x-slot:aksi>
    </x-ui.kepala-halaman>

    @if (! $punyaUnit)
        <x-ui.kartu>
            <x-ui.kosong ikon="peringatan" judul="Akun belum terhubung ke unit bank sampah"
                         pesan="Katalog harga melekat pada unit. Hubungi admin Resikita."/>
        </x-ui.kartu>
    @else
        <div class="mb-5 max-w-sm">
            <label for="cari-harga" class="sr-only">Cari jenis sampah</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <x-ui.ikon nama="cari" class="h-4 w-4"/>
                </span>
                <x-ui.isian id="cari-harga" wire:model.live.debounce.400ms="cari" class="pl-9"
                            placeholder="Cari jenis sampah"/>
            </div>
        </div>

        @if ($daftar->isEmpty())
            <x-ui.kartu>
                <x-ui.kosong
                    ikon="label"
                    judul="Katalog masih kosong"
                    pesan="Tambahkan jenis sampah beserta harganya. Tanpa katalog, setoran tidak bisa ditimbang.">
                    <x-slot:aksi>
                        <x-ui.tombol wire:click="bukaForm" ikon="plus">Tambah jenis pertama</x-ui.tombol>
                    </x-slot:aksi>
                </x-ui.kosong>
            </x-ui.kartu>
        @else
            <div class="space-y-6">
                @foreach ($daftar as $namaKategori => $baris)
                    <x-ui.kartu padat :judul="$namaKategori">
                        <x-ui.tabel :kepala="['Jenis sampah', 'Satuan', 'Harga', 'Status', '']">
                            @foreach ($baris as $harga)
                                <tr class="{{ $harga->is_active ? '' : 'opacity-60' }}">
                                    <td class="px-4 py-3 font-medium text-primary-900">{{ $harga->jenis_sampah }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $harga->satuan }}</td>
                                    <td class="px-4 py-3 font-medium text-primary-900">
                                        {{ Rupiah::format($harga->harga_per_satuan) }}
                                        <span class="text-xs font-normal text-gray-500">/{{ $harga->satuan }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-ui.lencana :warna="$harga->is_active ? 'hijau' : 'abu'"
                                                      :label="$harga->is_active ? 'Diterima' : 'Tidak diterima'"/>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <div class="flex justify-end gap-1">
                                            <button type="button" wire:click="bukaForm({{ $harga->id }})"
                                                    class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-primary-900"
                                                    aria-label="Sunting harga {{ $harga->jenis_sampah }}">
                                                <x-ui.ikon nama="pensil" class="h-4 w-4"/>
                                            </button>
                                            <button type="button" wire:click="ubahAktif({{ $harga->id }})"
                                                    class="rounded-lg p-2 transition hover:bg-gray-100
                                                           {{ $harga->is_active ? 'text-gray-500' : 'text-primary-600' }}"
                                                    aria-label="{{ $harga->is_active ? 'Berhenti menerima' : 'Terima kembali' }} {{ $harga->jenis_sampah }}">
                                                <x-ui.ikon :nama="$harga->is_active ? 'silang' : 'centang'" class="h-4 w-4"/>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </x-ui.tabel>
                    </x-ui.kartu>
                @endforeach
            </div>
        @endif
    @endif

    <x-modal active="formTerbuka">
        <form wire:submit="simpan">
            <div class="border-b border-gray-100 px-6 py-4">
                <h2 class="text-base font-semibold text-primary-900">
                    {{ $hargaId ? 'Sunting jenis sampah' : 'Tambah jenis sampah' }}
                </h2>
            </div>

            <div class="space-y-4 px-6 py-5">
                <x-ui.bidang label="Jenis sampah" untuk="jenis-sampah" :wajib="true"
                             petunjuk="Pakai nama yang dikenali warga, mis. &quot;Botol PET bening&quot;."
                             :galat="$errors->first('jenisSampah')">
                    <x-ui.isian id="jenis-sampah" wire:model="jenisSampah" :galat="$errors->has('jenisSampah')"/>
                </x-ui.bidang>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.bidang label="Kategori" untuk="kategori-harga" :wajib="true"
                                 :galat="$errors->first('kategori')">
                        <x-ui.pilihan id="kategori-harga" wire:model="kategori" :opsi="$kategoriTersedia"/>
                    </x-ui.bidang>

                    <x-ui.bidang label="Satuan" untuk="satuan-harga" :wajib="true"
                                 :galat="$errors->first('satuan')">
                        <x-ui.isian id="satuan-harga" wire:model="satuan" placeholder="kg"/>
                    </x-ui.bidang>
                </div>

                <x-ui.bidang label="Harga per satuan" untuk="harga-satuan" :wajib="true"
                             petunjuk="Rupiah penuh tanpa titik. Contoh: 3500 untuk Rp 3.500."
                             :galat="$errors->first('hargaPerSatuan')">
                    <x-ui.isian id="harga-satuan" wire:model="hargaPerSatuan" inputmode="numeric"
                                placeholder="3500" :galat="$errors->has('hargaPerSatuan')"/>
                </x-ui.bidang>

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" wire:model="isActive"
                           class="h-4 w-4 rounded border-gray-300 text-primary-500 focus:ring-primary-200">
                    Sedang menerima jenis ini
                </label>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-100 px-6 py-4">
                <x-ui.tombol jenis="kedua" wire:click="tutupForm">Batal</x-ui.tombol>
                <x-ui.tombol tipe="submit">{{ $hargaId ? 'Simpan perubahan' : 'Tambah' }}</x-ui.tombol>
            </div>
        </form>
    </x-modal>
</div>
