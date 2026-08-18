@props(['status' => null, 'warna' => null, 'label' => null])

{{--
    Lencana status.

    Menerima backed enum yang punya label() dan warna(), seluruh enum
    status di app/Enums memenuhinya. Dengan begitu label dan warna
    sebuah status ditentukan satu kali di enumnya, bukan diulang di
    setiap tabel yang menampilkannya. Status baru cukup ditambahkan ke
    enum, dan setiap layar langsung ikut menampilkannya dengan benar.
--}}
@php
    $teks = $label;
    $nama = $warna;

    if ($status !== null) {
        $teks ??= method_exists($status, 'label') ? $status->label() : (string) $status;
        $nama ??= method_exists($status, 'warna') ? $status->warna() : 'gray';
    }

    $kelas = match ($nama) {
        'green', 'primary', 'hijau' => 'bg-primary-50 text-primary-700 ring-primary-100',
        'blue', 'biru' => 'bg-blue-50 text-blue-700 ring-blue-100',
        'cyan' => 'bg-cyan-50 text-cyan-700 ring-cyan-100',
        'orange', 'oranye' => 'bg-orange-50 text-orange-700 ring-orange-100',
        'amber', 'yellow', 'kuning' => 'bg-amber-50 text-amber-700 ring-amber-100',
        'red', 'merah' => 'bg-red-50 text-red-700 ring-red-100',
        'indigo', 'nila' => 'bg-indigo-50 text-indigo-700 ring-indigo-100',
        'purple', 'ungu' => 'bg-purple-50 text-purple-700 ring-purple-100',
        default => 'bg-gray-100 text-gray-600 ring-gray-200',
    };
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset '.$kelas,
]) }}>{{ $teks ?? '—' }}</span>
