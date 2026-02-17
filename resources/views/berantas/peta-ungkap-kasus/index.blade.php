@extends('admin')

@section('content')
<main class="admin-main" x-data="mapComponent">
    {{-- CONTAINER FULL HEIGHT AGAR PETA MAKSIMAL --}}
    <div class="container-fluid p-0 position-relative" style="height: calc(100vh - 70px); overflow: hidden;">
        
        {{-- 1. TOMBOL TOGGLE FILTER (DESKTOP/MOBILE) --}}
        <button class="btn btn-light shadow position-absolute top-0 start-0 m-3 z-3 border fw-bold text-secondary" 
                type="button" 
                @click="showFilter = !showFilter">
            <i class="bi bi-funnel-fill me-2 text-primary"></i>Filter Data
        </button>

        {{-- 2. SIDEBAR FILTER --}}
        <div class="position-absolute top-0 start-0 h-100 bg-white shadow z-2 transition-all d-flex flex-column"
             style="width: 350px; max-width: 90vw;"
             :style="showFilter ? 'transform: translateX(0);' : 'transform: translateX(-100%);'"
             x-transition>
            
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
                <h6 class="mb-0 fw-bold"><i class="bi bi-funnel me-2"></i>Filter Peta</h6>
                <button type="button" class="btn-close btn-sm" @click="showFilter = false"></button>
            </div>

            <div class="p-3 overflow-auto flex-grow-1">
                <form id="filter-form" @submit.prevent="loadMapData">
                    
                    {{-- TAHUN & BULAN --}}
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Tahun</label>
                            <select name="tahun[]" class="form-select" multiple id="select-tahun" placeholder="Pilih Tahun...">
                                @foreach($years as $y) <option value="{{ $y }}" selected>{{ $y }}</option> @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Bulan</label>
                            <select name="bulan[]" class="form-select" multiple id="select-bulan" placeholder="Pilih Bulan...">
                                @foreach(range(1, 12) as $m) <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->locale('id')->translatedFormat('F') }}</option> @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- SATKER (ADMIN ONLY) --}}
                    @if(Auth::user()->hasRole('admin'))
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Satuan Kerja</label>
                        <select name="satuan_kerja_id[]" class="form-select" multiple id="select-satker" placeholder="Semua Satuan Kerja">
                            @foreach($satuanKerjas as $sat) <option value="{{ $sat->id }}">{{ $sat->satuan_kerja }}</option> @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- NARKOTIKA --}}
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary">Jenis Narkotika</label>
                        <select name="narkotika_ids[]" class="form-select" multiple id="select-narkotika" placeholder="Semua Jenis Narkotika">
                            @foreach($masterNarkotika as $n) <option value="{{ $n->id }}">{{ $n->nama_narkotika }}</option> @endforeach
                        </select>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary" :disabled="isLoading">
                            <span x-show="isLoading" class="spinner-border spinner-border-sm me-2"></span>
                            Terapkan Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- 3. CONTAINER PETA --}}
        <div id="map" class="w-100 h-100 z-0 bg-secondary-subtle"></div>

        {{-- 4. FLOATING LEGEND (POJOK KIRI BAWAH) --}}
        <div class="card position-absolute bottom-0 start-0 m-3 z-1 shadow border-0 bg-white bg-opacity-90 mb-5 pb-4" style="width: 260px;">
            <div class="card-body p-2 px-3 small">
                <h6 class="fw-bold mb-2 border-bottom pb-1">Legenda Peta</h6>
                
                {{-- Marker Size --}}
                <div class="d-flex align-items-center mb-2">
                    <div class="d-flex align-items-center justify-content-center me-2" style="width: 24px;">
                        <div style="width: 14px; height: 14px; background: rgba(220, 53, 69, 0.6); border: 2px solid #dc3545; border-radius: 50%;"></div>
                    </div>
                    <div class="lh-1">
                        <div class="fw-bold text-dark">Titik Kasus</div>
                        <div class="text-muted" style="font-size: 0.7rem;">Ukuran = Total Berat BB</div>
                    </div>
                </div>

                {{-- Choropleth Color --}}
                <div class="mt-2">
                    <div class="fw-bold text-dark mb-1">Tingkat Kerawanan (Total Kasus)</div>
                    <div class="d-flex rounded-1 overflow-hidden" style="height: 8px;">
                        <div class="flex-fill" style="background: #22c55e;"></div> {{-- Hijau --}}
                        <div class="flex-fill" style="background: #ffc107;"></div> {{-- Kuning --}}
                        <div class="flex-fill" style="background: #dc3545;"></div> {{-- Merah --}}
                    </div>
                    <div class="d-flex justify-content-between text-muted mt-1" style="font-size: 0.65rem;">
                        <span>Rendah</span>
                        <span>Sedang</span>
                        <span>Tinggi</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 5. FOOTER STATISTIK (MELAYANG DI TENGAH) --}}
        <div class="position-absolute bottom-0 start-50 translate-middle-x mb-3 z-3 w-auto" style="max-width: 90%;">
            <div class="card shadow-lg border-0 rounded-pill px-4 py-2 bg-white">
                <div class="d-flex align-items-center gap-4 text-nowrap">
                    <div class="text-center">
                        <div class="text-muted fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">TOTAL KASUS</div>
                        <div class="fw-bolder fs-5 text-primary lh-1" x-text="stats.total_kasus">0</div>
                    </div>
                    <div class="vr opacity-25"></div>
                    <div class="text-center">
                        <div class="text-muted fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">TERSANGKA</div>
                        <div class="fw-bolder fs-5 text-dark lh-1" x-text="stats.total_tersangka">0</div>
                    </div>
                    <div class="vr opacity-25"></div>
                    <div class="text-center">
                        <div class="text-muted fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">BERAT BB (GRAM)</div>
                        <div class="fw-bolder fs-5 text-danger lh-1" x-text="formatNumber(stats.total_berat_gram)">0</div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- 6. MODAL DETAIL --}}
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-info-circle me-2"></i>Detail Ungkap Kasus</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" id="modal-content-body">
                    {{-- Content loaded via AJAX --}}
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

