@extends('admin')

@section('content')
<main class="admin-main bg-light" x-data="dashboardBerantas()" x-init="init()" style="min-height: 100vh;">
    <div class="container-fluid p-4">

        {{-- HEADER & IDENTITAS --}}
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3">
            <div>
                <h1 class="h3 mb-2 fw-bold text-dark">Dashboard Kinerja Pemberantasan</h1>
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

        {{-- FILTER GLOBAL (TAHUN KINERJA & SATUAN BERAT) --}}
        <div class="d-flex flex-wrap justify-content-end gap-3 mb-3">
            <div class="d-flex align-items-center bg-white p-2 rounded-3 shadow-sm border border-light gap-2">
                <span class="fw-bold text-muted small ms-2"><i class="bi bi-calendar-event me-2 text-primary"></i>Tahun Kinerja:</span>
                <select x-model="globalYear" class="form-select form-select-sm border-0 bg-light fw-bold text-dark w-auto shadow-none pe-4 cursor-pointer">
                    @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                </select>
            </div>
            
            <div class="d-flex align-items-center bg-white p-2 rounded-3 shadow-sm border border-light gap-2">
                <span class="fw-bold text-muted small ms-2"><i class="bi bi-speedometer2 me-2 text-danger"></i>Satuan Berat:</span>
                <select x-model="weightUnit" class="form-select form-select-sm border-0 bg-light fw-bold text-dark w-auto shadow-none pe-4 cursor-pointer">
                    <option value="g">Gram (g)</option>
                    <option value="kg">Kilogram (Kg)</option>
                    <option value="ton">Ton</option>
                </select>
            </div>
        </div>

        {{-- 3 KARTU UTAMA --}}
        <div class="row g-3 mb-5">
            {{-- LKN --}}
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 bg-white border-start border-4 border-primary rounded-3 overflow-hidden">
                    <div class="card-body p-4 d-flex flex-column justify-content-center">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary bg-opacity-10 text-primary p-2 rounded me-3"><i class="bi bi-briefcase-fill fs-3"></i></div>
                            <h5 class="fw-bold text-dark mb-0">Ungkap Kasus (LKN)</h5>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <span class="text-muted small fw-bold">Total LKN</span>
                                <h3 class="fw-bold text-primary mb-0" x-text="formatAngka(cards.lkn.kasus)"></h3>
                            </div>
                            <div class="col-6">
                                <span class="text-muted small fw-bold">Tersangka</span>
                                <h3 class="fw-bold text-primary mb-0" x-text="formatAngka(cards.lkn.tersangka)"></h3>
                            </div>
                        </div>
                        <div class="bg-light p-2 rounded small fw-bold text-secondary d-flex justify-content-between">
                            <span>Sitaan: <span class="text-dark" x-text="formatWeight(cards.lkn.gram)"></span></span>
                            <span class="text-dark" x-text="formatAngka(cards.lkn.item) + ' Item'"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAT --}}
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 bg-white border-start border-4 border-info rounded-3 overflow-hidden">
                    <div class="card-body p-4 d-flex flex-column justify-content-center">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-info bg-opacity-10 text-info p-2 rounded me-3"><i class="bi bi-file-medical-fill fs-3"></i></div>
                            <h5 class="fw-bold text-dark mb-0">Asesmen (TAT)</h5>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <span class="text-muted small fw-bold">Total Kasus</span>
                                <h3 class="fw-bold text-info mb-0" x-text="formatAngka(cards.tat.kasus)"></h3>
                            </div>
                            <div class="col-6">
                                <span class="text-muted small fw-bold">Tersangka</span>
                                <h3 class="fw-bold text-info mb-0" x-text="formatAngka(cards.tat.tersangka)"></h3>
                            </div>
                        </div>
                        <div class="bg-light p-2 rounded small fw-bold text-secondary d-flex justify-content-between">
                            <span>BB Terkait: <span class="text-dark" x-text="formatWeight(cards.tat.gram)"></span></span>
                            <span class="text-dark" x-text="formatAngka(cards.tat.item) + ' Item'"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- REG BB --}}
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 bg-white border-start border-4 border-danger rounded-3 overflow-hidden">
                    <div class="card-body p-4 d-flex flex-column justify-content-center">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-danger bg-opacity-10 text-danger p-2 rounded me-3"><i class="bi bi-box-seam-fill fs-3"></i></div>
                            <h5 class="fw-bold text-dark mb-0">Register Barang Bukti</h5>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted small fw-bold">Total Berat Keseluruhan</span>
                            <h3 class="fw-bold text-danger mb-0 d-flex align-items-center gap-2">
                                <span x-text="formatWeight(cards.reg.total_gram)"></span>
                                <span class="badge bg-danger-subtle text-danger border border-danger fs-6" x-text="formatAngka(cards.reg.total_item) + ' Item'"></span>
                            </h3>
                        </div>
                        <div class="row g-2 bg-light p-2 rounded small">
                            <div class="col-6 border-end">
                                <span class="text-muted fw-bold d-block">Hasil Tangkap</span>
                                <span class="text-dark fw-bold" x-text="formatWeight(cards.reg.tangkap_gram)"></span> 
                                <span class="text-muted" x-text="'('+cards.reg.tangkap_item+'x)'"></span>
                            </div>
                            <div class="col-6 ps-2">
                                <span class="text-muted fw-bold d-block">Temuan (Tak Bertuan)</span>
                                <span class="text-dark fw-bold" x-text="formatWeight(cards.reg.temuan_gram)"></span> 
                                <span class="text-muted" x-text="'('+cards.reg.temuan_item+'x)'"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- BLOK A: UNGKAP KASUS (LKN) --}}
        {{-- ========================================================= --}}
        <div class="bg-white p-4 rounded-4 shadow-sm mb-4 border">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                <div><h5 class="m-0 fw-bold text-dark"><i class="bi bi-briefcase-fill me-2 text-primary"></i>Pusat Analisis Ungkap Kasus (LKN)</h5></div>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <div class="d-flex align-items-center bg-light rounded-3 px-3 py-1">
                        <i class="bi bi-funnel text-muted me-2"></i>
                        <select x-model="lkn.narkotika" class="form-select border-0 bg-transparent text-dark fw-bold shadow-none cursor-pointer pe-4" style="min-width: 150px; outline: none;">
                            <option value="">Semua Narkotika</option>
                            @foreach($narkotikas as $n) <option value="{{ $n->id }}">{{ $n->nama_narkotika }}</option> @endforeach
                        </select>
                    </div>
                    <div class="d-flex align-items-center bg-light rounded-3 px-3 py-1">
                        <i class="bi bi-filter text-muted me-2"></i>
                        <select x-model="lkn.month" class="form-select form-select-sm border-0 bg-transparent text-dark fw-bold shadow-none cursor-pointer pe-4">
                            <option value="all">Total Akumulasi</option>
                            <option value="per_bulan">Tren Per Bulan</option>
                            <option value="1">Januari</option><option value="2">Februari</option><option value="3">Maret</option><option value="4">April</option>
                            <option value="5">Mei</option><option value="6">Juni</option><option value="7">Juli</option><option value="8">Agustus</option>
                            <option value="9">September</option><option value="10">Oktober</option><option value="11">November</option><option value="12">Desember</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            {{-- Tren LKN --}}
            <div class="col-12">
                <div class="card border-0 shadow-sm bg-white rounded-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <template x-if="isMultiSatker && lkn.month === 'per_bulan'">
                            <div class="d-flex align-items-center bg-light rounded-pill px-3 py-1">
                                <i class="bi bi-eye text-muted me-2"></i>
                                <select x-model="lkn.adminTrendType" class="form-select form-select-sm border-0 bg-transparent text-dark fw-bold shadow-none cursor-pointer pe-4">
                                    <option value="bar">Grafik Batang</option>
                                    <option value="heatmap">Grafik Matriks (Heatmap)</option>
                                </select>
                            </div>
                        </template>
                        <div class="d-flex bg-light p-1 rounded-pill ms-auto">
                            <button @click="lkn.tabTrend = 'kasus'" :class="lkn.tabTrend === 'kasus' ? 'btn-primary text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Total LKN</button>
                            <button @click="lkn.tabTrend = 'tersangka'" :class="lkn.tabTrend === 'tersangka' ? 'btn-primary text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Tersangka</button>
                            <button @click="lkn.tabTrend = 'berat'" :class="lkn.tabTrend === 'berat' ? 'btn-danger text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0" x-text="'Berat ('+weightLabel+')'"></button>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4 pt-0">
                        <div style="overflow-x: auto;" class="custom-scrollbar pe-2"><div x-ref="chartLknTrend" style="min-width: 800px; min-height: 400px;"></div></div>
                    </div>
                </div>
            </div>
            
            {{-- Proporsi LKN --}}
            <div class="col-12">
                <div class="card border-0 shadow-sm bg-white rounded-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <template x-if="isMultiSatker && (lkn.tabComp === 'pekerjaan' || lkn.tabComp === 'pendidikan')">
                            <div class="d-flex align-items-center bg-light rounded-pill px-3 py-1">
                                <i class="bi bi-layout-text-window-reverse text-muted me-2"></i>
                                <select x-model="lkn.compView" class="form-select form-select-sm border-0 bg-transparent text-dark fw-bold shadow-none cursor-pointer pe-4">
                                    <option value="panel">Mode Panel Grid (Horizontal Bar)</option>
                                    <option value="heatmap">Mode Matriks (Heatmap)</option>
                                </select>
                            </div>
                        </template>
                        <div class="d-flex bg-light p-1 rounded-pill ms-auto">
                            <button @click="lkn.tabComp = 'gender'" :class="lkn.tabComp === 'gender' ? 'btn-success text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Gender</button>
                            <button @click="lkn.tabComp = 'pekerjaan'" :class="lkn.tabComp === 'pekerjaan' ? 'btn-warning text-dark shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Pekerjaan</button>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4 pt-4">
                        {{-- APEXCHART GLOBAL --}}
                        <div x-show="!isMultiSatker || lkn.compView === 'heatmap' || lkn.tabComp === 'gender'" style="overflow-x: auto;" class="custom-scrollbar pe-2">
                            <div x-ref="chartLknComp" style="min-width: 800px; min-height: 400px;"></div>
                        </div>

                        {{-- PANEL GRID + APEXCHART MINI HORIZONTAL --}}
                        <div x-show="isMultiSatker && lkn.compView === 'panel' && (lkn.tabComp === 'pekerjaan' || lkn.tabComp === 'pendidikan')" class="row g-3">
                            <template x-for="(pData, pIdx) in getPanelData('lkn', lkn.tabComp)" :key="pIdx">
                                <div class="col-md-6 col-xl-4">
                                    <div class="card border border-light shadow-sm h-100 rounded-3">
                                        <div class="card-header bg-white py-3 border-bottom"><h6 class="mb-0 fw-bold text-dark"><i class="bi bi-building me-2 text-primary"></i><span x-text="pData.satker"></span></h6></div>
                                        <div class="card-body p-2 overflow-auto custom-scrollbar" style="max-height: 350px;">
                                            <div :id="'chart-lkn-panel-' + pIdx"></div>
                                            <div x-show="pData.items.length === 0" class="text-center text-muted small py-4"><i class="bi bi-inbox d-block fs-3 mb-2 text-light"></i>Nihil Data</div>
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
        {{-- BLOK B: TIM ASESMEN TERPADU (TAT) --}}
        {{-- ========================================================= --}}
        <div class="bg-white p-4 rounded-4 shadow-sm mb-4 border">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                <div><h5 class="m-0 fw-bold text-dark"><i class="bi bi-file-medical-fill me-2 text-info"></i>Pusat Analisis Tim Asesmen Terpadu (TAT)</h5></div>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <div class="d-flex align-items-center bg-light rounded-3 px-3 py-1">
                        <i class="bi bi-funnel text-muted me-2"></i>
                        <select x-model="tat.narkotika" class="form-select border-0 bg-transparent text-dark fw-bold shadow-none cursor-pointer pe-4" style="min-width: 150px; outline: none;">
                            <option value="">Semua Narkotika</option>
                            @foreach($narkotikas as $n) <option value="{{ $n->id }}">{{ $n->nama_narkotika }}</option> @endforeach
                        </select>
                    </div>
                    <div class="d-flex align-items-center bg-light rounded-3 px-3 py-1">
                        <i class="bi bi-filter text-muted me-2"></i>
                        <select x-model="tat.month" class="form-select form-select-sm border-0 bg-transparent text-dark fw-bold shadow-none cursor-pointer pe-4">
                            <option value="all">Total Akumulasi</option>
                            <option value="per_bulan">Tren Per Bulan</option>
                            <option value="1">Januari</option><option value="2">Februari</option><option value="3">Maret</option><option value="4">April</option>
                            <option value="5">Mei</option><option value="6">Juni</option><option value="7">Juli</option><option value="8">Agustus</option>
                            <option value="9">September</option><option value="10">Oktober</option><option value="11">November</option><option value="12">Desember</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            {{-- Tren TAT --}}
            <div class="col-12">
                <div class="card border-0 shadow-sm bg-white rounded-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <template x-if="isMultiSatker && tat.month === 'per_bulan'">
                            <div class="d-flex align-items-center bg-light rounded-pill px-3 py-1">
                                <i class="bi bi-eye text-muted me-2"></i>
                                <select x-model="tat.adminTrendType" class="form-select form-select-sm border-0 bg-transparent text-dark fw-bold shadow-none cursor-pointer pe-4">
                                    <option value="bar">Grafik Batang</option>
                                    <option value="heatmap">Grafik Matriks (Heatmap)</option>
                                </select>
                            </div>
                        </template>
                        <div class="d-flex bg-light p-1 rounded-pill ms-auto">
                            <button @click="tat.tabTrend = 'kasus'" :class="tat.tabTrend === 'kasus' ? 'btn-info text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Kasus TAT</button>
                            <button @click="tat.tabTrend = 'tersangka'" :class="tat.tabTrend === 'tersangka' ? 'btn-info text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Tersangka TAT</button>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4 pt-0"><div style="overflow-x: auto;" class="custom-scrollbar pe-2"><div x-ref="chartTatTrend" style="min-width: 800px; min-height: 400px;"></div></div></div>
                </div>
            </div>
            
            {{-- Proporsi TAT --}}
            <div class="col-12">
                <div class="card border-0 shadow-sm bg-white rounded-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <template x-if="isMultiSatker && (tat.tabComp === 'pekerjaan' || tat.tabComp === 'pendidikan')">
                            <div class="d-flex align-items-center bg-light rounded-pill px-3 py-1">
                                <i class="bi bi-layout-text-window-reverse text-muted me-2"></i>
                                <select x-model="tat.compView" class="form-select form-select-sm border-0 bg-transparent text-dark fw-bold shadow-none cursor-pointer pe-4">
                                    <option value="panel">Mode Panel Grid (Horizontal Bar)</option>
                                    <option value="heatmap">Mode Matriks (Heatmap)</option>
                                </select>
                            </div>
                        </template>
                        <div class="d-flex bg-light p-1 rounded-pill ms-auto flex-wrap">
                            <button @click="tat.tabComp = 'rekom'" :class="tat.tabComp === 'rekom' ? 'btn-primary text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Rekomendasi</button>
                            <button @click="tat.tabComp = 'gender'" :class="tat.tabComp === 'gender' ? 'btn-success text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Gender</button>
                            <button @click="tat.tabComp = 'pendidikan'" :class="tat.tabComp === 'pendidikan' ? 'btn-dark text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Pendidikan</button>
                            <button @click="tat.tabComp = 'pekerjaan'" :class="tat.tabComp === 'pekerjaan' ? 'btn-warning text-dark shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Pekerjaan</button>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4 pt-4">
                        {{-- APEXCHART GLOBAL --}}
                        <div x-show="!isMultiSatker || tat.compView === 'heatmap' || (tat.tabComp !== 'pekerjaan' && tat.tabComp !== 'pendidikan')" style="overflow-x: auto;" class="custom-scrollbar pe-2">
                            <div x-ref="chartTatComp" style="min-width: 800px; min-height: 400px;"></div>
                        </div>

                        {{-- HTML PANEL GRID --}}
                        <div x-show="isMultiSatker && tat.compView === 'panel' && (tat.tabComp === 'pekerjaan' || tat.tabComp === 'pendidikan')" class="row g-3">
                            <template x-for="(pData, pIdx) in getPanelData('tat', tat.tabComp)" :key="pIdx">
                                <div class="col-md-6 col-xl-4">
                                    <div class="card border border-light shadow-sm h-100 rounded-3">
                                        <div class="card-header bg-white py-3 border-bottom"><h6 class="mb-0 fw-bold text-dark"><i class="bi bi-building me-2 text-info"></i><span x-text="pData.satker"></span></h6></div>
                                        <div class="card-body p-2 overflow-auto custom-scrollbar" style="max-height: 350px;">
                                            <div :id="'chart-tat-panel-' + pIdx"></div>
                                            <div x-show="pData.items.length === 0" class="text-center text-muted small py-4"><i class="bi bi-inbox d-block fs-3 mb-2 text-light"></i>Nihil Data</div>
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
        {{-- BLOK C: REGISTER BARANG BUKTI --}}
        {{-- ========================================================= --}}
        <div class="bg-white p-4 rounded-4 shadow-sm mb-4 border">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                <div><h5 class="m-0 fw-bold text-dark"><i class="bi bi-box-seam-fill me-2 text-danger"></i>Pusat Analisis Register Barang Bukti</h5></div>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <div class="d-flex align-items-center bg-light rounded-3 px-3 py-1">
                        <i class="bi bi-funnel text-muted me-2"></i>
                        <select x-model="bb.narkotika" class="form-select border-0 bg-transparent text-dark fw-bold shadow-none cursor-pointer pe-4" style="min-width: 150px; outline: none;">
                            <option value="">Semua Narkotika</option>
                            @foreach($narkotikas as $n) <option value="{{ $n->id }}">{{ $n->nama_narkotika }}</option> @endforeach
                        </select>
                    </div>
                    <div class="d-flex align-items-center bg-light rounded-3 px-3 py-1">
                        <i class="bi bi-filter text-muted me-2"></i>
                        <select x-model="bb.month" class="form-select form-select-sm border-0 bg-transparent text-dark fw-bold shadow-none cursor-pointer pe-4">
                            <option value="all">Total Akumulasi</option>
                            <option value="per_bulan">Tren Per Bulan</option>
                            <option value="1">Januari</option><option value="2">Februari</option><option value="3">Maret</option><option value="4">April</option>
                            <option value="5">Mei</option><option value="6">Juni</option><option value="7">Juli</option><option value="8">Agustus</option>
                            <option value="9">September</option><option value="10">Oktober</option><option value="11">November</option><option value="12">Desember</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm bg-white rounded-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <template x-if="isMultiSatker && bb.month === 'per_bulan'">
                            <div class="d-flex align-items-center bg-light rounded-pill px-3 py-1">
                                <i class="bi bi-eye text-muted me-2"></i>
                                <select x-model="bb.adminTrendType" class="form-select form-select-sm border-0 bg-transparent text-dark fw-bold shadow-none cursor-pointer pe-4">
                                    <option value="bar">Grafik Batang</option>
                                    <option value="heatmap">Grafik Matriks (Heatmap)</option>
                                </select>
                            </div>
                        </template>
                        <div class="d-flex bg-light p-1 rounded-pill ms-auto">
                            <button @click="bb.tabTrend = 'berat'" :class="bb.tabTrend === 'berat' ? 'btn-danger text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0" x-text="'Total Berat ('+weightLabel+')'"></button>
                            <button @click="bb.tabTrend = 'item'" :class="bb.tabTrend === 'item' ? 'btn-primary text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Total Item</button>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4 pt-0"><div style="overflow-x: auto;" class="custom-scrollbar pe-2"><div x-ref="chartBbTrend" style="min-width: 800px; min-height: 400px;"></div></div></div>
                </div>
            </div>
            
            <div class="col-12">
                <div class="card border-0 shadow-sm bg-white rounded-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-wrap justify-content-center align-items-center gap-2">
                        <div class="d-flex bg-light p-1 rounded-pill">
                            <button class="btn btn-sm btn-success text-white rounded-pill fw-bold px-5 shadow-sm border-0">Sumber Perolehan Barang Bukti</button>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4 pt-4"><div style="overflow-x: auto;" class="custom-scrollbar pe-2"><div x-ref="chartBbComp" style="min-width: 800px; min-height: 400px;"></div></div></div>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- BLOK D: RANKING JENIS NARKOTIKA --}}
        {{-- ========================================================= --}}
        <div class="bg-white p-4 rounded-4 shadow-sm mb-3 border">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                <div><h5 class="m-0 fw-bold text-dark"><i class="bi bi-bar-chart-steps me-2 text-warning"></i>Pemetaan Top Tren Narkotika Sultra</h5></div>
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
                        <select x-model="rank.month" class="form-select form-select-sm border-0 bg-transparent text-dark fw-bold shadow-none cursor-pointer pe-4">
                            <option value="all">Total Akumulasi</option>
                            <option value="1">Januari</option><option value="2">Februari</option><option value="3">Maret</option><option value="4">April</option>
                            <option value="5">Mei</option><option value="6">Juni</option><option value="7">Juli</option><option value="8">Agustus</option>
                            <option value="9">September</option><option value="10">Oktober</option><option value="11">November</option><option value="12">Desember</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm bg-white rounded-4 mb-5">
            <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex bg-light p-1 rounded-pill">
                    <button @click="rank.source = 'lkn'" :class="rank.source === 'lkn' ? 'btn-primary text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Data LKN</button>
                    <button @click="rank.source = 'tat'" :class="rank.source === 'tat' ? 'btn-info text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Data TAT</button>
                    <button @click="rank.source = 'bb'" :class="rank.source === 'bb' ? 'btn-danger text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Data Register BB</button>
                </div>
                <div class="d-flex bg-light p-1 rounded-pill">
                    <button @click="rank.metric = 'berat'" :class="rank.metric === 'berat' ? 'btn-dark text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0" x-text="'Berdasarkan Berat ('+weightLabel+')'"></button>
                    <button @click="rank.metric = 'freq'" :class="rank.metric === 'freq' ? 'btn-dark text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-4 border-0">Berdasarkan Frekuensi</button>
                </div>
            </div>
            <div class="card-body px-4 pb-4 pt-2">
                <div style="max-height: 500px; overflow-y: auto; overflow-x: hidden;" class="pe-2 custom-scrollbar">
                    <div x-ref="chartRanking" style="min-height: 200px;"></div>
                </div>
            </div>
        </div>

    </div>
