@extends('admin')

@section('content')
<main class="admin-main bg-light" x-data="dashboardRehab()" x-init="init()" style="min-height: 100vh;">
    <div class="container-fluid p-4">

        {{-- ========================================================= --}}
        {{-- A. HEADER & IDENTITAS --}}
        {{-- ========================================================= --}}
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3">
            <div>
                <h1 class="h3 mb-2 fw-bold text-dark">Dashboard Kinerja Rehabilitasi</h1>
                <div class="mt-2">
                    @if(auth()->user()->role === 'admin')
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
            
            {{-- PANGGIL TAB NAVIGASI --}}
            @include('dashboard.partials.nav')
        </div>

        {{-- FILTER GLOBAL WAKTU --}}
        <div class="d-flex justify-content-end mb-3">
            <div class="d-flex align-items-center bg-white p-2 rounded-3 shadow-sm border border-light gap-2">
                <span class="fw-bold text-muted small ms-2"><i class="bi bi-calendar-range me-2 text-primary"></i>Akumulasi:</span>
                <select x-model="globalStartYear" 
                        class="form-select form-select-sm border-0 bg-light fw-bold text-dark w-auto shadow-none pe-4">
                    @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                </select>
                <span class="fw-bold text-muted">-</span>
                <select x-model="globalEndYear" 
                        class="form-select form-select-sm border-0 bg-light fw-bold text-dark w-auto me-1 shadow-none pe-4">
                    @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                </select>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- B. 4 KARTU UTAMA --}}
        {{-- ========================================================= --}}
        <div class="row g-3 mb-5">
            {{-- Kartu 1: Rawat Jalan --}}
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 bg-white border-start border-4 border-success rounded-3 overflow-hidden">
                    <div class="card-body p-3 d-flex flex-column justify-content-center">
                        <div class="d-flex align-items-center mb-2">
                            <div class="bg-success bg-opacity-10 text-success p-2 rounded me-2">
                                <i class="bi bi-hospital fs-4"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-0">Rawat Jalan</h6>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small fw-bold">Realisasi Layanan</span>
                            <h3 class="fw-bold text-success mb-0" x-text="formatAngka(cards.rj.realisasi)"></h3>
                        </div>
                        <div class="bg-light p-2 rounded small fw-bold text-secondary d-flex justify-content-between">
                            <span>Target: <span class="text-dark" x-text="formatAngka(cards.rj.target)"></span></span>
                            <span :class="cards.rj.target > 0 && cards.rj.realisasi >= cards.rj.target ? 'text-success' : 'text-danger'" 
                                  x-text="calcPct(cards.rj.realisasi, cards.rj.target) + '%'"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kartu 2: Pasca Rehab --}}
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 bg-white border-start border-4 border-primary rounded-3 overflow-hidden">
                    <div class="card-body p-3 d-flex flex-column justify-content-center">
                        <div class="d-flex align-items-center mb-2">
                            <div class="bg-primary bg-opacity-10 text-primary p-2 rounded me-2">
                                <i class="bi bi-house-heart-fill fs-4"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-0">Pasca Rehab</h6>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small fw-bold">Realisasi Layanan</span>
                            <h3 class="fw-bold text-primary mb-0" x-text="formatAngka(cards.pasca.realisasi)"></h3>
                        </div>
                        <div class="bg-light p-2 rounded small fw-bold text-secondary d-flex justify-content-between">
                            <span>Target: <span class="text-dark" x-text="formatAngka(cards.pasca.target)"></span></span>
                            <span :class="cards.pasca.target > 0 && cards.pasca.realisasi >= cards.pasca.target ? 'text-success' : 'text-danger'" 
                                  x-text="calcPct(cards.pasca.realisasi, cards.pasca.target) + '%'"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kartu 3: SKHPN --}}
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 bg-white border-start border-4 border-warning rounded-3 overflow-hidden">
                    <div class="card-body p-3 d-flex flex-column justify-content-center">
                        <div class="d-flex align-items-center mb-2">
                            <div class="bg-warning bg-opacity-10 text-warning p-2 rounded me-2">
                                <i class="bi bi-file-earmark-medical-fill fs-4"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-0">Penerbitan SKHPN</h6>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small fw-bold">Total Diterbitkan</span>
                            <h3 class="fw-bold text-warning mb-0" x-text="formatAngka(cards.skhpn.realisasi)"></h3>
                        </div>
                        <div class="bg-light p-2 rounded small fw-bold text-secondary d-flex justify-content-between">
                            <span>Target: <span class="text-dark" x-text="formatAngka(cards.skhpn.target)"></span></span>
                            <span :class="cards.skhpn.target > 0 && cards.skhpn.realisasi >= cards.skhpn.target ? 'text-success' : 'text-danger'" 
                                  x-text="calcPct(cards.skhpn.realisasi, cards.skhpn.target) + '%'"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kartu 4: Profil Klien --}}
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 bg-white border-start border-4 rounded-3 overflow-hidden" 
                     style="border-color: #6f42c1 !important;">
                    <div class="card-body p-3 d-flex flex-column justify-content-center">
                        <div class="d-flex align-items-center mb-2">
                            <div class="bg-danger bg-opacity-10 p-2 rounded me-2" 
                                 style="color: #6f42c1; background-color: rgba(111, 66, 193, 0.1) !important;">
                                <i class="bi bi-people-fill fs-4"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-0">Profil Klien Rehab</h6>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small fw-bold">Total Kedatangan (Kasus)</span>
                            <h3 class="fw-bold mb-0" style="color: #6f42c1;" x-text="formatAngka(cards.klien.total)"></h3>
                        </div>
                        <div class="bg-light p-2 rounded small fw-bold text-secondary d-flex flex-column">
                            {{-- Sukarela & Hukum di atas, Unik di bawah --}}
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-dark" x-text="'Sukarela: ' + formatAngka(cards.klien.voluntary)"></span>
                                <span class="text-dark" x-text="'Hukum: ' + formatAngka(cards.klien.compulsory)"></span>
                            </div>
                            <div class="d-flex justify-content-between pt-1 border-top border-secondary border-opacity-25">
                                <span>Klien Unik:</span>
                                <span class="text-dark" x-text="formatAngka(cards.klien.unik) + ' Orang'"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- BLOK A: KINERJA LAYANAN (MODERN PROGRESS BAR) --}}
        {{-- ========================================================= --}}
        <div class="bg-white p-4 rounded-4 shadow-sm mb-4 border">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                <div>
                    <h5 class="m-0 fw-bold text-dark"><i class="bi bi-bar-chart-line-fill me-2 text-success"></i>Pusat Analisis Kinerja Layanan (Tahunan)</h5>
                </div>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <div class="d-flex align-items-center bg-light rounded-3 px-3 py-1">
                        <span class="text-muted fw-bold me-2 small">Pilih Tahun:</span>
                        <select x-model="layanan.year" 
                                class="form-select border-0 bg-transparent text-dark fw-bold shadow-none w-auto cursor-pointer p-1 pe-4">
                            @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            {{-- Tren Layanan --}}
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm h-100 bg-white rounded-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <template x-if="isMultiSatker">
                            <div class="d-flex align-items-center bg-light rounded-pill px-3 py-1">
                                <i class="bi bi-eye text-muted me-2"></i>
                                <select x-model="layanan.adminType" 
                                        class="form-select form-select-sm border-0 bg-transparent text-dark fw-bold shadow-none cursor-pointer pe-4" 
                                        style="min-width: 150px;">
                                    <option value="bar">Bar Chart</option>
                                    <option value="heatmap">Heatmap</option>
                                </select>
                            </div>
                        </template>
                        <div class="d-flex bg-light p-1 rounded-pill ms-auto">
                            <button @click="layanan.tabTrend = 'rj'" :class="layanan.tabTrend === 'rj' ? 'btn-success text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-3 border-0">Rawat Jalan</button>
                            <button @click="layanan.tabTrend = 'pasca'" :class="layanan.tabTrend === 'pasca' ? 'btn-primary text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-3 border-0">Pasca Rehab</button>
                            <button @click="layanan.tabTrend = 'skhpn'" :class="layanan.tabTrend === 'skhpn' ? 'btn-warning text-dark shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-3 border-0">SKHPN</button>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4 pt-0">
                        <div x-ref="chartLayananTrend" style="min-height: 350px;"></div>
                    </div>
                </div>
            </div>
            
            {{-- Progress Bar Target --}}
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm h-100 bg-white rounded-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-0 text-center">
                        <h6 class="fw-bold text-dark m-0">Capaian Target Setahun</h6>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-center p-4">
                        <template x-if="layanan.data">
                            <div>
                                {{-- Bar 1: Rawat Jalan --}}
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-end mb-2">
                                        <span class="fw-bold text-dark"><i class="bi bi-hospital text-success me-2"></i>Rawat Jalan</span>
                                        <span class="fw-bold text-secondary small">
                                            <span x-text="formatAngka(layanan.data.progress.rj.real)"></span> / <span x-text="formatAngka(layanan.data.progress.rj.target)"></span> Kasus (<span x-text="layanan.data.progress.rj.pct"></span>%)
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 12px; background-color: #e9ecef; border-radius: 10px;">
                                        <div class="progress-bar bg-success rounded-pill" role="progressbar" 
                                             :style="'width: ' + Math.min(layanan.data.progress.rj.pct, 100) + '%'" 
                                             aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>

                                {{-- Bar 2: Pasca Rehab --}}
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-end mb-2">
                                        <span class="fw-bold text-dark"><i class="bi bi-house-heart-fill text-primary me-2"></i>Pasca Rehab</span>
                                        <span class="fw-bold text-secondary small">
                                            <span x-text="formatAngka(layanan.data.progress.pasca.real)"></span> / <span x-text="formatAngka(layanan.data.progress.pasca.target)"></span> Kasus (<span x-text="layanan.data.progress.pasca.pct"></span>%)
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 12px; background-color: #e9ecef; border-radius: 10px;">
                                        <div class="progress-bar bg-primary rounded-pill" role="progressbar" 
                                             :style="'width: ' + Math.min(layanan.data.progress.pasca.pct, 100) + '%'" 
                                             aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>

                                {{-- Bar 3: SKHPN --}}
                                <div>
                                    <div class="d-flex justify-content-between align-items-end mb-2">
                                        <span class="fw-bold text-dark"><i class="bi bi-file-earmark-medical-fill text-warning me-2"></i>SKHPN</span>
                                        <span class="fw-bold text-secondary small">
                                            <span x-text="formatAngka(layanan.data.progress.skhpn.real)"></span> / <span x-text="formatAngka(layanan.data.progress.skhpn.target)"></span> Kasus (<span x-text="layanan.data.progress.skhpn.pct"></span>%)
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 12px; background-color: #e9ecef; border-radius: 10px;">
                                        <div class="progress-bar bg-warning rounded-pill" role="progressbar" 
                                             :style="'width: ' + Math.min(layanan.data.progress.skhpn.pct, 100) + '%'" 
                                             aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- BLOK B: DEMOGRAFI KLIEN REHAB --}}
        {{-- ========================================================= --}}
        <div class="bg-white p-4 rounded-4 shadow-sm mb-4 border">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                <div>
                    <h5 class="m-0 fw-bold" style="color: #6f42c1;"><i class="bi bi-people-fill me-2"></i>Pusat Analisis Demografi Klien</h5>
                </div>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <div class="d-flex align-items-center bg-light rounded-3 px-3 py-1">
                        <select x-model="demo.mode" 
                                class="form-select border-0 bg-transparent text-dark fw-bold shadow-none w-auto cursor-pointer pe-4">
                            <option value="monthly">Per Bulan</option>
                            <option value="yearly">Rentang Tahun</option>
                        </select>
                        
                        <template x-if="demo.mode === 'monthly'">
                            <select x-model="demo.m_year" 
                                    class="form-select border-0 bg-transparent text-dark fw-bold shadow-none w-auto ms-1 cursor-pointer pe-4">
                                @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                            </select>
                        </template>
                        
                        <template x-if="demo.mode === 'yearly'">
                            <div class="d-flex align-items-center ms-1 bg-white rounded">
                                <select x-model="demo.y_start" 
                                        class="form-select form-select-sm border-0 bg-transparent text-dark fw-bold shadow-none w-auto cursor-pointer p-1 pe-3">
                                    @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                                </select>
                                <span class="text-muted fw-bold">-</span>
                                <select x-model="demo.y_end" 
                                        class="form-select form-select-sm border-0 bg-transparent text-dark fw-bold shadow-none w-auto cursor-pointer p-1 pe-3">
                                    @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                                </select>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            {{-- Tren Kedatangan --}}
            <div class="col-xl-6">
                <div class="card border-0 shadow-sm h-100 bg-white rounded-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <template x-if="isMultiSatker">
                            <div class="d-flex align-items-center bg-light rounded-pill px-3 py-1">
                                <i class="bi bi-eye text-muted me-2"></i>
                                <select x-model="demo.adminType" 
                                        class="form-select form-select-sm border-0 bg-transparent text-dark fw-bold shadow-none cursor-pointer pe-4" 
                                        style="min-width: 150px;">
                                    <option value="bar">Bar Chart</option>
                                    <option value="heatmap">Heatmap</option>
                                </select>
                            </div>
                        </template>
                        <div class="d-flex bg-light p-1 rounded-pill ms-auto">
                            <button class="btn btn-sm btn-dark text-white rounded-pill fw-bold px-3 border-0 shadow-sm">Total Kedatangan</button>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4 pt-0"><div x-ref="chartDemoTrend" style="min-height: 400px;"></div></div>
                </div>
            </div>
            
            {{-- Proporsi Klien --}}
            <div class="col-xl-6">
                <div class="card border-0 shadow-sm h-100 bg-white rounded-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <template x-if="demo.mode === 'monthly'">
                            <div class="d-flex align-items-center bg-light rounded-pill px-3 py-1">
                                <i class="bi bi-filter text-muted me-2"></i>
                                <select x-model="demo.m_month" 
                                        class="form-select form-select-sm border-0 bg-transparent text-dark fw-bold shadow-none cursor-pointer pe-4">
                                    <option value="all">Setahun</option>
                                    <option value="1">Januari</option><option value="2">Februari</option>
                                    <option value="3">Maret</option><option value="4">April</option>
                                    <option value="5">Mei</option><option value="6">Juni</option>
                                    <option value="7">Juli</option><option value="8">Agustus</option>
                                    <option value="9">September</option><option value="10">Oktober</option>
                                    <option value="11">November</option><option value="12">Desember</option>
                                </select>
                            </div>
                        </template>
                        <div class="d-flex bg-light p-1 rounded-pill ms-auto flex-wrap">
                            <button @click="demo.tabComp = 'sumber'" :class="demo.tabComp === 'sumber' ? 'btn-dark text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-3 border-0">Sumber</button>
                            <button @click="demo.tabComp = 'gender'" :class="demo.tabComp === 'gender' ? 'btn-dark text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-3 border-0">Gender</button>
                            <button @click="demo.tabComp = 'usia'" :class="demo.tabComp === 'usia' ? 'btn-dark text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-3 border-0">Usia</button>
                            <button @click="demo.tabComp = 'pendidikan'" :class="demo.tabComp === 'pendidikan' ? 'btn-dark text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-3 border-0">Pendidikan</button>
                            <button @click="demo.tabComp = 'pekerjaan'" :class="demo.tabComp === 'pekerjaan' ? 'btn-dark text-white shadow-sm' : 'btn-light text-secondary bg-transparent'" class="btn btn-sm rounded-pill fw-bold px-3 border-0">Pekerjaan</button>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4 pt-0"><div x-ref="chartDemoComp" style="min-height: 400px;"></div></div>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- BLOK C: RANKING ZAT ADIKTIF (BACKGROUND PUTIH) --}}
        {{-- ========================================================= --}}
        <div class="bg-white p-4 rounded-4 shadow-sm mb-3 border">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                <div>
                    <h5 class="m-0 fw-bold text-dark"><i class="bi bi-bar-chart-steps me-2 text-warning"></i>Pemetaan Zat Adiktif Klien Rehab</h5>
                </div>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <div class="d-flex align-items-center bg-light rounded-3 px-3 py-1">
                        <i class="bi bi-sort-down text-muted me-2"></i>
                        <select x-model="rank.limit" 
                                class="form-select border-0 bg-transparent text-dark fw-bold shadow-none cursor-pointer p-0 pe-4" 
                                style="min-width: 120px;">
                            <option value="all">Semua Jenis</option>
                            <option value="10">Top 10 Saja</option>
                            <option value="5">Top 5 Saja</option>
                        </select>
                    </div>
                    
                    <div class="d-flex align-items-center bg-light rounded-3 px-3 py-1">
                        <select x-model="rank.mode" 
                                class="form-select border-0 bg-transparent text-dark fw-bold shadow-none w-auto cursor-pointer p-0 pe-4">
                            <option value="monthly">Per Tahun</option>
                            <option value="yearly">Rentang Tahun</option>
                        </select>
                        
                        <template x-if="rank.mode === 'monthly'">
                            <div class="d-flex align-items-center ms-1">
                                <select x-model="rank.m_month" 
                                        class="form-select border-0 bg-transparent text-dark fw-bold shadow-none w-auto cursor-pointer p-0 pe-2">
                                    <option value="all">Setahun</option>
                                    <option value="1">Jan</option><option value="2">Feb</option><option value="3">Mar</option><option value="4">Apr</option>
                                    <option value="5">Mei</option><option value="6">Jun</option><option value="7">Jul</option><option value="8">Agu</option>
                                    <option value="9">Sep</option><option value="10">Okt</option><option value="11">Nov</option><option value="12">Des</option>
                                </select>
                                <select x-model="rank.m_year" 
                                        class="form-select border-0 bg-transparent text-dark fw-bold shadow-none w-auto ms-1 cursor-pointer p-0 pe-3">
                                    @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                                </select>
                            </div>
                        </template>
                        
                        <template x-if="rank.mode === 'yearly'">
                            <div class="d-flex align-items-center ms-1">
                                <select x-model="rank.y_start" 
                                        class="form-select form-select-sm border-0 bg-transparent text-dark fw-bold shadow-none w-auto cursor-pointer p-0 pe-3">
                                    @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                                </select>
                                <span class="text-muted fw-bold mx-1">-</span>
                                <select x-model="rank.y_end" 
                                        class="form-select form-select-sm border-0 bg-transparent text-dark fw-bold shadow-none w-auto cursor-pointer p-0 pe-3">
                                    @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                                </select>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm bg-white rounded-4 mb-5">
            <div class="card-body px-4 pb-4 pt-4">
                <div style="max-height: 500px; overflow-y: auto; overflow-x: hidden;" class="pe-2">
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
        Alpine.data('dashboardRehab', () => ({
            // Global State
            globalSatkerId: '', 
            globalStartYear: '{{ min($years) }}', 
            globalEndYear: '{{ max($years) }}',
            
            cards: { 
                rj: {realisasi:0, target:0}, 
                pasca: {realisasi:0, target:0}, 
                skhpn: {realisasi:0, target:0}, 
                klien: {total:0, unik:0, voluntary:0, compulsory:0} 
            },
            
            isMultiSatker: false,
            chartInst: { layT: null, demoT: null, demoC: null, rank: null },
            
            getBarColors() { return ['#198754', '#0d6efd', '#fd7e14', '#6f42c1', '#dc3545', '#0dcaf0', '#20c997', '#ffc107', '#6c757d']; },

            // States
            layanan: { year: '{{ max($years) }}', tabTrend: 'rj', adminType: 'bar', data: null },
            demo: { mode: 'monthly', m_year: '{{ max($years) }}', m_month: 'all', y_start: '{{ min($years) }}', y_end: '{{ max($years) }}', tabComp: 'sumber', adminType: 'bar', data: null },
            rank: { mode: 'monthly', m_year: '{{ max($years) }}', m_month: 'all', y_start: '{{ min($years) }}', y_end: '{{ max($years) }}', limit: 'all', data: null },

            init() {
                this.fetchAll();
                
                this.$watch('globalSatkerId', () => this.fetchAll());
                this.$watch('globalStartYear', () => { this.globalEndYear = Math.max(this.globalStartYear, this.globalEndYear); this.fetchGlobal(); });
                this.$watch('globalEndYear', () => { this.globalStartYear = Math.min(this.globalStartYear, this.globalEndYear); this.fetchGlobal(); });
                
                ['year','adminType'].forEach(p => this.$watch('layanan.'+p, () => this.fetchLayanan()));
                this.$watch('layanan.tabTrend', () => this.renderLayananTrend());
                
                ['mode','m_year','m_month','y_start','y_end','adminType'].forEach(p => this.$watch('demo.'+p, () => this.fetchDemo()));
                this.$watch('demo.tabComp', () => this.renderDemoComp());

                ['mode','m_year','m_month','y_start','y_end','limit'].forEach(p => this.$watch('rank.'+p, () => this.fetchRank()));
            },

            fetchAll() { 
                this.fetchGlobal(); 
                this.fetchLayanan(); 
                this.fetchDemo(); 
                this.fetchRank(); 
            },

            formatAngka(num) { return new Intl.NumberFormat('id-ID').format(num || 0); },
            calcPct(r, t) { return t > 0 ? ((r/t)*100).toFixed(1) : (r > 0 ? 100 : 0); },
            getTitle(st, metric) {
                let time = st.mode === 'monthly' ? (st.m_month === 'all' ? `(Tahun ${st.m_year})` : `(Bulan ${st.m_month} ${st.m_year})`) : `(${st.y_start} - ${st.y_end})`;
                return `${metric} ${time}`;
            },

            // Fetches
            fetchGlobal() { fetch(`{{ route('dashboard.rehab.api.global') }}?start_year=${this.globalStartYear}&end_year=${this.globalEndYear}&satker_id=${this.globalSatkerId}`).then(r=>r.json()).then(res => this.cards = res); },
            fetchLayanan() { fetch(`{{ route('dashboard.rehab.api.layanan') }}?satker_id=${this.globalSatkerId}&year=${this.layanan.year}`).then(r=>r.json()).then(res => { this.layanan.data = res; this.isMultiSatker = res.is_multi; this.renderLayananTrend(); }); },
            fetchDemo() { fetch(`{{ route('dashboard.rehab.api.demografi') }}?satker_id=${this.globalSatkerId}&mode=${this.demo.mode}&m_year=${this.demo.m_year}&m_month=${this.demo.m_month}&y_start=${this.demo.y_start}&y_end=${this.demo.y_end}`).then(r=>r.json()).then(res => { this.demo.data = res; this.renderDemoTrend(); this.renderDemoComp(); }); },
            fetchRank() { fetch(`{{ route('dashboard.rehab.api.ranking') }}?satker_id=${this.globalSatkerId}&mode=${this.rank.mode}&m_year=${this.rank.m_year}&m_month=${this.rank.m_month}&y_start=${this.rank.y_start}&y_end=${this.rank.y_end}&limit=${this.rank.limit}`).then(r=>r.json()).then(res => { this.rank.data = res; this.renderRank(); }); },

            // Render Layanan
            renderLayananTrend() {
                if(!this.$refs.chartLayananTrend || !this.layanan.data) return;
                const ds = this.layanan.data.trend[this.layanan.tabTrend];
                const isHeat = this.isMultiSatker && this.layanan.adminType === 'heatmap';
                const titles = {rj:'Realisasi Rawat Jalan', pasca:'Realisasi Pasca Rehab', skhpn:'Penerbitan SKHPN'};
                const baseColor = this.layanan.tabTrend === 'rj' ? '#198754' : (this.layanan.tabTrend === 'pasca' ? '#0d6efd' : '#ffc107');
                
                let opts = {
                    series: ds,
                    chart: { type: isHeat?'heatmap':'bar', height: 350, toolbar: {show:true}, fontFamily: 'inherit' },
                    xaxis: { categories: this.layanan.data.trend_labels },
                    title: { text: `${titles[this.layanan.tabTrend]} (Tahun ${this.layanan.year})`, align: 'center', margin: 20, style: {fontSize: '16px', fontWeight: '500'} },
                    plotOptions: { 
                        bar: { borderRadius: 4, columnWidth: this.isMultiSatker ? '85%' : '50%' }, 
                        heatmap: { useFillColorAsStroke: false } 
                    },
                    colors: isHeat ? [baseColor] : (this.isMultiSatker ? this.getBarColors() : [baseColor]),
                    dataLabels: { 
                        enabled: isHeat ? true : !this.isMultiSatker, 
                        formatter: (val) => { let v = val || 0; return v > 0 ? this.formatAngka(v) : "0"; }, 
                        style: {colors: ['#212529']} 
                    },
                    legend: { show: !isHeat, position: 'top' },
                    yaxis: { labels: { formatter: (val) => typeof val === 'number' ? Math.round(val || 0) : val } }
                };
                if(this.chartInst.layT) this.chartInst.layT.destroy(); 
                this.chartInst.layT = new ApexCharts(this.$refs.chartLayananTrend, opts); 
                this.chartInst.layT.render();
            },

            // Render Demo
            renderDemoTrend() {
                if(!this.$refs.chartDemoTrend || !this.demo.data) return;
                const isHeat = this.isMultiSatker && this.demo.adminType === 'heatmap';
                let opts = {
                    series: this.demo.data.trend.kedatangan,
                    chart: { type: isHeat?'heatmap':'bar', height: 400, toolbar: {show:true}, fontFamily: 'inherit' },
                    xaxis: { categories: this.demo.data.trend_labels },
                    title: { text: this.getTitle(this.demo, 'Total Kedatangan/Kasus'), align: 'center', margin: 20, style: {fontSize: '16px', fontWeight: '500'} },
                    plotOptions: { 
                        bar: { borderRadius: 4, columnWidth: this.isMultiSatker ? '85%' : '50%' }, 
                        heatmap: { useFillColorAsStroke: false } 
                    },
                    colors: isHeat ? ['#6f42c1'] : (this.isMultiSatker ? this.getBarColors() : ['#6f42c1']),
                    dataLabels: { 
                        enabled: isHeat ? true : !this.isMultiSatker, 
                        formatter: (val) => { let v = val || 0; return v > 0 ? this.formatAngka(v) : "0"; }, 
                        style: {colors: ['#212529']} 
                    },
                    legend: { show: !isHeat, position: 'top' },
                    yaxis: { labels: { formatter: (val) => typeof val === 'number' ? Math.round(val || 0) : val } }
                };
                if(this.chartInst.demoT) this.chartInst.demoT.destroy(); 
                this.chartInst.demoT = new ApexCharts(this.$refs.chartDemoTrend, opts); 
                this.chartInst.demoT.render();
            },
            
            renderDemoComp() {
                if(!this.$refs.chartDemoComp || !this.demo.data) return;
                const ds = this.demo.data.comp[this.demo.tabComp];
                const names = {sumber:'Sumber Pasien', gender:'Gender', usia:'Kelompok Usia', pendidikan:'Pendidikan', pekerjaan:'Pekerjaan'};
                let opts = {
                    series: ds,
                    chart: { type: 'bar', height: 400, stacked: true, toolbar: {show:false}, fontFamily: 'inherit' },
                    plotOptions: { bar: { borderRadius: 2, columnWidth: '40%' } },
                    xaxis: { categories: this.demo.data.comp_labels, labels: { style: { fontWeight: 'bold' } } },
                    colors: this.getBarColors(),
                    title: { text: this.getTitle(this.demo, 'Proporsi '+names[this.demo.tabComp]), align: 'center', margin: 20, style: {fontSize: '16px', fontWeight: '500'} },
                    dataLabels: { 
                        enabled: true, 
                        formatter: function(v,o){ 
                            let t=0; o.w.config.series.forEach(s=>t+=s.data[o.dataPointIndex]); 
                            return t===0 ? "" : v+" ("+Math.round((v/t)*100)+"%)"; 
                        }, 
                        style: {colors: ['#212529']} 
                    },
                    legend: { position: 'top', offsetY: -10 }
                };
                if(this.chartInst.demoC) this.chartInst.demoC.destroy(); 
                this.chartInst.demoC = new ApexCharts(this.$refs.chartDemoComp, opts); 
                this.chartInst.demoC.render();
            },

            // Render Rank
            renderRank() {
                if(!this.$refs.chartRanking || !this.rank.data) return;
                const d = this.rank.data;
                const dynHeight = Math.max(300, (d.labels.length * 40) + 100);
                let opts = { 
                    series: [{ name: 'Frekuensi', data: d.data }], 
                    chart: { type: 'bar', height: dynHeight, toolbar: { show: false }, fontFamily: 'inherit' }, 
                    plotOptions: { bar: { horizontal: true, distributed: true, borderRadius: 4, barHeight: '70%' } }, 
                    xaxis: { categories: d.labels }, 
                    dataLabels: { 
                        enabled: true, 
                        formatter: (val) => this.formatAngka(val || 0), 
                        style: { colors: ['#333'] } 
                    },
                    grid: { show: false, xaxis: { lines: { show: false } }, yaxis: { lines: { show: false } } }, 
                    title: { text: this.getTitle(this.rank, 'Ranking Zat Adiktif Dikonsumsi (Frekuensi Kasus)'), align: 'left', margin: 20, style: { fontSize: '16px', fontWeight: '500' } }
                };
                if (this.chartInst.rank) this.chartInst.rank.destroy(); 
                this.chartInst.rank = new ApexCharts(this.$refs.chartRanking, opts); 
                this.chartInst.rank.render();
            }
        }));
    });
</script>
@endpush