<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    // 1. Tampilkan Form Input Email/NIP
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    // 2. Proses Kirim Link
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['login_id' => 'required']);

        // Logika Cerdas: Cek apakah input NIP atau Email
        $input = $request->input('login_id');
        $fieldType = filter_var($input, FILTER_VALIDATE_EMAIL) ? 'email' : 'pegawai_nip';

        // Cari User berdasarkan NIP atau Email
        $user = User::where($fieldType, $input)->first();

        // Jika user tidak ditemukan
        if (!$user) {
            return back()->withErrors(['login_id' => 'Data pengguna tidak ditemukan.']);
        }

        // Jika user ditemukan, kita ambil email aslinya untuk dikirimkan link
        // Kita "memaksa" Laravel mengirim ke email user tersebut
        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status == Password::RESET_LINK_SENT) {
            return back()->with('status', 'Link reset password telah dikirim ke email Anda (' . $this->maskEmail($user->email) . ')');
        }

        return back()->withErrors(['login_id' => __($status)]);
    }

    // 3. Tampilkan Form Ganti Password Baru
    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.reset-password')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }

    // 4. Proses Simpan Password Baru
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'is_password_default' => false // Set false karena user sudah ganti sendiri
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Password berhasil diperbarui! Silakan login.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }

    // Helper: Sensor email (con: a***@gmail.com) untuk privasi di notifikasi
    private function maskEmail($email) {
        $em   = explode("@", $email);
        $name = implode('@', array_slice($em, 0, count($em)-1));
        $len  = floor(strlen($name)/2);
        return substr($name,0, $len) . str_repeat('*', $len) . "@" . end($em);
    }
}