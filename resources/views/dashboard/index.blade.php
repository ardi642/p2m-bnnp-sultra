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
                    @if($permissions['p2m']) 
                        <button @click="activeTab = 'p2m'" :class="activeTab === 'p2m' ? 'bg-primary text-white shadow' : 'bg-light text-secondary'" class="nav-link fw-bold rounded transition-all py-2 fs-6"><i class="bi bi-people-fill me-2"></i>P2M</button> 
                    @endif
                    
                    @if($permissions['berantas']) 
                        <button @click="activeTab = 'berantas'" :class="activeTab === 'berantas' ? 'bg-primary text-white shadow' : 'bg-light text-secondary'" class="nav-link fw-bold rounded transition-all py-2 fs-6"><i class="bi bi-shield-shaded me-2"></i>Berantas</button> 
                    @endif
                    
                    @if($permissions['rehab']) 
                        <button @click="activeTab = 'rehab'" :class="activeTab === 'rehab' ? 'bg-primary text-white shadow' : 'bg-light text-secondary'" class="nav-link fw-bold rounded transition-all py-2 fs-6"><i class="bi bi-heart-pulse-fill me-2"></i>Rehab</button> 
                    @endif
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

        {{-- ========================================================================= --}}
        {{-- TAB BERANTAS --}}
        {{-- ========================================================================= --}}

        <div x-show="activeTab === 'berantas'" x-transition>
            {{-- A. FILTER RENTANG TAHUN (TETAP UNTUK KARTU ATAS) --}}
            <div class="d-flex justify-content-end mb-3">
                <div class="d-flex align-items-center bg-white p-2 rounded shadow-sm border gap-2">
                    <span class="fw-bold text-muted small"><i class="bi bi-calendar-range me-2 text-primary"></i>Rentang Tahun:</span>
                    <select x-model="startYear" class="form-select form-select-sm border-secondary fw-bold text-primary" style="width: 90px;">@foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach</select>
                    <span class="fw-bold">-</span>
                    <select x-model="endYear" class="form-select form-select-sm border-secondary fw-bold text-primary" style="width: 90px;">@foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach</select>
                </div>
            </div>

            {{-- B. KARTU RINGKASAN UTAMA (JANGAN DIUBAH - TETAP ADA) --}}
            <div class="row g-3 mb-4">
                {{-- Ungkap Kasus (Biru) --}}
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 bg-primary text-white p-4 overflow-hidden position-relative">
                        <i class="bi bi-shield-shaded position-absolute opacity-10" style="font-size: 7rem; right: -10px; top: -10px;"></i>
                        <h6 class="text-uppercase fw-bold opacity-75 mb-3 small">UNGKAP KASUS (LKN)</h6>
                        <div class="d-flex gap-4 mb-3">
                            <div><small class="d-block opacity-75">Total LKN</small><h3 class="fw-bold" x-text="berantasCards.lkn.kasus">0</h3></div>
                            <div><small class="d-block opacity-75">Total Tersangka</small><h3 class="fw-bold" x-text="berantasCards.lkn.tersangka">0</h3></div>
                        </div>
                        <div class="bg-white bg-opacity-10 p-2 rounded border border-white border-opacity-25 mt-auto small">
                            <div class="d-flex justify-content-between"><span>Total Berat Narkotika:</span><span class="fw-bold" x-text="berantasCards.lkn.berat">0 g</span></div>
                            <div class="d-flex justify-content-between"><span>Total Item Narkotika:</span><span class="fw-bold" x-text="berantasCards.lkn.item">0 Item</span></div>
                        </div>
                    </div>
                </div>
                {{-- TAT (Cyan) --}}
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 bg-info text-white p-4 overflow-hidden position-relative">
                        <i class="bi bi-person-lines-fill position-absolute opacity-10" style="font-size: 7rem; right: -10px; top: -10px;"></i>
                        <h6 class="text-uppercase fw-bold opacity-75 mb-3 small">ASESMEN (TAT)</h6>
                        <div class="d-flex gap-4 mb-3">
                            <div><small class="d-block opacity-75">Total Kasus</small><h3 class="fw-bold" x-text="berantasCards.tat.kasus">0</h3></div>
                            <div><small class="d-block opacity-75">Total Tersangka</small><h3 class="fw-bold" x-text="berantasCards.tat.tersangka">0</h3></div>
                        </div>
                        <div class="bg-white bg-opacity-10 p-2 rounded border border-white border-opacity-25 mt-auto small">
                            <div class="d-flex justify-content-between"><span>Total Berat Narkotika:</span><span class="fw-bold" x-text="berantasCards.tat.berat">0 g</span></div>
                            <div class="d-flex justify-content-between"><span>Total Item Narkotika:</span><span class="fw-bold" x-text="berantasCards.tat.item">0 Item</span></div>
                        </div>
                    </div>
                </div>
                {{-- Register (Merah) --}}
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 bg-danger text-white p-4">
                        <h6 class="text-uppercase fw-bold opacity-75 mb-3 text-center small">BARANG BUKTI</h6>
                        <div class="text-center mb-3">
                            <small class="d-block opacity-75">Total Berat Narkotika</small>
                            <h2 class="fw-bold" x-text="berantasCards.bb.total_berat">0 g</h2>
                            <span class="badge bg-white text-danger fw-bold" x-text="berantasCards.bb.total_item">0 Item</span>
                        </div>
                        <div class="row g-2 mt-auto text-center small">
                            <div class="col-6"><div class="bg-white bg-opacity-10 p-2 rounded border border-white border-opacity-25"><small class="d-block opacity-75">Hasil Tangkap</small><b class="d-block" x-text="berantasCards.bb.tangkap_berat">0</b><span style="font-size:0.65rem" x-text="berantasCards.bb.tangkap_item">0 Item</span></div></div>
                            <div class="col-6"><div class="bg-white bg-opacity-10 p-2 rounded border border-white border-opacity-25"><small class="d-block opacity-75">Temuan</small><b class="d-block" x-text="berantasCards.bb.temuan_berat">0</b><span style="font-size:0.65rem" x-text="berantasCards.bb.temuan_item">0 Item</span></div></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- C. DETAIL TREN BULANAN PER BARIS (FONT BESAR & KONTRAS) --}}
            <div class="card border-0 shadow-sm p-4 mt-4">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                    <h5 class="fw-bold m-0 text-dark"><i class="bi bi-graph-up text-primary me-2"></i>Tren Bulanan Per Pilar (Narkotika)</h5>
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-bold text-muted">Tahun Grafik:</span>
                        <select x-model="chartFilter.year" class="form-select border-primary fw-bold text-primary" style="width: 120px;">@foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach</select>
                    </div>
                </div>

                {{-- BARIS 1: LKN --}}
                <div class="mb-5 border-bottom pb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                        <h6 class="fw-bold text-primary mb-0 fs-5">1. UNGKAP KASUS (LKN)</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-white text-dark border border-secondary fs-6 p-2">Total LKN: <span x-text="berantasTrendSummary.lkn.kasus"></span></span>
                            <span class="badge bg-white text-dark border border-secondary fs-6 p-2">Total Tersangka: <span x-text="berantasTrendSummary.lkn.tersangka"></span></span>
                            <span class="badge bg-white text-dark border border-secondary fs-6 p-2">Total BB Narkotika: <span x-text="berantasTrendSummary.lkn.item"></span></span>
                            <span class="badge bg-dark text-white fs-6 p-2">Total Berat: <span x-text="berantasTrendSummary.lkn.berat"></span></span>
                        </div>
                    </div>
                    <div x-ref="chartLknFull" style="min-height: 400px;"></div>
                </div>

                {{-- BARIS 2: TAT --}}
                <div class="mb-5 border-bottom pb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                        <h6 class="fw-bold text-info mb-0 fs-5">2. ASESMEN (TAT)</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-white text-dark border border-secondary fs-6 p-2">Total Kasus: <span x-text="berantasTrendSummary.tat.kasus"></span></span>
                            <span class="badge bg-white text-dark border border-secondary fs-6 p-2">Total Tersangka: <span x-text="berantasTrendSummary.tat.tersangka"></span></span>
                            <span class="badge bg-white text-dark border border-secondary fs-6 p-2">Total BB Narkotika: <span x-text="berantasTrendSummary.tat.item"></span></span>
                            <span class="badge bg-dark text-white fs-6 p-2">Total Berat: <span x-text="berantasTrendSummary.tat.berat"></span></span>
                        </div>
                    </div>
                    <div x-ref="chartTatFull" style="min-height: 400px;"></div>
                </div>

                {{-- BARIS 3: REGISTER --}}
                <div class="pb-2">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                        <h6 class="fw-bold text-danger mb-0 fs-5">3. BARANG BUKTI (REGISTER)</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-white text-dark border border-secondary fs-6 p-2">Total Register: <span x-text="berantasTrendSummary.bb.total_reg"></span></span>
                            <span class="badge bg-white text-dark border border-secondary fs-6 p-2">Total BB Narkotika: <span x-text="berantasTrendSummary.bb.total_item"></span></span>
                            <span class="badge bg-dark text-white fs-6 p-2">Total Berat: <span x-text="berantasTrendSummary.bb.total_berat"></span></span>
                            <span class="badge bg-primary text-white fs-6 p-2">Tangkap: <span x-text="berantasTrendSummary.bb.tangkap"></span></span>
                            <span class="badge bg-warning text-dark fs-6 p-2">Temuan: <span x-text="berantasTrendSummary.bb.temuan"></span></span>
                        </div>
                    </div>
                    <div x-ref="chartBbFull" style="min-height: 400px;"></div>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- TAB REHAB --}}
        {{-- ========================================================================= --}}
        <div x-show="activeTab === 'rehab'" x-transition>
            {{-- A. FILTER RENTANG TAHUN UNTUK KARTU ATAS --}}
            <div class="d-flex justify-content-end mb-3">
                <div class="d-flex align-items-center bg-white p-2 rounded shadow-sm border gap-2">
                    <span class="fw-bold text-muted small"><i class="bi bi-calendar-range me-2 text-success"></i>Rentang Tahun:</span>
                    <select x-model="startYear" class="form-select form-select-sm border-secondary fw-bold text-success" style="width: 90px;">@foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach</select>
                    <span class="fw-bold">-</span>
                    <select x-model="endYear" class="form-select form-select-sm border-secondary fw-bold text-success" style="width: 90px;">@foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach</select>
                </div>
            </div>

            {{-- B. KARTU RINGKASAN UTAMA REHAB --}}
            <div class="row g-3 mb-4">
                <template x-for="(data, key) in {rj: 'RAWAT JALAN', pr: 'PASCA REHABILITASI', sk: 'LAYANAN SKHPN'}">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100 p-4 text-white overflow-hidden position-relative" :class="key === 'rj' ? 'bg-success' : (key === 'pr' ? 'bg-teal' : 'bg-dark')" style="background-color: #198754;">
                            <h6 class="text-uppercase fw-bold opacity-75 mb-3 small" x-text="data"></h6>
                            <div class="d-flex justify-content-between align-items-end mb-3">
                                <div><small class="d-block opacity-75">Total Realisasi</small><h2 class="fw-bold m-0" x-text="rehabCards[key].realisasi">0</h2></div>
                                <div class="text-end"><span class="display-6 fw-bold" x-text="rehabCards[key].pct + '%'">0%</span><small class="d-block opacity-75">Capaian</small></div>
                            </div>
                            <div class="bg-white bg-opacity-10 p-2 rounded border border-white border-opacity-25 mt-auto">
                                <div class="d-flex justify-content-between small"><span>Target Tahunan:</span><span class="fw-bold" x-text="rehabCards[key].target">0</span></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- C. GRAFIK DETAIL PER BARIS --}}
            <div class="card border-0 shadow-sm p-4 mt-4">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                    <h5 class="fw-bold m-0 text-dark"><i class="bi bi-graph-up text-success me-2"></i>Tren Capaian Bulanan</h5>
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-bold text-muted">Tahun Grafik:</span>
                        <select x-model="chartFilter.year" class="form-select border-success fw-bold text-success" style="width: 120px;">@foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach</select>
                    </div>
                </div>

                <template x-for="(label, key) in {rj: '1. LAYANAN RAWAT JALAN', pr: '2. LAYANAN PASCA REHABILITASI', sk: '3. LAYANAN SKHPN'}">
                    <div class="mb-5 border-bottom pb-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                            <h6 class="fw-bold text-success mb-0 fs-5" x-text="label"></h6>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-white text-dark border border-secondary fs-6 p-2">Target: <span x-text="rehabTrendSummary[key].t"></span></span>
                                <span class="badge bg-white text-dark border border-secondary fs-6 p-2">Realisasi: <span x-text="rehabTrendSummary[key].r"></span></span>
                                <span class="badge fs-6 p-2" :class="rehabTrendSummary[key].p >= 80 ? 'bg-success text-white' : 'bg-warning text-dark'">Capaian: <span x-text="rehabTrendSummary[key].p + '%'"></span></span>
                            </div>
                        </div>
                        <div :x-ref="'chartRehab' + key" style="min-height: 400px;"></div>
                    </div>
                </template>
            </div>
        </div>

    </div>
