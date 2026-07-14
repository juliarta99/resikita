<div class="space-y-5">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Peta Sebaran Laporan</h1>
        <p class="text-slate-500 text-sm">Sebaran lokasi laporan yang ditugaskan kepada Anda.</p>
    </div>

    {{-- Filter --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Status Tugas</label>
                <select wire:model.live="filterStatus"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Semua Status</option>
                    <option value="ditugaskan">Ditugaskan</option>
                    <option value="dikerjakan">Dikerjakan</option>
                    <option value="selesai">Selesai</option>
                    <option value="dibatalkan">Dibatalkan</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Kategori</label>
                <select wire:model.live="filterKategori"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Semua Kategori</option>
                    @foreach ($kategori as $k)
                        <option value="{{ $k->id }}">{{ $k->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Dari Tanggal</label>
                <input type="date" wire:model.live="dariTanggal"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Sampai Tanggal</label>
                <input type="date" wire:model.live="sampaiTanggal"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>
        </div>
        <div class="mt-3 flex items-center justify-between">
            <p class="text-xs text-slate-400">{{ count($markers) }} titik ditampilkan.</p>
            <button wire:click="resetFilter" class="text-xs font-semibold text-slate-500 hover:text-slate-700">Reset Filter</button>
        </div>
    </div>

    {{-- Peta --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div wire:ignore
             x-data="{ markers: @entangle('markers'), ...petugasPeta() }"
             x-init="init()">
            <div x-ref="map" style="height: 520px; width: 100%;"></div>
        </div>
    </div>

    @push('scripts')
        <script>
            function petugasPeta() {
                return {
                    map: null,
                    layer: null,
                    colorFor(status) {
                        return {
                            ditugaskan: '#F59E0B',
                            dikerjakan: '#2563EB',
                            selesai: '#059669',
                            dibatalkan: '#94A3B8',
                        }[status] || '#059669';
                    },
                    icon(color) {
                        const svg = '<svg width="26" height="36" viewBox="0 0 26 36" xmlns="http://www.w3.org/2000/svg">'
                            + '<path d="M13 0C5.8 0 0 5.8 0 13c0 9.1 13 23 13 23s13-13.9 13-23C26 5.8 20.2 0 13 0z" fill="' + color + '"/>'
                            + '<circle cx="13" cy="13" r="5" fill="#fff"/></svg>';
                        return L.divIcon({ html: svg, className: '', iconSize: [26, 36], iconAnchor: [13, 36] });
                    },
                    render() {
                        this.layer.clearLayers();
                        const list = this.markers || [];
                        const bounds = [];
                        list.forEach((m) => {
                            const mk = L.marker([m.lat, m.lng], { icon: this.icon(this.colorFor(m.status)) });
                            const popup = '<div style="min-width:180px">'
                                + '<div style="font-weight:700;margin-bottom:2px">' + (m.judul || 'Laporan') + '</div>'
                                + '<div style="font-size:12px;color:#64748b">' + (m.kategori || '-') + '</div>'
                                + '<div style="font-size:12px;color:#64748b;margin:4px 0">' + (m.alamat || '-') + '</div>'
                                + '<a href="' + m.url + '" style="color:#059669;font-weight:600;font-size:12px">Lihat detail →</a>'
                                + '</div>';
                            mk.bindPopup(popup);
                            mk.addTo(this.layer);
                            bounds.push([m.lat, m.lng]);
                        });
                        if (bounds.length) this.map.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 });
                    },
                    init() {
                        // Cegah inisialisasi ganda pada container yang sama
                        if (this.map) return;
                        if (this.$refs.map._leaflet_id) {
                            this.$refs.map._leaflet_id = null;
                        }
                        this.map = L.map(this.$refs.map, { zoomControl: true })
                            .setView([-8.6478, 115.2028], 12); // Denpasar / Badung
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19, attribution: '© OpenStreetMap',
                        }).addTo(this.map);
                        this.layer = L.layerGroup().addTo(this.map);
                        this.render();
                        setTimeout(() => this.map.invalidateSize(), 200);

                        // Re-render marker saat data berubah dari filter Livewire
                        this.$watch('markers', () => this.render());
                    },
                };
            }
        </script>
    @endpush
</div>