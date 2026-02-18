@extends('admin')

@section('content')
<main class="admin-main" x-data="mapComponent">
    
    <div class="container-fluid p-0 position-relative" style="height: calc(100vh - 70px); overflow: hidden;">
        
        {{-- 1. TOMBOL TOGGLE SIDEBAR FILTER --}}
        <button 
            class="btn btn-light shadow position-absolute top-0 start-0 m-3 z-3 border fw-bold text-secondary" 
            type="button" 
            @click="showFilter = !showFilter"
        >
            <i class="bi bi-funnel-fill me-2 text-primary"></i>
            Filter Data
        </button>

        {{-- 2. SIDEBAR FILTER --}}
        <div 
            class="position-absolute top-0 start-0 h-100 bg-white shadow z-2 transition-all d-flex flex-column"
            style="width: 350px; max-width: 90vw;"
            :style="showFilter ? 'transform: translateX(0);' : 'transform: translateX(-100%);'" 
            x-transition
        >
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
                <h6 class="mb-0 fw-bold"><i class="bi bi-funnel me-2"></i>Filter Peta</h6>
                <button type="button" class="btn-close btn-sm" @click="showFilter = false"></button>
            </div>

            <div class="p-3 overflow-auto flex-grow-1">
                <form id="filter-form" @submit.prevent="loadMapData">
                    <div class="row g-3">
                        
                        {{-- Tahun & Bulan --}}
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Tahun</label>
                            <select name="tahun[]" class="form-select" multiple id="select-tahun" placeholder="Pilih Tahun...">
                                @foreach($years as $y) 
                                    <option value="{{ $y }}" {{ in_array($y, $selectedTahun) ? 'selected' : '' }}>{{ $y }}</option> 
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Bulan</label>
                            <select name="bulan[]" class="form-select" multiple id="select-bulan" placeholder="Pilih Bulan...">
                                @foreach(range(1, 12) as $m) 
                                    <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->locale('id')->translatedFormat('F') }}</option> 
                                @endforeach
                            </select>
                        </div>

                        {{-- Satker (Admin) --}}
                        @if(Auth::user()->hasRole('admin'))
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary">Satuan Kerja</label>
                            <div class="w-100">
                                <select name="satuan_kerja_id[]" class="form-select" multiple id="select-satker" placeholder="Semua Satuan Kerja">
                                    @foreach($satuanKerjas as $sat) 
                                        <option value="{{ $sat->id }}">{{ $sat->satuan_kerja }}</option> 
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @endif

                        {{-- Filter Narkotika --}}
                        <div class="col-12" x-data="{ logic: 'OR' }">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold text-secondary mb-0">Jenis Narkotika</label>
                                <div class="bg-light border rounded p-0 d-flex" style="cursor: pointer;" @click="logic = (logic === 'OR' ? 'AND' : 'OR')">
                                    <div class="px-2 py-0 small fw-bold" :class="logic === 'OR' ? 'bg-primary text-white rounded' : 'text-muted'">OR</div>
                                    <div class="px-2 py-0 small fw-bold" :class="logic === 'AND' ? 'bg-danger text-white rounded' : 'text-muted'">AND</div>
                                </div>
                                <input type="hidden" name="narkotika_logic" :value="logic">
                            </div>
                            <div class="w-100">
                                <select name="narkotika_ids[]" class="form-select" multiple id="select-narkotika" placeholder="Semua Jenis Narkotika">
                                    @foreach($masterNarkotika as $n) <option value="{{ $n->id }}">{{ $n->nama_narkotika }}</option> @endforeach
                                </select>
                            </div>
                            <div class="form-text text-danger fst-italic" style="font-size: 0.65rem;" x-show="logic === 'AND'">*Harus mengandung SEMUA jenis.</div>
                        </div>

                        {{-- Filter Pekerjaan --}}
                        <div class="col-12" x-data="{ logic: 'OR' }">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold text-secondary mb-0">Pekerjaan Tersangka</label>
                                <div class="bg-light border rounded p-0 d-flex" style="cursor: pointer;" @click="logic = (logic === 'OR' ? 'AND' : 'OR')">
                                    <div class="px-2 py-0 small fw-bold" :class="logic === 'OR' ? 'bg-primary text-white rounded' : 'text-muted'">OR</div>
                                    <div class="px-2 py-0 small fw-bold" :class="logic === 'AND' ? 'bg-danger text-white rounded' : 'text-muted'">AND</div>
                                </div>
                                <input type="hidden" name="pekerjaan_logic" :value="logic">
                            </div>
                            <div class="w-100">
                                <select name="pekerjaan[]" class="form-select" multiple id="select-pekerjaan" placeholder="Semua Pekerjaan">
                                    @foreach($listPekerjaan as $p) <option value="{{ $p }}">{{ $p }}</option> @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="col-12 mt-4">
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" :disabled="isLoading">
                                    <span x-show="isLoading" class="spinner-border spinner-border-sm me-2"></span> Terapkan Filter
                                </button>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        {{-- 3. KONTAINER PETA --}}
        <div id="map" class="w-100 h-100 z-0 bg-secondary-subtle"></div>

        {{-- 4. SMART SLIDER --}}
        <div class="position-absolute bottom-0 start-50 translate-middle-x mb-5 z-3 w-75 w-md-50" 
             x-show="showSlider" x-transition style="bottom: 80px !important;">
            <div class="card shadow border-0 bg-white bg-opacity-90 px-3 py-2 rounded-pill backdrop-blur">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-sm btn-primary rounded-circle shadow-sm d-flex align-items-center justify-content-center" 
                            @click="togglePlay" style="width: 36px; height: 36px; padding: 0;">
                        <i class="bi" :class="isPlaying ? 'bi-pause-fill fs-5' : 'bi-play-fill fs-5 ps-1'"></i>
                    </button>
                    <div class="flex-grow-1 position-relative pt-2">
                        <label class="form-label small fw-bold text-primary mb-0 position-absolute start-50 top-0 translate-middle-x mt-n3 bg-white px-2 rounded shadow-sm border" 
                               style="font-size: 0.7rem;" x-text="getSliderLabel()"></label>
                        <input type="range" class="form-range" min="0" max="12" step="1" 
                               x-model="sliderValue" @input="updateSliderMap" @mousedown="isPlaying = false">
                        <div class="d-flex justify-content-between text-muted fw-bold" style="font-size: 0.6rem; margin-top: -5px;">
                            <span>SEMUA</span><span>JAN</span><span>DES</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 5. LEGENDA & FOOTER STATISTIK --}}
        <div class="card position-absolute bottom-0 start-0 m-3 z-1 shadow border-0 bg-white bg-opacity-90 mb-5 pb-4" style="width: 260px;">
            <div class="card-body p-2 px-3 small">
                <h6 class="fw-bold mb-2 border-bottom pb-1">Legenda Peta</h6>
                <div class="d-flex align-items-center mb-2">
                    <div class="d-flex align-items-center justify-content-center me-2" style="width: 24px;">
                        <div style="width: 20px; height: 20px; background-color: rgba(110, 204, 57, 0.6); border-radius: 50%; text-align: center; font-size: 10px; font-weight: bold; color: #fff;">10</div>
                    </div>
                    <div class="lh-1"><div class="fw-bold text-dark">Cluster</div><div class="text-muted" style="font-size: 0.7rem;">Grup Titik</div></div>
                </div>
                
                {{-- LEGENDA TITIK --}}
                <div class="d-flex align-items-center mb-2">
                    <div class="d-flex align-items-center justify-content-center me-2" style="width: 24px;">
                        <div style="width: 14px; height: 14px; background: rgba(220, 53, 69, 0.6); border: 2px solid #dc3545; border-radius: 50%;"></div>
                    </div>
                    <div class="lh-1"><div class="fw-bold text-dark">Titik Kasus</div><div class="text-muted" style="font-size: 0.7rem;">Lokasi Spesifik</div></div>
                </div>
                
                {{-- LEGENDA GRADASI WILAYAH --}}
                <div class="mt-2 pt-2 border-top">
                    <div class="fw-bold text-dark mb-1">Kerawanan Wilayah</div>
                    
                    {{-- 0 Kasus --}}
                    <div class="d-flex align-items-center mb-1">
                        <span class="d-inline-block border border-light me-2" style="width: 30px; height: 12px; background: #2196F3; border-radius: 2px;"></span>
                        <span class="text-muted" style="font-size: 0.7rem;">0 Kasus (Aman)</span>
                    </div>

                    {{-- Gradient Bar --}}
                    {{-- KUNING -> MERAH --}}
                    <div class="d-flex rounded-1 overflow-hidden border border-light" style="height: 12px; background: linear-gradient(to right, #ffff00, #ff8800, #ff0000);"></div>
                    <div class="d-flex justify-content-between text-muted mt-1" style="font-size: 0.65rem;">
                        <span>Waspada</span>
                        <span>Bahaya</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="position-absolute bottom-0 start-50 translate-middle-x mb-3 z-3 w-auto" style="max-width: 90%;">
            <div class="card shadow-lg border-0 rounded-pill px-4 py-2 bg-white">
                <div class="d-flex align-items-center gap-4 text-nowrap">
                    <div class="text-center">
                        <div class="text-muted fw-bold" style="font-size: 0.65rem;">TOTAL KASUS</div>
                        <div class="fw-bolder fs-5 text-primary lh-1" x-text="stats.total_kasus">0</div>
                    </div>
                    <div class="vr opacity-25"></div>
                    <div class="text-center">
                        <div class="text-muted fw-bold" style="font-size: 0.65rem;">TERSANGKA</div>
                        <div class="fw-bolder fs-5 text-dark lh-1" x-text="stats.total_tersangka">0</div>
                    </div>
                    <div class="vr opacity-25"></div>
                    <div class="text-center">
                        <div class="text-muted fw-bold" style="font-size: 0.65rem;">BERAT BB (GRAM)</div>
                        <div class="fw-bolder fs-5 text-danger lh-1" x-text="formatNumber(stats.total_berat_gram)">0</div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- MODAL DETAIL --}}
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-info-circle me-2"></i>Detail Ungkap Kasus</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" id="modal-content-body"></div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL REGION DASHBOARD (UPDATED) --}}
    <div class="modal fade" id="regionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white py-2">
                    <h6 class="modal-title fw-bold" x-text="regionStats.name"></h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    
                    {{-- 1. RINGKASAN UTAMA (3 KARTU) --}}
                    <div class="row g-3 mb-3">
                        <div class="col-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body text-center p-2">
                                    <div class="text-muted small fw-bold" style="font-size: 0.65rem;">BERAT BB (NARKOTIKA)</div>
                                    <div class="fs-5 fw-bold text-danger" x-text="formatNumber(regionStats.total_berat) + ' g'"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body text-center p-2">
                                    <div class="text-muted small fw-bold" style="font-size: 0.65rem;">TOTAL TERSANGKA</div>
                                    <div class="fs-5 fw-bold text-dark" x-text="regionStats.total_tersangka + ' Org'"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body text-center p-2">
                                    <div class="text-muted small fw-bold" style="font-size: 0.65rem;">TOTAL ITEM NARKOTIKA</div>
                                    <div class="fs-5 fw-bold text-primary" x-text="regionStats.total_item + ' Item'"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. RINCIAN DATA --}}
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body text-center p-3 bg-primary bg-opacity-10">
                                    <div class="text-muted small fw-bold">TOTAL KASUS</div>
                                    <div class="display-6 fw-bold text-primary lh-1" x-text="regionStats.total_cases"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-white fw-bold small text-secondary">RINCIAN NARKOTIKA</div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush small" style="max-height: 250px; overflow-y: auto;">
                                        <template x-for="n in regionStats.narkotika" :key="n.name">
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <span x-text="n.name" class="fw-semibold"></span>
                                                <div class="text-end">
                                                    <div x-text="formatNumber(n.weight) + ' g'" class="fw-bold text-danger"></div>
                                                    <div x-text="n.percent + '%'" class="text-muted" style="font-size: 0.65rem;"></div>
                                                </div>
                                            </div>
                                        </template>
                                        <div x-show="regionStats.narkotika.length === 0" class="p-3 text-center text-muted fst-italic">Tidak ada data.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-white fw-bold small text-secondary">PROFIL TERSANGKA</div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush small" style="max-height: 250px; overflow-y: auto;">
                                        <template x-for="p in regionStats.pekerjaan" :key="p.name">
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <span x-text="p.name"></span>
                                                <div class="text-end">
                                                    <span class="badge bg-secondary rounded-pill" x-text="p.count + ' Org'"></span>
                                                    <span class="d-block text-muted" style="font-size: 0.65rem;" x-text="p.percent + '%'"></span>
                                                </div>
                                            </div>
                                        </template>
                                        <div x-show="regionStats.pekerjaan.length === 0" class="p-3 text-center text-muted fst-italic">Tidak ada data.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />

