import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

/*
 * Alpine dibawa Livewire, jadi komponen didaftarkan lewat alpine:init.
 * Mendaftarkannya sebelum peristiwa itu akan gagal diam-diam, Alpine
 * belum ada, dan x-data hanya akan tampak tidak melakukan apa pun.
 */
document.addEventListener('alpine:init', () => {
    /**
     * Peta sebaran titik laporan.
     *
     * Ubin diambil dari OpenStreetMap. Ia butuh atribusi yang terlihat,
     * dan itu bukan formalitas: syarat lisensinya, dan menghapusnya
     * membuat pemakaian peta ini tidak sah.
     */
    window.Alpine.data('petaLaporan', (titik = []) => ({
        peta: null,

        gambar() {
            if (titik.length === 0) return

            this.peta = L.map(this.$refs.peta, { scrollWheelZoom: false })

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; Kontributor OpenStreetMap',
            }).addTo(this.peta)

            const warna = {
                blue: '#2563eb',
                cyan: '#0891b2',
                amber: '#d97706',
                orange: '#ea580c',
                green: '#057D5D',
                red: '#dc2626',
                gray: '#6b7280',
            }

            const penanda = titik.map((t) =>
                L.circleMarker([t.latitude, t.longitude], {
                    radius: 7,
                    weight: 2,
                    color: '#ffffff',
                    fillColor: warna[t.warna] ?? warna.gray,
                    fillOpacity: 0.9,
                })
                    .bindPopup(
                        `<strong>${escapeHtml(t.judul)}</strong><br>` +
                            `<span style="color:#6b7280">${escapeHtml(t.tiket)}</span>` +
                            (t.kategori ? `<br>${escapeHtml(t.kategori)}` : ''),
                    )
                    .addTo(this.peta),
            )

            this.peta.fitBounds(L.featureGroup(penanda).getBounds(), { padding: [30, 30] })

            // Wadah peta kadang belum punya tinggi akhir saat digambar
            // di dalam kartu yang baru muncul; satu invalidate menyusul
            // membuat ubin tidak tampak terpotong.
            setTimeout(() => this.peta.invalidateSize(), 200)
        },
    }))

    /**
     * Peta fasilitas publik: bank sampah, TPS, dan TPS3R.
     *
     * Warnanya membedakan jenis, bukan status, karena yang dicari
     * pengunjung halaman ini adalah "ke mana saya harus pergi", bukan
     * seberapa sibuk tempat itu.
     */
    window.Alpine.data('petaFasilitas', (titik = []) => ({
        peta: null,

        gambar() {
            if (titik.length === 0) return

            this.peta = L.map(this.$refs.peta, { scrollWheelZoom: false })

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; Kontributor OpenStreetMap',
            }).addTo(this.peta)

            const warna = {
                bank_sampah: '#057D5D',
                tps: '#2563eb',
                tps3r: '#7c3aed',
            }

            const label = {
                bank_sampah: 'Bank sampah',
                tps: 'TPS',
                tps3r: 'TPS3R',
            }

            const penanda = titik.map((t) =>
                L.circleMarker([t.latitude, t.longitude], {
                    radius: 8,
                    weight: 2,
                    color: '#ffffff',
                    fillColor: warna[t.jenis] ?? warna.tps,
                    fillOpacity: 0.9,
                })
                    .bindPopup(
                        `<strong>${escapeHtml(t.nama)}</strong><br>` +
                            `<span style="color:#057D5D">${escapeHtml(label[t.jenis] ?? t.jenis)}</span>` +
                            (t.wilayah ? `<br>${escapeHtml(t.wilayah)}` : '') +
                            (t.alamat ? `<br><span style="color:#6b7280">${escapeHtml(t.alamat)}</span>` : ''),
                    )
                    .addTo(this.peta),
            )

            this.peta.fitBounds(L.featureGroup(penanda).getBounds(), { padding: [30, 30] })

            setTimeout(() => this.peta.invalidateSize(), 200)
        },
    }))

    /**
     * Pembaca artikel lewat speechSynthesis peramban.
     *
     * Teksnya datang utuh dari peladen, sudah dibersihkan dari markdown
     * oleh TeksBacaService, sehingga web dan aplikasi ponsel membacakan
     * kalimat yang persis sama.
     *
     * Teks dipecah menjadi potongan pendek sebelum diucapkan. Chrome
     * memotong ucapan secara diam-diam pada teks yang panjang, dan
     * artikel yang berhenti di tengah tanpa penjelasan jauh lebih buruk
     * daripada artikel yang jeda sepersekian detik antar paragraf.
     */
    window.Alpine.data('pembacaArtikel', (teks = '') => ({
        didukung: false,
        sedangMemutar: false,
        pernahMulai: false,
        kecepatan: 1,
        keterangan: '',
        potongan: [],
        indeks: 0,

        siapkan() {
            this.didukung = typeof window.speechSynthesis !== 'undefined'
                && typeof window.SpeechSynthesisUtterance !== 'undefined'

            if (!this.didukung) return

            this.potongan = pecahUntukDibacakan(teks)
            this.keterangan = `Siap dibacakan dalam ${this.potongan.length} bagian.`

            // Ucapan yang masih berjalan saat pengguna berpindah halaman
            // akan terus terdengar di halaman berikutnya.
            const hentikanSaatPindah = () => window.speechSynthesis.cancel()
            document.addEventListener('livewire:navigating', hentikanSaatPindah)
            window.addEventListener('beforeunload', hentikanSaatPindah)
        },

        putarAtauJeda() {
            if (this.sedangMemutar) {
                window.speechSynthesis.pause()
                this.sedangMemutar = false
                this.keterangan = 'Dijeda.'
                return
            }

            if (this.pernahMulai && window.speechSynthesis.paused) {
                window.speechSynthesis.resume()
                this.sedangMemutar = true
                this.keterangan = 'Melanjutkan pembacaan.'
                return
            }

            this.mulai()
        },

        mulai() {
            window.speechSynthesis.cancel()

            this.indeks = 0
            this.sedangMemutar = true

            if (!this.pernahMulai) {
                this.pernahMulai = true
                // Dihitung sekali per kunjungan, saat pemutaran benar-benar
                // dimulai, bukan saat halaman dibuka.
                this.$wire.catatDidengarkan()
            }

            this.ucapkanBerikutnya()
        },

        ucapkanBerikutnya() {
            if (this.indeks >= this.potongan.length) {
                this.sedangMemutar = false
                this.keterangan = 'Selesai dibacakan.'
                return
            }

            const ucapan = new SpeechSynthesisUtterance(this.potongan[this.indeks])
            ucapan.lang = 'id-ID'
            ucapan.rate = this.kecepatan

            ucapan.onend = () => {
                this.indeks += 1
                if (this.sedangMemutar) this.ucapkanBerikutnya()
            }

            ucapan.onerror = () => {
                this.sedangMemutar = false
                this.keterangan = 'Pembacaan terhenti. Coba mulai ulang.'
            }

            this.keterangan = `Membacakan bagian ${this.indeks + 1} dari ${this.potongan.length}.`
            window.speechSynthesis.speak(ucapan)
        },

        ulangJikaSedangMemutar() {
            if (!this.sedangMemutar) return

            // Kecepatan hanya berlaku pada ucapan baru, jadi potongan yang
            // sedang berjalan diputar ulang dari awalnya.
            window.speechSynthesis.cancel()
            this.ucapkanBerikutnya()
        },

        hentikan() {
            window.speechSynthesis.cancel()
            this.sedangMemutar = false
            this.pernahMulai = false
            this.indeks = 0
            this.keterangan = 'Dihentikan.'
        },
    }))

    /**
     * Pendiktean pertanyaan lewat Web Speech API.
     *
     * Hasilnya masuk ke properti Livewire sebagai teks biasa. Berkas
     * audionya tidak pernah dikirim ke peladen: menyimpan rekaman suara
     * warga menciptakan kewajiban perlindungan data yang tidak sepadan
     * dengan manfaatnya, sementara yang dibutuhkan sistem hanya teksnya.
     */
    window.Alpine.data('pendiktean', () => ({
        didukung: false,
        merekam: false,
        keterangan: '',
        pengenal: null,

        init() {
            const Pengenal = window.SpeechRecognition ?? window.webkitSpeechRecognition
            this.didukung = typeof Pengenal !== 'undefined'

            if (!this.didukung) return

            this.pengenal = new Pengenal()
            this.pengenal.lang = 'id-ID'
            this.pengenal.continuous = true
            this.pengenal.interimResults = false

            this.pengenal.onresult = (e) => {
                const potongan = Array.from(e.results)
                    .slice(e.resultIndex)
                    .map((r) => r[0].transcript)
                    .join(' ')
                    .trim()

                if (!potongan) return

                const sekarang = this.$refs.kotak.value
                const gabungan = sekarang ? `${sekarang} ${potongan}` : potongan

                this.$refs.kotak.value = gabungan
                this.$wire.set('pesan', gabungan)
                this.$wire.set('lewatSuara', true)
            }

            this.pengenal.onerror = (e) => {
                this.merekam = false
                this.keterangan =
                    e.error === 'not-allowed'
                        ? 'Akses mikrofon ditolak. Izinkan di pengaturan peramban Anda.'
                        : 'Pendiktean terhenti. Coba lagi.'
            }

            this.pengenal.onend = () => {
                this.merekam = false
                if (this.keterangan.startsWith('Mendengarkan')) this.keterangan = ''
            }
        },

        alihkan() {
            if (this.merekam) {
                this.pengenal.stop()
                return
            }

            try {
                this.pengenal.start()
                this.merekam = true
                this.keterangan = 'Mendengarkan… bicara dengan jelas, lalu tekan tombol lagi untuk berhenti.'
            } catch {
                this.keterangan = 'Pendiktean tidak bisa dimulai. Coba muat ulang halaman.'
            }
        },
    }))

    /**
     * Pembaca satu jawaban asisten.
     *
     * Jawaban chatbot memang sudah disiapkan untuk dibacakan, tanpa
     * judul markdown, tabel, atau blok kode, jadi teksnya bisa langsung
     * diserahkan ke pembaca suara tanpa pembersihan tambahan.
     */
    window.Alpine.data('pembacaJawaban', (teks = '') => ({
        didukung: typeof window.speechSynthesis !== 'undefined',
        sedangMemutar: false,

        putarAtauHenti() {
            if (this.sedangMemutar) {
                window.speechSynthesis.cancel()
                this.sedangMemutar = false
                return
            }

            window.speechSynthesis.cancel()

            const potongan = pecahUntukDibacakan(teks)
            let indeks = 0

            const lanjut = () => {
                if (indeks >= potongan.length || !this.sedangMemutar) {
                    this.sedangMemutar = false
                    return
                }

                const ucapan = new SpeechSynthesisUtterance(potongan[indeks])
                ucapan.lang = 'id-ID'
                ucapan.onend = () => {
                    indeks += 1
                    lanjut()
                }
                ucapan.onerror = () => {
                    this.sedangMemutar = false
                }

                window.speechSynthesis.speak(ucapan)
            }

            this.sedangMemutar = true
            lanjut()
        },
    }))

    /**
     * Penyusun sampul produk.
     *
     * Seluruh keputusan tata letak datang dari SampulService sebagai
     * daftar lapisan siap gambar: bidang ini di koordinat ini, tumpukan
     * teks ini berjangkar di titik ini. Tidak ada nama gaya yang
     * diterjemahkan di sini, kalau ada, aturan tata letak akan hidup di
     * dua tempat sekaligus dan cepat atau lambat keduanya berbeda.
     *
     * Yang memang diselesaikan di peramban hanya pengukuran: berapa
     * baris sebuah kalimat setelah dipatahkan pada lebar tertentu, dan
     * seberapa tinggi tumpukan jadinya. Itu bergantung pada metrik huruf
     * yang baru ada setelah Plus Jakarta Sans termuat.
     *
     * Foto yang digambar selalu foto produk asli yang diunggah penjual.
     * Tidak ada citra hasil generate di mana pun pada alur ini.
     */
    window.Alpine.data('penyusunSampul', (spek) => ({
        siap: false,
        keterangan: 'Menyiapkan sampul…',

        async gambar() {
            const kanvas = this.$refs.kanvas
            kanvas.width = spek.lebar
            kanvas.height = spek.tinggi

            const ctx = kanvas.getContext('2d')
            ctx.clearRect(0, 0, spek.lebar, spek.tinggi)

            let foto
            try {
                foto = await muatGambar(spek.foto_url)
            } catch {
                this.keterangan = 'Foto produk gagal dimuat. Muat ulang halaman lalu coba lagi.'
                return
            }

            // Huruf harus benar-benar siap sebelum menggambar; kalau
            // tidak, teks dirender dengan huruf cadangan dan hasil
            // unduhannya berbeda dari yang dilihat pengguna.
            if (document.fonts?.ready) await document.fonts.ready

            gambarFotoKotak(ctx, foto, spek.foto.kotak)

            for (const lapis of spek.lapisan ?? []) {
                gambarLapisan(ctx, lapis)
            }

            this.siap = true
            this.keterangan = 'Sampul siap. Simpan untuk menyimpannya di Resikita, atau unduh langsung.'
        },

        kirim() {
            if (!this.siap) return
            this.keterangan = 'Menyimpan…'
            this.$wire.simpanSampul(this.$refs.kanvas.toDataURL('image/png'))
        },

        unduh() {
            if (!this.siap) return
            const tautan = document.createElement('a')
            tautan.download = `sampul-resikita-${spek.gaya}-${spek.rasio}.png`
            tautan.href = this.$refs.kanvas.toDataURL('image/png')
            tautan.click()
        },
    }))

    /**
     * Pemilih titik koordinat untuk formulir.
     *
     * Menulis balik ke properti Livewire lewat wire:model, sehingga
     * lintang dan bujur tetap bisa diketik manual oleh yang lebih suka
     * menyalin dari sumber lain.
     */
    window.Alpine.data('pemilihTitik', (lat, lng) => ({
        peta: null,
        penanda: null,

        gambar() {
            const awal = [lat || -2.5, lng || 118]

            this.peta = L.map(this.$refs.peta).setView(awal, lat && lng ? 15 : 5)

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; Kontributor OpenStreetMap',
            }).addTo(this.peta)

            if (lat && lng) this.taruh(awal)

            this.peta.on('click', (e) => {
                this.taruh([e.latlng.lat, e.latlng.lng])
                this.$dispatch('titik-dipilih', {
                    latitude: e.latlng.lat.toFixed(7),
                    longitude: e.latlng.lng.toFixed(7),
                })
            })

            setTimeout(() => this.peta.invalidateSize(), 200)
        },

        taruh(posisi) {
            if (this.penanda) this.peta.removeLayer(this.penanda)
            this.penanda = L.marker(posisi).addTo(this.peta)
        },
    }))
})

