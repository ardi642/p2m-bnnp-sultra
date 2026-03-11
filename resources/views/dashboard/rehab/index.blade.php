@extends('admin')

@section('content')
<main class="admin-main bg-light" x-data="dashboardRehab()" x-init="init()" style="min-height: 100vh;">
    <div class="container-fluid p-4">

        {{-- ========================================================= --}}
        {{-- HEADER & IDENTITAS --}}
        {{-- ========================================================= --}}
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3">
            <div>
                <h1 class="h3 mb-2 fw-bold text-dark">Dashboard Kinerja Rehabilitasi</h1>
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

        {{-- ========================================================= --}}
        {{-- FILTER GLOBAL (TAHUN KINERJA) --}}
        {{-- ========================================================= --}}
        <div class="d-flex flex-wrap justify-content-end gap-3 mb-3">
            <div class="d-flex align-items-center bg-white p-2 rounded-3 shadow-sm border border-light gap-2">
                <span class="fw-bold text-muted small ms-2"><i class="bi bi-calendar-event me-2 text-primary"></i>Tahun Kinerja:</span>
                <select x-model="globalYear" class="form-select form-select-sm border-0 bg-light fw-bold text-dark w-auto shadow-none pe-4 cursor-pointer">
                    @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                </select>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- 4 KARTU UTAMA (Kunjungan vs Unik & Persentase Target) --}}
        {{-- ========================================================= --}}
        <div class="row g-3 mb-5">
            {{-- RAWAT JALAN --}}
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 bg-white border-start border-4 border-primary rounded-3 overflow-hidden">
                    <div class="card-body p-4 d-flex flex-column justify-content-center">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary bg-opacity-10 text-primary p-2 rounded me-3"><i class="bi bi-bandaid-fill fs-3"></i></div>
                            <h5 class="fw-bold text-dark mb-0">Rawat Jalan</h5>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <span class="text-muted small fw-bold">Realisasi</span>
                                <h3 class="fw-bold text-primary mb-0" x-text="formatAngka(cards.rj.real)"></h3>
                            </div>
                            <div class="col-6 border-start ps-3">
                                <span class="text-muted small fw-bold">Target</span>
                                <div class="d-flex align-items-center gap-2">
                                    <h3 class="fw-bold text-dark mb-0" x-text="formatAngka(cards.rj.target)"></h3>
                                    <span class="badge rounded-pill" :class="getPctClass(cards.rj.pct)" x-text="cards.rj.pct + '%'"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- PASCA REHAB --}}
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 bg-white border-start border-4 border-warning rounded-3 overflow-hidden">
                    <div class="card-body p-4 d-flex flex-column justify-content-center">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-warning bg-opacity-10 text-warning p-2 rounded me-3"><i class="bi bi-house-heart-fill fs-3"></i></div>
                            <h5 class="fw-bold text-dark mb-0">Pasca Rehab</h5>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <span class="text-muted small fw-bold">Realisasi</span>
                                <h3 class="fw-bold text-warning mb-0" x-text="formatAngka(cards.pasca.real)"></h3>
                            </div>
                            <div class="col-6 border-start ps-3">
                                <span class="text-muted small fw-bold">Target</span>
                                <div class="d-flex align-items-center gap-2">
                                    <h3 class="fw-bold text-dark mb-0" x-text="formatAngka(cards.pasca.target)"></h3>
                                    <span class="badge rounded-pill" :class="getPctClass(cards.pasca.pct)" x-text="cards.pasca.pct + '%'"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SKHPN --}}
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 bg-white border-start border-4 border-success rounded-3 overflow-hidden">
                    <div class="card-body p-4 d-flex flex-column justify-content-center">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success bg-opacity-10 text-success p-2 rounded me-3"><i class="bi bi-file-earmark-medical-fill fs-3"></i></div>
                            <h5 class="fw-bold text-dark mb-0">SKHPN</h5>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <span class="text-muted small fw-bold">Realisasi</span>
                                <h3 class="fw-bold text-success mb-0" x-text="formatAngka(cards.skhpn.real)"></h3>
                            </div>
                            <div class="col-6 border-start ps-3">
                                <span class="text-muted small fw-bold">Target</span>
                                <div class="d-flex align-items-center gap-2">
                                    <h3 class="fw-bold text-dark mb-0" x-text="formatAngka(cards.skhpn.target)"></h3>
                                    <span class="badge rounded-pill" :class="getPctClass(cards.skhpn.pct)" x-text="cards.skhpn.pct + '%'"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TOTAL KLIEN --}}
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 bg-white border-start border-4 border-info rounded-3 overflow-hidden">
                    <div class="card-body p-4 d-flex flex-column justify-content-center">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-info bg-opacity-10 text-info p-2 rounded me-3"><i class="bi bi-people-fill fs-3"></i></div>
                            <h5 class="fw-bold text-dark mb-0">Klien Dilayani</h5>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <span class="text-muted small fw-bold">Total Kunjungan</span>
                                <h3 class="fw-bold text-info mb-0" x-text="formatAngka(cards.klien.total)"></h3>
                            </div>
                            <div class="col-6 border-start ps-3">
                                <span class="text-muted small fw-bold">Klien Unik</span>
                                <h3 class="fw-bold text-primary mb-0" x-text="formatAngka(cards.klien.unik)"></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- BLOK A: LAYANAN KINERJA (MURNI BATANG REALISASI) --}}
        {{-- ========================================================= --}}
        <div class="bg-white p-4 rounded-4 shadow-sm mb-4 border">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                <div><h5 class="m-0 fw-bold text-dark"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Pusat Analisis Layanan Kinerja</h5></div>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <div class="d-flex align-items-center bg-light rounded-3 px-3 py-1">
                        <i class="bi bi-filter text-muted me-2"></i>
                        <select x-model="layanan.time" class="form-select border-0 bg-transparent text-dark fw-bold shadow-none cursor-pointer pe-4" style="min-width: 150px; outline: none;">
                            <optgroup label="Tampilan Tren">
                                <option value="all">Total Akumulasi</option>
                                <option value="per_triwulan">Tren Per Triwulan</option>
                                <option value="per_bulan">Tren Per Bulan</option>
                            </optgroup>
                            <optgroup label="Triwulan Spesifik">
                                <option value="Q1">Triwulan I</option><option value="Q2">Triwulan II</option><option value="Q3">Triwulan III</option><option value="Q4">Triwulan IV</option>
                            </optgroup>
                            <optgroup label="Bulan Spesifik">
                                <option value="1">Januari</option><option value="2">Februari</option><option value="3">Maret</option><option value="4">April</option>
                                <option value="5">Mei</option><option value="6">Juni</option><option value="7">Juli</option><option value="8">Agustus</option>
                                <option value="9">September</option><option value="10">Oktober</option><option value="11">November</option><option value="12">Desember</option>
                            </optgroup>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm bg-white rounded-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div></div> {{-- Spacer kosong --}}
                        <div class="d-flex bg-light p-1 rounded-pill ms-auto flex-wrap">
                            <button @click="layanan.tabTrend = 'gabungan'" :class="layanan.tabTrend === 'gabungan' ? 'btn-dark text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0"><i class="bi bi-layers-fill me-1"></i>Gabungan</button>
                            <button @click="layanan.tabTrend = 'rj'" :class="layanan.tabTrend === 'rj' ? 'btn-primary text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Rawat Jalan</button>
                            <button @click="layanan.tabTrend = 'pasca'" :class="layanan.tabTrend === 'pasca' ? 'btn-warning text-dark shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Pasca Rehab</button>
                            <button @click="layanan.tabTrend = 'skhpn'" :class="layanan.tabTrend === 'skhpn' ? 'btn-success text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">SKHPN</button>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4 pt-0">
                        <div style="overflow-x: auto;" class="custom-scrollbar pe-2"><div x-ref="chartLayanan" style="min-width: 800px; min-height: 400px;"></div></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- NEW BLOCK: TREN KUNJUNGAN VS PASIEN BARU --}}
        {{-- ========================================================= --}}
        <div class="bg-white p-4 rounded-4 shadow-sm mb-4 border border-info border-opacity-25">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                <div>
                    <h5 class="m-0 fw-bold text-dark"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Tren Total Kunjungan vs Pasien Baru</h5>
                </div>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <div class="d-flex align-items-center bg-light rounded-3 px-3 py-1">
                        <i class="bi bi-calendar-range text-muted me-2"></i>
                        <select x-model="trend.mode" class="form-select border-0 bg-transparent text-dark fw-bold shadow-none cursor-pointer pe-4" style="min-width: 150px; outline: none;">
                            <option value="per_bulan">Tren Per Bulan</option>
                            <option value="per_triwulan">Tren Per Triwulan</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm bg-white rounded-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div></div> {{-- Spacer kosong --}}
                        <div class="d-flex bg-light p-1 rounded-pill ms-auto flex-wrap">
                            <button @click="trend.view = 'gabungan'" :class="trend.view === 'gabungan' ? 'btn-dark text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0"><i class="bi bi-layers-fill me-1"></i>Gabungan</button>
                            <button @click="trend.view = 'kunjungan'" :class="trend.view === 'kunjungan' ? 'btn-primary text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Total Kunjungan</button>
                            <button @click="trend.view = 'baru'" :class="trend.view === 'baru' ? 'btn-warning text-dark shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Pasien Baru</button>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4 pt-0">
                        
                        {{-- 1. SINGLE SATKER VIEW --}}
                        <div x-show="!isMultiSatker" style="overflow-x: auto;" class="custom-scrollbar pe-2">
                            <div x-ref="chartTrend" style="min-width: 800px; min-height: 350px;"></div>
                        </div>

                        {{-- 2. MULTI SATKER VIEW (GRID PANEL) --}}
                        <div x-show="isMultiSatker" class="row g-3 mt-2">
                            <template x-for="(pData, pIdx) in trend.data?.panel || []" :key="'tmb'+pIdx">
                                <div class="col-md-6 col-xl-4">
                                    <div class="card border border-light shadow-sm h-100 rounded-3">
                                        <div class="card-header bg-white py-3 border-bottom">
                                            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-building me-2 text-primary"></i><span x-text="pData.satker"></span></h6>
                                        </div>
                                        <div class="card-body p-2 overflow-auto custom-scrollbar">
                                            <div :id="'chart-trend-panel-' + pIdx" style="min-height: 250px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- BLOK B: DEMOGRAFI KLIEN (SNAPSHOT / POTRET DATA) --}}
        {{-- ========================================================= --}}
        <div class="bg-white p-4 rounded-4 shadow-sm mb-4 border">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                <div><h5 class="m-0 fw-bold text-dark"><i class="bi bi-person-lines-fill me-2 text-info"></i>Pusat Analisis Demografi Klien</h5></div>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    
                    {{-- TOGGLE LAYANAN VS PASIEN UNIK --}}
                    <div class="d-flex bg-light p-1 rounded-pill">
                        <button @click="demografi.modeHitung = 'layanan'" :class="demografi.modeHitung === 'layanan' ? 'btn-primary text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-3 border-0"><i class="bi bi-file-text me-1"></i>Total Kunjungan</button>
                        <button @click="demografi.modeHitung = 'unik'" :class="demografi.modeHitung === 'unik' ? 'btn-primary text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-3 border-0"><i class="bi bi-person-check me-1"></i>Pasien Unik</button>
                    </div>

                    {{-- FILTER WAKTU (Tanpa Tren Per Bulan/Triwulan) --}}
                    <div class="d-flex align-items-center bg-light rounded-3 px-3 py-1">
                        <i class="bi bi-filter text-muted me-2"></i>
                        <select x-model="demografi.time" class="form-select border-0 bg-transparent text-dark fw-bold shadow-none cursor-pointer pe-4" style="min-width: 150px; outline: none;">
                            <option value="all">Total Akumulasi</option>
                            <optgroup label="Triwulan Spesifik">
                                <option value="Q1">Triwulan I</option><option value="Q2">Triwulan II</option><option value="Q3">Triwulan III</option><option value="Q4">Triwulan IV</option>
                            </optgroup>
                            <optgroup label="Bulan Spesifik">
                                <option value="1">Januari</option><option value="2">Februari</option><option value="3">Maret</option><option value="4">April</option>
                                <option value="5">Mei</option><option value="6">Juni</option><option value="7">Juli</option><option value="8">Agustus</option>
                                <option value="9">September</option><option value="10">Oktober</option><option value="11">November</option><option value="12">Desember</option>
                            </optgroup>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm bg-white rounded-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div></div>
                        <div class="d-flex bg-light p-1 rounded-pill ms-auto flex-wrap">
                            <button @click="demografi.tabComp = 'sumber'" :class="demografi.tabComp === 'sumber' ? 'btn-primary text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Sumber Pasien</button>
                            <button @click="demografi.tabComp = 'gender'" :class="demografi.tabComp === 'gender' ? 'btn-success text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Gender</button>
                            <button @click="demografi.tabComp = 'usia'" :class="demografi.tabComp === 'usia' ? 'btn-danger text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Kelompok Usia</button>
                            <button @click="demografi.tabComp = 'pendidikan'" :class="demografi.tabComp === 'pendidikan' ? 'btn-dark text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Pendidikan</button>
                            <button @click="demografi.tabComp = 'pekerjaan'" :class="demografi.tabComp === 'pekerjaan' ? 'btn-warning text-dark shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Pekerjaan</button>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4 pt-4">

                        {{-- APEXCHART GLOBAL (Jika Single Satker) --}}
                        <div x-show="showGlobalChart()" style="overflow-x: auto;" class="custom-scrollbar pe-2">
                            <div x-ref="chartDemografi" style="min-width: 800px; min-height: 450px;"></div>
                        </div>

                        {{-- HORIZONTAL BAR MINI UNTUK PANEL GRID (Jika Multi Satker) --}}
                        <div x-show="showMiniBarPanel()" class="row g-3">
                            <template x-for="(pData, pIdx) in getPanelData()" :key="'mb'+pIdx">
                                <div class="col-md-6 col-xl-4">
                                    <div class="card border border-light shadow-sm h-100 rounded-3">
                                        <div class="card-header bg-white py-3 border-bottom"><h6 class="mb-0 fw-bold text-dark"><i class="bi bi-building me-2 text-info"></i><span x-text="pData.satker"></span></h6></div>
                                        <div class="card-body p-2 overflow-auto custom-scrollbar" style="min-height: 250px; max-height: 400px;">
                                            <div :id="'chart-demo-panel-' + pIdx"></div>
                                            <div x-show="pData.items && pData.items.length === 0" class="text-center text-muted small py-4"><i class="bi bi-inbox d-block fs-3 mb-2 text-light"></i>Nihil Data</div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- BLOK C: RANKING JENIS NARKOTIKA --}}
        {{-- ========================================================= --}}
        <div class="bg-white p-4 rounded-4 shadow-sm mb-3 border">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                <div><h5 class="m-0 fw-bold text-dark"><i class="bi bi-bar-chart-steps me-2 text-warning"></i>Pemetaan Top Narkotika Pasien Rehab</h5></div>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <div class="d-flex align-items-center bg-light rounded-3 px-3 py-1">
                        <i class="bi bi-sort-down text-muted me-2"></i>
                        <select x-model="rank.limit" class="form-select border-0 bg-transparent text-dark fw-bold shadow-none cursor-pointer p-0 pe-4" style="min-width: 120px;">
                            <option value="all">Semua Jenis</option>
                            <option value="10">Top 10 Saja</option>
                            <option value="5">Top 5 Saja</option>
                        </select>
                    </div>
                    
                    <div class="d-flex align-items-center bg-light rounded-3 px-3 py-1">
                        <i class="bi bi-filter text-muted me-2"></i>
                        <select x-model="rank.time" class="form-select form-select-sm border-0 bg-transparent text-dark fw-bold shadow-none cursor-pointer pe-4" style="min-width: 150px; outline: none;">
                            <option value="all">Total Akumulasi</option>
                            <optgroup label="Triwulan Spesifik">
                                <option value="Q1">Triwulan I</option><option value="Q2">Triwulan II</option><option value="Q3">Triwulan III</option><option value="Q4">Triwulan IV</option>
                            </optgroup>
                            <optgroup label="Bulan Spesifik">
                                <option value="1">Januari</option><option value="2">Februari</option><option value="3">Maret</option><option value="4">April</option>
                                <option value="5">Mei</option><option value="6">Juni</option><option value="7">Juli</option><option value="8">Agustus</option>
                                <option value="9">September</option><option value="10">Oktober</option><option value="11">November</option><option value="12">Desember</option>
                            </optgroup>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm bg-white rounded-4 mb-5">
            <div class="card-body px-4 pb-4 pt-4">
                <div style="max-height: 500px; overflow-y: auto; overflow-x: hidden;" class="pe-2 custom-scrollbar">
                    <div x-ref="chartRanking" style="min-height: 300px;"></div>
                </div>
            </div>
        </div>

    </div>
