@extends('admin')

@section('content')
<main class="admin-main">
    <div class="container-fluid p-4 p-lg-5">
        
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Manajemen User</h1>
                <p class="text-muted mb-0">Kelola Akun Login Pegawai</p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                <i class="bi bi-person-plus-fill"></i> Tambah User
            </a>
        </div>

        {{-- ALERT SESSION (Notifikasi Sukses/Gagal dari Controller) --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card shadow-lg">
            <div class="card-body p-8">
                
                {{-- SEARCH & FILTER FORM --}}
                <form action="{{ route('admin.users.index') }}" method="GET" class="mb-4">
                    <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">

                    <div class="row g-3">
                        @if(auth()->user()->role === 'admin')
                        <div class="col-md-4">
                            <select id="select-satker" name="satuan_kerja_id[]" multiple placeholder="Filter Satker...">
                                @foreach($satuanKerjas as $s)
                                    <option value="{{ $s->id }}" {{ in_array($s->id, request('satuan_kerja_id', [])) ? 'selected' : '' }}>
                                        {{ $s->satuan_kerja }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        
                        <div class="col-md">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Cari Nama / NIP / Email..." value="{{ request('search') }}">
                                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Cari</button>
                                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary" title="Reset Filter"><i class="bi bi-arrow-clockwise"></i></a>
                            </div>
                        </div>
                    </div>
                </form>

                {{-- TABLE WRAPPER --}}
                <div class="custom-table-scroll mb-3">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nama / NIP</th>
                                <th>Satuan Kerja</th>
                                <th>Email Login</th>
                                <th>Role</th>
                                <th class="text-center" style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                            <tr>
                                <td>{{ $users->firstItem() + $loop->index }}</td>
                                <td>
                                    @if($user->pegawai)
                                        <div class="fw-bold">{{ $user->pegawai->nama }}</div>
                                        <small class="text-muted">{{ $user->pegawai->nip }}</small>
                                    @else
                                        <div class="fw-bold text-primary">{{ $user->name }}</div>
                                        <small class="text-muted">Administrator Pusat</small>
                                    @endif
                                </td>
                                <td>
                                    @if($user->pegawai && $user->pegawai->satuanKerja)
                                        {{ $user->pegawai->satuanKerja->satuan_kerja }}
                                    @else
                                        <span class="badge bg-secondary">Pusat / BNN RI</span>
                                    @endif
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @switch($user->role)
                                        @case('admin') <span class="badge rounded-pill bg-danger">Super Admin</span> @break
                                        @case('admin_satker') <span class="badge rounded-pill bg-primary">Admin Satker</span> @break
                                        @case('operator_satker') <span class="badge rounded-pill bg-info text-dark">Operator Satker</span> @break
                                        @case('admin_p2m') <span class="badge rounded-pill bg-success">Admin P2M</span> @break
                                        @case('operator_p2m') <span class="badge rounded-pill" style="background-color: #20c997;">Operator P2M</span> @break
                                        @case('admin_berantas') <span class="badge rounded-pill bg-dark">Admin Berantas</span> @break
                                        @case('operator_berantas') <span class="badge rounded-pill bg-secondary">Operator Berantas</span> @break
                                        @case('admin_rehab') <span class="badge rounded-pill" style="background-color: #fd7e14;">Admin Rehab</span> @break
                                        @case('operator_rehab') <span class="badge rounded-pill bg-warning text-dark">Operator Rehab</span> @break
                                        @default <span class="badge rounded-pill bg-light text-dark border">{{ $user->role }}</span>
                                    @endswitch
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-2 justify-content-center">
                                        
                                        @php
                                            $currentUser = auth()->user();
                                            $isSuperAdmin  = $currentUser->role === 'admin';
                                            $isAdminSatker = $currentUser->role === 'admin_satker' && $user->role !== 'admin';
                                            $isMyOperatorP2M = ($currentUser->role === 'admin_p2m' && $user->role === 'operator_p2m');
                                            $isMyOperatorBerantas = ($currentUser->role === 'admin_berantas' && $user->role === 'operator_berantas');
                                            $isMyOperatorRehab = ($currentUser->role === 'admin_rehab' && $user->role === 'operator_rehab');

                                            $isAuthorizedToModify = $isSuperAdmin || $isAdminSatker || $isMyOperatorP2M || $isMyOperatorBerantas || $isMyOperatorRehab;
                                        @endphp

                                        {{-- TOMBOL EDIT --}}
                                        @if(($isSuperAdmin || $isAdminSatker) && $currentUser->id !== $user->id)
                                            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-warning text-dark" title="Edit Role">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                        @endif

                                        {{-- TOMBOL RESET & HAPUS (PAKAI SWEETALERT) --}}
                                        @if($isAuthorizedToModify && $currentUser->id !== $user->id)
                                            
                                            {{-- Form Reset Password --}}
                                            <form action="{{ route('admin.users.reset_password', $user->id) }}" method="POST" class="form-reset">
                                                @csrf @method('PUT')
                                                <button type="submit" class="btn btn-sm btn-dark" title="Reset Password">
                                                    <i class="me-0 bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            </form>

                                            {{-- Form Hapus User --}}
                                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="form-delete">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Hapus User">
                                                    <i class="me-0 bi bi-trash"></i>
                                                </button>
                                            </form>

                                        @endif

                                        {{-- Indikator Terkunci --}}
                                        @if(!($isSuperAdmin || $isAdminSatker) && !$isAuthorizedToModify)
                                            <span class="text-muted small" title="Akses Terkunci"><i class="bi bi-lock-fill"></i></span>
                                        @endif

                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada data user.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- PAGINATION --}}
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small text-nowrap">Tampilkan</span>
                        <select class="form-select form-select-sm border-secondary-subtle" style="width: auto;" onchange="window.location.href = this.value">
                            @foreach([10, 25, 50, 100] as $num)
                                <option value="{{ request()->fullUrlWithQuery(['per_page' => $num, 'page' => 1]) }}" {{ request('per_page') == $num ? 'selected' : '' }}>{{ $num }}</option>
                            @endforeach
                        </select>
                        <span class="text-muted small text-nowrap">data / halaman</span>
                    </div>
                    <div>{{ $users->links() }}</div>
                </div>

            </div>
        </div>
    </div>
</main>
@endsection

@push('styles') 
<style>
    .custom-table-scroll {
        max-height: 60vh;
        overflow-y: auto;
        position: relative;
        border: 1px solid #dee2e6;
    }
    .custom-table-scroll thead th {
        position: sticky !important;
        top: 0 !important;
        z-index: 2;
        background-color: #f8f9fa !important; 
        box-shadow: inset 0 -1px 0 #dee2e6;
    }
</style>
@endpush

@push('scripts')
{{-- IMPORT SWEETALERT2 DARI CDN --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        if(document.getElementById('select-satker')) new TomSelect('#select-satker', { plugins: ['remove_button'] });

        // --- LOGIKA SWEETALERT DELETE ---
        const deleteForms = document.querySelectorAll('.form-delete');
        deleteForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Cegah submit langsung
                
                Swal.fire({
                    title: 'Hapus User ini?',
                    text: "Data yang dihapus tidak dapat dikembalikan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit(); // Submit form manual jika user klik Ya
                    }
                });
            });
        });

        // --- LOGIKA SWEETALERT RESET PASSWORD ---
        const resetForms = document.querySelectorAll('.form-reset');
        resetForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); 
                
                Swal.fire({
                    title: 'Reset Password?',
                    text: "Password user akan kembali ke default ('password').",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6', // Warna biru
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Reset!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });

    });
</script>
@endpush