/** Popup Leaflet menerima HTML mentah, jadi isinya dilepas dari makna tag. */
function escapeHtml(teks) {
    const el = document.createElement('div')
    el.textContent = teks ?? ''
    return el.innerHTML
}

// ---------------------------------------------------------------------
// Penggambaran sampul
// ---------------------------------------------------------------------

function muatGambar(url) {
    return new Promise((resolve, reject) => {
        const gambar = new Image()
        // Foto berada di origin yang sama, tapi crossOrigin tetap disetel
        // supaya kanvas tidak menjadi "tainted" ketika aset dilayani dari
        // CDN di kemudian hari, kanvas tainted tidak bisa diekspor.
        gambar.crossOrigin = 'anonymous'
        gambar.onload = () => resolve(gambar)
        gambar.onerror = reject
        gambar.src = url
    })
}

/**
 * Isi sebuah bidang dengan foto, dipotong tengah tanpa merusak rasio.
 *
 * Bidangnya belum tentu seluruh kanvas: gaya berpanel menyisakan sebagian
 * kanvas untuk warna padat. Pemotongan tetap diambil dari tengah foto,
 * karena di situlah pokok gambar hampir selalu berada.
 */
function gambarFotoKotak(ctx, foto, [x, y, lebar, tinggi]) {
    const skala = Math.max(lebar / foto.width, tinggi / foto.height)
    const l = foto.width * skala
    const t = foto.height * skala

    ctx.save()
    ctx.beginPath()
    ctx.rect(x, y, lebar, tinggi)
    ctx.clip()
    ctx.drawImage(foto, x + (lebar - l) / 2, y + (tinggi - t) / 2, l, t)
    ctx.restore()
}

