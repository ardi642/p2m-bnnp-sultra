@extends('admin')

@section('content')
<main class="admin-main">
    <div class="container-fluid p-4 p-lg-5">
        
        {{-- HEADER TITLE --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Input Register Barang Bukti</h1>
                <p class="text-secondary small mb-0">Pencatatan Barang Bukti Hasil Tangkap / Temuan</p>
            </div>
            <a href="{{ route('berantas.register-barang-bukti.index') }}" 
               class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        {{-- ALERT ERROR --}}
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
                    <div>
                        <strong>Periksa Kembali Inputan!</strong><br>
                        <small>File yang sudah diupload tersimpan sementara di server.</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- FORM UTAMA --}}
        {{-- Hapus @submit.prevent, biarkan native submit agar bisa di-intercept FilePondManager --}}
        <form action="{{ route('berantas.register-barang-bukti.store') }}" 
              method="POST" 
              enctype="multipart/form-data" 
              id="form-register-bb">
            @csrf
            
            {{-- CARD 1: INFORMASI UMUM --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-primary">
                        <i class="bi bi-info-circle me-2"></i>Informasi Umum
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        
                        {{-- Satuan Kerja (Admin Only) --}}
                        @if(Auth::user()->isAdmin())
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">
                                Satuan Kerja <span class="text-danger">*</span>
                            </label>
                            <select name="satuan_kerja_id" class="form-select py-2">
                                <option value="" selected disabled>Pilih Satuan Kerja...</option>
                                @foreach($satuanKerjas ?? [] as $satker)
                                    <option value="{{ $satker->id }}" @selected(old('satuan_kerja_id') == $satker->id)>
                                        {{ $satker->satuan_kerja }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        
                        {{-- Tanggal Perolehan --}}
                        <div class="col-md-{{ Auth::user()->isAdmin() ? '6' : '12' }}">
                            <label class="form-label fw-semibold small text-secondary">
                                Tanggal Perolehan <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="tanggal_perolehan" value="{{ old('tanggal_perolehan') }}" class="form-control py-2 @error('tanggal_perolehan') is-invalid @enderror">
                            @error('tanggal_perolehan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        
                        {{-- MAPS & KOORDINAT --}}
                        <div class="col-12" x-data="locationPicker">
                            <label class="form-label fw-semibold small text-secondary">Titik Koordinat</label>
                            <div class="row g-2 mb-2">
                                <div class="col-12 col-md-5">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-secondary small">Lat</span>
                                        <input type="text" name="latitude" x-model="lat" class="form-control @error('latitude') is-invalid @enderror" placeholder="-4.xxxx" @input="updateMarker">
                                    </div>
                                    @error('latitude') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12 col-md-5">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-secondary small">Lng</span>
                                        <input type="text" name="longitude" x-model="lng" class="form-control @error('longitude') is-invalid @enderror" placeholder="122.xxxx" @input="updateMarker">
                                    </div>
                                    @error('longitude') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12 col-md-2">
                                    <button type="button" class="btn btn-outline-primary w-100" @click="getGPS" :disabled="isLoading" title="Ambil Lokasi Saat Ini">
                                        <span x-show="isLoading" style="display: none;">
                                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                        </span>
                                        <span x-show="!isLoading">
                                            <i class="bi bi-geo-alt-fill"></i> <span class="d-none d-md-inline ms-1">GPS</span>
                                        </span>
                                    </button>
                                </div>
                            </div>
                            <div wire:ignore id="map" style="height: 60vh; border-radius: 6px; border: 1px solid #dee2e6; z-index: 1;"></div>
                        </div>
                        
                        {{-- Lokasi Perolehan --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">Lokasi Perolehan (TKP)</label>
                            <textarea name="lokasi_perolehan" class="form-control py-2" rows="3" placeholder="Masukkan alamat lengkap TKP...">{{ old('lokasi_perolehan') }}</textarea>
                            @error('lokasi_perolehan') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- CARD 2: DAFTAR BARANG BUKTI --}}
            {{-- x-data dipindah ke wrapper ini agar tidak mengganggu form submit --}}
            <div class="card shadow-sm border-0 mb-4" x-data="registerBBForm">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-bold text-primary">
                        <i class="bi bi-box-seam me-2"></i>Daftar Barang Bukti
                    </h5>
                    <button type="button" class="btn btn-dark btn-sm px-3 shadow-sm" @click="addItem">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Item
                    </button>
                </div>
                <div class="card-body p-4">
                    @error('items') 
                        <div class="alert alert-danger small py-2 mb-3">
                            <i class="bi bi-exclamation-circle me-1"></i> {{ $message }}
                        </div> 
                    @enderror

                    <div class="d-flex flex-column">
                        <template x-for="(item, i) in items" :key="item.temp_id">
                            <div class="position-relative" :class="i < items.length - 1 ? 'pb-4 mb-4' : ''">
                                
                                {{-- HEADER & TOMBOL HAPUS --}}
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold text-primary mb-0 text-uppercase small">
                                        <span class="badge bg-primary me-2 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 22px; height: 22px; padding: 0;">
                                            <span x-text="i + 1" style="font-size: 11px;"></span>
                                        </span>
                                        Item Barang Bukti
                                    </h6>
                                    
                                    <button type="button" class="btn btn-sm text-danger fw-bold border-0 px-0" 
                                            @click="removeItem(i)" 
                                            title="Hapus Baris"
                                            x-show="items.length > 1"
                                            style="font-size: 0.85rem;">
                                        <i class="bi bi-trash me-1"></i>Hapus
                                    </button>
                                </div>

                                {{-- GRID INPUTAN --}}
                                <div class="row g-3">
                                    
                                    {{-- 1. SUMBER --}}
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <label class="form-label small fw-bold text-secondary mb-1">Sumber Perolehan</label>
                                        <select :name="`items[${i}][sumber_perolehan]`" x-model="item.sumber_perolehan" class="form-select">
                                            <option value="Hasil Tangkap">Hasil Tangkap</option>
                                            <option value="Temuan">Temuan</option>
                                        </select>
                                    </div>

                                    {{-- 2. KATEGORI --}}
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <label class="form-label small fw-bold text-secondary mb-1">Kategori Barang</label>
                                        <select :name="`items[${i}][kategori]`" x-model="item.kategori" class="form-select" @change="resetItem(item)">
                                            <option value="Narkotika">Narkotika</option>
                                            <option value="Non-Narkotika">Non-Narkotika</option>
                                        </select>
                                    </div>

                                    {{-- 3. NAMA BARANG --}}
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <label class="form-label small fw-bold text-secondary mb-1">Nama Barang Bukti <span class="text-danger">*</span></label>
                                        <div x-show="item.kategori === 'Narkotika'">
                                            <div wire:ignore :class="{'border border-danger rounded': hasError('items', i, 'narkotika_id')}">
                                                <select :id="'select_narkotika_' + item.temp_id" :name="`items[${i}][narkotika_id]`" x-init="initTS($el, item)"></select>
                                            </div>
                                            <div class="text-danger small mt-1" x-show="hasError('items', i, 'narkotika_id')" x-text="getErrorMessage('items', i, 'narkotika_id')"></div>
                                        </div>
                                        <div x-show="item.kategori === 'Non-Narkotika'" style="display: none;">
                                            <input type="text" :name="`items[${i}][nama_barang_non_narkotika]`" x-model="item.nama_barang_non_narkotika" class="form-control" :class="{'is-invalid': hasError('items', i, 'nama_barang_non_narkotika')}" placeholder="Contoh: Handphone...">
                                            <div class="invalid-feedback" x-text="getErrorMessage('items', i, 'nama_barang_non_narkotika')"></div>
                                        </div>
                                    </div>

                                    {{-- 4. MODUS PENGIRIMAN --}}
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <label class="form-label small fw-bold text-secondary mb-1">Modus Pengiriman</label>
                                        <textarea 
                                            :name="`items[${i}][modus_pengiriman]`" 
                                            x-model="item.modus_pengiriman" 
                                            class="form-control" 
                                            :class="{'is-invalid': hasError('items', i, 'modus_pengiriman')}"
                                            placeholder="Masukkan modus pengiriman"
                                            rows="2"
                                            style="resize: none; overflow-y: hidden;"
                                            x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' } }"
                                            x-init="$el.value ? resize() : null"
                                            @input="resize()"
                                        ></textarea>
                                        <div class="invalid-feedback" x-text="getErrorMessage('items', i, 'modus_pengiriman')"></div>
                                    </div>

                                    {{-- 5. JUMLAH --}}
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <label class="form-label small fw-bold text-secondary mb-1">Jumlah / Berat Netto <span class="text-danger">*</span></label>
                                        <input type="number" step="0.0001" :name="`items[${i}][jumlah]`" x-model="item.jumlah" class="form-control" :class="{'is-invalid': hasError('items', i, 'jumlah')}" placeholder="Masukkan nilai">
                                        <div class="invalid-feedback" x-text="getErrorMessage('items', i, 'jumlah')"></div>
                                    </div>

                                    {{-- 6. SATUAN --}}
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <label class="form-label small fw-bold text-secondary mb-1">Satuan <span class="text-danger">*</span></label>
                                        <div x-show="item.kategori === 'Narkotika'">
                                            <select :name="`items[${i}][satuan_narkotika]`" x-model="item.satuan_narkotika" class="form-select" :class="{'is-invalid': hasError('items', i, 'satuan_narkotika')}">
                                                <option value="Gram">Gram</option>
                                                <option value="Kg">Kg</option>
                                                <option value="Ton">Ton</option>
                                            </select>
                                            <div class="invalid-feedback" x-text="getErrorMessage('items', i, 'satuan_narkotika')"></div>
                                        </div>
                                        <div x-show="item.kategori === 'Non-Narkotika'" style="display: none;">
                                            <input type="text" :name="`items[${i}][satuan_non_narkotika]`" x-model="item.satuan_non_narkotika" class="form-control" :class="{'is-invalid': hasError('items', i, 'satuan_non_narkotika')}" placeholder="Pcs/Unit/Buah">
                                            <div class="invalid-feedback" x-text="getErrorMessage('items', i, 'satuan_non_narkotika')"></div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- 4. LAMPIRAN --}}
            <div class="card shadow-sm border-0 mb-5">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-primary"><i class="bi bi-paperclip me-2"></i>Dokumentasi & Lampiran</h5>
                </div>
                <div class="card-body p-4">
                    <div class="bg-body-tertiary p-4 rounded-3 border border-dashed">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold h6 mb-3 text-dark d-block border-bottom pb-2">
                                    <i class="bi bi-cloud-arrow-up me-2"></i>Upload File & Link (Opsional)
                                </label>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <div class="bg-white p-3 rounded border h-100 d-flex flex-column shadow-sm">
                                            <label class="form-label fw-bold small text-primary mb-1"><i class="bi bi-folder2-open me-2"></i>Dokumentasi</label>
                                            <div class="mb-3">
                                                <p class="text-muted small mb-2" style="font-size: 0.75rem">Upload dokumentasi. Maksimal 10MB.</p>
                                                {{-- ID ini penting untuk selector di JS --}}
                                                <input type="file" id="fp-dokumentasi" name="dokumentasi[]" multiple>
                                                @error('dokumentasi') <div class="text-danger small">{{ $message }}</div> @enderror
                                            </div>
                                            <hr class="border-secondary-subtle my-3">
                                            <div x-data="linkManager( {{ \Illuminate\Support\Js::from(array_values(old('dokumentasi_links', []))) }} )">
                                                <label class="form-label fw-bold small text-primary mb-2"><i class="bi bi-link-45deg me-1"></i>Atau Tautkan Link</label>
                                                <template x-for="(link, index) in links" :key="index">
                                                    <div class="input-group mb-2 input-group-sm">
                                                        <input type="text" class="form-control" :name="`dokumentasi_links[${index}][nama]`" placeholder="Nama Tautan / File" x-model="link.nama" required>
                                                        <input type="url" class="form-control" :name="`dokumentasi_links[${index}][url]`" placeholder="https://" x-model="link.url" required>
                                                        <button type="button" class="btn btn-outline-danger" @click="removeLink(index)"><i class="bi bi-x"></i></button>
                                                    </div>
                                                </template>
                                                @error('dokumentasi_links.*') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                                                <button type="button" class="btn btn-xs btn-outline-primary dashed-border w-100 mt-1" @click="addLink()"><i class="bi bi-plus-circle me-1"></i> Tambah Link</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="bg-white p-3 rounded border h-100 d-flex flex-column shadow-sm">
                                            <label class="form-label fw-bold small text-danger mb-1"><i class="bi bi-paperclip me-2"></i>Lampiran Pendukung</label>
                                            <div class="mb-3">
                                                <p class="text-muted small mb-2" style="font-size: 0.75rem">Upload file pendukung. Maksimal 10MB.</p>
                                                {{-- ID ini penting untuk selector di JS --}}
                                                <input type="file" id="fp-lampiran" name="lampiran[]" multiple>
                                                @error('lampiran') <div class="text-danger small">{{ $message }}</div> @enderror
                                            </div>
                                            <hr class="border-secondary-subtle my-3">    
                                            <div x-data="linkManager( {{ \Illuminate\Support\Js::from(array_values(old('lampiran_links', []))) }} )">
                                                <label class="form-label fw-bold small text-danger mb-2"><i class="bi bi-link-45deg me-1"></i>Atau Tautkan Link</label>
                                                <template x-for="(link, index) in links" :key="index">
                                                    <div class="input-group mb-2 input-group-sm">
                                                        <input type="text" class="form-control" :name="`lampiran_links[${index}][nama]`" placeholder="Nama Tautan / File" x-model="link.nama" required>
                                                        <input type="url" class="form-control" :name="`lampiran_links[${index}][url]`" placeholder="https://" x-model="link.url" required>
                                                        <button type="button" class="btn btn-outline-danger" @click="removeLink(index)"><i class="bi bi-x"></i></button>
                                                    </div>
                                                </template>
                                                @error('lampiran_links.*') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                                                <button type="button" class="btn btn-xs btn-outline-danger dashed-border w-100 mt-1" @click="addLink()"><i class="bi bi-plus-circle me-1"></i> Tambah Link</button>
                                            </div>
                                        </div>
                                    </div>
                                </div> 
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- BUTTONS --}}
            <div class="d-flex flex-column-reverse flex-lg-row justify-content-end gap-2 pt-4 border-top mt-5 mb-5">
                <button type="button" onclick="window.location.reload()" class="btn btn-light border text-secondary px-4">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                </button>
                {{-- ID btn-submit digunakan oleh FilePondManager untuk disable otomatis saat upload --}}
                <button type="submit" id="btn-submit" class="btn btn-primary px-5 shadow-sm">
                    <i class="bi bi-save me-2"></i>Simpan Data
                </button>
            </div>
        </form>
    </div>
