<!DOCTYPE html>
<html>
<head>
    <title>Konfirmasi Perubahan Email</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">

    <h2>Halo, {{ $user->name }}</h2>

    <p>Kami menerima permintaan untuk mengubah alamat email akun Anda menjadi: <strong>{{ $user->pending_email }}</strong>.</p>
    
    <p>Demi keamanan, kami perlu memastikan bahwa permintaan ini berasal dari Anda.</p>

    <p style="margin: 20px 0;">
        <a href="{{ route('profile.email.verify', $token) }}" 
        style="background-color: #0d6efd; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
            Setujui Perubahan Email
        </a>
    </p>

    <p><strong>PENTING:</strong> Pastikan email baru yang Anda masukkan ({{ $user->pending_email }}) sudah benar penulisannya. Setelah Anda klik tombol di atas, Anda harus login menggunakan email baru tersebut.</p>

    <p><em>Jika Anda tidak melakukan permintaan ini, abaikan saja. Akun Anda aman.</em></p>

    <br>
    <p>Terima Kasih,<br>
    Tim Admin</p>

</body>
</html>