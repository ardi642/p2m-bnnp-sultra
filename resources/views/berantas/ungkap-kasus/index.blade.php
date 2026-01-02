@extends('admin')

@section('content')
<main class="admin-main">
    <div class="container-fluid p-4 p-lg-5">
        
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Data Ungkap Kasus</h1>
                <p class="text-muted mb-0">Manajemen Data Penindakan dan Ungkap Kasus Narkoba</p>
            </div>
            <a href="{{ route('berantas.ungkap-kasus.create') }}" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-plus-lg"></i> Tambah Data
            </a>
        </div>

        {{-- ALERT NOTIFIKASI --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <div><strong>Berhasil!</strong> {{ session('message') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><strong>Gagal!</strong> {{ session('message') ?? session('error') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- LOGIKA SORTING HELPER --}}
        @php
            $sortLink = function($col, $label) {
                $currentCol = request('sort_by', 'created_at'); 
                $currentOrder = request('sort_order', 'desc');
                $newOrder = ($currentCol === $col && $currentOrder === 'desc') ? 'asc' : 'desc';
                $icon = 'bi-arrow-down-up text-muted opacity-25';
                if ($currentCol === $col) {
                    $icon = $currentOrder === 'desc' ? 'bi-sort-down text-primary' : 'bi-sort-up text-primary';
                }
                $url = request()->fullUrlWithQuery(['sort_by' => $col, 'sort_order' => $newOrder]);
                return '<a href="'.$url.'" class="text-decoration-none text-secondary fw-bold d-flex align-items-center justify-content-between gap-2">'.$label.' <i class="bi '.$icon.'"></i></a>';
            };
            
            // Hitung filter aktif
            $allFilters = request()->only(['satuan_kerja_id', 'bulan', 'tahun', 'search']);
            if (empty($allFilters['tahun'])) { $allFilters['tahun'] = [date('Y')]; }
            $activeFilters = collect($allFilters)->filter(function($value) { return !empty($value); })->count(); 
        @endphp

        <div class="row justify-content-center mb-5" x-data="{ showFilter: true }">
            <div class="col-12">
                
                <div class="card border-0 shadow-sm">
                    {{-- CARD HEADER & TOGGLE FILTER --}}
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-2">
                            <h5 class="card-title mb-0 fw-bold text-secondary"><i class="bi bi-table me-2"></i>Daftar Laporan Kasus</h5>
                            
                            <button type="button" @click="showFilter = !showFilter" 
                                class="btn btn-sm transition-all d-flex align-items-center gap-2"
                                :class="showFilter ? 'btn-light text-secondary border' : 'btn-primary shadow-sm'">
                                <i class="bi" :class="showFilter ? 'bi-chevron-up' : 'bi-funnel'"></i> 
                                <span x-text="showFilter ? 'Sembunyikan Filter' : 'Filter Pencarian'"></span>
                                @if($activeFilters > 0)
                                    <span class="badge bg-danger rounded-pill">{{ $activeFilters }}</span>
                                @endif
                            </button>
                        </div>
                    </div>

                    <div class="card-body p-0 p-lg-4">
                        
                        {{-- FORM FILTER & EXPORT --}}
                        <form action="{{ route('berantas.ungkap-kasus.index') }}" method="GET">
                            {{-- Pertahankan parameter sorting saat submit filter --}}
                            <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                            <input type="hidden" name="sort_order" value="{{ request('sort_order') }}">

                            {{-- PANEL FILTER --}}
                            <div x-show="showFilter" x-transition class="mb-4 px-3 px-lg-0 pt-3 pt-lg-0">
                                <div class="bg-body-tertiary p-4 rounded-3 border">
                                    <div class="row g-3 text-start">
                                        
                                        {{-- 1. Search Keyword --}}
                                        <div class="{{ Auth::user()->isAdmin() ? 'col-lg-6' : 'col-12' }}">
                                            <label class="form-label fw-bold small text-secondary text-uppercase">Kata Kunci</label>
                                            <div class="input-group shadow-sm">
                                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                                <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Cari Nomor LKN, TKP, Nama Tersangka..." value="{{ request('search') }}">
                                            </div>
                                        </div>

                                        {{-- 2. Satker (Admin Only) --}}
                                        @if(Auth::user()->isAdmin())
                                        <div class="col-lg-6">
                                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Satuan Kerja</label>
                                            <div class="shadow-sm bg-white rounded">
                                                <select id="select-satker" name="satuan_kerja_id[]" multiple placeholder="Pilih Satuan Kerja..." autocomplete="off">
                                                    @foreach($satuanKerjas as $satker)
                                                        <option value="{{ $satker->id }}" {{ in_array($satker->id, request('satuan_kerja_id', [])) ? 'selected' : '' }}>{{ $satker->satuan_kerja }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        @endif

                                        {{-- 3. Bulan --}}
                                        <div class="col-6 col-lg-3">
                                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Bulan</label>
                                            <div class="shadow-sm bg-white rounded">
                                                <select id="select-bulan" name="bulan[]" multiple placeholder="Bulan..." autocomplete="off">
                                                    @foreach(range(1, 12) as $m)
                                                        <option value="{{ $m }}" {{ in_array($m, request('bulan', [])) ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->locale('id')->translatedFormat('F') }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- 4. Tahun --}}
                                        <div class="col-6 col-lg-3">
                                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Tahun</label>
                                            <div class="shadow-sm bg-white rounded">
                                                <select id="select-tahun" name="tahun[]" multiple placeholder="Tahun..." autocomplete="off">
                                                    @foreach($years as $year)
                                                        <option value="{{ $year }}" {{ in_array($year, request('tahun', [date('Y')])) ? 'selected' : '' }}>{{ $year }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Tombol Action Filter --}}
                                        <div class="col-12 text-end pt-3 border-top mt-4 text-start">
                                            <a href="{{ route('berantas.ungkap-kasus.index') }}" class="btn btn-link text-decoration-none text-muted btn-sm me-2">Reset</a>
                                            <button type="submit" class="btn btn-primary px-4 shadow-sm"><i class="bi bi-funnel-fill me-1"></i> Terapkan</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- TOMBOL EXPORT & TOTAL DATA --}}
                            <div class="d-flex justify-content-between align-items-center mb-3 px-3 px-lg-0">
                                {{-- Tombol Export dengan FormAction Override --}}
                                <button type="submit" formaction="{{ route('berantas.ungkap-kasus.export') }}" class="btn btn-success btn-sm text-white d-flex align-items-center gap-2 shadow-sm">
                                    <i class="bi bi-file-earmark-excel"></i> <span class="d-none d-lg-inline">Export Excel</span>
                                </button>
                                
                                <div class="text-muted small fst-italic">
                                    Total Data: <strong>{{ $kasus->total() }}</strong> Kasus
                                </div>
                            </div>
                        </form>

                        {{-- TABEL DATA --}}
                        <div class="custom-table-scroll mb-3">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light sticky-top">
                                    <tr class="text-center align-middle small text-uppercase text-secondary text-nowrap">
                                        <th class="py-3 bg-light ps-3">No</th>
                                        <th class="py-3 bg-light text-start">{!! $sortLink('nomor_lkn', 'Nomor LKN') !!}</th>
                                        <th class="py-3 bg-light">{!! $sortLink('tanggal_kejadian', 'Tanggal') !!}</th>
                                        <th class="py-3 bg-light text-start">Satuan Kerja</th>
                                        <th class="py-3 bg-light text-start">Tersangka</th>
                                        <th class="py-3 bg-light text-start">Barang Bukti</th>
                                        <th class="py-3 bg-light pe-3">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="border-top-0">
                                    @forelse($kasus as $data)
                                    <tr>
                                        <td class="text-center fw-bold text-secondary">{{ $kasus->firstItem() + $loop->index }}</td>
                                        <td class="text-start"><span class="fw-bold text-dark">{{ $data->nomor_lkn }}</span></td>
                                        <td class="text-center text-nowrap small">{{ $data->tanggal_kejadian->format('d/m/Y') }}</td>
                                        <td class="text-start small">{{ $data->satuanKerja->satuan_kerja ?? '-' }}</td>
                                        
                                        {{-- List Tersangka --}}
                                        <td class="text-start small">
                                            @if($data->tersangka->count() > 0)
                                                <ul class="mb-0 ps-3">
                                                    @foreach($data->tersangka->take(3) as $tsk)
                                                        <li>{{ $tsk->nama_tersangka }}</li>
                                                    @endforeach
                                                    @if($data->tersangka->count() > 3)
                                                        <li class="fst-italic text-muted">+ {{ $data->tersangka->count() - 3 }} lainnya...</li>
                                                    @endif
                                                </ul>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        {{-- List Barang Bukti --}}
                                        <td class="text-start small">
                                            @if($data->barangBukti->count() > 0)
                                                <ul class="mb-0 ps-3">
                                                    @foreach($data->barangBukti->take(2) as $bb)
                                                        <li>{{ $bb->jenis_barang_bukti }} ({{ $bb->jumlah_barang_bukti }} {{ $bb->satuan_barang_bukti }})</li>
                                                    @endforeach
                                                    @if($data->barangBukti->count() > 2)
                                                        <li class="fst-italic text-muted">+ {{ $data->barangBukti->count() - 2 }} lainnya...</li>
                                                    @endif
                                                </ul>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        <td class="text-center pe-3">
                                            <div class="btn-group btn-group-sm shadow-sm">
                                                <a href="{{ route('berantas.ungkap-kasus.edit', $data->id) }}" class="btn btn-light border text-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                                <button type="button" class="btn btn-light border text-danger" onclick="confirmDelete({{ $data->id }})" title="Hapus"><i class="bi bi-trash"></i></button>
                                            </div>
                                            <form id="delete-form-{{ $data->id }}" action="{{ route('berantas.ungkap-kasus.destroy', $data->id) }}" method="POST" class="d-none">@csrf @method('DELETE')</form>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted fst-italic">Belum ada data kasus ditemukan.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- PAGINATION --}}
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
                                <div>{{ $kasus->withQueryString()->links() }}</div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@push('styles')
<style>
    /* Styling agar dropdown tidak tertutup header tabel */
    .ts-dropdown, .ts-dropdown.single { z-index: 2000 !important; }
    .ts-control { border: none !important; box-shadow: none !important; padding-top: 0.5rem; padding-bottom: 0.5rem; background-color: transparent !important; min-height: 40px; }
    .ts-wrapper.focus .ts-control { box-shadow: none !important; }
    
    .custom-table-scroll { max-height: 70vh; overflow-y: auto; position: relative; border: 1px solid #dee2e6; border-radius: 6px; }
    .custom-table-scroll thead th { position: sticky !important; top: 0 !important; z-index: 10; background-color: #f8f9fa !important; box-shadow: inset 0 -1px 0 #dee2e6; }
    
    .page-link { border: none; color: #6c757d; border-radius: 50% !important; margin: 0 2px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; }
    .page-item.active .page-link { background-color: #0d6efd; color: white; box-shadow: 0 2px 4px rgba(13,110,253,0.3); }
</style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        // Init TomSelect untuk semua filter dropdown
        const configTomSelect = { plugins: ['remove_button', 'clear_button'], persist: false, create: false, maxOptions: null };
        const ids = ['select-satker', 'select-bulan', 'select-tahun'];
        ids.forEach(id => { if(document.getElementById(id)) new TomSelect('#' + id, configTomSelect); });
    });

    window.confirmDelete = function(id) {
        Swal.fire({
            title: 'Hapus Kasus?', text: "Data LKN, Tersangka, dan Barang Bukti akan dihapus permanen.", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) { document.getElementById('delete-form-' + id).submit(); }
        });
    }
</script>
@endpush