<div>
    <x-ui.kepala-halaman
        judul="Profil akun"
        keterangan="Data diri dan kredensial masuk Anda."/>

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Ringkasan akun --}}
        <x-ui.kartu>
            <div class="flex flex-col items-center text-center">
                @if ($pengguna->urlAvatar())
                    <img src="{{ $pengguna->urlAvatar() }}" alt="Foto profil {{ $pengguna->name }}"
                         class="h-20 w-20 rounded-2xl object-cover">
                @else
                    <span class="flex h-20 w-20 items-center justify-center rounded-2xl bg-primary-50 text-xl font-bold text-primary-700"
                          aria-hidden="true">
                        {{ $pengguna->inisial() }}
                    </span>
                @endif

                <p class="mt-3 text-base font-semibold text-primary-900">{{ $pengguna->name }}</p>
                <p class="text-sm text-gray-500">{{ $pengguna->email }}</p>

                <x-ui.lencana class="mt-3" warna="hijau" :label="$pengguna->roleUtama()?->label() ?? 'Tanpa peran'"/>
            </div>

            <dl class="mt-6 space-y-3 border-t border-gray-100 pt-5 text-sm">
                @if ($pengguna->wilayah)
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Wilayah</dt>
                        <dd class="text-right font-medium text-primary-900">{{ $pengguna->wilayah->namaLengkap() }}</dd>
                    </div>
                @endif
                @if ($pengguna->bankSampah)
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Bank sampah</dt>
                        <dd class="text-right font-medium text-primary-900">{{ $pengguna->bankSampah->nama }}</dd>
                    </div>
                @endif
                @if ($pengguna->umkm)
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">UMKM</dt>
                        <dd class="text-right font-medium text-primary-900">{{ $pengguna->umkm->nama }}</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">Email terverifikasi</dt>
                    <dd class="text-right">
                        <x-ui.lencana :warna="$pengguna->email_verified_at ? 'hijau' : 'kuning'"
                                      :label="$pengguna->email_verified_at ? 'Sudah' : 'Belum'"/>
                    </dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">Bergabung</dt>
                    <dd class="text-right font-medium text-primary-900">
                        {{ $pengguna->created_at->translatedFormat('j F Y') }}
                    </dd>
                </div>
            </dl>
        </x-ui.kartu>

        <div class="space-y-6 lg:col-span-2">

            {{-- Data diri --}}
            <x-ui.kartu judul="Data diri">
                <form wire:submit="simpanProfil" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.bidang label="Nama lengkap" untuk="nama" :wajib="true" :galat="$errors->first('name')">
                            <x-ui.isian id="nama" wire:model="name" :galat="$errors->has('name')"/>
                        </x-ui.bidang>

                        <x-ui.bidang label="Email" untuk="email" :wajib="true"
                                     petunjuk="Mengganti email membatalkan status verifikasinya."
                                     :galat="$errors->first('email')">
                            <x-ui.isian id="email" tipe="email" wire:model="email" :galat="$errors->has('email')"/>
                        </x-ui.bidang>

                        <x-ui.bidang label="Nomor WhatsApp" untuk="phone"
                                     petunjuk="Opsional, hanya untuk notifikasi."
                                     :galat="$errors->first('phone')">
                            <x-ui.isian id="phone" wire:model="phone" placeholder="08xxxxxxxxxx"
                                        :galat="$errors->has('phone')"/>
                        </x-ui.bidang>

                        <x-ui.bidang label="Tanggal lahir" untuk="tanggal-lahir"
                                     :galat="$errors->first('tanggalLahir')">
                            <x-ui.isian id="tanggal-lahir" tipe="date" wire:model="tanggalLahir"
                                        :galat="$errors->has('tanggalLahir')"/>
                        </x-ui.bidang>

                        <x-ui.bidang label="Jenis kelamin" untuk="jenis-kelamin"
                                     :galat="$errors->first('jenisKelamin')">
                            <x-ui.pilihan id="jenis-kelamin" wire:model="jenisKelamin"
                                          kosong="Tidak disebutkan" :opsi="$jenisKelaminTersedia"/>
                        </x-ui.bidang>

                        <x-ui.bidang label="Foto profil" untuk="avatar"
                                     petunjuk="JPG, PNG, atau WebP. Maksimal 2 MB."
                                     :galat="$errors->first('avatarBaru')">
                            <input id="avatar" type="file" wire:model="avatarBaru" accept="image/*"
                                   class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0
                                          file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium
                                          file:text-primary-700 hover:file:bg-primary-100">
                            <div wire:loading wire:target="avatarBaru" class="text-xs text-gray-500">Mengunggah…</div>
                        </x-ui.bidang>
                    </div>

                    <div class="flex justify-end border-t border-gray-100 pt-4">
                        <x-ui.tombol tipe="submit" wire:loading.attr="disabled" wire:target="simpanProfil">
                            Simpan perubahan
                        </x-ui.tombol>
                    </div>
                </form>
            </x-ui.kartu>

            {{-- Kata sandi --}}
            <x-ui.kartu judul="Ganti kata sandi"
                        keterangan="Mengganti kata sandi memutus sesi di semua perangkat lain, termasuk aplikasi ponsel.">
                <form wire:submit="gantiPassword" class="space-y-4">
                    <x-ui.bidang label="Kata sandi saat ini" untuk="password-lama" :wajib="true"
                                 :galat="$errors->first('passwordLama')">
                        <x-ui.isian id="password-lama" tipe="password" wire:model="passwordLama"
                                    autocomplete="current-password" :galat="$errors->has('passwordLama')"/>
                    </x-ui.bidang>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.bidang label="Kata sandi baru" untuk="password-baru" :wajib="true"
                                     :galat="$errors->first('passwordBaru')">
                            <x-ui.isian id="password-baru" tipe="password" wire:model="passwordBaru"
                                        autocomplete="new-password" :galat="$errors->has('passwordBaru')"/>
                        </x-ui.bidang>

                        <x-ui.bidang label="Ulangi kata sandi baru" untuk="password-baru-ulang" :wajib="true">
                            <x-ui.isian id="password-baru-ulang" tipe="password"
                                        wire:model="passwordBaru_confirmation" autocomplete="new-password"/>
                        </x-ui.bidang>
                    </div>

                    <div class="flex justify-end border-t border-gray-100 pt-4">
                        <x-ui.tombol tipe="submit" jenis="kedua" wire:loading.attr="disabled" wire:target="gantiPassword">
                            Ganti kata sandi
                        </x-ui.tombol>
                    </div>
                </form>
            </x-ui.kartu>
        </div>
    </div>
</div>
