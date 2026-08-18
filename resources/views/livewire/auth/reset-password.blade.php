<div>
    <h1 class="text-xl font-bold text-primary-900">Setel kata sandi baru</h1>
    <p class="mt-1 text-sm text-gray-500">
        Masukkan kode enam angka yang dikirim ke email Anda, lalu kata sandi barunya.
    </p>

    <form wire:submit="setel" class="mt-6 space-y-4">
        <x-ui.bidang label="Email" untuk="email" :wajib="true" :galat="$errors->first('email')">
            <x-ui.isian id="email" tipe="email" wire:model="email" autocomplete="username"
                        :galat="$errors->has('email')"/>
        </x-ui.bidang>

        <x-ui.bidang label="Kode pemulihan" untuk="kode" :wajib="true"
                     petunjuk="Enam angka, berlaku 10 menit sejak dikirim."
                     :galat="$errors->first('kode')">
            <x-ui.isian id="kode" wire:model="kode" inputmode="numeric" autocomplete="one-time-code"
                        maxlength="6" placeholder="000000"
                        class="tracking-[0.4em] text-center font-mono text-lg"
                        :galat="$errors->has('kode')"/>
        </x-ui.bidang>

        <x-ui.bidang label="Kata sandi baru" untuk="password" :wajib="true" :galat="$errors->first('password')">
            <x-ui.isian id="password" tipe="password" wire:model="password" autocomplete="new-password"
                        :galat="$errors->has('password')"/>
        </x-ui.bidang>

        <x-ui.bidang label="Ulangi kata sandi baru" untuk="password_confirmation" :wajib="true">
            <x-ui.isian id="password_confirmation" tipe="password" wire:model="password_confirmation"
                        autocomplete="new-password"/>
        </x-ui.bidang>

        <x-ui.tombol tipe="submit" class="w-full" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="setel">Simpan kata sandi baru</span>
            <span wire:loading wire:target="setel">Menyimpan…</span>
        </x-ui.tombol>
    </form>

    <p class="mt-6 border-t border-gray-100 pt-5 text-center text-sm text-gray-500">
        Belum punya kode?
        <a href="{{ route('lupa-password') }}" wire:navigate class="font-medium text-primary-700 hover:text-primary-900">
            Minta kode baru
        </a>
    </p>
</div>
