@extends('admin')

@section('content')

{{-- ==================================================================== --}}
{{-- LOGIKA PHP VIEW HELPER --}}
{{-- ==================================================================== --}}
@php
    // 1. Logika Badge Filter
    $activeFilters = 0;
    if (request()->filled('satuan_kerja_id')) $activeFilters++;
    if (request()->filled('bulan')) $activeFilters++;
    if (request()->filled('tahun')) $activeFilters++; 

    // 2. Helper Sorting Link
    $sortLink = function($col, $label) {
        $currCol = request('sort_by', 'tanggal');
        $currOrd = request('sort_order', 'desc');
        $newOrd = ($currCol === $col && $currOrd === 'desc') ? 'asc' : 'desc';
        
        $icon = 'bi-arrow-down-up text-muted opacity-25'; 
        if ($currCol === $col) {
            $icon = $currOrd === 'desc' ? 'bi-sort-down text-primary' : 'bi-sort-up text-primary';
        }
        
        $url = request()->fullUrlWithQuery(['sort_by' => $col, 'sort_order' => $newOrd]);
        return '<a href="'.$url.'" class="text-decoration-none text-secondary fw-bold d-flex align-items-center justify-content-center gap-1">'.$label.' <i class="bi '.$icon.'"></i></a>';
    };

    // 3. Logic Tahun Modal (Tahun Depan Paling Atas)
    $currYear = date('Y');
    $modalYears = [];
    for($i = $currYear + 1; $i >= $currYear - 4; $i--) {
        $modalYears[] = $i;
    }
@endphp

