@props(['status'])
@php
    $map = [
        'menunggu'     => ['Menunggu', 'bg-amber-50 text-amber-700'],
        'diverifikasi' => ['Diverifikasi', 'bg-blue-50 text-blue-700'],
        'ditugaskan'   => ['Ditugaskan', 'bg-indigo-50 text-indigo-700'],
        'proses'       => ['Diproses', 'bg-blue-50 text-blue-700'],
        'selesai'      => ['Selesai', 'bg-primary-50 text-primary-700'],
        'ditolak'      => ['Ditolak', 'bg-red-50 text-red-600'],
    ];
    [$label, $cls] = $map[$status] ?? [ucfirst($status), 'bg-gray-100 text-gray-600'];
@endphp
<span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $cls }}">{{ $label }}</span>