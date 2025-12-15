<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset("assets/favicon-B_cwPWBd.png") }}">
    <title>Buat Password Baru</title>
    {{-- Javascript Utama --}}
    <script type="module" crossorigin src="{{  asset("assets/main-Bfr21rhA.js") }}"></script>
    {{-- CSS Utama --}}
    <link rel="stylesheet" crossorigin href="{{ asset("assets/main-DLfE7m78.css") }}">
<body>

    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5 col-lg-4">
                <div class="card login-card p-3">
                    
                    <div class="card-header text-center">
                        <h5 class="mb-0 fw-bold text-primary">Password Baru</h5>
                        <small class="text-muted">Silakan buat password baru Anda</small>
                    </div>

                    <div class="card-body p-4">
                        <form action="{{ route('password.update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">

                            {{-- Email (Readonly) --}}
                            <div class="mb-3">
                                <label class="form-label small text-muted fw-bold">Email Address</label>
                                <input type="email" class="form-control bg-light" name="email" value="{{ $email ?? old('email') }}" readonly>
                                @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            {{-- Password Baru --}}
                            <div class="mb-3">
                                <label class="form-label small text-muted fw-bold">Password Baru</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autofocus>
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Konfirmasi Password --}}
                            <div class="mb-4">
                                <label class="form-label small text-muted fw-bold">Ulangi Password</label>
                                <input type="password" class="form-control" name="password_confirmation" required>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Reset Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>