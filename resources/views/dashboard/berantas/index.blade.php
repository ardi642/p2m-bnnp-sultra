@extends('admin')

@section('content')
<main class="admin-main bg-light" x-data="dashboardBerantas()" x-init="init()" style="min-height: 100vh;">
    <div class="container-fluid p-4">

        {{-- ========================================================= --}}
        {{-- A. HEADER & IDENTITAS --}}
        {{-- ========================================================= --}}
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3">
            <div>
                <h1 class="h3 mb-2 fw-bold text-dark">Dashboard Kinerja Pemberantasan</h1>
                <div class="mt-2">
                    @if(auth()->user()->role === 'admin')
                        <div class="input-group shadow-sm" style="max-width: 400px;">
                            <span class="input-group-text bg-white border-primary text-primary">
                                <i class="bi bi-building-fill"></i>
                            </span>
                            <select x-model="globalSatkerId" class="form-select border-primary text-primary fw-bold" style="font-size: 1.1rem;">
                                <option value="">Seluruh Satuan Kerja (Gabungan)</option>
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
                <a href="{{ route('dashboard.berantas.index') }}" class="btn btn-primary fw-bold px-4">
                    <i class="bi bi-shield-fill-check me-1"></i> Berantas
                </a>
            </div>
            @endif
        </div>

        {{-- FILTER GLOBAL WAKTU --}}
        <div class="d-flex justify-content-end mb-3">
            <div class="d-flex align-items-center bg-white p-2 rounded-3 shadow-sm border gap-2">
                <span class="fw-bold text-muted small ms-2"><i class="bi bi-calendar-range me-2 text-primary"></i>Akumulasi:</span>
                <select x-model="globalStartYear" class="form-select form-select-sm border-secondary fw-bold text-dark w-auto">
                    @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                </select>
                <span class="fw-bold text-muted">-</span>
                <select x-model="globalEndYear" class="form-select form-select-sm border-secondary fw-bold text-dark w-auto me-1">
                    @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                </select>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- B. 3 KARTU UTAMA --}}
        {{-- ========================================================= --}}
        <div class="row g-3 mb-5">
            {{-- Kartu 1: LKN --}}
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 bg-white border-start border-4 border-primary rounded-3 overflow-hidden">
                    <div class="card-body p-4 d-flex flex-column justify-content-center">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary bg-opacity-10 text-primary p-2 rounded me-3"><i class="bi bi-briefcase-fill fs-3"></i></div>
                            <h5 class="fw-bold text-dark mb-0">Ungkap Kasus (LKN)</h5>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6"><span class="text-muted small fw-bold">Total LKN</span><h3 class="fw-bold text-primary mb-0" x-text="formatAngka(cards.lkn.kasus)"></h3></div>
                            <div class="col-6"><span class="text-muted small fw-bold">Tersangka</span><h3 class="fw-bold text-primary mb-0" x-text="formatAngka(cards.lkn.tersangka)"></h3></div>
                        </div>
                        <div class="bg-light border p-2 rounded small fw-bold text-secondary d-flex justify-content-between">
                            <span>Sitaan: <span class="text-dark" x-text="formatGram(cards.lkn.gram)"></span></span>
                            <span class="text-dark" x-text="formatAngka(cards.lkn.item) + ' Item'"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kartu 2: TAT --}}
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 bg-white border-start border-4 border-info rounded-3 overflow-hidden">
                    <div class="card-body p-4 d-flex flex-column justify-content-center">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-info bg-opacity-10 text-info p-2 rounded me-3"><i class="bi bi-file-medical-fill fs-3"></i></div>
                            <h5 class="fw-bold text-dark mb-0">Asesmen (TAT)</h5>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6"><span class="text-muted small fw-bold">Total Kasus</span><h3 class="fw-bold text-info mb-0" x-text="formatAngka(cards.tat.kasus)"></h3></div>
                            <div class="col-6"><span class="text-muted small fw-bold">Tersangka</span><h3 class="fw-bold text-info mb-0" x-text="formatAngka(cards.tat.tersangka)"></h3></div>
                        </div>
                        <div class="bg-light border p-2 rounded small fw-bold text-secondary d-flex justify-content-between">
                            <span>BB Terkait: <span class="text-dark" x-text="formatGram(cards.tat.gram)"></span></span>
                            <span class="text-dark" x-text="formatAngka(cards.tat.item) + ' Item'"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kartu 3: Register BB --}}
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
                                <span x-text="formatGram(cards.reg.total_gram)"></span>
                                <span class="badge bg-danger-subtle text-danger border border-danger fs-6" x-text="formatAngka(cards.reg.total_item) + ' Item'"></span>
                            </h3>
                        </div>
                        <div class="row g-2 bg-light border p-2 rounded small">
                            <div class="col-6 border-end border-secondary"><span class="text-muted fw-bold d-block">Hasil Tangkap</span><span class="text-dark fw-bold" x-text="formatGramSingkat(cards.reg.tangkap_gram)"></span> <span class="text-muted" x-text="'('+cards.reg.tangkap_item+'x)'"></span></div>
                            <div class="col-6 ps-2"><span class="text-muted fw-bold d-block">Temuan (Tak Bertuan)</span><span class="text-dark fw-bold" x-text="formatGramSingkat(cards.reg.temuan_gram)"></span> <span class="text-muted" x-text="'('+cards.reg.temuan_item+'x)'"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- BLOK A: UNGKAP KASUS (LKN) --}}
        {{-- ========================================================= --}}
        <div class="bg-white p-4 rounded-4 shadow-sm mb-4 border border-primary">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                <div>
                    <h5 class="m-0 fw-bold text-dark"><i class="bi bi-briefcase-fill me-2 text-primary"></i>Pusat Analisis Ungkap Kasus (LKN)</h5>
                </div>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    
                    <div class="input-group shadow-sm w-auto">
                        <span class="input-group-text bg-light border-secondary"><i class="bi bi-funnel text-muted"></i></span>
                        <select x-model="lkn.narkotika" class="form-select border-secondary text-dark fw-bold" style="min-width: 180px;">
                            <option value="">Semua Narkotika</option>
                            @foreach($narkotikas as $n) <option value="{{ $n->id }}">{{ $n->nama_narkotika }}</option> @endforeach
                        </select>
                    </div>

                    <div class="input-group shadow-sm w-auto">
                        <select x-model="lkn.mode" class="form-select border-secondary text-dark fw-bold w-auto">
                            <option value="monthly">Per Bulan</option>
                            <option value="yearly">Rentang Tahun</option>
                        </select>
                        <template x-if="lkn.mode === 'monthly'">
                            <select x-model="lkn.m_year" class="form-select border-secondary text-dark fw-bold w-auto">
                                @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                            </select>
                        </template>
                        <template x-if="lkn.mode === 'yearly'">
                            <div class="d-flex border border-secondary rounded-end bg-white">
                                <select x-model="lkn.y_start" class="form-select border-0 bg-transparent text-dark fw-bold w-auto px-2">
                                    @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                                </select>
                                <span class="text-muted fw-bold align-self-center">-</span>
                                <select x-model="lkn.y_end" class="form-select border-0 bg-transparent text-dark fw-bold w-auto px-2">
                                    @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                                </select>
                            </div>
                        </template>
                    </div>

                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            {{-- Tren LKN --}}
            <div class="col-xl-6">
                <div class="card border-0 shadow-sm h-100 bg-white rounded-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <template x-if="isMultiSatker">
                            <div class="input-group input-group-sm shadow-sm w-auto">
                                <span class="input-group-text bg-light border-secondary"><i class="bi bi-eye text-muted"></i></span>
                                <select x-model="lkn.adminType" class="form-select border-secondary text-dark fw-bold" style="min-width: 150px;">
                                    <option value="bar">Bar Chart</option>
                                    <option value="heatmap">Heatmap</option>
                                </select>
                            </div>
                        </template>
                        <div class="btn-group btn-group-sm shadow-sm rounded-pill">
                            <button @click="lkn.tabTrend = 'kasus'" :class="lkn.tabTrend === 'kasus' ? 'btn-primary text-white' : 'btn-outline-secondary'" class="btn fw-bold px-3">LKN</button>
                            <button @click="lkn.tabTrend = 'tersangka'" :class="lkn.tabTrend === 'tersangka' ? 'btn-primary text-white' : 'btn-outline-secondary'" class="btn fw-bold px-3">Tersangka</button>
                            <button @click="lkn.tabTrend = 'berat'" :class="lkn.tabTrend === 'berat' ? 'btn-danger text-white' : 'btn-outline-secondary'" class="btn fw-bold px-3">Berat (g)</button>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4 pt-0"><div x-ref="chartLknTrend" style="min-height: 400px;"></div></div>
                </div>
            </div>
            {{-- Proporsi LKN --}}
            <div class="col-xl-6">
                <div class="card border-0 shadow-sm h-100 bg-white rounded-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <template x-if="lkn.mode === 'monthly'">
                            <div class="input-group input-group-sm shadow-sm w-auto">
                                <span class="input-group-text bg-light border-secondary"><i class="bi bi-filter text-muted"></i></span>
                                <select x-model="lkn.m_month" class="form-select border-secondary text-dark fw-bold">
                                    <option value="all">Setahun</option><option value="1">Jan</option><option value="2">Feb</option><option value="3">Mar</option><option value="4">Apr</option><option value="5">Mei</option><option value="6">Jun</option><option value="7">Jul</option><option value="8">Agu</option><option value="9">Sep</option><option value="10">Okt</option><option value="11">Nov</option><option value="12">Des</option>
                                </select>
                            </div>
                        </template>
                        <div class="btn-group btn-group-sm shadow-sm rounded-pill">
                            <button @click="lkn.tabComp = 'gender'" :class="lkn.tabComp === 'gender' ? 'btn-success text-white' : 'btn-outline-secondary'" class="btn fw-bold px-3">Gender</button>
                            <button @click="lkn.tabComp = 'pekerjaan'" :class="lkn.tabComp === 'pekerjaan' ? 'btn-warning text-dark' : 'btn-outline-secondary'" class="btn fw-bold px-3">Pekerjaan</button>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4 pt-0"><div x-ref="chartLknComp" style="min-height: 400px;"></div></div>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- BLOK B: TIM ASESMEN TERPADU (TAT) --}}
        {{-- ========================================================= --}}
        <div class="bg-white p-4 rounded-4 shadow-sm mb-4 border border-info">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                <div><h5 class="m-0 fw-bold text-dark"><i class="bi bi-file-medical-fill me-2 text-info"></i>Pusat Analisis Tim Asesmen Terpadu (TAT)</h5></div>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    
                    <div class="input-group shadow-sm w-auto">
                        <span class="input-group-text bg-light border-secondary"><i class="bi bi-funnel text-muted"></i></span>
                        <select x-model="tat.narkotika" class="form-select border-secondary text-dark fw-bold" style="min-width: 180px;">
                            <option value="">Semua Narkotika</option>
                            @foreach($narkotikas as $n) <option value="{{ $n->id }}">{{ $n->nama_narkotika }}</option> @endforeach
                        </select>
                    </div>

                    <div class="input-group shadow-sm w-auto">
                        <select x-model="tat.mode" class="form-select border-secondary text-dark fw-bold w-auto">
                            <option value="monthly">Per Bulan</option>
                            <option value="yearly">Rentang Tahun</option>
                        </select>
                        <template x-if="tat.mode === 'monthly'">
                            <select x-model="tat.m_year" class="form-select border-secondary text-dark fw-bold w-auto">
                                @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                            </select>
                        </template>
                        <template x-if="tat.mode === 'yearly'">
                            <div class="d-flex border border-secondary rounded-end bg-white">
                                <select x-model="tat.y_start" class="form-select border-0 bg-transparent text-dark fw-bold w-auto px-2">@foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach</select>
                                <span class="text-muted fw-bold align-self-center">-</span>
                                <select x-model="tat.y_end" class="form-select border-0 bg-transparent text-dark fw-bold w-auto px-2">@foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach</select>
                            </div>
                        </template>
                    </div>

                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            {{-- Tren TAT --}}
            <div class="col-xl-6">
                <div class="card border-0 shadow-sm h-100 bg-white rounded-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <template x-if="isMultiSatker">
                            <div class="input-group input-group-sm shadow-sm w-auto">
                                <span class="input-group-text bg-light border-secondary"><i class="bi bi-eye text-muted"></i></span>
                                <select x-model="tat.adminType" class="form-select border-secondary text-dark fw-bold" style="min-width: 150px;">
                                    <option value="bar">Bar Chart</option>
                                    <option value="heatmap">Heatmap</option>
                                </select>
                            </div>
                        </template>
                        <div class="btn-group btn-group-sm shadow-sm rounded-pill">
                            <button @click="tat.tabTrend = 'kasus'" :class="tat.tabTrend === 'kasus' ? 'btn-info text-white' : 'btn-outline-secondary'" class="btn fw-bold px-3">Kasus TAT</button>
                            <button @click="tat.tabTrend = 'tersangka'" :class="tat.tabTrend === 'tersangka' ? 'btn-info text-white' : 'btn-outline-secondary'" class="btn fw-bold px-3">Tersangka TAT</button>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4 pt-0"><div x-ref="chartTatTrend" style="min-height: 400px;"></div></div>
                </div>
            </div>
            {{-- Proporsi TAT --}}
            <div class="col-xl-6">
                <div class="card border-0 shadow-sm h-100 bg-white rounded-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <template x-if="tat.mode === 'monthly'">
                            <div class="input-group input-group-sm shadow-sm w-auto">
                                <span class="input-group-text bg-light border-secondary"><i class="bi bi-filter text-muted"></i></span>
                                <select x-model="tat.m_month" class="form-select border-secondary text-dark fw-bold">
                                    <option value="all">Setahun</option><option value="1">Jan</option><option value="2">Feb</option><option value="3">Mar</option><option value="4">Apr</option><option value="5">Mei</option><option value="6">Jun</option><option value="7">Jul</option><option value="8">Agu</option><option value="9">Sep</option><option value="10">Okt</option><option value="11">Nov</option><option value="12">Des</option>
                                </select>
                            </div>
                        </template>
                        <div class="btn-group btn-group-sm shadow-sm rounded-pill">
                            <button @click="tat.tabComp = 'rekom'" :class="tat.tabComp === 'rekom' ? 'btn-primary text-white' : 'btn-outline-secondary'" class="btn fw-bold px-3">Rekomendasi</button>
                            <button @click="tat.tabComp = 'gender'" :class="tat.tabComp === 'gender' ? 'btn-success text-white' : 'btn-outline-secondary'" class="btn fw-bold px-3">Gender</button>
                            <button @click="tat.tabComp = 'pendidikan'" :class="tat.tabComp === 'pendidikan' ? 'btn-dark text-white' : 'btn-outline-secondary'" class="btn fw-bold px-3">Pendidikan</button>
                            <button @click="tat.tabComp = 'pekerjaan'" :class="tat.tabComp === 'pekerjaan' ? 'btn-warning text-dark' : 'btn-outline-secondary'" class="btn fw-bold px-3">Pekerjaan</button>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4 pt-0"><div x-ref="chartTatComp" style="min-height: 400px;"></div></div>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- BLOK C: REGISTER BARANG BUKTI --}}
        {{-- ========================================================= --}}
        <div class="bg-white p-4 rounded-4 shadow-sm mb-4 border border-danger">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                <div><h5 class="m-0 fw-bold text-dark"><i class="bi bi-box-seam-fill me-2 text-danger"></i>Pusat Analisis Register Barang Bukti</h5></div>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    
                    <div class="input-group shadow-sm w-auto">
                        <span class="input-group-text bg-light border-secondary"><i class="bi bi-funnel text-muted"></i></span>
                        <select x-model="bb.narkotika" class="form-select border-secondary text-dark fw-bold" style="min-width: 180px;">
                            <option value="">Semua Narkotika</option>
                            @foreach($narkotikas as $n) <option value="{{ $n->id }}">{{ $n->nama_narkotika }}</option> @endforeach
                        </select>
                    </div>

                    <div class="input-group shadow-sm w-auto">
                        <select x-model="bb.mode" class="form-select border-secondary text-dark fw-bold w-auto">
                            <option value="monthly">Per Bulan</option>
                            <option value="yearly">Rentang Tahun</option>
                        </select>
                        <template x-if="bb.mode === 'monthly'">
                            <select x-model="bb.m_year" class="form-select border-secondary text-dark fw-bold w-auto">@foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach</select>
                        </template>
                        <template x-if="bb.mode === 'yearly'">
                            <div class="d-flex border border-secondary rounded-end bg-white">
                                <select x-model="bb.y_start" class="form-select border-0 bg-transparent text-dark fw-bold w-auto px-2">@foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach</select>
                                <span class="text-muted fw-bold align-self-center">-</span>
                                <select x-model="bb.y_end" class="form-select border-0 bg-transparent text-dark fw-bold w-auto px-2">@foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach</select>
                            </div>
                        </template>
                    </div>

                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            {{-- Tren BB --}}
            <div class="col-xl-6">
                <div class="card border-0 shadow-sm h-100 bg-white rounded-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <template x-if="isMultiSatker">
                            <div class="input-group input-group-sm shadow-sm w-auto">
                                <span class="input-group-text bg-light border-secondary"><i class="bi bi-eye text-muted"></i></span>
                                <select x-model="bb.adminType" class="form-select border-secondary text-dark fw-bold" style="min-width: 150px;">
                                    <option value="bar">Bar Chart</option>
                                    <option value="heatmap">Heatmap</option>
                                </select>
                            </div>
                        </template>
                        <div class="btn-group btn-group-sm shadow-sm rounded-pill">
                            <button @click="bb.tabTrend = 'berat'" :class="bb.tabTrend === 'berat' ? 'btn-danger text-white' : 'btn-outline-secondary'" class="btn fw-bold px-3">Total Berat (g)</button>
                            <button @click="bb.tabTrend = 'item'" :class="bb.tabTrend === 'item' ? 'btn-primary text-white' : 'btn-outline-secondary'" class="btn fw-bold px-3">Total Item</button>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4 pt-0"><div x-ref="chartBbTrend" style="min-height: 400px;"></div></div>
                </div>
            </div>
            {{-- Proporsi BB --}}
            <div class="col-xl-6">
                <div class="card border-0 shadow-sm h-100 bg-white rounded-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <template x-if="bb.mode === 'monthly'">
                            <div class="input-group input-group-sm shadow-sm w-auto">
                                <span class="input-group-text bg-light border-secondary"><i class="bi bi-filter text-muted"></i></span>
                                <select x-model="bb.m_month" class="form-select border-secondary text-dark fw-bold">
                                    <option value="all">Setahun</option><option value="1">Jan</option><option value="2">Feb</option><option value="3">Mar</option><option value="4">Apr</option><option value="5">Mei</option><option value="6">Jun</option><option value="7">Jul</option><option value="8">Agu</option><option value="9">Sep</option><option value="10">Okt</option><option value="11">Nov</option><option value="12">Des</option>
                                </select>
                            </div>
                        </template>
                        <div class="btn-group btn-group-sm shadow-sm rounded-pill">
                            <button class="btn btn-success text-white fw-bold px-4 border-0">Sumber Perolehan</button>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4 pt-0"><div x-ref="chartBbComp" style="min-height: 400px;"></div></div>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- BLOK D: RANKING JENIS NARKOTIKA --}}
        {{-- ========================================================= --}}
        <div class="bg-dark p-4 rounded-4 shadow-sm mb-3 border border-warning">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                <div><h5 class="m-0 fw-bold text-warning"><i class="bi bi-bar-chart-steps me-2 text-warning"></i>Pemetaan Tren Narkotika Sultra</h5></div>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    
                    <div class="input-group shadow-sm w-auto">
                        <span class="input-group-text bg-white border-white"><i class="bi bi-sort-down text-muted"></i></span>
                        <select x-model="rank.limit" class="form-select border-white text-dark fw-bold" style="min-width: 140px;">
                            <option value="all">Semua Jenis</option>
                            <option value="10">Top 10 Saja</option>
                            <option value="5">Top 5 Saja</option>
                        </select>
                    </div>

                    <div class="input-group shadow-sm w-auto">
                        <select x-model="rank.mode" class="form-select border-white text-dark fw-bold w-auto">
                            <option value="monthly">Per Bulan</option>
                            <option value="yearly">Rentang Tahun</option>
                        </select>
                        <template x-if="rank.mode === 'monthly'">
                            <select x-model="rank.m_year" class="form-select border-white text-dark fw-bold w-auto">@foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach</select>
                        </template>
                        <template x-if="rank.mode === 'yearly'">
                            <div class="d-flex border border-white rounded-end bg-white">
                                <select x-model="rank.y_start" class="form-select border-0 bg-transparent text-dark fw-bold w-auto px-2">@foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach</select>
                                <span class="text-muted fw-bold align-self-center">-</span>
                                <select x-model="rank.y_end" class="form-select border-0 bg-transparent text-dark fw-bold w-auto px-2">@foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach</select>
                            </div>
                        </template>
                    </div>

                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm bg-white rounded-4 mb-5">
            <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="btn-group btn-group-sm shadow-sm rounded-pill">
                    <button @click="rank.source = 'lkn'" :class="rank.source === 'lkn' ? 'btn-primary text-white' : 'btn-outline-secondary'" class="btn fw-bold px-3">Dari LKN</button>
                    <button @click="rank.source = 'tat'" :class="rank.source === 'tat' ? 'btn-info text-white' : 'btn-outline-secondary'" class="btn fw-bold px-3">Dari TAT</button>
                    <button @click="rank.source = 'bb'" :class="rank.source === 'bb' ? 'btn-danger text-white' : 'btn-outline-secondary'" class="btn fw-bold px-3">Dari Register BB</button>
                </div>
                <div class="btn-group btn-group-sm shadow-sm rounded-pill">
                    <button @click="rank.metric = 'berat'" :class="rank.metric === 'berat' ? 'btn-dark text-white' : 'btn-outline-secondary'" class="btn fw-bold px-3">Berdasarkan Berat</button>
                    <button @click="rank.metric = 'freq'" :class="rank.metric === 'freq' ? 'btn-dark text-white' : 'btn-outline-secondary'" class="btn fw-bold px-3">Berdasarkan Frekuensi</button>
                </div>
            </div>
            <div class="card-body px-4 pb-4 pt-2">
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
        Alpine.data('dashboardBerantas', () => ({
            // Global State
            globalSatkerId: '', 
            globalStartYear: '{{ min($years) }}', 
            globalEndYear: '{{ max($years) }}',
            cards: { 
                lkn: { kasus: 0, tersangka: 0, gram: 0, item: 0 },
                tat: { kasus: 0, tersangka: 0, gram: 0, item: 0 },
                reg: { total_gram: 0, total_item: 0, tangkap_gram: 0, tangkap_item: 0, temuan_gram: 0, temuan_item: 0 }
            },
            isMultiSatker: false,
            chartInst: { lknT: null, lknC: null, tatT: null, tatC: null, bbT: null, bbC: null, rank: null },
            
            getBarColors() { return ['#0d6efd', '#fd7e14', '#198754', '#6f42c1', '#dc3545', '#0dcaf0', '#20c997', '#ffc107', '#6c757d']; },

            // Modul States
            lkn: { mode: 'yearly', m_year: '{{ max($years) }}', m_month: 'all', y_start: '{{ min($years) }}', y_end: '{{ max($years) }}', narkotika: '', tabTrend: 'kasus', tabComp: 'gender', adminType: 'bar', data: null },
            tat: { mode: 'yearly', m_year: '{{ max($years) }}', m_month: 'all', y_start: '{{ min($years) }}', y_end: '{{ max($years) }}', narkotika: '', tabTrend: 'kasus', tabComp: 'rekom', adminType: 'bar', data: null },
            bb: { mode: 'yearly', m_year: '{{ max($years) }}', m_month: 'all', y_start: '{{ min($years) }}', y_end: '{{ max($years) }}', narkotika: '', tabTrend: 'berat', tabComp: 'sumber', adminType: 'bar', data: null },
            rank: { mode: 'yearly', m_year: '{{ max($years) }}', y_start: '{{ min($years) }}', y_end: '{{ max($years) }}', limit: 'all', source: 'lkn', metric: 'berat', data: null },

            init() {
                this.fetchAll();
                
                this.$watch('globalSatkerId', () => this.fetchAll());
                this.$watch('globalStartYear', () => { this.globalEndYear = Math.max(this.globalStartYear, this.globalEndYear); this.fetchGlobal(); });
                this.$watch('globalEndYear', () => { this.globalStartYear = Math.min(this.globalStartYear, this.globalEndYear); this.fetchGlobal(); });
                
                ['mode','m_year','m_month','y_start','y_end','narkotika','adminType'].forEach(p => this.$watch('lkn.'+p, () => this.fetchLkn()));
                this.$watch('lkn.tabTrend', () => this.renderLknTrend()); this.$watch('lkn.tabComp', () => this.renderLknComp());
                
                ['mode','m_year','m_month','y_start','y_end','narkotika','adminType'].forEach(p => this.$watch('tat.'+p, () => this.fetchTat()));
                this.$watch('tat.tabTrend', () => this.renderTatTrend()); this.$watch('tat.tabComp', () => this.renderTatComp());

                ['mode','m_year','m_month','y_start','y_end','narkotika','adminType'].forEach(p => this.$watch('bb.'+p, () => this.fetchBb()));
                this.$watch('bb.tabTrend', () => this.renderBbTrend()); this.$watch('bb.tabComp', () => this.renderBbComp());

                ['mode','m_year','y_start','y_end','limit','source','metric'].forEach(p => this.$watch('rank.'+p, () => this.fetchRank()));
            },

            fetchAll() { this.fetchGlobal(); this.fetchLkn(); this.fetchTat(); this.fetchBb(); this.fetchRank(); },

            // Formatters aman
            formatAngka(num) { return new Intl.NumberFormat('id-ID').format(num || 0); },
            formatGram(gram) {
                let g = gram || 0;
                if (g >= 1000000) return this.formatAngka(g) + ' g (' + this.formatAngka(g/1000000) + ' Ton)';
                if (g >= 1000) return this.formatAngka(g) + ' g (' + this.formatAngka(g/1000) + ' Kg)';
                return this.formatAngka(g) + ' g';
            },
            formatGramSingkat(gram) {
                let g = gram || 0;
                if (g >= 1000000) return this.formatAngka(g/1000000) + ' Ton';
                if (g >= 1000) return this.formatAngka(g/1000) + ' Kg';
                return this.formatAngka(g) + ' g';
            },
            getTitle(st, metric) {
                let time = st.mode === 'monthly' ? (st.m_month === 'all' ? `(Tahun ${st.m_year})` : `(Bulan ${st.m_month} ${st.m_year})`) : `(${st.y_start} - ${st.y_end})`;
                return `${metric} ${time}`;
            },

            // Api Fetches
            fetchGlobal() { fetch(`{{ route('dashboard.berantas.api.global') }}?start_year=${this.globalStartYear}&end_year=${this.globalEndYear}&satker_id=${this.globalSatkerId}`).then(r=>r.json()).then(res => this.cards = res); },
            fetchLkn() { fetch(`{{ route('dashboard.berantas.api.lkn') }}?satker_id=${this.globalSatkerId}&mode=${this.lkn.mode}&m_year=${this.lkn.m_year}&m_month=${this.lkn.m_month}&y_start=${this.lkn.y_start}&y_end=${this.lkn.y_end}&narkotika_id=${this.lkn.narkotika}`).then(r=>r.json()).then(res => { this.lkn.data = res; this.isMultiSatker = res.is_multi; this.renderLknTrend(); this.renderLknComp(); }); },
            fetchTat() { fetch(`{{ route('dashboard.berantas.api.tat') }}?satker_id=${this.globalSatkerId}&mode=${this.tat.mode}&m_year=${this.tat.m_year}&m_month=${this.tat.m_month}&y_start=${this.tat.y_start}&y_end=${this.tat.y_end}&narkotika_id=${this.tat.narkotika}`).then(r=>r.json()).then(res => { this.tat.data = res; this.renderTatTrend(); this.renderTatComp(); }); },
            fetchBb() { fetch(`{{ route('dashboard.berantas.api.bb') }}?satker_id=${this.globalSatkerId}&mode=${this.bb.mode}&m_year=${this.bb.m_year}&m_month=${this.bb.m_month}&y_start=${this.bb.y_start}&y_end=${this.bb.y_end}&narkotika_id=${this.bb.narkotika}`).then(r=>r.json()).then(res => { this.bb.data = res; this.renderBbTrend(); this.renderBbComp(); }); },
            fetchRank() { fetch(`{{ route('dashboard.berantas.api.ranking') }}?satker_id=${this.globalSatkerId}&mode=${this.rank.mode}&m_year=${this.rank.m_year}&y_start=${this.rank.y_start}&y_end=${this.rank.y_end}&source=${this.rank.source}&metric=${this.rank.metric}&limit=${this.rank.limit}`).then(r=>r.json()).then(res => { this.rank.data = res; this.renderRank(); }); },

            // =======================================================
            // RENDER LKN
            // =======================================================
            renderLknTrend() {
                if(!this.$refs.chartLknTrend || !this.lkn.data) return;
                const d = this.lkn.data;
                const ds = this.lkn.tabTrend === 'kasus' ? d.trend.kasus : (this.lkn.tabTrend === 'tersangka' ? d.trend.tersangka : d.trend.berat);
                const isHeat = this.isMultiSatker && this.lkn.adminType === 'heatmap';
                const tTitle = this.lkn.tabTrend === 'kasus' ? 'Jumlah Kasus LKN' : (this.lkn.tabTrend === 'tersangka' ? 'Jumlah Tersangka' : 'Total Berat Sitaan (g)');
                const self = this; 
                
                let opts = {
                    series: ds,
                    chart: { type: isHeat ? 'heatmap' : 'bar', height: 400, toolbar: { show: true }, fontFamily: 'inherit' },
                    xaxis: { categories: d.trend_labels },
                    title: { text: this.getTitle(this.lkn, tTitle), align: 'center', margin: 20, style: { fontSize: '18px', fontWeight: '500', color: '#212529' } },
                    grid: { show: true, borderColor: '#f1f3f5' },
                    plotOptions: {
                        bar: { borderRadius: 4, columnWidth: this.isMultiSatker ? '85%' : '50%' },
                        heatmap: { shadeIntensity: 0.6, radius: 4, useFillColorAsStroke: false }
                    },
                    colors: isHeat ? ['#0d6efd'] : (this.isMultiSatker ? this.getBarColors() : (this.lkn.tabTrend === 'berat' ? ['#dc3545'] : ['#0d6efd'])),
                    dataLabels: {
                        enabled: isHeat ? true : !this.isMultiSatker,
                        formatter: function(val) { return val > 0 ? new Intl.NumberFormat('id-ID').format(val) : ""; },
                        style: { colors: ['#212529'], fontSize: '13px' }
                    },
                    tooltip: { 
                        shared: true, intersect: false,
                        y: { formatter: function(val) { return self.lkn.tabTrend === 'berat' ? self.formatGram(val) : new Intl.NumberFormat('id-ID').format(val); } }
                    },
                    legend: { show: !isHeat, position: 'top', fontWeight: 'bold', offsetY: -10 },
                    // PERBAIKAN BUG YAXIS HEATMAP "NaN"
                    yaxis: { labels: { formatter: function(val) { return typeof val === 'number' ? Math.round(val) : val; } } }
                };

                if(this.chartInst.lknT) this.chartInst.lknT.destroy();
                this.chartInst.lknT = new ApexCharts(this.$refs.chartLknTrend, opts);
                this.chartInst.lknT.render();
            },
            renderLknComp() {
                if(!this.$refs.chartLknComp || !this.lkn.data) return;
                const ds = this.lkn.tabComp === 'gender' ? this.lkn.data.comp.gender : this.lkn.data.comp.pekerjaan;
                const tTitle = this.lkn.tabComp === 'gender' ? 'Proporsi Gender Tersangka' : 'Proporsi Pekerjaan Tersangka';
                
                let opts = {
                    series: ds,
                    chart: { type: 'bar', height: 400, stacked: true, toolbar: { show: false }, fontFamily: 'inherit' },
                    plotOptions: { bar: { borderRadius: 2, columnWidth: '40%' } },
                    xaxis: { categories: this.lkn.data.comp_labels, labels: { style: { fontWeight: 'bold' } } },
                    colors: this.getBarColors(),
                    title: { text: this.getTitle(this.lkn, tTitle), align: 'center', margin: 20, style: { fontSize: '18px', fontWeight: '500', color: '#212529' } },
                    grid: { show: true, borderColor: '#f1f3f5' },
                    dataLabels: {
                        enabled: true,
                        formatter: function(val, opt) {
                            let t = 0; opt.w.config.series.forEach(s => t += s.data[opt.dataPointIndex]);
                            return t === 0 ? "" : val + " (" + Math.round((val/t)*100) + "%)";
                        },
                        style: { colors: ['#212529'], fontSize: '12px' }
                    },
                    tooltip: { shared: true, intersect: false },
                    legend: { position: 'top', fontWeight: 'bold', offsetY: -10 }
                };

                if(this.chartInst.lknC) this.chartInst.lknC.destroy();
                this.chartInst.lknC = new ApexCharts(this.$refs.chartLknComp, opts);
                this.chartInst.lknC.render();
            },

            // =======================================================
            // RENDER TAT
            // =======================================================
            renderTatTrend() {
                if(!this.$refs.chartTatTrend || !this.tat.data) return;
                const d = this.tat.data;
                const ds = this.tat.tabTrend === 'kasus' ? d.trend.kasus : d.trend.tersangka;
                const isHeat = this.isMultiSatker && this.tat.adminType === 'heatmap';
                const tTitle = this.tat.tabTrend === 'kasus' ? 'Jumlah Kasus TAT' : 'Jumlah Tersangka TAT';
                
                let opts = {
                    series: ds,
                    chart: { type: isHeat ? 'heatmap' : 'bar', height: 400, toolbar: { show: true }, fontFamily: 'inherit' },
                    xaxis: { categories: d.trend_labels },
                    title: { text: this.getTitle(this.tat, tTitle), align: 'center', margin: 20, style: { fontSize: '18px', fontWeight: '500', color: '#212529' } },
                    grid: { show: true, borderColor: '#f1f3f5' },
                    plotOptions: {
                        bar: { borderRadius: 4, columnWidth: this.isMultiSatker ? '85%' : '50%' },
                        heatmap: { shadeIntensity: 0.6, radius: 4, useFillColorAsStroke: false }
                    },
                    colors: isHeat ? ['#0dcaf0'] : (this.isMultiSatker ? this.getBarColors() : ['#0dcaf0']),
                    dataLabels: {
                        enabled: isHeat ? true : !this.isMultiSatker,
                        formatter: function(val) { return val > 0 ? new Intl.NumberFormat('id-ID').format(val) : ""; },
                        style: { colors: ['#212529'], fontSize: '13px' }
                    },
                    tooltip: { shared: true, intersect: false },
                    legend: { show: !isHeat, position: 'top', fontWeight: 'bold', offsetY: -10 },
                    // PERBAIKAN BUG YAXIS HEATMAP "NaN"
                    yaxis: { labels: { formatter: function(val) { return typeof val === 'number' ? Math.round(val) : val; } } }
                };

                if(this.chartInst.tatT) this.chartInst.tatT.destroy();
                this.chartInst.tatT = new ApexCharts(this.$refs.chartTatTrend, opts);
                this.chartInst.tatT.render();
            },
            renderTatComp() {
                if(!this.$refs.chartTatComp || !this.tat.data) return;
                const ds = this.tat.data.comp[this.tat.tabComp];
                const names = {'rekom':'Rekomendasi', 'gender':'Gender', 'pendidikan':'Pendidikan', 'pekerjaan':'Pekerjaan'};
                
                let opts = {
                    series: ds,
                    chart: { type: 'bar', height: 400, stacked: true, toolbar: { show: false }, fontFamily: 'inherit' },
                    plotOptions: { bar: { borderRadius: 2, columnWidth: '40%' } },
                    xaxis: { categories: this.tat.data.comp_labels, labels: { style: { fontWeight: 'bold' } } },
                    colors: this.getBarColors(),
                    title: { text: this.getTitle(this.tat, 'Proporsi ' + names[this.tat.tabComp]), align: 'center', margin: 20, style: { fontSize: '18px', fontWeight: '500', color: '#212529' } },
                    grid: { show: true, borderColor: '#f1f3f5' },
                    dataLabels: {
                        enabled: true,
                        formatter: function(val, opt) {
                            let t = 0; opt.w.config.series.forEach(s => t += s.data[opt.dataPointIndex]);
                            return t === 0 ? "" : val + " (" + Math.round((val/t)*100) + "%)";
                        },
                        style: { colors: ['#212529'], fontSize: '12px' }
                    },
                    tooltip: { shared: true, intersect: false },
                    legend: { position: 'top', fontWeight: 'bold', offsetY: -10 }
                };

                if(this.chartInst.tatC) this.chartInst.tatC.destroy();
                this.chartInst.tatC = new ApexCharts(this.$refs.chartTatComp, opts);
                this.chartInst.tatC.render();
            },

            // =======================================================
            // RENDER BB
            // =======================================================
            renderBbTrend() {
                if(!this.$refs.chartBbTrend || !this.bb.data) return;
                const d = this.bb.data;
                const ds = this.bb.tabTrend === 'berat' ? d.trend.berat : d.trend.item;
                const isHeat = this.isMultiSatker && this.bb.adminType === 'heatmap';
                const tTitle = this.bb.tabTrend === 'berat' ? 'Total Berat BB (g)' : 'Total Item BB';
                const self = this;
                
                let opts = {
                    series: ds,
                    chart: { type: isHeat ? 'heatmap' : 'bar', height: 400, toolbar: { show: true }, fontFamily: 'inherit' },
                    xaxis: { categories: d.trend_labels },
                    title: { text: this.getTitle(this.bb, tTitle), align: 'center', margin: 20, style: { fontSize: '18px', fontWeight: '500', color: '#212529' } },
                    grid: { show: true, borderColor: '#f1f3f5' },
                    plotOptions: {
                        bar: { borderRadius: 4, columnWidth: this.isMultiSatker ? '85%' : '50%' },
                        heatmap: { shadeIntensity: 0.6, radius: 4, useFillColorAsStroke: false }
                    },
                    colors: isHeat ? ['#dc3545'] : (this.isMultiSatker ? this.getBarColors() : ['#dc3545']),
                    dataLabels: {
                        enabled: isHeat ? true : !this.isMultiSatker,
                        formatter: function(val) { return val > 0 ? new Intl.NumberFormat('id-ID').format(val) : ""; },
                        style: { colors: ['#212529'], fontSize: '13px' }
                    },
                    tooltip: { 
                        shared: true, intersect: false,
                        y: { formatter: function(val) { return self.bb.tabTrend === 'berat' ? self.formatGram(val) : new Intl.NumberFormat('id-ID').format(val); } }
                    },
                    legend: { show: !isHeat, position: 'top', fontWeight: 'bold', offsetY: -10 },
                    // PERBAIKAN BUG YAXIS HEATMAP "NaN"
                    yaxis: { labels: { formatter: function(val) { return typeof val === 'number' ? Math.round(val) : val; } } }
                };

                if(this.chartInst.bbT) this.chartInst.bbT.destroy();
                this.chartInst.bbT = new ApexCharts(this.$refs.chartBbTrend, opts);
                this.chartInst.bbT.render();
            },
            renderBbComp() {
                if(!this.$refs.chartBbComp || !this.bb.data) return;
                let opts = {
                    series: this.bb.data.comp.sumber,
                    chart: { type: 'bar', height: 400, stacked: true, toolbar: { show: false }, fontFamily: 'inherit' },
                    plotOptions: { bar: { borderRadius: 2, columnWidth: '40%' } },
                    xaxis: { categories: this.bb.data.comp_labels, labels: { style: { fontWeight: 'bold' } } },
                    colors: ['#dc3545', '#ffc107'],
                    title: { text: this.getTitle(this.bb, 'Proporsi Sumber Perolehan'), align: 'center', margin: 20, style: { fontSize: '18px', fontWeight: '500', color: '#212529' } },
                    grid: { show: true, borderColor: '#f1f3f5' },
                    dataLabels: {
                        enabled: true,
                        formatter: function(val, opt) {
                            let t = 0; opt.w.config.series.forEach(s => t += s.data[opt.dataPointIndex]);
                            return t === 0 ? "" : val + " (" + Math.round((val/t)*100) + "%)";
                        },
                        style: { colors: ['#212529'], fontSize: '12px' }
                    },
                    tooltip: { shared: true, intersect: false },
                    legend: { position: 'top', fontWeight: 'bold', offsetY: -10 }
                };

                if(this.chartInst.bbC) this.chartInst.bbC.destroy();
                this.chartInst.bbC = new ApexCharts(this.$refs.chartBbComp, opts);
                this.chartInst.bbC.render();
            },

            // =======================================================
            // RENDER RANKING (GRIDLINES MATI, AUTO HEIGHT)
            // =======================================================
            renderRank() {
                if(!this.$refs.chartRanking || !this.rank.data) return;
                const d = this.rank.data;
                const tTitle = `Top Narkotika ${this.rank.metric === 'berat' ? '(Gram)' : '(Frekuensi)'} - Sumber: ${this.rank.source.toUpperCase()}`;
                const dynHeight = Math.max(300, (d.labels.length * 40) + 100);
                const self = this;

                let opts = { 
                    series: [{ name: this.rank.metric === 'berat' ? 'Berat' : 'Frekuensi', data: d.data }], 
                    chart: { type: 'bar', height: dynHeight, toolbar: { show: false }, fontFamily: 'inherit' }, 
                    plotOptions: { bar: { horizontal: true, distributed: true, borderRadius: 4, barHeight: '70%' } }, 
                    xaxis: { categories: d.labels }, 
                    dataLabels: { 
                        enabled: true, 
                        formatter: function(val) { return self.rank.metric === 'berat' ? new Intl.NumberFormat('id-ID').format(val) : val; },
                        style: { colors: ['#333'], fontSize: '12px' } 
                    },
                    grid: { show: false, xaxis: { lines: { show: false } }, yaxis: { lines: { show: false } } }, 
                    title: { text: this.getTitle(this.rank, tTitle), align: 'left', margin: 20, style: { fontSize: '16px', fontWeight: '500' } },
                    tooltip: { 
                        y: { formatter: function(val) { return self.rank.metric === 'berat' ? self.formatGram(val) : new Intl.NumberFormat('id-ID').format(val); } } 
                    }
                };
                
                if (this.chartInst.rank) this.chartInst.rank.destroy(); 
                this.chartInst.rank = new ApexCharts(this.$refs.chartRanking, opts); 
                this.chartInst.rank.render();
            }
        }));
    });
</script>
@endpush