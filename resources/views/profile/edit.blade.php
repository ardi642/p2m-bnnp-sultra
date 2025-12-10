@extends('admin') 

@section('content')
<main class="admin-main">
    <div class="container-fluid p-4 p-lg-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                
                {{-- HEADER --}}
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-0">Profil Saya</h1>
                        <p class="text-muted mb-0">Kelola informasi akun dan keamanan</p>
                    </div>
                </div>

                {{-- ================================================= --}}
                {{-- FLASH MESSAGES (Sukses / Error / Info)            --}}
                {{-- ================================================= --}}
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show mb-4 shadow-sm" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert">
                        <i class="bi bi-exclamation-octagon-fill me-2"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('info'))
                    <div class="alert alert-info alert-dismissible fade show mb-4 shadow-sm" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i> {{ session('info') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
    
                {{-- CARD 1: INFORMASI AKUN --}}
                <div class="card shadow-sm mb-4 border-0">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="mb-0 fw-bold text-primary">
                            <i class="bi bi-person-badge me-2"></i> Informasi Akun
                        </h5>
                    </div>
                    <div class="card-body p-4">

                        {{-- 
                            1. ALERT PENDING EMAIL (Ditaruh DI LUAR form utama)
                        --}}
                        @if($user->pending_email)
                            <div class="alert alert-warning mb-4 d-flex align-items-start" role="alert">
                                <i class="bi bi-shield-lock fs-4 me-3"></i>
                                <div class="flex-grow-1">
                                    <strong>Konfirmasi Diperlukan!</strong><br>
                                    Anda meminta perubahan email menjadi: 
                                    <span class="fw-bold text-dark">{{ $user->pending_email }}</span>.
                                    <br>
                                    Sebuah link konfirmasi telah dikirim ke <strong>{{ $user->email }}</strong> (email Anda saat ini).
                                    Silakan cek inbox email lama Anda untuk menyetujui perubahan ini.
                                    
                                    <div class="mt-2">
                                        <form action="{{ route('profile.email.cancel') }}" method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-x-circle"></i> Batalkan
                                            </button>
                                        </form>

                                        <form action="{{ route('profile.email.resend') }}" method="POST" class="d-inline ms-1">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-link text-decoration-none">Kirim Ulang ke Email Lama</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif
                        {{-- END ALERT PENDING EMAIL --}}

                        {{-- 
                            2. FORM UTAMA (Update Profil)
                        --}}
                        <form action="{{ route('profile.update') }}" method="POST">
                            @csrf
                            @method('PATCH')
    
                            {{-- Nama Lengkap --}}
                            <div class="row mb-3">
                                <label class="col-md-4 col-form-label text-md-end fw-bold">Nama Lengkap</label>
                                <div class="col-md-6">
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                                           value="{{ old('name', $user->name) }}" required>
                                    
                                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    
                                    <div class="form-text text-muted small">
                                        @if($user->pegawai)
                                            <i class="bi bi-info-circle"></i> Perubahan nama akan disinkronkan ke data Pegawai.
                                        @else
                                            Nama tampilan akun administrator.
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- NIP (TAMPILAN BARU: TEKS BIASA) --}}
                            @if($user->pegawai)
                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label text-md-end text-muted">NIP</label>
                                    <div class="col-md-6 pt-2"> {{-- pt-2 agar sejajar dengan label --}}
                                        {{-- Gunakan Teks Tebal, bukan Input --}}
                                        <div class="fw-bold text-dark fs-6 font-monospace">
                                            {{ $user->pegawai->nip }}
                                        </div>
                                        
                                        {{-- Keterangan Khusus --}}
                                        <div class="form-text text-muted small mt-1">
                                            <i class="bi bi-lock-fill"></i> Data ini hanya bisa diubah oleh Admin Pusat atau Admin Satuan Kerja
                                        </div>
                                    </div>
                                </div>
                            @endif
    
                            {{-- Hak Akses (Role) --}}
                            <div class="row mb-3">
                                <label class="col-md-4 col-form-label text-md-end text-muted">Hak Akses (Role)</label>
                                <div class="col-md-6 d-flex align-items-center">
                                    @if($user->role == 'admin')
                                        <span class="badge bg-danger rounded-pill px-3 py-2">
                                            <i class="bi bi-shield-lock-fill me-1"></i> Super Admin
                                        </span>
                                    @elseif($user->role == 'admin_satker')
                                        <span class="badge bg-warning text-dark rounded-pill px-3 py-2">
                                            <i class="bi bi-building-lock me-1"></i> Admin Satker
                                        </span>
                                    @else
                                        <span class="badge bg-info text-dark rounded-pill px-3 py-2">
                                            <i class="bi bi-person-workspace me-1"></i> Operator
                                        </span>
                                    @endif
                                </div>
                            </div>
    
                            {{-- Alamat Email --}}
                            <div class="row mb-3">
                                <label class="col-md-4 col-form-label text-md-end fw-bold">Alamat Email</label>
                                <div class="col-md-6">
                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                           value="{{ old('email', $user->email) }}" required>
                                    
                                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <div class="form-text">Email ini digunakan untuk login.</div>
                                </div>
                            </div>
    
                            <div class="row mb-0">
                                <div class="col-md-6 offset-md-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-1"></i> Simpan Profil
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
    
                {{-- CARD 2: KEAMANAN (Password) --}}
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="mb-0 fw-bold {{ auth()->user()->is_password_default ? 'text-danger' : 'text-primary' }}">
                            <i class="bi bi-shield-lock me-2"></i> Keamanan Password
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        
                        @if(auth()->user()->is_password_default)
                            <div class="alert alert-danger d-flex align-items-center mb-4">
                                <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                                <div>
                                    <strong>Peringatan!</strong> Akun Anda masih menggunakan password default. Segera ganti untuk keamanan.
                                </div>
                            </div>
                        @endif
    
                        <form action="{{ route('profile.password.update') }}" method="POST">
                            @csrf
                            @method('PUT')
    
                            <div class="row mb-3">
                                <label class="col-md-4 col-form-label text-md-end">Password Saat Ini</label>
                                <div class="col-md-6">
                                    <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                                    @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
    
                            <div class="row mb-3">
                                <label class="col-md-4 col-form-label text-md-end">Password Baru</label>
                                <div class="col-md-6">
                                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
    
                            <div class="row mb-3">
                                <label class="col-md-4 col-form-label text-md-end">Konfirmasi Password</label>
                                <div class="col-md-6">
                                    <input type="password" name="password_confirmation" class="form-control" required>
                                </div>
                            </div>
    
                            <div class="row mb-0">
                                <div class="col-md-6 offset-md-4">
                                    <button type="submit" class="btn btn-warning text-dark">
                                        Update Password
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
    
            </div>
        </div>
    </div>
</main>
@endsection