@extends('admin')

@section('content')
<main class="admin-main">
    <div class="container-fluid p-4 p-lg-5">
        
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Data TAT (Tim Asesmen Terpadu)</h1>
                <p class="text-muted mb-0">Manajemen Data Pelaksanaan Asesmen Terpadu</p>
            </div>
            <a href="{{ route('berantas.tat.create') }}" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-plus-lg"></i> Tambah Data
            </a>
        </div>

        {{-- ALERTS --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center"><i class="bi bi-check-circle-fill me-2"></i><div><strong>Berhasil!</strong> {{ session('message') ?? session('success') }}</div></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center"><i class="bi bi-exclamation-triangle-fill me-2"></i><div><strong>Gagal!</strong> {{ session('error') }}</div></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- FILTER PANEL --}}
        <div class="row justify-content-center mb-5" 
             x-data="{ 
                showFilter: true, 
                showAdvanced: {{ request()->anyFilled(['filter_register','filter_nama','filter_nik','filter_jk','filter_instansi','filter_status','filter_narkoba','filter_lembaga','filter_tindak_lanjut','filter_tgl_mulai']) ? 'true' : 'false' }} 
             }">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-2">
                            <h5 class="card-title mb-0 fw-bold text-secondary"><i class="bi bi-table me-2"></i>Daftar Laporan TAT</h5>
                            <button type="button" @click="showFilter = !showFilter" class="btn btn-sm btn-light text-secondary border d-flex align-items-center gap-2">
                                <i class="bi" :class="showFilter ? 'bi-chevron-up' : 'bi-chevron-down'"></i> <span x-text="showFilter ? 'Sembunyikan Panel' : 'Tampilkan Panel'"></span>
                            </button>
                        </div>
                    </div>

                    <div class="card-body p-0 p-lg-4" x-show="showFilter" x-transition>
                        <form action="{{ route('berantas.tat.index') }}" method="GET">
                            <input type="hidden" name="sort_by" value="{{ request('sort_by', 'created_at') }}">
                            <input type="hidden" name="sort_order" value="{{ request('sort_order', 'desc') }}">

                            {{-- A. FILTER UMUM (Standard) --}}
                            <div class="bg-body-tertiary p-4 rounded-3 border mb-3">
                                <div class="row g-3">
                                    {{-- 1. PENCARIAN UMUM --}}
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label fw-bold small text-secondary text-uppercase">Pencarian Umum</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                            <input type="text" name="search" class="form-control border-start-0 ps-0" 
                                                   placeholder="Cari No Register, Nama, Instansi, atau Status..." 
                                                   value="{{ request('search') }}">
                                        </div>
                                    </div>

                                    {{-- 2. FILTER WAKTU & SATKER --}}
                                    @if(Auth::user()->isAdmin())
                                        <div class="col-12 col-lg-3">
                                            <label class="form-label fw-bold small text-secondary text-uppercase">Satuan Kerja</label>
                                            <div class="shadow-sm bg-white rounded">
                                                <select id="select-satker" name="satuan_kerja_id[]" multiple placeholder="Pilih Satuan Kerja...">
                                                    @foreach($satuanKerjas as $satker)
                                                        <option value="{{ $satker->id }}" {{ in_array($satker->id, request('satuan_kerja_id', [])) ? 'selected' : '' }}>{{ $satker->satuan_kerja }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- PERBAIKAN: Bungkus Select Bulan & Tahun agar kotak putih --}}
                                    <div class="col-6 col-lg-{{ Auth::user()->isAdmin() ? '1' : '3' }}">
                                        <label class="form-label fw-bold small text-secondary text-uppercase">Bulan</label>
                                        <div class="shadow-sm bg-white rounded">
                                            <select id="select-bulan" name="bulan[]" multiple placeholder="Bulan...">
                                                @foreach(range(1, 12) as $m)
                                                    <option value="{{ $m }}" {{ in_array($m, request('bulan', [])) ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->locale('id')->translatedFormat('F') }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-6 col-lg-{{ Auth::user()->isAdmin() ? '2' : '3' }}">
                                        <label class="form-label fw-bold small text-secondary text-uppercase">Tahun</label>
                                        <div class="shadow-sm bg-white rounded">
                                            <select id="select-tahun" name="tahun[]" multiple placeholder="Tahun...">
                                                @foreach($years as $year)
                                                    <option value="{{ $year }}" {{ in_array($year, request('tahun', [date('Y')])) ? 'selected' : '' }}>{{ $year }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                {{-- TOGGLE ADVANCED FILTER --}}
                                <div class="mt-3">
                                    <button type="button" class="btn btn-link text-decoration-none p-0 fw-bold small d-flex align-items-center" 
                                            @click="showAdvanced = !showAdvanced">
                                        <i class="bi me-1" :class="showAdvanced ? 'bi-dash-square' : 'bi-plus-square'"></i>
                                        <span x-text="showAdvanced ? 'Tutup Filter Spesifik' : 'Buka Filter Spesifik (Advanced)'"></span>
                                    </button>
                                </div>
                            </div>

                            {{-- B. FILTER KOMPLEKS (Lengkap) --}}
                            <div x-show="showAdvanced" x-transition class="bg-white p-4 rounded-3 border mb-3 border-start border-4 border-start-primary">
                                <h6 class="fw-bold text-primary mb-3 small text-uppercase">Filter Data Spesifik</h6>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">No Register</label>
                                        <input type="text" name="filter_register" class="form-control form-control-sm" value="{{ request('filter_register') }}" placeholder="Contoh: REG/001...">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">Nama Tersangka</label>
                                        <input type="text" name="filter_nama" class="form-control form-control-sm" value="{{ request('filter_nama') }}" placeholder="Cari nama...">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">NIK</label>
                                        <input type="text" name="filter_nik" class="form-control form-control-sm" value="{{ request('filter_nik') }}" placeholder="Cari NIK...">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">Jenis Kelamin</label>
                                        <select name="filter_jk" class="form-select form-select-sm">
                                            <option value="">Semua</option>
                                            <option value="Laki-laki" @selected(request('filter_jk') == 'Laki-laki')>Laki-laki</option>
                                            <option value="Perempuan" @selected(request('filter_jk') == 'Perempuan')>Perempuan</option>
                                        </select>
                                    </div>
                                    
                                    {{-- Baris 2 --}}
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">Instansi Pengirim</label>
                                        <input type="text" name="filter_instansi" class="form-control form-control-sm" value="{{ request('filter_instansi') }}" placeholder="Polres, BNNK...">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">Jenis Narkoba</label>
                                        <input type="text" name="filter_narkoba" class="form-control form-control-sm" value="{{ request('filter_narkoba') }}" placeholder="Sabu, Ganja...">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">Lembaga Rehab</label>
                                        <input type="text" name="filter_lembaga" class="form-control form-control-sm" value="{{ request('filter_lembaga') }}" placeholder="Nama lembaga...">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">Tindak Lanjut</label>
                                        <select name="filter_tindak_lanjut" class="form-select form-select-sm">
                                            <option value="">Semua</option>
                                            <option value="dilaksanakan" @selected(request('filter_tindak_lanjut') == 'dilaksanakan')>Dilaksanakan</option>
                                            <option value="tidak dilaksanakan" @selected(request('filter_tindak_lanjut') == 'tidak dilaksanakan')>Tidak Dilaksanakan</option>
                                        </select>
                                    </div>

                                    {{-- Baris 3 --}}
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted">Status / Proses Hukum</label>
                                        <input type="text" name="filter_status" class="form-control form-control-sm" value="{{ request('filter_status') }}" placeholder="Ketikan status...">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted">Rentang Tanggal Pelaksanaan</label>
                                        <div class="input-group input-group-sm">
                                            <input type="date" name="filter_tgl_mulai" class="form-control" value="{{ request('filter_tgl_mulai') }}">
                                            <span class="input-group-text">s/d</span>
                                            <input type="date" name="filter_tgl_selesai" class="form-control" value="{{ request('filter_tgl_selesai') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- ACTION BUTTONS --}}
                            <div class="d-flex justify-content-between align-items-center pt-2">
                                <a href="{{ route('berantas.tat.index') }}" class="btn btn-light border text-secondary btn-sm px-3">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filter
                                </a>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                        <i class="bi bi-search me-1"></i> Terapkan Pencarian
                                    </button>
                                    <button type="submit" formaction="{{ route('berantas.tat.export') }}" class="btn btn-success px-4 shadow-sm">
                                        <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABLE DATA --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="custom-table-scroll border rounded">
                    <table class="table table-hover align-middle mb-0" x-data="{ expanded: [] }">
                        <thead class="bg-light sticky-top">
                            <tr class="text-center align-middle small text-uppercase text-secondary text-nowrap">
                                <th class="py-3 bg-light ps-3">No</th>
                                <th class="py-3 bg-light text-start">
                                    @php
                                        $col = 'no_register'; $label = 'No. Register';
                                        $curr = request('sort_by', 'created_at'); $ord = request('sort_order', 'desc');
                                        $newOrd = ($curr === $col && $ord === 'desc') ? 'asc' : 'desc';
                                        $icon = ($curr === $col) ? ($ord === 'desc' ? 'bi-sort-down text-primary' : 'bi-sort-up text-primary') : 'bi-arrow-down-up text-muted opacity-25';
                                        $url = request()->fullUrlWithQuery(['sort_by' => $col, 'sort_order' => $newOrd]);
                                    @endphp
                                    <a href="{{ $url }}" class="text-decoration-none text-secondary fw-bold d-flex align-items-center justify-content-between gap-2">{{ $label }} <i class="bi {{ $icon }}"></i></a>
                                </th>
                                <th class="py-3 bg-light text-start">
                                    @php
                                        $col = 'nama_tersangka'; $label = 'Tersangka';
                                        $curr = request('sort_by', 'created_at'); $ord = request('sort_order', 'desc');
                                        $newOrd = ($curr === $col && $ord === 'desc') ? 'asc' : 'desc';
                                        $icon = ($curr === $col) ? ($ord === 'desc' ? 'bi-sort-down text-primary' : 'bi-sort-up text-primary') : 'bi-arrow-down-up text-muted opacity-25';
                                        $url = request()->fullUrlWithQuery(['sort_by' => $col, 'sort_order' => $newOrd]);
                                    @endphp
                                    <a href="{{ $url }}" class="text-decoration-none text-secondary fw-bold d-flex align-items-center justify-content-between gap-2">{{ $label }} <i class="bi {{ $icon }}"></i></a>
                                </th>
                                <th class="py-3 bg-light text-start">Instansi & Barang Bukti</th>
                                <th class="py-3 bg-light text-center">Status</th>
                                <th class="py-3 bg-light">Dibuat</th>
                                <th class="py-3 bg-light pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @forelse($data as $row)
                            <tr class="align-middle {{ $loop->even ? 'bg-white' : 'bg-light bg-opacity-25' }}" :class="expanded.includes({{ $row->id }}) ? 'bg-light' : ''">
                                <td class="text-center fw-bold text-secondary ps-3">{{ $data->firstItem() + $loop->index }}</td>
                                <td class="text-start">
                                    <span class="fw-bold text-dark d-block">{{ $row->no_register }}</span>
                                    <small class="text-muted text-nowrap"><i class="bi bi-calendar-event me-1"></i>{{ $row->tanggal_pelaksanaan->format('d M Y') }}</small>
                                    <div class="mt-1"><span class="badge bg-light text-secondary border">{{ $row->satuanKerja->satuan_kerja ?? '-' }}</span></div>
                                </td>
                                <td class="text-start" style="max-width: 200px;">
                                    <a href="#" class="text-decoration-none fw-semibold text-primary d-block text-truncate" @click.prevent="expanded.includes({{ $row->id }}) ? expanded = expanded.filter(id => id !== {{ $row->id }}) : expanded.push({{ $row->id }})">
                                        {{ Str::limit($row->nama_tersangka, 50) }}
                                    </a>
                                    <div class="small text-muted mt-1">{{ $row->jenis_kelamin }} / {{ $row->usia }} Thn</div>
                                </td>
                                <td class="text-start">
                                    <div class="text-dark mb-1">{{ $row->instansi_pengirim }}</div>
                                    <div class="small text-muted">{{ $row->jenis_narkoba }} @if($row->jumlah_satuan) <span class="text-secondary">({{ $row->jumlah_satuan }} g)</span> @endif</div>
                                </td>
                                <td class="text-center">
                                    @if($row->proses_hukum_lanjut)
                                        <span class="badge rounded-pill bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1">{{ Str::limit($row->proses_hukum_lanjut, 20) }}</span>
                                    @else <span class="text-muted small">-</span> @endif
                                </td>
                                <td class="text-center small text-muted text-nowrap">{{ $row->created_at->format('d M Y') }}</td>
                                <td class="text-center pe-3">
                                    <div class="btn-group btn-group-sm shadow-sm">
                                        <button type="button" class="btn btn-light border text-secondary" @click="expanded.includes({{ $row->id }}) ? expanded = expanded.filter(id => id !== {{ $row->id }}) : expanded.push({{ $row->id }})" title="Lihat Detail"><i class="bi" :class="expanded.includes({{ $row->id }}) ? 'bi-chevron-up text-primary' : 'bi-eye text-secondary'"></i></button>
                                        <a href="{{ route('berantas.tat.edit', $row->id) }}" class="btn btn-light border text-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                        @if(auth()->user()->isOperator())
                                            <button type="button" class="btn btn-light border text-danger" onclick="confirmDelete({{ $row->id }})" title="Hapus"><i class="bi bi-trash"></i></button>
                                            <form id="delete-form-{{ $row->id }}" action="{{ route('berantas.tat.destroy', $row->id) }}" method="POST" class="d-none">@csrf @method('DELETE')</form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            <tr x-show="expanded.includes({{ $row->id }})" x-transition.opacity.duration.300ms>
                                <td colspan="7" class="p-0 border-0">
                                    <div class="bg-body-tertiary p-4 border-bottom shadow-inner text-start">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-body">
                                                <div class="row g-4">
                                                    <div class="col-lg-6 border-end-lg">
                                                        <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-1">Data Tersangka</h6>
                                                        <dl class="row mb-0 small text-start g-1">
                                                            <dt class="col-sm-4 text-muted">Nama Lengkap</dt><dd class="col-sm-8 fw-semibold text-dark text-pre-wrap">{{ $row->nama_tersangka }}</dd>
                                                            <dt class="col-sm-4 text-muted">NIK</dt><dd class="col-sm-8">{{ $row->nik ?? '-' }}</dd>
                                                            <dt class="col-sm-4 text-muted">Pendidikan</dt><dd class="col-sm-8">{{ $row->pendidikan }}</dd>
                                                            <dt class="col-sm-4 text-muted">Pekerjaan</dt><dd class="col-sm-8">{{ $row->pekerjaan ?? '-' }}</dd>
                                                            <dt class="col-sm-4 text-muted">No. Telepon</dt><dd class="col-sm-8">{{ $row->no_telepon ?? '-' }}</dd>
                                                        </dl>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-1">Kasus & Asesmen</h6>
                                                        <dl class="row mb-0 small text-start g-1">
                                                            <dt class="col-sm-4 text-muted">Pasal</dt><dd class="col-sm-8 text-pre-wrap">{{ $row->pasal_disangkakan }}</dd>
                                                            <dt class="col-sm-4 text-muted">Tim Hukum</dt><dd class="col-sm-8 text-pre-wrap">{{ $row->tim_hukum }}</dd>
                                                            <dt class="col-sm-4 text-muted">Tim Medis</dt><dd class="col-sm-8 text-pre-wrap">{{ $row->tim_medis }}</dd>
                                                            <dt class="col-sm-4 text-muted">Lembaga Rehab</dt><dd class="col-sm-8">{{ $row->lembaga_rehab ?? '-' }}</dd>
                                                            <dt class="col-sm-4 text-muted">Tindak Lanjut</dt><dd class="col-sm-8">{{ $row->tindak_lanjut_rekomendasi ?? '-' }}</dd>
                                                            <dt class="col-sm-4 text-muted">Biaya</dt><dd class="col-sm-8 fw-bold text-success">Rp {{ number_format($row->biaya, 0, ',', '.') }}</dd>
                                                        </dl>
                                                    </div>
                                                    <div class="col-12 mt-3">
                                                        <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-1">Lampiran</h6>
                                                        @if($row->dokumentasi->count() > 0)
                                                            <div class="d-flex flex-wrap gap-2">
                                                                @foreach($row->dokumentasi as $doc)
                                                                    <a href="{{ Storage::url($doc->path_file) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark me-1"></i>{{ $doc->nama_file_asli }}</a>
                                                                @endforeach
                                                            </div>
                                                        @else <div class="text-muted small fst-italic">Tidak ada lampiran.</div> @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center py-5 text-muted fst-italic">Belum ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                {{-- Pagination --}}
                <div class="d-flex justify-content-between align-items-center p-3">
                    <div class="text-muted small">Total: {{ $data->total() }} Data</div>
                    <div>{{ $data->withQueryString()->links() }}</div>
                </div>
            </div>
        </div>

    </div>
</main>
@endsection

@push('styles')
<style>
    .ts-dropdown, .ts-dropdown.single { z-index: 2000 !important; }
    .ts-control { border: none !important; padding: 0.5rem; background: transparent; }
    .custom-table-scroll { max-height: 70vh; overflow-y: auto; }
    .custom-table-scroll thead th { position: sticky; top: 0; z-index: 10; }
    .text-pre-wrap { white-space: pre-wrap; }
    .border-end-lg { border-right: 1px solid #dee2e6; }
    @media (max-width: 992px) { .border-end-lg { border-right: none; } }
</style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        const configTomSelect = { plugins: ['remove_button', 'clear_button'], persist: false, create: false, maxOptions: null };
        const ids = ['select-satker', 'select-bulan', 'select-tahun'];
        ids.forEach(id => { if(document.getElementById(id)) new TomSelect('#' + id, configTomSelect); });
    });
    window.confirmDelete = function(id) {
        Swal.fire({
            title: 'Hapus Data?', text: "Data ini akan dihapus permanen.", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal'
        }).then((result) => { if (result.isConfirmed) document.getElementById('delete-form-' + id).submit(); });
    }
</script>
@endpush