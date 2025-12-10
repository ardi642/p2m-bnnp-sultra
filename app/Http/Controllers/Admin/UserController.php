<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pegawai;
use App\Models\SatuanKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        
        // Eager load relasi pegawai & satker untuk performa
        $query = User::with('pegawai.satuanKerja');

        // LOGIKA FILTERING BERDASARKAN ROLE LOGIN
        if ($currentUser->role === 'admin_satker') {
            // Admin Satker: Hanya lihat user yang satu Satker dengannya
            $mySatkerId = $currentUser->pegawai->satuan_kerja_id;

            $query->whereHas('pegawai', function($q) use ($mySatkerId) {
                $q->where('satuan_kerja_id', $mySatkerId);
            });
            
            // Sembunyikan akun Admin Pusat dan Dirinya Sendiri
            $query->where('role', '!=', 'admin')
                ->where('id', '!=', $currentUser->id);
        } 
        else {
            // Admin Pusat: Lihat Semua, kecuali dirinya sendiri
            $query->where('id', '!=', $currentUser->id);
        }

        // FITUR PENCARIAN
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%") // Email = Username login
                ->orWhereHas('pegawai', function($subQ) use ($search) {
                    $subQ->where('nama', 'LIKE', "%{$search}%")
                        ->orWhere('nip', 'LIKE', "%{$search}%");
                });
            });
        }

        // FITUR FILTER SATKER (Khusus Admin Pusat)
        if ($currentUser->role === 'admin' && $request->filled('satuan_kerja_id')) {
            $query->whereHas('pegawai', function($q) use ($request) {
                $q->whereIn('satuan_kerja_id', $request->satuan_kerja_id);
            });
        }

        $perPage = $request->input('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $users = $query->paginate($perPage)->withQueryString();
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja')->get(); // Untuk dropdown filter admin

        return view('admin.users.index', compact('users', 'satuanKerjas'));
    }

    public function create()
    {
        $currentUser = Auth::user();

        // 1. Cari NIP yang SUDAH punya akun (agar tidak double account)
        $existingNips = User::whereNotNull('pegawai_nip')->pluck('pegawai_nip')->toArray();

        // 2. Query Pegawai yang BELUM punya akun
        $pegawaiQuery = Pegawai::with('satuanKerja')
            ->whereNotIn('nip', $existingNips)
            ->orderBy('nama');

        // 3. Batasan jika yang login adalah Admin Satker
        if ($currentUser->role === 'admin_satker') {
            $pegawaiQuery->where('satuan_kerja_id', $currentUser->pegawai->satuan_kerja_id);
        }

        $pegawais = $pegawaiQuery->get();

        return view('admin.users.create', compact('pegawais'));
    }

    public function store(Request $request)
    {
        $currentUser = Auth::user();

        // Validasi
        $request->validate([
            'pegawai_nip' => 'required|exists:pegawai,nip|unique:users,pegawai_nip',
            'role' => 'required|in:admin_satker,operator', // Admin Pusat tidak bisa dibuat lewat sini
        ]);

        // Proteksi Tambahan: Admin Satker hanya boleh buat Operator
        if ($currentUser->role === 'admin_satker' && $request->role === 'admin_satker') {
            abort(403, 'Anda hanya diperbolehkan membuat akun Operator.');
        }

        // Ambil data pegawai untuk isi nama otomatis
        $pegawai = Pegawai::find($request->pegawai_nip);

        User::create([
            'name' => $pegawai->nama,
            'email' => $pegawai->email,
            'password' => Hash::make('12345678'),
            'role' => $request->role,
            'pegawai_nip' => $request->pegawai_nip,
            'is_password_default' => true, 
            
            // --- UPDATE DISINI: Pastikan akun baru langsung Verified ---
            'email_verified_at' => now(), 
            'pending_email' => null,
            'verification_token' => null,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dibuat.');
    }

    public function resetPassword($id)
    {
        $targetUser = User::findOrFail($id);
        $currentUser = Auth::user();

        // Admin Satker tidak boleh reset password Admin Pusat atau Satker Lain
        if ($currentUser->role === 'admin_satker') {
            // Cek apakah target punya NIP (Admin pusat nip=null, jadi aman)
            if (!$targetUser->pegawai) {
                abort(403);
            }
            // Cek kesamaan Satker
            if ($targetUser->pegawai->satuan_kerja_id !== $currentUser->pegawai->satuan_kerja_id) {
                abort(403, 'Akses ditolak.');
            }
        }

        $targetUser->update([
            'password' => Hash::make('12345678'),
            'is_password_default' => true,
            
            // Opsional: Kita bisa juga membersihkan request ganti email yang nyangkut (jika ada)
            'pending_email' => null,
            'verification_token' => null,
        ]);

        return redirect()->back()->with('success', 'Password direset. User wajib mengganti password saat login.');
    }

    public function destroy($id)
    {
        $targetUser = User::findOrFail($id);
        $currentUser = Auth::user();

        // Proteksi Hapus: Tidak boleh hapus Admin Pusat
        if ($targetUser->role === 'admin') {
            return redirect()->back()->with('error', 'Super Admin tidak bisa dihapus.');
        }

        // Proteksi Admin Satker
        if ($currentUser->role === 'admin_satker') {
            if (!$targetUser->pegawai || $targetUser->pegawai->satuan_kerja_id !== $currentUser->pegawai->satuan_kerja_id) {
                abort(403);
            }
        }

        $targetUser->delete();

        return redirect()->back()->with('success', 'User berhasil dihapus.');
    }
}