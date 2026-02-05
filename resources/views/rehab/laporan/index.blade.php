@extends('admin')

@section('content')
{{-- 
    x-data logic: 
    - showFilter: Mengontrol visibilitas form filter
    - showSummary: Mengontrol accordion ringkasan
    - expanded: Array ID untuk expand baris tabel harian
    - mode: 'list' (tabel target) atau 'form' (input/edit target)
    - form: Objek data untuk binding input form target
--}}
<main class="admin-main" x-data="{ 
    showFilter: true, 
    showSummary: true, 
    expanded: [],
    mode: 'list', 
    form: { 
        satker_id: '{{ auth()->user()->getSatkerId() }}', 
        bulan: '{{ date('n') }}', 
        tahun: '{{ date('Y') }}', 
        target_rj: '', 
        target_pasca: '', 
        target_skhpn: '' 
    },
    resetForm() {
        this.form = { 
            satker_id: '{{ auth()->user()->getSatkerId() }}', 
            bulan: '{{ date('n') }}', 
            tahun: '{{ date('Y') }}', 
            target_rj: '', 
            target_pasca: '', 
            target_skhpn: '' 
        };
        this.mode = 'form';
    },
    edit(satker, bln, thn, rj, pasca, skhpn) {
        this.form = { 
            satker_id: satker, 
            bulan: bln, 
            tahun: thn, 
            target_rj: rj, 
            target_pasca: pasca, 
            target_skhpn: skhpn 
        };
        this.mode = 'form';
    }
}">
    <div class="container-fluid p-4 p-lg-5">
        
        {{-- HEADER & TOMBOL AKSI --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Laporan Harian Rehabilitasi</h1>
                <p class="text-muted mb-0">Input Aktivitas Harian & Monitoring Kinerja</p>
            </div>
            
            <div class="d-flex gap-2">
                {{-- 1. TOMBOL UNIFIED: KELOLA TARGET --}}
                @if(auth()->user()->hasRole(['operator_satker', 'operator_rehab', 'admin']))
                    <button type="button" 
                            class="btn btn-outline-primary shadow-sm d-flex align-items-center gap-2" 
                            data-bs-toggle="modal" 
                            data-bs-target="#manageTargetModal"
                            @click="mode = 'list'">
                        <i class="bi bi-bullseye"></i> 
                        <span>Kelola Target</span>
                    </button>
                @endif

                {{-- 2. TOMBOL INPUT HARIAN --}}
                @if(auth()->user()->hasRole(['operator_satker', 'operator_rehab']))
                    <a href="{{ route('rehab.laporan.create') }}" 
                       class="btn btn-primary d-flex align-items-center gap-2 shadow-sm">
                        <i class="bi bi-plus-lg"></i> 
                        <span>Input Harian</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- ALERT NOTIFIKASI --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <div><strong>Berhasil!</strong> {{ session('success') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><strong>Gagal!</strong> {{ session('error') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- WIDGET REKAPITULASI --}}
        <div class="card mb-4 shadow-sm border-0 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom clickable" 
                 @click="showSummary = !showSummary" 
                 style="cursor: pointer;">
                <div class="d-flex justify-content-between align-items-center text-primary">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-graph-up-arrow me-2"></i> Ringkasan Kinerja (Berdasarkan Filter)
                    </h6>
                    <i class="bi" :class="showSummary ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                </div>
            </div>
            
            <div x-show="showSummary" x-transition.duration.300ms>
                <div class="card-body bg-light">
                    <div class="table-responsive bg-white rounded shadow-sm border">
                        <table class="table table-bordered table-sm mb-0 text-center align-middle" style="font-size: 0.9rem;">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th rowspan="2" class="align-middle">Periode</th>
                                    <th colspan="3" class="bg-warning text-dark">Rawat Jalan</th>
                                    <th colspan="3" class="bg-success text-white">Pasca Rehab</th>
                                    <th colspan="3" class="bg-info text-dark">SKHPN</th>
                                </tr>
                                <tr>
                                    <th>Target</th><th>Real</th><th>%</th>
                                    <th>Target</th><th>Real</th><th>%</th>
                                    <th>Target</th><th>Real</th><th>%</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($summary as $sum)
                                    @php
                                        $pR = $sum['target_rj'] > 0 ? ($sum['real_rj']/$sum['target_rj'])*100 : 0;
                                        $pP = $sum['target_pasca'] > 0 ? ($sum['real_pasca']/$sum['target_pasca'])*100 : 0;
                                        $pS = $sum['target_skhpn'] > 0 ? ($sum['real_skhpn']/$sum['target_skhpn'])*100 : 0;
                                    @endphp
                                    <tr>
                                        <td class="fw-bold">{{ $sum['periode'] }}</td>
                                        <td>{{ number_format($sum['target_rj']) }}</td>
                                        <td class="fw-bold">{{ number_format($sum['real_rj']) }}</td>
                                        <td class="{{ $pR >= 100 ? 'text-success fw-bold' : '' }}">{{ round($pR, 1) }}%</td>
                                        
                                        <td>{{ number_format($sum['target_pasca']) }}</td>
                                        <td class="fw-bold">{{ number_format($sum['real_pasca']) }}</td>
                                        <td class="{{ $pP >= 100 ? 'text-success fw-bold' : '' }}">{{ round($pP, 1) }}%</td>
                                        
                                        <td>{{ number_format($sum['target_skhpn']) }}</td>
                                        <td class="fw-bold">{{ number_format($sum['real_skhpn']) }}</td>
                                        <td class="{{ $pS >= 100 ? 'text-success fw-bold' : '' }}">{{ round($pS, 1) }}%</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-muted fst-italic py-3">
                                            Data tidak tersedia. Silakan atur filter atau input target.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD UTAMA: FILTER & LIST DATA --}}
        @php
            $filterCount = collect(request()->only(['bulan', 'tahun', 'satuan_kerja_id']))->filter()->count();
            if (!request()->has('tahun')) $filterCount += 1; 
            
            $sortLink = function($col, $label) {
                $currentCol = request('sort_by', 'tanggal'); 
                $currentOrder = request('sort_order', 'desc');
                $newOrder = ($currentCol === $col && $currentOrder === 'desc') ? 'asc' : 'desc';
                $icon = 'bi-arrow-down-up text-muted opacity-25';
                if ($currentCol === $col) { 
                    $icon = $currentOrder === 'desc' ? 'bi-sort-down text-primary' : 'bi-sort-up text-primary'; 
                }
                $url = request()->fullUrlWithQuery(['sort_by' => $col, 'sort_order' => $newOrder]);
                return '<a href="'.$url.'" class="text-decoration-none text-secondary fw-bold d-flex align-items-center justify-content-between gap-2">'.$label.' <i class="bi '.$icon.'"></i></a>';
            };

            $user = Auth::user();
            $userSatker = ($user->pegawai && $user->pegawai->satuanKerja) ? $user->pegawai->satuanKerja : null;
            $isSuperAdmin = $user->hasRole('admin') && !$userSatker;
            $isBnnpSultra = ($userSatker && strtoupper(trim($userSatker->satuan_kerja)) === 'BNNP SULTRA');
            $canExport = $isSuperAdmin || $isBnnpSultra || $user->hasRole(['operator_satker', 'operator_rehab']);
        @endphp

        <div class="row justify-content-center mb-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    
                    {{-- HEADER FILTER --}}
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-2">
                            <h5 class="card-title mb-0 fw-bold text-secondary">
                                <i class="bi bi-table me-2"></i>Data Laporan Harian
                            </h5>
                            <button type="button" @click="showFilter = !showFilter" 
                                    class="btn btn-sm transition-all d-flex align-items-center gap-2"
                                    :class="showFilter ? 'btn-light text-secondary border' : 'btn-primary shadow-sm'">
                                <i class="bi" :class="showFilter ? 'bi-chevron-up' : 'bi-funnel'"></i> 
                                <span x-text="showFilter ? 'Sembunyikan Filter' : 'Filter Pencarian'"></span>
                                @if($filterCount > 0) 
                                    <span class="badge bg-danger rounded-pill">{{ $filterCount }}</span> 
                                @endif
                            </button>
                        </div>
                    </div>

                    <div class="card-body p-0 p-lg-4">
                        <form action="{{ route('rehab.laporan.index') }}" method="GET">
                            <input type="hidden" name="sort_by" value="{{ request('sort_by', 'tanggal') }}">
                            <input type="hidden" name="sort_order" value="{{ request('sort_order', 'desc') }}">

                            {{-- FORM FILTER --}}
                            {{-- PERBAIKAN Z-INDEX: Turunkan ke 1040 agar DI BAWAH SweetAlert/Modal (biasanya 1060+) --}}
                            <div x-show="showFilter" x-transition class="mb-4 px-3 px-lg-0 pt-3 pt-lg-0" style="position: relative; z-index: 1040;">
                                <div class="bg-body-tertiary p-4 rounded-3 border">
                                    <div class="row g-3 text-start">
                                        {{-- Filter Satker (Admin Only) --}}
                                        @if(auth()->user()->hasRole('admin'))
                                            <div class="col-lg-6">
                                                <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Satuan Kerja</label>
                                                <div class="shadow-sm bg-white rounded">
                                                    <select id="select-satker" name="satuan_kerja_id[]" multiple placeholder="Pilih..." autocomplete="off">
                                                        @foreach($satuanKerjas as $s) 
                                                            <option value="{{ $s->id }}" {{ in_array($s->id, request('satuan_kerja_id', [])) ? 'selected' : '' }}>
                                                                {{ $s->satuan_kerja }}
                                                            </option> 
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Filter Bulan --}}
                                        <div class="col-6 {{ auth()->user()->hasRole('admin') ? 'col-lg-3' : 'col-lg-6' }}">
                                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Bulan</label>
                                            <div class="shadow-sm bg-white rounded">
                                                <select id="select-bulan" name="bulan[]" multiple placeholder="Pilih..." autocomplete="off">
                                                    @foreach(range(1,12) as $m) 
                                                        <option value="{{ $m }}" {{ in_array($m, request('bulan', [])) ? 'selected' : '' }}>
                                                            {{ \Carbon\Carbon::create()->month($m)->locale('id')->translatedFormat('F') }}
                                                        </option> 
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Filter Tahun --}}
                                        <div class="col-6 {{ auth()->user()->hasRole('admin') ? 'col-lg-3' : 'col-lg-6' }}">
                                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Tahun</label>
                                            <div class="shadow-sm bg-white rounded">
                                                <select id="select-tahun" name="tahun[]" multiple placeholder="Pilih..." autocomplete="off">
                                                    @foreach($years as $y) 
                                                        <option value="{{ $y }}" {{ in_array($y, request('tahun', request()->has('tahun') ? [] : [date('Y')])) ? 'selected' : '' }}>
                                                            {{ $y }}
                                                        </option> 
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Tombol Terapkan --}}
                                        <div class="col-12 text-end pt-3 border-top mt-4 text-start">
                                            <a href="{{ route('rehab.laporan.index') }}" class="btn btn-link text-decoration-none text-muted btn-sm me-2">Reset</a>
                                            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                                <i class="bi bi-funnel-fill me-1"></i> Terapkan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- EXPORT BUTTONS --}}
                            {{-- PERBAIKAN Z-INDEX: Turunkan ke 1030 (di bawah filter) --}}
                            <div class="d-flex justify-content-between align-items-center mb-3 px-3 px-lg-0" style="position: relative; z-index: 1030;">
                                <div>
                                    @if($canExport)
                                        <div class="dropdown">
                                            <button class="btn btn-success btn-sm dropdown-toggle shadow-sm d-flex align-items-center gap-2" 
                                                    type="button" 
                                                    data-bs-toggle="dropdown" 
                                                    aria-expanded="false">
                                                <i class="bi bi-file-earmark-excel"></i> Export Excel
                                            </button>
                                            <ul class="dropdown-menu shadow-sm border-0">
                                                <li><h6 class="dropdown-header small text-uppercase fw-bold text-muted">Pilih Kategori</h6></li>
                                                <li>
                                                    <a class="dropdown-item py-2" href="{{ route('rehab.laporan.export', array_merge(request()->query(), ['kategori' => 'rawat_jalan'])) }}">
                                                        <i class="bi bi-bandaid me-2 text-warning"></i> Rawat Jalan
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item py-2" href="{{ route('rehab.laporan.export', array_merge(request()->query(), ['kategori' => 'pasca_rehab'])) }}">
                                                        <i class="bi bi-heart-pulse me-2 text-success"></i> Pasca Rehabilitasi
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item py-2" href="{{ route('rehab.laporan.export', array_merge(request()->query(), ['kategori' => 'skhpn'])) }}">
                                                        <i class="bi bi-file-medical me-2 text-info"></i> SKHPN
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <div class="text-muted small fst-italic">Total Data: <strong>{{ $data->total() }}</strong></div>
                                </div>
                            </div>
                        </form>
                        
                        {{-- TABEL DATA HARIAN --}}
                        <div class="custom-table-scroll mb-3" id="data-table">
                            <table class="table table-hover align-middle mb-0 text-center" x-data="{ expanded: [] }">
                                <thead class="bg-light sticky-top">
                                    <tr class="text-center align-middle small text-uppercase text-secondary text-nowrap">
                                        <th class="py-3 bg-light ps-3">No</th>
                                        <th class="py-3 bg-light">{!! $sortLink('tanggal', 'Tanggal') !!}</th>
                                        <th class="py-3 bg-light text-start">{!! $sortLink('satuan_kerja_id', 'Satuan Kerja') !!}</th>
                                        <th class="py-3 bg-light text-warning">RAWAT JALAN</th>
                                        <th class="py-3 bg-light text-success">PASCA REHAB</th>
                                        <th class="py-3 bg-light text-info">SKHPN</th>
                                        <th class="py-3 bg-light pe-3">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="border-top-0">
                                    @forelse($data as $key => $row)
                                    <tr :class="expanded.includes({{ $row->id }}) ? 'bg-light' : ''">
                                        <td class="text-secondary fw-bold ps-3">{{ $data->firstItem() + $key }}</td>
                                        
                                        {{-- Tanggal --}}
                                        <td>
                                            <span class="badge bg-light text-dark border border-secondary-subtle px-2 py-1 shadow-sm">
                                                {{ $row->tanggal_text }}
                                            </span>
                                        </td>
                                        
                                        {{-- Satker --}}
                                        <td class="text-start fw-bold text-dark">{{ $row->satuanKerja->satuan_kerja ?? '-' }}</td>
                                        
                                        {{-- Angka Realisasi --}}
                                        <td class="fw-bold text-dark">{{ number_format($row->realisasi_rawat_jalan) }}</td>
                                        <td class="fw-bold text-dark">{{ number_format($row->realisasi_pasca_rehab) }}</td>
                                        <td class="fw-bold text-dark">{{ number_format($row->realisasi_skhpn) }}</td>
                                        
                                        {{-- Aksi --}}
                                        <td class="pe-3">
                                            <div class="btn-group btn-group-sm shadow-sm">
                                                {{-- Tombol Mata (Toggle Detail Expand) --}}
                                                <button type="button" class="btn btn-light border text-secondary" 
                                                        @click="expanded.includes({{ $row->id }}) ? expanded = expanded.filter(id => id !== {{ $row->id }}) : expanded.push({{ $row->id }})">
                                                    <i class="bi" :class="expanded.includes({{ $row->id }}) ? 'bi-chevron-up text-primary' : 'bi-eye text-secondary'"></i>
                                                </button>
                                                
                                                {{-- Tombol Edit/Hapus (Operator Only) --}}
                                                @if(auth()->user()->hasRole(['operator_satker', 'operator_rehab']))
                                                    <a href="{{ route('rehab.laporan.edit', $row->id) }}" 
                                                       class="btn btn-light border text-primary" 
                                                       title="Edit">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-light border text-danger" 
                                                            onclick="confirmDelete({{ $row->id }})" 
                                                            title="Hapus">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                    <form id="delete-form-{{ $row->id }}" 
                                                          action="{{ route('rehab.laporan.destroy', $row->id) }}" 
                                                          method="POST" 
                                                          class="d-none">
                                                        @csrf @method('DELETE')
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>

                                    {{-- BARIS DETAIL EXPAND (LAMPIRAN) --}}
                                    <tr x-show="expanded.includes({{ $row->id }})" x-transition>
                                        <td colspan="7" class="p-0 border-0">
                                            <div class="bg-body-tertiary p-3 border-bottom shadow-inner text-start">
                                                <h6 class="small fw-bold text-secondary mb-2 text-uppercase">
                                                    <i class="bi bi-paperclip me-1"></i> Dokumentasi & Lampiran ({{ $row->dokumentasi->count() }} File)
                                                </h6>
                                                
                                                @if($row->dokumentasi->count() > 0)
                                                    <div class="row g-2">
                                                        @foreach($row->dokumentasi as $doc)
                                                            <div class="col-12 col-md-6 col-lg-4">
                                                                <div class="p-2 border rounded bg-white d-flex justify-content-between align-items-center shadow-sm">
                                                                    {{-- Icon & Nama File --}}
                                                                    <div class="text-truncate small me-2">
                                                                        @if(Str::contains($doc->tipe_file, 'image')) 
                                                                            <i class="bi bi-file-image text-primary me-1"></i>
                                                                        @elseif(Str::contains($doc->tipe_file, 'pdf')) 
                                                                            <i class="bi bi-file-pdf text-danger me-1"></i>
                                                                        @else 
                                                                            <i class="bi bi-file-earmark-text text-secondary me-1"></i> 
                                                                        @endif
                                                                        {{ $doc->nama_file_asli }}
                                                                    </div>
                                                                    
                                                                    {{-- Tombol Download/Preview --}}
                                                                    <div class="d-flex gap-1">
                                                                        @if(Str::contains($doc->tipe_file, ['image', 'pdf']))
                                                                            <a href="{{ Storage::url($doc->path_file) }}" 
                                                                               target="_blank" 
                                                                               class="btn btn-xs btn-outline-info px-2 py-0">
                                                                                <i class="bi bi-eye"></i>
                                                                            </a>
                                                                        @endif
                                                                        <a href="{{ route('dokumentasi.download', $doc->id) }}" 
                                                                           class="btn btn-xs btn-outline-primary px-2 py-0">
                                                                            <i class="bi bi-download"></i>
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="text-muted small fst-italic border rounded p-2 bg-white text-center">
                                                        Tidak ada dokumentasi yang dilampirkan.
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="py-5 text-muted fst-italic">Belum ada laporan harian.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        {{-- FOOTER PAGINATION --}}
                        <div class="card-footer bg-white py-3 border-top-0">
                            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-3">
                                <div class="d-flex align-items-center gap-2">
                                    <select class="form-select form-select-sm border-secondary-subtle" 
                                            style="width: 70px;" 
                                            onchange="window.location.href = this.value">
                                        @foreach([10, 25, 50, 100] as $num) 
                                            <option value="{{ request()->fullUrlWithQuery(['per_page' => $num, 'page' => 1]) }}" 
                                                    {{ request('per_page') == $num ? 'selected' : '' }}>
                                                {{ $num }}
                                            </option> 
                                        @endforeach
                                    </select>
                                    <span class="text-muted small">Data / halaman</span>
                                </div>
                                <div>{{ $data->withQueryString()->links() }}</div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL UNIFIED (KELOLA TARGET) --}}
    <div class="modal fade" id="manageTargetModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                
                {{-- HEADER MODAL --}}
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi" :class="mode === 'list' ? 'bi-list-ul' : 'bi-pencil-square'"></i> 
                        <span x-text="mode === 'list' ? 'Daftar Target Bulanan' : 'Form Target Bulanan'"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                {{-- BODY: MODE LIST (TABEL) --}}
                <div class="modal-body p-4" x-show="mode === 'list'">
                    
                    {{-- Header List (Tombol Tambah) --}}
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-bold text-dark mb-1">Target Tersimpan</h6>
                            <p class="text-muted small mb-0">Daftar target kinerja yang telah diinput.</p>
                        </div>
                        <button class="btn btn-sm btn-success fw-bold shadow-sm px-3" @click="mode = 'form'; resetForm()">
                            <i class="bi bi-plus-lg me-1"></i> Buat Target Baru
                        </button>
                    </div>

                    {{-- Tabel dalam Card --}}
                    <div class="card border shadow-sm">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-hover mb-0 text-center align-middle small">
                                <thead class="bg-light sticky-top border-bottom">
                                    <tr>
                                        @if(auth()->user()->isAdmin()) <th class="py-3">Satker</th> @endif
                                        <th class="py-3">Periode</th>
                                        <th class="py-3">Rawat Jalan</th>
                                        <th class="py-3">Pasca Rehab</th>
                                        <th class="py-3">SKHPN</th>
                                        <th class="py-3" width="15%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($allTargets as $t)
                                        <tr>
                                            @if(auth()->user()->isAdmin()) <td class="text-start">{{ $t->satuanKerja->satuan_kerja ?? '-' }}</td> @endif
                                            <td class="fw-bold text-primary">{{ \Carbon\Carbon::create()->month($t->bulan)->locale('id')->translatedFormat('F') }} {{ $t->tahun }}</td>
                                            <td>{{ number_format($t->target_rawat_jalan) }}</td>
                                            <td>{{ number_format($t->target_pasca_rehab) }}</td>
                                            <td>{{ number_format($t->target_skhpn) }}</td>
                                            <td>
                                                <div class="d-flex gap-1 justify-content-center">
                                                    {{-- Tombol Edit --}}
                                                    <button class="btn btn-xs btn-outline-primary px-2" 
                                                            @click="edit('{{ $t->satuan_kerja_id }}', '{{ $t->bulan }}', '{{ $t->tahun }}', '{{ $t->target_rawat_jalan }}', '{{ $t->target_pasca_rehab }}', '{{ $t->target_skhpn }}')" 
                                                            title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    
                                                    {{-- Tombol Hapus (Restrict) --}}
                                                    @if($t->has_laporan)
                                                        <button class="btn btn-xs btn-outline-secondary px-2" disabled title="Tidak bisa dihapus karena sudah ada data harian">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    @else
                                                        <button class="btn btn-xs btn-outline-danger px-2" onclick="confirmDeleteTarget({{ $t->id }})" title="Hapus">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                        <form id="delete-target-form-{{ $t->id }}" action="{{ route('rehab.laporan.destroy_target', $t->id) }}" method="POST" class="d-none">@csrf @method('DELETE')</form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-muted fst-italic py-5">Belum ada target yang diset. Silakan klik tombol <strong>Buat Target Baru</strong>.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- BODY: MODE FORM (INPUT/EDIT) --}}
                <div class="modal-body p-4" x-show="mode === 'form'">
                    <form action="{{ route('rehab.laporan.store_target') }}" method="POST">
                        @csrf
                        
                        <div class="alert alert-info small mb-4 border-0 bg-info bg-opacity-10 d-flex align-items-center">
                            <i class="bi bi-info-circle-fill me-2 fs-5 text-info"></i>
                            <div>Data akan otomatis di-update jika Periode (Bulan/Tahun) sama dengan data yang sudah ada.</div>
                        </div>

                        @if(auth()->user()->isAdmin())
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Satuan Kerja</label>
                            <select name="satuan_kerja_id" class="form-select py-2" x-model="form.satker_id" required>
                                @foreach($satuanKerjas as $s) <option value="{{ $s->id }}">{{ $s->satuan_kerja }}</option> @endforeach
                            </select>
                        </div>
                        @endif

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold small text-secondary">Bulan</label>
                                <select name="bulan" class="form-select py-2" x-model="form.bulan" required>
                                    @foreach(range(1,12) as $m) <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->locale('id')->translatedFormat('F') }}</option> @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold small text-secondary">Tahun</label>
                                <select name="tahun" class="form-select py-2" x-model="form.tahun" required>
                                    @foreach($years as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-warning">Target Rawat Jalan</label>
                                <input type="number" name="target_rawat_jalan" class="form-control py-2 fw-bold" x-model="form.target_rj" required min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-success">Target Pasca Rehab</label>
                                <input type="number" name="target_pasca_rehab" class="form-control py-2 fw-bold" x-model="form.target_pasca" required min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-info">Target SKHPN</label>
                                <input type="number" name="target_skhpn" class="form-control py-2 fw-bold" x-model="form.target_skhpn" required min="0">
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light border px-4" @click="mode = 'list'">Batal</button>
                            <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm"><i class="bi bi-save me-1"></i> Simpan Target</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

</main>
@endsection

@push('styles')
<style>
    /* CSS FIX Z-INDEX DROPDOWN */
    .ts-dropdown, .ts-dropdown.single { z-index: 9999 !important; }
    
    /* CSS FIX STACKING CONTEXT TABEL */
    .custom-table-scroll thead th { z-index: 1 !important; background-color: #f8f9fa !important; box-shadow: inset 0 -1px 0 #dee2e6; }
    
    .ts-control { border: none !important; box-shadow: none !important; padding-top: 0.5rem; padding-bottom: 0.5rem; background-color: transparent !important; min-height: 40px; }
    .custom-table-scroll { max-height: 70vh; overflow-y: auto; position: relative; border: 1px solid #dee2e6; border-radius: 6px; }
    .custom-table-scroll thead th { position: sticky !important; top: 0 !important; }
    .btn-xs { padding: 1px 5px; font-size: 0.75rem; }
    .page-link { border: none; color: #6c757d; border-radius: 50% !important; margin: 0 2px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; }
    .page-item.active .page-link { background-color: #0d6efd; color: white; box-shadow: 0 2px 4px rgba(13,110,253,0.3); }
    [x-cloak] { display: none !important; }
</style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        const configTomSelect = { plugins: ['remove_button', 'clear_button'], persist: false, create: false, maxOptions: null, placeholder: 'Pilih...' };
        ['select-bulan', 'select-tahun'].forEach(id => { 
            if(document.getElementById(id)) new TomSelect('#' + id, configTomSelect); 
        });
        if(document.getElementById('select-satker')) { 
            new TomSelect('#select-satker', configTomSelect); 
        }
    });

    // Script Delete Harian
    window.confirmDelete = function(id) {
        Swal.fire({
            title: 'Hapus Data?', text: "Data ini akan dihapus permanen.", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal', reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) { document.getElementById('delete-form-' + id).submit(); }
        });
    }

    // Script Delete Target (SweetAlert)
    window.confirmDeleteTarget = function(id) {
        Swal.fire({
            title: 'Hapus Target?', text: "Data target ini akan dihapus permanen.", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal', reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) { document.getElementById('delete-target-form-' + id).submit(); }
        });
    }
</script>
@endpush