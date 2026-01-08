@extends('admin')

@section('content')
<main class="admin-main">
    <div class="container-fluid p-4">
        
        {{-- 1. ALERT SUCCESS (BOOTSTRAP DISMISSIBLE) --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle-fill fs-5"></i>
                    <div><strong>Berhasil!</strong> {{ session('success') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- ALERT ERROR --}}
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    <div><strong>Gagal!</strong> {{ session('error') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- 2. HEADER HALAMAN --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Data Pegawai</h1>
                <p class="text-muted mb-0">Kelola Data Pegawai Instansi</p>
            </div>
            
            <a href="{{ route('admin.pegawai.create') }}" class="btn btn-primary px-4 shadow-sm">
                <i class="bi bi-person-plus-fill me-2"></i> Tambah Pegawai
            </a>
        </div>

        {{-- 3. CARD WRAPPER --}}
        <div class="card shadow-lg">
            <div class="card-body p-8">
                
                {{-- FORM PENCARIAN & FILTER --}}
                <form action="{{ route('admin.pegawai.index') }}" method="GET" class="mb-4">
                    {{-- Hidden inputs --}}
                    <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                    <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                    <input type="hidden" name="sort_order" value="{{ request('sort_order') }}">

                    <div class="row g-3">
                        
                        {{-- BAGIAN KIRI: FILTER SATKER --}}
                        <div class="col-md-5">
                            @if(auth()->user()->role === 'admin')
                                {{-- ADMIN UTAMA: Dropdown --}}
                                <select id="select-satker" name="satuan_kerja_id[]" multiple placeholder="Filter Satuan Kerja...">
                                    @foreach($satuanKerjas as $satker)
                                        <option value="{{ $satker->id }}" {{ in_array($satker->id, request('satuan_kerja_id', [])) ? 'selected' : '' }}>
                                            {{ $satker->satuan_kerja }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                {{-- ADMIN SATKER: Info Statis --}}
                                <div class="d-flex align-items-center px-3 border rounded bg-light text-secondary" style="height: 38px;">
                                    <i class="bi bi-building-lock me-2"></i>
                                    <span class="small fw-bold text-uppercase me-2">Data Satker:</span>
                                    <span class="fw-bold text-dark text-truncate">
                                        {{ auth()->user()->pegawai->satuanKerja->satuan_kerja ?? 'Satuan Kerja Anda' }}
                                    </span>
                                </div>
                            @endif
                        </div>
                        
                        {{-- BAGIAN KANAN: PENCARIAN --}}
                        <div class="col-md-7">
                            <div class="input-group">
                                <span class="input-group-text bg-white text-secondary border-end-0"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control border-start-0 ps-0" 
                                       placeholder="Cari Nama, NIP, Email..." 
                                       value="{{ request('search') }}">
                                <button class="btn btn-primary px-4" type="submit">Cari</button>
                                <a href="{{ route('admin.pegawai.index') }}" class="btn btn-secondary px-3" title="Reset Filter">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </form>

                {{-- 4. TABEL (STICKY & SORTABLE) --}}
                <div class="custom-table-scroll mb-3 border rounded">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="text-center align-middle">
                                <th style="width: 50px;" class="py-3">No</th>
                                
                                {{-- SORT BY NAMA --}}
                                <th class="text-start py-3" style="min-width: 200px;">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'nama', 'sort_order' => request('sort_by') == 'nama' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" 
                                       class="text-decoration-none text-dark d-flex align-items-center gap-1"
                                       title="Urutkan Nama">
                                        Nama & NIP
                                        @if(request('sort_by') == 'nama')
                                            <i class="bi bi-sort-alpha-{{ request('sort_order') == 'asc' ? 'down' : 'up' }} text-primary"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up text-secondary opacity-25 small"></i>
                                        @endif
                                    </a>
                                </th>

                                {{-- SORT BY EMAIL --}}
                                <th class="text-start py-3" style="min-width: 200px;">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'email', 'sort_order' => request('sort_by') == 'email' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" 
                                       class="text-decoration-none text-dark d-flex align-items-center gap-1"
                                       title="Urutkan Email">
                                        Kontak
                                        @if(request('sort_by') == 'email')
                                            <i class="bi bi-sort-alpha-{{ request('sort_order') == 'asc' ? 'down' : 'up' }} text-primary"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up text-secondary opacity-25 small"></i>
                                        @endif
                                    </a>
                                </th>

                                <th class="py-3" style="min-width: 150px;">Satuan Kerja</th>
                                <th class="py-3" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pegawais as $index => $pegawai)
                            <tr>
                                <td class="text-center">{{ $pegawais->firstItem() + $index }}</td>
                                
                                {{-- NAMA & NIP --}}
                                <td>
                                    <div class="fw-bold text-dark">{{ $pegawai->nama }}</div>
                                    <div class="text-muted small font-monospace">
                                        <i class="bi bi-person-badge me-1"></i>{{ $pegawai->nip }}
                                    </div>
                                </td>

                                {{-- KONTAK --}}
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-envelope text-secondary small"></i>
                                            <span class="text-muted small text-truncate" style="max-width: 180px;">
                                                {{ $pegawai->email }}
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-whatsapp text-secondary small"></i>
                                            <span class="text-muted small">
                                                {{ $pegawai->nomor_hp }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                {{-- SATKER --}}
                                <td class="text-center">
                                    <span class="badge bg-light text-secondary border fw-normal">
                                        {{ $pegawai->satuanKerja->satuan_kerja ?? '-' }}
                                    </span>
                                </td>

                                {{-- AKSI --}}
                                <td class="text-center">
                                    <div class="d-flex gap-2 justify-content-center">
                                        <a href="{{ route('admin.pegawai.edit', $pegawai->nip) }}" class="btn btn-sm btn-warning text-dark" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        
                                        <form id="delete-form-{{ $pegawai->nip }}" action="{{ route('admin.pegawai.destroy', $pegawai->nip) }}" method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete('{{ $pegawai->nip }}')" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <div class="mb-2"><i class="bi bi-folder-x display-4 opacity-50"></i></div>
                                    Data pegawai tidak ditemukan.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- 5. FOOTER & PAGINATION --}}
                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small text-nowrap">Tampilkan</span>
                        <select class="form-select form-select-sm border-secondary-subtle" style="width: auto;" onchange="window.location.href = this.value">
                            @foreach([10, 25, 50, 100] as $num)
                                <option value="{{ request()->fullUrlWithQuery(['per_page' => $num, 'page' => 1]) }}" 
                                    {{ request('per_page') == $num ? 'selected' : '' }}>{{ $num }}</option>
                            @endforeach
                        </select>
                        <span class="text-muted small text-nowrap">data</span>
                    </div>
                    <div>
                        {{ $pegawais->appends(request()->query())->links() }}
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>
@endsection

@push('styles')
 
<style>
    /* CSS Tabel Scroll & Sticky */
    .custom-table-scroll { max-height: 65vh; overflow-y: auto; position: relative; }
    
    .custom-table-scroll thead th { 
        position: sticky !important; 
        top: 0 !important; 
        z-index: 2; 
        background-color: #f8f9fa !important; 
        box-shadow: inset 0 -1px 0 #dee2e6; 
        vertical-align: middle;
        white-space: nowrap; 
    }

    /* Penyesuaian Tom Select agar sejajar dengan input normal (bukan lg) */
    .ts-control { 
        padding: 0.375rem 0.75rem !important; 
        min-height: 38px; 
    }
</style>
@endpush

@push('scripts')
{{-- Load Scripts --}}
{{-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> --}}

<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        if(document.getElementById('select-satker')) {
            new TomSelect('#select-satker', {
                plugins: ['remove_button'],
                placeholder: 'Filter Satuan Kerja...',
                allowEmptyOption: true
            });
        }
    });
</script>

<script>
    window.confirmDelete = function(nip) {
        Swal.fire({
            title: 'Hapus Pegawai?',
            text: "Data NIP " + nip + " akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) { document.getElementById('delete-form-' + nip).submit(); }
        });
    }
</script>
@endpush