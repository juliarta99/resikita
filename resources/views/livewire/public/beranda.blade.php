@php
    // ==== Palet (samakan dgn tema) ====
    $PRIMARY = '#057D5D';
    $PRIMARY_DARK = '#046A4F';
    $ACCENT = '#d1fae5';   // emerald-100
    $BG = '#f0faf6';       // hijau sangat muda

    // ==== Sad Kerthi ====
    $sadKerthi = [
        ['title' => 'Warga Sejahtera', 'desc' => 'Setiap rumah tangga mendapat manfaat ekonomi nyata dari aktivitas memilah sampah sehari-hari.',
         'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a3 3 0 00-5.36-1.87M17 20H7m10 0v-1a5 5 0 00-.9-2.87M7 20H2v-1a3 3 0 015.36-1.87M7 20v-1a5 5 0 01.9-2.87m0 0A5 5 0 0112 12a5 5 0 014.1 2.13M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>'],
        ['title' => 'UMKM Tumbuh', 'desc' => 'Produk daur ulang dari pengrajin lokal Bali terjual lebih luas melalui platform e-commerce terintegrasi.',
         'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 9h18M3 9l2-4h14l2 4M5 9v10h14V9M9 13h6"/>'],
        ['title' => 'Alam Terjaga', 'desc' => 'Sungai, pantai, dan laut Bali terlindungi dari sampah plastik yang selama ini tidak terkelola.',
         'icon' => '<circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 12h18M12 3a15 15 0 010 18M12 3a15 15 0 000 18"/>'],
    ];

    // ==== Manfaat ====
    $manfaat = [
        ['title' => 'Manfaat Lingkungan', 'icon' => '<circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 12h18M12 3a15 15 0 010 18M12 3a15 15 0 000 18"/>',
         'items' => ['Mengurangi volume sampah ke TPA hingga 15%', 'Mencegah sampah plastik bermuara ke sungai dan laut Bali', 'Mendukung Gerakan Bali Bersih Sampah SE No. 9/2025', 'Menjaga Danu Kerthi dan Segara Kerthi']],
        ['title' => 'Manfaat Ekonomi', 'icon' => '<circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v10M14.5 9.5a2.5 2.5 0 00-2.5-1.5c-1.4 0-2.5.9-2.5 2s1.1 2 2.5 2 2.5.9 2.5 2-1.1 2-2.5 2a2.5 2.5 0 01-2.5-1.5"/>',
         'items' => ['Sampah pilah menghasilkan saldo digital nyata', 'Membuka peluang UMKM produk daur ulang lokal', 'Mengurangi biaya operasional pengelolaan sampah', 'Menciptakan ekosistem Jana Kerthi yang berkelanjutan']],
        ['title' => 'Manfaat Sosial', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a3 3 0 00-5.36-1.87M17 20H7m10 0v-1a5 5 0 00-.9-2.87M7 20H2v-1a3 3 0 015.36-1.87M7 20v-1a5 5 0 01.9-2.87m0 0A5 5 0 0112 12a5 5 0 014.1 2.13M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>',
         'items' => ['Edukasi pemilahan sampah berbasis AI interaktif', 'Mendorong partisipasi aktif seluruh lapisan warga', 'Memperkuat gotong royong digital antar komunitas', 'Mewujudkan nilai Atma Kerthi dan Jagat Kerthi']],
    ];

    // ==== Fitur (6 kartu reguler + 1 kartu lebar) ====
    $fitur = [
        ['num' => '01', 'subtitle' => 'KECERDASAN BUATAN', 'title' => 'Klasifikasi Sampah AI', 'desc' => 'Arahkan kamera ke sampah, AI mengenali jenis & cara pengolahannya seketika.', 'benefit' => 'Tak perlu bingung memilah — cukup foto, langsung tahu organik atau anorganik.', 'tech' => 'CNN · TensorFlow',
         'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.55 2.28a1 1 0 010 1.79L15 16m0-6l-6-3-6 3m6-3v13m0 0l6-3"/>'],
        ['num' => '02', 'subtitle' => 'EKONOMI SIRKULAR', 'title' => 'Bank Sampah Digital', 'desc' => 'Setor sampah terpilah, dapatkan saldo yang tercatat otomatis lewat QR identitas.', 'benefit' => 'Sampahmu berubah jadi rupiah tanpa ribet pencatatan manual.', 'tech' => 'QR · Wallet',
         'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16v10H4zM4 11h16M8 15h4"/>'],
        ['num' => '03', 'subtitle' => 'MARKETPLACE', 'title' => 'E-Commerce Daur Ulang', 'desc' => 'Belanja produk ramah lingkungan buatan UMKM lokal memakai saldo bank sampah.', 'benefit' => 'Dukung ekonomi warga sekaligus tutup siklus daur ulang.', 'tech' => 'Marketplace · Midtrans',
         'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 4h12m-6-4v4"/>'],
        ['num' => '04', 'subtitle' => 'PARTISIPASI WARGA', 'title' => 'Pelaporan Sampah', 'desc' => 'Laporkan tumpukan sampah lengkap dengan foto & lokasi peta, lalu pantau progresnya.', 'benefit' => 'Suaramu langsung sampai ke petugas dan bisa kamu lacak.', 'tech' => 'GIS · Leaflet',
         'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>'],
        ['num' => '05', 'subtitle' => 'ASISTEN CERDAS', 'title' => 'Chatbot Edukasi', 'desc' => 'Tanya apa saja soal pemilahan & pengolahan sampah, dijawab asisten AI.', 'benefit' => 'Panduan pengelolaan sampah 24 jam dalam genggaman.', 'tech' => 'Gemini API',
         'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12a8 8 0 01-8 8 9 9 0 01-4-1l-4 1 1-4a8 8 0 016-14 8 8 0 019 10z"/>'],
        ['num' => '06', 'subtitle' => 'LITERASI', 'title' => 'Edukasi & Jurnal', 'desc' => 'Artikel, panduan, tutorial, hingga jurnal ilmiah seputar pengelolaan sampah.', 'benefit' => 'Belajar dari sumber tepercaya, tingkatkan kesadaran lingkungan.', 'tech' => 'CMS · WYSIWYG',
         'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.5A2.5 2.5 0 0114.5 4H20v13h-5.5A2.5 2.5 0 0012 19.5m0-13A2.5 2.5 0 009.5 4H4v13h5.5A2.5 2.5 0 0112 19.5m0-13v13"/>'],
    ];

    $fiturLebar = [
        'num' => '07', 'subtitle' => 'SATU EKOSISTEM', 'title' => 'Direktori & Peta Fasilitas',
        'desc' => 'Semua terhubung: temukan TPS, bank sampah, dan UMKM terdekat di peta, lengkap dengan info tarif dan kontak — pintu masuk ke seluruh layanan Niti Resik.',
        'benefit' => 'Dari satu peta, akses semua titik layanan pengelolaan sampah di sekitarmu.', 'tech' => 'GIS · Direktori',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>',
        'extra' => [
            ['label' => 'TPS', 'desc' => 'Lokasi & tarif tempat pengelolaan sampah.', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 7l1 12h14l1-12M9 7V4h6v3"/>'],
            ['label' => 'Bank Sampah', 'desc' => 'Titik setor untuk menukar sampah jadi saldo.', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m0 0l4-4m-4 4l-4-4M4 8l8-4 8 4"/>'],
            ['label' => 'UMKM', 'desc' => 'Perajin produk daur ulang warga lokal.', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 9h18M3 9l2-4h14l2 4M5 9v10h14V9"/>'],
        ],
    ];

    // ==== Tim (TODO: sesuaikan data & foto) ====
    $team = [
        ['name' => 'Nayla Lareina Widyastuti Seregar', 'role' => 'Hustler', 'institusi' => 'Universitas Udayana', 'bio' => 'Menjembatani teknologi dengan kebutuhan nyata masyarakat, hingga memastikan platform ini benar-benar menjawab masalah persampahan di Bali', 'image' => 'images/reina.png'],
        ['name' => 'Si Ngurah Putu Juliarta', 'role' => 'Hacker', 'institusi' => 'Universitas Udayana', 'bio' => 'Memastikan Niti Resik tidak hanya sebatas ide, melainkan platform digital yang andal dan terukur. Mengelola alur logika sistem demi menciptakan pengalaman digital yang membawa dampak nyata bagi kebersihan Bali', 'image' => 'images/ngurah-juliarta.png'],
        ['name' => 'I Wayan Yudhiastara Sudarmawan', 'role' => 'Hipster', 'institusi' => 'Universitas Udayana', 'bio' => 'Memastikan teknologi secanggih apapun terasa mudah digunakan semua kalangan, merancang antarmuka yang intuitif dan membangun pengalaman pengguna yang mencerminkan nilai kearifan lokal Bali', 'image' => 'images/yudhiastara.png'],
    ];
@endphp

<div>
    <style>
        @keyframes nr-badge-float { 0%,100% { transform: translateY(0) } 50% { transform: translateY(-12px) } }
        .nr-badge-float      { animation: nr-badge-float 4.5s ease-in-out infinite }
        .nr-badge-float-slow { animation: nr-badge-float 6s ease-in-out infinite }
        @media (prefers-reduced-motion: reduce) {
            .nr-badge-float, .nr-badge-float-slow { animation: none }
        }
    </style>
    {{-- ============ HERO ============ --}}
    <section id="hero" class="relative overflow-hidden" style="background:linear-gradient(135deg,#f0faf6 0%,#fafaf8 60%,#ecfdf5 100%);">
        <div class="pointer-events-none absolute inset-0" style="opacity:.04;background-image:url('data:image/svg+xml,%3Csvg width=%2740%27 height=%2740%27 viewBox=%270 0 40 40%27 xmlns=%27http://www.w3.org/2000/svg%27%3E%3Cg fill=%27%23057D5D%27%3E%3Cpath d=%27M20 20.5V18H0v5h5v5H0v5h20v-4.5zm0 0V25h20v-4.5H20z%27/%3E%3C/g%3E%3C/svg%3E');"></div>

        <div class="relative mx-auto max-w-7xl px-5 py-24 lg:py-28">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                {{-- KIRI --}}
                <div>
                    {{-- Logo row --}}
                    <div class="mb-6 flex flex-wrap items-center gap-6">
                        @foreach (['images/logo_udayana.png','images/logo_badung.png','images/logo_bfi.png'] as $logo)
                            <img src="{{ asset($logo) }}" alt="" style="height:50px;width:auto">
                        @endforeach
                    </div>

                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold" style="background:{{ $ACCENT }};color:{{ $PRIMARY }}">
                        Inovasi Badung Festival Inovasi 2026
                    </span>

                    <h1 class="mt-6 font-extrabold text-gray-900" style="font-size:clamp(2.4rem,5vw,4rem);line-height:1.1">
                        Kelola Sampah<br>
                        <span style="color:{{ $PRIMARY }};font-style:italic">Lebih Cerdas,</span><br>
                        Lebih Bermakna
                    </h1>

                    <p class="mt-5 text-gray-500" style="font-size:17px;line-height:1.75">
                        Platform mobile terintegrasi yang menggabungkan <strong>kecerdasan buatan</strong> dan
                        <strong>kearifan lokal Bali</strong> untuk mewujudkan ekosistem penanganan sampah
                        terpadu di Indonesia.
                    </p>

                    {{-- Stats --}}
                    <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ([
                            ['Bank Sampah', number_format($stat['bankSampah'], 0, ',', '.')],
                            ['Titik TPS', number_format($stat['tps'], 0, ',', '.')],
                            ['UMKM Aktif', number_format($stat['umkm'], 0, ',', '.')],
                            ['Sampah Terkelola', number_format($stat['sampahKg'], 0, ',', '.').' kg'],
                            ['Laporan Tuntas', number_format($stat['laporanTuntas'], 0, ',', '.')],
                        ] as [$label, $val])
                            <div class="rounded-2xl border px-4 py-3" style="border-color:#d1fae5;background:rgba(255,255,255,.85)">
                                <p class="text-lg font-bold" style="color:{{ $PRIMARY }}">{{ $val }}</p>
                                <p class="text-xs text-gray-500">{{ $label }}</p>
                            </div>
                        @endforeach
                    </div>

                    {{-- CTA --}}
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="#fitur" class="rounded-xl px-6 py-3 text-center text-sm font-semibold text-white" style="background:{{ $PRIMARY }}">Jelajahi Fitur</a>
                        <a href="#unduh" class="rounded-xl border px-6 py-3 text-center text-sm font-semibold" style="border-color:{{ $PRIMARY }};color:{{ $PRIMARY }}">Unduh Aplikasi</a>
                    </div>
                </div>

                {{-- KANAN: mockup --}}
                <div class="relative flex justify-center px-6">
                    <img src="{{ asset('images/home_mockup.png') }}" alt="Niti Resik" style="width:100%;height:auto">

                    <div class="nr-badge-float absolute hidden items-center gap-2.5 rounded-2xl bg-white p-2.5 sm:flex" style="right:-10px;top:15%;box-shadow:0 12px 40px rgba(5,125,93,.15)">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg" style="background:{{ $ACCENT }};color:{{ $PRIMARY }}">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd"/></svg>
                        </span>
                        <div>
                            <div class="text-xs font-semibold text-gray-900">Sampah terdeteksi!</div>
                            <div class="text-[11px] text-gray-400">Botol PET Anorganik</div>
                        </div>
                    </div>

                    <div class="nr-badge-float-slow absolute hidden items-center gap-2.5 rounded-2xl bg-white p-2.5 sm:flex" style="left:-16px;bottom:20%;box-shadow:0 12px 40px rgba(5,125,93,.15);animation-delay:1s">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg" style="background:{{ $ACCENT }};color:{{ $PRIMARY }}">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M9 4.5a.75.75 0 01.721.544l.813 2.846a3.75 3.75 0 002.576 2.576l2.846.813a.75.75 0 010 1.442l-2.846.813a3.75 3.75 0 00-2.576 2.576l-.813 2.846a.75.75 0 01-1.442 0l-.813-2.846a3.75 3.75 0 00-2.576-2.576l-2.846-.813a.75.75 0 010-1.442l2.846-.813A3.75 3.75 0 007.466 7.89l.813-2.846A.75.75 0 019 4.5z" clip-rule="evenodd"/></svg>
                        </span>
                        <div>
                            <div class="text-xs font-semibold text-gray-900">Informasi Terbaru!</div>
                            <div class="text-[11px] text-gray-400">Cara terbaik membuat kompos!</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ SAD KERTHI ============ --}}
    <section style="background:#f0fdf4;border-top:1px solid #bbf7d0;border-bottom:1px solid #bbf7d0">
        <div class="mx-auto max-w-7xl px-5 py-12">
            <div class="grid gap-10 lg:grid-cols-[1fr_2fr]">
                <div>
                    <img src="{{ asset('images/logo-primary.png') }}" alt="Niti Resik" style="height:44px;width:auto" class="mb-3">
                    <h3 class="mb-2 font-bold" style="font-size:28px;color:{{ $PRIMARY }}">Sad Kerthi Bali</h3>
                    <p style="font-size:14px;color:#065f46;line-height:1.65">
                        Kesejahteraan masyarakat Bali melalui pengelolaan sampah yang bermartabat dan berkelanjutan.
                    </p>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach ($sadKerthi as $s)
                        <div class="relative overflow-hidden rounded-2xl border bg-white p-4" style="border-color:#bbf7d0">
                            <div class="relative z-10">
                                <div class="mb-2" style="color:{{ $PRIMARY }}">
                                    <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">{!! $s['icon'] !!}</svg>
                                </div>
                                <div class="mb-1.5 text-sm font-bold" style="color:{{ $PRIMARY }}">{{ $s['title'] }}</div>
                                <p class="text-xs text-gray-500" style="line-height:1.55">{{ $s['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ============ MANFAAT ============ --}}
    <section id="manfaat" style="background:linear-gradient(135deg,{{ $PRIMARY_DARK }},{{ $PRIMARY }})">
        <div class="mx-auto max-w-7xl px-5 py-20">
            <div class="mx-auto mb-14 max-w-xl text-center">
                <div class="mb-4 text-xs font-semibold uppercase tracking-wider" style="color:rgba(255,255,255,.7)">Teknologi Tepat Guna</div>
                <h2 class="mb-3 font-bold text-white" style="font-size:clamp(2rem,3.5vw,2.8rem)">Manfaat Nyata untuk <em>Masyarakat Bali</em></h2>
                <p style="color:rgba(255,255,255,.7);font-size:16px">Niti Resik bukan sekadar aplikasi, tetapi solusi yang langsung terasa manfaatnya bagi setiap lapisan masyarakat.</p>
            </div>
            <div class="grid gap-6 md:grid-cols-3">
                @foreach ($manfaat as $b)
                    <div class="rounded-2xl border p-7" style="background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.15);backdrop-filter:blur(12px)">
                        <div class="mb-3" style="color:{{ $ACCENT }}">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">{!! $b['icon'] !!}</svg>
                        </div>
                        <h3 class="mb-4 font-bold text-white" style="font-size:22px">{{ $b['title'] }}</h3>
                        <ul class="space-y-2.5">
                            @foreach ($b['items'] as $it)
                                <li class="flex items-start gap-2 text-sm" style="color:rgba(255,255,255,.78);line-height:1.5">
                                    <span style="color:rgba(255,255,255,.5);margin-top:2px">✦</span>{{ $it }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ FITUR ============ --}}
    <section id="fitur" class="bg-white">
        <div class="mx-auto max-w-7xl px-5 py-20">
            <div class="mx-auto mb-14 max-w-2xl text-center">
                <span class="mb-4 inline-block rounded-full px-3 py-1 text-xs font-semibold" style="background:{{ $ACCENT }};color:{{ $PRIMARY }}">Platform Terintegrasi</span>
                <h2 class="mb-3 font-bold text-gray-900" style="font-size:clamp(2rem,3.5vw,2.8rem)">Fitur yang <em style="color:{{ $PRIMARY }}">Membangun Budaya</em></h2>
                <p class="text-gray-500" style="font-size:16px">Dari memilah sampah hingga membaca jurnal ilmiah, semua dalam satu ekosistem yang saling terhubung.</p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($fitur as $f)
                    <div id="{{ \Illuminate\Support\Str::slug($f['title']) }}" class="rounded-2xl border p-6 transition hover:-translate-y-0.5 hover:shadow-md" style="border:1.5px solid #d1fae5;background:{{ $BG }}">
                        <div class="mb-4 flex items-start justify-between">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl" style="background:{{ $ACCENT }}">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:{{ $PRIMARY }}">{!! $f['icon'] !!}</svg>
                            </div>
                            <span class="text-xs font-bold" style="color:rgba(5,125,93,.25)">{{ $f['num'] }}</span>
                        </div>
                        <div class="mb-1.5 text-[11px] font-bold uppercase tracking-wider" style="color:{{ $PRIMARY }}">{{ $f['subtitle'] }}</div>
                        <h3 class="mb-2.5 font-bold text-gray-900" style="font-size:20px">{{ $f['title'] }}</h3>
                        <p class="mb-4 text-gray-500" style="font-size:13px;line-height:1.65">{{ $f['desc'] }}</p>
                        <div class="mb-3.5 rounded-xl px-3.5 py-3" style="background:{{ $ACCENT }}">
                            <div class="mb-1 text-[11px] font-bold" style="color:{{ $PRIMARY }}">✦ Manfaat untuk Anda</div>
                            <p class="text-xs text-gray-700" style="line-height:1.55">{{ $f['benefit'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Kartu lebar --}}
            <div id="{{ \Illuminate\Support\Str::slug($fiturLebar['title']) }}" class="mt-5 rounded-2xl border p-6" style="border:1.5px solid #d1fae5;background:{{ $BG }}">
                <div class="mb-4 flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl" style="background:{{ $ACCENT }}">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:{{ $PRIMARY }}">{!! $fiturLebar['icon'] !!}</svg>
                        </div>
                        <div>
                            <div class="text-[11px] font-bold uppercase tracking-wider" style="color:{{ $PRIMARY }}">{{ $fiturLebar['subtitle'] }}</div>
                            <h3 class="font-bold text-gray-900" style="font-size:22px">{{ $fiturLebar['title'] }}</h3>
                        </div>
                    </div>
                    <span class="text-xs font-bold" style="color:rgba(5,125,93,.25)">{{ $fiturLebar['num'] }}</span>
                </div>
                <p class="mb-5 max-w-3xl text-gray-500" style="font-size:14px;line-height:1.65">{{ $fiturLebar['desc'] }}</p>
                <div class="mb-4 grid gap-3 sm:grid-cols-3">
                    @foreach ($fiturLebar['extra'] as $ex)
                        <div class="rounded-2xl border bg-white p-4" style="border-color:#d1fae5">
                            <svg class="mb-2 h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:{{ $PRIMARY }}">{!! $ex['icon'] !!}</svg>
                            <div class="mb-1.5 text-sm font-bold" style="color:{{ $PRIMARY }}">{{ $ex['label'] }}</div>
                            <p class="text-xs text-gray-500" style="line-height:1.55">{{ $ex['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="rounded-xl px-4 py-3" style="background:{{ $ACCENT }}">
                    <div class="mb-1 text-[11px] font-bold" style="color:{{ $PRIMARY }}">✦ Manfaat untuk Anda</div>
                    <p class="text-gray-700" style="font-size:13px;line-height:1.55">{{ $fiturLebar['benefit'] }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ YANG BISA ANDA LIHAT ============ --}}
    <section class="mx-auto max-w-7xl px-4 py-16">
        <h2 class="text-2xl font-bold text-primary-900">Yang bisa Anda lihat di sini</h2>
        <p class="mt-1 text-sm text-gray-500">Informasi terbuka untuk publik — untuk transaksi & pelaporan gunakan aplikasi.</p>
        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['UMKM Daur Ulang', 'Produk kreatif dari bahan daur ulang warga Badung.', route('publik.umkm.index'), '#f59e0b'],
                ['Lokasi TPS', 'Tempat pengelolaan sampah terdekat & tarif layanannya.', route('publik.tps.index'), '#0ea5e9'],
                ['Bank Sampah', 'Titik setor sampah untuk ditukar menjadi saldo.', route('publik.bank-sampah.index'), '#057D5D'],
                ['Laporan Publik', 'Transparansi penanganan laporan masalah sampah.', route('publik.laporan.index'), '#ef4444'],
            ] as [$judul, $desc, $url, $warna])
                <a href="{{ $url }}" class="group rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl" style="background:{{ $warna }}1a;color:{{ $warna }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10"/></svg>
                    </span>
                    <h3 class="mt-4 font-semibold text-primary-900 group-hover:text-primary-700">{{ $judul }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ $desc }}</p>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ============ PETA FASILITAS ============ --}}
    <section class="bg-gray-50 py-16">
        <div class="mx-auto max-w-7xl px-4">
            <h2 class="text-2xl font-bold text-primary-900">Peta Fasilitas</h2>
            <p class="mt-1 text-sm text-gray-500">Sebaran TPS, bank sampah, dan UMKM di Kabupaten Badung.</p>
            <div class="mt-6 flex flex-wrap gap-4 text-xs text-gray-500">
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background:#0ea5e9"></span> TPS</span>
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background:#057D5D"></span> Bank Sampah</span>
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background:#f59e0b"></span> UMKM</span>
            </div>
            <div wire:ignore class="mt-4" x-data x-init="
                const label = { tps:'TPS', bank_sampah:'Bank Sampah', umkm:'UMKM' };
                const colors = { tps:'#0ea5e9', bank_sampah:'#057D5D', umkm:'#f59e0b' };
                const pin = (c) => L.divIcon({ className:'', iconSize:[26,36], iconAnchor:[13,36], popupAnchor:[0,-32],
                    html:`<svg width='26' height='36' viewBox='0 0 26 36' xmlns='http://www.w3.org/2000/svg'><path d='M13 0C5.8 0 0 5.8 0 13c0 9.2 13 23 13 23s13-13.8 13-23C26 5.8 20.2 0 13 0z' fill='${c}'/><circle cx='13' cy='13' r='5' fill='white'/></svg>` });
                const map = L.map($refs.map).setView([-8.6478, 115.1385], 11);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
                const data = @js($markers); const pts = [];
                data.forEach(m => {
                    const popup = `<div style='min-width:190px'>
                        <div style='font-weight:700;color:#0f172a'>${m.n}</div>
                        <div style='font-size:12px;color:#64748b;margin-top:1px'>${label[m.t] || ''}</div>
                        ${m.alamat ? `<div style='font-size:12px;color:#64748b;margin-top:4px'>${m.alamat}</div>` : ''}
                        ${m.url ? `<a href='${m.url}' style='display:inline-block;margin-top:8px;font-size:12px;font-weight:600;color:#057D5D'>Lihat detail →</a>` : ''}
                    </div>`;
                    L.marker([m.lat,m.lng],{icon:pin(colors[m.t]||'#666')}).bindPopup(popup).addTo(map);
                    pts.push([m.lat,m.lng]);
                });
                if (pts.length) map.fitBounds(pts,{padding:[30,30],maxZoom:13});
                setTimeout(()=>map.invalidateSize(),200);
            ">
                <div x-ref="map" class="h-96 w-full rounded-2xl border border-gray-200"></div>
            </div>
        </div>
    </section>

    {{-- ============ UMKM UNGGULAN ============ --}}
    <section class="mx-auto max-w-7xl px-4 py-16">
        <div class="flex items-end justify-between">
            <div>
                <h2 class="text-2xl font-bold text-primary-900">UMKM Unggulan</h2>
                <p class="mt-1 text-sm text-gray-500">Dukung produk daur ulang warga lokal.</p>
            </div>
            <a href="{{ route('publik.umkm.index') }}" class="text-sm font-semibold text-primary-500 hover:text-primary-700">Lihat semua →</a>
        </div>
        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($umkms as $u)
                <a href="{{ route('publik.umkm.show', $u) }}" class="group overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
                    <div class="aspect-video bg-gray-100">
                        @if ($u->foto)<img src="{{ asset('storage/' . $u->foto) }}" class="h-full w-full object-cover" alt="">@else<div class="flex h-full items-center justify-center text-primary-200"><svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" d="M3 9h18M3 9l2-4h14l2 4M5 9v10h14V9"/></svg></div>@endif
                    </div>
                    <div class="p-5">
                        <h3 class="font-semibold text-primary-900 group-hover:text-primary-700">{{ $u->nama }}</h3>
                        <p class="mt-1 line-clamp-2 text-sm text-gray-500">{{ $u->deskripsi }}</p>
                        <p class="mt-3 text-xs text-gray-400">{{ $u->products_count }} produk</p>
                    </div>
                </a>
            @empty
                <p class="text-gray-400">Belum ada UMKM.</p>
            @endforelse
        </div>
    </section>

    {{-- ============ LAPORAN TERKINI ============ --}}
    @if ($laporans->isNotEmpty())
    <section class="bg-gray-50 py-16">
        <div class="mx-auto max-w-7xl px-4">
            <div class="flex items-end justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-primary-900">Penanganan Laporan Terkini</h2>
                    <p class="mt-1 text-sm text-gray-500">Transparansi tindak lanjut laporan warga.</p>
                </div>
                <a href="{{ route('publik.laporan.index') }}" class="text-sm font-semibold text-primary-500 hover:text-primary-700">Lihat semua →</a>
            </div>
            <div class="mt-8 grid gap-4 md:grid-cols-2">
                @foreach ($laporans as $l)
                    <a href="{{ route('publik.laporan.show', $l) }}"
                    class="flex min-w-0 gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md">
                        <div class="h-16 w-16 flex-none overflow-hidden rounded-lg bg-gray-100">
                            @if ($l->foto)<img src="{{ asset('storage/' . $l->foto) }}" class="h-full w-full object-cover" alt="">@endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex min-w-0 items-center gap-2">
                                <span class="truncate text-xs font-medium text-primary-500">{{ $l->kategori?->nama }}</span>
                                <span class="shrink-0"><x-status-badge :status="$l->status" /></span>
                            </div>
                            <h3 class="mt-0.5 truncate font-semibold text-primary-900">{{ $l->judul }}</h3>
                            <p class="mt-0.5 truncate text-xs text-gray-400">{{ $l->alamat }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ============ TIM ============ --}}
    <section id="tim" class="bg-white">
        <div class="mx-auto max-w-7xl px-5 py-20">
            <div class="mx-auto mb-14 max-w-xl text-center">
                <span class="mb-4 inline-block rounded-full px-3 py-1 text-xs font-semibold" style="background:{{ $ACCENT }};color:{{ $PRIMARY }}">Di Balik Inovasi</span>
                <h2 class="mb-3 font-bold text-gray-900" style="font-size:clamp(2rem,3.5vw,2.8rem)">Tim <em style="color:{{ $PRIMARY }}">Niti Resik</em></h2>
                <p class="text-gray-500" style="font-size:16px">Mahasiswa yang percaya bahwa teknologi dan kearifan lokal dapat bersinergi menyelesaikan masalah lingkungan nyata.</p>
            </div>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($team as $t)
                    <div class="rounded-3xl border p-9 text-center transition hover:-translate-y-0.5 hover:shadow-md" style="border:1.5px solid #d1fae5;background:{{ $BG }}">
                        <img src="{{ asset($t['image']) }}" alt="{{ $t['name'] }}" class="mx-auto mb-4 rounded-2xl" style="width:100%;height:auto;max-width:300px">
                        <h3 class="mb-1 font-bold text-gray-900 text-base sm:text-lg">{{ $t['name'] }}</h3>
                        <div class="mb-1 flex items-center justify-center gap-1.5 text-xs sm:text-sm font-semibold" style="color:{{ $PRIMARY }}">{{ $t['role'] }}</div>
                        <div class="mb-3.5 text-xs text-gray-400">{{ $t['institusi'] }}</div>
                        <p class="text-gray-500" style="font-size:13px;line-height:1.65">{{ $t['bio'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ VIDEO ============ --}}
    <section id="video" style="background:linear-gradient(135deg,{{ $PRIMARY_DARK }},{{ $PRIMARY }})">
        <div class="mx-auto max-w-2xl px-5 py-20" x-data="{ playing:false }">
            <div class="mb-12 text-center">
                <div class="mb-2 text-xs font-semibold uppercase tracking-wider" style="color:rgba(255,255,255,.7)">Kenali Lebih Dekat</div>
                <h2 class="mb-3 font-bold text-white" style="font-size:clamp(2rem,3.5vw,2.8rem)">Video Pengenalan Niti Resik</h2>
                <p class="mx-auto max-w-md" style="color:rgba(255,255,255,.7);font-size:16px">Saksikan bagaimana Niti Resik mengubah cara masyarakat Bali mengelola sampah secara cerdas dan bermakna.</p>
            </div>

            <div class="overflow-hidden rounded-3xl" style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);backdrop-filter:blur(12px)">
                <div class="relative flex items-center justify-center" style="height:300px;background:rgba(0,0,0,.25);cursor:pointer" @click="playing = true">
                    <template x-if="playing">
                        <iframe class="absolute inset-0 h-full w-full" src="https://www.youtube.com/embed/HSUASRicz6s?autoplay=1&rel=0" title="Niti Resik" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </template>
                    <template x-if="!playing">
                        <div class="absolute inset-0">
                            <img src="{{ asset('images/thumbnail-aplikasi.jpg') }}" alt="Video" class="h-full w-full object-cover">
                            <div class="absolute inset-0 flex items-center justify-center" style="background:rgba(0,0,0,.3)">
                                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-white" style="box-shadow:0 8px 32px rgba(0,0,0,.3)">
                                    <svg width="26" height="26" fill="{{ $PRIMARY }}" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                </div>
                            </div>
                            <div class="absolute rounded-md px-2 py-1 text-xs font-semibold text-white" style="bottom:12px;right:12px;background:rgba(0,0,0,.6)">3:11</div>
                        </div>
                    </template>
                </div>

                <div class="p-7">
                    <h3 class="mb-2 font-bold text-white" style="font-size:22px">Niti Resik: Platform Ekonomi Sirkular Berbasis AI & Kearifan Lokal</h3>
                    <p class="mb-5" style="color:rgba(255,255,255,.65);font-size:14px;line-height:1.65">Pengenalan lengkap: dari latar krisis sampah Bali, solusi teknologi berbasis AI dan kearifan lokal, hingga demo tujuh fitur utama dalam satu ekosistem terintegrasi.</p>
                    <a href="https://drive.google.com/file/d/1qdsZAvu8XZVZXTN_Il5nG7jBrbTaxAQV/view?usp=sharing" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-semibold" style="color:{{ $PRIMARY }}">Lihat Video</a>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ CTA DOWNLOAD ============ --}}
    <section id="unduh" class="mx-auto max-w-7xl px-4 py-20">
        <div class="overflow-hidden rounded-3xl bg-primary-900 px-8 py-14 text-center">
            <h2 class="mx-auto max-w-xl text-3xl font-extrabold text-white">Mulai kelola sampahmu dari genggaman</h2>
            <p class="mx-auto mt-3 max-w-lg text-primary-100/80">Setor sampah, tukar saldo, belanja produk daur ulang, dan laporkan masalah sampah, semua lewat aplikasi Niti Resik.</p>
            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="#" class="inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3 text-sm font-bold text-primary-900 hover:bg-primary-50">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M3 20.5V3.5c0-.6.3-1 .8-1.3L13 12 3.8 21.8c-.5-.3-.8-.7-.8-1.3Zm12.5-7L6 3.9l11.6 6.6-2.1 3Zm3.7 2.1-2.6-1.5-2.3 2.3 2.3 2.3 2.6-1.5c.7-.4.7-1.5 0-1.9ZM6 20.1l9.5-9.5 2.1 3L6 20.1Z"/></svg>
                    Unduh Aplikasi
                </a>
            </div>
            <p class="mt-4 text-xs text-primary-100/50">Tautan unduh akan aktif saat aplikasi resmi dirilis.</p>
        </div>
    </section>
</div>