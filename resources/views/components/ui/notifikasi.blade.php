{{--
    Pesan sesaat dari komponen Livewire.

    Dipasang satu kali di layout, bukan diulang di tiap halaman: pesan
    keberhasilan yang muncul di satu halaman dan hilang di halaman lain
    membuat pengguna tidak pernah yakin tindakannya tersimpan.

    role="status" dengan aria-live="polite" supaya pembaca layar
    mengumumkannya tanpa memotong apa yang sedang dibacakan.
--}}
<div x-data="{ tampil: false, pesan: '', jenis: 'sukses' }"
     x-on:pesan.window="pesan = $event.detail.pesan; jenis = $event.detail.jenis ?? 'sukses'; tampil = true; setTimeout(() => tampil = false, 5000)"
     x-show="tampil"
     x-transition
     x-cloak
     role="status"
     aria-live="polite"
     class="mb-5">
    <div class="flex items-start gap-3 rounded-xl border p-4 text-sm"
         :class="jenis === 'galat'
             ? 'border-red-200 bg-red-50 text-red-800'
             : 'border-primary-100 bg-primary-50 text-primary-900'">
        <template x-if="jenis === 'galat'">
            <x-ui.ikon nama="peringatan" class="h-5 w-5 flex-none text-red-600"/>
        </template>
        <template x-if="jenis !== 'galat'">
            <x-ui.ikon nama="centang" class="h-5 w-5 flex-none text-primary-600"/>
        </template>

        <p class="flex-1" x-text="pesan"></p>

        <button type="button" @click="tampil = false"
                class="rounded p-0.5 opacity-60 transition hover:opacity-100"
                aria-label="Tutup pemberitahuan">
            <x-ui.ikon nama="silang" class="h-4 w-4"/>
        </button>
    </div>
</div>
