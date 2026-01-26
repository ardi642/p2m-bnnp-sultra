@extends('admin')

@section('content')
<main class="admin-main" x-data="dashboardUnified()" x-init="init()">
    <div class="container-fluid p-4">

        {{-- A. HEADER & IDENTITAS --}}
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3">
            <div>
                <h1 class="h3 mb-2 fw-bold text-dark">Dashboard Kinerja Terpadu</h1>
                <p class="text-muted mb-0 fs-5">
                    @if(auth()->user()->hasRole('admin'))
                        <i class="bi bi-info-circle-fill me-2 text-primary"></i>
                        <span class="fw-bold text-dark" x-text="satkerLabel">Memuat status data...</span>
                    @else
                        {{-- IDENTITAS DINAMIS --}}
                        <i class="bi bi-building-fill me-2 text-primary"></i>
                        Satuan Kerja: 
                        <span class="fw-bold text-dark">
                            {{ auth()->user()->pegawai?->satuanKerja?->satuan_kerja ?? auth()->user()->name }}
                        </span>
                    @endif
                </p>
            </div>
            
            @if(auth()->user()->hasRole('admin'))
            <div class="bg-white p-2 rounded shadow-sm border" style="min-width: 350px;">
                <label class="small text-muted fw-bold mb-1 ms-1">Filter Data Satuan Kerja:</label>
                <select id="select-satker" x-model="satkerId" class="form-select border-0 fw-bold fs-6">
                    <option value="">Semua Satuan Kerja (Gabungan)</option>
                    @foreach($satkers as $s) <option value="{{ $s->id }}">{{ $s->satuan_kerja }}</option> @endforeach
                </select>
            </div>
            @endif
        </div>

        {{-- B. TAB NAVIGASI --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-2">
                <div class="nav nav-pills nav-fill gap-2">
                    @if($permissions['p2m']) <button @click="activeTab = 'p2m'" :class="activeTab === 'p2m' ? 'bg-primary text-white shadow' : 'bg-light text-secondary'" class="nav-link fw-bold rounded transition-all py-2 fs-6"><i class="bi bi-people-fill me-2"></i>Bidang P2M</button> @endif
                    @if($permissions['berantas']) <button @click="activeTab = 'berantas'" class="nav-link fw-bold bg-light text-secondary py-2 fs-6">Berantas</button> @endif
                    @if($permissions['rehab']) <button @click="activeTab = 'rehab'" class="nav-link fw-bold bg-light text-secondary py-2 fs-6">Rehab</button> @endif
                </div>
            </div>
        </div>

        <div x-show="activeTab === 'p2m'" x-transition>
            
            {{-- FILTER PERIODE --}}
            <div class="d-flex justify-content-end mb-3">
                <div class="d-flex align-items-center bg-white p-2 rounded shadow-sm border gap-2">
                    <span class="fw-bold text-muted small"><i class="bi bi-calendar-range me-2"></i>Periode Laporan:</span>
                    <select x-model="startYear" class="form-select form-select-sm border-secondary fw-bold text-primary" style="width: 90px;">
                        @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                    </select>
                    <span class="fw-bold">-</span>
                    <select x-model="endYear" class="form-select form-select-sm border-secondary fw-bold text-primary" style="width: 90px;">
                        @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                    </select>
                </div>
            </div>

            {{-- C. KARTU UTAMA --}}
            <div class="row g-3 mb-4">
                {{-- 1. TOTAL KEGIATAN --}}
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100 bg-primary text-white overflow-hidden">
                        <i class="bi bi-layers-fill position-absolute opacity-25" style="font-size: 8rem; right: -20px; bottom: -30px;"></i>
                        <div class="card-body d-flex flex-column justify-content-center align-items-center text-center position-relative z-1 p-4">
                            <h6 class="text-uppercase text-white-50 fw-bold mb-3">Total Kegiatan</h6>
                            <h1 class="display-3 fw-bold mb-3" x-text="p2mCards.kegiatan.total">0</h1>
                            <div class="badge bg-white text-primary rounded-pill px-3 py-2 shadow-sm"><i class="bi bi-check-circle-fill me-2"></i>Akumulasi Laporan</div>
                        </div>
                    </div>
                </div>
                {{-- 2. JANGKAUAN ORANG --}}
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100 overflow-hidden">
                        <div class="d-flex h-100 align-items-stretch">
                            <div class="bg-success text-white p-3 d-flex flex-column justify-content-center align-items-center text-center" style="width: 35%; flex-shrink: 0;">
                                <h2 class="fw-bold mb-2 display-6" x-text="p2mCards.orang.total">0</h2>
                                <span class="text-white-50 fw-bold small lh-sm">Masyarakat<br>Terlayani</span>
                            </div>
                            <div class="flex-grow-1 bg-success-subtle p-3 d-flex flex-column justify-content-center">
                                <template x-for="(item, label) in p2mCards.orang.list">
                                    <div class="d-flex justify-content-between align-items-center mb-2 border-bottom border-success border-opacity-25 pb-1">
                                        <span class="text-dark fw-bold small lh-sm" x-text="label"></span>
                                        <div class="text-end" style="flex-shrink: 0;">
                                            <span class="fw-bold text-success" x-text="new Intl.NumberFormat('id-ID').format(item.val || item)"></span>
                                            <template x-if="item.is_tes_urine"><div class="lh-1 mt-1"><span class="badge bg-danger text-white" style="font-size: 0.6rem;">Positif: <span x-text="item.positif"></span></span></div></template>
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
                            <div class="bg-warning text-dark p-3 d-flex flex-column justify-content-center align-items-center text-center" style="width: 35%; flex-shrink: 0;">
                                <h2 class="fw-bold mb-2 display-6" x-text="p2mCards.media.total_freq">0</h2>
                                <span class="text-dark-50 fw-bold small lh-sm">Total<br>Pelaksanaan</span>
                                <div class="mt-3 badge bg-dark text-white bg-opacity-25 rounded-pill px-3 py-1 small"><i class="bi bi-clock me-1"></i> Durasi: <span x-text="p2mCards.media.total_durasi"></span> Hari</div>
                            </div>
                            <div class="flex-grow-1 bg-warning-subtle p-3 d-flex flex-column justify-content-center">
                                <template x-for="(item, label) in p2mCards.media.list">
                                    <div class="d-flex justify-content-between align-items-center mb-2 border-bottom border-warning border-opacity-25 pb-1">
                                        <span class="text-dark fw-bold small" x-text="label"></span>
                                        <div class="text-end lh-1" style="flex-shrink: 0;"><span class="fw-bold text-dark d-block small" x-text="item.freq + ' x'"></span><template x-if="item.durasi > 0"><span class="text-muted fw-bold" style="font-size: 0.7rem;" x-text="item.durasi + ' Hari'"></span></template></div>
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
                            <div class="bg-info text-white p-3 d-flex flex-column justify-content-center align-items-center text-center" style="width: 35%; flex-shrink: 0;">
                                <h2 class="fw-bold mb-2 display-6" x-text="p2mCards.wilayah.total">0</h2>
                                <span class="text-white-50 fw-bold small lh-sm">Kawasan<br>Binaan</span>
                            </div>
                            <div class="flex-grow-1 bg-info-subtle p-3 d-flex flex-column justify-content-center">
                                <template x-for="(val, label) in p2mCards.wilayah.list">
                                    <div class="d-flex justify-content-between align-items-center border-bottom border-info border-opacity-25 py-3">
                                        <span class="text-dark fw-bold small" x-text="label"></span>
                                        <span class="fw-bold text-info h5 mb-0" x-text="val + (label.includes('Desa') ? ' Desa' : ' Lingkungan')"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- D. CHART RANKING --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3"><h6 class="m-0 fw-bold text-dark"><i class="bi bi-bar-chart-fill text-primary me-2"></i>Ranking Frekuensi Kegiatan</h6></div>
                <div class="card-body p-3"><div x-ref="p2mRankingChart" style="min-height: 500px;"></div></div>
            </div>

            {{-- E. CHART ANALISA --}}
            <div class="row g-4"> 
                
                {{-- FILTER SECTION --}}
                <div class="col-12">
                    <div class="bg-white p-3 rounded shadow-sm border d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                        <div>
                            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-sliders me-2 text-primary"></i>Filter Analisa Grafik</h6>
                        </div>
                        <div class="d-flex gap-2">
                            <select x-model="chartFilter.type" class="form-select border-secondary fw-bold text-dark fs-6" style="width: 280px;">
                                <option value="sosialisasi">Sosialisasi Tatap Muka</option>
                                <option value="tes_urine">Tes Urine (Deteksi Dini)</option>
                                <option value="upacara">Pembina Upacara</option>
                                <option value="cfd">Car Free Day</option>
                                <option value="safari">Safari Religi</option>
                                <option disabled>──────────</option>
                                <option value="media_elektronik">Media Elektronik</option>
                                <option value="media_non_elektronik">Media Non-Elektronik</option>
                                <option value="media_online">Media Online</option>
                                <option value="kie">KIE Keliling</option>
                                <option disabled>──────────</option>
                                <option value="desa_bersinar">Desa Bersinar (Terbentuk)</option>
                                <option value="lingkungan_bersinar">Lingkungan Bersinar (Terbentuk)</option>
                            </select>
                            <select x-model="chartFilter.year" class="form-select border-secondary fw-bold text-primary fs-6" style="width: 100px;">
                                @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- CHART 1 (ATAS): TREN KEGIATAN & PESERTA --}}
                <div class="col-12">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3">
                            <h6 class="m-0 fw-bold text-primary"><i class="bi bi-graph-up-arrow me-2"></i>Grafik Jumlah Kegiatan & Total Peserta</h6>
                        </div>
                        <div class="card-body p-4">
                            <div x-ref="leftChart" style="min-height: 450px;"></div>
                        </div>
                    </div>
                </div>

                {{-- CHART 2 (BAWAH): ANGGARAN & SASARAN --}}
                <div class="col-12">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="m-0 fw-bold text-success"><i class="bi bi-pie-chart me-2"></i>Rincian Sumber Dana & Sasaran</h6>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button @click="rightChartMode = 'anggaran'" :class="rightChartMode === 'anggaran' ? 'btn-success text-white' : 'btn-outline-secondary'" class="btn fw-bold">Anggaran</button>
                                <button x-show="hasSasaran" @click="rightChartMode = 'sasaran'" :class="rightChartMode === 'sasaran' ? 'btn-warning text-dark' : 'btn-outline-secondary'" class="btn fw-bold">Sasaran</button>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div x-ref="rightChart" style="min-height: 450px;"></div>
                        </div>
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
        Alpine.data('dashboardUnified', () => ({
            startYear: '{{ date("Y") }}',
            endYear: '{{ date("Y") }}',
            satkerId: '',
            activeTab: '{{ $defaultTab }}', 
            
            p2mCards: { kegiatan: { total: 0 }, orang: { total: 0, list: {} }, media: { total_freq: 0, total_durasi: 0, list: {} }, wilayah: { total: 0, list: {} } },
            
            chartFilter: { type: 'sosialisasi', year: '{{ date("Y") }}' },
            rightChartMode: 'anggaran',
            chartDataCache: null, 
            hasSasaran: true,

            charts: { p2mRanking: null, left: null, right: null },

            init() {
                let elSatker = document.getElementById('select-satker');
                if (elSatker) {
                    if (elSatker.tomselect) elSatker.tomselect.destroy();
                    let ts = new TomSelect(elSatker, { create: false, controlInput: null, allowEmptyOption: true, placeholder: "Pilih Satuan Kerja..." });
                    ts.on('change', (val) => { this.satkerId = val; });
                }
                
                if(this.activeTab === 'p2m') {
                    this.fetchP2M(); 
                    this.fetchChartData(); 
                }

                this.$watch('startYear', () => this.fetchP2M());
                this.$watch('endYear', () => this.fetchP2M());
                this.$watch('satkerId', () => { this.fetchP2M(); this.fetchChartData(); });
                this.$watch('chartFilter.type', () => { 
                    this.rightChartMode = 'anggaran'; 
                    this.fetchChartData(); 
                });
                this.$watch('chartFilter.year', () => this.fetchChartData());
                this.$watch('rightChartMode', () => this.renderRightChart(this.chartDataCache));
            },

            fetchP2M() {
                if(this.startYear > this.endYear) this.endYear = this.startYear;
                let url = `{{ route('api.dashboard.global') }}?scope=p2m&start_year=${this.startYear}&end_year=${this.endYear}&satker_id=${this.satkerId}`;
                fetch(url).then(res => res.json()).then(data => {
                    this.p2mCards = data;
                    this.renderRanking(data.ranking_chart);
                });
            },

            fetchChartData() {
                let url = `{{ route('api.dashboard.chart') }}?type=${this.chartFilter.type}&year=${this.chartFilter.year}&satker_id=${this.satkerId}`;
                fetch(url).then(res => res.json()).then(data => {
                    this.chartDataCache = data;
                    this.hasSasaran = data.config.has_sasaran;
                    
                    this.renderLeftChart(data);
                    this.renderRightChart(data);
                });
            },

            renderRanking(data) {
                let options = {
                    series: [{ name: 'Jumlah Laporan', data: data.data }],
                    chart: { type: 'bar', height: 500, toolbar: {show: false}, fontFamily: 'Nunito, sans-serif' },
                    plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '70%', distributed: true } },
                    colors: ['#0d6efd', '#198754', '#ffc107', '#0dcaf0', '#d63384', '#6f42c1', '#fd7e14', '#20c997'],
                    xaxis: { categories: data.labels, labels: { style: { fontSize: '13px', fontWeight: 'bold' } } },
                    yaxis: { labels: { style: { fontSize: '13px', fontWeight: 'bold', colors: '#333' } } },
                    legend: { show: false }, 
                    dataLabels: { enabled: true, textAnchor: 'start', style: { colors: ['#fff'], fontSize: '13px', fontWeight: 'bold' }, offsetX: 0 },
                    tooltip: { theme: 'light' }
                };
                if (this.charts.p2mRanking) {
                    this.charts.p2mRanking.updateOptions(options);
                } else {
                    this.charts.p2mRanking = new ApexCharts(this.$refs.p2mRankingChart, options);
                    this.charts.p2mRanking.render();
                }
            },

            // RENDER CHART KIRI (KOMBINASI WARNA BARU)
            renderLeftChart(data) {
                let unit = data.config.label_unit;
                let series = [
                    { name: 'Jumlah Kegiatan', type: 'column', data: data.tren.kegiatan }
                ];

                if (unit && unit !== '-') {
                    series.push({ name: unit, type: 'line', data: data.tren.dampak });
                }
                if (data.config.has_positif) {
                    series.push({ name: 'Terindikasi Positif', type: 'line', data: data.tren.positif });
                }

                let options = {
                    series: series,
                    chart: { height: 450, type: 'line', toolbar: { show: false }, fontFamily: 'Nunito' },
                    stroke: { width: [0, 4, 4], curve: 'smooth' }, 
                    plotOptions: { 
                        bar: { 
                            columnWidth: '50%', 
                            borderRadius: 4,
                            // POSISI DATA BATANG: DI ATAS
                            dataLabels: { position: 'top' } 
                        } 
                    },
                    // WARNA: BIRU (Kegiatan), MERAH (Peserta/Garis), HITAM (Positif)
                    colors: ['#0d6efd', '#dc3545', '#000000'], 
                    labels: data.labels,
                    xaxis: { labels: { style: { fontWeight: 'bold', fontSize: '15px', colors: '#000' } } }, 
                    yaxis: [
                        { title: { text: 'Jumlah Kegiatan', style: { fontSize: '14px', fontWeight: 'bold' } }, labels: { style: { colors: '#0d6efd', fontSize: '14px', fontWeight: 'bold' } } },
                        { opposite: true, title: { text: unit, style: { fontSize: '14px', fontWeight: 'bold' } }, labels: { style: { colors: '#dc3545', fontSize: '14px', fontWeight: 'bold' } } }
                    ],
                    // --- BERSIH TANPA BAYANGAN ---
                    dataLabels: { 
                        enabled: true, 
                        enabledOnSeries: undefined, 
                        // WARNA TEKS: BIRU UNTUK BATANG, MERAH UNTUK GARIS
                        style: { 
                            fontSize: '13px',  
                            fontWeight: 'bold',
                            colors: ['#0d6efd', '#dc3545', '#000000'] 
                        },
                        background: { enabled: false }, // MATIKAN BOX
                        dropShadow: { enabled: false }, // MATIKAN SHADOW
                        offsetY: -20, // GESER KE ATAS BIAR GAK NEMPEL
                        formatter: function (val, opts) {
                            if (val === 0) return ""; 
                            return new Intl.NumberFormat('id-ID').format(val);
                        }
                    },
                    tooltip: { theme: 'light', style: { fontSize: '14px' } },
                    legend: { position: 'top', fontSize: '14px', fontWeight: 'bold' }
                };

                if (this.charts.left) {
                    this.charts.left.updateOptions(options);
                } else {
                    this.charts.left = new ApexCharts(this.$refs.leftChart, options);
                    this.charts.left.render();
                }
            },

            // RENDER CHART KANAN
            renderRightChart(data) {
                if(!data) return;
                
                let series = [];
                let colors = [];

                if (this.rightChartMode === 'anggaran') {
                    series = [
                        { name: 'DIPA', data: data.anggaran.dipa },
                        { name: 'Non-DIPA', data: data.anggaran.non_dipa }
                    ];
                    colors = ['#0d6efd', '#ffc107']; 
                } else {
                    series = data.sasaran;
                    // PALET STANDAR: Hijau, Ungu, Oranye, Biru Muda
                    colors = ['#20c997', '#6f42c1', '#fd7e14', '#0dcaf0']; 
                }

                let options = {
                    series: series,
                    plotOptions: { bar: { borderRadius: 4, columnWidth: '70%' } },
                    chart: { type: 'bar', height: 450, stacked: true, toolbar: { show: false }, fontFamily: 'Nunito' },
                    colors: colors,
                    labels: data.labels,
                    xaxis: { labels: { style: { fontWeight: 'bold', fontSize: '15px', colors: '#000' } } },
                    yaxis: { labels: { style: { fontWeight: 'bold', fontSize: '14px' } } },
                    legend: { position: 'top', horizontalAlign: 'right', fontSize: '13px', fontWeight: 'bold' },
                    
                    dataLabels: { 
                        enabled: true, 
                        style: { fontSize: '13px', fontWeight: 'bold', colors: ['#fff'] },
                        dropShadow: { enabled: false }, // NO SHADOW
                        formatter: function (val, opts) {
                            if (val === 0) return ""; 
                            let total = 0;
                            let seriesAll = opts.w.config.series;
                            seriesAll.forEach(s => {
                                total += s.data[opts.dataPointIndex];
                            });
                            let percent = 0;
                            if(total > 0) percent = Math.round((val / total) * 100);
                            
                            if(percent < 5) return ""; // Sembunyikan jika terlalu kecil
                            
                            return val + " (" + percent + "%)";
                        }
                    },
                    tooltip: { theme: 'light', style: { fontSize: '14px' } }
                };

                if (this.charts.right) {
                    this.charts.right.destroy(); 
                    this.charts.right = new ApexCharts(this.$refs.rightChart, options);
                    this.charts.right.render();
                } else {
                    this.charts.right = new ApexCharts(this.$refs.rightChart, options);
                    this.charts.right.render();
                }
            },

            get satkerLabel() {
                if (this.satkerId === "") return "Menampilkan Data Gabungan Seluruh Satuan Kerja (Provinsi & Kab/Kota)";
                let el = document.getElementById('select-satker');
                if (el && el.options.length > 0) {
                    for (let i = 0; i < el.options.length; i++) {
                        if (el.options[i].value == this.satkerId) return "Menampilkan Data: " + el.options[i].text;
                    }
                }
                return "Menampilkan Data Satuan Kerja";
            }
        }));
    });
</script>
@endpush