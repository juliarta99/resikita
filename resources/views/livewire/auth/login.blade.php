<div>
    <h1 class="text-xl font-bold text-primary-900">Masuk ke panel</h1>
    <p class="mt-1 text-sm text-gray-500">
        Untuk pemerintah daerah, fasilitator, bank sampah, UMKM, dan admin.
    </p>

    <form wire:submit="masuk" class="mt-6 space-y-4">
        <x-ui.bidang label="Email" untuk="email" :wajib="true" :galat="$errors->first('email')">
            <x-ui.isian
                id="email"
                tipe="email"
                wire:model="email"
                autocomplete="username"
                autofocus
                placeholder="nama@instansi.go.id"
                :galat="$errors->has('email')"
                :aria-describedby="$errors->has('email') ? 'email-galat' : null"
            />
        </x-ui.bidang>

        <x-ui.bidang label="Kata sandi" untuk="password" :wajib="true" :galat="$errors->first('password')">
            <div x-data="{ terlihat: false }" class="relative">
                <x-ui.isian
                    id="password"
                    x-bind:type="terlihat ? 'text' : 'password'"
                    wire:model="password"
                    autocomplete="current-password"
                    class="pr-11"
                    :galat="$errors->has('password')"
                    :aria-describedby="$errors->has('password') ? 'password-galat' : null"
                />
                <button type="button" @click="terlihat = !terlihat"
                        class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600"
                        x-bind:aria-label="terlihat ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                    <span x-show="!terlihat"><x-ui.ikon nama="mata" class="h-4 w-4"/></span>
                    <span x-show="terlihat" x-cloak><x-ui.ikon nama="mata-tutup" class="h-4 w-4"/></span>
                </button>
            </div>
        </x-ui.bidang>

        <div class="flex items-center justify-between gap-3">
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" wire:model="ingatSaya"
                       class="h-4 w-4 rounded border-gray-300 text-primary-500 focus:ring-primary-200">
                Ingat saya
            </label>

            <a href="{{ route('lupa-password') }}" wire:navigate
               class="text-sm font-medium text-primary-700 hover:text-primary-900">
                Lupa kata sandi?
            </a>
        </div>

        <x-ui.tombol tipe="submit" class="w-full" wire:loading.attr="disabled" wire:target="masuk">
            <span wire:loading.remove wire:target="masuk">Masuk</span>
            <span wire:loading wire:target="masuk">Memeriksa…</span>
        </x-ui.tombol>
    </form>

    <div class="mt-6 space-y-1.5 border-t border-gray-100 pt-5 text-center text-sm text-gray-500">
        <p>
            Daerah Anda belum bergabung?
            <a href="{{ route('publik.pengajuan-wilayah') }}" wire:navigate
               class="font-medium text-primary-700 hover:text-primary-900">
                Ajukan pendaftaran wilayah
            </a>
        </p>
        <p>
            Punya usaha daur ulang?
            <a href="{{ route('publik.pendaftaran-umkm') }}" wire:navigate
               class="font-medium text-primary-700 hover:text-primary-900">
                Daftarkan UMKM
            </a>
        </p>
    </div>
</div>
