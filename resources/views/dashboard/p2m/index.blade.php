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

        {{-- RANKING CHART --}}
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
                            <option value="all">Total Akumulasi</option>
                            <option value="per_bulan">Per Bulan</option>
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
                            <template x-if="isMultiSatker && filterMonth === 'per_bulan'">
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
                    <div class="card-body px-4 pb-4 pt-0">
                        <div style="max-height: 65vh; overflow-y: auto; overflow-x: auto;" class="pe-2 w-100">
                            <div x-ref="chartTrend"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Grafik Proporsi --}}
            <div class="col-12" x-show="config.has_anggaran || config.has_sasaran || config.has_kategori || config.has_sub_kegiatan">
                <div class="card border-0 shadow-sm h-100 bg-white rounded-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-3 d-flex justify-content-center align-items-center gap-3">
                        <div class="d-flex bg-light p-1 rounded-3 flex-wrap justify-content-center border shadow-sm">
                            <button x-show="config.has_anggaran" @click="tabComp = 'anggaran'" :class="tabComp === 'anggaran' ? 'btn-white text-dark shadow-sm border' : 'btn-transparent text-secondary border-0'" class="btn btn-md fw-bold px-4 rounded-3">Berdasarkan Anggaran</button>
                            <button x-show="config.has_sasaran" @click="tabComp = 'sasaran'" :class="tabComp === 'sasaran' ? 'btn-white text-dark shadow-sm border' : 'btn-transparent text-secondary border-0'" class="btn btn-md fw-bold px-4 rounded-3">Berdasarkan Sasaran Wilayah</button>
                            <button x-show="config.has_kategori" @click="tabComp = 'kategori'" :class="tabComp === 'kategori' ? 'btn-white text-dark shadow-sm border' : 'btn-transparent text-secondary border-0'" class="btn btn-md fw-bold px-4 rounded-3">Berdasarkan Kategori Kegiatan</button>
                            <button x-show="config.has_sub_kegiatan" @click="tabComp = 'sub_kegiatan'" :class="tabComp === 'sub_kegiatan' ? 'btn-white text-dark shadow-sm border' : 'btn-transparent text-secondary border-0'" class="btn btn-md fw-bold px-4 rounded-3">Berdasarkan Sub Kegiatan</button>
                        </div>
                    </div>
                    
                    <div class="px-4 pt-2 pb-3 d-flex justify-content-center border-bottom border-light">
                        <div class="d-inline-flex overflow-auto max-w-100 gap-2" style="white-space: nowrap;">
                            <button @click="compToggle = 'all'" :class="compToggle === 'all' ? 'btn-dark' : 'btn-outline-dark bg-white'" class="btn btn-sm rounded-pill px-4 fw-bold shadow-sm">Semua Proporsi (Gabungan)</button>
                            <template x-for="opt in (rawData?.comp[tabComp]?.options || [])">
                                <button @click="compToggle = opt.id" :class="compToggle === opt.id ? 'btn-primary' : 'btn-outline-primary bg-white'" class="btn btn-sm rounded-pill px-4 fw-bold shadow-sm" x-text="opt.label"></button>
                            </template>
                        </div>
                    </div>

                    {{-- WADAH GRAFIK PROPORSI --}}
                    <div class="card-body px-4 pb-4 pt-4">
                        <div x-ref="chartComp" class="w-100"></div>
                    </div>
                </div>
            </div>

            {{-- Grafik Rincian Spesifik --}}
            <div class="col-12" x-show="detailType === 'peran_serta_masyarakat' || detailType === 'pemberdayaan'">
                <div class="card border-0 shadow-sm bg-white rounded-4">
                    
                    <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <h6 class="fw-bold text-dark mb-0">
                            <span x-show="drilldownLevel === 1">Level 1: Kinerja Program <span x-text="detailTypeName"></span> (<span x-text="timeLabelText"></span>)</span>
                            <span x-show="drilldownLevel === 2">Level 2: Detail Kegiatan - <span class="text-primary" x-text="selectedKategori"></span></span>
                            <span x-show="drilldownLevel === 3">Level 3: Sebaran Satuan Kerja - <span class="text-primary" x-text="selectedKegiatan"></span></span>
                        </h6>

                        <div class="d-flex align-items-center gap-2">
                            <button x-show="drilldownLevel > 1" @click="goBack()" class="btn btn-sm btn-outline-secondary rounded-pill fw-bold px-3">
                                <i class="bi bi-arrow-left me-1"></i> Kembali
                            </button>

                            <div class="bg-light p-1 rounded-pill d-flex">
                                <button @click="drilldownMetric = 'kegiatan'" :class="drilldownMetric === 'kegiatan' ? 'btn-success text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-3 border-0">Total Kegiatan</button>
                                <button @click="drilldownMetric = 'peserta'" :class="drilldownMetric === 'peserta' ? 'btn-primary text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-3 border-0">Peserta (Orang)</button>
                            </div>
                        </div>
                    </div>

                    <div class="card-body px-4 pb-4 pt-2">
                        <p x-show="drilldownLevel < 3 && tableData.length > 0" class="text-muted small mb-4">
                            <i class="bi bi-info-circle me-1"></i> Tips Interaktif: Silakan klik salah satu batang grafik di bawah ini untuk melihat rincian datanya lebih dalam.
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
            compToggle: 'all',
            adminTrendType: 'bar', 
            
            drilldownLevel: 1, 
            selectedKategori: '',
            selectedKegiatan: '',
            drilldownMetric: 'kegiatan', 

            config: { 
                unit: 'Peserta', has_anggaran: true, has_sasaran: true, 
                has_kategori: false, has_sub_kegiatan: false 
            },
            isMultiSatker: false, 
            rawData: null, 
            tableData: [],
            
            chartInst: { rank: null, trend: null, comp: [], detail: null },
            
            getColors() { return ['#0d6efd', '#198754', '#fd7e14', '#6f42c1', '#0dcaf0', '#dc3545', '#20c997', '#ffc107', '#6c757d']; },

            init() {
                this.fetchGlobal(); 
                this.fetchDetail();
                
                this.$watch('globalSatkerId', () => { this.fetchGlobal(); this.fetchDetail(); });
                this.$watch('globalYear', () => { this.fetchGlobal(); this.fetchDetail(); });
                
                ['detailType', 'adminTrendType', 'filterMonth'].forEach(p => { 
                    this.$watch(p, () => { 
                        this.compToggle = 'all'; 
                        this.fetchDetail(); 
                    }); 
                });
                
                this.$watch('tabTrend', () => this.renderTrend());
                this.$watch('tabComp', () => {
                    this.compToggle = 'all'; 
                    this.renderComp();
                });
                this.$watch('compToggle', () => this.renderComp());
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
                    return `Total Akumulasi Tahun ${this.globalYear}`; 
                } else if (this.filterMonth === 'per_bulan') {
                    return `Tren Per Bulan Tahun ${this.globalYear}`;
                } else {
                    const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                    return `Bulan ${months[this.filterMonth - 1]} ${this.globalYear}`;
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

            goBack() {
                if (this.drilldownLevel === 3) {
                    this.drilldownLevel = 2;
                    this.selectedKegiatan = '';
                } else if (this.drilldownLevel === 2) {
                    this.drilldownLevel = 1;
                    this.selectedKategori = '';
                }
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
                        this.selectedKegiatan = '';

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
                let isPerBulan = this.filterMonth === 'per_bulan';
                let isHeatmap = this.isMultiSatker && this.adminTrendType === 'heatmap' && isPerBulan;
                let metricLabel = this.tabTrend === 'kegiatan' ? 'Kegiatan' : 'Orang';
                
                let numSeries = dataSeries.length;
                let calculatedHeight = isHeatmap ? Math.max(450, numSeries * 60) : 450;
                
                let opts = { 
                    series: dataSeries, 
                    chart: { type: isHeatmap ? 'heatmap' : 'bar', height: calculatedHeight, toolbar: { show: true }, fontFamily: 'inherit' }, 
                    title: { text: `${this.dynamicTrendMetric} - ${this.detailTypeName} (${this.timeLabelText})`, align: 'center', margin: 20, style: { fontSize: '18px', fontWeight: '500', color: '#212529' } } 
                };

                if (isHeatmap) {
                    opts.xaxis = { categories: this.rawData.trend_labels };
                    opts.colors = ['#0d6efd']; 
                    opts.legend = { show: false };
                    opts.dataLabels = { enabled: true, formatter: (val) => val > 0 ? new Intl.NumberFormat('id-ID').format(val) : "", style: { colors: ['#212529'], fontSize: '13px' } };
                    opts.plotOptions = { heatmap: { shadeIntensity: 0.6, radius: 4, useFillColorAsStroke: false } };
                    opts.yaxis = { labels: { style: { fontWeight: 'bold' } } };
                } else {
                    opts.plotOptions = { bar: { horizontal: false, borderRadius: 2, columnWidth: '70%', barHeight: '70%', distributed: (numSeries === 1 && this.isMultiSatker && !isPerBulan) } };
                    if (opts.plotOptions.bar.distributed) {
                        opts.colors = this.getColors(); opts.legend = { show: false };
                    } else if (this.isMultiSatker) {
                        opts.colors = this.getColors(); opts.legend = { position: 'top', fontWeight: 'bold', offsetY: -10 };
                    } else {
                        opts.colors = ['#0d6efd']; opts.legend = { show: false };
                    }

                    opts.xaxis = { categories: this.rawData.trend_labels, labels: { formatter: (v) => typeof v === 'number' ? Math.round(v) : v } };
                    opts.yaxis = { labels: { style: { fontWeight: 'bold' } } };
                    opts.stroke = { show: true, width: 2, colors: ['#ffffff'] };
                    opts.tooltip = { shared: true, intersect: false, y: { formatter: (val) => new Intl.NumberFormat('id-ID').format(val) + ' ' + metricLabel } };
                    opts.dataLabels = { enabled: false };
                }
                
                if (this.chartInst.trend) this.chartInst.trend.destroy(); 
                this.chartInst.trend = new ApexCharts(this.$refs.chartTrend, opts); 
                this.chartInst.trend.render();
            },

            // --- FUNGSI CHART KOMPARASI (PROPORSI) ---
            renderComp() {
                if (!this.$refs.chartComp || !this.rawData || !this.tabComp) return;
                
                let compData = this.rawData.comp[this.tabComp];
                if (!compData) return;

                let isPerBulan = this.filterMonth === 'per_bulan';
                let isMulti = this.isMultiSatker;
                let container = this.$refs.chartComp;

                // 1. Bersihkan seluruh chart lama di dalam container
                if (this.chartInst.comp) {
                    if (Array.isArray(this.chartInst.comp)) {
                        this.chartInst.comp.forEach(c => c.destroy());
                    } else {
                        this.chartInst.comp.destroy();
                    }
                }
                this.chartInst.comp = [];
                container.innerHTML = ''; 

                let colors = this.getColors();

                // =========================================================================
                // SKENARIO KHUSUS: Seluruh Satuan Kerja + Per Bulan + SEMUA PROPORSI (GABUNGAN)
                // Menggunakan layout Opsi B (Grouped Horizontal Bar) dgn wadah relatif (vh)
                // =========================================================================
                if (isPerBulan && isMulti && this.compToggle === 'all') {
                    
                    // Gunakan custom scrollbar biasa tanpa scroll-snap yang kaku
                    container.classList.add('custom-scrollbar');
                    container.style.maxHeight = '85vh'; // Batasan tinggi wadah utama
                    container.style.overflowY = 'auto';

                    let months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

                    // Cari max global agar sumbu X sama dari Jan - Des 
                    let globalMax = 0;
                    compData.options.forEach(opt => {
                        (compData.detailed[opt.id] || []).forEach(s => {
                            s.data.forEach(val => { if (val > globalMax) globalMax = val; });
                        });
                    });
                    // Buffer ruang agar label angka tidak mentok dinding chart
                    let maxX = globalMax > 0 ? Math.ceil(globalMax * 1.15) : undefined; 

                    for (let m = 0; m < 12; m++) {
                        let monthSeries = [];
                        let satkerNames = [];
                        
                        compData.options.forEach(opt => {
                            (compData.detailed[opt.id] || []).forEach(s => {
                                if (!satkerNames.includes(s.name)) satkerNames.push(s.name);
                            });
                        });

                        compData.options.forEach(opt => {
                            let dataForOpt = [];
                            satkerNames.forEach(sName => {
                                let match = (compData.detailed[opt.id] || []).find(s => s.name === sName);
                                dataForOpt.push(match ? match.data[m] : 0);
                            });
                            monthSeries.push({ name: opt.label, data: dataForOpt });
                        });

                        // Wrapper per bulan
                        let monthDiv = document.createElement('div');
                        monthDiv.className = 'w-100 mb-5 pb-3 border-bottom border-2 border-light';
                        // Memastikan setiap blok bulan berukuran tepat satu viewport (misal 75vh)
                        monthDiv.style.height = '75vh'; 
                        monthDiv.style.display = 'flex';
                        monthDiv.style.flexDirection = 'column';
                        
                        let titleWrapper = document.createElement('div');
                        titleWrapper.className = 'text-center flex-shrink-0 mb-3'; 
                        titleWrapper.innerHTML = `<h5 class="fw-bold text-primary d-inline-block border-bottom border-primary border-opacity-25 pb-2"><i class="bi bi-calendar-event me-2"></i>BULAN ${months[m].toUpperCase()} ${this.globalYear}</h5>`;
                        monthDiv.appendChild(titleWrapper);

                        let chartDiv = document.createElement('div');
                        // Trik Flexbox: Biarkan Chart mengambil 100% dari sisa tinggi monthDiv
                        chartDiv.style.flexGrow = '1';
                        chartDiv.style.minHeight = '0';
                        chartDiv.style.width = '100%';
                        
                        monthDiv.appendChild(chartDiv);
                        container.appendChild(monthDiv);

                        let opts = {
                            series: monthSeries,
                            chart: { 
                                type: 'bar', 
                                height: '100%', // <-- Kunci responsivitas (tidak pakai px)
                                stacked: false, 
                                toolbar: { show: false }, 
                                fontFamily: 'inherit' 
                            },
                            colors: colors,
                            plotOptions: {
                                bar: { horizontal: true, barHeight: '80%', dataLabels: { position: 'top' }, borderRadius: 2 }
                            },
                            dataLabels: {
                                enabled: true,
                                textAnchor: 'start', 
                                style: { colors: ['#212529'], fontSize: '11px', fontWeight: 'bold' },
                                formatter: function (val) { return val > 0 ? val : ''; }, 
                                offsetX: 5 
                            },
                            stroke: { show: true, width: 1, colors: ['#fff'] },
                            xaxis: { 
                                categories: satkerNames, 
                                max: maxX, 
                                labels: { 
                                    show: true, // Pastikan angka sumbu X selalu terlihat
                                    style: { fontWeight: 'bold' } 
                                } 
                            },
                            yaxis: { labels: { style: { fontWeight: 'bold', fontSize: '13px' }, maxWidth: 250 } },
                            tooltip: { shared: true, intersect: false, y: { formatter: function(val) { return val + " Kegiatan"; } } },
                            legend: { position: 'top', fontWeight: 'bold' }
                        };

                        let chart = new ApexCharts(chartDiv, opts);
                        chart.render();
                        this.chartInst.comp.push(chart); 
                    }
                } 
                // =========================================================================
                // SKENARIO REGULER & TOGGLE KHUSUS (Single Chart)
                // =========================================================================
                else {
                    container.classList.remove('custom-scrollbar');
                    container.style.maxHeight = 'none';
                    container.style.overflowY = 'visible';
                    
                    let dataSeries = [];
                    let chartType = 'bar';
                    let isStacked = false; 
                    let isHorizontal = false; 
                    let labels = [];

                    if (this.compToggle === 'all') {
                        if (!isPerBulan && !isMulti) {
                            chartType = 'donut';
                            dataSeries = compData.aggregated.map(s => s.data[0] || 0);
                            labels = compData.aggregated.map(s => s.name);
                            if (this.tabComp === 'anggaran') colors = compData.aggregated.map(s => s.name === 'DIPA' ? '#198754' : '#fd7e14');
                        } else {
                            dataSeries = compData.aggregated;
                            labels = this.rawData.comp_labels;
                            isStacked = true;
                            if (this.tabComp === 'anggaran') colors = ['#198754', '#fd7e14'];
                        }
                    } else {
                        let optLabel = compData.options.find(o => o.id === this.compToggle)?.label || this.compToggle;
                        
                        if (isPerBulan && isMulti) {
                            dataSeries = compData.detailed[this.compToggle] || [];
                            labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                            isHorizontal = false; 
                            isStacked = false; 
                            colors = this.getColors();
                        } else {
                            let found = compData.aggregated.find(s => s.name === optLabel);
                            dataSeries = found ? [found] : [];
                            labels = this.rawData.comp_labels;
                            isHorizontal = false;
                            isStacked = false;
                            if (this.tabComp === 'anggaran') colors = optLabel === 'DIPA' ? ['#198754'] : ['#fd7e14'];
                            else colors = ['#0d6efd'];
                        }
                    }

                    let chartDiv = document.createElement('div');
                    container.appendChild(chartDiv);

                    let calculatedHeight = 450; 

                    let opts = { 
                        series: dataSeries, 
                        chart: { type: chartType, height: calculatedHeight, stacked: isStacked, toolbar: { show: true }, fontFamily: 'inherit' }, 
                        colors: colors, 
                        title: { text: `${this.dynamicCompMetric} - ${this.detailTypeName} (${this.timeLabelText})`, align: 'center', margin: 20, style: { fontSize: '18px', fontWeight: '500' } }, 
                    };

                    if (chartType === 'donut') {
                        opts.labels = labels;
                        opts.plotOptions = { pie: { donut: { size: '65%' } } };
                        opts.dataLabels = { enabled: true, formatter: function (val, opts) { return opts.w.config.series[opts.seriesIndex] + " Kegiatan (" + val.toFixed(1) + "%)" } };
                        opts.legend = { position: 'bottom', fontSize: '14px', fontWeight: 'bold' };
                        opts.tooltip = { y: { formatter: (val) => val + ' Kegiatan' } };
                    } else {
                        opts.plotOptions = { 
                            bar: { 
                                horizontal: isHorizontal, 
                                borderRadius: 2, 
                                columnWidth: '70%', 
                                barHeight: '70%' 
                            } 
                        };
                        opts.xaxis = { categories: labels, labels: { style: { fontSize: '12px', fontWeight: 'bold' } } };
                        opts.yaxis = { labels: { style: { fontSize: '13px', fontWeight: 'bold' } } };
                        opts.legend = { position: 'top', fontWeight: 'bold', offsetY: -10 };
                        opts.tooltip = { shared: true, intersect: false, y: { formatter: function (val) { return val + " Kegiatan"; } } };
                        opts.stroke = { show: true, width: 1, colors: ['#ffffff'] };
                        opts.dataLabels = { enabled: false }; 
                    }

                    let chart = new ApexCharts(chartDiv, opts);
                    chart.render();
                    this.chartInst.comp = chart;
                }
            },

            // --- FUNGSI CHART DRILL-DOWN ---
            renderDetailChart() {
                if (!this.$refs.chartDetail || this.tableData.length === 0) return;
                
                let categories = []; let s1 = []; let s2 = []; let colors = [];
                let isPeserta = this.drilldownMetric === 'peserta';
                
                const colorPalette = ['#0d6efd', '#198754', '#fd7e14', '#6f42c1', '#0dcaf0', '#20c997', '#ffc107'];
                let categoryColors = {}; let colorIdx = 0; let showPositif = false;

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
                        categories.push(kat); colors.push(categoryColors[kat]); 
                        if (isPeserta) s1.push(grouped[kat].peserta);
                        else s1.push(grouped[kat].frekuensi);
                    }
                } 
                else if (this.drilldownLevel === 2) {
                    showPositif = isPeserta && this.selectedKategori.includes('Pengembangan Kapasitas');
                    let baseColor = isPeserta ? '#0d6efd' : '#198754';
                    
                    let filteredData = this.tableData.filter(row => row.kategori === this.selectedKategori);
                    let grouped = {};
                    
                    filteredData.forEach(row => {
                        if (!grouped[row.nama]) grouped[row.nama] = { peserta: 0, frekuensi: 0, positif: 0 };
                        grouped[row.nama].peserta += row.peserta;
                        grouped[row.nama].frekuensi += row.frekuensi;
                        grouped[row.nama].positif += (row.positif || 0);
                    });

                    for (let nama in grouped) {
                        categories.push(nama); colors.push(baseColor);
                        if (isPeserta) {
                            s1.push(grouped[nama].peserta);
                            if (showPositif) s2.push(grouped[nama].positif);
                        } else s1.push(grouped[nama].frekuensi);
                    }
                }
                else if (this.drilldownLevel === 3) {
                    showPositif = isPeserta && this.selectedKegiatan.toLowerCase().includes('tes urine');
                    let baseColor = isPeserta ? '#0d6efd' : '#198754';

                    let filteredData = this.tableData.filter(row => row.kategori === this.selectedKategori && row.nama === this.selectedKegiatan);
                    let grouped = {};

                    filteredData.forEach(row => {
                        if (!grouped[row.satker]) grouped[row.satker] = { peserta: 0, frekuensi: 0, positif: 0 };
                        grouped[row.satker].peserta += row.peserta;
                        grouped[row.satker].frekuensi += row.frekuensi;
                        grouped[row.satker].positif += (row.positif || 0);
                    });

                    let sortedSatkers = Object.keys(grouped).sort((a, b) => {
                        let valA = isPeserta ? grouped[a].peserta : grouped[a].frekuensi;
                        let valB = isPeserta ? grouped[b].peserta : grouped[b].frekuensi;
                        return valB - valA; 
                    });

                    for (let satker of sortedSatkers) {
                        categories.push(satker); colors.push(baseColor);
                        if (isPeserta) {
                            s1.push(grouped[satker].peserta);
                            if (showPositif) s2.push(grouped[satker].positif);
                        } else s1.push(grouped[satker].frekuensi);
                    }
                }

                let dynamicHeight = Math.max(300, categories.length * 85);
                let series = []; let chartColors = [];

                if (isPeserta) {
                    if (showPositif) {
                        series = [{ name: 'Jumlah Peserta / Orang', data: s1 }, { name: 'Indikasi Positif', data: s2 }];
                        chartColors = ['#0d6efd', '#dc3545'];
                    } else {
                        series = [{ name: 'Jumlah Peserta / Orang', data: s1 }];
                        chartColors = this.drilldownLevel === 1 ? colors : ['#0d6efd'];
                    }
                } else {
                    series = [{ name: 'Total Kegiatan', data: s1 }];
                    chartColors = this.drilldownLevel === 1 ? colors : ['#198754'];
                }

                let opts = {
                    series: series,
                    chart: {
                        type: 'bar', height: dynamicHeight, stacked: false, toolbar: { show: false }, fontFamily: 'inherit',
                        events: {
                            dataPointSelection: (event, chartContext, config) => {
                                if (this.drilldownLevel === 1) {
                                    this.selectedKategori = categories[config.dataPointIndex];
                                    this.drilldownLevel = 2;
                                    this.renderDetailChart();
                                } else if (this.drilldownLevel === 2) {
                                    this.selectedKegiatan = categories[config.dataPointIndex];
                                    this.drilldownLevel = 3;
                                    this.renderDetailChart();
                                }
                            }
                        }
                    },
                    stroke: { show: true, width: 3, colors: ['#ffffff'] },
                    plotOptions: {
                        bar: {
                            horizontal: true, barHeight: '80%', borderRadius: 4,
                            distributed: (this.drilldownLevel === 1), 
                            cursor: this.drilldownLevel < 3 ? 'pointer' : 'default',
                        }
                    },
                    colors: chartColors,
                    dataLabels: { enabled: false },
                    xaxis: { categories: categories },
                    yaxis: { labels: { style: { fontSize: '12px', fontWeight: '600' }, maxWidth: 400 } },
                    tooltip: {
                        theme: 'light',
                        y: {
                            formatter: function(val) {
                                return new Intl.NumberFormat('id-ID').format(val || 0) + (isPeserta ? " Orang" : " Kegiatan");
                            }
                        }
                    },
                    legend: { show: showPositif, position: 'top', horizontalAlign: 'left', fontWeight: 'bold' }
                };

                if (this.chartInst.detail) this.chartInst.detail.destroy();
                this.chartInst.detail = new ApexCharts(this.$refs.chartDetail, opts);
                this.chartInst.detail.render();
            }
        }));
    });
</script>

<style>
    /* Styling untuk custom scrollbar yang elegan */
    .custom-scrollbar::-webkit-scrollbar {
        width: 8px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f1f1; 
        border-radius: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #c1c1c1; 
        border-radius: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8; 
    }
</style>
@endpush