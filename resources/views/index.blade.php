<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login Page Bootstrap</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body {
      background: #f3f6fb;
      height: 100vh;
    }
    .img-right {
      background: url('02283533-b0b0-41f0-b3ce-4da9378177d6.png') no-repeat center/cover;
      border-radius: 20px;
      min-height: 100%;
    }
    .logo img {
      width: 100px;
    }
  </style>
</head>
<body>
  <div class="container-fluid h-100">
    <div class="row h-100">

      <!-- Left Section -->
      <div class="col-md-6 d-flex flex-column justify-content-center px-5">
        <div class="text-center logo mb-3">
          <img src="https://upload.wikimedia.org/wikipedia/commons/f/fb/BNN_%28Badan_Narkotika_Nasional%29_logo.png" />
          <p class="mt-2 fw-semibold">P2M BNNP SULTRA</p>
        </div>

        <h2 class="fw-bold mb-4">Welcome Back</h2>

        <form>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" placeholder="Contoh@email.com" />
          </div>

          <div class="mb-2">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" placeholder="at least 8 characters" />
          </div>

          <div class="text-end mb-3">
            <a href="#" class="small text-primary">Forgot Password?</a>
          </div>

          <button class="btn btn-primary w-100 py-2">Sign in</button>
        </form>
      </div>

      <!-- Right Section -->
      <div class="col-md-6 img-right d-none d-md-block">
<img src=" "alt="">
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
