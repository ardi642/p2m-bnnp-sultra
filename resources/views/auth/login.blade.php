<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem P2M BNNP Sultra</title>
    
    {{-- Favicon --}}
    <link rel="icon" type="image/png" href="{{ asset("assets/favicon-B_cwPWBd.png") }}">
    
    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    {{-- Javascript Utama --}}
    <script type="module" crossorigin src="{{  asset("assets/main-Bfr21rhA.js") }}"></script>
    {{-- CSS Utama --}}
    <link rel="stylesheet" crossorigin href="{{ asset("assets/main-DLfE7m78.css") }}">
    
    {{-- Custom CSS untuk Halaman Login --}}
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #fff;
            overflow-x: hidden; /* Hilangkan scroll horizontal */
        }
        
        .login-wrapper {
            min-height: 100vh;
        }

        /* --- BAGIAN KIRI (BRANDING) --- */
        .brand-side {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
            padding: 3rem;
        }

        /* Pattern Overlay (Hiasan Latar Belakang) */
        .brand-side::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: radial-gradient(circle at 25% 25%, rgba(255, 255, 255, 0.1) 2%, transparent 2.5%), 
                              radial-gradient(circle at 75% 75%, rgba(255, 255, 255, 0.1) 2%, transparent 2.5%);
            background-size: 40px 40px;
            opacity: 0.6;
        }

        .brand-content {
            position: relative;
            z-index: 2;
        }

        .logo-besar {
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2));
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
            height: 140px;
            width: auto;
        }
        
        .logo-besar:hover {
            transform: scale(1.05);
        }

        /* --- BAGIAN KANAN (FORM) --- */
        .form-side {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background-color: #ffffff;
        }

        .form-container {
            width: 100%;
            max-width: 400px; /* Batasi lebar form agar enak dilihat */
        }

        /* Styling Input Modern */
        .form-control {
            padding: 0.8rem 1rem;
            font-size: 0.95rem;
            border-radius: 0.5rem;
            border: 1px solid #dee2e6;
            background-color: #f8f9fa; /* Abu-abu sangat muda */
            transition: all 0.2s;
        }
        
        .form-control:focus {
            background-color: #fff;
            border-color: #86b7fe;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
        }

        .input-group-text {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-right: none;
            border-top-left-radius: 0.5rem;
            border-bottom-left-radius: 0.5rem;
            color: #6c757d;
        }
        
        /* Hilangkan border kiri input agar menyatu dengan icon */
        .input-group .form-control {
            border-left: none;
        }
        
        /* Efek fokus pada grup input */
        .input-group:focus-within .input-group-text {
            border-color: #86b7fe;
            background-color: #fff;
            color: #0d6efd;
        }

        .btn-primary {
            padding: 0.8rem;
            font-weight: 600;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(13, 110, 253, 0.2);
            transition: transform 0.1s, box-shadow 0.1s;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 8px rgba(13, 110, 253, 0.3);
        }

        .btn-primary:active {
            transform: scale(0.98);
        }

        /* --- RESPONSIVE (MOBILE) --- */
        @media (max-width: 991.98px) {
            .brand-side {
                min-height: 280px; /* Tinggi header di HP */
                padding: 2rem 1rem;
            }
            .logo-besar {
                height: 80px; /* Logo lebih kecil di HP */
                margin-bottom: 1rem;
            }
            .brand-content h4 { font-size: 1.2rem; }
            .brand-content p { font-size: 0.9rem; }
            
            .form-side {
                padding: 2rem 1.5rem;
                min-height: calc(100vh - 280px); /* Sisa tinggi layar */
                align-items: flex-start; /* Form mulai dari atas */
            }
        }
    </style>
</head>
<body>

    <div class="container-fluid p-0">
        <div class="row g-0 login-wrapper">
            
            {{-- 1. BAGIAN KIRI: BRANDING & LOGO --}}
            <div class="col-lg-6 brand-side">
                <div class="brand-content">
                    <img src="{{ asset("assets/logo-bnn.png") }}" alt="Logo BNN" class="logo-besar">
                    <h4 class="fw-bold mb-1">BNN PROVINSI</h4>
                    <p class="mb-0 text-white-50 fw-semibold ls-1">SULAWESI TENGGARA</p>
                    
                    <div class="mt-4 d-none d-lg-block">
                        <hr class="opacity-25 mx-auto" style="width: 50px; border-width: 3px;">
                        <p class="small opacity-75 mt-3">Sistem Informasi Pelaporan P2M<br>Sebenarnya belum ditahu nama sistemnya</p>
                    </div>
                </div>
            </div>

            {{-- 2. BAGIAN KANAN: FORM LOGIN --}}
            <div class="col-lg-6 form-side">
                <div class="form-container">
                    
                    <div class="mb-4 text-center text-lg-start">
                        <h2 class="fw-bold text-dark mb-2">Selamat Datang</h2>
                        <p class="text-muted">Silakan login menggunakan akun Anda.</p>
                    </div>

                    {{-- ALERT ERROR --}}
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-exclamation-circle-fill fs-5 me-2"></i>
                                <span>{{ session('error') }}</span>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('status'))
                        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i> {{ session('status') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ route('login.authenticate') }}" method="POST">
                        @csrf
                        
                        {{-- INPUT NIP / EMAIL --}}
                        <div class="mb-4">
                            <label for="loginId" class="form-label small text-uppercase text-muted fw-bold ls-1">NIP / Email</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-person"></i>
                                </span>
                                <input type="text" 
                                       class="form-control @error('login_id') is-invalid @enderror" 
                                       id="loginId" 
                                       name="login_id" 
                                       value="{{ old('login_id') }}" 
                                       placeholder="Contoh: 1980... atau email@bnn.go.id" 
                                       required autofocus>
                            </div>
                            @error('login_id')
                                <div class="text-danger small mt-1 ps-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- INPUT PASSWORD --}}
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="password" class="form-label small text-uppercase text-muted fw-bold ls-1 mb-0">Password</label>
                                <a href="{{ route('password.request') }}" class="small text-decoration-none fw-semibold text-primary">Lupa Password?</a>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" 
                                       class="form-control @error('password') is-invalid @enderror" 
                                       id="password" 
                                       name="password" 
                                       placeholder="••••••••" 
                                       required>
                            </div>
                            @error('password')
                                <div class="text-danger small mt-1 ps-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- BUTTON LOGIN --}}
                        <div class="d-grid mt-5">
                            <button type="submit" class="btn btn-primary btn-lg">
                                Masuk Aplikasi <i class="bi bi-arrow-right ms-2 small"></i>
                            </button>
                        </div>

                    </form>

                    <div class="mt-5 text-center">
                        <small class="text-muted d-block">&copy; {{ date('Y') }} BNNP Sulawesi Tenggara</small>
                    </div>

                </div>
            </div>
        </div>
    </div>

</body>
</html>