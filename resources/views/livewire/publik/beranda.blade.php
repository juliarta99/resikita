@php use App\Support\Rupiah; @endphp

<div>
    {{-- Hero --}}
    <section class="relative overflow-hidden border-b border-gray-100 bg-linear-to-b from-primary-50/60 to-white">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20 lg:px-8">
            {{--
                Teks lebih dulu di urutan markup, gambar menyusul. Di layar
                lebar keduanya berdampingan; di layar sempit gambar turun ke
                bawah tombol, sehingga judul dan ajakan bertindak tetap
                terlihat tanpa perlu menggulir melewati mockup setinggi layar.
            --}}
            <div class="grid items-center gap-10 lg:grid-cols-12 lg:gap-12">
                <div class="lg:col-span-7 animate-muncul">
                    <h1 class="mt-5 text-3xl font-bold tracking-tight text-primary-900 sm:text-4xl lg:text-5xl">
                        Bumi Bersih,
                        <span class="block text-primary-600">Dimulai Dari Kita.</span>
                    </h1>

                    <p class="mt-5 max-w-2xl text-base leading-relaxed text-gray-600 sm:text-lg">
                        Resikita menyatukan empat hal yang selama ini berjalan sendiri-sendiri:
                        laporan warga, penanganan pemerintah daerah, setoran ke bank sampah, dan
                        penjualan produk daur ulang oleh UMKM.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                        <x-ui.tombol tautan="#dampak">
                            Lihat Dampak 
                        </x-ui.tombol>
                        <x-ui.tombol jenis="kedua" :tautan="route('masuk')">
                            Mulai Sekarang
                        </x-ui.tombol>
                    </div>
                </div>

                {{--
                    Mockup yang bergerak.

                    Dua lapis, seluruhnya CSS: gambarnya mengambang pelan,
                    dan dua kartu angka melayang di tepinya dengan irama
                    sendiri, sengaja berbeda durasi dan delay supaya tidak
                    terbaca sebagai satu blok yang naik-turun bersama.

                    Angka pada kartu itu angka nyata dari `$ringkasan`, sama
                    dengan yang dipakai bagian "Angka dampak" di bawah.
                    Mengarang angka di hero, sekadar supaya terlihat ramai,
                    akan membuat seluruh klaim di halaman ini kehilangan
                    dasarnya.

                    Kartunya `aria-hidden` karena angka yang sama sudah
                    dibacakan sebagai daftar definisi di bagian berikutnya;
                    mengulanginya di sini hanya menambah kebisingan bagi
                    pengguna pembaca layar.
                --}}
                <div class="relative isolate lg:col-span-5 animate-muncul [animation-delay:.15s]">
                    <img src="{{ asset('images/home_mockup.png') }}"
                         alt="Dua layar aplikasi Resikita di ponsel: dasbor saldo dan aksi cepat warga, serta percakapan dengan asisten lingkungan."
                         width="549" height="586"
                         decoding="async"
                         class="mx-auto w-full max-w-xs animate-mengambang sm:max-w-sm lg:max-w-none">

                    {{-- Kartu angka melayang --}}
                    <div aria-hidden="true"
                         class="pointer-events-none absolute -left-1 top-10 animate-mengambang-lambat
                                [animation-delay:-2s] sm:left-2 lg:-left-6">
                        <div class="flex items-center gap-2.5 rounded-2xl bg-white/95 px-3.5 py-2.5
                                    shadow-lg shadow-primary-900/10 ring-1 ring-primary-100 backdrop-blur">
                            <span class="flex h-8 w-8 flex-none items-center justify-center rounded-xl bg-primary-50 text-primary-700">
                                <x-ui.ikon nama="dompet" class="h-4 w-4"/>
                            </span>
                            <span class="text-left">
                                <span class="block text-[0.65rem] font-medium text-gray-500">Kembali ke warga</span>
                                <span class="block text-sm font-bold tabular-nums text-primary-900">
                                    {{ Rupiah::ringkas($ringkasan['nilai_ke_warga']) }}
                                </span>
                            </span>
                        </div>
                    </div>

                    <div aria-hidden="true"
                         class="pointer-events-none absolute -right-1 bottom-12 animate-mengambang
                                [animation-delay:-4s] sm:right-2 lg:-right-6">
                        <div class="flex items-center gap-2.5 rounded-2xl bg-white/95 px-3.5 py-2.5
                                    shadow-lg shadow-primary-900/10 ring-1 ring-primary-100 backdrop-blur">
                            <span class="flex h-8 w-8 flex-none items-center justify-center rounded-xl bg-primary-50 text-primary-700">
                                <x-ui.ikon nama="timbangan" class="h-4 w-4"/>
                            </span>
                            <span class="text-left">
                                <span class="block text-[0.65rem] font-medium text-gray-500">Dialihkan dari TPA</span>
                                <span class="block text-sm font-bold tabular-nums text-primary-900">
                                    {{ number_format($ringkasan['berat_teralihkan_kg'], 0, ',', '.') }} kg
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Angka dampak --}}
    <section id="dampak" class="border-b border-gray-100 py-14" aria-labelledby="judul-dampak">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 id="judul-dampak" class="text-2xl font-bold tracking-tight text-primary-900">
                Yang sudah terjadi sejauh ini
            </h2>
            <p class="mt-2 max-w-2xl text-gray-600">
                Angka di bawah dihitung langsung dari transaksi yang benar-benar tercatat,
                bukan dari perkiraan.
            </p>

            <dl class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl border border-gray-200 p-5">
                    <dt class="text-sm text-gray-500">Sampah dialihkan dari TPA</dt>
                    <dd class="mt-1.5 text-2xl font-bold tracking-tight tabular-nums text-primary-900 xl:text-3xl">
                        {{ number_format($ringkasan['berat_teralihkan_kg'], 0, ',', '.') }}
                        <span class="text-base font-medium text-gray-500">kg</span>
                    </dd>
                </div>

                <div class="rounded-2xl border border-gray-200 p-5">
                    <dt class="text-sm text-gray-500">Kembali ke kantong warga</dt>
                    <dd class="mt-1.5 text-2xl font-bold tracking-tight tabular-nums text-primary-900 xl:text-3xl">
                        {{ Rupiah::ringkas($ringkasan['nilai_ke_warga']) }}
                    </dd>
                </div>

                <div class="rounded-2xl border border-gray-200 p-5">
                    <dt class="text-sm text-gray-500">Laporan warga ditangani</dt>
                    <dd class="mt-1.5 text-2xl font-bold tracking-tight tabular-nums text-primary-900 xl:text-3xl">
                        {{ number_format($ringkasan['laporan_selesai']) }}
                        <span class="text-base font-medium text-gray-500">
                            / {{ number_format($ringkasan['total_laporan']) }}
                        </span>
                    </dd>
                </div>

                <div class="rounded-2xl border border-gray-200 p-5">
                    <dt class="text-sm text-gray-500">Titik layanan terdaftar</dt>
                    <dd class="mt-1.5 text-2xl font-bold tracking-tight tabular-nums text-primary-900 xl:text-3xl">
                        {{ number_format($ringkasan['bank_sampah'] + $ringkasan['tps']) }}
                    </dd>
                    <dd class="mt-1 text-xs text-gray-500">
                        {{ number_format($ringkasan['bank_sampah']) }} bank sampah &middot;
                        {{ number_format($ringkasan['tps']) }} TPS
                    </dd>
                </div>
            </dl>
        </div>
    </section>

    {{-- Alur --}}
    <section class="border-b border-gray-100 py-14" aria-labelledby="judul-alur">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 id="judul-alur" class="text-2xl font-bold tracking-tight text-primary-900">
                Satu lingkaran, empat pemeran
            </h2>

            <ol class="mt-8 grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['ikon' => 'megafon', 'judul' => 'Warga melapor', 'isi' => 'Foto tumpukan sampah, titiknya terkunci otomatis. Deskripsinya boleh diketik atau didiktekan.'],
                    ['ikon' => 'orang', 'judul' => 'Pemerintah menangani', 'isi' => 'Laporan diteruskan ke tingkat yang benar-benar berwenang, lalu ditugaskan ke petugas lapangan.'],
                    ['ikon' => 'timbangan', 'judul' => 'Sampah disetor', 'isi' => 'Warga menyetor sampah terpilah ke bank sampah dan menerima saldo yang bisa ditarik.'],
                    ['ikon' => 'toko', 'judul' => 'UMKM mengolah', 'isi' => 'Bahan bekas menjadi produk baru, dijual kembali lewat marketplace di aplikasi yang sama.'],
                ] as $langkah)
                    <li class="rounded-2xl border border-gray-200 p-5">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
                            <x-ui.ikon :nama="$langkah['ikon']"/>
                        </span>
                        <h3 class="mt-4 text-base font-semibold text-primary-900">
                            {{ $loop->iteration }}. {{ $langkah['judul'] }}
                        </h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-gray-600">{{ $langkah['isi'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Inklusivitas --}}
    <section class="border-b border-gray-100 bg-primary-50/40 py-14" aria-labelledby="judul-suara">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-8 lg:grid-cols-2">
                <div>
                    <h2 id="judul-suara" class="text-2xl font-bold tracking-tight text-primary-900">
                        Bisa dipakai tanpa harus mengetik
                    </h2>
                    <p class="mt-3 leading-relaxed text-gray-600">
                        Laporan boleh didiktekan, artikel literasi bisa didengarkan, dan jawaban
                        asisten lingkungan disiapkan supaya enak dibacakan pembaca suara,
                        tanpa simbol, tanpa singkatan yang aneh diucapkan.
                    </p>
                    <p class="mt-3 leading-relaxed text-gray-600">
                        Kami menghitung pemakaiannya secara terbuka, karena klaim yang tidak
                        pernah diukur tidak berarti apa-apa.
                    </p>
                </div>

                <dl class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-primary-100 bg-white p-5">
                        <dt class="text-sm text-gray-500">Laporan yang didiktekan</dt>
                        <dd class="mt-1.5 text-2xl font-bold tabular-nums text-primary-900 xl:text-3xl">
                            {{ $fiturSuara['persen_laporan_suara'] }}<span class="text-base">%</span>
                        </dd>
                        <dd class="mt-1 text-xs text-gray-500">
                            {{ number_format($fiturSuara['laporan_suara']) }} laporan lewat suara
                        </dd>
                    </div>

                    <div class="rounded-2xl border border-primary-100 bg-white p-5">
                        <dt class="text-sm text-gray-500">Artikel didengarkan</dt>
                        <dd class="mt-1.5 text-2xl font-bold tabular-nums text-primary-900 xl:text-3xl">
                            {{ number_format($fiturSuara['artikel_didengarkan']) }}
                        </dd>
                        <dd class="mt-1 text-xs text-gray-500">kali diputar sampai selesai</dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    {{-- Literasi --}}
    @if ($artikelUnggulan->isNotEmpty() || $artikelTerbaru->isNotEmpty())
        <section class="border-b border-gray-100 py-14" aria-labelledby="judul-literasi">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 id="judul-literasi" class="text-2xl font-bold tracking-tight text-primary-900">
                            Belajar memilah
                        </h2>
                        <p class="mt-2 text-gray-600">Panduan praktis yang bisa dibaca maupun didengarkan.</p>
                    </div>
                    <x-ui.tombol jenis="kedua" :tautan="route('publik.artikel')">Semua artikel</x-ui.tombol>
                </div>

                <div class="mt-8 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach (($artikelUnggulan->isNotEmpty() ? $artikelUnggulan : $artikelTerbaru)->take(3) as $artikel)
                        <x-publik.kartu-artikel :artikel="$artikel"/>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Marketplace --}}
    @if ($produkTerbaru->isNotEmpty())
        <section class="border-b border-gray-100 py-14" aria-labelledby="judul-produk">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 id="judul-produk" class="text-2xl font-bold tracking-tight text-primary-900">
                            Produk daur ulang
                        </h2>
                        <p class="mt-2 text-gray-600">Dibuat UMKM dari bahan yang tadinya berakhir di TPA.</p>
                    </div>
                    <x-ui.tombol jenis="kedua" :tautan="route('publik.produk')">Buka marketplace</x-ui.tombol>
                </div>

                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($produkTerbaru as $produk)
                        <x-publik.kartu-produk :produk="$produk"/>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{--
        Dua pintu bergabung, berdampingan dan setara.

        Pemerintah daerah membuka jalur penanganan laporan; UMKM membuka
        jalur nilai ekonominya. Menonjolkan salah satu saja membuat sisi
        yang lain hanya bisa ditemukan di kaki halaman, dan sisi UMKM
        justru yang paling mungkin datang tanpa diundang.
    --}}
    <section class="py-14" aria-labelledby="judul-ajakan">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 id="judul-ajakan" class="sr-only">Bergabung dengan Resikita</h2>

            <div class="grid gap-5 lg:grid-cols-2">
                <div class="flex flex-col rounded-3xl bg-primary-900 px-6 py-10 sm:px-10">
                    <h3 class="text-2xl font-bold tracking-tight text-white">
                        Daerah Anda belum terdaftar?
                    </h3>
                    <p class="mt-4 leading-relaxed text-primary-100">
                        Laporan warga dari wilayah yang belum bergabung tetap kami terima dan
                        teruskan ke dinas terkait. Tapi penanganannya baru benar-benar berjalan
                        setelah pemerintah daerahnya masuk ke sistem.
                    </p>
                    <div class="mt-7 flex flex-wrap gap-3">
                        <a href="{{ route('publik.pengajuan-wilayah') }}" wire:navigate
                           class="inline-flex items-center rounded-xl bg-white px-5 py-3 text-sm font-semibold
                                  text-primary-900 transition hover:bg-primary-50">
                            Ajukan pendaftaran wilayah
                        </a>
                        <a href="{{ route('masuk') }}"
                           class="inline-flex items-center rounded-xl border border-primary-700 px-5 py-3
                                  text-sm font-semibold text-white transition hover:bg-primary-700">
                            Sudah punya akun
                        </a>
                    </div>
                </div>

                <div class="flex flex-col rounded-3xl border border-primary-200 bg-primary-50 px-6 py-10 sm:px-10">
                    <h3 class="text-2xl font-bold tracking-tight text-primary-900">
                        Mengolah sampah jadi produk?
                    </h3>
                    <p class="mt-4 leading-relaxed text-gray-700">
                        Daftarkan usaha Anda dan jual produknya di marketplace Resikita. Etalase,
                        pengelolaan pesanan, ongkos kirim, sampai penarikan saldo sudah tersedia
                        sejak hari pertama.
                    </p>
                    <div class="mt-7 flex flex-wrap gap-3">
                        <x-ui.tombol :tautan="route('publik.pendaftaran-umkm')">
                            Daftarkan UMKM
                        </x-ui.tombol>
                        <x-ui.tombol jenis="kedua" :tautan="route('publik.produk')">
                            Lihat marketplace
                        </x-ui.tombol>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
