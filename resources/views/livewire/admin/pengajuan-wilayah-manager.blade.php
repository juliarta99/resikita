<div>
    <x-ui.kepala-halaman
        judul="Pengajuan wilayah"
        keterangan="Permohonan pemerintah daerah untuk bergabung dengan Resikita. Menyetujui berarti menyerahkan kewenangan atas seluruh laporan daerah itu.">
        <x-slot:aksi>
            <x-ui.pilihan wire:model.live="status" kosong="Semua status" :opsi="$statusTersedia"
                          class="w-auto" aria-label="Saring status pengajuan"/>
        </x-slot:aksi>
    </x-ui.kepala-halaman>

    @if ($jumlahMenunggu > 0)
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4">
            <p class="text-sm text-amber-900">
                <span class="font-semibold">{{ number_format($jumlahMenunggu) }} pengajuan</span>
                menunggu ditinjau. Setiap hari tertunda adalah satu daerah yang laporannya masih
                menumpuk di meja fasilitator.
            </p>
        </div>
    @endif

    @if ($daftar->isEmpty())
        <x-ui.kartu>
            <x-ui.kosong ikon="peta" judul="Tidak ada pengajuan"
                         pesan="Belum ada permohonan yang cocok dengan penyaring ini."/>
        </x-ui.kartu>
    @else
        <div class="space-y-4">
            @foreach ($daftar as $pengajuan)
                <x-ui.kartu>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="text-base font-semibold text-primary-900">
                                {{ $pengajuan->wilayah?->namaLengkap() ?? 'Wilayah terhapus' }}
                            </h3>
                            <p class="text-xs text-gray-500">
                                {{ $pengajuan->wilayah?->parent?->namaLengkap() }}
                                &middot; kode {{ $pengajuan->wilayah?->kode }}
                            </p>
                        </div>
                        <x-ui.lencana :status="$pengajuan->status"/>
                    </div>

                    <dl class="mt-4 grid gap-4 border-t border-gray-100 pt-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <dt class="text-xs text-gray-500">Pemohon</dt>
                            <dd class="mt-0.5 font-medium text-primary-900">{{ $pengajuan->pemohon_nama }}</dd>
                            <dd class="text-xs text-gray-500">{{ $pengajuan->pemohon_jabatan }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Instansi</dt>
                            <dd class="mt-0.5 text-gray-700">{{ $pengajuan->instansi }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Kontak</dt>
                            <dd class="mt-0.5 break-all text-gray-700">{{ $pengajuan->pemohon_email }}</dd>
                            @if ($pengajuan->pemohon_phone)
                                <dd class="text-xs text-gray-500">{{ $pengajuan->pemohon_phone }}</dd>
                            @endif
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Diajukan</dt>
                            <dd class="mt-0.5 text-gray-700">
                                {{ $pengajuan->created_at->translatedFormat('j F Y') }}
                            </dd>
                            @if ($pengajuan->ditinjau_at)
                                <dd class="text-xs text-gray-500">
                                    Ditinjau {{ $pengajuan->ditinjau_at->translatedFormat('j M Y') }}
                                    oleh {{ $pengajuan->peninjau?->name }}
                                </dd>
                            @endif
                        </div>
                    </dl>

                    @if ($pengajuan->catatan)
                        <p class="mt-4 rounded-xl bg-gray-50 p-3 text-sm text-gray-600">
                            <span class="font-medium text-primary-900">Catatan peninjau:</span>
                            {{ $pengajuan->catatan }}
                        </p>
                    @endif

                    <div class="mt-4 flex flex-wrap gap-2 border-t border-gray-100 pt-4">
                        <x-ui.tombol jenis="kedua" ukuran="kecil" wire:click="unduhSurat({{ $pengajuan->id }})"
                                     ikon="unduh">
                            Unduh surat kewenangan
                        </x-ui.tombol>

                        @unless ($pengajuan->sudahDitinjau())
                            <x-ui.tombol ukuran="kecil" wire:click="setujui({{ $pengajuan->id }})" ikon="centang"
                                         wire:confirm="Setujui pengajuan {{ $pengajuan->wilayah?->namaLengkap() }}? Akun pemerintah akan diterbitkan dan wilayah menjadi terverifikasi."
                                         wire:loading.attr="disabled">
                                Setujui
                            </x-ui.tombol>

                            <x-ui.tombol jenis="bahaya" ukuran="kecil"
                                         wire:click="bukaFormTolak({{ $pengajuan->id }})" ikon="silang">
                                Tolak
                            </x-ui.tombol>
                        @endunless
                    </div>

                    @if ($tolakId === $pengajuan->id)
                        <form wire:submit="tolak" class="mt-4 space-y-3 rounded-xl bg-red-50 p-4">
                            <x-ui.bidang label="Alasan penolakan" untuk="tolak-{{ $pengajuan->id }}" :wajib="true"
                                         petunjuk="Pemohon membaca alasan ini. Sebutkan apa yang perlu diperbaiki."
                                         :galat="$errors->first('catatanTolak')">
                                <textarea id="tolak-{{ $pengajuan->id }}" wire:model="catatanTolak" rows="3"
                                          class="block w-full rounded-xl border border-red-200 bg-white px-3.5 py-2.5
                                                 text-sm focus:border-red-400 focus:outline-none focus:ring-4
                                                 focus:ring-red-100"></textarea>
                            </x-ui.bidang>

                            <div class="flex gap-2">
                                <x-ui.tombol tipe="submit" jenis="bahaya" ukuran="kecil">Kirim penolakan</x-ui.tombol>
                                <x-ui.tombol jenis="polos" ukuran="kecil" wire:click="$set('tolakId', null)">
                                    Batal
                                </x-ui.tombol>
                            </div>
                        </form>
                    @endif
                </x-ui.kartu>
            @endforeach
        </div>

        <div class="mt-6">{{ $daftar->links() }}</div>
    @endif
</div>
