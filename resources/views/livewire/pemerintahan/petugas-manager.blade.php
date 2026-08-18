<div>
    <x-ui.kepala-halaman
        judul="Petugas lapangan"
        keterangan="Akun petugas yang menangani laporan di wilayah Anda. Petugas bekerja lewat aplikasi ponsel, bukan panel web.">
        <x-slot:aksi>
            <x-ui.tombol wire:click="bukaForm" ikon="plus">Tambah petugas</x-ui.tombol>
        </x-slot:aksi>
    </x-ui.kepala-halaman>

    <x-ui.kartu padat>
        <div class="border-b border-gray-100 p-5">
            <label for="cari-petugas" class="sr-only">Cari petugas</label>
            <div class="relative max-w-sm">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <x-ui.ikon nama="cari" class="h-4 w-4"/>
                </span>
                <x-ui.isian id="cari-petugas" wire:model.live.debounce.400ms="cari" class="pl-9"
                            placeholder="Cari nama atau email"/>
            </div>
        </div>

        @if ($daftar->isEmpty())
            <x-ui.kosong
                ikon="orang"
                judul="Belum ada petugas"
                pesan="Tambahkan petugas lapangan supaya laporan yang sudah diverifikasi bisa ditugaskan kepada seseorang.">
                <x-slot:aksi>
                    <x-ui.tombol wire:click="bukaForm" ikon="plus">Tambah petugas</x-ui.tombol>
                </x-slot:aksi>
            </x-ui.kosong>
        @else
            <x-ui.tabel :kepala="['Nama', 'Kontak', 'Wilayah', 'Status', '']">
                @foreach ($daftar as $petugas)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium text-primary-900">{{ $petugas->name }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            <p>{{ $petugas->email }}</p>
                            @if ($petugas->phone)
                                <p class="text-xs text-gray-400">{{ $petugas->phone }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $petugas->wilayah?->namaLengkap() ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <x-ui.lencana :warna="$petugas->is_active ? 'hijau' : 'abu'"
                                          :label="$petugas->is_active ? 'Aktif' : 'Nonaktif'"/>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <div class="flex justify-end gap-1">
                                <button type="button" wire:click="bukaForm({{ $petugas->id }})"
                                        class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-primary-900"
                                        aria-label="Sunting data {{ $petugas->name }}">
                                    <x-ui.ikon nama="pensil" class="h-4 w-4"/>
                                </button>

                                <button type="button" wire:click="ubahStatus({{ $petugas->id }})"
                                        wire:confirm="{{ $petugas->is_active
                                            ? 'Nonaktifkan akun '.$petugas->name.'? Sesi di ponselnya akan langsung diputus.'
                                            : 'Aktifkan kembali akun '.$petugas->name.'?' }}"
                                        class="rounded-lg p-2 transition hover:bg-gray-100
                                               {{ $petugas->is_active ? 'text-red-500 hover:text-red-700' : 'text-primary-600' }}"
                                        aria-label="{{ $petugas->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ $petugas->name }}">
                                    <x-ui.ikon :nama="$petugas->is_active ? 'silang' : 'centang'" class="h-4 w-4"/>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-ui.tabel>

            <div class="border-t border-gray-100 px-5 py-3">{{ $daftar->links() }}</div>
        @endif
    </x-ui.kartu>

    {{-- Formulir --}}
    <x-modal active="formTerbuka">
        <form wire:submit="simpan">
            <div class="border-b border-gray-100 px-6 py-4">
                <h2 class="text-base font-semibold text-primary-900">
                    {{ $petugasId ? 'Sunting petugas' : 'Tambah petugas' }}
                </h2>
            </div>

            <div class="space-y-4 px-6 py-5">
                <x-ui.bidang label="Nama lengkap" untuk="nama-petugas" :wajib="true" :galat="$errors->first('name')">
                    <x-ui.isian id="nama-petugas" wire:model="name" :galat="$errors->has('name')"/>
                </x-ui.bidang>

                <x-ui.bidang label="Email" untuk="email-petugas" :wajib="true" :galat="$errors->first('email')">
                    <x-ui.isian id="email-petugas" tipe="email" wire:model="email" :galat="$errors->has('email')"/>
                </x-ui.bidang>

                <x-ui.bidang label="Nomor WhatsApp" untuk="phone-petugas"
                             petunjuk="Opsional. Dipakai untuk notifikasi penugasan."
                             :galat="$errors->first('phone')">
                    <x-ui.isian id="phone-petugas" wire:model="phone" placeholder="08xxxxxxxxxx"
                                :galat="$errors->has('phone')"/>
                </x-ui.bidang>

                <x-ui.bidang label="Wilayah penempatan" untuk="wilayah-petugas" :wajib="true"
                             :galat="$errors->first('wilayahId')">
                    @if ($wilayahTersedia->isEmpty())
                        <p class="rounded-xl bg-amber-50 p-3 text-xs text-amber-800">
                            Akun Anda belum terhubung ke wilayah mana pun, jadi belum ada wilayah yang bisa dipilih.
                        </p>
                    @else
                        <x-ui.pilihan id="wilayah-petugas" wire:model="wilayahId" kosong="Pilih wilayah"
                                      :opsi="$wilayahTersedia->mapWithKeys(fn ($w) => [$w->id => $w->namaLengkap()])->all()"
                                      :galat="$errors->has('wilayahId')"/>
                    @endif
                </x-ui.bidang>

                @unless ($petugasId)
                    <div class="flex items-start gap-2.5 rounded-xl bg-gray-50 p-3">
                        <x-ui.ikon nama="info" class="h-4 w-4 flex-none text-gray-400"/>
                        <p class="text-xs text-gray-600">
                            Kata sandi tidak disetel di sini. Petugas menyetelnya sendiri lewat menu
                            <span class="font-medium">Lupa kata sandi</span> di halaman masuk, sehingga
                            kredensialnya tidak pernah melintas di pesan atau email.
                        </p>
                    </div>
                @endunless
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-100 px-6 py-4">
                <x-ui.tombol jenis="kedua" wire:click="tutupForm">Batal</x-ui.tombol>
                <x-ui.tombol tipe="submit" wire:loading.attr="disabled" wire:target="simpan">
                    {{ $petugasId ? 'Simpan perubahan' : 'Buat akun' }}
                </x-ui.tombol>
            </div>
        </form>
    </x-modal>
</div>
