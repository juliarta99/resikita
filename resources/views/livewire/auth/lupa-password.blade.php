<div>
    @if ($terkirim)
        <div class="text-center">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-50 text-primary-600">
                <x-ui.ikon nama="centang" class="h-6 w-6"/>
            </span>

            <h1 class="mt-4 text-xl font-bold text-primary-900">Kode sudah dikirim</h1>
            <p class="mt-2 text-sm text-gray-600">
                Kalau <span class="font-medium text-primary-900">{{ $email }}</span> terdaftar di Resikita,
                kode pemulihan sudah dikirim ke sana. Kode berlaku 10 menit.
            </p>

            <x-ui.tombol :tautan="route('reset-password', ['email' => $email])" class="mt-6 w-full">
                Masukkan kode
            </x-ui.tombol>

            <button type="button" wire:click="$set('terkirim', false)"
                    class="mt-3 text-sm font-medium text-gray-500 hover:text-primary-900">
                Kirim ulang ke email lain
            </button>
        </div>
    @else
        <h1 class="text-xl font-bold text-primary-900">Lupa kata sandi</h1>
        <p class="mt-1 text-sm text-gray-500">
            Masukkan email akun Anda. Kami kirimkan kode untuk menyetel kata sandi baru.
        </p>

        <form wire:submit="kirim" class="mt-6 space-y-4">
            <x-ui.bidang label="Email" untuk="email" :wajib="true" :galat="$errors->first('email')">
                <x-ui.isian id="email" tipe="email" wire:model="email" autocomplete="username" autofocus
                            :galat="$errors->has('email')"/>
            </x-ui.bidang>

            <x-ui.tombol tipe="submit" class="w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="kirim">Kirim kode</span>
                <span wire:loading wire:target="kirim">Mengirim…</span>
            </x-ui.tombol>
        </form>
    @endif

    <p class="mt-6 border-t border-gray-100 pt-5 text-center text-sm text-gray-500">
        <a href="{{ route('masuk') }}" wire:navigate class="font-medium text-primary-700 hover:text-primary-900">
            Kembali ke halaman masuk
        </a>
    </p>
</div>
