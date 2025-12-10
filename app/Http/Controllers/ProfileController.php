<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\ChangeEmailNotification;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Update Informasi Profil (Email)
     */
    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 1. Validasi
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'unique:users,email,'.$user->id],
        ]);

        // 2. Cek apa saja yang berubah SEBELUM update database
        $nameChanged = $request->name !== $user->name;
        $emailChanged = $request->email !== $user->email;

        // Jika tidak ada yang berubah sama sekali
        if (!$nameChanged && !$emailChanged) {
            return back()->with('info', 'Tidak ada perubahan data.');
        }

        DB::transaction(function () use ($user, $request, $emailChanged) {
            
            // Update Nama
            $user->name = $request->name;

            // Logika Email Berubah
            if ($emailChanged) {
                $token = Str::random(60);
                $user->pending_email = $request->email;
                $user->verification_token = $token;
                $user->verification_token_expires_at = now()->addHours(24);
                
                // Kirim Email (Ke Email Lama - Sesuai request keamanan sebelumnya)
                Mail::send('emails.verify-change', ['token' => $token, 'user' => $user], function ($message) use ($user) {
                    $message->to($user->email); 
                    $message->subject('Konfirmasi Perubahan Email');
                });
            }

            $user->save();

            // Sinkron Pegawai
            if ($user->pegawai) {
                $user->pegawai->update(['nama' => $request->name]);
            }
        });

        // 3. MENENTUKAN PESAN FLASH YANG TEPAT
        // -------------------------------------------------------------
        
        // KASUS A: Ubah Nama DAN Ubah Email
        if ($nameChanged && $emailChanged) {
            return back()->with('success', 'Nama diperbarui dan link konfirmasi email telah dikirim ke ' . $user->email);
        }
        
        // KASUS B: Cuma Ubah Email
        if ($emailChanged) {
            return back()->with('success', 'Link konfirmasi perubahan email telah dikirim ke ' . $user->email . '. Silakan cek inbox Anda.');
        }

        // KASUS C: Cuma Ubah Nama (Default)
        return back()->with('success', 'Nama profil berhasil diperbarui.');
    }

    /**
     * Update Password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|confirmed|min:8|different:current_password',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $user->update([
            'password' => Hash::make($request->password),
            'is_password_default' => false, 
        ]);

        return redirect()->route('profile.edit')->with('success', 'Password berhasil diperbarui.');
    }


    public function verifyNewEmail($token)
    {
        // Cari user berdasarkan token yang diklik
        $user = \App\Models\User::where('verification_token', $token)->first();

        // SKENARIO 1: Token Tidak Ditemukan
        // Ini terjadi jika:
        // a. User sudah klik "Batalkan Perubahan" (Token di DB jadi NULL)
        // b. User sudah berhasil verifikasi sebelumnya (Token di DB jadi NULL)
        // c. Tokennya salah ketik / ngawur
        if (!$user) {
            return redirect()->route('profile.edit')
                ->with('error', 'Link verifikasi tidak valid atau permintaan perubahan email telah dibatalkan.');
        }

        // SKENARIO 2: Token Ada, Tapi Sudah Kadaluwarsa
        // Ini terjadi jika waktu sekarang > waktu expires_at
        if (now()->greaterThan($user->verification_token_expires_at)) {
            return redirect()->route('profile.edit')
                ->with('error', 'Link verifikasi sudah kadaluwarsa. Silakan minta kirim ulang link baru.');
        }

        // SKENARIO 3: Berhasil (Token Valid & Belum Expired)
        DB::transaction(function () use ($user) {
            $user->email = $user->pending_email; // Update Email
            
            // Bersihkan data pending
            $user->pending_email = null;
            $user->verification_token = null;
            $user->verification_token_expires_at = null;
            
            $user->email_verified_at = now();
            $user->save();
        });

        return redirect()->route('profile.edit')->with('success', 'Email berhasil diperbarui!');
    }

    // Batalkan Request (Tombol Batal di Blade)
    public function cancelEmailChange()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->update([
            'pending_email' => null,
            'verification_token' => null,
            'verification_token_expires_at' => null,
        ]);

        return back()->with('success', 'Permintaan perubahan email dibatalkan.');
    }

    // Kirim Ulang Link (Tombol Resend di Blade)
    public function resendEmailVerification()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if (!$user->pending_email) {
            return back()->with('error', 'Tidak ada permintaan perubahan email.');
        }

        // Perpanjang waktu expired
        $user->verification_token_expires_at = now()->addHours(24);
        $user->save();

        // Kirim Email lagi (Logic sama seperti di update)
        Mail::send('emails.verify-change', ['token' => $user->verification_token, 'user' => $user], function ($message) use ($user) {
            $message->to($user->email);
            $message->subject('Kirim Ulang: Verifikasi Perubahan Email');
        });

        return back()->with('success', 'Link verifikasi telah dikirim ulang.');
    }
}