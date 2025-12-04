<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{

    public function index() {
        return view('auth.login');
    }

    public function authenticate(Request $request) {
        // 1. Validasi Input
        // Kita pastikan user sudah mengisi kolom login_id dan password
        $request->validate([
            'login_id' => 'required|string',
            'password' => 'required|string',
        ]);

        // 2. Logika Cerdas: Tentukan apakah inputnya Email atau NIP
        // filter_var akan mengecek format string. 
        // Jika format email, kita set $fieldType jadi 'email'. Jika bukan, jadi 'nip'.
        $fieldType = filter_var($request->input('login_id'), FILTER_VALIDATE_EMAIL) 
            ? 'email' 
            : 'pegawai_nip'; // Pastikan nama kolom di database Anda adalah 'pegawai_nip'

        // 3. Susun data kredensial untuk dicocokkan
        $credentials = [
            $fieldType => $request->input('login_id'),
            'password' => $request->input('password')
        ];

        // 4. Eksekusi Login dengan Auth::attempt
        // Laravel otomatis menghash password input dan mencocokkan dengan hash di DB
        if (Auth::attempt($credentials)) {
            
            // 5. KEAMANAN: Regenerate Session ID
            // Ini wajib untuk mencegah serangan "Session Fixation"
            $request->session()->regenerate();

            // 6. Redirect User
            // intended() akan mengarahkan user ke halaman yang ingin mereka buka sebelum dihadang login
            // Jika tidak ada, default ke 'dashboard' (atau route('p2m.index'))
            return redirect()->intended(route('p2m.index'));
        }

        // 7. Jika Login Gagal
        // Kembalikan user ke halaman login dengan pesan error
        return back()->withErrors([
            'login_id' => 'Kombinasi NIP/Email dan Password tidak ditemukan.',
        ])->onlyInput('login_id'); // Biarkan input login_id tetap terisi agar user tidak ngetik ulang
    }

    public function logout(Request $request)
    {
        // 1. Keluarkan user dari sistem (Un-authenticate)
        Auth::logout();

        // 2. Invalidate Session (Hancurkan file session user saat ini)
        // Supaya session ID lama tidak bisa dipakai lagi oleh orang iseng
        $request->session()->invalidate();

        // 3. Regenerate Token (Buat ulang CSRF token)
        // Untuk mencegah serangan CSRF pada form login berikutnya
        $request->session()->regenerateToken();

        // 4. Redirect ke halaman login (dengan pesan sukses opsional)
        return redirect('/login')
            ->with('status', 'logout')
            ->with('message', 'Anda berhasil logout');
    }
}