function gambarLapisan(ctx, lapis) {
    if (lapis.jenis === 'kotak') return gambarKotak(ctx, lapis)
    if (lapis.jenis === 'gradasi') return gambarGradasi(ctx, lapis)
    if (lapis.jenis === 'tumpukan') return gambarTumpukan(ctx, lapis)
}

function gambarKotak(ctx, { kotak: [x, y, lebar, tinggi], warna, radius }) {
    ctx.fillStyle = warna

    if (radius) {
        bulatkan(ctx, x, y, lebar, tinggi, radius)
        ctx.fill()
        return
    }

    ctx.fillRect(x, y, lebar, tinggi)
}

function gambarGradasi(ctx, { kotak: [x, y, lebar, tinggi], dari, ke }) {
    const gradasi = ctx.createLinearGradient(x, y, x, y + tinggi)
    gradasi.addColorStop(0, dari)
    gradasi.addColorStop(1, ke)

    ctx.fillStyle = gradasi
    ctx.fillRect(x, y, lebar, tinggi)
}

/**
 * Tumpukan teks berjangkar.
 *
 * Diukur seluruhnya lebih dulu, baru digambar. Tingginya tidak bisa
 * diketahui peladen karena bergantung pada berapa baris tiap kalimat
 * setelah dipatahkan, dan itu bergantung pada metrik huruf. Yang
 * ditentukan peladen adalah titik jangkarnya dan ke arah mana tumpukan
 * tumbuh, sehingga menambah satu baris tidak pernah mendorong teks
 * keluar dari bidangnya.
 */