<style>
    .admin-main { padding-bottom: 0 !important; } 
    .modal-backdrop { z-index: 1050; } .modal { z-index: 1060; }
    .backdrop-blur { backdrop-filter: blur(5px); }
    .custom-tooltip { background: rgba(255, 255, 255, 0.95); border: 1px solid #666; font-size: 0.8rem; border-radius: 4px; padding: 4px 8px; }
    
    .ts-wrapper { width: 100% !important; max-width: 100% !important; }
    .ts-control { display: flex !important; flex-wrap: wrap !important; height: auto !important; min-height: 38px; overflow: visible !important; padding: 4px 8px !important; }
    .ts-control > .item { display: inline-flex !important; align-items: center; width: auto !important; max-width: 270px !important; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; margin: 3px 4px 3px 0 !important; }
    .ts-control > input { flex: 1 1 auto !important; min-width: 4rem !important; margin-top: 3px !important; }
    .ts-dropdown { z-index: 2000 !important; }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.heat/0.2.0/leaflet-heat.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('mapComponent', () => ({
            showFilter: true, isLoading: false,
            map: null, 
            
            // --- LAYER DEFINITIONS ---
            markerCluster: null,    
            weightedLayer: null,    
            uniformLayer: null,
            noMarkerLayer: null,    // Layer Kosong
            choroplethLayer: null, 
            heatLayer: null,
            
            geoJsonData: null, features: [], 
            stats: { total_kasus: 0, total_tersangka: 0, total_berat_gram: 0 },
            
            // Dashboard Logic (UPDATED)
            regionModal: null, 
            regionStats: { 
                name: '', 
                total_cases: 0, 
                total_berat: 0, 
                total_tersangka: 0, 
                total_item: 0,
                narkotika: [], 
                pekerjaan: [] 
            },
            
            showSlider: false, sliderValue: 0, isPlaying: false, playInterval: null,
            detailModal: null, detailRouteUrl: "{{ route('berantas.peta-ungkap-kasus.show', ':id') }}",
            
            init() {
                const config = { plugins: ['remove_button', 'clear_button', 'dropdown_input'], maxOptions: null, create: false, persist: false };
                ['select-tahun','select-bulan','select-satker','select-narkotika','select-pekerjaan'].forEach(id => {
                    const el = document.getElementById(id);
                    if(el) { if(el.tomselect) el.tomselect.destroy(); new TomSelect('#'+id, config); }
                });
                this.detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
                this.regionModal = new bootstrap.Modal(document.getElementById('regionModal'));
                this.initMap();
            },

            initMap() {
                this.map = L.map('map', { zoomControl: false }).setView([-4.10, 122.10], 7);
                L.control.zoom({ position: 'topright' }).addTo(this.map);
                
                L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { attribution: '© CARTO', maxZoom: 19 }).addTo(this.map);

                // Setup Panes
                this.map.createPane('choroplethPane');
                this.map.getPane('choroplethPane').style.zIndex = 250; 
                this.map.createPane('heatmapPane'); 
                this.map.getPane('heatmapPane').style.zIndex = 500; 
                this.map.getPane('heatmapPane').style.pointerEvents = 'none'; 
                
                // --- SETUP LAYERS ---
                this.markerCluster = L.markerClusterGroup({ showCoverageOnHover: false, zoomToBoundsOnClick: true, spiderfyOnMaxZoom: true });
                this.weightedLayer = L.layerGroup();
                this.uniformLayer = L.layerGroup();
                this.noMarkerLayer = L.layerGroup(); 
                this.heatLayer = L.layerGroup(); 
                this.choroplethLayer = L.layerGroup(); 

                // --- DEFAULT ACTIVE LAYERS ---
                this.map.addLayer(this.uniformLayer);
                this.map.addLayer(this.choroplethLayer);

                // --- KONFIGURASI CONTROL LAYER ---
                // Base Layers (Radio Button)
                const radioLayers = {
                    "Titik (Biasa)": this.uniformLayer,
                    "Titik (Bobot BB)": this.weightedLayer,
                    "Tanpa Titik": this.noMarkerLayer
                };

                // Overlays (Checkbox)
                const overlayLayers = {
                    "Titik Group (Cluster)": this.markerCluster,
                    "Heatmap": this.heatLayer,
                    "Peta Kerawanan": this.choroplethLayer
                };

                L.control.layers(radioLayers, overlayLayers, { position: 'topright' }).addTo(this.map);

                fetch("{{ asset('maps/sultra_kabupaten.geojson') }}").then(r => r.json()).then(data => {
                    this.geoJsonData = data; this.loadMapData();
                });
            },

            async loadMapData() {
                this.isLoading = true;
                const formData = new FormData(document.getElementById('filter-form'));
                const params = new URLSearchParams(formData).toString();
                const m = formData.getAll('bulan[]');
                this.showSlider = (m.length !== 1);
                this.sliderValue = 0; this.stopPlay();

                try {
                    const res = await fetch(`{{ route('berantas.peta-ungkap-kasus.data') }}?${params}`);
                    const data = await res.json();
                    this.stats = data.stats;
                    this.features = data.features;
                    this.renderMap(this.features);
                    if(window.innerWidth < 768) this.showFilter = false;
                } catch (e) { console.error(e); Swal.fire('Error', 'Gagal memuat data.', 'error'); } 
                finally { this.isLoading = false; }
            },

            renderMap(featuresToRender) {
                // Clear semua layer
                this.weightedLayer.clearLayers();
                this.uniformLayer.clearLayers();
                this.noMarkerLayer.clearLayers(); 
                this.markerCluster.clearLayers();
                this.choroplethLayer.clearLayers();
                this.heatLayer.clearLayers();

                let maxWeight = 0; const heatPoints = [];
                featuresToRender.forEach(f => { if(f.properties.berat_gram > maxWeight) maxWeight = f.properties.berat_gram; });

                featuresToRender.forEach(f => {
                    const props = f.properties;
                    const coords = f.geometry.coordinates; 
                    
                    // --- 1. MARKER BERBOBOT ---
                    const radiusW = this.calculateRadius(props.berat_gram, maxWeight);
                    const markerW = L.circleMarker([coords[1], coords[0]], {
                        radius: radiusW, fillColor: "#dc3545", color: "#fff", weight: 1, opacity: 1, fillOpacity: 0.8
                    });
                    this.bindPopupAction(markerW, props);
                    this.weightedLayer.addLayer(markerW);

                    // --- 2. MARKER BIASA ---
                    const markerU = L.circleMarker([coords[1], coords[0]], {
                        radius: 6, fillColor: "#dc3545", color: "#fff", weight: 1, opacity: 1, fillOpacity: 0.8
                    });
                    this.bindPopupAction(markerU, props);
                    this.uniformLayer.addLayer(markerU);
                    this.markerCluster.addLayer(markerU); 

                    // Heatmap Data
                    heatPoints.push([coords[1], coords[0], 0.2]);
                });

                if (heatPoints.length > 0) {
                    const heat = L.heatLayer(heatPoints, { radius: 30, blur: 25, gradient: { 0.2: 'blue', 0.5: 'lime', 0.8: 'orange', 1.0: 'red' }, pane: 'heatmapPane' });
                    this.heatLayer.addLayer(heat);
                }

                if(this.geoJsonData) this.processChoropleth(featuresToRender);
            },

            bindPopupAction(marker, props) {
                marker.bindPopup(`
                    <div class='text-center'>
                        <h6 class='fw-bold mb-1'>${props.tkp}</h6>
                        <div class='text-muted small mb-2'>${props.tanggal} - ${props.lkn}</div>
                        <div class='text-start border-top pt-2'>${props.popup_html}</div>
                        <div class='mt-2 pt-2 border-top'>
                            <button class='btn btn-xs btn-primary w-100 detail-btn' data-id='${props.id}'><i class='bi bi-search me-1'></i>Detail</button>
                        </div>
                    </div>
                `);
                marker.on('popupopen', () => {
                    const btn = document.querySelector(`.detail-btn[data-id='${props.id}']`);
                    if(btn) btn.onclick = () => this.fetchDetail(props.id);
                });
            },

            processChoropleth(casePoints) {
                const turfPoints = turf.featureCollection(casePoints);
                const regionCounts = {}; let maxCount = 0;
                this.geoJsonData.features.forEach(feature => {
                    const pts = turf.pointsWithinPolygon(turfPoints, feature);
                    const count = pts.features.length;
                    regionCounts[feature.properties.code] = count;
                    if(count > maxCount) maxCount = count;
                });

                const geoLayer = L.geoJson(this.geoJsonData, {
                    pane: 'choroplethPane',
                    style: (feature) => ({
                        fillColor: this.getColor(regionCounts[feature.properties.code] || 0, maxCount),
                        weight: 1, opacity: 1, color: 'white', dashArray: '3', fillOpacity: 0.7
                    }),
                    onEachFeature: (feature, layer) => {
                        const count = regionCounts[feature.properties.code] || 0;
                        layer.bindTooltip(`<div class="text-center"><strong>${feature.properties.name}</strong><br><span class="badge ${count > 0 ? 'bg-danger' : 'bg-success'}">${count} Kasus</span></div>`, { sticky: true, className: 'custom-tooltip' });
                        layer.on({
                            mouseover: (e) => e.target.setStyle({ weight: 3, color: '#666', fillOpacity: 0.9, dashArray: '' }),
                            mouseout: (e) => geoLayer.resetStyle(e.target),
                            click: (e) => {
                                if (count > 0) this.showRegionDashboard(feature, turfPoints);
                                else Swal.fire('Info', `Tidak ada kasus di ${feature.properties.name}`, 'info');
                            }
                        });
                    }
                });
                this.choroplethLayer.addLayer(geoLayer);
            },

            // LOGIKA WARNA KUNING -> MERAH
            getColor(val, max) {
                if (val === 0) return '#2196F3'; 
                if (max <= 0) return '#2196F3';
                let ratio = val / max;
                if (ratio > 1) ratio = 1;
                // Hue 60 (Kuning) -> Hue 0 (Merah)
                const hue = ((1 - ratio) * 60).toString(10);
                return `hsl(${hue}, 100%, 50%)`;
            },

            showRegionDashboard(feature, turfPoints) {
                const pts = turf.pointsWithinPolygon(turfPoints, feature);
                let totalNarko = {}, totalBeratAll = 0, totalPekerjaan = {};
                let totalTskAll = 0;
                let totalItemAll = 0;
                
                pts.features.forEach(f => {
                    const props = f.properties;
                    
                    // Aggregation Data dari Properties
                    totalBeratAll += parseFloat(props.berat_gram || 0);
                    totalItemAll += parseInt(props.jml_item_narko || 0);
                    totalTskAll += parseInt(props.raw_pekerjaan.length || 0);

                    for (const [nama, berat] of Object.entries(props.raw_narkoba || {})) {
                        if (!totalNarko[nama]) totalNarko[nama] = 0;
                        totalNarko[nama] += berat; 
                    }
                    (props.raw_pekerjaan || []).forEach(p => {
                        if (!totalPekerjaan[p]) totalPekerjaan[p] = 0;
                        totalPekerjaan[p]++;
                    });
                });

                const narkoArray = Object.entries(totalNarko).map(([name, weight]) => ({
                    name, weight, percent: totalBeratAll > 0 ? ((weight/totalBeratAll)*100).toFixed(1) : 0
                })).sort((a,b) => b.weight - a.weight);

                const totalTskReal = Object.values(totalPekerjaan).reduce((a, b) => a + b, 0);

                const pekArray = Object.entries(totalPekerjaan).map(([name, count]) => ({
                    name, count, percent: totalTskReal > 0 ? ((count/totalTskReal)*100).toFixed(1) : 0
                })).sort((a,b) => b.count - a.count);

                this.regionStats = {
                    name: feature.properties.name,
                    total_cases: pts.features.length,
                    total_berat: totalBeratAll,
                    total_tersangka: totalTskAll,
                    total_item: totalItemAll,
                    narkotika: narkoArray,
                    pekerjaan: pekArray
                };
                this.regionModal.show();
            },

            updateSliderMap() {
                const val = parseInt(this.sliderValue);
                if (val === 0) this.renderMap(this.features);
                else {
                    const filtered = this.features.filter(f => f.properties.bulan_angka === val);
                    this.renderMap(filtered);
                }
            },
            togglePlay() {
                if (this.isPlaying) this.stopPlay();
                else {
                    this.isPlaying = true;
                    if(this.sliderValue >= 12) this.sliderValue = 0;
                    this.playInterval = setInterval(() => {
                        this.sliderValue++;
                        if(this.sliderValue > 12) this.sliderValue = 0;
                        this.updateSliderMap();
                    }, 1500);
                }
            },
            stopPlay() { this.isPlaying = false; clearInterval(this.playInterval); },
            getSliderLabel() { const m = ["SEMUA DATA", "JANUARI", "FEBRUARI", "MARET", "APRIL", "MEI", "JUNI", "JULI", "AGUSTUS", "SEPTEMBER", "OKTOBER", "NOVEMBER", "DESEMBER"]; return m[this.sliderValue]; },
            calculateRadius(val, max) { return max <= 0 ? 6 : 6 + (Math.sqrt(val) / Math.sqrt(max) * 20); },
            formatNumber(num) { return parseFloat(num).toLocaleString('id-ID'); },
            async fetchDetail(id) {
                const modal = document.getElementById('modal-content-body');
                modal.innerHTML = `<div class="py-5 text-center"><div class="spinner-border text-primary"></div><div class="mt-2 text-muted">Memuat...</div></div>`;
                this.detailModal.show();
                try {
                    const res = await fetch(this.detailRouteUrl.replace(':id', id));
                    if(!res.ok) throw new Error;
                    modal.innerHTML = await res.text();
                } catch(e) { modal.innerHTML = `<div class="py-5 text-center text-danger">Gagal memuat data.</div>`; }
            }
        }));
    });
</script>
@endpush