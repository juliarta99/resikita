@props(['judul' => 'Belum ada data', 'pesan' => null, 'ikon' => 'kosong'])

{{--
    Keadaan kosong.

    Selalu menyebutkan langkah berikutnya, tidak berhenti pada "tidak
    ada data". Layar kosong tanpa penjelasan tidak bisa dibedakan dari
    layar yang gagal memuat, dan pengguna akan menunggu sesuatu yang
    tidak akan pernah datang.
--}}
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 text-gray-400">
        <x-ui.ikon :nama="$ikon" class="h-6 w-6"/>
    </span>

    <p class="mt-4 text-sm font-semibold text-primary-900">{{ $judul }}</p>

    @if ($pesan)
        <p class="mt-1 max-w-sm text-sm text-gray-500">{{ $pesan }}</p>
    @endif

    @isset($aksi)
        <div class="mt-5">{{ $aksi }}</div>
    @endisset
</div>
