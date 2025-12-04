<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="{{ asset("assets/favicon-CvUZKS4z.svg") }}">
    <link rel="icon" type="image/png" href="{{ asset("assets/favicon-B_cwPWBd.png") }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" crossorigin href="{{ asset("assets/main-DLfE7m78.css") }}">
    <title>Login</title>
    @stack("styles")
<body>

    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5 col-lg-4">
                <div class="card login-card p-3">
                    
                    <div class="card-header text-center">
                        <img src="{{ asset("assets/logo-bnn.png") }}" alt="Logo BNN" class="bnn-logo" height="128">
                        <h5 class="mb-0 fw-bold text-primary">BNN PROVINSI</h5>
                        <small class="text-muted fw-bold">SULAWESI TENGGARA</small>
                    </div>

                    <div class="card-body p-4">
                        
                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <form action="{{ route('login.authenticate') }}" method="POST">
                            @csrf
                            
                            <div class="mb-3">
                                <label for="loginId" class="form-label small text-muted fw-bold">NIP / Email</label>
                                <div class="input-group">
                                    <span class="input-group-text text-muted">
                                        <i class="bi bi-person-badge"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control @error('login_id') is-invalid @enderror" 
                                           id="loginId" 
                                           name="login_id" 
                                           value="{{ old('login_id') }}" 
                                           placeholder="Masukkan NIP atau Email" 
                                           required autofocus>
                                    
                                    @error('login_id')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label small text-muted fw-bold">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text text-muted">
                                        <i class="bi bi-lock-fill"></i>
                                    </span>
                                    <input type="password" 
                                        class="form-control @error('password') is-invalid @enderror" 
                                        id="password" 
                                        name="password" 
                                        placeholder="Masukkan password" 
                                        required>
                                    
                                    @error('password')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-in-right me-2"></i> Masuk Aplikasi
                                </button>
                            </div>

                        </form>
                    </div>
                    
                    <div class="card-footer text-center bg-white border-0 pb-4">
                        <small class="text-muted">&copy; 2025 BNNP Sulawesi Tenggara</small>
                    </div>

                </div>
            </div>
        </div>
    </div>

</body>
</html>