</main>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    /* CSS Tambahan */
    .ts-control { border: 1px solid #dee2e6 !important; border-radius: 0.375rem !important; }
    .ts-wrapper.multi .ts-control > div { background: #e9ecef; border-radius: 3px; padding: 0 5px; }
    .admin-main { padding-bottom: 0 !important; } 
    
    /* Fix Z-Index agar Modal di atas Peta */
    .modal-backdrop { z-index: 1050; }
    .modal { z-index: 1060; }
    
    /* Styling Tooltip Peta */
    .custom-tooltip { 
        background: rgba(255, 255, 255, 0.95); 
        border: 1px solid #666; 
        box-shadow: 2px 2px 5px rgba(0,0,0,0.2); 
        font-size: 0.8rem;
        border-radius: 4px;
        padding: 4px 8px;
    }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
{{-- Heatmap Plugin --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.heat/0.2.0/leaflet-heat.js"></script>
{{-- Turf.js untuk Analisis Spasial --}}
<script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('mapComponent', () => ({
            showFilter: true,
            isLoading: false,
            map: null,
            markersLayer: null,     // Layer Group untuk Titik
            choroplethLayer: null,  // Layer Group untuk Wilayah
            heatLayer: null,        // Layer Heatmap
            geoJsonData: null,      // Raw Data GeoJSON
            stats: { total_kasus: 0, total_tersangka: 0, total_berat_gram: 0 },
            detailModal: null,
            
            // URL Helper dengan placeholder untuk replace ID nanti
            detailRouteUrl: "{{ route('berantas.peta-ungkap-kasus.show', ':id') }}",

            init() {
                // 1. Init TomSelect
                const config = { plugins: ['remove_button', 'clear_button'], maxOptions: null };
                ['select-tahun','select-bulan','select-satker','select-narkotika'].forEach(id => {
                    if(document.getElementById(id)) new TomSelect('#'+id, config);
                });

                // 2. Init Modal Bootstrap
                this.detailModal = new bootstrap.Modal(document.getElementById('detailModal'));

                // 3. Init Peta
                this.initMap();
            },

            initMap() {
                // Setup Map (Zoom & Center ke Sultra)
                this.map = L.map('map', { zoomControl: false }).setView([-4.10, 122.10], 7);
                L.control.zoom({ position: 'topright' }).addTo(this.map);

                // Basemap Clean (Grey)
                L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                    attribution: '© CARTO',
                    maxZoom: 19
                }).addTo(this.map);

                // Inisialisasi Layer Groups
                this.choroplethLayer = L.layerGroup().addTo(this.map);
                this.heatLayer = L.layerGroup(); // Default off
                this.markersLayer = L.layerGroup().addTo(this.map); 

                // Layer Control (Pojok Kanan Atas)
                const overlayMaps = {
                    "Peta Kerawanan (Wilayah)": this.choroplethLayer,
                    "Titik Kasus (Marker)": this.markersLayer,
                    "Heatmap Densitas (Berat BB)": this.heatLayer // Update Label
                };
                L.control.layers(null, overlayMaps, { position: 'topright' }).addTo(this.map);

                // Load GeoJSON Sultra
                fetch("{{ asset('maps/sultra_kabupaten.geojson') }}")
                    .then(r => r.json())
                    .then(data => {
                        this.geoJsonData = data;
                        this.loadMapData(); // Load data kasus setelah peta siap
                    })
                    .catch(err => {
                        console.error("Gagal load GeoJSON", err);
                        Swal.fire("Error", "Gagal memuat peta dasar Sultra", "error");
                    });
            },

            async loadMapData() {
                this.isLoading = true;
                const formData = new FormData(document.getElementById('filter-form'));
                const params = new URLSearchParams(formData).toString();

                try {
                    // Fetch Data API
                    const res = await fetch(`{{ route('berantas.peta-ungkap-kasus.data') }}?${params}`);
                    const data = await res.json();

                    // Update Stats
                    this.stats = data.stats;

                    // Render Map
                    this.renderMap(data.features);
                    
                    // Auto close sidebar di mobile
                    if(window.innerWidth < 768) this.showFilter = false;

                } catch (e) {
                    console.error("Error loading map data", e);
                    Swal.fire('Error', 'Gagal memuat data sebaran kasus', 'error');
                } finally {
                    this.isLoading = false;
                }
            },

            renderMap(features) {
                // Reset Layer
                this.markersLayer.clearLayers();
                this.choroplethLayer.clearLayers();
                this.heatLayer.clearLayers();

                let maxWeight = 0;
                const heatPoints = [];

                // Cari berat maksimum untuk skala radius marker & intensitas heatmap
                features.forEach(f => { 
                    if(f.properties.berat_gram > maxWeight) maxWeight = f.properties.berat_gram; 
                });

                // --- 1. RENDER MARKERS & DATA HEATMAP ---
                features.forEach(f => {
                    const props = f.properties;
                    const coords = f.geometry.coordinates; // GeoJSON [Lng, Lat]
                    const lat = coords[1];
                    const lng = coords[0];

                    // Marker
                    const radius = this.calculateRadius(props.berat_gram, maxWeight);
                    const marker = L.circleMarker([lat, lng], {
                        radius: radius,
                        fillColor: "#dc3545",
                        color: "#fff",
                        weight: 1,
                        opacity: 1,
                        fillOpacity: 0.8
                    });

                    // Tooltip Sederhana
                    marker.bindTooltip(`
                        <div class='text-center fw-bold small'>
                            ${props.tkp}<br>
                            <span class='text-danger'>${this.formatNumber(props.berat_gram)} gram</span>
                        </div>
                    `, { direction: 'top', offset: [0, -5] });

                    // CLICK EVENT: Buka Modal Langsung
                    marker.on('click', () => {
                        this.fetchDetail(props.id);
                    });

                    this.markersLayer.addLayer(marker);

                    // --- PERBAIKAN LOGIKA HEATMAP ---
                    // Hitung intensitas berdasarkan berat barang bukti relative terhadap maxWeight
                    // Jika maxWeight 0 (tidak ada data), intensitas 0
                    // Kita gunakan nilai minimum 0.3 agar titik dengan berat kecil tetap terlihat sedikit
                    let intensity = 0.3; 
                    if (maxWeight > 0) {
                        intensity = 0.3 + (0.7 * (props.berat_gram / maxWeight));
                    }
                    
                    // Pastikan intensitas tidak lebih dari 1.0
                    intensity = Math.min(intensity, 1.0);

                    // Heatmap Data (Lat, Lng, Intensity)
                    heatPoints.push([lat, lng, intensity]);
                });

                // Tambah Layer Heatmap
                if (heatPoints.length > 0) {
                    const heat = L.heatLayer(heatPoints, {
                        radius: 35, // Sedikit diperbesar agar gradasi lebih terlihat
                        blur: 20,
                        maxZoom: 10, // Zoom level dimana intensitas mencapai maksimum visual
                        // Gradasi warna heatmap: Biru (Rendah) -> Hijau -> Kuning -> Merah (Tinggi)
                        gradient: {0.2: 'blue', 0.4: 'lime', 0.6: 'yellow', 1: 'red'}
                    });
                    this.heatLayer.addLayer(heat);
                }

                // --- 2. RENDER CHOROPLETH (WARNA WILAYAH) ---
                if(this.geoJsonData) {
                    this.processChoropleth(features);
                }
            },

            processChoropleth(casePoints) {
                // Konversi titik kasus ke format Turf FeatureCollection
                const turfPoints = turf.featureCollection(casePoints);
                
                const regionCounts = {}; 
                let maxCount = 0;

                // Hitung jumlah kasus per wilayah (Polygon)
                this.geoJsonData.features.forEach(feature => {
                    const ptsWithin = turf.pointsWithinPolygon(turfPoints, feature);
                    const count = ptsWithin.features.length;
                    
                    regionCounts[feature.properties.code] = count;
                    if(count > maxCount) maxCount = count;
                });

                // Buat Layer GeoJSON dengan Style Function
                const geoLayer = L.geoJson(this.geoJsonData, {
                    // STYLE FUNCTION: Dijalankan saat inisialisasi
                    style: (feature) => {
                        const code = feature.properties.code;
                        const count = regionCounts[code] || 0;
                        
                        return {
                            fillColor: this.getColor(count, maxCount),
                            weight: 1,
                            opacity: 1,
                            color: 'white', // Border antar wilayah
                            dashArray: '3',
                            fillOpacity: 0.6 // Transparansi
                        };
                    },
                    // EVENT LISTENER
                    onEachFeature: (feature, layer) => {
                        const code = feature.properties.code;
                        const count = regionCounts[code] || 0;

                        // Tooltip Nama Wilayah & Jumlah
                        layer.bindTooltip(`
                            <div class="text-center">
                                <strong>${feature.properties.name}</strong><br>
                                <span class="badge ${count > 0 ? 'bg-danger' : 'bg-success'}">${count} Kasus</span>
                            </div>
                        `, { sticky: true, direction: 'top', className: 'custom-tooltip' });

                        // Hover Highlight
                        layer.on({
                            mouseover: (e) => {
                                const l = e.target;
                                l.setStyle({ weight: 3, color: '#666', fillOpacity: 0.8, dashArray: '' });
                                l.bringToFront();
                            },
                            mouseout: (e) => {
                                // Reset ke style awal (warna choropleth)
                                geoLayer.resetStyle(e.target); 
                            },
                            click: (e) => {
                                this.map.fitBounds(e.target.getBounds());
                            }
                        });
                    }
                });

                this.choroplethLayer.addLayer(geoLayer);
            },

            // --- HELPERS ---

            calculateRadius(val, max) {
                if(max <= 0) return 5;
                // Skala akar kuadrat agar visualisasi berat tidak terlalu ekstrim
                return 5 + (Math.sqrt(val) / Math.sqrt(max) * 25); 
            },

            getColor(val, max) {
                // JIKA KASUS 0 = HIJAU (Aman)
                if (val === 0) return '#22c55e'; // Hijau Muda

                // Logic Gradasi (Hijau -> Kuning -> Merah)
                const ratio = val / max;
                if (ratio > 0.66) return '#dc3545'; // Merah Tua (Tinggi)
                if (ratio > 0.33) return '#ffc107'; // Kuning (Sedang)
                return '#22c55e'; // Hijau (Rendah)
            },

            formatNumber(num) {
                return parseFloat(num).toLocaleString('id-ID');
            },

            // --- FETCH MODAL DETAIL ---
            async fetchDetail(id) {
                const modalBody = document.getElementById('modal-content-body');
                
                // Loading State
                modalBody.innerHTML = `
                    <div class="d-flex flex-column justify-content-center align-items-center py-5" style="min-height: 300px;">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <div class="text-muted fw-bold">Sedang memuat data...</div>
                    </div>
                `;
                
                this.detailModal.show();

                try {
                    // Replace placeholder ID di URL
                    const url = this.detailRouteUrl.replace(':id', id);
                    
                    const res = await fetch(url);
                    if (!res.ok) throw new Error("Gagal mengambil data");
                    
                    const html = await res.text();
                    modalBody.innerHTML = html;

                } catch (e) {
                    console.error(e);
                    modalBody.innerHTML = `
                        <div class="text-center py-5 text-danger">
                            <i class="bi bi-exclamation-triangle display-1 mb-3"></i><br>
                            <h5 class="fw-bold">Terjadi Kesalahan</h5>
                            <p class="text-muted">Gagal memuat detail kasus. Silakan coba lagi.</p>
                        </div>
                    `;
                }
            }
        }));
    });
</script>
@endpush