</main>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('dashboardRehab', () => ({
            globalSatkerId: '', 
            globalYear: '{{ max($years) }}', 
            
            cards: { 
                rj: { real: 0, target: 0, pct: 0 }, 
                pasca: { real: 0, target: 0, pct: 0 }, 
                skhpn: { real: 0, target: 0, pct: 0 }, 
                klien: { total: 0, unik: 0 } 
            },
            isMultiSatker: false,
            
            chartInst: { trend: null, layanan: null, demografi: null, rank: null, panelDemo: [], panelTrend: [] },
            
            getBarColors() { return ['#0d6efd', '#ffc107', '#198754', '#0dcaf0', '#6f42c1', '#dc3545', '#20c997', '#fd7e14', '#6c757d', '#e83e8c', '#adb5bd', '#212529']; },
            getLayananColors() { return ['#0d6efd', '#ffc107', '#198754']; }, 

            getCategoryColor(catName, index = 0) {
                let colorMap = { 'Laki-laki': '#0d6efd', 'Perempuan': '#e83e8c', 'Voluntary': '#0dcaf0', 'Compulsory': '#dc3545' };
                if (colorMap[catName]) return colorMap[catName];
                
                let palette = this.getBarColors();
                return palette[index % palette.length];
            },

            resolveColors(series, tabName) {
                if (!this.isMultiSatker) {
                    return this.getBarColors();
                }
                return series.map((s, index) => this.getCategoryColor(s.name, index));
            },

            formatAngka(num) { return new Intl.NumberFormat('id-ID').format(num || 0); },
            
            getPctClass(pct) {
                if (pct >= 100) return 'bg-success';
                if (pct >= 50) return 'bg-warning text-dark';
                return 'bg-danger';
            },

            showEmptyState(refElement) {
                if(refElement) refElement.innerHTML = '<div class="d-flex flex-column align-items-center justify-content-center text-muted" style="min-height: 350px;"><i class="bi bi-inbox fs-1 mb-2"></i><h6 class="fw-bold">Tidak Ada Data</h6></div>';
            },

            trend: { mode: 'per_bulan', view: 'gabungan', data: null },
            layanan: { time: 'all', tabTrend: 'gabungan', adminTrendType: 'bar', data: null },
            demografi: { time: 'all', tabComp: 'sumber', modeHitung: 'layanan', data: null },
            rank: { time: 'all', limit: 'all', data: null },

            init() {
                this.fetchAll();
                this.$watch('globalSatkerId', () => this.fetchAll());
                this.$watch('globalYear', () => this.fetchAll());
                
                this.$watch('trend.mode', () => this.fetchTrend());
                this.$watch('trend.view', () => this.renderTrend());
                
                ['time', 'adminTrendType'].forEach(p => this.$watch('layanan.'+p, () => this.fetchLayanan()));
                this.$watch('layanan.tabTrend', () => this.renderLayanan()); 
                
                this.$watch('demografi.modeHitung', () => {
                    this.fetchDemografi();
                    this.fetchRank(); 
                });
                
                ['time'].forEach(p => this.$watch('demografi.'+p, () => this.fetchDemografi()));
                this.$watch('demografi.tabComp', () => this.renderDemografi());

                ['time', 'limit'].forEach(p => this.$watch('rank.'+p, () => this.fetchRank()));
            },

            showGlobalChart() {
                return !this.isMultiSatker;
            },
            showMiniBarPanel() {
                return this.isMultiSatker;
            },

            getPanelData() {
                try {
                    if (this.demografi.data && this.demografi.data.comp && this.demografi.data.comp[this.demografi.tabComp]) {
                        let panel = this.demografi.data.comp[this.demografi.tabComp].panel;
                        return Array.isArray(panel) ? panel : [];
                    }
                } catch (e) {} return [];
            },

            fetchAll() { 
                this.fetchGlobal(); 
                this.fetchTrend();
                this.fetchLayanan(); 
                this.fetchDemografi(); 
                this.fetchRank(); 
            },

            getTitle(timeVar, metricStr) {
                let t = this[timeVar].time;
                let tStr = '';
                if (t === 'all') tStr = `Tahun ${this.globalYear}`;
                else if (t === 'per_bulan') tStr = `Tren Bulanan ${this.globalYear}`;
                else if (t === 'per_triwulan') tStr = `Tren Triwulanan ${this.globalYear}`;
                else if (t.includes('Q')) tStr = `Triwulan ${t.replace('Q','')} Tahun ${this.globalYear}`;
                else {
                    let m = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                    tStr = `Bulan ${m[t-1]} Tahun ${this.globalYear}`;
                }
                
                let modeStr = '';
                if (timeVar === 'demografi' || timeVar === 'rank') {
                    modeStr = this.demografi.modeHitung === 'unik' ? ' (Berdasarkan Pasien Unik)' : ' (Berdasarkan Total Kunjungan)';
                }
                
                return `${metricStr} - ${tStr}${modeStr}`;
            },

            fetchGlobal() { 
                fetch(`{{ route('dashboard.rehab.api.global') }}?year=${this.globalYear}&satker_id=${this.globalSatkerId}`)
                .then(r=>r.json()).then(res => { 
                    this.cards = res; 
                    this.cards.rj.pct = this.cards.rj.target > 0 ? Math.round((this.cards.rj.real / this.cards.rj.target) * 100) : (this.cards.rj.real > 0 ? 100 : 0);
                    this.cards.pasca.pct = this.cards.pasca.target > 0 ? Math.round((this.cards.pasca.real / this.cards.pasca.target) * 100) : (this.cards.pasca.real > 0 ? 100 : 0);
                    this.cards.skhpn.pct = this.cards.skhpn.target > 0 ? Math.round((this.cards.skhpn.real / this.cards.skhpn.target) * 100) : (this.cards.skhpn.real > 0 ? 100 : 0);
                }); 
            },

            fetchTrend() {
                fetch(`{{ route('dashboard.rehab.api.trend') }}?year=${this.globalYear}&satker_id=${this.globalSatkerId}&trend_mode=${this.trend.mode}`)
                .then(r => {
                    if (!r.ok) throw new Error('Gagal mengambil data trend');
                    return r.json();
                })
                .then(res => { 
                    this.trend.data = res; 
                    this.renderTrend(); 
                })
                .catch(err => {
                    console.error("Error Fetch Trend:", err);
                    this.showEmptyState(this.$refs.chartTrend);
                });
            },
            fetchLayanan() { fetch(`{{ route('dashboard.rehab.api.layanan') }}?year=${this.globalYear}&satker_id=${this.globalSatkerId}&time=${this.layanan.time}`).then(r=>r.json()).then(res => { this.layanan.data = res; this.isMultiSatker = res.is_multi; this.renderLayanan(); }); },
            fetchDemografi() { fetch(`{{ route('dashboard.rehab.api.demografi') }}?year=${this.globalYear}&satker_id=${this.globalSatkerId}&time=${this.demografi.time}&mode_hitung=${this.demografi.modeHitung}`).then(r=>r.json()).then(res => { this.demografi.data = res; this.renderDemografi(); }); },
            fetchRank() { fetch(`{{ route('dashboard.rehab.api.ranking') }}?year=${this.globalYear}&satker_id=${this.globalSatkerId}&time=${this.rank.time}&mode_hitung=${this.demografi.modeHitung}&limit=${this.rank.limit}`).then(r=>r.json()).then(res => { this.rank.data = res; this.renderRank(); }); },

            // ==========================================
            // RENDER TREN (KUNJUNGAN VS PASIEN BARU)
            // ==========================================
            renderTrend() {
                if(!this.$refs.chartTrend || !this.trend.data) return;
                
                let d = this.trend.data;

                // Bersihkan Instance Lama
                if (this.chartInst.trend) { this.chartInst.trend.destroy(); this.chartInst.trend = null; }
                if (this.chartInst.panelTrend && this.chartInst.panelTrend.length > 0) { 
                    this.chartInst.panelTrend.forEach(c => c.destroy()); 
                    this.chartInst.panelTrend = []; 
                }

                // Fungsi Internal untuk Konfigurasi Seri berdasarkan Toggle
                const getSeriesConfig = (dataKunjungan, dataBaru) => {
                    let activeSeries = []; let activeColors = [];
                    let chartType = 'line'; let strokeWidth = 4;

                    if (this.trend.view === 'gabungan') {
                        activeSeries = [
                            { name: 'Total Kunjungan', type: 'column', data: dataKunjungan },
                            { name: 'Pasien Baru', type: 'line', data: dataBaru }
                        ];
                        activeColors = ['#0d6efd', '#ffc107'];
                        chartType = 'line'; strokeWidth = [0, 4];
                    } else if (this.trend.view === 'kunjungan') {
                        activeSeries = [{ name: 'Total Kunjungan', type: 'column', data: dataKunjungan }];
                        activeColors = ['#0d6efd']; chartType = 'bar'; strokeWidth = 0;
                    } else if (this.trend.view === 'baru') {
                        activeSeries = [{ name: 'Pasien Baru', type: 'column', data: dataBaru }];
                        activeColors = ['#ffc107']; chartType = 'bar'; strokeWidth = 0;
                    }
                    return { activeSeries, activeColors, chartType, strokeWidth };
                };

                // ===== RENDER MULTI SATKER (GRID PANEL) =====
                if (this.isMultiSatker) {
                    this.$nextTick(() => {
                        if (!d.panel) return;
                        
                        // Cari Nilai Maksimal Y agar semua satker pakai skala yang seimbang/sama tinggi
                        let maxY = 0;
                        d.panel.forEach(p => {
                            let maxK = Math.max(...(p.kunjungan || [0]));
                            let maxB = Math.max(...(p.baru || [0]));
                            if (maxK > maxY) maxY = maxK;
                            if (maxB > maxY) maxY = maxB;
                        });
                        let yMaxConfig = maxY > 0 ? Math.ceil(maxY * 1.1) : undefined;

                        this.chartInst.panelTrend = []; 
                        
                        d.panel.forEach((pData, pIdx) => {
                            let el = document.getElementById('chart-trend-panel-' + pIdx);
                            if (el) {
                                if (el.__apex_inst) el.__apex_inst.destroy(); el.innerHTML = '';
                                
                                let isEmpty = pData.kunjungan.every(v => v === 0) && pData.baru.every(v => v === 0);
                                if (isEmpty) {
                                    el.innerHTML = '<div class="text-center text-muted small py-4"><i class="bi bi-inbox d-block fs-3 mb-2 text-light"></i>Nihil Data</div>';
                                    return;
                                }

                                let conf = getSeriesConfig(pData.kunjungan, pData.baru);
                                
                                let opts = {
                                    series: conf.activeSeries,
                                    // Tinggi chart dinaikkan sedikit ke 260 untuk memberi ruang bagi legend
                                    chart: { height: 260, type: conf.chartType, toolbar: { show: false }, fontFamily: 'inherit' },
                                    stroke: { width: conf.strokeWidth, curve: 'smooth' },
                                    xaxis: { categories: d.labels, labels: { style: { fontSize: '10px' } } },
                                    yaxis: { max: yMaxConfig, labels: { style: { fontSize: '10px' }, formatter: val => new Intl.NumberFormat('id-ID').format(val||0) } },
                                    colors: conf.activeColors,
                                    dataLabels: { enabled: false }, // Disembunyikan untuk Grid agar tidak sempit
                                    // LEGEND DIAKTIFKAN
                                    legend: { show: true, position: 'top', fontSize: '10px', markers: { width: 10, height: 10 }, itemMargin: { horizontal: 5, vertical: 0 } },
                                    tooltip: { shared: true, intersect: false }
                                };
                                
                                let chart = new ApexCharts(el, opts);
                                el.__apex_inst = chart; chart.render(); 
                                this.chartInst.panelTrend.push(chart);
                            }
                        });
                    });
                } 
                // ===== RENDER SINGLE SATKER (CHART BESAR) =====
                else {
                    let isEmpty = d.kunjungan.every(v => v === 0) && d.baru.every(v => v === 0);
                    if (isEmpty) { this.showEmptyState(this.$refs.chartTrend); return; }

                    let conf = getSeriesConfig(d.kunjungan, d.baru);
                    
                    let titleText = 'Tren Kunjungan & Pasien Baru';
                    if (this.trend.view === 'kunjungan') titleText = 'Tren Total Kunjungan';
                    if (this.trend.view === 'baru') titleText = 'Tren Pasien Baru';

                    let opts = {
                        series: conf.activeSeries,
                        chart: { height: 350, type: conf.chartType, toolbar: { show: true }, fontFamily: 'inherit' },
                        stroke: { width: conf.strokeWidth, curve: 'smooth' },
                        title: { text: `${titleText} (${this.globalYear})`, align: 'center', margin: 20, style: { fontSize: '16px', fontWeight: '500', color: '#212529' } },
                        xaxis: { categories: d.labels },
                        yaxis: { labels: { formatter: function(val) { return new Intl.NumberFormat('id-ID').format(val || 0); } } },
                        colors: conf.activeColors,
                        dataLabels: { 
                            enabled: true, 
                            enabledOnSeries: this.trend.view === 'gabungan' ? [0, 1] : [0],
                            formatter: function(val) { return val === 0 ? "" : new Intl.NumberFormat('id-ID').format(val); },
                            style: { colors: ['#000'], fontSize: '11px', fontWeight: 'bold' }
                        },
                        // LEGEND DIAKTIFKAN
                        legend: { show: true, position: 'top', fontWeight: 'bold', offsetY: -5 },
                        tooltip: { shared: true, intersect: false, y: { formatter: function(val) { return new Intl.NumberFormat('id-ID').format(val||0) + ' Orang/Sesi'; } } }
                    };

                    this.chartInst.trend = new ApexCharts(this.$refs.chartTrend, opts);
                    this.chartInst.trend.render();
                }
            },

            // ==========================================
            // RENDER LAYANAN (MURNI BATANG REALISASI)
            // ==========================================
            renderLayanan() {
                if(!this.$refs.chartLayanan || !this.layanan.data) return;
                
                let ds = []; let colors = []; let tTitle = ''; let isStacked = false;
                let isDistributed = false;

                if (this.layanan.tabTrend === 'gabungan') {
                    ds = [
                        { name: 'Rawat Jalan', data: this.layanan.data.trend.rj[0]?.data || [] },
                        { name: 'Pasca Rehab', data: this.layanan.data.trend.pasca[0]?.data || [] },
                        { name: 'SKHPN', data: this.layanan.data.trend.skhpn[0]?.data || [] }
                    ];
                    colors = this.getLayananColors();
                    tTitle = 'Total Layanan Keseluruhan';
                    isStacked = true;
                } else {
                    ds = JSON.parse(JSON.stringify(this.layanan.data.trend[this.layanan.tabTrend]));
                    
                    if (this.isMultiSatker && !this.layanan.time.includes('per_')) {
                        colors = this.getBarColors();
                        isDistributed = true;
                    } else if (this.isMultiSatker && this.layanan.time.includes('per_')) {
                        colors = this.getBarColors();
                    } else {
                        colors = [this.layanan.tabTrend === 'pasca' ? '#ffc107' : (this.layanan.tabTrend === 'skhpn' ? '#198754' : '#0d6efd')];
                    }
                    
                    tTitle = 'Total Layanan ' + (this.layanan.tabTrend === 'rj' ? 'Rawat Jalan' : (this.layanan.tabTrend === 'pasca' ? 'Pasca Rehab' : 'SKHPN'));
                }

                if (!ds || ds.length === 0 || (ds[0] && ds[0].data && ds[0].data.length === 0)) {
                    this.showEmptyState(this.$refs.chartLayanan); return;
                }

                let opts = {
                    series: ds,
                    chart: { type: 'bar', height: 400, stacked: isStacked, toolbar: { show: true }, fontFamily: 'inherit' },
                    xaxis: { categories: this.layanan.data.trend_labels },
                    yaxis: { labels: { formatter: function(val) { return new Intl.NumberFormat('id-ID').format(val||0); } } },
                    title: { text: this.getTitle('layanan', tTitle), align: 'center', margin: 20, style: { fontSize: '18px', fontWeight: '500', color: '#212529' } },
                    grid: { show: true, borderColor: '#f1f3f5' },
                    plotOptions: { bar: { borderRadius: isStacked ? 0 : 3, columnWidth: this.isMultiSatker ? '85%' : '40%', distributed: isDistributed } },
                    colors: colors,
                    dataLabels: { 
                        enabled: true, 
                        formatter: function(val) { let v = val||0; return v === 0 ? "" : new Intl.NumberFormat('id-ID').format(v); }, 
                        style: { colors: ['#000'], fontSize: '12px', fontWeight: 'bold' }
                    },
                    tooltip: { 
                        shared: true, intersect: false, 
                        y: { formatter: function(val) { return new Intl.NumberFormat('id-ID').format(val||0) + ' Klien'; } } 
                    },
                    legend: { show: !isDistributed, position: 'top', fontWeight: 'bold', offsetY: -10 }
                };

                if(this.chartInst.layanan) this.chartInst.layanan.destroy();
                this.chartInst.layanan = new ApexCharts(this.$refs.chartLayanan, opts);
                this.chartInst.layanan.render();
            },

            // ==========================================
            // RENDER DEMOGRAFI (SELALU HORIZONTAL BAR)
            // ==========================================
            renderDemografi() {
                if(!this.$refs.chartDemografi || !this.demografi.data) return;
                const dComp = this.demografi.data.comp[this.demografi.tabComp];
                if (!dComp) return; 

                if (this.chartInst.demografi) { this.chartInst.demografi.destroy(); this.chartInst.demografi = null; }
                if (this.chartInst.panelDemo.length > 0) { this.chartInst.panelDemo.forEach(c => c.destroy()); this.chartInst.panelDemo = []; }

                if (!dComp.labels || dComp.labels.length === 0) {
                    this.showEmptyState(this.$refs.chartDemografi); return;
                }

                if (this.showMiniBarPanel()) {
                    this.$nextTick(() => {
                        let pDataArr = this.getPanelData();
                        let maxVal = 0;
                        pDataArr.forEach(pData => { if (pData.items && pData.items.length > 0) { let m = Math.max(...pData.items.map(i => i.count)); if(m > maxVal) maxVal = m; } });
                        let xMax = maxVal > 0 ? Math.ceil(maxVal * 1.1) : undefined;

                        pDataArr.forEach((pData, pIdx) => {
                            let el = document.getElementById('chart-demo-panel-' + pIdx);
                            if (el && pData.items && pData.items.length > 0) {
                                if (el.__apex_inst) el.__apex_inst.destroy(); el.innerHTML = ''; 
                                let categories = pData.items.map(i => i.name);
                                let values = pData.items.map(i => i.count);
                                let dHeight = Math.max(180, categories.length * 45); 
                                
                                let opts = {
                                    series: [{ name: 'Total', data: values }],
                                    chart: { type: 'bar', height: dHeight, toolbar: { show: false }, parentHeightOffset: 0, fontFamily: 'inherit' },
                                    plotOptions: { bar: { horizontal: true, distributed: true, borderRadius: 3, barHeight: '65%', dataLabels: { hideOverflowingLabels: false } } },
                                    colors: categories.map((cat, i) => this.getCategoryColor(cat, i)), 
                                    dataLabels: { enabled: true, formatter: function(val) { return val === 0 ? "" : new Intl.NumberFormat('id-ID').format(val); }, style: { colors: ['#000'], fontSize: '11px', fontWeight: 'bold' } },
                                    xaxis: { max: xMax, categories: categories, labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
                                    yaxis: { labels: { style: { fontWeight: '600', fontSize: '11px', colors: '#495057' }, maxWidth: 140 } },
                                    grid: { show: false, padding: { top: 0, right: 0, bottom: 0, left: 10 } },
                                    legend: { show: false },
                                    tooltip: { y: { formatter: v => v + ' Orang' } }
                                };
                                let chart = new ApexCharts(el, opts);
                                el.__apex_inst = chart; chart.render(); this.chartInst.panelDemo.push(chart);
                            }
                        });
                    });
                    return; 
                }

                const names = {'sumber':'Sumber Pasien', 'gender':'Gender', 'usia':'Kelompok Usia', 'pendidikan': 'Pendidikan', 'pekerjaan':'Pekerjaan'};
                const dynHeight = Math.max(450, dComp.labels.length * 35);

                let opts = {
                    series: dComp.series,
                    chart: { type: 'bar', height: dynHeight, stacked: false, toolbar: { show: false }, fontFamily: 'inherit' },
                    plotOptions: { 
                        bar: { horizontal: true, borderRadius: 2, columnWidth: '50%', barHeight: '70%', distributed: !this.isMultiSatker, dataLabels: { hideOverflowingLabels: false } }, 
                    },
                    xaxis: { 
                        categories: dComp.labels, tickAmount: 3, 
                        labels: { style: { fontWeight: 'bold' } } 
                    },
                    colors: this.resolveColors(dComp.series, this.demografi.tabComp),
                    title: { text: this.getTitle('demografi', 'Proporsi ' + names[this.demografi.tabComp]), align: 'center', margin: 20, style: { fontSize: '18px', fontWeight: '500', color: '#212529' } },
                    dataLabels: { enabled: true, formatter: function(val) { let v = val || 0; return v === 0 ? "" : new Intl.NumberFormat('id-ID').format(v); }, style: { colors: ['#000'], fontSize: '11px', fontWeight: 'bold' } },
                    stroke: { show: true, width: 1, colors: ['#fff'] },
                    tooltip: { shared: true, intersect: false, y: { formatter: function(val, opt) { let v = val || 0; return new Intl.NumberFormat('id-ID').format(v) + " Orang"; } } },
                    legend: { show: true, position: 'top', fontWeight: 'bold', offsetY: -10 }
                };

                this.chartInst.demografi = new ApexCharts(this.$refs.chartDemografi, opts);
                this.chartInst.demografi.render();
            },

            // ==========================================
            // RENDER RANKING NARKOTIKA
            // ==========================================
            renderRank() {
                if(!this.$refs.chartRanking || !this.rank.data) return;
                const d = this.rank.data;

                if (!d.labels || d.labels.length === 0) {
                    this.showEmptyState(this.$refs.chartRanking); return;
                }

                const dynHeight = Math.max(300, (d.labels.length * 45) + 100);

                let opts = { 
                    series: [{ name: 'Frekuensi', data: d.data }], 
                    chart: { type: 'bar', height: dynHeight, toolbar: { show: false }, fontFamily: 'inherit' }, 
                    plotOptions: { bar: { horizontal: true, distributed: true, borderRadius: 4, barHeight: '75%', dataLabels: { hideOverflowingLabels: false } } }, 
                    xaxis: { categories: d.labels, tickAmount: 3, labels: { formatter: function(val) { return new Intl.NumberFormat('id-ID').format(val); } } }, 
                    colors: this.getBarColors(),
                    dataLabels: { enabled: true, formatter: function(val) { let v = val || 0; return v === 0 ? "" : new Intl.NumberFormat('id-ID').format(v) + 'x'; }, style: { fontSize: '13px', fontWeight: 'bold', colors: ['#000'] } },
                    grid: { show: true, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } }, borderColor: '#f1f3f5' }, 
                    title: { text: this.getTitle('rank', 'Top Frekuensi Penggunaan Narkotika'), align: 'left', margin: 20, style: { fontSize: '16px', fontWeight: '500' } },
                    legend: { show: false },
                    tooltip: { y: { formatter: function(val) { return new Intl.NumberFormat('id-ID').format(val||0) + ' Kasus'; }} }
                };
                
                if (this.chartInst.rank) this.chartInst.rank.destroy(); 
                this.chartInst.rank = new ApexCharts(this.$refs.chartRanking, opts); 
                this.chartInst.rank.render();
            }
        }));
    });
</script>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 8px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
</style>
@endpush