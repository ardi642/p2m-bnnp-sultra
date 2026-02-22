@extends('admin')

@section('content')
<main class="admin-main bg-light" x-data="dashboardP2M()" x-init="init()" style="min-height: 100vh;">
    <div class="container-fluid p-4">

        {{-- A. HEADER & IDENTITAS --}}
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3">
            <div>
                <h1 class="h3 mb-2 fw-bold text-dark">Dashboard Kinerja P2M</h1>
                <div class="mt-2">
                    @if(auth()->user()->role === 'admin')
                        {{-- Dropdown Satker (Pill Style) --}}
                        <div class="d-flex align-items-center bg-light rounded-pill px-3 py-2 shadow-sm w-auto" 
                             style="max-width: max-content;">
                            <i class="bi bi-building-fill text-muted me-2"></i>
                            <select x-model="globalSatkerId" 
                                    class="form-select border-0 bg-transparent text-dark fw-bold shadow-none p-0 pe-4 cursor-pointer" 
                                    style="font-size: 1.1rem; outline: none; min-width: 300px;">
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
            
            @if($showTabs)
            <div class="btn-group shadow-sm">
                <a href="{{ route('dashboard.p2m.index') }}" class="btn btn-primary fw-bold px-4">
                    <i class="bi bi-megaphone-fill me-1"></i> P2M
                </a>
            </div>
            @endif
        </div>

        {{-- FILTER GLOBAL WAKTU --}}
        <div class="d-flex justify-content-end mb-3">
            <div class="d-flex align-items-center bg-white p-2 rounded-3 shadow-sm gap-2">
                <span class="fw-bold text-muted small ms-2">
                    <i class="bi bi-calendar-range me-2 text-primary"></i>Akumulasi:
                </span>
                <select x-model="globalStartYear" 
                        class="form-select form-select-sm border-0 bg-light fw-bold text-dark w-auto shadow-none">
                    @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                </select>
                <span class="fw-bold text-muted">-</span>
                <select x-model="globalEndYear" 
                        class="form-select form-select-sm border-0 bg-light fw-bold text-dark w-auto me-1 shadow-none">
                    @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                </select>
            </div>
        </div>

        {{-- B. KARTU UTAMA --}}
        <div class="row g-3 mb-4">
            {{-- Kartu 1: Total Kegiatan --}}
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-white border-start border-4 border-primary rounded-3">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-4">
                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle mb-3"><i class="bi bi-layers-fill fs-2"></i></div>
                        <h6 class="text-uppercase text-muted fw-bold mb-2">Total Kegiatan P2M</h6>
                        <h1 class="display-4 fw-bold text-dark mb-0" x-text="formatAngka(cards.kegiatan.total)">0</h1>
                    </div>
                </div>
            </div>
            {{-- Kartu 2: Masyarakat Terlayani --}}
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-white border-start border-4 border-success rounded-3 overflow-hidden">
                    <div class="card-header bg-white border-0 pt-4 pb-2 d-flex align-items-center gap-3">
                        <div class="bg-success bg-opacity-10 text-success p-2 rounded-3"><i class="bi bi-people-fill fs-4"></i></div>
                        <div><h6 class="text-muted fw-bold mb-0">Masyarakat Terlayani</h6><h3 class="fw-bold text-dark mb-0"><span x-text="formatAngka(cards.orang.total)">0</span> <span class="fs-6 text-muted fw-normal">Orang</span></h3></div>
                    </div>
                    <div class="card-body pt-0 overflow-auto" style="max-height: 220px;">
                        <template x-for="(item, label) in cards.orang.list">
                            <div x-show="(item.val || item) > 0" class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                <span class="small fw-bold text-secondary" x-text="label"></span>
                                <div class="text-end"><span class="fw-bold text-success" x-text="formatAngka(item.val || item)"></span><template x-if="item.is_tes_urine"><div class="lh-1 mt-1"><span class="badge bg-danger-subtle text-danger border border-danger">Positif: <span x-text="item.positif"></span></span></div></template></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            {{-- Kartu 3: Total Publikasi Media --}}
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-white border-start border-4 border-warning rounded-3 overflow-hidden">
                    <div class="card-header bg-white border-0 pt-4 pb-2 d-flex align-items-center gap-3">
                        <div class="bg-warning bg-opacity-10 text-warning p-2 rounded-3"><i class="bi bi-megaphone-fill fs-4 text-dark"></i></div>
                        <div><h6 class="text-muted fw-bold mb-0">Total Publikasi Media</h6><h3 class="fw-bold text-dark mb-0"><span x-text="formatAngka(cards.media.total_freq)">0</span> <span class="fs-6 text-muted fw-normal">Publikasi</span></h3></div>
                    </div>
                    <div class="card-body pt-0 d-flex flex-column justify-content-center">
                        <div class="mb-3"><span class="badge bg-light text-dark border px-3 py-2">Total Durasi Tayang: <span class="fw-bold text-primary" x-text="formatAngka(cards.media.total_durasi) + ' Hari'"></span></span></div>
                        <template x-for="(item, label) in cards.media.list"><div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2"><span class="small fw-bold text-secondary" x-text="label"></span><div class="text-end fw-bold text-dark"><span x-text="item.freq + ' x'"></span> <small class="text-muted fw-normal ms-1" x-text="'(' + item.durasi + ' Hari)'"></small></div></div></template>
                    </div>
                </div>
            </div>
            {{-- Kartu 4: Kawasan Binaan --}}
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-white border-start border-4 border-info rounded-3 overflow-hidden">
                    <div class="card-header bg-white border-0 pt-4 pb-2 d-flex align-items-center gap-3">
                        <div class="bg-info bg-opacity-10 text-info p-2 rounded-3"><i class="bi bi-geo-alt-fill fs-4 text-dark"></i></div>
                        <div><h6 class="text-muted fw-bold mb-0">Kawasan Binaan</h6><h3 class="fw-bold text-dark mb-0"><span x-text="formatAngka(cards.wilayah.total)">0</span> <span class="fs-6 text-muted fw-normal">Kawasan</span></h3></div>
                    </div>
                    <div class="card-body pt-0 d-flex flex-column justify-content-center">
                        <template x-for="(val, label) in cards.wilayah.list"><div class="d-flex justify-content-between align-items-center border-bottom py-3"><span class="small fw-bold text-secondary" x-text="label"></span><span class="h5 fw-bold text-info mb-0" x-text="formatAngka(val)"></span></div></template>
                    </div>
                </div>
            </div>
        </div>

        {{-- C. RANKING CHART --}}
        <div class="card border-0 shadow-sm mb-5 bg-white rounded-3">
            <div class="card-body p-4">
                <div x-ref="rankingChart" style="min-height: 400px;"></div>
            </div>
        </div>

        {{-- D. PUSAT ANALISIS KINERJA --}}
        <div class="bg-white p-4 rounded-4 shadow-sm mb-4">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-4">
                
                <div>
                    <h5 class="m-0 fw-bold text-dark">
                        <i class="bi bi-display me-2 text-primary"></i>Analisis Kinerja Detail
                    </h5>
                    <small class="text-muted">Pilih parameter di bawah untuk mengubah analitik secara instan.</small>
                </div>
                
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    
                    {{-- Dropdown Kegiatan --}}
                    <div class="d-flex align-items-center bg-light rounded-3 px-3 py-1">
                        <span class="text-muted small fw-bold me-2">Kegiatan:</span>
                        <select x-model="detailType" 
                                class="form-select border-0 bg-transparent fw-bold text-dark shadow-none cursor-pointer" 
                                style="min-width: 200px; outline: none;">
                            <option value="informasi_edukasi">Informasi & Edukasi</option>
                            <option value="media_elektronik">Media Elektronik</option>
                            <option value="media_non_elektronik">Media Non-Elektronik</option>
                            <option value="media_online">Media Online</option>
                            <option value="tes_urine">Tes Urine</option>
                            <option value="desa_bersinar">Desa/Kelurahan Bersinar</option>
                            <option value="asistensi">Asistensi Relawan</option>
                            <option value="pelatihan">Pelatihan Soft Skill</option>
                            <option value="keluarga">Ketahanan Keluarga</option>
                            <option value="monev">Monitoring & Evaluasi</option>
                            <option value="pemetaan">Pemetaan SDM/SDA</option>
                            <option value="ikan">IKAN</option>
                        </select>
                    </div>

                    {{-- Mode Waktu --}}
                    <div class="d-flex align-items-center bg-light rounded-3 px-3 py-1">
                        <span class="text-muted small fw-bold me-2">Waktu:</span>
                        <select x-model="detailMode" 
                                class="form-select border-0 bg-transparent fw-bold text-dark shadow-none w-auto cursor-pointer">
                            <option value="monthly">Per Bulan</option>
                            <option value="yearly">Rentang Tahun</option>
                        </select>
                        
                        {{-- Mode Bulanan --}}
                        <template x-if="detailMode === 'monthly'">
                            <select x-model="detailMonthYear" 
                                    class="form-select border-0 bg-transparent fw-bold text-dark shadow-none w-auto ms-1 cursor-pointer">
                                @foreach($years as $y) 
                                    <option value="{{ $y }}">{{ $y }}</option> 
                                @endforeach
                            </select>
                        </template>

                        {{-- Mode Tahunan --}}
                        <template x-if="detailMode === 'yearly'">
                            <div class="d-flex align-items-center ms-1">
                                <select x-model="detailYearStart" 
                                        class="form-select border-0 bg-transparent fw-bold text-dark shadow-none w-auto cursor-pointer p-1">
                                    @foreach($years as $y) 
                                        <option value="{{ $y }}">{{ $y }}</option> 
                                    @endforeach
                                </select>
                                <span class="text-muted fw-bold">-</span>
                                <select x-model="detailYearEnd" 
                                        class="form-select border-0 bg-transparent fw-bold text-dark shadow-none w-auto cursor-pointer p-1">
                                    @foreach($years as $y) 
                                        <option value="{{ $y }}">{{ $y }}</option> 
                                    @endforeach
                                </select>
                            </div>
                        </template>
                    </div>

                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            {{-- Grafik 1: TREN KINERJA --}}
            <div class="col-12">
                <div class="card border-0 shadow-sm h-100 bg-white rounded-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        
                        <div>
                            {{-- Dropdown Tipe Chart Admin --}}
                            <template x-if="isMultiSatker">
                                <div class="d-flex align-items-center bg-light rounded-pill px-3 py-1">
                                    <i class="bi bi-eye text-muted me-2"></i>
                                    <select x-model="adminTrendType" 
                                            class="form-select form-select-sm border-0 bg-transparent text-dark fw-bold shadow-none cursor-pointer" 
                                            style="min-width: 180px;">
                                        <option value="bar">Grafik Batang (Default)</option>
                                        <option value="heatmap">Grafik Matriks (Heatmap)</option>
                                    </select>
                                </div>
                            </template>
                        </div>

                        {{-- Tab Kendali Tren (Pill Style) --}}
                        <div class="d-flex bg-light p-1 rounded-pill">
                            <button @click="tabTrend = 'kegiatan'" 
                                    :class="tabTrend === 'kegiatan' ? 'btn-primary text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" 
                                    class="btn btn-sm rounded-pill fw-bold px-4 border-0">
                                Kegiatan
                            </button>
                            
                            <button x-show="config.unit !== '-'" 
                                    @click="tabTrend = 'peserta'" 
                                    :class="tabTrend === 'peserta' ? 'btn-primary text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" 
                                    class="btn btn-sm rounded-pill fw-bold px-4 border-0">
                                <span x-text="config.unit"></span>
                            </button>
                            
                            <button x-show="config.has_positif" 
                                    @click="tabTrend = 'positif'" 
                                    :class="tabTrend === 'positif' ? 'btn-danger text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" 
                                    class="btn btn-sm rounded-pill fw-bold px-4 border-0">
                                Positif
                            </button>
                        </div>
                    </div>
                    
                    <div class="card-body px-4 pb-4 pt-0">
                        <div x-ref="chartTrend" style="min-height: 450px;"></div>
                    </div>
                </div>
            </div>
            
            {{-- Grafik 2: PROPORSI KINERJA --}}
            <div class="col-12" x-show="config.has_anggaran || config.has_sasaran">
                <div class="card border-0 shadow-sm h-100 bg-white rounded-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        
                        <div>
                            {{-- Dropdown Filter Bulan Proporsi --}}
                            <template x-if="detailMode === 'monthly'">
                                <div class="d-flex align-items-center bg-light rounded-pill px-3 py-1">
                                    <i class="bi bi-filter text-muted me-2"></i>
                                    <select x-model="detailMonthMonth" 
                                            class="form-select form-select-sm border-0 bg-transparent text-dark fw-bold shadow-none cursor-pointer" 
                                            style="min-width: 150px;">
                                        <option value="all">Sepanjang Tahun</option>
                                        <option value="1">Bulan Januari</option><option value="2">Bulan Februari</option>
                                        <option value="3">Bulan Maret</option><option value="4">Bulan April</option>
                                        <option value="5">Bulan Mei</option><option value="6">Bulan Juni</option>
                                        <option value="7">Bulan Juli</option><option value="8">Bulan Agustus</option>
                                        <option value="9">Bulan September</option><option value="10">Bulan Oktober</option>
                                        <option value="11">Bulan November</option><option value="12">Bulan Desember</option>
                                    </select>
                                </div>
                            </template>
                        </div>

                        {{-- Tab Kendali Proporsi (Pill Style) --}}
                        <div class="d-flex bg-light p-1 rounded-pill">
                            <button x-show="config.has_anggaran" 
                                    @click="tabComp = 'anggaran'" 
                                    :class="tabComp === 'anggaran' ? 'btn-success text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" 
                                    class="btn btn-sm rounded-pill fw-bold px-4 border-0">
                                Anggaran
                            </button>
                            
                            <button x-show="config.has_sasaran" 
                                    @click="tabComp = 'sasaran'" 
                                    :class="tabComp === 'sasaran' ? 'btn-warning text-dark shadow-sm' : 'btn-light text-secondary bg-transparent'" 
                                    class="btn btn-sm rounded-pill fw-bold px-4 border-0">
                                Sasaran
                            </button>
                        </div>
                    </div>
                    
                    <div class="card-body px-4 pb-4 pt-0">
                        <div x-ref="chartComp" style="min-height: 450px;"></div>
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
            // Default awal mengambil data tertua hasil radar dari controller
            globalSatkerId: '', 
            globalStartYear: '{{ min($years) }}', 
            globalEndYear: '{{ max($years) }}',
            
            cards: { 
                kegiatan: { total: 0 }, 
                orang: { total: 0, list: {} }, 
                media: { total_freq: 0, total_durasi: 0, list: {} }, 
                wilayah: { total: 0, list: {} } 
            },
            
            detailType: 'informasi_edukasi', 
            detailMode: 'monthly', 
            detailMonthYear: '{{ max($years) }}', 
            detailMonthMonth: 'all', 
            
            detailYearStart: '{{ min($years) }}', 
            detailYearEnd: '{{ max($years) }}',
            
            tabTrend: 'kegiatan', 
            tabComp: 'anggaran', 
            adminTrendType: 'bar', 
            
            config: { unit: 'Peserta', has_anggaran: true, has_sasaran: true, has_positif: false },
            isMultiSatker: false, 
            rawData: null, 
            chartInst: { rank: null, trend: null, comp: null },
            
            satkerColors: ['#0d6efd', '#fd7e14', '#198754', '#6f42c1', '#dc3545', '#0dcaf0'],

            init() {
                this.fetchGlobal(); 
                this.fetchDetail();
                
                this.$watch('globalSatkerId', () => { 
                    this.fetchGlobal(); 
                    this.fetchDetail(); 
                });
                
                this.$watch('globalStartYear', () => { 
                    this.globalEndYear = Math.max(this.globalStartYear, this.globalEndYear); 
                    this.fetchGlobal(); 
                });
                
                this.$watch('globalEndYear', () => { 
                    this.globalStartYear = Math.min(this.globalStartYear, this.globalEndYear); 
                    this.fetchGlobal(); 
                });
                
                ['detailType', 'detailMode', 'detailMonthYear', 'detailMonthMonth', 'detailYearStart', 'detailYearEnd', 'adminTrendType']
                .forEach(p => {
                    this.$watch(p, () => this.fetchDetail());
                });
                
                this.$watch('tabTrend', () => this.renderTrend());
                this.$watch('tabComp', () => this.renderComp());
            },

            formatAngka(num) { 
                if (!num) return "0"; 
                return new Intl.NumberFormat('id-ID').format(num); 
            },

            get detailTypeName() {
                const types = {
                    'informasi_edukasi': 'Informasi & Edukasi', 
                    'media_elektronik': 'Media Elektronik', 
                    'media_non_elektronik': 'Media Non-Elektronik', 
                    'media_online': 'Media Online', 
                    'tes_urine': 'Tes Urine', 
                    'desa_bersinar': 'Desa/Kel. Bersinar', 
                    'asistensi': 'Asistensi Relawan', 
                    'pelatihan': 'Pelatihan Soft Skill', 
                    'keluarga': 'Ketahanan Keluarga', 
                    'monev': 'Monitoring & Evaluasi', 
                    'pemetaan': 'Pemetaan SDM/SDA', 
                    'ikan': 'IKAN'
                };
                return types[this.detailType] || 'Kegiatan';
            },

            get dynamicTrendMetric() { 
                if (this.tabTrend === 'kegiatan') return 'Jumlah Kegiatan';
                if (this.tabTrend === 'peserta') return 'Jumlah ' + this.config.unit;
                return 'Indikasi Positif';
            },

            get dynamicCompMetric() { 
                return this.tabComp === 'anggaran' ? 'Berdasarkan Anggaran' : 'Berdasarkan Sasaran Area'; 
            },

            get dynamicTrendTitle() { 
                if (this.detailMode === 'monthly') {
                    return '(Januari - Desember ' + this.detailMonthYear + ')';
                }
                return '(' + this.detailYearStart + ' s/d ' + this.detailYearEnd + ')'; 
            },

            get dynamicCompTitle() {
                if (this.detailMode === 'monthly') {
                    if (this.detailMonthMonth === 'all') {
                        return '(Akumulasi Tahun ' + this.detailMonthYear + ')';
                    }
                    const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                    return '(Bulan ' + months[parseInt(this.detailMonthMonth)-1] + ' ' + this.detailMonthYear + ')';
                }
                return '(' + this.detailYearStart + ' s/d ' + this.detailYearEnd + ')';
            },

            fetchGlobal() { 
                let url = `{{ route('dashboard.p2m.api.global') }}?start_year=${this.globalStartYear}&end_year=${this.globalEndYear}&satker_id=${this.globalSatkerId}`;
                fetch(url)
                    .then(r => r.json())
                    .then(res => { 
                        this.cards = res; 
                        this.renderRanking(res.ranking_chart); 
                    }); 
            },

            fetchDetail() {
                let url = `{{ route('dashboard.p2m.api.chart') }}?type=${this.detailType}&mode=${this.detailMode}&m_year=${this.detailMonthYear}&m_month=${this.detailMonthMonth}&y_start=${this.detailYearStart}&y_end=${this.detailYearEnd}&satker_id=${this.globalSatkerId}`;
                fetch(url)
                    .then(r => r.json())
                    .then(res => {
                        this.rawData = res; 
                        this.isMultiSatker = res.is_multi_satker; 
                        this.config = res.config;
                        
                        if (this.tabTrend === 'peserta' && this.config.unit === '-') this.tabTrend = 'kegiatan';
                        if (this.tabTrend === 'positif' && !this.config.has_positif) this.tabTrend = 'kegiatan';
                        
                        this.$nextTick(() => { 
                            this.renderTrend(); 
                            if(this.config.has_anggaran || this.config.has_sasaran) this.renderComp(); 
                        });
                    });
            },

            renderRanking(data) {
                let opts = { 
                    series: [{ name: 'Total Kegiatan', data: data.data }], 
                    chart: { type: 'bar', height: 400, toolbar: { show: false }, fontFamily: 'inherit' }, 
                    plotOptions: { bar: { horizontal: true, distributed: true, borderRadius: 4 } }, 
                    xaxis: { categories: data.labels }, 
                    dataLabels: { enabled: true },
                    grid: { show: false }, // GRIDLINES DIHILANGKAN KHUSUS CHART RANKING
                    title: { 
                        text: `Ranking Frekuensi Seluruh Kegiatan P2M (${this.globalStartYear} - ${this.globalEndYear})`, 
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
                
                let dataSeries = this.tabTrend === 'kegiatan' ? this.rawData.trend.kegiatan : (this.tabTrend === 'peserta' ? this.rawData.trend.peserta : this.rawData.trend.positif);
                let isHeatmap = this.isMultiSatker && this.adminTrendType === 'heatmap';
                
                let opts = { 
                    series: dataSeries, 
                    chart: { type: isHeatmap ? 'heatmap' : 'bar', height: 450, toolbar: { show: true }, fontFamily: 'inherit' }, 
                    xaxis: { categories: this.rawData.trend_labels },
                    title: {
                        text: `${this.dynamicTrendMetric} - ${this.detailTypeName} ${this.dynamicTrendTitle}`,
                        align: 'center', 
                        margin: 20, 
                        style: { fontSize: '18px', fontWeight: '500', color: '#212529' }
                    }
                };

                if (isHeatmap) {
                    opts.colors = ['#0d6efd']; 
                    opts.legend = { show: false };
                    opts.dataLabels = { enabled: true, style: { colors: ['#212529'], fontSize: '13px' } };
                    opts.plotOptions = { heatmap: { shadeIntensity: 0.6, radius: 4, useFillColorAsStroke: false } };
                    opts.yaxis = { labels: { style: { fontWeight: 'bold' } } };
                } else {
                    opts.colors = this.isMultiSatker ? this.satkerColors : (this.tabTrend === 'positif' ? ['#dc3545'] : ['#0d6efd']);
                    opts.plotOptions = { bar: { borderRadius: 4, columnWidth: this.isMultiSatker ? '85%' : '50%' } };
                    opts.tooltip = { shared: true, intersect: false };
                    opts.dataLabels = { enabled: !this.isMultiSatker, formatter: (val) => val > 0 ? val : "" };
                    
                    if (this.isMultiSatker) {
                        opts.legend = { position: 'top', fontWeight: 'bold', offsetY: -10 };
                    }
                    opts.yaxis = { labels: { formatter: (v) => Math.round(v) } };
                }
                
                if (this.chartInst.trend) this.chartInst.trend.destroy(); 
                this.chartInst.trend = new ApexCharts(this.$refs.chartTrend, opts); 
                this.chartInst.trend.render();
            },

            renderComp() {
                if (!this.$refs.chartComp || !this.rawData) return;
                
                let dataSeries = this.tabComp === 'anggaran' ? this.rawData.comp.anggaran : this.rawData.comp.sasaran;
                
                let colors = this.tabComp === 'anggaran' 
                    ? ['#198754', '#fd7e14'] 
                    : ['#0d6efd', '#0dcaf0', '#20c997', '#ffc107'];
                
                let opts = { 
                    series: dataSeries, 
                    chart: { type: 'bar', height: 450, stacked: true, toolbar: { show: false }, fontFamily: 'inherit' }, 
                    plotOptions: { bar: { columnWidth: this.isMultiSatker ? '50%' : '15%', borderRadius: 2 } }, 
                    xaxis: { categories: this.rawData.comp_labels, labels: { style: { fontSize: '12px', fontWeight: 'bold' } } }, 
                    colors: colors, 
                    legend: { position: 'top', fontWeight: 'bold', offsetY: -10 }, 
                    tooltip: { shared: true, intersect: false },
                    title: {
                        text: `${this.dynamicCompMetric} - ${this.detailTypeName} ${this.dynamicCompTitle}`,
                        align: 'center', 
                        margin: 20, 
                        style: { fontSize: '18px', fontWeight: '500', color: '#212529' }
                    },
                    dataLabels: { 
                        enabled: true, 
                        formatter: function(val, opt) { 
                            if (!val) return ""; 
                            let t = 0; 
                            opt.w.config.series.forEach(s => t += s.data[opt.dataPointIndex]); 
                            if (t === 0) return "";
                            return val + " (" + Math.round((val/t)*100) + "%)"; 
                        }, 
                        style: { fontSize: '12px', colors: ['#212529'] } 
                    }
                };
                
                if (this.chartInst.comp) this.chartInst.comp.destroy(); 
                this.chartInst.comp = new ApexCharts(this.$refs.chartComp, opts); 
                this.chartInst.comp.render();
            }
        }));
    });
</script>
@endpush