@props(['kepala' => []])

{{--
    Pembungkus tabel.

    Tabel selalu menggulir di dalam wadahnya sendiri. Tanpa itu, satu
    kolom lebar membuat seluruh halaman ikut bergeser mendatar di layar
    kecil, dan bilah navigasi ikut hilang dari pandangan.
--}}
<div {{ $attributes->merge(['class' => 'overflow-x-auto']) }}>
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        @if ($kepala !== [])
            <thead>
                <tr class="bg-gray-50">
                    @foreach ($kepala as $judul)
                        <th scope="col"
                            class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            {{ $judul }}
                        </th>
                    @endforeach
                </tr>
            </thead>
        @endif

        <tbody class="divide-y divide-gray-100 bg-white">
            {{ $slot }}
        </tbody>
    </table>
</div>