function gambarTumpukan(ctx, lapis) {
    const { x, y, lebar_maks: lebarMaks, jangkar, rata, jarak, latar, blok } = lapis

    const diukur = blok.map((b) => ukurBlok(ctx, b, lebarMaks))
    const isi = diukur.filter((b) => b.tinggi > 0)

    if (isi.length === 0) return

    const total = isi.reduce((jml, b) => jml + b.tinggi, 0) + jarak * (isi.length - 1)

    let atas = y
    if (jangkar === 'bawah') atas = y - total
    if (jangkar === 'tengah') atas = y - total / 2

    if (latar) {
        const kiri = rata === 'tengah' ? x - lebarMaks / 2 : x
        ctx.fillStyle = latar.warna
        bulatkan(
            ctx,
            kiri - latar.sisip,
            atas - latar.sisip,
            lebarMaks + latar.sisip * 2,
            total + latar.sisip * 2,
            latar.radius,
        )
        ctx.fill()
    }

    let kursor = atas

    for (const b of isi) {
        b.gambar(ctx, x, kursor, lebarMaks, rata)
        kursor += b.tinggi + jarak
    }
}

/** Ukur satu blok dan kembalikan tinggi beserta cara menggambarnya. */
function ukurBlok(ctx, blok, lebarMaks) {
    return blok.jenis === 'pil'
        ? ukurPil(ctx, blok, lebarMaks)
        : ukurTeks(ctx, blok, lebarMaks)
}

