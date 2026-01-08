@extends('admin')

@section('content')
<main class="admin-main">
    <div class="container-fluid p-4 p-lg-5">

        {{-- HEADER JUDUL --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Master Narkotika</h1>
                <p class="text-muted mb-0">Kelola daftar nama dan golongan narkotika</p>
            </div>
            <button type="button" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="bi bi-plus-lg"></i> Tambah Narkotika
            </button>
        </div>

        {{-- 1. ALERT SUKSES (HIJAU) --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2 fs-4"></i>
                    <div class="ms-2"><strong>Berhasil!</strong> {{ session('success') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- 2. ALERT ERROR / GAGAL HAPUS (MERAH) --}}
        {{-- Ini akan muncul jika Try-Catch di controller menangkap Foreign Key error --}}
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-octagon-fill me-2 fs-4"></i>
                    <div class="ms-2">
                        <strong>Tidak Bisa Dihapus!</strong><br>
                        {{ session('error') }}
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- 3. ALERT VALIDASI FORM --}}
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div>
                        <strong>Terjadi Kesalahan Input!</strong>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- CARD DATA --}}
        <div class="card border-0 shadow-sm">
            
            {{-- BAGIAN ATAS: PENCARIAN & RESET --}}
            <div class="card-header bg-white py-3">
                <div class="row">
                    <div class="col-md-6 ms-auto">
                        <form action="{{ url()->current() }}" method="GET">
                            {{-- Pertahankan filter lain saat searching --}}
                            <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                            <input type="hidden" name="sort_by" value="{{ request('sort_by', 'created_at') }}">
                            <input type="hidden" name="sort_direction" value="{{ request('sort_direction', 'desc') }}">

                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Cari nama atau golongan..." value="{{ request('search') }}">
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-search"></i> Cari
                                </button>
                                
                                {{-- Tombol Reset hanya muncul jika ada filter --}}
                                @if(request()->has('search') || request()->has('sort_by'))
                                    <a href="{{ route('berantas.narkotika.index') }}" class="btn btn-outline-danger" title="Reset Filter">
                                        <i class="bi bi-x-circle"></i> Reset
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="py-3 px-4" width="5%">No</th>
                                
                                {{-- HEADER: NAMA (SORTABLE) --}}
                                <th class="py-3">
                                    <a href="{{ request()->fullUrlWithQuery([
                                        'sort_by' => 'nama_narkotika', 
                                        'sort_direction' => request('sort_by') === 'nama_narkotika' && request('sort_direction') === 'asc' ? 'desc' : 'asc'
                                    ]) }}" class="text-decoration-none text-dark d-flex align-items-center gap-1">
                                        Nama Narkotika
                                        @if(request('sort_by') === 'nama_narkotika')
                                            <i class="bi bi-sort-{{ request('sort_direction') === 'asc' ? 'down' : 'up' }}"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up text-muted opacity-25 small"></i>
                                        @endif
                                    </a>
                                </th>

                                {{-- HEADER: GOLONGAN (SORTABLE) --}}
                                <th class="py-3">
                                    <a href="{{ request()->fullUrlWithQuery([
                                        'sort_by' => 'golongan', 
                                        'sort_direction' => request('sort_by') === 'golongan' && request('sort_direction') === 'asc' ? 'desc' : 'asc'
                                    ]) }}" class="text-decoration-none text-dark d-flex align-items-center gap-1">
                                        Golongan
                                        @if(request('sort_by') === 'golongan')
                                            <i class="bi bi-sort-{{ request('sort_direction') === 'asc' ? 'down' : 'up' }}"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up text-muted opacity-25 small"></i>
                                        @endif
                                    </a>
                                </th>

                                {{-- HEADER: DIBUAT (SORTABLE - DEFAULT) --}}
                                <th class="py-3">
                                    <a href="{{ request()->fullUrlWithQuery([
                                        'sort_by' => 'created_at', 
                                        'sort_direction' => request('sort_by', 'created_at') === 'created_at' && request('sort_direction', 'desc') === 'asc' ? 'desc' : 'asc'
                                    ]) }}" class="text-decoration-none text-dark d-flex align-items-center gap-1">
                                        Dibuat
                                        @if(request('sort_by', 'created_at') === 'created_at')
                                            <i class="bi bi-sort-{{ request('sort_direction', 'desc') === 'asc' ? 'down' : 'up' }}"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up text-muted opacity-25 small"></i>
                                        @endif
                                    </a>
                                </th>

                                <th class="py-3 px-4 text-end" width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $item)
                                <tr>
                                    <td class="px-4 fw-bold text-secondary">{{ $data->firstItem() + $loop->index }}</td>
                                    
                                    <td class="fw-semibold text-dark">{{ $item->nama_narkotika }}</td>
                                    
                                    <td>
                                        @php
                                            $badgeClass = match($item->golongan) {
                                                'Golongan I' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                                'Golongan II' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                                'Golongan III' => 'bg-success-subtle text-success border border-success-subtle',
                                                default => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                                            };
                                        @endphp
                                        <span class="badge rounded-pill {{ $badgeClass }} fw-normal px-3 py-2">
                                            {{ $item->golongan }}
                                        </span>
                                    </td>

                                    {{-- Kolom Tanggal Dibuat --}}
                                    <td class="text-muted small">
                                        {{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-' }}
                                    </td>
                                    
                                    <td class="px-4 text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            {{-- EDIT --}}
                                            <button type="button" 
                                                    class="btn btn-sm btn-light border text-primary btn-edit"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editModal"
                                                    data-id="{{ $item->id }}"
                                                    data-nama="{{ $item->nama_narkotika }}"
                                                    data-golongan="{{ $item->golongan }}">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            {{-- HAPUS --}}
                                            <button type="button" 
                                                    class="btn btn-sm btn-light border text-danger" 
                                                    onclick="confirmDelete({{ $item->id }})">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <form id="delete-form-{{ $item->id }}" action="{{ route('berantas.narkotika.destroy', $item->id) }}" method="POST" class="d-none">
                                                @csrf @method('DELETE')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted fst-italic">
                                        <i class="bi bi-search fs-1 d-block mb-2 opacity-25"></i>
                                        Data tidak ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- BAGIAN BAWAH: FILTER PER PAGE (KIRI) & PAGINATION (KANAN) --}}
            <div class="card-footer bg-white py-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                    
                    {{-- Form Per Page --}}
                    <form action="{{ url()->current() }}" method="GET" class="d-flex align-items-center gap-2">
                        {{-- Hidden inputs agar search/sort tidak hilang saat ganti jumlah data --}}
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        <input type="hidden" name="sort_by" value="{{ request('sort_by', 'created_at') }}">
                        <input type="hidden" name="sort_direction" value="{{ request('sort_direction', 'desc') }}">

                        <select name="per_page" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                            <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                        </select>
                        <span class="text-muted small">Data / halaman</span>
                    </form>

                    {{-- Pagination Links --}}
                    <div>
                        {{ $data->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
            
        </div>
    </div>

    {{-- MODAL CREATE --}}
    <div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Tambah Narkotika</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('berantas.narkotika.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-secondary">Nama Narkotika <span class="text-danger">*</span></label>
                            <input type="text" name="nama_narkotika" class="form-control" placeholder="Contoh: Ganja, Sabu" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-secondary">Golongan <span class="text-danger">*</span></label>
                            <select name="golongan" class="form-select" required>
                                <option value="" disabled selected>Pilih Golongan...</option>
                                <option value="Golongan I">Golongan I</option>
                                <option value="Golongan II">Golongan II</option>
                                <option value="Golongan III">Golongan III</option>
                                <option value="Non Golongan">Non Golongan</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL EDIT --}}
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Narkotika</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-secondary">Nama Narkotika <span class="text-danger">*</span></label>
                            <input type="text" name="nama_narkotika" id="edit_nama" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-secondary">Golongan <span class="text-danger">*</span></label>
                            <select name="golongan" id="edit_golongan" class="form-select" required>
                                <option value="Golongan I">Golongan I</option>
                                <option value="Golongan II">Golongan II</option>
                                <option value="Golongan III">Golongan III</option>
                                <option value="Non Golongan">Non Golongan</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning px-4">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</main>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const editButtons = document.querySelectorAll('.btn-edit');
        const editForm = document.getElementById('editForm');
        const editNama = document.getElementById('edit_nama');
        const editGolongan = document.getElementById('edit_golongan');
        const baseUrl = "{{ route('berantas.narkotika.update', 0) }}";

        editButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const nama = this.getAttribute('data-nama');
                const golongan = this.getAttribute('data-golongan');

                editNama.value = nama;
                editGolongan.value = golongan;
                editForm.action = baseUrl.replace('/0', '/' + id);
            });
        });
    });

    window.confirmDelete = function(id) {
        Swal.fire({
            title: 'Hapus Data?',
            text: "Data narkotika ini akan dihapus permanen.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-' + id).submit();
            }
        });
    }
</script>
@endpush