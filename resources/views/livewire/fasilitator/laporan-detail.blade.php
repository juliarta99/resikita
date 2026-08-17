@php $bolehCatat = auth()->user()->can('tindakLanjut', $laporan); @endphp

<div>
    <a href="{{ route('fasilitator.laporan') }}" wire:navigate
       class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-primary-900">
        <x-ui.ikon nama="keluar" class="h-4 w-4 rotate-180"/>
        Kembali ke papan laporan
    </a>

    <x-ui.kepala-halaman :judul="$laporan->judul">
        <x-slot:keterangan>
            <span class="font-mono">{{ $laporan->tiket }}</span> &middot;
            {{ $laporan->created_at->translatedFormat('j F Y, H:i') }} WIB
            oleh {{ $laporan->pelapor?->name ?? 'pengguna terhapus' }}
        </x-slot:keterangan>

        <x-slot:aksi>
            <x-ui.lencana :status="$laporan->status"/>
            @if ($laporan->alasan_routing)
                <x-ui.lencana warna="kuning" :label="$laporan->alasan_routing->label()"/>
            @endif
        </x-slot:aksi>
    </x-ui.kepala-halaman>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">

            @if ($laporan->foto->isNotEmpty())
                <x-ui.kartu padat judul="Foto dari pelapor">
                    <div class="grid grid-cols-2 gap-2 p-5 pt-0 sm:grid-cols-3">
                        @foreach ($laporan->foto as $foto)
                            <a href="{{ $foto->url() }}" target="_blank" rel="noopener"
                               class="block overflow-hidden rounded-xl bg-gray-100">
                                <img src="{{ $foto->url() }}"
                                     alt="Foto laporan {{ $laporan->tiket }} nomor {{ $loop->iteration }}"
                                     loading="lazy" class="aspect-4/3 w-full object-cover">
                            </a>
                        @endforeach
                    </div>
                </x-ui.kartu>
            @endif

            <x-ui.kartu judul="Keterangan">
                <p class="whitespace-pre-line text-sm leading-relaxed text-gray-700">{{ $laporan->deskripsi }}</p>

                <dl class="mt-5 grid gap-4 border-t border-gray-100 pt-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Kategori</dt>
                        <dd class="mt-0.5 text-sm text-primary-900">{{ $laporan->kategori?->nama ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Alamat</dt>
                        <dd class="mt-0.5 text-sm text-primary-900">{{ $laporan->alamat ?? 'Tidak tersedia' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Wilayah</dt>
                        <dd class="mt-0.5 text-sm text-primary-900">
                            {{ collect([
                                $laporan->desa?->nama, $laporan->kecamatan?->nama,
                                $laporan->kabupaten?->nama, $laporan->provinsi?->nama,
                            ])->filter()->implode(', ') ?: 'Belum teridentifikasi' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Titik koordinat</dt>
                        <dd class="mt-0.5 text-sm">
                            <a href="https://www.google.com/maps?q={{ $laporan->latitude }},{{ $laporan->longitude }}"
                               target="_blank" rel="noopener"
                               class="font-medium text-primary-700 underline hover:text-primary-900">
                                {{ number_format((float) $laporan->latitude, 6) }},
                                {{ number_format((float) $laporan->longitude, 6) }}
                            </a>
                        </dd>
                    </div>
                </dl>
            </x-ui.kartu>

            <x-ui.kartu judul="Catatan tindak lanjut"
                        keterangan="Riwayat komunikasi dengan dinas atas laporan ini.">
                <x-slot:aksi>
                    @if ($bolehCatat)
                        <x-ui.tombol ukuran="kecil" wire:click="bukaForm" ikon="plus">Catat kontak</x-ui.tombol>
                    @endif
                </x-slot:aksi>

                @if ($laporan->tindakLanjut->isEmpty())
                    <x-ui.kosong
                        ikon="jejak"
                        judul="Belum ada tindak lanjut"
                        pesan="Laporan ini belum pernah diteruskan ke dinas mana pun. Catat setiap kontak yang Anda lakukan."/>
                @else
                    <ol class="space-y-4">
                        @foreach ($laporan->tindakLanjut->sortByDesc('tanggal_kontak') as $catatan)
                            <li class="rounded-xl border border-gray-200 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-primary-900">{{ $catatan->nama_dinas }}</p>
                                        @if ($catatan->kontak_dinas)
                                            <p class="text-xs text-gray-500">{{ $catatan->kontak_dinas }}</p>
                                        @endif
                                    </div>
                                    <span class="flex-none text-xs text-gray-400">
                                        {{ $catatan->tanggal_kontak?->translatedFormat('j F Y') }}
                                    </span>
                                </div>

                                <p class="mt-3 whitespace-pre-line text-sm text-gray-700">{{ $catatan->hasil }}</p>

                                <p class="mt-3 border-t border-gray-100 pt-2 text-xs text-gray-400">
                                    Dicatat oleh {{ $catatan->fasilitator?->name ?? 'fasilitator' }}
                                    @if ($catatan->lampiran_path)
                                        &middot; ada lampiran
                                    @endif
                                </p>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-ui.kartu>
        </div>

        <div class="space-y-6">
            <x-ui.kartu judul="Kenapa laporan ini di sini">
                <p class="text-sm leading-relaxed text-gray-600">
                    Wilayah asal laporan ini belum terdaftar di Resikita, sehingga tidak ada pemerintah
                    daerah yang bisa menerimanya secara otomatis. Tugas fasilitator adalah meneruskannya
                    ke dinas terkait di luar sistem, lalu mencatat hasilnya di sini.
                </p>
                <p class="mt-3 text-sm leading-relaxed text-gray-600">
                    Setiap laporan seperti ini juga menaikkan skor prioritas wilayahnya di
                    <a href="{{ route('fasilitator.prioritas') }}" wire:navigate
                       class="font-medium text-primary-700 underline">papan prioritas</a>.
                </p>
            </x-ui.kartu>

            @if ($formTerbuka && $bolehCatat)
                <x-ui.kartu judul="Catat kontak ke dinas">
                    <form wire:submit="simpan" class="space-y-4">
                        <x-ui.bidang label="Nama dinas" untuk="nama-dinas" :wajib="true"
                                     petunjuk="Contoh: Dinas Lingkungan Hidup Kabupaten Sikka"
                                     :galat="$errors->first('namaDinas')">
                            <x-ui.isian id="nama-dinas" wire:model="namaDinas" :galat="$errors->has('namaDinas')"/>
                        </x-ui.bidang>

                        <x-ui.bidang label="Kontak" untuk="kontak-dinas"
                                     petunjuk="Nama pejabat, nomor telepon, atau email."
                                     :galat="$errors->first('kontakDinas')">
                            <x-ui.isian id="kontak-dinas" wire:model="kontakDinas"/>
                        </x-ui.bidang>

                        <x-ui.bidang label="Tanggal kontak" untuk="tanggal-kontak" :wajib="true"
                                     :galat="$errors->first('tanggalKontak')">
                            <x-ui.isian id="tanggal-kontak" tipe="date" wire:model="tanggalKontak"
                                        :galat="$errors->has('tanggalKontak')"/>
                        </x-ui.bidang>

                        <x-ui.bidang label="Hasil komunikasi" untuk="hasil" :wajib="true"
                                     petunjuk="Apa yang disampaikan dan bagaimana tanggapannya."
                                     :galat="$errors->first('hasil')">
                            <textarea id="hasil" wire:model="hasil" rows="5"
                                      class="block w-full rounded-xl border px-3.5 py-2.5 text-sm shadow-sm
                                             focus:outline-none focus:ring-4
                                             {{ $errors->has('hasil')
                                                 ? 'border-red-300 focus:border-red-500 focus:ring-red-200'
                                                 : 'border-gray-300 focus:border-primary-500 focus:ring-primary-100' }}"></textarea>
                        </x-ui.bidang>

                        <x-ui.bidang label="Lampiran" untuk="lampiran"
                                     petunjuk="Opsional. PDF atau gambar, maksimal 5 MB. Disimpan tidak publik."
                                     :galat="$errors->first('lampiran')">
                            <input id="lampiran" type="file" wire:model="lampiran" accept=".pdf,image/*"
                                   class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0
                                          file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium
                                          file:text-primary-700 hover:file:bg-primary-100">
                            <div wire:loading wire:target="lampiran" class="text-xs text-gray-500">Mengunggah…</div>
                        </x-ui.bidang>

                        <div class="flex gap-2 border-t border-gray-100 pt-4">
                            <x-ui.tombol tipe="submit" class="flex-1" wire:loading.attr="disabled" wire:target="simpan">
                                Simpan catatan
                            </x-ui.tombol>
                            <x-ui.tombol jenis="polos" wire:click="$set('formTerbuka', false)">Batal</x-ui.tombol>
                        </div>
                    </form>
                </x-ui.kartu>
            @endif
        </div>
    </div>
</div>