{{-- ROOT ALPINE DATA --}}
<main class="admin-main" x-data="rehabPage">
    <div class="container-fluid p-4 p-lg-5">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Laporan Rehabilitasi</h1>
                <p class="text-muted mb-0">Data harian layanan Rawat Jalan, Pasca Rehab, dan SKHPN</p>
            </div>

            <div class="d-flex gap-2">
                @if(auth()->user()->hasRole(['admin', 'admin_satker', 'operator_satker', 'operator_rehab']))
                {{-- TOMBOL BUKA MODAL (Via Alpine Function) --}}
                <button type="button" 
                        class="btn btn-outline-secondary d-flex align-items-center gap-2 shadow-sm" 
                        @click="openTargetModal">
                    <i class="bi bi-bullseye"></i> <span class="d-none d-md-inline">Target Tahunan</span>
                </button>
                @endif

                @if(auth()->user()->hasRole(['operator_satker', 'operator_rehab', 'admin', 'admin_satker']))
                <a href="{{ route('rehab.laporan.create') }}" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm">
                    <i class="bi bi-plus-lg"></i> <span>Input Laporan</span>
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

        {{-- STATISTIK CARDS --}}
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100" style="border-left: 5px solid #0dcaf0 !important;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div><h6 class="text-uppercase text-muted fw-bold small mb-1">Rawat Jalan ({{ $breakdownYear }})</h6><h2 class="mb-0 fw-bold text-dark">{{ number_format($stats['rj']['realisasi']) }}</h2></div>
                            <span class="badge bg-info bg-opacity-10 text-info fs-6">{{ number_format($stats['rj']['persen'], 1) }}%</span>
                        </div>
                        <div class="progress" style="height: 6px;"><div class="progress-bar bg-info" style="width: {{ $stats['rj']['persen'] }}%"></div></div>
                        <div class="d-flex justify-content-between small text-muted mt-2"><span>Target: <strong>{{ number_format($stats['rj']['target']) }}</strong></span><span>Sisa: <strong>{{ number_format($stats['rj']['sisa']) }}</strong></span></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100" style="border-left: 5px solid #198754 !important;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div><h6 class="text-uppercase text-muted fw-bold small mb-1">Pasca Rehab ({{ $breakdownYear }})</h6><h2 class="mb-0 fw-bold text-dark">{{ number_format($stats['pasca']['realisasi']) }}</h2></div>
                            <span class="badge bg-success bg-opacity-10 text-success fs-6">{{ number_format($stats['pasca']['persen'], 1) }}%</span>
                        </div>
                        <div class="progress" style="height: 6px;"><div class="progress-bar bg-success" style="width: {{ $stats['pasca']['persen'] }}%"></div></div>
                        <div class="d-flex justify-content-between small text-muted mt-2"><span>Target: <strong>{{ number_format($stats['pasca']['target']) }}</strong></span><span>Sisa: <strong>{{ number_format($stats['pasca']['sisa']) }}</strong></span></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100" style="border-left: 5px solid #ffc107 !important;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div><h6 class="text-uppercase text-muted fw-bold small mb-1">SKHPN ({{ $breakdownYear }})</h6><h2 class="mb-0 fw-bold text-dark">{{ number_format($stats['skhpn']['realisasi']) }}</h2></div>
                            <span class="badge bg-warning bg-opacity-10 text-warning-emphasis fs-6">{{ number_format($stats['skhpn']['persen'], 1) }}%</span>
                        </div>
                        <div class="progress" style="height: 6px;"><div class="progress-bar bg-warning" style="width: {{ $stats['skhpn']['persen'] }}%"></div></div>
                        <div class="d-flex justify-content-between small text-muted mt-2"><span>Target: <strong>{{ number_format($stats['skhpn']['target']) }}</strong></span><span>Sisa: <strong>{{ number_format($stats['skhpn']['sisa']) }}</strong></span></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ACCORDION RINCIAN --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <button class="btn btn-link text-decoration-none w-100 d-flex align-items-center justify-content-between text-dark fw-bold px-0 shadow-none" 
                            type="button" @click="showBreakdown = !showBreakdown">
                        <span class="text-primary"><i class="bi bi-bar-chart-steps me-2"></i>Rincian Capaian Bulanan</span>
                        <i class="bi transition-transform" :class="showBreakdown ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                    </button>
                    <div class="ms-3" @click.stop>
                         <select class="form-select form-select-sm border-primary text-primary fw-bold" style="width: auto;" onchange="updateBreakdownYear(this.value)">
                            @foreach($allYears as $y) <option value="{{ $y }}" {{ $y == $breakdownYear ? 'selected' : '' }}>{{ $y }}</option> @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div x-show="showBreakdown" x-collapse>
                <div class="card-body p-0 border-top">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover mb-0 text-center small align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th rowspan="2" class="align-middle bg-white sticky-start">Bulan</th>
                                    <th colspan="4" class="bg-info bg-opacity-10 text-info-emphasis">Rawat Jalan</th>
                                    <th colspan="4" class="bg-success bg-opacity-10 text-success">Pasca Rehab</th>
                                    <th colspan="4" class="bg-warning bg-opacity-10 text-warning-emphasis">SKHPN</th>
                                </tr>
                                <tr class="text-secondary" style="font-size: 0.75rem;">
                                    <th>Real</th><th>Akum</th><th>Sisa</th><th>%</th><th>Real</th><th>Akum</th><th>Sisa</th><th>%</th><th>Real</th><th>Akum</th><th>Sisa</th><th>%</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($monthsData as $row)
                                <tr>
                                    <td class="text-start fw-bold sticky-start bg-white">{{ $row['bulan_nama'] }}</td>
                                    <td>{{ number_format($row['rj']['real']) }}</td><td>{{ number_format($row['rj']['akum']) }}</td><td class="text-muted">{{ number_format($row['rj']['sisa']) }}</td><td>{{ number_format($row['rj']['persen'], 1) }}%</td>
                                    <td>{{ number_format($row['pasca']['real']) }}</td><td>{{ number_format($row['pasca']['akum']) }}</td><td class="text-muted">{{ number_format($row['pasca']['sisa']) }}</td><td>{{ number_format($row['pasca']['persen'], 1) }}%</td>
                                    <td>{{ number_format($row['skhpn']['real']) }}</td><td>{{ number_format($row['skhpn']['akum']) }}</td><td class="text-muted">{{ number_format($row['skhpn']['sisa']) }}</td><td>{{ number_format($row['skhpn']['persen'], 1) }}%</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- FILTER & TABEL UTAMA --}}
        <div class="card border-0 shadow-sm" x-data="{ showFilter: true }">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-2">
                    <h5 class="card-title mb-0 fw-bold text-secondary"><i class="bi bi-table me-2"></i>Data Laporan Harian</h5>
                    <button type="button" @click="showFilter = !showFilter" class="btn btn-sm transition-all d-flex align-items-center gap-2" :class="showFilter ? 'btn-light text-secondary border' : 'btn-primary shadow-sm'">
                        <i class="bi" :class="showFilter ? 'bi-chevron-up' : 'bi-funnel'"></i> 
                        <span x-text="showFilter ? 'Sembunyikan Filter' : 'Filter Data'"></span>
                        @if($activeFilters > 0) <span class="badge bg-danger rounded-pill">{{ $activeFilters }}</span> @endif
                    </button>
                </div>
            </div>

            <div class="card-body p-0 p-lg-4">
                {{-- Form Filter --}}
                <div x-show="showFilter" x-transition class="mb-4">
                    <form action="{{ route('rehab.laporan.index') }}" method="GET" class="bg-light p-4 rounded border" id="form-filter">
                        <input type="hidden" name="breakdown_year" value="{{ $breakdownYear }}">
                        <div class="row g-3">
                            @if(auth()->user()->isAdmin())
                                <div class="col-lg-4">
                                    <label class="form-label fw-bold small text-secondary">Satuan Kerja</label>
                                    <div class="bg-white rounded shadow-sm"><select id="select-satker" name="satuan_kerja_id[]" multiple placeholder="Pilih Satuan Kerja...">@foreach($satuanKerjas as $sk) <option value="{{ $sk->id }}" {{ in_array($sk->id, request('satuan_kerja_id', [])) ? 'selected' : '' }}>{{ $sk->satuan_kerja }}</option> @endforeach</select></div>
                                </div>
                            @endif
                            <div class="{{ auth()->user()->isAdmin() ? 'col-lg-4' : 'col-md-6' }}">
                                <label class="form-label fw-bold small text-secondary">Tahun (Tabel)</label>
                                <div class="bg-white rounded shadow-sm"><select id="select-tahun" name="tahun[]" multiple placeholder="Pilih Tahun...">@foreach($allYears as $y) <option value="{{ $y }}" {{ in_array($y, request('tahun', [])) ? 'selected' : '' }}>{{ $y }}</option> @endforeach</select></div>
                            </div>
                            <div class="{{ auth()->user()->isAdmin() ? 'col-lg-4' : 'col-md-6' }}">
                                <label class="form-label fw-bold small text-secondary">Bulan</label>
                                <div class="bg-white rounded shadow-sm"><select id="select-bulan" name="bulan[]" multiple placeholder="Pilih Bulan...">@foreach(range(1,12) as $m) <option value="{{ $m }}" {{ in_array($m, request('bulan', [])) ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->locale('id')->translatedFormat('F') }}</option> @endforeach</select></div>
                            </div>
                            <div class="col-12 d-flex justify-content-end align-items-center pt-2 gap-2">
                                <a href="{{ route('rehab.laporan.index') }}" class="btn btn-link btn-sm text-decoration-none text-muted">Reset</a>
                                <button type="submit" class="btn btn-primary px-4 shadow-sm"><i class="bi bi-search me-1"></i> Terapkan</button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Action Bar & Export --}}
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="small text-muted">Total Data: <strong>{{ $data->total() }}</strong></div>
                    <div class="btn-group position-relative" x-data="{ open: false }">
                        <button type="button" @click="open = !open" @click.outside="open = false" class="btn btn-success btn-sm text-white dropdown-toggle shadow-sm">
                            <i class="bi bi-file-excel"></i> Export Excel
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" x-show="open" style="display: none; position: absolute; right: 0; top: 100%; z-index: 1050; margin-top: 5px; min-width: 180px;" x-transition.opacity>
                            <li><button type="submit" form="form-filter" formaction="{{ route('rehab.laporan.export', ['kategori' => 'rawat_jalan']) }}" class="dropdown-item py-2">Laporan Rawat Jalan</button></li>
                            <li><button type="submit" form="form-filter" formaction="{{ route('rehab.laporan.export', ['kategori' => 'pasca_rehab']) }}" class="dropdown-item py-2">Laporan Pasca Rehab</button></li>
                            <li><button type="submit" form="form-filter" formaction="{{ route('rehab.laporan.export', ['kategori' => 'skhpn']) }}" class="dropdown-item py-2">Laporan SKHPN</button></li>
                        </ul>
                    </div>
                </div>

                {{-- Tabel Data --}}
                <div class="custom-table-scroll mb-3">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light sticky-top">
                            <tr class="text-center small text-uppercase text-secondary">
                                <th class="py-3 ps-3">No</th>
                                <th class="py-3 text-nowrap">{!! $sortLink('tanggal', 'Tanggal') !!}</th>
                                <th class="py-3 text-start">{!! $sortLink('satuan_kerja_id', 'Satuan Kerja') !!}</th>
                                <th class="py-3 text-info">{!! $sortLink('realisasi_rawat_jalan', 'RJ') !!}</th>
                                <th class="py-3 text-success">{!! $sortLink('realisasi_pasca_rehab', 'Pasca') !!}</th>
                                <th class="py-3 text-warning-emphasis">{!! $sortLink('realisasi_skhpn', 'SKHPN') !!}</th>
                                <th class="py-3 text-nowrap">{!! $sortLink('created_at', 'Dibuat') !!}</th>
                                <th class="py-3 pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @forelse($data as $key => $row)
                            <tr class="text-center" :class="isExpanded({{ $row->id }}) ? 'bg-light' : ''">
                                <td class="ps-3 text-muted fw-bold">{{ $data->firstItem() + $key }}</td>
                                <td class="fw-bold text-nowrap">{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}</td>
                                <td class="text-start text-truncate" style="max-width: 200px;"><span class="d-block text-dark fw-semibold">{{ $row->satuanKerja->satuan_kerja ?? '-' }}</span></td>
                                <td class="fw-bold text-info">{{ number_format($row->realisasi_rawat_jalan) }}</td>
                                <td class="fw-bold text-success">{{ number_format($row->realisasi_pasca_rehab) }}</td>
                                <td class="fw-bold text-warning-emphasis">{{ number_format($row->realisasi_skhpn) }}</td>
                                <td class="small text-muted">{{ $row->created_at->format('d/m/Y H:i') }}</td>
                                <td class="pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-light border text-secondary" @click="toggleExpand({{ $row->id }})" title="Lihat Detail"><i class="bi" :class="isExpanded({{ $row->id }}) ? 'bi-chevron-up text-primary' : 'bi-eye text-secondary'"></i></button>
                                        @if(auth()->user()->hasRole(['operator_satker', 'operator_rehab', 'admin', 'admin_satker']))
                                        <a href="{{ route('rehab.laporan.edit', $row->id) }}" class="btn btn-light border text-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                        <button type="button" class="btn btn-light border text-danger" onclick="confirmDeleteLaporan({{ $row->id }})" title="Hapus"><i class="bi bi-trash"></i></button>
                                        <form id="delete-laporan-{{ $row->id }}" action="{{ route('rehab.laporan.destroy', $row->id) }}" method="POST" class="d-none">@csrf @method('DELETE')</form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            <tr x-show="isExpanded({{ $row->id }})" x-transition>
                                <td colspan="8" class="p-0 border-0">
                                    <div class="bg-body-tertiary p-4 border-bottom shadow-inner text-start">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-body">
                                                <h6 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="bi bi-info-circle me-2"></i>Detail Realisasi & Dokumentasi</h6>
                                                <div class="row g-3 mb-4">
                                                    <div class="col-md-4"><div class="p-3 border rounded bg-info bg-opacity-10 text-center"><div class="small text-muted fw-bold">Rawat Jalan</div><div class="fs-4 fw-bold text-info">{{ number_format($row->realisasi_rawat_jalan) }}</div></div></div>
                                                    <div class="col-md-4"><div class="p-3 border rounded bg-success bg-opacity-10 text-center"><div class="small text-muted fw-bold">Pasca Rehab</div><div class="fs-4 fw-bold text-success">{{ number_format($row->realisasi_pasca_rehab) }}</div></div></div>
                                                    <div class="col-md-4"><div class="p-3 border rounded bg-warning bg-opacity-10 text-center"><div class="small text-muted fw-bold">SKHPN</div><div class="fs-4 fw-bold text-warning-emphasis">{{ number_format($row->realisasi_skhpn) }}</div></div></div>
                                                </div>
                                                <h6 class="fw-bold text-secondary small text-uppercase mb-2">File Dokumentasi:</h6>
                                                <ul class="list-group">
                                                    @forelse($row->dokumentasi as $doc)
                                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                                            <a href="{{ asset('storage/'.$doc->path_file) }}" target="_blank" class="text-decoration-none text-dark d-flex align-items-center"><i class="bi bi-file-earmark-image me-2 text-muted"></i> {{ $doc->nama_file_asli }}</a>
                                                            <a href="{{ asset('storage/'.$doc->path_file) }}" download class="btn btn-xs btn-outline-secondary"><i class="bi bi-download"></i></a>
                                                        </li>
                                                    @empty
                                                        <li class="list-group-item text-muted fst-italic">Tidak ada dokumentasi.</li>
                                                    @endforelse
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center py-5 text-muted fst-italic">Tidak ada data untuk filter yang dipilih.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                {{-- FOOTER (PAGINATION) --}}
                <div class="card-footer bg-white py-3 border-top-0">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <select class="form-select form-select-sm border-secondary-subtle" style="width: 70px;" onchange="window.location.href = this.value">
                                @foreach([10, 25, 50, 100] as $num)
                                    <option value="{{ request()->fullUrlWithQuery(['per_page' => $num, 'page' => 1]) }}" {{ request('per_page') == $num ? 'selected' : '' }}>{{ $num }}</option>
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

    {{-- ==================================================================== --}}
    {{-- MODAL TARGET TAHUNAN (BOOTSTRAP MODAL NATIVE - DIJAMIN AMAN) --}}
    {{-- ==================================================================== --}}
    <div class="modal fade" id="targetModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-bullseye me-2"></i>Kelola Target Tahunan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    {{-- Form --}}
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold m-0" :class="targetForm.isEdit ? 'text-warning' : 'text-primary'">
                                    <i class="bi" :class="targetForm.isEdit ? 'bi-pencil-square' : 'bi-plus-circle'"></i> 
                                    <span x-text="targetForm.isEdit ? 'Edit Target' : 'Tambah Target Baru'"></span>
                                </h6>
                                <button type="button" x-show="targetForm.isEdit" @click="resetTargetForm" class="btn btn-xs btn-outline-secondary">Batal Edit</button>
                            </div>
                            <form action="{{ route('rehab.target.store') }}" method="POST">
                                @csrf
                                <template x-if="targetForm.isEdit"><input type="hidden" name="target_id" :value="targetForm.id"></template>
                                <div class="row g-3">
                                    @if(auth()->user()->isAdmin())
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Satuan Kerja</label>
                                        <select name="satuan_kerja_id" class="form-select form-select-sm" x-model="targetForm.satker_id" :disabled="targetForm.isEdit" required>
                                            <option value="">-- Pilih --</option>
                                            @foreach($satuanKerjas as $sk) <option value="{{ $sk->id }}">{{ $sk->satuan_kerja }}</option> @endforeach
                                        </select>
                                        <template x-if="targetForm.isEdit"><input type="hidden" name="satuan_kerja_id" :value="targetForm.satker_id"></template>
                                    </div>
                                    @endif
                                    <div class="col-md-{{ auth()->user()->isAdmin() ? '6' : '12' }}">
                                        <label class="form-label small fw-bold">Tahun</label>
                                        <select name="tahun" class="form-select form-select-sm" x-model="targetForm.tahun" :disabled="targetForm.isEdit" required>
                                            @foreach($modalYears as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                                        </select>
                                        <template x-if="targetForm.isEdit"><input type="hidden" name="tahun" :value="targetForm.tahun"></template>
                                    </div>
                                    <div class="col-md-4"><label class="small fw-bold text-info">Target RJ</label><input type="number" name="target_rawat_jalan" class="form-control" x-model="targetForm.rj" required min="0" placeholder="0"></div>
                                    <div class="col-md-4"><label class="small fw-bold text-success">Target Pasca</label><input type="number" name="target_pasca_rehab" class="form-control" x-model="targetForm.pasca" required min="0" placeholder="0"></div>
                                    <div class="col-md-4"><label class="small fw-bold text-warning-emphasis">Target SKHPN</label><input type="number" name="target_skhpn" class="form-control" x-model="targetForm.skhpn" required min="0" placeholder="0"></div>
                                    <div class="col-12 text-end"><button type="submit" class="btn btn-sm" :class="targetForm.isEdit ? 'btn-warning' : 'btn-primary'">Simpan</button></div>
                                </div>
                            </form>
                        </div>
                    </div>
                    {{-- Table --}}
                    <div class="table-responsive bg-white border rounded shadow-sm" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-sm table-striped text-center mb-0 small align-middle">
                            <thead class="bg-dark text-white sticky-top"><tr><th>Tahun</th><th>Satker</th><th>RJ</th><th>Pasca</th><th>SKHPN</th><th>Aksi</th></tr></thead>
                            <tbody>
                                @foreach($allTargets as $t)
                                <tr :class="targetForm.id == {{ $t->id }} ? 'table-warning' : ''">
                                    <td>{{ $t->tahun }}</td><td>{{ $t->satuanKerja->satuan_kerja ?? '-' }}</td>
                                    <td>{{ number_format($t->target_rawat_jalan) }}</td><td>{{ number_format($t->target_pasca_rehab) }}</td><td>{{ number_format($t->target_skhpn) }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-xs btn-light border text-primary" @click="editTarget({{ json_encode($t) }})"><i class="bi bi-pencil"></i></button>
                                            @if(!$t->has_laporan)
                                                <button type="button" class="btn btn-xs btn-light border text-danger" onclick="confirmDeleteTarget({{ $t->id }})"><i class="bi bi-trash"></i></button>
                                                <form id="delete-target-{{ $t->id }}" action="{{ route('rehab.target.destroy', $t->id) }}" method="POST" class="d-none">@csrf @method('DELETE')</form>
                                            @else
                                                <button type="button" class="btn btn-xs btn-light border text-muted" title="Terkunci" disabled><i class="bi bi-lock-fill"></i></button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2"><button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal">Tutup</button></div>
            </div>
        </div>
    </div>

</main>
@endsection

@push('styles')
<style>
    .ts-control { border: none !important; box-shadow: none !important; padding-top: 0.5rem; padding-bottom: 0.5rem; background-color: transparent !important; min-height: 40px; }
    .custom-table-scroll { max-height: 70vh; overflow-y: auto; position: relative; border: 1px solid #dee2e6; border-radius: 6px; }
    .custom-table-scroll thead th { position: sticky !important; top: 0 !important; z-index: 10; background-color: #f8f9fa !important; box-shadow: inset 0 -1px 0 #dee2e6; }
    .btn-xs { padding: 1px 5px; font-size: 0.75rem; }
    .transition-transform { transition: transform 0.3s ease; }
    body.modal-open { overflow: hidden; }
</style>
@endpush

@push('scripts')
<script>
    function updateBreakdownYear(year) {
        const url = new URL(window.location.href);
        url.searchParams.set('breakdown_year', year);
        window.location.href = url.toString();
    }

    // Fungsi SweetAlert (Global)
    window.confirmDeleteLaporan = function(id) {
        Swal.fire({
            title: 'Hapus Laporan?', text: "Data tidak bisa dikembalikan!", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d', confirmButtonText: 'Ya, Hapus'
        }).then((result) => { if (result.isConfirmed) document.getElementById('delete-laporan-' + id).submit(); });
    }

    window.confirmDeleteTarget = function(id) {
        Swal.fire({
            title: 'Hapus Target?', text: "Target tahunan ini akan dihapus.", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d', confirmButtonText: 'Ya, Hapus'
        }).then((result) => { if (result.isConfirmed) document.getElementById('delete-target-' + id).submit(); });
    }

    document.addEventListener('alpine:init', () => {
        Alpine.data('rehabPage', () => ({
            showBreakdown: false,
            expanded: [],
            // Tidak perlu state isOpen untuk modal, karena pakai bootstrap native
            targetForm: { isEdit: false, id: null, tahun: '{{ date("Y") }}', satker_id: '', rj: '', pasca: '', skhpn: '' },
            
            // Modal Instance
            modalInstance: null,

            init() {
                // Inisialisasi Modal Bootstrap saat Alpine ready
                const el = document.getElementById('targetModal');
                if(el) this.modalInstance = new bootstrap.Modal(el);
            },

            toggleExpand(id) {
                if (this.expanded.includes(id)) this.expanded = this.expanded.filter(i => i !== id);
                else this.expanded.push(id);
            },
            isExpanded(id) { return this.expanded.includes(id); },

            // --- Modal Target Logic (BOOTSTRAP NATIVE) ---
            openTargetModal() { 
                this.resetTargetForm(); 
                if(this.modalInstance) this.modalInstance.show();
            },
            editTarget(data) {
                this.targetForm = { isEdit: true, id: data.id, tahun: data.tahun, satker_id: data.satuan_kerja_id, rj: data.target_rawat_jalan, pasca: data.target_pasca_rehab, skhpn: data.target_skhpn };
            },
            resetTargetForm() {
                this.targetForm = { isEdit: false, id: null, tahun: '{{ date("Y") }}', satker_id: '', rj: '', pasca: '', skhpn: '' };
            }
        }));

        document.addEventListener("DOMContentLoaded", function() {
            if(typeof TomSelect !== 'undefined'){
                const config = { plugins: ['remove_button'], create: false };
                ['select-satker', 'select-bulan', 'select-tahun'].forEach(id => { 
                    if(document.getElementById(id)) new TomSelect('#'+id, config); 
                });
            }
        });
    });
</script>
@endpush