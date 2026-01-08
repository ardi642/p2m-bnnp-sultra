@extends('admin')

@section('content')
<main class="admin-main">
    <div class="container-fluid p-4 p-lg-5">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Manajemen User</h1>
                <p class="text-muted mb-0">Kelola Akun Login Pegawai</p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                <i class="bi bi-person-plus-fill"></i> Tambah User
            </a>
        </div>

        <div class="card shadow-lg">
            <div class="card-body p-8">
                
                {{-- SEARCH & FILTER FORM --}}
                <form action="{{ route('admin.users.index') }}" method="GET" class="mb-4">
                    {{-- Hidden input per_page agar settingan jumlah data tidak reset saat search --}}
                    <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">

                    <div class="row g-3">
                        {{-- Filter Satker --}}
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
                        
                        {{-- Search Input --}}
                        <div class="col-md">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Cari Nama / NIP / Email..." value="{{ request('search') }}">
                                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Cari</button>
                                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary" title="Reset Filter"><i class="bi bi-arrow-clockwise"></i></a>
                            </div>
                        </div>
                    </div>
                </form>

                {{-- TABLE WRAPPER DENGAN SCROLL & STICKY HEADER --}}
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
                                    @if($user->role == 'admin')
                                        <span class="badge bg-danger">Super Admin</span>
                                    @elseif($user->role == 'admin_satker')
                                        <span class="badge bg-warning text-dark">Admin Satker</span>
                                    @else
                                        <span class="badge bg-info text-dark">Operator</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-2 justify-content-center">
                                        @if($user->role !== 'admin')
                                            {{-- RESET PASSWORD --}}
                                            <form action="{{ route('admin.users.reset_password', $user->id) }}" method="POST" onsubmit="return confirm('Reset password user ini ke default?');">
                                                @csrf @method('PUT')
                                                <button type="submit" class="btn btn-sm btn-dark" title="Reset Password"><i class="me-0 bi bi-arrow-counterclockwise"></i></button>
                                            </form>
                                            {{-- HAPUS USER --}}
                                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Hapus user ini?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Hapus User"><i class="me-0 bi bi-trash"></i></button>
                                            </form>
                                        @else
                                            <span class="text-muted small"><i class="bi bi-lock-fill"></i> Locked</span>
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

                {{-- PAGINATION & PER PAGE --}}
                <div class="d-flex justify-content-between align-items-center mt-4">
                    
                    {{-- BAGIAN KIRI: Dropdown Jumlah Data --}}
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small text-nowrap">Tampilkan</span>
                        
                        <select class="form-select form-select-sm border-secondary-subtle" 
                                style="width: auto;" 
                                onchange="window.location.href = this.value">
                            @foreach([10, 25, 50, 100] as $num)
                                <option value="{{ request()->fullUrlWithQuery(['per_page' => $num, 'page' => 1]) }}"
                                        {{ request('per_page') == $num ? 'selected' : '' }}>
                                    {{ $num }}
                                </option>
                            @endforeach
                        </select>
                        
                        <span class="text-muted small text-nowrap">data / halaman</span>
                    </div>

                    {{-- BAGIAN KANAN: Pagination Links --}}
                    <div>
                        {{ $users->links() }}
                    </div>
                    
                </div>
                {{-- END PAGINATION --}}

            </div>
        </div>
    </div>
</main>
@endsection

@push('styles') 
 
<style>
    /* CSS KHUSUS UNTUK TABEL SCROLL & STICKY */
    .custom-table-scroll {
        max-height: 60vh;       /* Batasi tinggi tabel */
        overflow-y: auto;       /* Munculkan scrollbar vertikal */
        position: relative;     /* Agar posisi sticky relative terhadap kotak ini */
        border: 1px solid #dee2e6; /* Border tipis pembatas area scroll */
    }

    /* Memaksa Header Diam di Tempat */
    .custom-table-scroll thead th {
        position: sticky !important;
        top: 0 !important;
        z-index: 2;
        
        /* PENTING: Warna background header agar tidak tembus pandang */
        background-color: #f8f9fa !important; 
        
        /* Garis bawah header agar tegas */
        box-shadow: inset 0 -1px 0 #dee2e6;
    }
</style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        if(document.getElementById('select-satker')) new TomSelect('#select-satker', { plugins: ['remove_button'] });
    });
</script>
@endpush