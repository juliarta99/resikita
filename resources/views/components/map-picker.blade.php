@props(['lat' => null, 'lng' => null])

<div wire:ignore
     x-data="{
        map: null,
        marker: null,
        init() {
            const la = ({{ $lat ?: 'null' }}) ?? -8.6478;
            const ln = ({{ $lng ?: 'null' }}) ?? 115.1385;
            this.map = L.map(this.$refs.map).setView([la, ln], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(this.map);
            if ({{ $lat ? 'true' : 'false' }}) this.setMarker(la, ln);
            this.map.on('click', (e) => this.fromMap(e.latlng.lat, e.latlng.lng));
            this.$wire.$watch('lat', () => this.fromInput());
            this.$wire.$watch('lng', () => this.fromInput());
        },
        setMarker(la, ln) {
            if (this.marker) {
                this.marker.setLatLng([la, ln]);
            } else {
                this.marker = L.marker([la, ln], { draggable: true }).addTo(this.map);
                this.marker.on('dragend', (e) => { const p = e.target.getLatLng(); this.fromMap(p.lat, p.lng); });
            }
        },
        fromMap(la, ln) {
            this.setMarker(la, ln);
            this.$wire.set('lat', la.toFixed(7));
            this.$wire.set('lng', ln.toFixed(7));
        },
        fromInput() {
            const la = parseFloat(this.$wire.get('lat'));
            const ln = parseFloat(this.$wire.get('lng'));
            if (isNaN(la) || isNaN(ln)) return;
            if (this.marker) {
                const c = this.marker.getLatLng();
                if (Math.abs(c.lat - la) < 1e-6 && Math.abs(c.lng - ln) < 1e-6) return;
            }
            this.setMarker(la, ln);
            this.map.setView([la, ln], Math.max(this.map.getZoom() || 15, 15));
        },
        recenter(la, ln) {
            setTimeout(() => {
                this.map.invalidateSize();
                if (la && ln) {
                    this.setMarker(parseFloat(la), parseFloat(ln));
                    this.map.setView([parseFloat(la), parseFloat(ln)], 16);
                } else {
                    this.map.setView([-8.6478, 115.1385], 12);
                    if (this.marker) { this.map.removeLayer(this.marker); this.marker = null; }
                }
            }, 150);
        }
     }"
     x-init="init()"
     @form-opened.window="recenter($event.detail.lat, $event.detail.lng)">
    <div x-ref="map" class="h-56 w-full rounded-lg border border-gray-300"></div>
    <p class="mt-1 text-xs text-gray-500">Klik peta, geser pin, atau isi koordinat manual di bawah.</p>
</div>