<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - BNNP Sultra</title>
    <link rel="icon" type="image/png" href="{{ asset("assets/favicon-B_cwPWBd.png") }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" crossorigin href="{{ asset("assets/main-DLfE7m78.css") }}">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f6f8; }
        .login-card { border: none; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
        .form-control { padding: 0.75rem 1rem; }
        .btn-primary { padding: 0.75rem 1rem; font-weight: 600; }
        .info-box { background-color: #eef2ff; border: 1px solid #c7d2fe; color: #3730a3; font-size: 0.85rem; border-radius: 6px; padding: 12px; margin-top: 1.5rem; }
    </style>
<body>

    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5 col-lg-4">
                <div class="card login-card p-3">
                    
                    <div class="card-header text-center bg-white border-0 pb-0">
                        <img src="{{ asset("assets/logo-bnn.png") }}" alt="Logo BNN" class="bnn-logo mb-3" height="70">
                        <h4 class="fw-bold text-dark">Reset Password</h4>
                        <p class="text-muted small">Masukkan NIP atau Email akun Anda untuk menerima link reset password.</p>
                    </div>

                    <div class="card-body p-4 pt-3">
                        
                        {{-- Alert Sukses / Gagal --}}
                        @if (session('status'))
                            <div class="alert alert-success small shadow-sm border-0" role="alert">
                                <i class="bi bi-check-circle-fill me-1"></i> {{ session('status') }}
                            </div>
                        @endif
                        @if ($errors->any())
                            <div class="alert alert-danger small shadow-sm border-0" role="alert">
                                <i class="bi bi-exclamation-circle-fill me-1"></i> {{ $errors->first() }}
                            </div>
                        @endif

                        <form action="{{ route('password.email') }}" method="POST">
                            @csrf
                            
                            <div class="mb-4">
                                <label class="form-label small text-muted fw-bold text-uppercase">NIP / Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control border-start-0 ps-0 @error('login_id') is-invalid @enderror" 
                                           name="login_id" 
                                           value="{{ old('login_id') }}" 
                                           placeholder="Contoh: 1980... atau email@bnn.go.id" 
                                           required autofocus>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    Kirim Link Reset
                                </button>
                                <a href="{{ route('login') }}" class="btn btn-light text-muted border-0">
                                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Login
                                </a>
                            </div>

                            {{-- INFO TAMBAHAN: Opsi Reset Manual --}}
                            <div class="info-box d-flex align-items-start">
                                <i class="bi bi-info-circle-fill me-2 mt-1 fs-6"></i>
                                <div>
                                    <strong>Kesulitan Akses?</strong><br>
                                    Jika Anda tidak menerima email atau lupa email yang terdaftar, silakan hubungi <strong>Admin Pusat</strong> atau <strong>Admin Satker</strong> Anda untuk melakukan reset password manual.
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Script Bootstrap (Penting untuk Icon & Komponen) --}}
    {{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script> --}}
</body>
</html>