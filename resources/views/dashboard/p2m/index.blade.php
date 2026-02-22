@extends('admin')

@section('content')
<main class="admin-main" x-data="dashboardP2M()" x-init="init()">
    <div class="container-fluid p-4">

        {{-- A. HEADER & IDENTITAS --}}
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3">
            <div>
                <h1 class="h3 mb-2 fw-bold text-dark">Dashboard Kinerja P2M</h1>
                
                <div class="mt-2">
                    @if(auth()->user()->role === 'admin')
                        {{-- Dropdown Satker Khusus Super Admin - Dibuat Jelas dan Tebal --}}
                        <select x-model="globalSatkerId" class="form-select form-select-lg border-primary text-primary fw-bold shadow-sm" style="width: auto; min-width: 350px;">
                            <option value="">-- Seluruh Satuan Kerja (Gabungan) --</option>
                            @foreach($satkers as $s)
                                <option value="{{ $s->id }}">{{ $s->satuan_kerja }}</option>
                            @endforeach
                        </select>
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
                <a href="{{ route('dashboard.p2m.index') }}" class="btn btn-primary fw-bold"><i class="bi bi-megaphone-fill me-1"></i> P2M</a>
                {{-- <a href="{{ route('dashboard.berantas.index') }}" class="btn btn-outline-secondary fw-bold">Berantas</a> --}}
                {{-- <a href="{{ route('dashboard.rehab.index') }}" class="btn btn-outline-secondary fw-bold">Rehab</a> --}}
            </div>
            @endif
        </div>

        {{-- FILTER GLOBAL (Kartu Atas & Ranking) --}}
        <div class="d-flex justify-content-end mb-3">
            <div class="d-flex align-items-center bg-white p-2 rounded shadow-sm border gap-2">
                <span class="fw-bold text-muted small"><i class="bi bi-calendar-range me-2 text-primary"></i>Periode Akumulasi:</span>
                <select x-model="globalStartYear" class="form-select form-select-sm border-secondary fw-bold text-primary" style="width: 90px;">
                    @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                </select>
                <span class="fw-bold">-</span>
                <select x-model="globalEndYear" class="form-select form-select-sm border-secondary fw-bold text-primary" style="width: 90px;">
                    @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                </select>
            </div>
        </div>

        {{-- B. KARTU UTAMA --}}
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-primary text-white overflow-hidden">
                    <i class="bi bi-layers-fill position-absolute opacity-25" style="font-size: 8rem; right: -20px; bottom: -30px;"></i>
                    <div class="card-body d-flex flex-column justify-content-center align-items-center text-center position-relative z-1 p-4">
                        <h6 class="text-uppercase text-white-50 fw-bold mb-3">Total Kegiatan P2M</h6>
                        <h1 class="display-3 fw-bold mb-3" x-text="new Intl.NumberFormat('id-ID').format(cards.kegiatan.total)">0</h1>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 overflow-hidden">
                    <div class="d-flex h-100 align-items-stretch">
                        <div class="bg-success text-white p-3 d-flex flex-column justify-content-center align-items-center text-center" style="width: 35%; flex-shrink: 0;">
                            <h2 class="fw-bold mb-2 display-6" x-text="new Intl.NumberFormat('id-ID').format(cards.orang.total)">0</h2>
                            <span class="text-white-50 fw-bold small lh-sm">Masyarakat<br>Terlayani</span>
                        </div>
                        <div class="flex-grow-1 bg-success-subtle p-3 overflow-auto" style="max-height: 200px;">
                            <template x-for="(item, label) in cards.orang.list">
                                <div x-show="(item.val || item) > 0" class="d-flex justify-content-between align-items-center mb-2 border-bottom border-success border-opacity-25 pb-1">
                                    <span class="text-dark fw-bold small lh-sm" x-text="label"></span>
                                    <div class="text-end" style="flex-shrink: 0;">
                                        <span class="fw-bold text-success" x-text="new Intl.NumberFormat('id-ID').format(item.val || item)"></span>
                                        <template x-if="item.is_tes_urine"><div class="lh-1 mt-1"><span class="badge bg-danger text-white" style="font-size: 0.6rem;">Positif: <span x-text="new Intl.NumberFormat('id-ID').format(item.positif)"></span></span></div></template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 overflow-hidden">
                    <div class="d-flex h-100 align-items-stretch">
                        <div class="bg-warning text-dark p-3 d-flex flex-column justify-content-center align-items-center text-center" style="width: 35%;">
                            <h2 class="fw-bold mb-2 display-6" x-text="new Intl.NumberFormat('id-ID').format(cards.media.total_freq)">0</h2>
                            <span class="text-dark-50 fw-bold small lh-sm">Total Publikasi</span>
                            <div class="mt-3 badge bg-dark text-white bg-opacity-25 rounded-pill px-3 py-1 small">Durasi: <span x-text="new Intl.NumberFormat('id-ID').format(cards.media.total_durasi)"></span> Hari</div>
                        </div>
                        <div class="flex-grow-1 bg-warning-subtle p-3 d-flex flex-column justify-content-center">
                            <template x-for="(item, label) in cards.media.list">
                                <div class="d-flex justify-content-between align-items-center mb-2 border-bottom border-warning border-opacity-25 pb-1">
                                    <span class="text-dark fw-bold small" x-text="label"></span>
                                    <div class="text-end lh-1"><span class="fw-bold text-dark d-block small" x-text="new Intl.NumberFormat('id-ID').format(item.freq) + ' x'"></span><span class="text-muted fw-bold" style="font-size: 0.7rem;" x-text="new Intl.NumberFormat('id-ID').format(item.durasi) + ' Hari'"></span></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 overflow-hidden">
                    <div class="d-flex h-100 align-items-stretch">
                        <div class="bg-info text-white p-3 d-flex flex-column justify-content-center align-items-center text-center" style="width: 35%;">
                            <h2 class="fw-bold mb-2 display-6" x-text="new Intl.NumberFormat('id-ID').format(cards.wilayah.total)">0</h2>
                            <span class="text-white-50 fw-bold small lh-sm">Kawasan<br>Binaan</span>
                        </div>
                        <div class="flex-grow-1 bg-info-subtle p-3 d-flex flex-column justify-content-center">
                            <template x-for="(val, label) in cards.wilayah.list">
                                <div class="d-flex justify-content-between align-items-center border-bottom border-info border-opacity-25 py-3">
                                    <span class="text-dark fw-bold small" x-text="label"></span>
                                    <span class="fw-bold text-info h5 mb-0" x-text="new Intl.NumberFormat('id-ID').format(val)"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- C. CHART RANKING --}}
        <div class="card border-0 shadow-sm mb-5">
            <div class="card-header bg-white py-3"><h6 class="m-0 fw-bold text-dark"><i class="bi bi-bar-chart-fill text-primary me-2"></i>Ranking Frekuensi Seluruh Kegiatan P2M (Sesuai Periode)</h6></div>
            <div class="card-body p-3"><div x-ref="rankingChart" style="min-height: 400px;"></div></div>
        </div>

        {{-- ================================================================= --}}
        {{-- D. PUSAT ANALISIS GRAFIK DETAIL (TREN & KOMPOSISI) --}}
        {{-- ================================================================= --}}
        
        <div class="bg-dark p-3 rounded shadow-sm mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h5 class="m-0 fw-bold text-white"><i class="bi bi-display me-2 text-warning"></i>Pusat Analisis Kinerja Detail</h5>
                <small class="text-white-50">Gunakan kontrol di bawah ini untuk mengatur tampilan grafik analitik secara instan.</small>
            </div>
            
            <div class="d-flex flex-wrap gap-2 align-items-center">
                {{-- Pilih Kegiatan --}}
                <select x-model="detailType" class="form-select border-warning fw-bold text-dark" style="width: 200px;">
                    <option value="informasi_edukasi">Informasi & Edukasi</option>
                    <option value="media_elektronik">Media Elektronik</option>
                    <option value="media_non_elektronik">Media Non-Elektronik</option>
                    <option value="media_online">Media Online</option>
                    <option value="tes_urine">Tes Urine</option>
                    <option value="desa_bersinar">Desa/Kel. Bersinar</option>
                    <option value="asistensi">Asistensi Relawan</option>
                    <option value="pelatihan">Pelatihan Soft Skill</option>
                    <option value="keluarga">Ketahanan Keluarga</option>
                    <option value="monev">Monitoring & Evaluasi</option>
                    <option value="pemetaan">Pemetaan SDM/SDA</option>
                    <option value="ikan">IKAN</option>
                </select>

                {{-- Pilih Mode (Bulanan / Tahunan) --}}
                <select x-model="detailMode" class="form-select border-primary fw-bold text-primary" style="width: 150px;">
                    <option value="monthly">Per Bulan</option>
                    <option value="yearly">Rentang Tahun</option>
                </select>

                {{-- Mode Bulanan: Pilih Tahun --}}
                <template x-if="detailMode === 'monthly'">
                    <div class="d-flex gap-2">
                        <select x-model="detailMonthYear" class="form-select border-info text-info fw-bold" style="width: 100px;">
                            @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                        </select>
                    </div>
                </template>

                {{-- Mode Tahunan: Pilih Rentang --}}
                <template x-if="detailMode === 'yearly'">
                    <div class="d-flex gap-2 align-items-center">
                        <select x-model="detailYearStart" class="form-select border-info text-info fw-bold" style="width: 90px;">
                            @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                        </select>
                        <span class="text-white-50 fw-bold">-</span>
                        <select x-model="detailYearEnd" class="form-select border-info text-info fw-bold" style="width: 90px;">
                            @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                        </select>
                    </div>
                </template>
            </div>
        </div>

        <div class="row g-4 mb-5">
            {{-- Grafik 1: TREN KINERJA --}}
            <div class="col-12">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
                        
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            {{-- Judul Terintegrasi dengan Metrik --}}
                            <h6 class="m-0 fw-bold text-primary">
                                <i class="bi bi-graph-up text-primary me-2"></i>
                                <span x-text="dynamicTrendMetric"></span> 
                                <span x-text="dynamicTrendTitle" class="text-secondary fw-normal fs-6 ms-1"></span>
                            </h6>
                            
                            {{-- Toggle Khusus Mode Multi Satker: Heatmap vs Grouped Bar --}}
                            <template x-if="isMultiSatker">
                                <select x-model="adminTrendType" class="form-select border-primary text-primary fw-bold ms-2" style="width: auto;">
                                    <option value="heatmap">Tampilan Heatmap</option>
                                    <option value="bar">Tampilan Batang</option>
                                </select>
                            </template>
                        </div>

                        <div class="btn-group btn-group-sm shadow-sm">
                            <button @click="tabTrend = 'kegiatan'" :class="tabTrend === 'kegiatan' ? 'btn-primary text-white' : 'btn-outline-secondary'" class="btn fw-bold">Jumlah Kegiatan</button>
                            <button x-show="config.unit !== '-'" @click="tabTrend = 'peserta'" :class="tabTrend === 'peserta' ? 'btn-primary text-white' : 'btn-outline-secondary'" class="btn fw-bold px-2">Jumlah <span x-text="config.unit"></span></button>
                            <button x-show="config.has_positif" @click="tabTrend = 'positif'" :class="tabTrend === 'positif' ? 'btn-danger text-white' : 'btn-outline-secondary'" class="btn fw-bold">Indikasi Positif</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div x-ref="chartTrend" style="min-height: 400px;"></div>
                    </div>
                </div>
            </div>
            
            {{-- Grafik 2: KOMPOSISI PROPORSI --}}
            <div class="col-12" x-show="config.has_anggaran || config.has_sasaran">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-2">
                            
                            <h6 class="m-0 fw-bold text-success">
                                <i class="bi bi-pie-chart-fill text-success me-2"></i>
                                Proporsi <span x-text="dynamicCompMetric"></span>
                                <span x-text="dynamicCompTitle" class="text-secondary fw-normal fs-6 ms-1"></span>
                            </h6>
                            
                            <select x-show="detailMode === 'monthly'" x-model="detailMonthMonth" class="form-select border-0 bg-light text-secondary fw-bold ms-3" style="width: auto; min-width: 160px;">
                                <option value="all">Sepanjang Tahun</option>
                                <option value="1">Januari</option>
                                <option value="2">Februari</option>
                                <option value="3">Maret</option>
                                <option value="4">April</option>
                                <option value="5">Mei</option>
                                <option value="6">Juni</option>
                                <option value="7">Juli</option>
                                <option value="8">Agustus</option>
                                <option value="9">September</option>
                                <option value="10">Oktober</option>
                                <option value="11">November</option>
                                <option value="12">Desember</option>
                            </select>
                        </div>

                        <div class="btn-group btn-group-sm shadow-sm">
                            <button x-show="config.has_anggaran" @click="tabComp = 'anggaran'" :class="tabComp === 'anggaran' ? 'btn-success text-white' : 'btn-outline-secondary'" class="btn fw-bold">Berdasarkan Anggaran</button>
                            <button x-show="config.has_sasaran" @click="tabComp = 'sasaran'" :class="tabComp === 'sasaran' ? 'btn-warning text-dark' : 'btn-outline-secondary'" class="btn fw-bold">Berdasarkan Sasaran</button>
                        </div>
                    </div>
                    <div class="card-body"><div x-ref="chartComp" style="min-height: 400px;"></div></div>
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
            // State Filter Utama
            globalSatkerId: '',
            globalStartYear: '{{ date("Y") }}',
            globalEndYear: '{{ date("Y") }}',
            
            cards: { kegiatan: { total: 0 }, orang: { total: 0, list: {} }, media: { total_freq: 0, total_durasi: 0, list: {} }, wilayah: { total: 0, list: {} } },
            
            // Detail Filter
            detailType: 'informasi_edukasi',
            detailMode: 'monthly',
            detailMonthYear: '{{ date("Y") }}',
            detailMonthMonth: 'all',
            detailYearStart: '{{ date("Y") - 4 }}',
            detailYearEnd: '{{ date("Y") }}',
            
            // State Tabs & Settings
            tabTrend: 'kegiatan', 
            tabComp: 'anggaran',
            adminTrendType: 'heatmap', 
            
            config: { unit: 'Peserta', has_anggaran: true, has_sasaran: true, has_positif: false },
            isMultiSatker: false,
            rawData: null,
            chartInst: { rank: null, trend: null, comp: null },
            
            // Palet Warna Kontras untuk 6 Satker (Biru, Hijau, Kuning Gelap, Merah, Ungu, Cyan Tua)
            satkerColors: ['#0d6efd', '#198754', '#d39e00', '#dc3545', '#6f42c1', '#0aa2c0'],

            init() {
                this.fetchGlobal();
                this.fetchDetail();

                this.$watch('globalSatkerId', () => { this.fetchGlobal(); this.fetchDetail(); });
                this.$watch('globalStartYear', () => Object.assign(this, { globalEndYear: Math.max(this.globalStartYear, this.globalEndYear) }) && this.fetchGlobal());
                this.$watch('globalEndYear', () => Object.assign(this, { globalStartYear: Math.min(this.globalStartYear, this.globalEndYear) }) && this.fetchGlobal());
                
                this.$watch('detailYearStart', () => Object.assign(this, { detailYearEnd: Math.max(this.detailYearStart, this.detailYearEnd) }) && this.fetchDetail());
                this.$watch('detailYearEnd', () => Object.assign(this, { detailYearStart: Math.min(this.detailYearStart, this.detailYearEnd) }) && this.fetchDetail());
                
                ['detailType', 'detailMode', 'detailMonthYear', 'detailMonthMonth'].forEach(prop => {
                    this.$watch(prop, () => this.fetchDetail());
                });
                
                this.$watch('tabTrend', () => this.renderTrend());
                this.$watch('tabComp', () => this.renderComp());
                this.$watch('adminTrendType', () => this.renderTrend());
            },

            get dynamicTrendMetric() {
                if (this.tabTrend === 'kegiatan') return 'Jumlah Kegiatan';
                if (this.tabTrend === 'peserta') return 'Jumlah ' + this.config.unit;
                if (this.tabTrend === 'positif') return 'Indikasi Positif';
            },

            get dynamicCompMetric() {
                if (this.tabComp === 'anggaran') return 'Berdasarkan Anggaran';
                if (this.tabComp === 'sasaran') return 'Berdasarkan Sasaran';
            },

            get dynamicTrendTitle() {
                if (this.detailMode === 'monthly') {
                    return '(Periode: Januari - Desember ' + this.detailMonthYear + ')';
                } else {
                    return '(Periode: ' + this.detailYearStart + ' s/d ' + this.detailYearEnd + ')';
                }
            },

            get dynamicCompTitle() {
                if (this.detailMode === 'monthly') {
                    if (this.detailMonthMonth === 'all') {
                        return '(Periode: Akumulasi Tahun ' + this.detailMonthYear + ')';
                    } else {
                        const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                        return '(Periode: Bulan ' + months[parseInt(this.detailMonthMonth)-1] + ' ' + this.detailMonthYear + ')';
                    }
                } else {
                    return '(Periode: ' + this.detailYearStart + ' s/d ' + this.detailYearEnd + ')';
                }
            },

            fetchGlobal() {
                fetch(`{{ route('dashboard.p2m.api.global') }}?start_year=${this.globalStartYear}&end_year=${this.globalEndYear}&satker_id=${this.globalSatkerId}`)
                    .then(r => r.json()).then(res => {
                        this.cards = res;
                        this.renderRanking(res.ranking_chart);
                    });
            },

            fetchDetail() {
                let url = `{{ route('dashboard.p2m.api.chart') }}?type=${this.detailType}&mode=${this.detailMode}&m_year=${this.detailMonthYear}&m_month=${this.detailMonthMonth}&y_start=${this.detailYearStart}&y_end=${this.detailYearEnd}&satker_id=${this.globalSatkerId}`;
                fetch(url).then(r => r.json()).then(res => {
                    this.rawData = res;
                    this.isMultiSatker = res.is_multi_satker;
                    this.config  = res.config;
                    
                    if(this.tabTrend === 'peserta' && this.config.unit === '-') this.tabTrend = 'kegiatan';
                    if(this.tabTrend === 'positif' && !this.config.has_positif) this.tabTrend = 'kegiatan';

                    this.$nextTick(() => {
                        this.renderTrend();
                        if(this.config.has_anggaran || this.config.has_sasaran) this.renderComp();
                    });
                });
            },

            renderRanking(data) {
                let opts = {
                    series: [{ name: 'Total Kegiatan', data: data.data }],
                    chart: { type: 'bar', height: 400, toolbar: { show: false } },
                    plotOptions: { bar: { horizontal: true, distributed: true, borderRadius: 4 } },
                    xaxis: { categories: data.labels },
                    dataLabels: { enabled: true }
                };
                if(this.chartInst.rank) this.chartInst.rank.destroy();
                this.chartInst.rank = new ApexCharts(this.$refs.rankingChart, opts);
                this.chartInst.rank.render();
            },

            renderTrend() {
                if(!this.$refs.chartTrend || !this.rawData) return;
                
                let dataSeries = this.tabTrend === 'kegiatan' ? this.rawData.trend.kegiatan : (this.tabTrend === 'peserta' ? this.rawData.trend.peserta : this.rawData.trend.positif);
                
                let isHeatmap = this.isMultiSatker && this.adminTrendType === 'heatmap'; 
                let isGroupedBar = this.isMultiSatker && this.adminTrendType === 'bar';
                
                let opts = {
                    series: dataSeries,
                    chart: { type: isHeatmap ? 'heatmap' : 'bar', height: 400, toolbar: { show: true } },
                    xaxis: { categories: this.rawData.trend_labels },
                };

                if (isHeatmap) {
                    opts.colors = ['#0d6efd'];
                    opts.legend = { show: false }; // Matikan Legend "Kosong"
                    opts.dataLabels = { 
                        enabled: true,
                        style: {
                            colors: ['#212529'], // Teks dipaksa abu-abu gelap agar selalu terbaca
                            fontSize: '12px'
                        }
                    };
                    opts.plotOptions = {
                        heatmap: {
                            shadeIntensity: 0.6,
                            radius: 4,
                            useFillColorAsStroke: false,
                            colorScale: { 
                                ranges: [{ from: 0, to: 0, color: '#f1f3f5', name: '' }] // Warna sangat pudar untuk 0
                            } 
                        }
                    };
                    opts.yaxis = { labels: { style: { fontWeight: 'bold' } } };
                } else {
                    opts.colors = this.isMultiSatker ? this.satkerColors : (this.tabTrend === 'positif' ? ['#dc3545'] : ['#0d6efd']);
                    opts.plotOptions = { bar: { borderRadius: 4, columnWidth: isGroupedBar ? '85%' : '50%' } };
                    opts.yaxis = { labels: { formatter: (val) => Math.round(val) } };
                    
                    // Tooltip Gabungan (Shared) diaktifkan agar muncul semua nilai sekaligus
                    opts.tooltip = { shared: true, intersect: false };
                    opts.dataLabels = { enabled: !this.isMultiSatker, formatter: (val) => val > 0 ? val : "" };
                    
                    if (isGroupedBar) opts.legend = { position: 'top', fontWeight: 'bold' };
                }

                if(this.chartInst.trend) this.chartInst.trend.destroy();
                this.chartInst.trend = new ApexCharts(this.$refs.chartTrend, opts);
                this.chartInst.trend.render();
            },

            renderComp() {
                if(!this.$refs.chartComp || !this.rawData) return;
                
                let dataSeries = this.tabComp === 'anggaran' ? this.rawData.comp.anggaran : this.rawData.comp.sasaran;
                let colors = this.tabComp === 'anggaran' ? ['#198754', '#ffc107'] : ['#0d6efd', '#dc3545', '#ffc107', '#20c997'];
                
                let opts = {
                    series: dataSeries,
                    chart: { type: 'bar', height: 400, stacked: true, toolbar: { show: false } },
                    plotOptions: { bar: { columnWidth: this.isMultiSatker ? '50%' : '15%', borderRadius: 2 } },
                    xaxis: { categories: this.rawData.comp_labels, labels: { style: { fontSize: '12px', fontWeight: 'bold' } } },
                    colors: colors,
                    legend: { position: 'top', fontWeight: 'bold' },
                    tooltip: { shared: true, intersect: false },
                    dataLabels: {
                        enabled: true,
                        formatter: function(val, opts) {
                            if (val === 0 || !val) return "";
                            let total = 0;
                            opts.w.config.series.forEach(s => { 
                                let v = s.data[opts.dataPointIndex];
                                if(v) total += v; 
                            });
                            if(total === 0) return val;
                            let percent = Math.round((val / total) * 100);
                            return val + " (" + percent + "%)";
                        },
                        style: { fontSize: '11px' }
                    }
                };

                if(this.chartInst.comp) this.chartInst.comp.destroy();
                this.chartInst.comp = new ApexCharts(this.$refs.chartComp, opts);
                this.chartInst.comp.render();
            }
        }));
    });
</script>
@endpush