@extends('admin')

@section('content')
<main class="admin-main bg-light" x-data="dashboardP2M()" x-init="init()" style="min-height: 100vh;">
    <div class="container-fluid p-4">

        {{-- HEADER & IDENTITAS --}}
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3">
            <div>
                <h1 class="h3 mb-2 fw-bold text-dark">Dashboard Kinerja P2M</h1>
                <div class="mt-2">
                    @if(auth()->user()->role === 'admin')
                        <div class="d-flex align-items-center bg-light rounded-pill px-3 py-2 shadow-sm w-auto" style="max-width: max-content;">
                            <i class="bi bi-building-fill text-muted me-2"></i>
                            <select x-model="globalSatkerId" class="form-select border-0 bg-transparent text-dark fw-bold shadow-none p-0 pe-4 cursor-pointer" style="font-size: 1.1rem; outline: none; min-width: 300px;">
                                <option value="">Seluruh Satuan Kerja</option>
                                @foreach($satkers as $s) <option value="{{ $s->id }}">{{ $s->satuan_kerja }}</option> @endforeach
                            </select>
                        </div>
                    @else
                        <p class="text-muted mb-0 fs-5 d-flex align-items-center gap-2">
                            <i class="bi bi-building-fill text-primary"></i>
                            <span class="fw-bold text-dark">{{ auth()->user()->pegawai?->satuanKerja?->satuan_kerja ?? 'Satuan Kerja' }}</span>
                        </p>
                    @endif
                </div>
            </div>
            @include('dashboard.partials.nav')
        </div>

        {{-- FILTER TAHUN --}}
        <div class="d-flex justify-content-end mb-3">
            <div class="d-flex align-items-center bg-white p-2 rounded-3 shadow-sm border border-light gap-2">
                <span class="fw-bold text-muted small ms-2"><i class="bi bi-calendar-event me-2 text-primary"></i>Tahun Kinerja:</span>
                <select x-model="globalYear" class="form-select form-select-sm border-0 bg-light fw-bold text-dark w-auto shadow-none pe-4 me-1 cursor-pointer">
                    @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                </select>
            </div>
        </div>

        {{-- KARTU UTAMA --}}
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-white border-start border-4 border-primary rounded-3">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-4">
                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle mb-3">
                            <i class="bi bi-layers-fill fs-2"></i>
                        </div>
                        <h6 class="text-uppercase text-muted fw-bold mb-2">Total Kegiatan P2M</h6>
                        <h1 class="display-4 fw-bold text-dark mb-0" x-text="formatAngka(cards.kegiatan.total)">0</h1>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-white border-start border-4 border-success rounded-3 overflow-hidden">
                    <div class="card-header bg-white border-0 pt-4 pb-2 d-flex align-items-center gap-3">
                        <div class="bg-success bg-opacity-10 text-success p-2 rounded-3"><i class="bi bi-people-fill fs-4"></i></div>
                        <div>
                            <h6 class="text-muted fw-bold mb-0">Masyarakat Terlayani (Program Dasar)</h6>
                            <h3 class="fw-bold text-dark mb-0"><span x-text="formatAngka(cards.orang.total)">0</span> <span class="fs-6 text-muted fw-normal">Orang</span></h3>
                        </div>
                    </div>
                    <div class="card-body pt-0 overflow-auto" style="max-height: 220px;">
                        <template x-for="(item, label) in cards.orang.list">
                            <div x-show="(item || 0) > 0" class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                <span class="small fw-bold text-secondary" x-text="label"></span>
                                <span class="fw-bold text-success" x-text="formatAngka(item) + ' Orang'"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-white border-start border-4 border-warning rounded-3 overflow-hidden">
                    <div class="card-header bg-white border-0 pt-4 pb-2 d-flex align-items-center gap-3">
                        <div class="bg-warning bg-opacity-10 text-warning p-2 rounded-3"><i class="bi bi-megaphone-fill fs-4 text-dark"></i></div>
                        <div>
                            <h6 class="text-muted fw-bold mb-0">Total Publikasi Media</h6>
                            <h3 class="fw-bold text-dark mb-0"><span x-text="formatAngka(cards.media.total_freq)">0</span> <span class="fs-6 text-muted fw-normal">Publikasi</span></h3>
                        </div>
                    </div>
                    <div class="card-body pt-0 d-flex flex-column justify-content-center">
                        <div class="mb-3">
                            <span class="badge bg-light text-dark border px-3 py-2">
                                Total Durasi Tayang: <span class="fw-bold text-primary" x-text="formatAngka(cards.media.total_durasi) + ' Hari'"></span>
                            </span>
                        </div>
                        <template x-for="(item, label) in cards.media.list">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                <span class="small fw-bold text-secondary" x-text="label"></span>
                                <div class="text-end fw-bold text-dark">
                                    <span x-text="item.freq + ' Kali Publikasi'"></span> 
                                    <small class="text-muted fw-normal ms-1" x-text="'(' + item.durasi + ' Hari)'"></small>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-white border-start border-4 border-info rounded-3 overflow-hidden">
                    <div class="card-header bg-white border-0 pt-4 pb-2 d-flex align-items-center gap-3">
                        <div class="bg-info bg-opacity-10 text-info p-2 rounded-3"><i class="bi bi-geo-alt-fill fs-4 text-dark"></i></div>
                        <div>
                            <h6 class="text-muted fw-bold mb-0">Kawasan Binaan</h6>
                            <h3 class="fw-bold text-dark mb-0"><span x-text="formatAngka(cards.wilayah.total)">0</span> <span class="fs-6 text-muted fw-normal">Kawasan</span></h3>
                        </div>
                    </div>
                    <div class="card-body pt-0 d-flex flex-column justify-content-center">
                        <template x-for="(val, label) in cards.wilayah.list">
                            <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                                <span class="small fw-bold text-secondary" x-text="label"></span>
                                <span class="h5 fw-bold text-info mb-0" x-text="formatAngka(val)"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- KARTU RINCIAN (MENAMPILKAN KEGIATAN & PESERTA) --}}
        <div class="row g-3 mb-5">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-white border-top border-4 border-primary rounded-3">
                    <div class="card-header bg-white border-0 pt-4 pb-3 d-flex align-items-center gap-3">
                        <div class="bg-primary text-white p-2 rounded-3 shadow-sm"><i class="bi bi-diagram-3-fill fs-5"></i></div>
                        <h5 class="fw-bold text-dark mb-0">Peran Serta Masyarakat</h5>
                    </div>
                    <div class="card-body pt-0 overflow-auto" style="max-height: 400px;">
                        <template x-for="(cat, catName) in cards.psm_card" :key="catName">
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center border-bottom border-primary border-opacity-25 pb-2 mb-2">
                                    <span class="fw-bold text-primary" x-text="catName"></span>
                                    <span class="badge bg-primary rounded-pill px-3 py-2" x-text="formatAngka(cat.kegiatan) + ' Kegiatan | ' + formatAngka(cat.peserta) + ' Orang'"></span>
                                </div>
                                <ul class="list-unstyled mb-0 ms-2 ps-3 border-start border-light border-2">
                                    <template x-for="(det, i) in cat.detail" :key="i">
                                        <li class="d-flex justify-content-between align-items-start mb-3 pb-2" :class="i !== cat.detail.length - 1 ? 'border-bottom border-light' : ''">
                                            <span class="small text-secondary" style="width: 55%; line-height: 1.3;" x-text="det.nama"></span>
                                            <div class="text-end" style="width: 45%">
                                                <span class="fw-bold text-dark small" x-text="formatAngka(det.kegiatan) + ' Kegiatan | ' + formatAngka(det.peserta) + ' Orang'"></span>
                                                <template x-if="det.is_tes_urine && det.positif > 0">
                                                    <div class="mt-1"><span class="badge bg-danger bg-opacity-10 text-danger border border-danger shadow-sm"><i class="bi bi-exclamation-circle-fill me-1"></i> Ditemukan <span x-text="det.positif"></span> Orang Positif</span></div>
                                                </template>
                                            </div>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </template>
                        <template x-if="Object.keys(cards.psm_card || {}).length === 0">
                            <div class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Belum ada data kegiatan.</div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-white border-top border-4 border-success rounded-3">
                    <div class="card-header bg-white border-0 pt-4 pb-3 d-flex align-items-center gap-3">
                        <div class="bg-success text-white p-2 rounded-3 shadow-sm"><i class="bi bi-briefcase-fill fs-5"></i></div>
                        <h5 class="fw-bold text-dark mb-0">Pemberdayaan Alternatif</h5>
                    </div>
                    <div class="card-body pt-0 overflow-auto" style="max-height: 400px;">
                        <template x-for="(sub, subName) in cards.pa_card" :key="subName">
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center border-bottom border-success border-opacity-25 pb-2 mb-2">
                                    <span class="fw-bold text-success" x-text="subName"></span>
                                    <span class="badge bg-success rounded-pill px-3 py-2" x-text="formatAngka(sub.kegiatan) + ' Kegiatan | ' + formatAngka(sub.peserta) + ' Orang'"></span>
                                </div>
                                <ul class="list-unstyled mb-0 ms-2 ps-3 border-start border-light border-2">
                                    <template x-for="(det, j) in sub.detail" :key="j">
                                        <li class="d-flex justify-content-between align-items-start mb-3 pb-2" :class="j !== sub.detail.length - 1 ? 'border-bottom border-light' : ''">
                                            <span class="small text-secondary" style="width: 55%; line-height: 1.3;" x-text="det.nama"></span>
                                            <span class="fw-bold text-dark small" style="width: 45%; text-align: right;" x-text="formatAngka(det.kegiatan) + ' Kegiatan | ' + formatAngka(det.peserta) + ' Orang'"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </template>
                        <template x-if="Object.keys(cards.pa_card || {}).length === 0">
                            <div class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Belum ada data kegiatan.</div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- RANKING CHART (HORIZONTAL BARS) --}}
        <div class="card border-0 shadow-sm mb-5 bg-white rounded-3">
            <div class="card-body p-4">
                <div x-ref="rankingChart" style="min-height: 400px;"></div>
            </div>
        </div>

        {{-- PUSAT ANALISIS KINERJA DETAIL --}}
        <div class="bg-white p-4 rounded-4 shadow-sm mb-4 border">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-4">
                <div>
                    <h5 class="m-0 fw-bold text-dark"><i class="bi bi-display me-2 text-primary"></i>Analisis Kinerja Detail</h5>
                    <small class="text-muted">Jelajahi proporsi dan rincian data per program secara interaktif.</small>
                </div>
                
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    {{-- Filter Pilihan Kegiatan --}}
                    <div class="d-flex align-items-center bg-light rounded-3 px-3 py-1 border border-secondary border-opacity-25">
                        <span class="text-muted small fw-bold me-2">Pilih Program:</span>
                        <select x-model="detailType" class="form-select border-0 bg-transparent fw-bold text-dark shadow-none cursor-pointer pe-4" style="min-width: 250px; outline: none;">
                            <option value="informasi_edukasi">Informasi & Edukasi</option>
                            <option value="media_elektronik">Media Elektronik</option>
                            <option value="media_non_elektronik">Media Non-Elektronik</option>
                            <option value="media_online">Media Online</option>
                            <option value="desa_bersinar">Desa/Kelurahan Bersinar</option>
                            <option value="asistensi">Asistensi Relawan</option>
                            <option value="pelatihan">Pelatihan Soft Skill</option>
                            <option value="keluarga">Ketahanan Keluarga</option>
                            <option value="ikan">Integrasi Kurikulum (IKAN)</option>
                            <option value="rts">Remaja Teman Sebaya</option>
                            <option value="peran_serta_masyarakat">Peran Serta Masyarakat</option>
                            <option value="pemberdayaan">Pemberdayaan Alternatif</option>
                        </select>
                    </div>

                    {{-- FILTER BULAN --}}
                    <div class="d-flex align-items-center bg-white rounded-3 px-3 py-1 border border-primary border-opacity-50">
                        <i class="bi bi-funnel-fill text-primary me-2"></i>
                        <select x-model="filterMonth" class="form-select border-0 bg-transparent fw-bold text-primary shadow-none cursor-pointer pe-4" style="min-width: 180px; outline: none;">
                            <option value="all">Akumulasi Tahunan</option>
                            <option value="1">Bulan Januari</option>
                            <option value="2">Bulan Februari</option>
                            <option value="3">Bulan Maret</option>
                            <option value="4">Bulan April</option>
                            <option value="5">Bulan Mei</option>
                            <option value="6">Bulan Juni</option>
                            <option value="7">Bulan Juli</option>
                            <option value="8">Bulan Agustus</option>
                            <option value="9">Bulan September</option>
                            <option value="10">Bulan Oktober</option>
                            <option value="11">Bulan November</option>
                            <option value="12">Bulan Desember</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            {{-- Grafik Tren --}}
            <div class="col-12">
                <div class="card border-0 shadow-sm h-100 bg-white rounded-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        <div>
                            <template x-if="isMultiSatker">
                                <div class="d-flex align-items-center bg-light rounded-pill px-3 py-1">
                                    <i class="bi bi-eye text-muted me-2"></i>
                                    <select x-model="adminTrendType" class="form-select form-select-sm border-0 bg-transparent text-dark fw-bold shadow-none cursor-pointer pe-4" style="min-width: 180px;">
                                        <option value="bar">Grafik Batang Biasa</option>
                                        <option value="heatmap">Grafik Matriks (Heatmap)</option>
                                    </select>
                                </div>
                            </template>
                        </div>
                        <div class="d-flex bg-light p-1 rounded-pill">
                            <button @click="tabTrend = 'kegiatan'" :class="tabTrend === 'kegiatan' ? 'btn-primary text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Total Kegiatan</button>
                            <button x-show="config.unit !== '-'" @click="tabTrend = 'peserta'" :class="tabTrend === 'peserta' ? 'btn-primary text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Jumlah Orang / Peserta</button>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4 pt-0"><div x-ref="chartTrend" style="min-height: 400px;"></div></div>
                </div>
            </div>
            
            {{-- Grafik Proporsi --}}
            <div class="col-12" x-show="config.has_anggaran || config.has_sasaran || config.has_kategori || config.has_sub_kegiatan">
                <div class="card border-0 shadow-sm h-100 bg-white rounded-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex justify-content-center align-items-center gap-3">
                        <div class="d-flex bg-light p-1 rounded-pill flex-wrap justify-content-center">
                            <button x-show="config.has_anggaran" @click="tabComp = 'anggaran'" :class="tabComp === 'anggaran' ? 'btn-success text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Berdasarkan Anggaran</button>
                            <button x-show="config.has_sasaran" @click="tabComp = 'sasaran'" :class="tabComp === 'sasaran' ? 'btn-warning text-dark shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Berdasarkan Sasaran Wilayah</button>
                            <button x-show="config.has_kategori" @click="tabComp = 'kategori'" :class="tabComp === 'kategori' ? 'btn-primary text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Berdasarkan Kategori Kegiatan</button>
                            <button x-show="config.has_sub_kegiatan" @click="tabComp = 'sub_kegiatan'" :class="tabComp === 'sub_kegiatan' ? 'btn-info text-dark shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Berdasarkan Sub Kegiatan</button>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4 pt-0"><div x-ref="chartComp" style="min-height: 450px;"></div></div>
                </div>
            </div>

            {{-- Grafik Rincian Spesifik dengan Drill-down --}}
            <div class="col-12" x-show="detailType === 'peran_serta_masyarakat' || detailType === 'pemberdayaan'">
                <div class="card border-0 shadow-sm bg-white rounded-4">
                    
                    <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        
                        {{-- JUDUL DI KIRI (Hitam Polos) --}}
                        <h6 class="fw-bold text-dark mb-0">
                            <span x-show="drilldownLevel === 1">Level 1: Kinerja <span x-text="detailTypeName"></span> (<span x-text="timeLabelText"></span>)</span>
                            <span x-show="drilldownLevel === 2">Level 2: Detail Kegiatan - <span x-text="selectedKategori"></span></span>
                        </h6>

                        {{-- SAKELAR METRIK DI KANAN --}}
                        <div class="d-flex align-items-center gap-2">
                            <button x-show="drilldownLevel === 2" @click="goBackLevel1()" class="btn btn-sm btn-outline-secondary rounded-pill fw-bold px-3">
                                <i class="bi bi-arrow-left me-1"></i> Kembali
                            </button>

                            <div class="bg-light p-1 rounded-pill d-flex">
                                <button @click="drilldownMetric = 'kegiatan'" :class="drilldownMetric === 'kegiatan' ? 'btn-success text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-3 border-0">Total Kegiatan</button>
                                <button @click="drilldownMetric = 'peserta'" :class="drilldownMetric === 'peserta' ? 'btn-primary text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-3 border-0">Peserta (Orang)</button>
                            </div>
                        </div>
                        
                    </div>

                    <div class="card-body px-4 pb-4 pt-2">
                        <p x-show="drilldownLevel === 1 && tableData.length > 0" class="text-muted small mb-4">
                            <i class="bi bi-info-circle me-1"></i> Tips Interaktif: Silakan klik salah satu batang grafik di bawah ini untuk melihat rincian kegiatannya (Level 2).
                        </p>

                        <div class="w-100" x-show="tableData.length > 0" style="overflow-x: auto;">
                            <div x-ref="chartDetail" style="min-width: 600px;"></div>
                        </div>
                        
                        <template x-if="tableData.length === 0">
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-3 text-light"></i>
                                Tidak ada data rincian kegiatan pada periode ini.
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('dashboardP2M', () => ({
            globalSatkerId: '', 
            globalYear: '{{ max($years) }}', 
            filterMonth: 'all',
            
            cards: { 
                kegiatan: { total: 0 }, 
                orang: { total: 0, list: {} }, 
                media: { total_freq: 0, total_durasi: 0, list: {} }, 
                wilayah: { total: 0, list: {} },
                psm_card: {},
                pa_card: {}
            },
            
            detailType: 'informasi_edukasi', 
            tabTrend: 'kegiatan', 
            tabComp: 'anggaran', 
            adminTrendType: 'bar', 
            
            // State untuk Drill-down
            drilldownLevel: 1,
            selectedKategori: '',
            drilldownMetric: 'kegiatan', // Default Sakelar adalah Total Kegiatan

            config: { 
                unit: 'Peserta', has_anggaran: true, has_sasaran: true, 
                has_kategori: false, has_sub_kegiatan: false 
            },
            isMultiSatker: false, 
            rawData: null, 
            tableData: [],
            chartInst: { rank: null, trend: null, comp: null, detail: null },
            
            getColors() { return ['#0d6efd', '#198754', '#fd7e14', '#6f42c1', '#0dcaf0', '#dc3545', '#20c997', '#ffc107', '#6c757d']; },

            init() {
                this.fetchGlobal(); 
                this.fetchDetail();
                
                this.$watch('globalSatkerId', () => { this.fetchGlobal(); this.fetchDetail(); });
                this.$watch('globalYear', () => { this.fetchGlobal(); this.fetchDetail(); });
                
                ['detailType', 'adminTrendType', 'filterMonth'].forEach(p => { 
                    this.$watch(p, () => this.fetchDetail()); 
                });
                
                this.$watch('tabTrend', () => this.renderTrend());
                this.$watch('tabComp', () => this.renderComp());
                this.$watch('drilldownMetric', () => this.renderDetailChart());
            },

            formatAngka(num) { return !num ? "0" : new Intl.NumberFormat('id-ID').format(num); },
            
            get detailTypeName() { 
                const types = { 
                    'informasi_edukasi': 'Informasi & Edukasi', 'media_elektronik': 'Media Elektronik', 
                    'media_non_elektronik': 'Media Non-Elektronik', 'media_online': 'Media Online', 
                    'desa_bersinar': 'Desa/Kel. Bersinar', 'asistensi': 'Asistensi Relawan', 
                    'pelatihan': 'Pelatihan Soft Skill', 'keluarga': 'Ketahanan Keluarga', 
                    'ikan': 'Integrasi Kurikulum (IKAN)', 'rts': 'Remaja Teman Sebaya',
                    'peran_serta_masyarakat': 'Peran Serta Masyarakat', 'pemberdayaan': 'Pemberdayaan Alternatif'
                }; 
                return types[this.detailType] || 'Kegiatan'; 
            },

            get timeLabelText() {
                if (this.filterMonth === 'all') {
                    return `Tahun ${this.globalYear}`;
                } else {
                    const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                    return `${months[this.filterMonth - 1]} ${this.globalYear}`;
                }
            },
            
            get dynamicTrendMetric() { 
                return this.tabTrend === 'kegiatan' ? 'Jumlah Kegiatan' : 'Jumlah Orang / Peserta'; 
            },
            
            get dynamicCompMetric() { 
                const maps = {
                    'anggaran': 'Anggaran Pelaksanaan', 'sasaran': 'Sasaran Wilayah', 
                    'kategori': 'Kategori Kegiatan', 'sub_kegiatan': 'Sub Kegiatan'
                };
                return 'Berdasarkan ' + (maps[this.tabComp] || 'Kategori'); 
            },

            goBackLevel1() {
                this.drilldownLevel = 1;
                this.selectedKategori = '';
                this.renderDetailChart();
            },

            fetchGlobal() { 
                fetch(`{{ route('dashboard.p2m.api.global') }}?year=${this.globalYear}&satker_id=${this.globalSatkerId}`)
                    .then(r => r.json()).then(res => { 
                        this.cards = res; 
                        this.renderRanking(res.ranking_chart); 
                    }); 
            },
            
            fetchDetail() { 
                fetch(`{{ route('dashboard.p2m.api.chart') }}?type=${this.detailType}&year=${this.globalYear}&month=${this.filterMonth}&satker_id=${this.globalSatkerId}`)
                    .then(r => r.json()).then(res => { 
                        this.rawData = res; 
                        this.isMultiSatker = res.is_multi_satker; 
                        this.config = res.config; 
                        this.tableData = res.detail_table || [];
                        
                        this.drilldownLevel = 1;
                        this.selectedKategori = '';

                        if (this.tabTrend === 'peserta' && this.config.unit === '-') this.tabTrend = 'kegiatan'; 
                        
                        const validComps = [];
                        if (this.config.has_anggaran) validComps.push('anggaran');
                        if (this.config.has_sasaran) validComps.push('sasaran');
                        if (this.config.has_kategori) validComps.push('kategori');
                        if (this.config.has_sub_kegiatan) validComps.push('sub_kegiatan');
                        
                        if (!validComps.includes(this.tabComp)) {
                            this.tabComp = validComps.length > 0 ? validComps[0] : '';
                        }
                        
                        this.$nextTick(() => { 
                            this.renderTrend(); 
                            if(this.tabComp) this.renderComp(); 
                            if (this.detailType === 'peran_serta_masyarakat' || this.detailType === 'pemberdayaan') {
                                this.renderDetailChart();
                            }
                        }); 
                    }); 
            },

            renderRanking(data) {
                let opts = { 
                    series: [{ name: 'Total Kegiatan', data: data.data }], 
                    chart: { type: 'bar', height: 400, toolbar: { show: false }, fontFamily: 'inherit' }, 
                    plotOptions: { bar: { horizontal: true, distributed: true, borderRadius: 6, borderRadiusApplication: 'end' } }, 
                    xaxis: { categories: data.labels }, 
                    dataLabels: { enabled: true }, 
                    grid: { show: false }, 
                    title: { 
                        text: `Ranking Agregat Program Utama P2M (Tahun ${this.globalYear})`, 
                        align: 'left', 
                        style: { fontSize: '15px', fontWeight: '500', color: '#495057' } 
                    } 
                };
                if (this.chartInst.rank) this.chartInst.rank.destroy(); 
                this.chartInst.rank = new ApexCharts(this.$refs.rankingChart, opts); 
                this.chartInst.rank.render();
            },

            renderTrend() {
                if (!this.$refs.chartTrend || !this.rawData) return;
                let dataSeries = this.tabTrend === 'kegiatan' ? this.rawData.trend.kegiatan : this.rawData.trend.peserta;
                let isHeatmap = this.isMultiSatker && this.adminTrendType === 'heatmap';
                
                let opts = { 
                    series: dataSeries, 
                    chart: { type: isHeatmap ? 'heatmap' : 'bar', height: 400, toolbar: { show: true }, fontFamily: 'inherit' }, 
                    xaxis: { categories: this.rawData.trend_labels }, 
                    title: { 
                        text: `${this.dynamicTrendMetric} - ${this.detailTypeName} (Tren 12 Bulan Tahun ${this.globalYear})`, 
                        align: 'center', margin: 20, 
                        style: { fontSize: '18px', fontWeight: '500', color: '#212529' } 
                    } 
                };

                if (isHeatmap) {
                    opts.colors = ['#0d6efd']; opts.legend = { show: false };
                    opts.dataLabels = { enabled: true, formatter: (val) => (val || 0).toLocaleString('id-ID'), style: { colors: ['#212529'], fontSize: '13px' } };
                    opts.plotOptions = { heatmap: { shadeIntensity: 0.6, radius: 4, useFillColorAsStroke: false } };
                    opts.yaxis = { labels: { style: { fontWeight: 'bold' } } };
                } else {
                    opts.colors = this.isMultiSatker ? this.getColors() : ['#0d6efd'];
                    opts.plotOptions = { bar: { borderRadius: 4, columnWidth: this.isMultiSatker ? '85%' : '50%' } };
                    opts.tooltip = { shared: true, intersect: false };
                    opts.dataLabels = { enabled: !this.isMultiSatker, formatter: (val) => val > 0 ? new Intl.NumberFormat('id-ID').format(val) : "" };
                    if (this.isMultiSatker) opts.legend = { position: 'top', fontWeight: 'bold', offsetY: -10 };
                    opts.yaxis = { labels: { formatter: (v) => Math.round(v||0) } };
                }
                
                if (this.chartInst.trend) this.chartInst.trend.destroy(); 
                this.chartInst.trend = new ApexCharts(this.$refs.chartTrend, opts); 
                this.chartInst.trend.render();
            },

            renderComp() {
                if (!this.$refs.chartComp || !this.rawData || !this.tabComp) return;
                
                let dataSeries = this.rawData.comp[this.tabComp];
                let colors = this.tabComp === 'anggaran' ? ['#198754', '#fd7e14'] : this.getColors();
                
                let opts = { 
                    series: dataSeries, 
                    chart: { type: 'bar', height: 450, stacked: true, toolbar: { show: false }, fontFamily: 'inherit' }, 
                    plotOptions: { 
                        bar: { 
                            horizontal: true, 
                            columnWidth: this.isMultiSatker ? '50%' : '30%', 
                            borderRadius: 6, 
                            borderRadiusApplication: 'end', 
                        } 
                    }, 
                    xaxis: { labels: { style: { fontSize: '12px', fontWeight: 'bold' } } }, 
                    yaxis: { 
                        categories: this.rawData.comp_labels,
                        labels: { style: { fontSize: '12px', fontWeight: 'bold' } } 
                    },
                    colors: colors, 
                    legend: { position: 'top', fontWeight: 'bold', offsetY: -10 }, 
                    tooltip: { shared: true, intersect: false }, 
                    title: { 
                        text: `${this.dynamicCompMetric} - ${this.detailTypeName} (${this.timeLabelText})`, 
                        align: 'center', margin: 20, 
                        style: { fontSize: '18px', fontWeight: '500', color: '#212529' } 
                    }, 
                    dataLabels: { 
                        enabled: true, 
                        formatter: function(val, opt) { 
                            if (!val) return ""; 
                            let t = 0; opt.w.config.series.forEach(s => t += s.data[opt.dataPointIndex]); 
                            return t === 0 ? "" : val + " (" + Math.round((val/t)*100) + "%)"; 
                        }, style: { fontSize: '12px', colors: ['#212529'] } 
                    } 
                };
                
                if (this.chartInst.comp) this.chartInst.comp.destroy(); 
                this.chartInst.comp = new ApexCharts(this.$refs.chartComp, opts); 
                this.chartInst.comp.render();
            },

            // --- FUNGSI CHART DRILL-DOWN ---
            renderDetailChart() {
                if (!this.$refs.chartDetail || this.tableData.length === 0) return;
                
                let categories = [];
                let s1 = []; // Data utama (Biru/Hijau)
                let s2 = []; // Data khusus Positif Narkoba (Merah)
                let colors = [];
                
                let isPeserta = this.drilldownMetric === 'peserta';
                
                // Cek apakah batang indikasi merah boleh dimunculkan
                let showPositif = isPeserta && 
                                  this.drilldownLevel === 2 && 
                                  this.selectedKategori.includes('Pengembangan Kapasitas');

                const colorPalette = ['#0d6efd', '#198754', '#fd7e14', '#6f42c1', '#0dcaf0', '#20c997', '#ffc107'];
                let categoryColors = {};
                let colorIdx = 0;

                if (this.drilldownLevel === 1) {
                    let grouped = {};
                    this.tableData.forEach(row => {
                        if (!grouped[row.kategori]) {
                            grouped[row.kategori] = { peserta: 0, frekuensi: 0, positif: 0 };
                            if(!categoryColors[row.kategori]) {
                                categoryColors[row.kategori] = colorPalette[colorIdx % colorPalette.length];
                                colorIdx++;
                            }
                        }
                        grouped[row.kategori].peserta += row.peserta;
                        grouped[row.kategori].frekuensi += row.frekuensi;
                        grouped[row.kategori].positif += (row.positif || 0);
                    });

                    for (let kat in grouped) {
                        categories.push(kat);
                        colors.push(categoryColors[kat]); // Terapkan Rainbow
                        
                        if (isPeserta) {
                            s1.push(grouped[kat].peserta);
                        } else {
                            s1.push(grouped[kat].frekuensi);
                        }
                    }

                } else if (this.drilldownLevel === 2) {
                    let filteredData = this.tableData.filter(row => row.kategori === this.selectedKategori);
                    let baseColor = isPeserta ? '#0d6efd' : '#198754';
                    
                    filteredData.forEach(row => {
                        categories.push(row.nama);
                        
                        if (isPeserta) {
                            if (showPositif) {
                                // Jangan disatukan (unstacked), tampilkan dua bar (total peserta vs positif)
                                s1.push(row.peserta);
                                s2.push(row.positif || 0);
                            } else {
                                s1.push(row.peserta);
                            }
                        } else {
                            s1.push(row.frekuensi);
                        }
                        colors.push(baseColor);
                    });
                }

                let dynamicHeight = Math.max(300, categories.length * 85);

                let series = [];
                let chartColors = [];

                if (isPeserta) {
                    if (showPositif) {
                        // Terpisah di atas bawah (karena parameter stacked akan diset false)
                        series = [
                            { name: 'Jumlah Peserta / Orang', data: s1 },
                            { name: 'Indikasi Positif', data: s2 }
                        ];
                        chartColors = ['#0d6efd', '#dc3545'];
                    } else {
                        series = [
                            { name: 'Jumlah Peserta / Orang', data: s1 }
                        ];
                        chartColors = this.drilldownLevel === 1 ? colors : ['#0d6efd'];
                    }
                } else {
                    series = [
                        { name: 'Total Kegiatan', data: s1 }
                    ];
                    chartColors = this.drilldownLevel === 1 ? colors : ['#198754'];
                }

                let opts = {
                    series: series,
                    chart: {
                        type: 'bar',
                        height: dynamicHeight,
                        stacked: false, // PASTI FALSE: Agar bar peserta dan positif terpisah atas bawah
                        toolbar: { show: false },
                        fontFamily: 'inherit',
                        events: {
                            dataPointSelection: (event, chartContext, config) => {
                                if (this.drilldownLevel === 1) {
                                    let clickedCat = categories[config.dataPointIndex];
                                    this.selectedKategori = clickedCat;
                                    this.drilldownLevel = 2;
                                    this.renderDetailChart();
                                }
                            }
                        }
                    },
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            barHeight: '55%',
                            borderRadius: 4,
                            distributed: (this.drilldownLevel === 1), 
                            cursor: this.drilldownLevel === 1 ? 'pointer' : 'default',
                            dataLabels: { position: 'center' } // Menempatkan kotak data di tengah bar
                        }
                    },
                    colors: chartColors,
                    dataLabels: {
                        enabled: true,
                        textAnchor: 'middle', // Teks benar-benar di tengah kotak
                        style: { colors: ['#fff'], fontSize: '13px', fontWeight: 'bold' },
                        formatter: (val, opt) => {
                            if (!val) return ""; 
                            let text = new Intl.NumberFormat('id-ID').format(val);
                            
                            if (isPeserta) {
                                if (showPositif && opt.seriesIndex === 1) return text + " Positif";
                                return text + " Orang";
                            } else {
                                return text + " Kegiatan";
                            }
                        },
                        offsetX: 0,
                        dropShadow: { enabled: true, top: 1, left: 1, blur: 1, color: '#000', opacity: 0.5 }
                    },
                    xaxis: {
                        categories: categories, 
                        labels: { formatter: (val) => new Intl.NumberFormat('id-ID').format(val) }
                    },
                    yaxis: {
                        labels: {
                            style: { fontSize: '12px', fontWeight: '600' },
                            maxWidth: 400 
                        }
                    },
                    tooltip: {
                        theme: 'light',
                        y: {
                            formatter: function(val) {
                                return new Intl.NumberFormat('id-ID').format(val) + (isPeserta ? " Orang" : " Kegiatan");
                            }
                        }
                    },
                    legend: { 
                        show: showPositif,
                        position: 'top',
                        horizontalAlign: 'left',
                        fontWeight: 'bold'
                    }
                };

                if (this.chartInst.detail) this.chartInst.detail.destroy();
                this.chartInst.detail = new ApexCharts(this.$refs.chartDetail, opts);
                this.chartInst.detail.render();
            }
        }));
    });
</script>
@endpush