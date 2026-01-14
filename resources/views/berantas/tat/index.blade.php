@extends('admin')
@section('content')
<main class="admin-main" x-data="{ showFilter: true, expanded: [] }">
    <div class="container-fluid p-4 p-lg-5">
        
        {{-- HEADER TITLE --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Data TAT</h1>
                <p class="text-muted mb-0">Kelola Data Tim Asesmen Terpadu</p>
            </div>
            <a href="{{ route('berantas.tat.create') }}" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-plus-lg"></i> Tambah Data
            </a>
        </div>

        {{-- ALERT NOTIFIKASI --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <div><strong>Berhasil!</strong> {{ session('success') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @php
            // Helper Filter Aktif
            $allFilters = request()->only(['search', 'bulan', 'tahun', 'satuan_kerja_id', 'kategori_bb', 'narkotika_ids', 'search_non_narkotika']);
            if (isset($allFilters['tahun']) && $allFilters['tahun'] == [date('Y')]) { unset($allFilters['tahun']); }
            
            $activeFilters = collect($allFilters)->filter(function($value) {
                return !empty($value) && ($value !== ['']);
            })->count();

            // Helper Sorting Link
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
        @endphp

        <div class="row justify-content-center mb-5">
            <div class="col-12">
                
                {{-- CARD FILTER UTAMA --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-2">
                            <h5 class="card-title mb-0 fw-bold text-secondary"><i class="bi bi-table me-2"></i>Data Kasus TAT</h5>
                            
                            {{-- Tombol Toggle Filter --}}
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
                        <form action="{{ route('berantas.tat.index') }}" method="GET">
                            <input type="hidden" name="sort_by" value="{{ request('sort_by', 'created_at') }}">
                            <input type="hidden" name="sort_order" value="{{ request('sort_order', 'desc') }}">

                            <div x-show="showFilter" x-transition class="mb-4 px-3 px-lg-0 pt-3 pt-lg-0">
                                <div class="bg-body-tertiary p-4 rounded-3 border">
                                    <div class="row g-3 text-start">

                                        {{-- Baris 1: Kata Kunci Global --}}
                                        <div class="col-12">
                                            <label class="form-label fw-bold small text-secondary text-uppercase">Kata Kunci</label>
                                            <div class="input-group shadow-sm">
                                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                                <input type="text" name="search" class="form-control border-start-0 ps-0" 
                                                       value="{{ request('search') }}" 
                                                       placeholder="Cari No Register, Nama Tersangka, Instansi...">
                            </div>
                        </div>

                        {{-- Baris 2: Filter Dropdown --}}
                        @if(Auth::user()->isAdmin())
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Satuan Kerja</label>
                            <div class="shadow-sm bg-white rounded">
                                <select id="select-satker" name="satuan_kerja_id[]" multiple placeholder="Pilih Satuan Kerja..." autocomplete="off">
                                    @foreach($satuanKerjas as $satker)
                                        <option value="{{ $satker->id }}" @selected(in_array($satker->id, request('satuan_kerja_id', [])))>
                                            {{ $satker->satuan_kerja }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @endif

                        <div class="col-md-6 {{ Auth::user()->isAdmin() ? 'col-lg-3' : 'col-lg-4' }}">
                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Kategori Barang Bukti</label>
                            <div class="shadow-sm bg-white rounded">
                                <select id="select-kategori-bb" name="kategori_bb[]" multiple placeholder="Pilih Kategori..." autocomplete="off">
                                    <option value="Narkotika" {{ in_array('Narkotika', request('kategori_bb', [])) ? 'selected' : '' }}>Narkotika</option>
                                    <option value="Non-Narkotika" {{ in_array('Non-Narkotika', request('kategori_bb', [])) ? 'selected' : '' }}>Non-Narkotika</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-6 {{ Auth::user()->isAdmin() ? 'col-lg-3' : 'col-lg-4' }}">
                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Bulan</label>
                            <div class="shadow-sm bg-white rounded">
                                <select id="select-bulan" name="bulan[]" multiple placeholder="Pilih Bulan..." autocomplete="off">
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" @selected(in_array($m, request('bulan', [])))>
                                            {{ \Carbon\Carbon::create()->month($m)->locale('id')->translatedFormat('F') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-6 {{ Auth::user()->isAdmin() ? 'col-lg-3' : 'col-lg-4' }}">
                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Tahun</label>
                            <div class="shadow-sm bg-white rounded">
                                <select id="select-tahun" name="tahun[]" multiple placeholder="Pilih Tahun..." autocomplete="off">
                                    @foreach($years as $year)
                                        <option value="{{ $year }}" @selected(in_array($year, request('tahun', [date('Y')])))>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        {{-- Baris 3: Filter Dinamis (Hidden by Default via JS) --}}
                        <div class="col-lg-6" id="wrapper-narkotika" style="display: none;">
                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Jenis Narkotika</label>
                            <div class="shadow-sm bg-white rounded">
                                <select id="select-narkotika" name="narkotika_ids[]" multiple placeholder="Pilih Jenis Narkotika..." autocomplete="off">
                                    @foreach($masterNarkotika as $n)
                                        <option value="{{ $n->id }}" {{ in_array($n->id, request('narkotika_ids', [])) ? 'selected' : '' }}>{{ $n->nama_narkotika }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-6" id="wrapper-non-narkotika" style="display: none;">
                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Nama Barang (Non-Narkotika)</label>
                            <div class="shadow-sm bg-white rounded">
                                <select id="select-non-narkotika" name="search_non_narkotika[]" multiple placeholder="Ketik nama barang..." autocomplete="off">
                                    @if(request('search_non_narkotika'))
                                        @foreach(request('search_non_narkotika') as $val)
                                            <option value="{{ $val }}" selected>{{ $val }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>

                        {{-- Tombol Aksi --}}
                        <div class="col-12 text-end pt-3 border-top mt-2">
                            <a href="{{ route('berantas.tat.index') }}" class="btn btn-link text-muted text-decoration-none me-3">Reset Filter</a>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm"><i class="bi bi-funnel-fill me-1"></i> Terapkan</button>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- EXPORT BUTTON & TOTAL DATA --}}
            <div class="d-flex justify-content-between align-items-center mb-3 px-3 px-lg-0">
                <button type="submit" formaction="{{ route('berantas.tat.export') }}" class="btn btn-success btn-sm text-white d-flex align-items-center gap-2 shadow-sm">
                    <i class="bi bi-file-earmark-excel"></i> <span class="d-none d-lg-inline">Export Excel</span>
                </button>
                <div class="text-muted small fst-italic">
                    Total Data: <strong>{{ $data->total() }}</strong>
                </div>
            </div>

            </form>

            {{-- TABEL DATA --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="min-width: 900px;">
                            <thead class="bg-light sticky-top">
                                <tr class="text-center align-middle small text-uppercase text-secondary text-nowrap">
                                    <th class="py-3 bg-light ps-3" width="5%">No</th>
                                    <th class="py-3 bg-light text-start" width="20%">{!! $sortLink('no_register', 'Register & Satker') !!}</th>
                                    <th class="py-3 bg-light text-start" width="25%">Tersangka</th>
                                    <th class="py-3 bg-light text-start" width="25%">Barang Bukti</th>
                                    <th class="py-3 bg-light" width="15%">{!! $sortLink('created_at', 'Dibuat') !!}</th>
                                    <th class="py-3 bg-light pe-3 text-center" width="10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="border-top-0">
                                @forelse($data as $row)
                                <tr class="align-top" :class="expanded.includes({{ $row->id }}) ? 'bg-light' : ''">
                                    <td class="ps-3 text-secondary text-center fw-bold py-3">{{ $data->firstItem() + $loop->index }}</td>
                                    <td class="py-3">
                                        <a href="#" class="text-decoration-none fw-bold text-dark d-block mb-1" @click.prevent="expanded.includes({{ $row->id }}) ? expanded = expanded.filter(id => id !== {{ $row->id }}) : expanded.push({{ $row->id }})">
                                            {{ $row->no_register }}
                                        </a>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge bg-white text-secondary border fw-normal shadow-sm">
                                                <i class="bi bi-calendar-event me-1"></i> {{ $row->tanggal_pelaksanaan->locale('id')->translatedFormat('d M Y') }}
                                            </span>
                                        </div>
                                        <div class="small text-muted fw-semibold">
                                            <i class="bi bi-building me-1"></i> {{ $row->satuanKerja->satuan_kerja ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="text-start py-3">
                                        @if($row->tersangka->count() > 0)
                                            <div class="d-flex flex-column gap-2">
                                                @foreach($row->tersangka as $t)
                                                    <div class="d-flex align-items-center bg-white p-1 rounded border shadow-sm" style="width: fit-content;">
                                                        <i class="bi bi-person-fill text-secondary mx-1 small"></i>
                                                        <div>
                                                            <span class="small fw-bold text-dark">{{ $t->nama_tersangka }}</span>
                                                            <span class="text-muted ms-1 small" style="font-size: 0.7rem;">({{ $t->jenis_kelamin }})</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted small fst-italic">-</span>
                                        @endif
                                    </td>
                                    <td class="text-start py-3">
                                        @if($row->barangBukti->count() > 0)
                                            <div class="d-flex flex-column gap-1">
                                                @foreach($row->barangBukti as $bb)
                                                    <div class="small d-flex align-items-center">
                                                        @if($bb->kategori === 'Narkotika') 
                                                            <i class="bi bi-capsule text-danger me-2" title="Narkotika"></i> 
                                                        @else 
                                                            <i class="bi bi-box-seam text-success me-2" title="Non-Narkotika"></i> 
                                                        @endif
                                                        <span class="text-dark me-1 fw-semibold">{{ $bb->nama_barang }}</span>
                                                        <span class="text-muted">({{ (float)$bb->kuantitas }} {{ $bb->satuan }})</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted small fst-italic">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center py-3">
                                        <div class="small text-dark">{{ $row->created_at->format('d/m/Y') }}</div>
                                        <div class="small text-muted" style="font-size: 0.75rem;">{{ $row->created_at->format('H:i') }} WIB</div>
                                    </td>
                                    <td class="text-center pe-3 py-3">
                                        <div class="btn-group btn-group-sm shadow-sm">
                                            <button type="button" class="btn btn-light border text-secondary" 
                                                    :class="expanded.includes({{ $row->id }}) ? 'btn-primary text-white' : 'btn-light border text-secondary'"
                                                    @click="expanded.includes({{ $row->id }}) ? expanded = expanded.filter(id => id !== {{ $row->id }}) : expanded.push({{ $row->id }})"
                                                    title="Lihat Detail">
                                                <i class="bi" :class="expanded.includes({{ $row->id }}) ? 'bi-chevron-up' : 'bi-eye'"></i>
                                            </button>

                                            <a href="{{ route('berantas.tat.edit', $row->id) }}" class="btn btn-light border text-primary" title="Edit Data">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>

                                            <button type="button" class="btn btn-light border text-danger" onclick="confirmDelete({{ $row->id }})" title="Hapus Data">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <form id="delete-form-{{ $row->id }}" action="{{ route('berantas.tat.destroy', $row->id) }}" method="POST" class="d-none">@csrf @method('DELETE')</form>
                                        </div>
                                    </td>
                                </tr>

                                {{-- BARIS DETAIL --}}
                                <tr x-show="expanded.includes({{ $row->id }})" x-transition>
                                    <td colspan="6" class="p-0 border-0">
                                        <div class="bg-body-tertiary p-4 border-bottom shadow-inner text-start">
                                            <div class="card border-0 shadow-sm">
                                                <div class="card-body">
                                                    <h6 class="fw-bold text-primary mb-3 border-bottom pb-2"><i class="bi bi-info-circle-fill me-2"></i>Detail Kasus & Asesmen</h6>
                                                    
                                                    <div class="row g-3 text-start">
                                                        <div class="col-md-12">
                                                            <label class="small text-secondary fw-bold text-uppercase mb-1">Pasal Disangkakan</label>
                                                            <div class="p-2 bg-light rounded border text-dark small fw-bold">{{ $row->pasal_disangkakan ?? '-' }}</div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="small text-secondary fw-bold text-uppercase mb-1">Instansi Pengirim</label>
                                                            <div class="text-dark fw-bold small">{{ $row->instansi_pengirim ?? '-' }}</div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="small text-secondary fw-bold text-uppercase mb-1">Tgl Penangkapan</label>
                                                            <div class="text-dark small">{{ $row->tanggal_penangkapan ? $row->tanggal_penangkapan->locale('id')->translatedFormat('d F Y') : '-' }}</div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="small text-secondary fw-bold text-uppercase mb-1">Tgl Permohonan</label>
                                                            <div class="text-dark small">{{ $row->tanggal_permohonan ? $row->tanggal_permohonan->locale('id')->translatedFormat('d F Y') : '-' }}</div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="small text-secondary fw-bold text-uppercase mb-1">Tim Hukum</label>
                                                            <div class="p-2 bg-light rounded border text-dark small">{{ $row->tim_hukum ?? '-' }}</div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="small text-secondary fw-bold text-uppercase mb-1">Tim Medis</label>
                                                            <div class="p-2 bg-light rounded border text-dark small">{{ $row->tim_medis ?? '-' }}</div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="small text-secondary fw-bold text-uppercase mb-1">Lembaga Rehab</label>
                                                            <div class="text-dark small fw-bold">{{ $row->lembaga_rehab ?? '-' }}</div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="small text-secondary fw-bold text-uppercase mb-1">Rekomendasi</label>
                                                            <div>
                                                                @if($row->tindak_lanjut_rekomendasi == 'dilaksanakan')
                                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25"><i class="bi bi-check-circle-fill me-1"></i> Dilaksanakan</span>
                                                                @else
                                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25"><i class="bi bi-x-circle-fill me-1"></i> Tidak Dilaksanakan</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="small text-secondary fw-bold text-uppercase mb-1">Proses Hukum Lanjut</label>
                                                            <div class="p-2 bg-light rounded border text-dark small">{{ $row->proses_hukum_lanjut ?? '-' }}</div>
                                                        </div>

                                                        {{-- LAMPIRAN DOKUMEN --}}
                                                        <div class="col-12 mt-3 pt-3 border-top">
                                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                                <label class="small text-secondary fw-bold text-uppercase mb-0"><i class="bi bi-paperclip me-1"></i> Lampiran Dokumentasi</label>
                                                                <span class="badge bg-secondary rounded-pill">{{ $row->dokumentasi->count() }} File</span>
                                                            </div>
                                                            
                                                            @if($row->dokumentasi->count() > 0)
                                                                <div class="d-flex gap-2 flex-wrap">
                                                                    @foreach($row->dokumentasi as $doc)
                                                                        <div class="btn-group btn-group-sm shadow-sm">
                                                                            <a href="{{ Storage::url($doc->path_file) }}" target="_blank" class="btn btn-white border d-flex align-items-center gap-2" title="{{ $doc->nama_file_asli }}">
                                                                                @if(Str::contains($doc->tipe_file, 'image')) <i class="bi bi-file-earmark-image text-primary"></i>
                                                                                @elseif(Str::contains($doc->tipe_file, 'pdf')) <i class="bi bi-file-earmark-pdf text-danger"></i>
                                                                                @else <i class="bi bi-file-earmark-text text-secondary"></i> @endif
                                                                                <span class="d-inline-block text-truncate" style="max-width: 150px;">{{ $doc->nama_file_asli }}</span>
                                                                            </a>
                                                                            <a href="{{ route('dokumentasi.download', $doc->id) }}" class="btn btn-light border text-primary" title="Download"><i class="bi bi-download"></i></a>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @else
                                                                <div class="text-muted small fst-italic border rounded p-2 text-center bg-light">Tidak ada lampiran dokumen.</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted fst-italic border-bottom">
                                        <i class="bi bi-inbox display-4 d-block mb-3 opacity-25"></i>
                                        Belum ada data TAT yang diinput.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    {{-- FOOTER PAGINATION --}}
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
    </div>
</main>
@endsection

@push('styles')
<style>
    .ts-dropdown, .ts-dropdown.single { z-index: 2000 !important; }
    .ts-control { border: none !important; box-shadow: none !important; padding-top: 0.5rem; padding-bottom: 0.5rem; background-color: transparent !important; min-height: 40px; }
    .ts-wrapper.focus .ts-control { box-shadow: none !important; }
    
    .custom-table-scroll { max-height: 70vh; overflow-y: auto; position: relative; border: 1px solid #dee2e6; border-radius: 6px; }
    .custom-table-scroll thead th { position: sticky !important; top: 0 !important; z-index: 10; background-color: #f8f9fa !important; box-shadow: inset 0 -1px 0 #dee2e6; }
    
    .btn-xs { padding: 1px 5px; font-size: 0.75rem; }
    .page-link { border: none; color: #6c757d; border-radius: 50% !important; margin: 0 2px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; }
    .page-item.active .page-link { background-color: #0d6efd; color: white; box-shadow: 0 2px 4px rgba(13,110,253,0.3); }
</style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        const configTomSelect = { plugins: ['remove_button', 'clear_button'], persist: false, create: false, maxOptions: null };
        
        // 1. Inisialisasi Filter Standar
        ['select-satker', 'select-bulan', 'select-tahun', 'select-narkotika'].forEach(id => { 
            if(document.getElementById(id)) new TomSelect('#' + id, configTomSelect); 
        });

        // 2. Inisialisasi Filter Kategori BB dengan Listener (Dynamic)
        if(document.getElementById('select-kategori-bb')) {
            const tsKategori = new TomSelect('#select-kategori-bb', {
                ...configTomSelect,
                onChange: function() { updateBBVisibility(); }
            });

            function updateBBVisibility() {
                const selected = tsKategori.getValue(); 
                const wrapperNarkotika = document.getElementById('wrapper-narkotika');
                const wrapperNonNarkotika = document.getElementById('wrapper-non-narkotika');

                if (selected.length === 0) {
                    if(wrapperNarkotika) wrapperNarkotika.style.display = 'none';
                    if(wrapperNonNarkotika) wrapperNonNarkotika.style.display = 'none';
                } else {
                    if(wrapperNarkotika) wrapperNarkotika.style.display = selected.includes('Narkotika') ? 'block' : 'none';
                    if(wrapperNonNarkotika) wrapperNonNarkotika.style.display = selected.includes('Non-Narkotika') ? 'block' : 'none';
                }
            }
            updateBBVisibility();
        }
        
        // 3. Inisialisasi Pencarian Non-Narkotika (Create Mode)
        if(document.getElementById('select-non-narkotika')) {
             new TomSelect('#select-non-narkotika', {
                plugins: ['remove_button', 'clear_button'],
                create: true, 
                persist: false,
                createOnBlur: true,
                maxOptions: null
             });
        }
    });

    window.confirmDelete = function(id) {
        Swal.fire({
            title: 'Hapus Data?', 
            text: "Data kasus, tersangka, dan Barang Bukti akan dihapus permanen.", 
            icon: 'warning',
            showCancelButton: true, 
            confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) { document.getElementById('delete-form-' + id).submit(); }
        });
    }
</script>
@endpush