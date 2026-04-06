<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Terintegrasi BNNP</title>
    
    <link rel="icon" type="image/png" href="{{ asset("assets/favicon-B_cwPWBd.png") }}">
    
    {{-- Load CSS App --}}
    @vite(['resources/css/app.css'])

    <style>
        :root {
            --bn-primary: #005eb8;
            --bn-dark: #003d7a;
            --text-main: #1e293b;
            --text-sub: #64748b;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #fff;
            margin: 0;
            overflow-x: hidden;
        }

        /* --- SISI KIRI (DESKTOP) --- */
        .left-side {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            padding: 2rem;
            /* Gambar Background */
            background-image: url("{{ asset('assets/gedung-bnn.png') }}");
            background-size: cover;
            background-position: center;
            position: relative;
        }

        /* OVERLAY BIRU (DESKTOP) - Dibuat lebih transparan */
        .left-side::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            /* Opacity diturunkan ke 0.75 dan 0.85 agar gedung terlihat */
            /* background: linear-gradient(135deg, rgba(0, 94, 184, 0.85) 0%, rgba(0, 42, 84, 0.95) 100%); */
            background: linear-gradient(135deg, rgba(0, 94, 184, 0.80) 0%, rgba(0, 42, 84, 0.90) 100%);
            z-index: 1;
        }

        .brand-content { position: relative; z-index: 2; width: 100%; display: flex; flex-direction: column; align-items: center; }
        .logo-img { height: 160px; width: auto; display: block; margin: 0 auto 1.5rem auto; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.3)); transition: transform 0.3s; }
        .app-title { font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem; line-height: 1.2; text-transform: uppercase; text-shadow: 0 4px 10px rgba(0,0,0,0.3); letter-spacing: -0.5px; }
        .app-desc { font-size: 1rem; font-weight: 400; opacity: 0.95; margin-bottom: 0; letter-spacing: 0.5px; text-shadow: 0 2px 4px rgba(0,0,0,0.3); max-width: 90%; }
        .separator { width: 60px; height: 4px; background: rgba(255,255,255,0.6); border-radius: 4px; margin: 2.5rem auto; }
        .instansi-text { font-size: 1.1rem; font-weight: 600; text-transform: uppercase; letter-spacing: 3px; line-height: 1.5; opacity: 1; text-shadow: 0 2px 4px rgba(0,0,0,0.3); }


        /* --- SISI KANAN (DESKTOP DEFAULT) --- */
        .right-side {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f1f5f9;
            padding: 2rem;
            flex-direction: column;
        }

        .login-card {
            width: 100%;
            max-width: 450px;
            background-color: #ffffff;
            padding: 3rem;
            border-radius: 24px;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08);
            position: relative; z-index: 2;
        }

        /* Style Desktop */
        .welcome-header { margin-bottom: 3rem; }
        .form-group { margin-bottom: 2rem; }
        .btn-submit { margin-top: 2rem; }
        .welcome-title { font-size: 1.8rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.5rem; }
        .welcome-sub { font-size: 0.95rem; color: var(--text-sub); line-height: 1.5; }
        .form-label { font-size: 0.9rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.8rem; display: block; }
        .forgot-pass { color: var(--bn-primary); font-size: 0.85rem; font-weight: 700; text-decoration: none; }
        .footer-copy { margin-top: 3rem; text-align: center; font-size: 0.8rem; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 1.5rem; }

        /* Input Style */
        .input-wrapper { position: relative; }
        .styled-input {
            width: 100%; padding: 0.9rem 1rem 0.9rem 3rem;
            background-color: #ffffff;
            border: 1px solid #e2e8f0; border-radius: 12px;
            font-size: 1rem; font-weight: 600; color: var(--text-main);
            transition: all 0.2s ease;
        }
        .styled-input:focus { background-color: #fff; border-color: var(--bn-primary); box-shadow: 0 0 0 4px rgba(0, 94, 184, 0.1); outline: none; }
        .input-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); font-size: 1.25rem; color: #94a3b8; }
        .styled-input:focus + .input-icon { color: var(--bn-primary); }
        .toggle-btn { position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; }

        .btn-submit {
            width: 100%; padding: 1rem;
            background: linear-gradient(135deg, var(--bn-primary) 0%, var(--bn-dark) 100%);
            color: white; font-size: 1rem; font-weight: 700; border: none; border-radius: 12px;
            cursor: pointer; box-shadow: 0 10px 20px -5px rgba(0, 94, 184, 0.3);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 15px 30px -5px rgba(0, 94, 184, 0.4); }


        /* --- MOBILE LAYOUT --- */
        .mobile-branding { display: none; }

        @media (max-width: 991px) {
            
            /* Jarak dirapatkan kembali khusus Mobile */
            .welcome-header { margin-bottom: 2rem; }
            .form-group { margin-bottom: 1.2rem; }
            .btn-submit { margin-top: 1rem; }
            .form-label { margin-bottom: 0.5rem; }

            /* Background & Overlay Mobile */
            .right-side {
                background-image: url("{{ asset('assets/gedung-bnn.png') }}");
                background-size: cover; background-position: center; justify-content: center;
                position: relative;
            }
            
            /* OVERLAY BIRU (MOBILE) - Dibuat Transparan Agar Gedung Terlihat */
            .right-side::before {
                content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
                /* Opacity 0.65 (Atas) sampai 0.85 (Bawah) -> Gedung akan kelihatan jelas */
                background: linear-gradient(135deg, rgba(0, 94, 184, 0.65) 0%, rgba(0, 42, 84, 0.85) 100%);
                z-index: 1;
            }

            .login-card { background-color: transparent; box-shadow: none; padding: 0; margin-top: 0; }
            
            /* Teks Putih dengan Shadow agar terbaca di atas gambar */
            .welcome-title { color: #ffffff; text-shadow: 0 2px 8px rgba(0,0,0,0.5); }
            .welcome-sub { color: rgba(255,255,255,0.95); text-shadow: 0 1px 3px rgba(0,0,0,0.5); }
            .form-label { color: #ffffff; text-shadow: 0 1px 4px rgba(0,0,0,0.5); }
            .forgot-pass { color: #bfdbfe; text-shadow: 0 1px 2px rgba(0,0,0,0.3); }
            .footer-copy { color: rgba(255,255,255,0.7); border-top: 1px solid rgba(255,255,255,0.3); }

            .mobile-branding { display: block; position: relative; z-index: 2; text-align: center; margin-bottom: 2.5rem; color: white; }
            .mobile-logo { height: 100px; display: block; margin: 0 auto 1rem auto; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.4)); }
            .mobile-app-name { font-size: 1.8rem; font-weight: 800; text-transform: uppercase; margin-bottom: 0.2rem; text-shadow: 0 2px 6px rgba(0,0,0,0.5); }
            .mobile-app-desc { font-size: 0.95rem; opacity: 1; text-shadow: 0 1px 4px rgba(0,0,0,0.5); }

            /* Input Putih Bersih */
            .styled-input { box-shadow: 0 4px 15px rgba(0,0,0,0.25); border: none; }
        }
    </style>
</head>
<body>

    <div class="container-fluid p-0">
        <div class="row g-0">
            
            <div class="col-lg-7 d-none d-lg-flex left-side">
                <div class="brand-content">
                    <img src="{{ asset("assets/logo-bnn.png") }}" alt="Logo BNN" class="logo-img">
                    <div class="app-title">SIPANTAU SULTRA</div>
                    <div class="app-desc">Sistem Informasi Pelaporan Terintegrasi</div>
                    <div class="separator"></div>
                    <div class="instansi-text">BNN PROVINSI</div>
                    <div class="instansi-text">SULAWESI TENGGARA</div>
                </div>
            </div>

            <div class="col-12 col-lg-5 right-side">
                
                {{-- MOBILE BRANDING --}}
                <div class="mobile-branding">
                    <img src="{{ asset("assets/logo-bnn.png") }}" alt="Logo" class="mobile-logo">
                    <div class="mobile-app-name">SIPANTAU SULTRA</div>
                    <div class="mobile-app-desc">Sistem Informasi Pelaporan Terintegrasi</div>
                </div>

                {{-- FORM AREA --}}
                <div class="login-card">
                    
                    <div class="welcome-header">
                        <h1 class="welcome-title">Selamat Datang 👋</h1>
                        <p class="welcome-sub">Silakan masuk untuk melanjutkan ke dalam aplikasi.</p>
                    </div>

                    @if (session('error'))
                        <div class="alert alert-danger border-0 rounded-3 d-flex align-items-center mb-4 p-3" style="background-color: #fef2f2; color: #991b1b;">
                            <i class="bi bi-exclamation-circle-fill me-2 fs-5"></i>
                            <div class="fw-semibold">{{ session('error') }}</div>
                        </div>
                    @endif

                    <form action="{{ route('login.authenticate') }}" method="POST">
                        @csrf
                        
                        <div class="form-group">
                            <label class="form-label" for="loginId">Identitas (NIP/Email)</label>
                            <div class="input-wrapper">
                                <input type="text" id="loginId" name="login_id" class="styled-input" 
                                       placeholder="Contoh: 198001..." value="{{ old('login_id') }}" required autofocus>
                                <i class="bi bi-person-vcard input-icon"></i>
                            </div>
                            @error('login_id')
                                <small class="text-danger fw-bold mt-1 d-block">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0" for="password">Kata Sandi</label>
                                <a href="{{ route('password.request') }}" class="forgot-pass">Lupa Sandi?</a>
                            </div>
                            
                            <div class="input-wrapper">
                                <input type="password" id="password" name="password" class="styled-input" 
                                       placeholder="Masukkan kata sandi Anda" required>
                                <i class="bi bi-shield-lock input-icon"></i>
                                <button type="button" class="toggle-btn" onclick="togglePassword()">
                                    <i class="bi bi-eye-slash" id="toggleIcon"></i>
                                </button>
                            </div>
                            @error('password')
                                <small class="text-danger fw-bold mt-1 d-block">{{ $message }}</small>
                            @enderror
                        </div>

                        <button type="submit" class="btn-submit">
                            MASUK APLIKASI <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                    </form>

                    <div class="footer-copy">
                        &copy; {{ date('Y') }} Badan Narkotika Nasional Provinsi Sulawesi Tenggara.
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function togglePassword() {
            const pass = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            
            if (pass.type === 'password') {
                pass.type = 'text';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
                icon.style.color = '#005eb8';
            } else {
                pass.type = 'password';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
                icon.style.color = '';
            }
        }
    </script>
</body>
</html>