function ukurTeks(ctx, blok, lebarMaks) {
    if (!blok.isi) return { tinggi: 0, gambar() {} }

    ctx.font = fontDari(blok)

    const baris = pecahBaris(ctx, blok.isi, lebarMaks).slice(0, blok.baris_maks)
    const tinggiBaris = blok.ukuran * blok.tinggi_baris

    return {
        tinggi: baris.length * tinggiBaris,
        gambar(ctx, x, atas, lebarMaks, rata) {
            ctx.font = fontDari(blok)
            ctx.fillStyle = blok.warna
            ctx.textBaseline = 'top'
            ctx.textAlign = rata === 'tengah' ? 'center' : 'left'

            baris.forEach((isi, i) => {
                // Selisih tinggi baris dan tinggi huruf dibagi rata di
                // atas dan bawah, supaya jarak antarblok terlihat sama
                // besar walau tinggi barisnya berbeda-beda.
                const sisa = (tinggiBaris - blok.ukuran) / 2
                ctx.fillText(isi, x, atas + i * tinggiBaris + sisa)
            })

            ctx.textAlign = 'left'
            ctx.textBaseline = 'alphabetic'
        },
    }
}

/** Baris pil keterangan; melipat ke baris berikutnya kalau tidak muat. */
function ukurPil(ctx, blok, lebarMaks) {
    const daftar = (blok.isi ?? []).filter(Boolean)

    if (daftar.length === 0) return { tinggi: 0, gambar() {} }

    ctx.font = fontDari(blok)

    const sisip = blok.ukuran * 0.8
    const tinggiPil = Math.round(blok.ukuran * 2)

    const baris = []
    let sekarang = []
    let lebarBaris = 0

    for (const teks of daftar) {
        const lebarPil = ctx.measureText(teks).width + sisip * 2
        const tambahan = sekarang.length === 0 ? lebarPil : lebarPil + blok.jarak

        if (lebarBaris + tambahan > lebarMaks && sekarang.length > 0) {
            baris.push({ pil: sekarang, lebar: lebarBaris })
            sekarang = [{ teks, lebar: lebarPil }]
            lebarBaris = lebarPil
        } else {
            sekarang.push({ teks, lebar: lebarPil })
            lebarBaris += tambahan
        }
    }

    if (sekarang.length > 0) baris.push({ pil: sekarang, lebar: lebarBaris })

    return {
        tinggi: baris.length * tinggiPil + (baris.length - 1) * blok.jarak,
        gambar(ctx, x, atas, lebarMaks, rata) {
            ctx.font = fontDari(blok)
            ctx.textBaseline = 'middle'

            baris.forEach((b, i) => {
                const y = atas + i * (tinggiPil + blok.jarak)
                let kursor = rata === 'tengah' ? x - b.lebar / 2 : x

                for (const p of b.pil) {
                    ctx.fillStyle = blok.warna_latar
                    bulatkan(ctx, kursor, y, p.lebar, tinggiPil, tinggiPil / 2)
                    ctx.fill()

                    ctx.fillStyle = blok.warna
                    ctx.textAlign = 'left'
                    ctx.fillText(p.teks, kursor + sisip, y + tinggiPil / 2)

                    kursor += p.lebar + blok.jarak
                }
            })

            ctx.textAlign = 'left'
            ctx.textBaseline = 'alphabetic'
        },
    }
}