</main>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('dashboardBerantas', () => ({
            globalSatkerId: '', 
            globalYear: '{{ max($years) }}', 
            weightUnit: 'g', 
            
            cards: { 
                lkn: { kasus: 0, tersangka: 0, gram: 0, item: 0 },
                tat: { kasus: 0, tersangka: 0, gram: 0, item: 0 },
                reg: { total_gram: 0, total_item: 0, tangkap_gram: 0, tangkap_item: 0, temuan_gram: 0, temuan_item: 0 }
            },
            
            isMultiSatker: false,
            chartInst: { lknT: null, lknC: null, tatT: null, tatC: null, bbT: null, bbC: null, rank: null },
            
            getBarColors() { return ['#0d6efd', '#fd7e14', '#198754', '#6f42c1', '#dc3545', '#0dcaf0', '#20c997', '#ffc107', '#6c757d', '#e83e8c']; },

            lkn: { month: 'all', narkotika: '', tabTrend: 'kasus', tabComp: 'gender', adminTrendType: 'bar', compView: 'panel', data: null },
            tat: { month: 'all', narkotika: '', tabTrend: 'kasus', tabComp: 'rekom', adminTrendType: 'bar', compView: 'panel', data: null },
            bb: { month: 'all', narkotika: '', tabTrend: 'berat', tabComp: 'sumber', adminTrendType: 'bar', compView: 'panel', data: null },
            rank: { month: 'all', limit: '10', source: 'lkn', metric: 'berat', data: null },

            init() {
                this.fetchAll();
                
                this.$watch('globalSatkerId', () => this.fetchAll());
                this.$watch('globalYear', () => this.fetchAll());
                
                this.$watch('weightUnit', () => { this.renderAllCharts(); });
                
                ['month', 'narkotika', 'adminTrendType', 'compView'].forEach(p => this.$watch('lkn.'+p, () => this.fetchLkn()));
                this.$watch('lkn.tabTrend', () => this.renderLknTrend()); 
                this.$watch('lkn.tabComp', () => this.renderLknComp());
                
                ['month', 'narkotika', 'adminTrendType', 'compView'].forEach(p => this.$watch('tat.'+p, () => this.fetchTat()));
                this.$watch('tat.tabTrend', () => this.renderTatTrend()); 
                this.$watch('tat.tabComp', () => this.renderTatComp());

                ['month', 'narkotika', 'adminTrendType', 'compView'].forEach(p => this.$watch('bb.'+p, () => this.fetchBb()));
                this.$watch('bb.tabTrend', () => this.renderBbTrend()); 
                this.$watch('bb.tabComp', () => this.renderBbComp());

                ['month', 'limit', 'source', 'metric'].forEach(p => this.$watch('rank.'+p, () => this.fetchRank()));
            },

            getPanelData(module, tab) {
                try {
                    if (this[module] && this[module].data && this[module].data.comp && this[module].data.comp[tab]) {
                        let panel = this[module].data.comp[tab].panel;
                        return Array.isArray(panel) ? panel : [];
                    }
                } catch (e) { console.error(e); }
                return [];
            },

            fetchAll() { 
                this.fetchGlobal(); 
                this.fetchLkn(); 
                this.fetchTat(); 
                this.fetchBb(); 
                this.fetchRank(); 
            },

            formatAngka(num) { return new Intl.NumberFormat('id-ID').format(num || 0); },
            get weightLabel() { return this.weightUnit === 'kg' ? 'Kg' : (this.weightUnit === 'ton' ? 'Ton' : 'Gram'); },
            getWeightVal(gram) { let g = parseFloat(gram) || 0; return this.weightUnit === 'kg' ? g/1000 : (this.weightUnit === 'ton' ? g/1000000 : g); },
            formatWeight(gram) { return new Intl.NumberFormat('id-ID', {maximumFractionDigits: 2}).format(this.getWeightVal(gram)) + ' ' + this.weightLabel; },

            getTitle(st, metric) {
                let time = st.month === 'all' ? `Total Akumulasi Tahun ${this.globalYear}` : (st.month === 'per_bulan' ? `Tren Bulanan Tahun ${this.globalYear}` : `Bulan ${st.month} Tahun ${this.globalYear}`);
                return `${metric} - ${time}`;
            },

            renderAllCharts() {
                this.renderLknTrend(); this.renderLknComp();
                this.renderTatTrend(); this.renderTatComp();
                this.renderBbTrend(); this.renderBbComp();
                this.renderRank();
            },

            fetchGlobal() { fetch(`{{ route('dashboard.berantas.api.global') }}?year=${this.globalYear}&satker_id=${this.globalSatkerId}`).then(r=>r.json()).then(res => this.cards = res); },
            fetchLkn() { fetch(`{{ route('dashboard.berantas.api.lkn') }}?year=${this.globalYear}&satker_id=${this.globalSatkerId}&month=${this.lkn.month}&narkotika_id=${this.lkn.narkotika}`).then(r=>r.json()).then(res => { this.lkn.data = res; this.isMultiSatker = res.is_multi; this.renderLknTrend(); this.renderLknComp(); }); },
            fetchTat() { fetch(`{{ route('dashboard.berantas.api.tat') }}?year=${this.globalYear}&satker_id=${this.globalSatkerId}&month=${this.tat.month}&narkotika_id=${this.tat.narkotika}`).then(r=>r.json()).then(res => { this.tat.data = res; this.renderTatTrend(); this.renderTatComp(); }); },
            fetchBb() { fetch(`{{ route('dashboard.berantas.api.bb') }}?year=${this.globalYear}&satker_id=${this.globalSatkerId}&month=${this.bb.month}&narkotika_id=${this.bb.narkotika}`).then(r=>r.json()).then(res => { this.bb.data = res; this.renderBbTrend(); this.renderBbComp(); }); },
            fetchRank() { fetch(`{{ route('dashboard.berantas.api.ranking') }}?year=${this.globalYear}&satker_id=${this.globalSatkerId}&month=${this.rank.month}&source=${this.rank.source}&metric=${this.rank.metric}&limit=${this.rank.limit}`).then(r=>r.json()).then(res => { this.rank.data = res; this.renderRank(); }); },

            // ==========================================
            // RENDER LKN
            // ==========================================
            renderLknTrend() {
                if(!this.$refs.chartLknTrend || !this.lkn.data) return;
                let dsRaw = this.lkn.tabTrend === 'kasus' ? this.lkn.data.trend.kasus : (this.lkn.tabTrend === 'tersangka' ? this.lkn.data.trend.tersangka : this.lkn.data.trend.berat);
                let ds = JSON.parse(JSON.stringify(dsRaw)); 
                if (this.lkn.tabTrend === 'berat') { ds.forEach(s => s.data = s.data.map(v => this.getWeightVal(v))); }

                const isHeat = this.isMultiSatker && this.lkn.adminTrendType === 'heatmap' && this.lkn.month === 'per_bulan';
                const tTitle = this.lkn.tabTrend === 'kasus' ? 'Jumlah Kasus LKN' : (this.lkn.tabTrend === 'tersangka' ? 'Jumlah Tersangka' : `Total Berat Sitaan`);
                const self = this; 
                
                let opts = {
                    series: ds,
                    chart: { type: isHeat ? 'heatmap' : 'bar', height: isHeat ? 450 : 400, toolbar: { show: true }, fontFamily: 'inherit' },
                    xaxis: { categories: this.lkn.data.trend_labels },
                    title: { text: this.getTitle(this.lkn, tTitle), align: 'center', margin: 20, style: { fontSize: '18px', fontWeight: '500', color: '#212529' } },
                    grid: { show: true, borderColor: '#f1f3f5' },
                    plotOptions: {
                        bar: { borderRadius: 3, columnWidth: this.isMultiSatker ? '85%' : '40%' },
                        heatmap: { shadeIntensity: 0.6, radius: 2, useFillColorAsStroke: false }
                    },
                    colors: isHeat ? ['#0d6efd'] : (this.isMultiSatker ? this.getBarColors() : (this.lkn.tabTrend === 'berat' ? ['#dc3545'] : ['#0d6efd'])),
                    dataLabels: {
                        enabled: isHeat ? true : !this.isMultiSatker,
                        formatter: function(val) { let v = val||0; return v > 0 ? new Intl.NumberFormat('id-ID', {maximumFractionDigits:1}).format(v) : ""; },
                        style: { colors: ['#212529'], fontSize: '12px' }
                    },
                    tooltip: { 
                        shared: true, intersect: false,
                        y: { formatter: function(val) { return new Intl.NumberFormat('id-ID', {maximumFractionDigits:2}).format(val||0) + (self.lkn.tabTrend === 'berat' ? ' '+self.weightLabel : ' Kasus/Orang'); } }
                    },
                    legend: { show: !isHeat, position: 'top', fontWeight: 'bold', offsetY: -10 }
                };

                if(this.chartInst.lknT) this.chartInst.lknT.destroy();
                this.chartInst.lknT = new ApexCharts(this.$refs.chartLknTrend, opts);
                this.chartInst.lknT.render();
            },
            
            renderLknComp() {
                if(!this.$refs.chartLknComp || !this.lkn.data) return;
                
                // Mencegah masalah penumpukan grafik (bug duplikasi) 
                if (this.chartInst.lknC) { this.chartInst.lknC.destroy(); this.chartInst.lknC = null; }

                // LOGIKA PANEL GRID HORIZONTAL MINI
                if (this.isMultiSatker && this.lkn.compView === 'panel' && (this.lkn.tabComp === 'pekerjaan' || this.lkn.tabComp === 'pendidikan')) {
                    this.$nextTick(() => {
                        let pDataArr = this.getPanelData('lkn', this.lkn.tabComp);
                        
                        pDataArr.forEach((pData, pIdx) => {
                            let el = document.getElementById('chart-lkn-panel-' + pIdx);
                            if (el && pData.items.length > 0) {
                                // 1. Hancurkan Instance lama di elemen DOM ini secara paksa (agar tidak ada duplikat)
                                if (el._apex) { el._apex.destroy(); }
                                el.innerHTML = ''; 

                                let categories = pData.items.map(i => i.name);
                                let values = pData.items.map(i => i.count);
                                let dHeight = Math.max(150, categories.length * 40);
                                
                                let opts = {
                                    series: [{ name: 'Total Kasus', data: values }],
                                    chart: { type: 'bar', height: dHeight, toolbar: { show: false }, parentHeightOffset: 0, fontFamily: 'inherit' },
                                    plotOptions: { bar: { horizontal: true, distributed: true, borderRadius: 3, barHeight: '70%' } },
                                    colors: this.getBarColors(),
                                    dataLabels: { enabled: true, textAnchor: 'start', style: { colors: ['#fff'], fontSize: '11px', fontWeight: 'bold' }, offsetX: 0 },
                                    xaxis: { categories: categories, labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
                                    yaxis: { labels: { style: { fontWeight: '600', fontSize: '11px', colors: '#495057' }, maxWidth: 140 } },
                                    grid: { show: false, padding: { top: 0, right: 0, bottom: 0, left: 10 } },
                                    legend: { show: false },
                                    tooltip: { y: { formatter: v => v + ' Kasus' } }
                                };
                                
                                let chart = new ApexCharts(el, opts);
                                chart.render();
                                el._apex = chart; // 2. Simpan wujud grafik di properti elemen HTML langsung (bukan state Alpine)
                            }
                        });
                    });
                    return; // Selesai jika mode panel
                }

                // LOGIKA CHART GLOBAL (Kategori Gender / Mode Matriks Heatmap)
                const dComp = this.lkn.data.comp[this.lkn.tabComp];
                if (!dComp) return; 
                
                const tTitle = this.lkn.tabComp === 'gender' ? 'Proporsi Gender Tersangka' : 'Proporsi Pekerjaan Tersangka';
                const isHeat = this.isMultiSatker && this.lkn.compView === 'heatmap' && this.lkn.tabComp !== 'gender';
                
                let opts = {
                    series: dComp.series,
                    chart: { type: isHeat ? 'heatmap' : 'bar', height: isHeat ? 600 : 450, stacked: !isHeat, toolbar: { show: false }, fontFamily: 'inherit' },
                    plotOptions: { bar: { borderRadius: 2, columnWidth: '50%' }, heatmap: { shadeIntensity: 0.6, radius: 2, useFillColorAsStroke: false } },
                    xaxis: { categories: dComp.labels, labels: { style: { fontWeight: 'bold' } } },
                    colors: isHeat ? ['#dc3545'] : this.getBarColors(),
                    title: { text: this.getTitle(this.lkn, tTitle), align: 'center', margin: 20, style: { fontSize: '18px', fontWeight: '500', color: '#212529' } },
                    dataLabels: {
                        enabled: true,
                        formatter: function(val, opt) {
                            if(isHeat) return val > 0 ? val : '';
                            let t = 0; opt.w.config.series.forEach(s => t += s.data[opt.dataPointIndex]);
                            return t === 0 ? "" : val + " (" + Math.round((val/t)*100) + "%)";
                        },
                        style: { colors: ['#212529'], fontSize: '12px' }
                    },
                    stroke: { show: true, width: 1, colors: ['#fff'] },
                    tooltip: { shared: !isHeat, intersect: isHeat },
                    legend: { show: !isHeat, position: 'top', fontWeight: 'bold', offsetY: -10 }
                };

                this.chartInst.lknC = new ApexCharts(this.$refs.chartLknComp, opts);
                this.chartInst.lknC.render();
            },

            // ==========================================
            // RENDER TAT
            // ==========================================
            renderTatTrend() {
                if(!this.$refs.chartTatTrend || !this.tat.data) return;
                const ds = this.tat.tabTrend === 'kasus' ? this.tat.data.trend.kasus : this.tat.data.trend.tersangka;
                const isHeat = this.isMultiSatker && this.tat.adminTrendType === 'heatmap' && this.tat.month === 'per_bulan';
                const tTitle = this.tat.tabTrend === 'kasus' ? 'Jumlah Kasus TAT' : 'Jumlah Tersangka TAT';
                
                let opts = {
                    series: ds,
                    chart: { type: isHeat ? 'heatmap' : 'bar', height: isHeat ? 450 : 400, toolbar: { show: true }, fontFamily: 'inherit' },
                    xaxis: { categories: this.tat.data.trend_labels },
                    title: { text: this.getTitle(this.tat, tTitle), align: 'center', margin: 20, style: { fontSize: '18px', fontWeight: '500', color: '#212529' } },
                    grid: { show: true, borderColor: '#f1f3f5' },
                    plotOptions: { bar: { borderRadius: 3, columnWidth: this.isMultiSatker ? '85%' : '40%' }, heatmap: { shadeIntensity: 0.6, radius: 2, useFillColorAsStroke: false } },
                    colors: isHeat ? ['#0dcaf0'] : (this.isMultiSatker ? this.getBarColors() : ['#0dcaf0']),
                    dataLabels: { enabled: isHeat ? true : !this.isMultiSatker, formatter: function(val) { let v = val||0; return v > 0 ? new Intl.NumberFormat('id-ID').format(v) : ""; }, style: { colors: ['#212529'], fontSize: '12px' } },
                    tooltip: { shared: true, intersect: false },
                    legend: { show: !isHeat, position: 'top', fontWeight: 'bold', offsetY: -10 }
                };

                if(this.chartInst.tatT) this.chartInst.tatT.destroy();
                this.chartInst.tatT = new ApexCharts(this.$refs.chartTatTrend, opts);
                this.chartInst.tatT.render();
            },
            
            renderTatComp() {
                if(!this.$refs.chartTatComp || !this.tat.data) return;

                if (this.chartInst.tatC) { this.chartInst.tatC.destroy(); this.chartInst.tatC = null; }

                // LOGIKA PANEL GRID HORIZONTAL MINI
                if (this.isMultiSatker && this.tat.compView === 'panel' && (this.tat.tabComp === 'pekerjaan' || this.tat.tabComp === 'pendidikan')) {
                    this.$nextTick(() => {
                        let pDataArr = this.getPanelData('tat', this.tat.tabComp);
                        
                        pDataArr.forEach((pData, pIdx) => {
                            let el = document.getElementById('chart-tat-panel-' + pIdx);
                            if (el && pData.items.length > 0) {
                                if (el._apex) { el._apex.destroy(); }
                                el.innerHTML = '';
                                
                                let categories = pData.items.map(i => i.name);
                                let values = pData.items.map(i => i.count);
                                let dHeight = Math.max(150, categories.length * 40);
                                
                                let opts = {
                                    series: [{ name: 'Total Kasus', data: values }],
                                    chart: { type: 'bar', height: dHeight, toolbar: { show: false }, parentHeightOffset: 0, fontFamily: 'inherit' },
                                    plotOptions: { bar: { horizontal: true, distributed: true, borderRadius: 3, barHeight: '70%' } },
                                    colors: this.getBarColors(),
                                    dataLabels: { enabled: true, textAnchor: 'start', style: { colors: ['#fff'], fontSize: '11px', fontWeight: 'bold' }, offsetX: 0 },
                                    xaxis: { categories: categories, labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
                                    yaxis: { labels: { style: { fontWeight: '600', fontSize: '11px', colors: '#495057' }, maxWidth: 140 } },
                                    grid: { show: false, padding: { top: 0, right: 0, bottom: 0, left: 10 } },
                                    legend: { show: false },
                                    tooltip: { y: { formatter: v => v + ' Kasus' } }
                                };
                                
                                let chart = new ApexCharts(el, opts);
                                chart.render();
                                el._apex = chart;
                            }
                        });
                    });
                    return; 
                }

                // LOGIKA CHART GLOBAL
                const dComp = this.tat.data.comp[this.tat.tabComp];
                if (!dComp) return;

                const names = {'rekom':'Rekomendasi', 'gender':'Gender', 'pendidikan':'Pendidikan', 'pekerjaan':'Pekerjaan'};
                const isHeat = this.isMultiSatker && this.tat.compView === 'heatmap' && (this.tat.tabComp === 'pekerjaan' || this.tat.tabComp === 'pendidikan');
                
                let opts = {
                    series: dComp.series,
                    chart: { type: isHeat ? 'heatmap' : 'bar', height: isHeat ? 600 : 450, stacked: !isHeat, toolbar: { show: false }, fontFamily: 'inherit' },
                    plotOptions: { bar: { borderRadius: 2, columnWidth: '50%' }, heatmap: { shadeIntensity: 0.6, radius: 2, useFillColorAsStroke: false } },
                    xaxis: { categories: dComp.labels, labels: { style: { fontWeight: 'bold' } } },
                    colors: isHeat ? ['#198754'] : this.getBarColors(),
                    title: { text: this.getTitle(this.tat, 'Proporsi ' + names[this.tat.tabComp]), align: 'center', margin: 20, style: { fontSize: '18px', fontWeight: '500', color: '#212529' } },
                    dataLabels: {
                        enabled: true,
                        formatter: function(val, opt) {
                            if(isHeat) return val > 0 ? val : '';
                            let t = 0; opt.w.config.series.forEach(s => t += s.data[opt.dataPointIndex]);
                            return t === 0 ? "" : val + " (" + Math.round((val/t)*100) + "%)";
                        },
                        style: { colors: ['#212529'], fontSize: '12px' }
                    },
                    stroke: { show: true, width: 1, colors: ['#fff'] },
                    tooltip: { shared: !isHeat, intersect: isHeat },
                    legend: { show: !isHeat, position: 'top', fontWeight: 'bold', offsetY: -10 }
                };

                this.chartInst.tatC = new ApexCharts(this.$refs.chartTatComp, opts);
                this.chartInst.tatC.render();
            },

            // ==========================================
            // RENDER BB
            // ==========================================
            renderBbTrend() {
                if(!this.$refs.chartBbTrend || !this.bb.data) return;
                
                let dsRaw = this.bb.tabTrend === 'berat' ? this.bb.data.trend.berat : this.bb.data.trend.item;
                let ds = JSON.parse(JSON.stringify(dsRaw)); 
                if (this.bb.tabTrend === 'berat') { ds.forEach(s => s.data = s.data.map(v => this.getWeightVal(v))); }

                const isHeat = this.isMultiSatker && this.bb.adminTrendType === 'heatmap' && this.bb.month === 'per_bulan';
                const tTitle = this.bb.tabTrend === 'berat' ? 'Total Berat BB' : 'Total Item BB';
                const self = this;
                
                let opts = {
                    series: ds,
                    chart: { type: isHeat ? 'heatmap' : 'bar', height: isHeat ? 450 : 400, toolbar: { show: true }, fontFamily: 'inherit' },
                    xaxis: { categories: this.bb.data.trend_labels },
                    title: { text: this.getTitle(this.bb, tTitle), align: 'center', margin: 20, style: { fontSize: '18px', fontWeight: '500', color: '#212529' } },
                    grid: { show: true, borderColor: '#f1f3f5' },
                    plotOptions: { bar: { borderRadius: 3, columnWidth: this.isMultiSatker ? '85%' : '40%' }, heatmap: { shadeIntensity: 0.6, radius: 2, useFillColorAsStroke: false } },
                    colors: isHeat ? ['#dc3545'] : (this.isMultiSatker ? this.getBarColors() : (this.bb.tabTrend === 'berat' ? ['#dc3545'] : ['#198754'])),
                    dataLabels: {
                        enabled: isHeat ? true : !this.isMultiSatker,
                        formatter: function(val) { let v = val||0; return v > 0 ? new Intl.NumberFormat('id-ID', {maximumFractionDigits:1}).format(v) : ""; },
                        style: { colors: ['#212529'], fontSize: '12px' }
                    },
                    tooltip: { 
                        shared: true, intersect: false,
                        y: { formatter: function(val) { return new Intl.NumberFormat('id-ID', {maximumFractionDigits:2}).format(val||0) + (self.bb.tabTrend === 'berat' ? ' '+self.weightLabel : ' Item'); } }
                    },
                    legend: { show: !isHeat, position: 'top', fontWeight: 'bold', offsetY: -10 }
                };

                if(this.chartInst.bbT) this.chartInst.bbT.destroy();
                this.chartInst.bbT = new ApexCharts(this.$refs.chartBbTrend, opts);
                this.chartInst.bbT.render();
            },
            
            renderBbComp() {
                if(!this.$refs.chartBbComp || !this.bb.data) return;
                const dComp = this.bb.data.comp.sumber;
                
                let opts = {
                    series: dComp.series,
                    chart: { type: 'bar', height: 450, stacked: true, toolbar: { show: false }, fontFamily: 'inherit' },
                    plotOptions: { bar: { borderRadius: 2, columnWidth: '50%' } },
                    xaxis: { categories: dComp.labels, labels: { style: { fontWeight: 'bold' } } },
                    colors: ['#dc3545', '#ffc107'],
                    title: { text: this.getTitle(this.bb, 'Proporsi Sumber Perolehan'), align: 'center', margin: 20, style: { fontSize: '18px', fontWeight: '500', color: '#212529' } },
                    dataLabels: {
                        enabled: true,
                        formatter: function(val, opt) {
                            let t = 0; opt.w.config.series.forEach(s => t += s.data[opt.dataPointIndex]);
                            return t === 0 ? "" : val + " (" + Math.round((val/t)*100) + "%)";
                        },
                        style: { colors: ['#212529'], fontSize: '12px' }
                    },
                    stroke: { show: true, width: 1, colors: ['#fff'] },
                    tooltip: { shared: true, intersect: false },
                    legend: { position: 'top', fontWeight: 'bold', offsetY: -10 }
                };

                if(this.chartInst.bbC) this.chartInst.bbC.destroy();
                this.chartInst.bbC = new ApexCharts(this.$refs.chartBbComp, opts);
                this.chartInst.bbC.render();
            },

            // ==========================================
            // RENDER RANKING NARKOTIKA
            // ==========================================
            renderRank() {
                if(!this.$refs.chartRanking || !this.rank.data) return;
                const d = this.rank.data;
                const tTitle = `Top Narkotika ${this.rank.metric === 'berat' ? `(Berdasarkan Berat - ${this.weightLabel})` : '(Berdasarkan Frekuensi Kasus)'} - Sumber: ${this.rank.source.toUpperCase()}`;
                const dynHeight = Math.max(400, (d.labels.length * 45) + 100);
                const self = this;

                let dataPoints = d.data;
                if (this.rank.metric === 'berat') {
                    dataPoints = dataPoints.map(v => this.getWeightVal(v));
                }

                let opts = { 
                    series: [{ name: this.rank.metric === 'berat' ? 'Berat' : 'Frekuensi', data: dataPoints }], 
                    chart: { type: 'bar', height: dynHeight, toolbar: { show: false }, fontFamily: 'inherit' }, 
                    plotOptions: { bar: { horizontal: true, distributed: true, borderRadius: 4, barHeight: '75%' } }, 
                    xaxis: { categories: d.labels, labels: { formatter: function(val) { return new Intl.NumberFormat('id-ID').format(val||0); } } }, 
                    colors: this.getBarColors(),
                    dataLabels: { 
                        enabled: true, 
                        formatter: function(val) { 
                            let v = val || 0; 
                            if (self.rank.metric === 'berat') {
                                return new Intl.NumberFormat('id-ID', {maximumFractionDigits:2}).format(v) + ' ' + self.weightLabel;
                            }
                            return new Intl.NumberFormat('id-ID').format(v) + 'x'; 
                        },
                        style: { colors: ['#212529'], fontSize: '13px' } 
                    },
                    grid: { show: true, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } }, borderColor: '#f1f3f5' }, 
                    title: { text: this.getTitle(this.rank, tTitle), align: 'left', margin: 20, style: { fontSize: '16px', fontWeight: '500' } },
                    legend: { show: false },
                    tooltip: { 
                        y: { formatter: function(val) { 
                            return new Intl.NumberFormat('id-ID', {maximumFractionDigits:2}).format(val||0) + (self.rank.metric === 'berat' ? ' ' + self.weightLabel : ' Kasus/Item'); 
                        }} 
                    }
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