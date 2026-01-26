@extends('admin')

@section('content')
<main class="admin-main">
    <div class="container-fluid p-4 p-lg-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                
                {{-- Header --}}
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">Edit User Role</h1>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="card shadow-lg">
                    <div class="card-body p-5">
                        
                        {{-- INFO PEGAWAI (READONLY) --}}
                        <div class="alert alert-light border mb-4">
                            <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-person-badge me-2"></i>Informasi Akun Pegawai</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Nama Pegawai</small>
                                    <span class="fw-bold text-dark">{{ $targetUser->pegawai->nama ?? $targetUser->name }}</span>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">NIP</small>
                                    <span class="font-monospace">{{ $targetUser->pegawai->nip ?? '-' }}</span>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Email Login</small>
                                    <span>{{ $targetUser->email }}</span>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Satuan Kerja</small>
                                    <span>{{ $targetUser->pegawai->satuanKerja->satuan_kerja ?? 'Pusat' }}</span>
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('admin.users.update', $targetUser->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            {{-- PILIH ROLE --}}
                            <div class="mb-4">
                                <label class="form-label fw-bold">Role Akses <span class="text-danger">*</span></label>
                                <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                                    <option value="" disabled>Pilih Role...</option>
                                    
                                    {{-- OPSI UNTUK SUPER ADMIN --}}
                                    @if(auth()->user()->hasRole('admin'))
                                        <option value="admin_satker" @selected($targetUser->role == 'admin_satker')>Admin Satuan Kerja</option>
                                        <option value="admin_p2m" @selected($targetUser->role == 'admin_p2m')>Admin P2M</option>
                                        <option value="admin_berantas" @selected($targetUser->role == 'admin_berantas')>Admin Berantas</option>
                                        <option value="admin_rehab" @selected($targetUser->role == 'admin_rehab')>Admin Rehab</option>
                                        
                                        <option disabled>──────────</option>
                                        
                                        <option value="operator_satker" @selected($targetUser->role == 'operator_satker')>Operator Satuan Kerja</option>
                                        <option value="operator_p2m" @selected($targetUser->role == 'operator_p2m')>Operator P2M</option>
                                        <option value="operator_berantas" @selected($targetUser->role == 'operator_berantas')>Operator Berantas</option>
                                        <option value="operator_rehab" @selected($targetUser->role == 'operator_rehab')>Operator Rehab</option>
                                    @endif

                                    {{-- OPSI UNTUK ADMIN SATKER --}}
                                    @if(auth()->user()->hasRole('admin_satker'))
                                        <option value="admin_p2m" @selected($targetUser->role == 'admin_p2m')>Admin P2M</option>
                                        <option value="admin_berantas" @selected($targetUser->role == 'admin_berantas')>Admin Berantas</option>
                                        <option value="admin_rehab" @selected($targetUser->role == 'admin_rehab')>Admin Rehab</option>
                                        
                                        <option disabled>──────────</option>

                                        <option value="operator_satker" @selected($targetUser->role == 'operator_satker')>Operator Satuan Kerja</option>
                                        <option value="operator_p2m" @selected($targetUser->role == 'operator_p2m')>Operator P2M</option>
                                        <option value="operator_berantas" @selected($targetUser->role == 'operator_berantas')>Operator Berantas</option>
                                        <option value="operator_rehab" @selected($targetUser->role == 'operator_rehab')>Operator Rehab</option>
                                    @endif

                                </select>
                                @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text text-muted">
                                    Mengubah role akan mengubah hak akses user ini di dalam sistem.
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-warning text-dark"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection