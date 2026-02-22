@extends('admin')

@section('content')
<main class="admin-main" x-data="dashboardP2M()" x-init="init()">
    <div class="container-fluid p-4">

        {{-- A. HEADER & IDENTITAS --}}
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3">
            <div>
                <h1 class="h3 mb-2 fw-bold text-dark">Dashboard Kinerja P2M</h1>
                <p class="text-muted mb-0 fs-5">
                    <i class="bi bi-building-fill me-2 text-primary"></i>
                    @if(auth()->user()->role === 'admin')
                        <span class="fw-bold text-dark">Data Seluruh Satuan Kerja</span>
                    @else
                        <span class="fw-bold text-dark">{{ auth()->user()->pegawai?->satuanKerja?->satuan_kerja ?? 'Satuan Kerja' }}</span>
                    @endif
                </p>
            </div>
            
            {{-- Tombol Navigasi Bidang --}}
            @if($showTabs)
            <div class="btn-group shadow-sm">
                <a href="{{ route('dashboard.p2m.index') }}" class="btn btn-primary fw-bold"><i class="bi bi-megaphone-fill me-1"></i> P2M</a>
                {{-- <a href="{{ route('dashboard.berantas.index') }}" class="btn btn-outline-secondary fw-bold">Berantas</a> --}}
                {{-- <a href="{{ route('dashboard.rehab.index') }}" class="btn btn-outline-secondary fw-bold">Rehab</a> --}}
            </div>
            @endif
        </div>

        {{-- FILTER PERIODE GLOBAL (Untuk Kartu Atas) --}}
        <div class="d-flex justify-content-end mb-3">
            <div class="d-flex align-items-center bg-white p-2 rounded shadow-sm border gap-2">
                <span class="fw-bold text-muted small"><i class="bi bi-calendar-range me-2 text-primary"></i>Periode Akumulasi:</span>
                <select x-model="startYear" class="form-select form-select-sm border-secondary fw-bold text-primary" style="width: 90px;">
                    @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                </select>
                <span class="fw-bold">-</span>
                <select x-model="endYear" class="form-select form-select-sm border-secondary fw-bold text-primary" style="width: 90px;">
                    @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                </select>
            </div>
        </div>

        {{-- B. KARTU UTAMA --}}
        <div class="row g-3 mb-4">
            {{-- 1. TOTAL KEGIATAN --}}
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-primary text-white overflow-hidden">
                    <i class="bi bi-layers-fill position-absolute opacity-25" style="font-size: 8rem; right: -20px; bottom: -30px;"></i>
                    <div class="card-body d-flex flex-column justify-content-center align-items-center text-center position-relative z-1 p-4">
                        <h6 class="text-uppercase text-white-50 fw-bold mb-3">Total Kegiatan P2M</h6>
                        <h1 class="display-3 fw-bold mb-3" x-text="new Intl.NumberFormat('id-ID').format(cards.kegiatan.total)">0</h1>
                    </div>
                </div>
            </div>
            {{-- 2. ORANG TERLAYANI --}}
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
            {{-- 3. MEDIA --}}
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
            {{-- 4. WILAYAH --}}
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
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3"><h6 class="m-0 fw-bold text-dark"><i class="bi bi-bar-chart-fill text-primary me-2"></i>Ranking Frekuensi Seluruh Kegiatan P2M</h6></div>
            <div class="card-body p-3"><div x-ref="rankingChart" style="min-height: 400px;"></div></div>
        </div>

        {{-- D. CHART ANALISA TREN & KOMPOSISI --}}
        <div class="row g-4"> 
            
            {{-- FILTER UTAMA ANALISA --}}
            <div class="col-12">
                <div class="bg-white p-3 rounded shadow-sm border d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                    <div>
                        <h5 class="m-0 fw-bold text-dark"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Analisis Kinerja Detil</h5>
                        <small class="text-muted">Pilih jenis kegiatan untuk mengeksplorasi tren dan proporsi anggaran/sasaran.</small>
                    </div>
                    <div class="d-flex gap-2">
                        <select x-model="chartFilter.type" class="form-select border-secondary fw-bold text-dark fs-6" style="width: 250px;">
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
                        <select x-model="chartFilter.year" class="form-select border-primary fw-bold text-primary fs-6" style="width: 100px;">
                            @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- 1. GRAFIK TREN (LINE CHART) --}}
            <div class="col-12">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="m-0 fw-bold text-primary">Grafik Tren Waktu</h6>
                            {{-- Toggle Mode: Bulanan vs Tahunan --}}
                            <select x-model="chartFilter.trendMode" class="form-select form-select-sm border-0 bg-light text-secondary fw-bold ms-2" style="width: 140px;">
                                <option value="monthly">Per Bulan</option>
                                <option value="yearly">Per Tahun (5 Thn)</option>
                            </select>
                        </div>
                        
                        {{-- Tab Kendali Metrik (Sama untuk Admin & Non-Admin) --}}
                        <div class="btn-group btn-group-sm shadow-sm">
                            <button @click="lineTab = 'kegiatan'" :class="lineTab === 'kegiatan' ? 'btn-primary text-white' : 'btn-outline-secondary'" class="btn fw-bold">Jml. Kegiatan</button>
                            <button x-show="config.unit !== '-'" @click="lineTab = 'peserta'" :class="lineTab === 'peserta' ? 'btn-primary text-white' : 'btn-outline-secondary'" class="btn fw-bold">Jml. <span x-text="config.unit"></span></button>
                            <button x-show="config.has_positif" @click="lineTab = 'positif'" :class="lineTab === 'positif' ? 'btn-danger text-white' : 'btn-outline-secondary'" class="btn fw-bold">Indikasi Positif</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div x-ref="lineChart" style="min-height: 400px;"></div>
                    </div>
                </div>
            </div>

            {{-- 2. GRAFIK KOMPOSISI (STACKED BAR CHART) --}}
            <div class="col-12" x-show="config.has_anggaran || config.has_sasaran">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="m-0 fw-bold text-success">Komposisi Profil Kegiatan</h6>
                            {{-- Toggle Filter Bulan untuk Stacked Bar --}}
                            <select x-model="chartFilter.barMonth" class="form-select form-select-sm border-0 bg-light text-secondary fw-bold ms-2" style="width: 160px;">
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

                        {{-- Tab Kendali Proporsi (Sama untuk Admin & Non-Admin) --}}
                        <div class="btn-group btn-group-sm shadow-sm">
                            <button x-show="config.has_anggaran" @click="barTab = 'anggaran'" :class="barTab === 'anggaran' ? 'btn-success text-white' : 'btn-outline-secondary'" class="btn fw-bold">Proporsi Anggaran</button>
                            <button x-show="config.has_sasaran" @click="barTab = 'sasaran'" :class="barTab === 'sasaran' ? 'btn-warning text-dark' : 'btn-outline-secondary'" class="btn fw-bold">Proporsi Sasaran</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div x-ref="barChart" style="min-height: 400px;"></div>
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
            // Filter Global (Top Cards)
            startYear: '{{ date("Y") }}',
            endYear: '{{ date("Y") }}',
            
            cards: {
                kegiatan: { total: 0 },
                orang: { total: 0, list: {} },
                media: { total_freq: 0, total_durasi: 0, list: {} },
                wilayah: { total: 0, list: {} }
            },
            
            // Filter Khusus Chart Analisis
            chartFilter: { 
                type: 'informasi_edukasi', 
                year: '{{ date("Y") }}',
                trendMode: 'monthly', // 'monthly' atau 'yearly'
                barMonth: 'all'       // 'all' atau '1' sampai '12'
            },
            
            // Konfigurasi & State
            config: { unit: 'Peserta', has_anggaran: true, has_sasaran: true, has_positif: false },
            lineTab: 'kegiatan', 
            barTab: 'anggaran',
            rawData: null,
            
            // Objek Chart Instance
            chartInst: { rank: null, line: null, bar: null },

            init() {
                this.fetchGlobal();
                this.fetchChart();

                // Observers API Request
                this.$watch('startYear', () => Object.assign(this, { endYear: Math.max(this.startYear, this.endYear) }) && this.fetchGlobal());
                this.$watch('endYear', () => Object.assign(this, { startYear: Math.min(this.startYear, this.endYear) }) && this.fetchGlobal());
                
                this.$watch('chartFilter.type', () => this.fetchChart());
                this.$watch('chartFilter.year', () => this.fetchChart());
                this.$watch('chartFilter.trendMode', () => this.fetchChart());
                this.$watch('chartFilter.barMonth', () => this.fetchChart());
                
                // Observers Client-Side Render (Ganti Tab)
                this.$watch('lineTab', () => this.renderLineChart());
                this.$watch('barTab', () => this.renderBarChart());
            },

            fetchGlobal() {
                let url = `{{ route('dashboard.p2m.api.global') }}?start_year=${this.startYear}&end_year=${this.endYear}`;
                fetch(url).then(r => r.json()).then(res => {
                    this.cards = res;
                    this.renderRanking(res.ranking_chart);
                });
            },

            fetchChart() {
                let url = `{{ route('dashboard.p2m.api.chart') }}?type=${this.chartFilter.type}&year=${this.chartFilter.year}&trend_mode=${this.chartFilter.trendMode}&bar_month=${this.chartFilter.barMonth}`;
                fetch(url).then(r => r.json()).then(res => {
                    this.rawData = res;
                    this.config  = res.config;
                    
                    // Otomatis pindah tab jika matrik tidak didukung di kegiatan yang dipilih
                    if(this.lineTab === 'peserta' && this.config.unit === '-') this.lineTab = 'kegiatan';
                    if(this.lineTab === 'positif' && !this.config.has_positif) this.lineTab = 'kegiatan';

                    this.$nextTick(() => {
                        this.renderLineChart();
                        if(this.config.has_anggaran || this.config.has_sasaran) this.renderBarChart();
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

            renderLineChart() {
                if(!this.$refs.lineChart || !this.rawData) return;
                
                let seriesData = this.lineTab === 'kegiatan' ? this.rawData.tren.kegiatan : 
                                (this.lineTab === 'peserta' ? this.rawData.tren.peserta : this.rawData.tren.positif);
                                
                let yTitle = this.lineTab === 'kegiatan' ? 'Jumlah Kegiatan' : (this.lineTab === 'positif' ? 'Indikasi Positif' : this.config.unit);
                
                // Pewarnaan dinamis: Jika Non-Admin (1 baris) pakai biru. Jika Admin (6 baris), ApexCharts akan otomatis mewarnai.
                let colors = this.rawData.is_admin ? undefined : (this.lineTab === 'positif' ? ['#dc3545'] : ['#0d6efd']);

                let opts = {
                    series: seriesData,
                    chart: { type: 'line', height: 400, toolbar: { show: true } },
                    stroke: { width: 3, curve: 'smooth' },
                    colors: colors,
                    xaxis: { categories: this.rawData.trend_labels },
                    yaxis: { title: { text: yTitle, style: { fontWeight: 'bold' } } },
                    tooltip: { shared: true, intersect: false },
                    legend: { position: 'top' },
                    dataLabels: { enabled: !this.rawData.is_admin, formatter: (val) => val > 0 ? val : "" } // Tampilkan angka di titik jika non-admin
                };

                if(this.chartInst.line) this.chartInst.line.destroy();
                this.chartInst.line = new ApexCharts(this.$refs.lineChart, opts);
                this.chartInst.line.render();
            },

            renderBarChart() {
                if(!this.$refs.barChart || !this.rawData) return;
                
                let seriesData = this.barTab === 'anggaran' ? this.rawData.komposisi.anggaran : this.rawData.komposisi.sasaran;
                let isAnggaran = this.barTab === 'anggaran';

                let opts = {
                    series: seriesData,
                    chart: { type: 'bar', height: 400, stacked: true, toolbar: { show: false } },
                    plotOptions: { bar: { columnWidth: '50%', borderRadius: 2 } },
                    xaxis: { categories: this.rawData.bar_labels, labels: { style: { fontSize: '11px', fontWeight: 'bold' } } },
                    colors: isAnggaran ? ['#198754', '#ffc107'] : ['#0d6efd', '#dc3545', '#ffc107', '#20c997'],
                    dataLabels: { enabled: true, formatter: (val) => val > 0 ? val : "" },
                    legend: { position: 'top' }
                };

                if(this.chartInst.bar) this.chartInst.bar.destroy();
                this.chartInst.bar = new ApexCharts(this.$refs.barChart, opts);
                this.chartInst.bar.render();
            }
        }));
    });
</script>
@endpush