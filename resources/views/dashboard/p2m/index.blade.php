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
                                @foreach($satkers as $s) 
                                    <option value="{{ $s->id }}">{{ $s->satuan_kerja }}</option> 
                                @endforeach
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
                    @foreach($years as $y) 
                        <option value="{{ $y }}">{{ $y }}</option> 
                    @endforeach
                </select>
            </div>
        </div>

        {{-- ========================================================== --}}
        {{-- 3 KARTU UTAMA (COMPACT & ELEGANT DESIGN) --}}
        {{-- ========================================================== --}}
        <div class="row g-4 mb-4">
            
            {{-- KARTU 1: JANGKAUAN ORANG --}}
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm h-100 bg-white rounded-4 overflow-hidden d-flex flex-column" style="border-top: 4px solid #0d6efd !important;">
                    <div class="card-header bg-white border-0 pt-3 pb-2 d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 shadow-sm"><i class="bi bi-people-fill fs-4"></i></div>
                        <div>
                            <h6 class="text-muted fw-bold mb-1 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.7rem;">Jangkauan Masyarakat</h6>
                            <h3 class="fw-bold text-dark mb-0 lh-1"><span x-text="formatAngka(cards.orang.total)">0</span> <span class="fs-6 text-muted fw-normal">Orang</span></h3>
                        </div>
                    </div>
                    <div class="card-body p-3 pt-1 overflow-auto custom-scrollbar flex-grow-1" style="max-height: 200px;">
                        
                        {{-- Edukasi & Pelatihan --}}
                        <div class="mb-3">
                            <div class="small fw-bold text-primary border-bottom pb-1 mb-2">Edukasi dan Pelatihan</div>
                            <template x-for="(item, idx) in cards.orang.edukasi" :key="'edu-'+idx">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-secondary" style="font-size: 0.8rem; width: 75%; line-height: 1.2;" x-text="item.label"></span>
                                    <span class="fw-bold text-dark text-end" style="font-size: 0.8rem; width: 25%;" x-text="formatAngka(item.val)"></span>
                                </div>
                            </template>
                        </div>

                        {{-- Peran Serta Masyarakat & Tes Urine --}}
                        <div class="mb-3">
                            <div class="small fw-bold text-primary border-bottom pb-1 mb-2">Peran Serta Masyarakat</div>
                            <template x-for="(item, idx) in cards.orang.psm" :key="'psm-'+idx">
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-secondary" style="font-size: 0.8rem; width: 75%; line-height: 1.2;" x-text="item.label"></span>
                                        <span class="fw-bold text-dark text-end" style="font-size: 0.8rem; width: 25%;" x-text="formatAngka(item.val)"></span>
                                    </div>
                                    
                                    {{-- Penanda Khusus Tes Urine (Selalu Tampil Jika is_tu true) --}}
                                    <template x-if="item.is_tu">
                                        <div class="mt-1 ps-2 ms-1 border-start border-3 border-danger rounded-end bg-danger bg-opacity-10 p-2">
                                            <span class="d-block text-danger fw-bold mb-1" style="font-size: 0.75rem;">
                                                <i class="bi bi-droplet-half me-1"></i>Kegiatan Tes Urine: <span x-text="formatAngka(item.tu_peserta)"></span> Peserta
                                            </span>
                                            <span class="badge bg-danger text-white shadow-sm w-100 text-start py-1" style="font-size: 0.7rem; white-space: normal;">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i> <span x-text="formatAngka(item.tu_positif)"></span> Terindikasi Positif Narkotika
                                            </span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        {{-- Pemberdayaan Alternatif --}}
                        <div class="mb-1">
                            <div class="small fw-bold text-primary border-bottom pb-1 mb-2">Pemberdayaan Alternatif</div>
                            <template x-for="(item, idx) in cards.orang.pa" :key="'pa-'+idx">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-secondary" style="font-size: 0.8rem; width: 75%; line-height: 1.2;" x-text="item.label"></span>
                                    <span class="fw-bold text-dark text-end" style="font-size: 0.8rem; width: 25%;" x-text="formatAngka(item.val)"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KARTU 2: MEDIA --}}
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm h-100 bg-white rounded-4 overflow-hidden d-flex flex-column" style="border-top: 4px solid #ffc107 !important;">
                    <div class="card-header bg-white border-0 pt-3 pb-2 d-flex align-items-center gap-3">
                        <div class="bg-warning bg-opacity-10 text-warning p-2 rounded-3 shadow-sm"><i class="bi bi-megaphone-fill fs-4 text-dark"></i></div>
                        <div>
                            <h6 class="text-muted fw-bold mb-1 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.7rem;">Publikasi Media</h6>
                            <h3 class="fw-bold text-dark mb-0 lh-1"><span x-text="formatAngka(cards.media.total_freq)">0</span> <span class="fs-6 text-muted fw-normal">Tayangan</span></h3>
                        </div>
                    </div>
                    <div class="card-body p-3 pt-2 d-flex flex-column justify-content-start flex-grow-1">
                        <div class="mb-3">
                            <div class="d-flex align-items-center bg-light border rounded-3 p-2 shadow-sm">
                                <i class="bi bi-stopwatch text-warning fs-4 me-3 ms-2"></i>
                                <div>
                                    <span class="d-block small text-muted fw-bold mb-0">Total Durasi Penayangan</span>
                                    <h5 class="mb-0 fw-bold text-dark"><span x-text="formatAngka(cards.media.total_durasi)"></span> <span class="fs-6 text-muted fw-normal">Hari</span></h5>
                                </div>
                            </div>
                        </div>
                        <div class="mt-1">
                            <template x-for="(item, idx) in cards.media.list" :key="'media-'+idx">
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                    <span class="fw-bold text-secondary" style="font-size: 0.85rem;" x-text="item.label"></span>
                                    <div class="text-end text-dark">
                                        <span class="fw-bold d-block" style="font-size: 0.85rem;" x-text="formatAngka(item.freq) + ' Tayang'"></span> 
                                        <span class="badge bg-light text-muted border mt-1" style="font-size: 0.7rem;" x-text="formatAngka(item.durasi) + ' Hari'"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KARTU 3: KAWASAN BERSINAR --}}
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm h-100 bg-white rounded-4 overflow-hidden d-flex flex-column" style="border-top: 4px solid #0dcaf0 !important;">
                    <div class="card-header bg-white border-0 pt-3 pb-2 d-flex align-items-center gap-3">
                        <div class="bg-info bg-opacity-10 text-info p-2 rounded-3 shadow-sm"><i class="bi bi-geo-alt-fill fs-4 text-dark"></i></div>
                        <div>
                            <h6 class="text-muted fw-bold mb-1 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.7rem;">Ketahanan Kawasan</h6>
                            <h3 class="fw-bold text-dark mb-0 lh-1"><span x-text="formatAngka(cards.wilayah.total)">0</span> <span class="fs-6 text-muted fw-normal">Kawasan</span></h3>
                        </div>
                    </div>
                    <div class="card-body p-3 pt-2 d-flex flex-column justify-content-center align-items-center flex-grow-1 position-relative">
                        <i class="bi bi-houses-fill text-light" style="font-size: 7rem; position: absolute; z-index: 0; opacity: 0.3;"></i>
                        <div style="z-index: 1;" class="text-center mt-3">
                            <h5 class="fw-bold text-info mb-2">Desa Bersinar</h5>
                            <p class="text-muted small px-3" style="font-size: 0.85rem;">Total pencanangan dan intervensi Desa / Kelurahan Bersinar pada wilayah kerja.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================== --}}
        {{-- RANKING CHART AGREGAT (KEGIATAN) --}}
        {{-- ========================================================== --}}
        <div class="card border-0 shadow-sm mb-5 bg-white rounded-4">
            <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <h5 class="fw-bold text-dark mb-0 d-flex align-items-center">
                    <i class="bi bi-bar-chart-line-fill text-primary me-2 fs-5"></i> Ranking Agregat Program P2M
                </h5>
                <div class="bg-primary bg-opacity-10 px-3 py-2 rounded-pill border border-primary border-opacity-25 shadow-sm">
                    <span class="text-primary fw-bold small">Total Keseluruhan: <span class="fs-6 ms-1" x-text="formatAngka(cards.kegiatan.total)"></span> Kegiatan</span>
                </div>
            </div>
            <div class="card-body p-4 pt-1">
                <div x-ref="rankingChart" style="min-height: 320px;"></div>
            </div>
        </div>

        {{-- ========================================================== --}}
        {{-- PUSAT ANALISIS KINERJA DETAIL --}}
        {{-- ========================================================== --}}
        <div class="bg-white p-4 rounded-4 shadow-sm mb-4 border border-light">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-4">
                <div>
                    <h5 class="m-0 fw-bold text-dark"><i class="bi bi-display me-2 text-primary"></i>Analisis Kinerja Detail</h5>
                    <small class="text-muted">Jelajahi proporsi dan rincian data per program secara interaktif.</small>
                </div>
                
                <div class="d-flex flex-wrap gap-3 align-items-center">
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
                        <div style="max-height: 65vh; overflow-y: auto; overflow-x: auto;" class="pe-2 w-100 custom-scrollbar">
                            <div x-ref="chartTrend" style="min-width: 700px;"></div>
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
                        <div class="d-inline-flex overflow-auto max-w-100 gap-2 custom-scrollbar pb-2" style="white-space: nowrap;">
                            <button @click="compToggle = 'all'" :class="compToggle === 'all' ? 'btn-dark' : 'btn-outline-dark bg-white'" class="btn btn-sm rounded-pill px-4 fw-bold shadow-sm">Semua Proporsi (Gabungan)</button>
                            <template x-for="(opt, idx) in (rawData?.comp[tabComp]?.options || [])" :key="'opt-'+idx">
                                <button @click="compToggle = opt.id" :class="compToggle === opt.id ? 'btn-primary' : 'btn-outline-primary bg-white'" class="btn btn-sm rounded-pill px-4 fw-bold shadow-sm" x-text="opt.label"></button>
                            </template>
                        </div>
                    </div>

                    <div class="card-body px-4 pb-4 pt-4">
                        <div x-ref="chartComp" class="w-100"></div>
                    </div>
                </div>
            </div>

            {{-- Grafik Rincian Spesifik (Drill-Down Multi-Level) --}}
            <div class="col-12" x-show="detailType === 'peran_serta_masyarakat' || detailType === 'pemberdayaan'">
                <div class="card border-0 shadow-sm bg-white rounded-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-3 d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3">
                        
                        {{-- BREADCRUMB HIERARKI MULTI-LEVEL --}}
                        <div class="d-flex flex-column">
                            <h6 class="fw-bold text-dark mb-2 lh-base" style="font-size: 16px;">
                                <span class="text-dark">Kinerja Program <span x-text="detailTypeName"></span></span>
                                
                                <template x-if="drilldownLevel >= 2">
                                    <span>
                                        <i class="bi bi-chevron-right text-muted mx-2" style="font-size: 0.9rem;"></i>
                                        <span class="text-primary" x-text="selectedKategori"></span>
                                    </span>
                                </template>
                                
                                <template x-if="drilldownLevel >= 3">
                                    <span>
                                        <i class="bi bi-chevron-right text-muted mx-2" style="font-size: 0.9rem;"></i>
                                        <span class="text-success" x-text="selectedKegiatan"></span>
                                    </span>
                                </template>
                            </h6>
                            <div>
                                <span class="badge bg-light text-secondary border border-secondary border-opacity-25 fw-normal" x-text="timeLabelText"></span>
                                <span x-show="drilldownLevel === 1" class="badge bg-primary text-white ms-1 shadow-sm"><i class="bi bi-cursor-fill me-1"></i>Level 1: Kategori</span>
                                <span x-show="drilldownLevel === 2" class="badge bg-primary text-white ms-1 shadow-sm"><i class="bi bi-cursor-fill me-1"></i>Level 2: Kegiatan</span>
                                <span x-show="drilldownLevel === 3" class="badge bg-success text-white ms-1 shadow-sm"><i class="bi bi-check-circle-fill me-1"></i>Level 3: Detail Satuan Kerja</span>
                            </div>
                        </div>

                        <div class="d-flex flex-column align-items-lg-end gap-2 mt-2 mt-lg-0">
                            <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                                <template x-if="filterMonth === 'per_bulan' && drilldownLevel === 3">
                                    <div class="d-flex align-items-center bg-light rounded-pill px-3 py-1 border border-secondary border-opacity-25 shadow-sm">
                                        <i class="bi bi-eye text-muted me-2"></i>
                                        <select x-model="detailChartType" class="form-select form-select-sm border-0 bg-transparent text-dark fw-bold shadow-none cursor-pointer pe-4" style="min-width: 170px; outline: none;">
                                            <option value="bar">Grafik Batang</option>
                                            <option value="heatmap">Grafik Matriks (Heatmap)</option>
                                        </select>
                                    </div>
                                </template>

                                <div class="bg-light p-1 rounded-pill d-flex border">
                                    <button @click="drilldownMetric = 'kegiatan'" :class="drilldownMetric === 'kegiatan' ? 'btn-success text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-3 border-0">Total Kegiatan</button>
                                    <button @click="drilldownMetric = 'peserta'" :class="drilldownMetric === 'peserta' ? 'btn-primary text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-3 border-0">Peserta (Orang)</button>
                                </div>
                            </div>
                            
                            <button x-show="drilldownLevel > 1" @click="goBack()" class="btn btn-sm btn-outline-secondary rounded-pill fw-bold px-3 shadow-sm align-self-end mt-1">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Level <span x-text="drilldownLevel - 1"></span>
                            </button>
                        </div>
                    </div>

                    <div class="card-body px-4 pb-4 pt-2">
                        <p x-show="drilldownLevel < 3 && tableData.length > 0" class="text-muted small mb-3">
                            <i class="bi bi-info-circle me-1"></i> Tips Interaktif: Silakan <b>klik salah satu batang grafik</b> di bawah ini untuk melihat rincian datanya lebih dalam.
                        </p>

                        <div class="w-100 custom-scrollbar pb-2" x-show="tableData.length > 0" style="overflow-x: auto;">
                            <div x-ref="chartDetail" style="min-width: 900px;"></div>
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
            
            // Struktur Data Awal harus Array Object agar tidak bentrok
            cards: { 
                kegiatan: { total: 0 }, 
                orang: { total: 0, edukasi: [], psm: [], pa: [] }, 
                media: { total_freq: 0, total_durasi: 0, list: [] }, 
                wilayah: { total: 0 }
            },
            
            detailType: 'informasi_edukasi', 
            tabTrend: 'kegiatan', 
            tabComp: 'anggaran', 
            compToggle: 'all',
            adminTrendType: 'bar', 
            detailChartType: 'bar', 
            
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
            
            getColors() { 
                return ['#0d6efd', '#198754', '#fd7e14', '#6f42c1', '#0dcaf0', '#e83e8c', '#20c997', '#ffc107', '#dc3545']; 
            },

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
                this.$watch('detailChartType', () => this.renderDetailChart());
            },

            formatAngka(num) { 
                return !num ? "0" : new Intl.NumberFormat('id-ID').format(num); 
            },
            
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
                    return `Total Akumulasi (Tahun ${this.globalYear})`; 
                } else if (this.filterMonth === 'per_bulan') {
                    return `Tren Pergerakan 12 Bulan (Tahun ${this.globalYear})`;
                } else {
                    const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                    return `Bulan ${months[this.filterMonth - 1]} ${this.globalYear}`;
                }
            },
            
            get dynamicTrendMetric() { 
                return this.tabTrend === 'kegiatan' ? 'Jumlah Kegiatan' : 'Jumlah Orang / Peserta'; 
            },
            
            get dynamicCompMetric() { 
                const maps = { 'anggaran': 'Anggaran Pelaksanaan', 'sasaran': 'Sasaran Wilayah', 'kategori': 'Kategori Kegiatan', 'sub_kegiatan': 'Sub Kegiatan' };
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
                    .then(r => r.json())
                    .then(res => { 
                        this.cards = res; 
                        this.renderRanking(res.ranking_chart); 
                    }); 
            },
            
            fetchDetail() { 
                fetch(`{{ route('dashboard.p2m.api.chart') }}?type=${this.detailType}&year=${this.globalYear}&month=${this.filterMonth}&satker_id=${this.globalSatkerId}`)
                    .then(r => r.json())
                    .then(res => { 
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
                if(!data || !data.labels || data.labels.length === 0) return;

                let opts = { 
                    series: [{ name: 'Total Kegiatan', data: data.data }], 
                    chart: { type: 'bar', height: 400, toolbar: { show: false }, fontFamily: 'inherit' }, 
                    plotOptions: { bar: { horizontal: true, distributed: true, borderRadius: 4 } }, 
                    xaxis: { 
                        categories: data.labels, 
                        tickAmount: 2, 
                        labels: { formatter: v => Math.round(v) } 
                    }, 
                    colors: this.getColors(),
                    dataLabels: { enabled: true, formatter: v => new Intl.NumberFormat('id-ID').format(v) }, 
                    grid: { show: true, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } }, 
                    tooltip: { y: { formatter: v => new Intl.NumberFormat('id-ID').format(v) + ' Kegiatan' } },
                    legend: { show: false }
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
                    if (opts.plotOptions.bar.distributed) { opts.colors = this.getColors(); opts.legend = { show: false }; } 
                    else if (this.isMultiSatker) { opts.colors = this.getColors(); opts.legend = { position: 'top', fontWeight: 'bold', offsetY: -10 }; } 
                    else { opts.colors = ['#0d6efd']; opts.legend = { show: false }; }

                    opts.xaxis = { categories: this.rawData.trend_labels, labels: { formatter: (v) => typeof v === 'number' ? Math.round(v) : v } };
                    opts.yaxis = { labels: { formatter: v => Math.round(v), style: { fontWeight: 'bold' } } };
                    opts.stroke = { show: true, width: 2, colors: ['#ffffff'] };
                    opts.tooltip = { shared: true, intersect: false, y: { formatter: (val) => new Intl.NumberFormat('id-ID').format(val) + ' ' + metricLabel } };
                    opts.dataLabels = { enabled: false };
                }
                
                if (this.chartInst.trend) this.chartInst.trend.destroy(); 
                this.chartInst.trend = new ApexCharts(this.$refs.chartTrend, opts); 
                this.chartInst.trend.render();
            },

            renderComp() {
                if (!this.$refs.chartComp || !this.rawData || !this.tabComp) return;
                let compData = this.rawData.comp[this.tabComp];
                if (!compData) return;

                let isPerBulan = this.filterMonth === 'per_bulan';
                let isMulti = this.isMultiSatker;
                let container = this.$refs.chartComp;

                if (this.chartInst.comp) {
                    if (Array.isArray(this.chartInst.comp)) { this.chartInst.comp.forEach(c => c.destroy()); } 
                    else { this.chartInst.comp.destroy(); }
                }
                this.chartInst.comp = [];
                container.innerHTML = ''; 

                let colors = this.getColors();

                if (isPerBulan && isMulti && this.compToggle === 'all') {
                    container.classList.remove('custom-scrollbar');
                    container.style.maxHeight = 'none'; 
                    container.style.overflowY = 'visible';

                    let months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                    let satkerNames = [];
                    
                    compData.options.forEach(opt => {
                        (compData.detailed[opt.id] || []).forEach(s => { 
                            if (!satkerNames.includes(s.name)) satkerNames.push(s.name); 
                        });
                    });
                    // satkerNames.sort();

                    let legendDiv = document.createElement('div');
                    legendDiv.className = 'd-flex flex-wrap justify-content-center gap-3 mb-4 pb-3 border-bottom border-light';
                    compData.options.forEach((opt, idx) => {
                        let c = colors[idx % colors.length];
                        legendDiv.innerHTML += `<div class="d-flex align-items-center"><span style="width:14px;height:14px;background-color:${c};border-radius:4px;margin-right:6px;box-shadow: 0 1px 2px rgba(0,0,0,0.1);"></span><span class="small fw-bold text-secondary">${opt.label}</span></div>`;
                    });
                    container.appendChild(legendDiv);

                    let gridRow = document.createElement('div');
                    gridRow.className = 'row g-3';
                    container.appendChild(gridRow);

                    satkerNames.forEach(sName => {
                        let satkerSeries = [];
                        compData.options.forEach(opt => {
                            let match = (compData.detailed[opt.id] || []).find(s => s.name === sName);
                            let processedData = (match ? match.data : new Array(12).fill(0)).map(v => v > 0 ? v : null); 
                            satkerSeries.push({ name: opt.label, data: processedData });
                        });

                        let colDiv = document.createElement('div');
                        colDiv.className = 'col-md-6 col-xl-4';
                        
                        let cardDiv = document.createElement('div');
                        cardDiv.className = 'card border-0 shadow-sm h-100 bg-white rounded-3 border-top border-3 border-primary';
                        
                        let cardHeader = document.createElement('div');
                        cardHeader.className = 'card-header bg-transparent border-0 pt-3 pb-0 text-center';
                        cardHeader.innerHTML = `<h6 class="mb-0 fw-bold text-dark"><i class="bi bi-building me-2 text-muted"></i>${sName}</h6>`;
                        
                        let cardBody = document.createElement('div');
                        cardBody.className = 'card-body p-2';
                        
                        let chartDiv = document.createElement('div');
                        chartDiv.style.minHeight = '250px';

                        cardBody.appendChild(chartDiv);
                        cardDiv.appendChild(cardHeader);
                        cardDiv.appendChild(cardBody);
                        colDiv.appendChild(cardDiv);
                        gridRow.appendChild(colDiv);

                        let opts = {
                            series: satkerSeries,
                            chart: { type: 'bar', height: 280, stacked: true, toolbar: { show: false }, fontFamily: 'inherit' },
                            colors: colors,
                            plotOptions: { bar: { horizontal: false, columnWidth: '75%', borderRadius: 2 } },
                            dataLabels: { enabled: false },
                            stroke: { show: true, width: 1, colors: ['#fff'] },
                            xaxis: { categories: months, labels: { style: { fontSize: '10px', fontWeight: 'bold' } } },
                            yaxis: { labels: { formatter: v => v ? Math.round(v) : '', style: { fontSize: '11px', fontWeight: 'bold' } } },
                            legend: { show: false }, 
                            tooltip: { 
                                shared: true, intersect: false, 
                                y: { formatter: v => v ? new Intl.NumberFormat('id-ID').format(v) + " Kegiatan" : '' } 
                            }
                        };
                        
                        let chart = new ApexCharts(chartDiv, opts);
                        chart.render();
                        this.chartInst.comp.push(chart);
                    });
                } 
                else {
                    container.classList.remove('custom-scrollbar');
                    container.style.maxHeight = 'none';
                    container.style.overflowY = 'visible';
                    
                    let dataSeries = []; let chartType = 'bar'; let isStacked = false; let isHorizontal = false; let labels = [];

                    if (this.compToggle === 'all') {
                        if (!isPerBulan && !isMulti) {
                            chartType = 'donut';
                            dataSeries = compData.aggregated.map(s => s.data[0] || 0);
                            labels = compData.aggregated.map(s => s.name);
                            if (this.tabComp === 'anggaran') colors = compData.aggregated.map(s => s.name === 'DIPA' ? '#198754' : '#fd7e14');
                        } else {
                            dataSeries = compData.aggregated; labels = this.rawData.comp_labels; isStacked = true;
                            if (this.tabComp === 'anggaran') colors = ['#198754', '#fd7e14'];
                        }
                    } else {
                        let optLabel = compData.options.find(o => o.id === this.compToggle)?.label || this.compToggle;
                        if (isPerBulan && isMulti) {
                            dataSeries = compData.detailed[this.compToggle] || [];
                            labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                            colors = this.getColors();
                        } else {
                            let found = compData.aggregated.find(s => s.name === optLabel);
                            dataSeries = found ? [found] : []; labels = this.rawData.comp_labels;
                            if (this.tabComp === 'anggaran') colors = optLabel === 'DIPA' ? ['#198754'] : ['#fd7e14'];
                            else colors = ['#0d6efd'];
                        }
                    }

                    let chartDiv = document.createElement('div'); container.appendChild(chartDiv);
                    let opts = { 
                        series: dataSeries, 
                        chart: { type: chartType, height: 450, stacked: isStacked, toolbar: { show: true }, fontFamily: 'inherit' }, 
                        colors: colors, 
                        title: { text: `${this.dynamicCompMetric} - ${this.detailTypeName} (${this.timeLabelText})`, align: 'center', margin: 20, style: { fontSize: '18px', fontWeight: '500' } }, 
                    };

                    if (chartType === 'donut') {
                        opts.labels = labels; opts.plotOptions = { pie: { donut: { size: '65%' } } };
                        opts.dataLabels = { enabled: true, formatter: function (val, opts) { return opts.w.config.series[opts.seriesIndex] + " Kegiatan (" + val.toFixed(1) + "%)" } };
                        opts.legend = { position: 'bottom', fontSize: '14px', fontWeight: 'bold' };
                        opts.tooltip = { y: { formatter: (val) => val + ' Kegiatan' } };
                    } else {
                        opts.plotOptions = { bar: { horizontal: isHorizontal, borderRadius: 2, columnWidth: '70%', barHeight: '70%' } };
                        opts.xaxis = { categories: labels, labels: { style: { fontSize: '12px', fontWeight: 'bold' } } };
                        opts.yaxis = { labels: { formatter: v => Math.round(v), style: { fontSize: '13px', fontWeight: 'bold' } } };
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

            renderDetailChart() {
                if (!this.$refs.chartDetail || this.tableData.length === 0) return;
                
                let isPeserta = this.drilldownMetric === 'peserta';
                let isPerBulan = this.filterMonth === 'per_bulan';
                let isMulti = this.isMultiSatker;
                
                let series = [];
                let categories = [];
                let colors = this.getColors();
                
                let opts = {
                    chart: {
                        toolbar: { show: false },
                        fontFamily: 'inherit',
                        events: {
                            dataPointSelection: (event, chartContext, config) => {
                                if(!isMulti && this.drilldownLevel === 2) return;
                                if(this.drilldownLevel === 3) return;

                                let clickedName = config.w.globals.labels[config.dataPointIndex];
                                
                                if (this.drilldownLevel === 1) {
                                    this.selectedKategori = clickedName;
                                    this.drilldownLevel = 2;
                                    this.renderDetailChart();
                                } else if (this.drilldownLevel === 2) {
                                    this.selectedKegiatan = clickedName;
                                    this.drilldownLevel = 3;
                                    this.renderDetailChart();
                                }
                            }
                        }
                    },
                    dataLabels: { enabled: false },
                    legend: { position: 'top', horizontalAlign: 'left', fontWeight: 'bold' },
                    tooltip: {
                        shared: true,
                        intersect: false,
                        custom: function({series, seriesIndex, dataPointIndex, w}) {
                            let category = w.globals.labels[dataPointIndex] || '';
                            let total = 0;
                            let hasData = false;

                            let isDistributed = w.config.plotOptions && w.config.plotOptions.bar && w.config.plotOptions.bar.distributed;

                            let html = '<div style="font-family: inherit; font-size: 13px; line-height: 1.5;">';
                            html += '<div style="font-weight: 600; padding: 8px 12px; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">' + category + '</div>';
                            html += '<div style="padding: 8px 12px; display: flex; flex-direction: column; gap: 6px;">';

                            w.globals.seriesNames.forEach((name, i) => {
                                let rawDataArray = w.config.series[i].data;
                                let val = rawDataArray ? rawDataArray[dataPointIndex] : null;

                                if (val !== undefined && val !== null && val > 0) {
                                    hasData = true;
                                    total += val;
                                    
                                    let color = isDistributed ? w.globals.colors[dataPointIndex] : w.globals.colors[i];
                                    let isPos = name.includes('Positif');
                                    let displayName = name.replace(' (Negatif/Aman)', ''); 
                                    let suffix = isPeserta ? (isPos ? ' Orang Positif' : ' Orang') : ' Kegiatan';

                                    html += '<div style="display:flex; align-items:center; justify-content: space-between; gap: 20px;">';
                                    html += '<div style="display:flex; align-items:center;">';
                                    html += '<span style="display:inline-block; width:10px; height:10px; border-radius:50%; background-color:' + color + '; margin-right:8px; flex-shrink:0;"></span>';
                                    html += '<span style="color: #495057;">' + displayName + '</span>';
                                    html += '</div>';
                                    html += '<b style="color: #212529; text-align: right;">' + new Intl.NumberFormat('id-ID').format(val) + suffix + '</b>';
                                    html += '</div>';
                                }
                            });

                            let suffixTotal = isPeserta ? ' Orang' : ' Kegiatan';
                            html += '<div style="margin-top: 4px; padding-top: 6px; border-top: 1px dashed #e9ecef; display:flex; justify-content: space-between; align-items:center; gap: 20px;">';
                            html += '<span style="font-weight: 600; color: #212529;">Total Keseluruhan</span>';
                            html += '<b style="color: #0d6efd; font-size: 14px; text-align: right;">' + new Intl.NumberFormat('id-ID').format(total) + suffixTotal + '</b>';
                            html += '</div>';

                            html += '</div></div>';

                            if (!hasData) return ''; 
                            return '<div style="background: #fff; border: 1px solid #e3e6f0; border-radius: 4px; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);">' + html + '</div>';
                        }
                    }
                };

                let allSatkers = [...new Set(this.tableData.map(r => r.satker))];

                if (this.drilldownLevel === 1) {
                    categories = [...new Set(this.tableData.map(r => r.kategori))];
                    
                    series = allSatkers.map(s => {
                        let data = categories.map(cat => {
                            let sum = 0;
                            let hasData = false;
                            this.tableData.filter(r => r.satker === s && r.kategori === cat).forEach(r => {
                                sum += isPeserta ? r.peserta : r.frekuensi;
                                hasData = true;
                            });
                            return (hasData && sum > 0) ? sum : null; 
                        });
                        return { name: s, data: data };
                    });

                    opts.series = series;
                    opts.chart.type = 'bar';
                    opts.chart.stacked = true;
                    opts.chart.height = Math.max(300, categories.length * 80);
                    opts.plotOptions = { bar: { horizontal: true, barHeight: '70%', borderRadius: 2, cursor: 'pointer' } };
                    opts.colors = colors;
                    opts.xaxis = { categories: categories, labels: { formatter: v => Math.round(v) } };
                    opts.yaxis = { labels: { style: { fontSize: '12px', fontWeight: '600' }, maxWidth: 400 } };
                    opts.stroke = { show: true, width: 1, colors: ['#fff'] };
                } 
                else if (this.drilldownLevel === 2) {
                    let filteredData = this.tableData.filter(r => r.kategori === this.selectedKategori);
                    categories = [...new Set(filteredData.map(r => r.nama))];
                    
                    series = allSatkers.map(s => {
                        let data = categories.map(keg => {
                            let sum = 0;
                            let hasData = false;
                            filteredData.filter(r => r.satker === s && r.nama === keg).forEach(r => {
                                sum += isPeserta ? r.peserta : r.frekuensi;
                                hasData = true;
                            });
                            return (hasData && sum > 0) ? sum : null;
                        });
                        return { name: s, data: data };
                    });

                    opts.series = series;
                    opts.chart.type = 'bar';
                    opts.chart.stacked = true;
                    opts.chart.height = Math.max(300, categories.length * 80);
                    opts.plotOptions = { bar: { horizontal: true, barHeight: '70%', borderRadius: 2, cursor: isMulti ? 'pointer' : 'default' } };
                    opts.colors = colors;
                    opts.xaxis = { categories: categories, labels: { formatter: v => Math.round(v) } };
                    opts.yaxis = { labels: { style: { fontSize: '12px', fontWeight: '600' }, maxWidth: 400 } };
                    opts.stroke = { show: true, width: 1, colors: ['#fff'] };
                } 
                else if (this.drilldownLevel === 3) {
                    let filteredData = this.tableData.filter(r => r.kategori === this.selectedKategori && r.nama === this.selectedKegiatan);
                    let showPositif = isPeserta && this.selectedKegiatan.toLowerCase().includes('tes urine');

                    if (isPerBulan) {
                        let isHeatmap = this.detailChartType === 'heatmap';
                        categories = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                        
                        if (showPositif && !isHeatmap) {
                            series = [];
                            let dynamicColors = [];

                            let customLegendLabels = allSatkers.slice();
                            customLegendLabels.push('Indikasi Positif');

                            let customLegendColors = allSatkers.map((s, i) => colors[i % colors.length]);
                            customLegendColors.push('#dc3545');

                            allSatkers.forEach((s, i) => {
                                let dataNegatif = new Array(12).fill(null);
                                let dataPositif = new Array(12).fill(null);
                                
                                filteredData.filter(r => r.satker === s).forEach(r => {
                                    let m = parseInt(r.bulan) - 1;
                                    if(m>=0 && m<12) {
                                        let pos = r.positif || 0;
                                        let tot = r.peserta || 0;
                                        let neg = Math.max(0, tot - pos); 

                                        if (pos > 0) {
                                            if (dataPositif[m] === null) dataPositif[m] = 0;
                                            dataPositif[m] += pos;
                                        }
                                        if (neg > 0) {
                                            if (dataNegatif[m] === null) dataNegatif[m] = 0;
                                            dataNegatif[m] += neg;
                                        }
                                    }
                                });

                                series.push({ name: s, group: s, data: dataNegatif });
                                series.push({ name: s + ' (Positif)', group: s, data: dataPositif });
                                
                                dynamicColors.push(colors[i % colors.length]); 
                                dynamicColors.push('#dc3545'); 
                            });

                            opts.colors = dynamicColors;
                            opts.chart.stacked = true;
                            
                            opts.legend = {
                                position: 'top',
                                horizontalAlign: 'left',
                                fontWeight: 'bold',
                                customLegendItems: customLegendLabels, 
                                markers: { fillColors: customLegendColors }
                            };

                        } else {
                            series = allSatkers.map(s => {
                                let data = new Array(12).fill(null);
                                filteredData.filter(r => r.satker === s).forEach(r => {
                                    let m = parseInt(r.bulan) - 1;
                                    if(m>=0 && m<12) {
                                        if (data[m] === null) data[m] = 0;
                                        data[m] += isPeserta ? r.peserta : r.frekuensi;
                                    }
                                });
                                return { name: s, data: data };
                            });
                            opts.colors = colors;
                            opts.chart.stacked = false;
                        }

                        opts.series = series; 
                        opts.chart.type = isHeatmap ? 'heatmap' : 'bar';
                        opts.xaxis = { categories: categories, labels: { style: { fontWeight: 'bold' } } };
                        
                        if (isHeatmap) {
                            opts.plotOptions = { heatmap: { shadeIntensity: 0.6, radius: 4, useFillColorAsStroke: false } };
                            opts.dataLabels = { 
                                enabled: true, 
                                formatter: (val) => val > 0 ? new Intl.NumberFormat('id-ID').format(val) : "", 
                                style: { colors: ['#212529'], fontSize: '12px' } 
                            };
                            opts.colors = isPeserta ? ['#0d6efd'] : ['#198754'];
                            opts.legend = { show: false };
                            opts.chart.height = Math.max(400, (allSatkers.length || 1) * 55);
                            
                            opts.tooltip = {
                                shared: false,
                                y: { formatter: v => v ? new Intl.NumberFormat('id-ID').format(v) : '' }
                            };
                        } else {
                            opts.plotOptions = { 
                                bar: { horizontal: false, columnWidth: '75%', borderRadius: 2 } 
                            };
                            opts.stroke = { show: true, width: 1, colors: ['transparent'] };
                            opts.chart.height = 450;
                            opts.yaxis = { labels: { formatter: v => Math.round(v), style: { fontWeight: 'bold' } } };
                        }
                    } else {
                        categories = [...new Set(filteredData.map(r => r.satker))];
                        
                        if (showPositif) {
                            let sNegatif = categories.map(s => {
                                let sum = 0;
                                let hasData = false;
                                filteredData.filter(r => r.satker === s).forEach(r => { 
                                    let pos = r.positif || 0;
                                    sum += Math.max(0, r.peserta - pos); 
                                    hasData = true;
                                });
                                return (hasData && sum > 0) ? sum : null;
                            });
                            
                            let sPositif = categories.map(s => {
                                let sum = 0;
                                let hasData = false;
                                filteredData.filter(r => r.satker === s).forEach(r => { 
                                    sum += (r.positif||0); 
                                    if(sum > 0) hasData = true;
                                });
                                return (hasData && sum > 0) ? sum : null;
                            });
                            
                            opts.series = [{ name: 'Peserta', data: sNegatif }, { name: 'Indikasi Positif', data: sPositif }];
                            opts.colors = ['#0d6efd', '#dc3545'];
                            opts.chart.stacked = true; 
                            opts.plotOptions = { bar: { horizontal: true, barHeight: '70%', borderRadius: 4 } };
                            opts.legend = { show: true, position: 'top' };
                        } else {
                            let s1 = categories.map(s => {
                                let sum = 0;
                                let hasData = false;
                                filteredData.filter(r => r.satker === s).forEach(r => { 
                                    sum += isPeserta ? r.peserta : r.frekuensi; 
                                    hasData = true;
                                });
                                return (hasData && sum > 0) ? sum : null;
                            });
                            
                            opts.series = [{ name: isPeserta ? 'Peserta' : 'Kegiatan', data: s1 }];
                            opts.colors = colors;
                            opts.chart.stacked = false;
                            opts.plotOptions = { bar: { horizontal: true, barHeight: '70%', borderRadius: 4, distributed: true } };
                            opts.legend = { show: false };
                        }

                        opts.chart.type = 'bar';
                        opts.chart.height = Math.max(300, categories.length * 80);
                        opts.xaxis = { categories: categories, labels: { formatter: v => Math.round(v) } };
                        opts.yaxis = { labels: { style: { fontSize: '12px', fontWeight: '600' }, maxWidth: 400 } };
                    }
                }

                if (this.chartInst.detail) this.chartInst.detail.destroy();
                this.chartInst.detail = new ApexCharts(this.$refs.chartDetail, opts);
                this.chartInst.detail.render();
            }
        }));
    });
</script>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 8px; height: 8px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
</style>
@endpush