</main>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @vite(['resources/css/filepond.css', 'resources/js/filepond.js'])
    <style>
        .ts-control { border: 1px solid #dee2e6; padding: 0.4rem 0.75rem; border-radius: 0.375rem; box-shadow: none; font-size: 0.875rem; }
        .ts-control.focus { border-color: #86b7fe; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
        .ts-dropdown { z-index: 9999 !important; }
        .filepond--panel-root { background-color: #ffffff; border: 1px solid #dee2e6; }
        .border-dashed { border-style: dashed !important; border-width: 2px !important; }
    </style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script type="module">

    // --- LOGIKA FILEPOND (Menggunakan Manager) ---
    document.addEventListener("DOMContentLoaded", function() {
        const commonConfig = {
            uploadRoute: '{{ route('upload.temp') }}',
            revertRoute: '{{ route('revert.temp') }}',
            loadRoute:   '{{ route('load.temp') }}',
            csrfToken:   '{{ csrf_token() }}',
            submitBtnId: 'btn-submit' // ID tombol submit yang akan di-disable saat upload
        };

        if (window.FilePondManager) {
            // 1. Init Dokumentasi
            window.FilePondManager.create('#fp-dokumentasi', {
                ...commonConfig,
                maxSize: '10MB',
                existingFiles: @json(old('dokumentasi', [])),
            });

            // 2. Init Lampiran
            window.FilePondManager.create('#fp-lampiran', {
                ...commonConfig,
                maxSize: '10MB',
                existingFiles: @json(old('lampiran', [])),
            });

            // 3. Attach Form Submit Blocker
            // Ini akan mencegah form tersubmit jika masih ada file yang loading
            window.FilePondManager.attachFormSubmit('form-register-bb', 'btn-submit');
        }
    });

    document.addEventListener('alpine:init', () => {

        // --- MANAJEMEN LINK ---
        Alpine.data('linkManager', (initialData = []) => ({
            links: Array.isArray(initialData) ? initialData : [], 
            addLink() { this.links.push({ nama: '', url: '' }); },
            removeLink(index) { this.links.splice(index, 1); }
        }));

        // --- MAPS / LOCATION PICKER ---
        Alpine.data('locationPicker', () => ({
            lat: {{ old('latitude') ? old('latitude') : 'null' }},
            lng: {{ old('longitude') ? old('longitude') : 'null' }},
            map: null, 
            marker: null,
            isLoading: false, 
            
            init() { 
                this.lat = (this.lat !== null) ? parseFloat(this.lat) : null;
                this.lng = (this.lng !== null) ? parseFloat(this.lng) : null;

                let center = (this.lat && this.lng) ? [this.lat, this.lng] : [-2.5489, 118.0149];
                let zoom = (this.lat && this.lng) ? 16 : 5;

                this.map = L.map('map').setView(center, zoom);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: 'OSM'
                }).addTo(this.map);

                if(this.lat && this.lng) {
                    this.updateMarkerPosition(this.lat, this.lng, false);
                }

                // Saat klik peta, update nilai di form
                this.map.on('click', e => {
                    this.lat = parseFloat(e.latlng.lat).toFixed(7);
                    this.lng = parseFloat(e.latlng.lng).toFixed(7);
                    this.updateMarkerPosition(this.lat, this.lng, true);
                });
                
                setTimeout(() => { this.map.invalidateSize(); }, 200);
            },

            // Fungsi memindahkan pin tanpa merusak form input (Input Fighting)
            updateMarkerPosition(lat, lng, setView = true) {
                let pLat = parseFloat(lat);
                let pLng = parseFloat(lng);

                if (isNaN(pLat) || isNaN(pLng)) return;

                if(this.marker) {
                    this.marker.setLatLng([pLat, pLng]); 
                } else {
                    this.marker = L.marker([pLat, pLng]).addTo(this.map);
                }
                
                if(setView) {
                    this.map.setView([pLat, pLng], this.map.getZoom()); 
                }
            },

            // Dipanggil saat mengetik manual
            updateMarker() {
                if (this.lat && this.lng) {
                    this.updateMarkerPosition(this.lat, this.lng, true);
                }
            },

            getGPS() { 
                this.isLoading = true;
                if(navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        (p) => { 
                            this.lat = parseFloat(p.coords.latitude).toFixed(7);
                            this.lng = parseFloat(p.coords.longitude).toFixed(7);
                            this.updateMarkerPosition(this.lat, this.lng, false); 
                            this.map.setView([this.lat, this.lng], 16);
                            this.isLoading = false; 
                        },
                        (err) => { 
                            this.isLoading = false; 
                            let msg = 'Gagal mengambil lokasi.';
                            if(err.code === 1) msg = 'Izin lokasi ditolak browser.';
                            if(err.code === 2) msg = 'Sinyal GPS tidak ditemukan.';
                            if(err.code === 3) msg = 'Waktu habis (Timeout).';
                            Swal.fire('GPS Error', msg, 'error');
                        },
                        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 } 
                    );
                } else {
                    this.isLoading = false;
                    Swal.fire('Error', 'Browser tidak mendukung Geolocation.', 'error');
                }
            }
        }));

        // --- REPEATER BARANG BUKTI ---
        // Bersih: Hanya mengurus array items dan TomSelect
        Alpine.data('registerBBForm', () => ({
            items: [], 
            tsInstances: {}, 
            errors: @json($errors->toArray()),
            masterNarkotika: @json($masterNarkotika),

            init() {
                const oldItems = @json(old('items', []));
                
                if (oldItems.length > 0) {
                    oldItems.forEach(i => {
                        this.items.push({
                            temp_id: 'i_' + Math.random(),
                            sumber_perolehan: i.sumber_perolehan || 'Hasil Tangkap', 
                            kategori: i.kategori,
                            narkotika_id: i.narkotika_id,
                            nama_barang_non_narkotika: i.nama_barang_non_narkotika,
                            modus_pengiriman: i.modus_pengiriman || '', 
                            jumlah: i.jumlah,
                            satuan_narkotika: i.satuan_narkotika,
                            satuan_non_narkotika: i.satuan_non_narkotika
                        });
                    });
                } else { 
                    this.addItem(); 
                }
                
                // Init TomSelect setelah render
                this.$nextTick(() => { 
                    this.items.forEach(item => { 
                        if(item.kategori === 'Narkotika') 
                            this.initTS(document.getElementById('select_narkotika_'+item.temp_id), item); 
                    }); 
                });
            },

            addItem() { 
                this.items.push({ 
                    temp_id: 'i_' + Date.now(),
                    sumber_perolehan: 'Hasil Tangkap', 
                    kategori: 'Narkotika', 
                    narkotika_id: '', 
                    nama_barang_non_narkotika: '', 
                    modus_pengiriman: '', 
                    jumlah: '', 
                    satuan_narkotika: 'Gram', 
                    satuan_non_narkotika: '' 
                }); 
            },
            
            removeItem(i) { 
                if(this.items.length > 1) { 
                    const id = this.items[i].temp_id; 
                    if(this.tsInstances[id]) { 
                        this.tsInstances[id].destroy(); 
                        delete this.tsInstances[id]; 
                    } 
                    this.items.splice(i, 1); 
                } else { 
                    Swal.fire('Info', 'Minimal harus ada satu barang bukti.', 'info'); 
                }
            },
            
            resetItem(item) {
                item.narkotika_id = ''; 
                item.nama_barang_non_narkotika = ''; 
                item.satuan_narkotika = 'Gram'; 
                item.satuan_non_narkotika = '';
                
                if(this.tsInstances[item.temp_id]) this.tsInstances[item.temp_id].clear();
                
                this.$nextTick(() => { 
                    if(item.kategori === 'Narkotika') 
                        this.initTS(document.getElementById('select_narkotika_'+item.temp_id), item); 
                });
            },

            initTS(el, item) {
                if(!el) return;
                const ts = new TomSelect(el, { 
                    plugins: ['remove_button'], 
                    create: false, 
                    valueField: 'id', 
                    labelField: 'text', 
                    searchField: 'text', 
                    options: this.masterNarkotika.map(n => ({id: n.id, text: n.nama_narkotika})), 
                    placeholder: 'Pilih Narkotika...', 
                    dropdownParent: 'body' 
                });
                if(item.narkotika_id) ts.setValue(item.narkotika_id);
                ts.on('change', (val) => { item.narkotika_id = val; });
                this.tsInstances[item.temp_id] = ts;
            },

            hasError(field, index, key) { const k = `${field}.${index}.${key}`; return this.errors && this.errors[k]; },
            getErrorMessage(field, index, key) { const k = `${field}.${index}.${key}`; return this.errors[k] ? this.errors[k][0] : ''; }
        }));
    });
</script>
@endpush