function fontDari(blok) {
    return `${blok.berat} ${blok.ukuran}px "Plus Jakarta Sans Variable", "Plus Jakarta Sans", system-ui, sans-serif`
}

function bulatkan(ctx, x, y, lebar, tinggi, jari) {
    ctx.beginPath()
    ctx.moveTo(x + jari, y)
    ctx.arcTo(x + lebar, y, x + lebar, y + tinggi, jari)
    ctx.arcTo(x + lebar, y + tinggi, x, y + tinggi, jari)
    ctx.arcTo(x, y + tinggi, x, y, jari)
    ctx.arcTo(x, y, x + lebar, y, jari)
    ctx.closePath()
}

/** Pecah teks menjadi baris yang muat dalam lebar tertentu. */
function pecahBaris(ctx, teks, lebarMaks) {
    const kata = String(teks).split(/\s+/).filter(Boolean)
    const baris = []
    let sekarang = ''

    for (const k of kata) {
        const calon = sekarang ? `${sekarang} ${k}` : k

        if (ctx.measureText(calon).width > lebarMaks && sekarang) {
            baris.push(sekarang)
            sekarang = k
        } else {
            sekarang = calon
        }
    }

    if (sekarang) baris.push(sekarang)

    return baris
}

/**
 * Pecah teks menjadi potongan yang aman diucapkan.
 *
 * Pemotongan mengikuti akhir kalimat, bukan jumlah karakter mentah,
 * supaya jeda antar potongan jatuh di tempat yang memang wajar untuk
 * mengambil napas.
 */
function pecahUntukDibacakan(teks, batas = 200) {
    const kalimat = (teks ?? '')
        .split(/(?<=[.!?])\s+|\n{2,}/)
        .map((k) => k.trim())
        .filter(Boolean)

    const potongan = []
    let sekarang = ''

    for (const k of kalimat) {
        if ((sekarang + ' ' + k).trim().length > batas && sekarang !== '') {
            potongan.push(sekarang.trim())
            sekarang = k
        } else {
            sekarang = (sekarang + ' ' + k).trim()
        }
    }

    if (sekarang !== '') potongan.push(sekarang)

    return potongan
}