</main>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('dashboardUnified', () => ({
            // ==========================================================
            // 1. STATE GLOBAL & P2M (TETAP SAMA)
            // ==========================================================
            startYear: '{{ date("Y") }}',
            endYear: '{{ date("Y") }}',
            satkerId: '',
            activeTab: '{{ $defaultTab }}',

            p2mCards: {
                kegiatan: { total: 0 },
                orang: { total: 0, list: {} },
                media: { total_freq: 0, total_durasi: 0, list: {} },
                wilayah: { total: 0, list: {} }
            },
            chartFilter: { type: 'sosialisasi', year: '{{ date("Y") }}' },
            rightChartMode: 'anggaran',
            chartDataCache: null,
            hasSasaran: true,

            // ==========================================================
            // 2. STATE BERANTAS (TETAP SAMA)
            // ==========================================================
            berantasCards: {
                lkn: { kasus: 0, tersangka: 0, berat: '0 g', item: '0 Item' },
                tat: { kasus: 0, tersangka: 0, berat: '0 g', item: '0 Item' },
                bb: {
                    total_berat: '0 g',
                    total_item: '0 Item',
                    tangkap_berat: '0 g',
                    tangkap_item: '0 Item',
                    temuan_berat: '0 g',
                    temuan_item: '0 Item'
                }
            },
            berantasTrendSummary: {
                lkn: { kasus: 0, tersangka: 0, item: 0, berat: '0 g' },
                tat: { kasus: 0, tersangka: 0, item: 0, berat: '0 g' },
                bb: { total_reg: 0, total_item: 0, total_berat: '0 g', tangkap: '0', temuan: '0' }
            },

            // ==========================================================
            // 3. STATE REHAB (TAMBAHAN BARU)
            // ==========================================================
            rehabCards: {
                rj: { target: 0, realisasi: 0, pct: 0 },
                pr: { target: 0, realisasi: 0, pct: 0 },
                sk: { target: 0, realisasi: 0, pct: 0 }
            },
            rehabTrendSummary: {
                rj: { t: 0, r: 0, p: 0 },
                pr: { t: 0, r: 0, p: 0 },
                sk: { t: 0, r: 0, p: 0 }
            },

            // Instance Chart Containers
            charts: {
                p2mRanking: null,
                left: null,
                right: null,
                chartLknFull: null,
                chartTatFull: null,
                chartBbFull: null,
                chartRehabrj: null,
                chartRehabpr: null,
                chartRehabsk: null
            },

            // ==========================================================
            // 4. INIT & WATCHERS
            // ==========================================================
            init() {
                let elSatker = document.getElementById('select-satker');
                if (elSatker && typeof TomSelect !== 'undefined') {
                    if (elSatker.tomselect) elSatker.tomselect.destroy();
                    let ts = new TomSelect(elSatker, {
                        create: false,
                        controlInput: null,
                        allowEmptyOption: true,
                        placeholder: "Pilih Satuan Kerja..."
                    });
                    ts.on('change', (val) => { this.satkerId = val; });
                }

                this.loadActiveTabData();

                // Global Watchers
                this.$watch('activeTab', () => this.loadActiveTabData());
                this.$watch('startYear', () => this.loadActiveTabData());
                this.$watch('endYear', () => this.loadActiveTabData());
                this.$watch('satkerId', () => this.loadActiveTabData());

                // Grafik Specific Watchers
                this.$watch('chartFilter.year', () => {
                    if (this.activeTab === 'berantas') this.fetchBerantasChart();
                    if (this.activeTab === 'p2m') this.fetchChartData();
                    if (this.activeTab === 'rehab') this.fetchRehabChart();
                });

                this.$watch('chartFilter.type', () => {
                    if (this.activeTab === 'p2m') this.fetchChartData();
                });

                this.$watch('rightChartMode', () => {
                    if (this.activeTab === 'p2m') this.renderRightChart(this.chartDataCache);
                });
            },

            loadActiveTabData() {
                if (this.startYear > this.endYear) this.endYear = this.startYear;

                if (this.activeTab === 'p2m') {
                    this.fetchP2MGlobal();
                    this.fetchChartData();
                } else if (this.activeTab === 'berantas') {
                    this.fetchBerantasGlobal();
                    this.fetchBerantasChart();
                } else if (this.activeTab === 'rehab') {
                    this.fetchRehabGlobal();
                    this.fetchRehabChart();
                }
            },

            // ==========================================================
            // 5. FUNGSI REHAB (RELAWAN BARU)
            // ==========================================================
            fetchRehabGlobal() {
                let url = `{{ route('api.dashboard.global') }}?scope=rehab&start_year=${this.startYear}&end_year=${this.endYear}&satker_id=${this.satkerId}`;
                fetch(url)
                    .then(res => res.json())
                    .then(data => { this.rehabCards = data; });
            },

            fetchRehabChart() {
                let url = `{{ route('api.dashboard.chart') }}?scope=rehab&year=${this.chartFilter.year}&satker_id=${this.satkerId}`;
                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        this.rehabTrendSummary = data.summary;
                        this.renderRehabGrouped(this.$refs.chartRehabrj, data.tren.rj, '#198754', 'chartRehabrj');
                        this.renderRehabGrouped(this.$refs.chartRehabpr, data.tren.pr, '#20c997', 'chartRehabpr');
                        this.renderRehabGrouped(this.$refs.chartRehabsk, data.tren.sk, '#0dcaf0', 'chartRehabsk');
                    });
            },

            renderRehabGrouped(el, data, color, chartKey) {
                let options = {
                    series: [
                        { name: 'Target', data: data.t },
                        { name: 'Realisasi', data: data.r }
                    ],
                    chart: {
                        type: 'bar',
                        height: 400,
                        toolbar: { show: true },
                        fontFamily: 'Nunito'
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '65%',
                            borderRadius: 4,
                            dataLabels: { position: 'top' }
                        }
                    },
                    colors: ['#dee2e6', color],
                    dataLabels: {
                        enabled: true,
                        offsetY: -25,
                        style: {
                            fontSize: '13px',
                            fontWeight: 'bold',
                            colors: ['#444']
                        },
                        formatter: (val) => val > 0 ? val : ''
                    },
                    xaxis: {
                        categories: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
                        labels: {
                            style: { fontSize: '13px', fontWeight: 'bold', colors: '#000' }
                        }
                    },
                    yaxis: {
                        title: { text: "Jumlah Layanan", style: { fontWeight: 'bold', fontSize: '14px' } },
                        labels: { style: { fontSize: '12px' } }
                    },
                    legend: {
                        position: 'top',
                        fontSize: '14px',
                        fontWeight: 'bold'
                    },
                    tooltip: { shared: true, style: { fontSize: '14px' } }
                };

                if (this.charts[chartKey]) this.charts[chartKey].destroy();
                this.charts[chartKey] = new ApexCharts(el, options);
                this.charts[chartKey].render();
            },

            // ==========================================================
            // 6. FUNGSI BERANTAS (TETAP SAMA - SUDAH RAPI)
            // ==========================================================
            fetchBerantasGlobal() {
                let url = `{{ route('api.dashboard.global') }}?scope=berantas&start_year=${this.startYear}&end_year=${this.endYear}&satker_id=${this.satkerId}`;
                fetch(url)
                    .then(res => res.json())
                    .then(data => { this.berantasCards = data; });
            },

            fetchBerantasChart() {
                let url = `{{ route('api.dashboard.chart') }}?scope=berantas&year=${this.chartFilter.year}&satker_id=${this.satkerId}`;
                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        this.berantasTrendSummary = data.summary;
                        
                        // LKN
                        this.renderBerantasCombo(this.$refs.chartLknFull, [
                            { name: 'Total LKN', type: 'column', data: data.tren.lkn.kasus },
                            { name: 'Tersangka', type: 'column', data: data.tren.lkn.tersangka },
                            { name: 'Item Narko', type: 'column', data: data.tren.lkn.item },
                            { name: 'Berat (Gram)', type: 'line', data: data.tren.lkn.berat }
                        ], ['#0d6efd', '#ffc107', '#198754', '#000000'], 'chartLknFull');

                        // TAT
                        this.renderBerantasCombo(this.$refs.chartTatFull, [
                            { name: 'Total TAT', type: 'column', data: data.tren.tat.kasus },
                            { name: 'Tersangka', type: 'column', data: data.tren.tat.tersangka },
                            { name: 'Item Narko', type: 'column', data: data.tren.tat.item },
                            { name: 'Berat (Gram)', type: 'line', data: data.tren.tat.berat }
                        ], ['#0dcaf0', '#ffc107', '#198754', '#000000'], 'chartTatFull');

                        // Register
                        this.renderBerantasCombo(this.$refs.chartBbFull, [
                            { name: 'Register', type: 'column', data: data.tren.bb.reg },
                            { name: 'Tangkap', type: 'column', data: data.tren.bb.tangkap },
                            { name: 'Temuan', type: 'column', data: data.tren.bb.temuan },
                            { name: 'Berat (Gram)', type: 'line', data: data.tren.bb.berat }
                        ], ['#dc3545', '#0d6efd', '#ffc107', '#000000'], 'chartBbFull');
                    });
            },

            renderBerantasCombo(el, series, colorArray, chartKey) {
                let options = {
                    series: series,
                    chart: { height: 400, type: 'line', toolbar: { show: true }, fontFamily: 'Nunito' },
                    stroke: { width: series.map(s => s.type === 'line' ? 5 : 0), curve: 'smooth' },
                    colors: colorArray,
                    dataLabels: {
                        enabled: true,
                        enabledOnSeries: [0, 1, 2, 3],
                        formatter: (val) => val > 0 ? new Intl.NumberFormat('id-ID').format(val) : '',
                        style: { fontSize: '13px', fontWeight: 'bold' },
                        background: { enabled: true, opacity: 0.9, borderRadius: 2, padding: 4 }
                    },
                    xaxis: {
                        categories: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
                        labels: { style: { fontSize: '13px', fontWeight: 'bold', colors: '#000' } }
                    },
                    yaxis: [
                        { title: { text: "Jumlah", style: { fontSize: '14px', fontWeight: 'bold' } }, labels: { style: { fontSize: '12px' } } },
                        { opposite: true, title: { text: "Volume (Gram)", style: { fontSize: '14px', fontWeight: 'bold' } }, labels: { style: { fontSize: '12px' } } }
                    ],
                    legend: { position: 'top', fontSize: '14px', fontWeight: 'bold' },
                    tooltip: { shared: true, style: { fontSize: '14px' } }
                };
                if (this.charts[chartKey]) this.charts[chartKey].destroy();
                this.charts[chartKey] = new ApexCharts(el, options);
                this.charts[chartKey].render();
            },

            // ==========================================================
            // 7. FUNGSI P2M (ORIGINAL - TIDAK BERUBAH)
            // ==========================================================
            fetchP2MGlobal() {
                let url = `{{ route('api.dashboard.global') }}?scope=p2m&start_year=${this.startYear}&end_year=${this.endYear}&satker_id=${this.satkerId}`;
                fetch(url).then(res => res.json()).then(data => {
                    this.p2mCards = data;
                    this.renderRanking(data.ranking_chart);
                });
            },

            fetchChartData() {
                let url = `{{ route('api.dashboard.chart') }}?scope=p2m&type=${this.chartFilter.type}&year=${this.chartFilter.year}&satker_id=${this.satkerId}`;
                fetch(url).then(res => res.json()).then(data => {
                    this.chartDataCache = data;
                    this.hasSasaran = data.config.has_sasaran;
                    this.renderLeftChart(data);
                    this.renderRightChart(data);
                });
            },

            renderRanking(data) {
                let options = {
                    series: [{ name: 'Laporan', data: data.data }],
                    chart: { type: 'bar', height: 500, toolbar: {show: false} },
                    plotOptions: { bar: { horizontal: true, distributed: true } },
                    xaxis: { categories: data.labels },
                    dataLabels: { enabled: true }
                };
                if (this.charts.p2mRanking) this.charts.p2mRanking.updateOptions(options);
                else {
                    this.charts.p2mRanking = new ApexCharts(this.$refs.p2mRankingChart, options);
                    this.charts.p2mRanking.render();
                }
            },

            renderLeftChart(data) {
                let unit = data.config.label_unit;
                let series = [{ name: 'Kegiatan', type: 'column', data: data.tren.kegiatan }];
                if (unit && unit !== '-') series.push({ name: unit, type: 'line', data: data.tren.dampak });
                if (data.config.has_positif) series.push({ name: 'Positif', type: 'line', data: data.tren.positif });

                let options = {
                    series: series,
                    chart: { height: 450, type: 'line', toolbar: { show: false } },
                    stroke: { width: [0, 4, 4], curve: 'smooth' },
                    colors: ['#0d6efd', '#dc3545', '#000000'],
                    labels: data.labels,
                    dataLabels: {
                        enabled: true,
                        enabledOnSeries: [0, 1],
                        formatter: (val) => val > 0 ? new Intl.NumberFormat('id-ID').format(val) : ""
                    },
                    yaxis: [{ title: { text: 'Kegiatan' } }, { opposite: true, title: { text: unit } }]
                };
                if (this.charts.left) this.charts.left.updateOptions(options);
                else {
                    this.charts.left = new ApexCharts(this.$refs.leftChart, options);
                    this.charts.left.render();
                }
            },

            renderRightChart(data) {
                if(!data) return;
                let s = this.rightChartMode === 'anggaran' ? [{ name: 'DIPA', data: data.anggaran.dipa }, { name: 'Non-DIPA', data: data.anggaran.non_dipa }] : data.sasaran;
                let options = {
                    series: s,
                    chart: { type: 'bar', height: 450, stacked: true, toolbar: { show: false } },
                    labels: data.labels,
                    dataLabels: {
                        enabled: true,
                        formatter: function (val, opts) {
                            if (val === 0) return "";
                            let t = 0;
                            opts.w.config.series.forEach(x => { t += x.data[opts.dataPointIndex]; });
                            return val + " (" + Math.round((val / t) * 100) + "%)";
                        }
                    }
                };
                if (this.charts.right) this.charts.right.destroy();
                this.charts.right = new ApexCharts(this.$refs.rightChart, options);
                this.charts.right.render();
            },

            get satkerLabel() {
                if (this.satkerId === "") return "Seluruh Satuan Kerja";
                let el = document.getElementById('select-satker');
                return el && el.options[el.selectedIndex] ? "Data: " + el.options[el.selectedIndex].text : "Data Satker";
            }
        }));
    });
</script>
@endpush