<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pegawai;
use App\Models\SatuanKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        
        // Eager load relasi pegawai & satker
        $query = User::with('pegawai.satuanKerja');

        // --- 1. FILTERING BERDASARKAN SIAPA YANG LOGIN ---
        
        if ($currentUser->role === 'admin') {
            // ADMIN PUSAT: Lihat semua, bisa filter satker
            $query->where('id', '!=', $currentUser->id);
            
            if ($request->filled('satuan_kerja_id')) {
                $query->whereHas('pegawai', function($q) use ($request) {
                    $q->whereIn('satuan_kerja_id', $request->satuan_kerja_id);
                });
            }
        } 
        else {
            // SELAIN ADMIN PUSAT (Admin Satker, Admin P2M, dll)
            // Wajib filter berdasarkan Satker pengguna saat ini
            $mySatkerId = $currentUser->pegawai->satuan_kerja_id ?? null;

            if (!$mySatkerId) {
                return view('admin.users.index', ['users' => collect([])->paginate(10), 'satuanKerjas' => []]);
            }

            // Filter user yang satu satker
            $query->whereHas('pegawai', function($q) use ($mySatkerId) {
                $q->where('satuan_kerja_id', $mySatkerId);
            });

            // Filter jangan tampilkan diri sendiri & Super Admin
            $query->where('id', '!=', $currentUser->id);
            $query->where('role', '!=', 'admin');

            // --- FILTER SPESIFIK PER BIDANG ---
            // Admin Bidang hanya boleh melihat Operator Bidangnya saja
            if ($currentUser->role === 'admin_p2m') {
                $query->whereIn('role', ['operator_p2m']);
            }
            elseif ($currentUser->role === 'admin_berantas') {
                $query->whereIn('role', ['operator_berantas']);
            }
            elseif ($currentUser->role === 'admin_rehab') {
                $query->whereIn('role', ['operator_rehab']);
            }
            // Admin Satker melihat semua (tidak perlu filter tambahan)
        }

        // --- 2. PENCARIAN GLOBAL ---
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%")
                ->orWhereHas('pegawai', function($subQ) use ($search) {
                    $subQ->where('nama', 'LIKE', "%{$search}%")
                        ->orWhere('nip', 'LIKE', "%{$search}%");
                });
            });
        }

        $perPage = $request->input('per_page', 10);
        $users = $query->paginate($perPage)->withQueryString();
        
        // Data dropdown satker hanya untuk Admin Pusat
        $satuanKerjas = ($currentUser->role === 'admin') ? SatuanKerja::orderBy('satuan_kerja')->get() : [];

        return view('admin.users.index', compact('users', 'satuanKerjas'));
    }

    public function create()
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        // 1. Cari NIP yang SUDAH punya akun
        $existingNips = User::whereNotNull('pegawai_nip')->pluck('pegawai_nip')->toArray();

        // 2. Query Pegawai yang BELUM punya akun
        $pegawaiQuery = Pegawai::with('satuanKerja')
            ->whereNotIn('nip', $existingNips)
            ->orderBy('nama');

        // 3. Filter Satker (Jika bukan Super Admin)
        if ($currentUser->role !== 'admin') {
            if ($currentUser->pegawai) {
                $pegawaiQuery->where('satuan_kerja_id', $currentUser->pegawai->satuan_kerja_id);
            } else {
                $pegawaiQuery->where('id', -1); 
            }
        }

        $pegawais = $pegawaiQuery->get();

        return view('admin.users.create', compact('pegawais'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        $allowedRoles = [];

        // Logic Hak Akses Pembuatan User
        if ($currentUser->hasRole('admin')) {
            $allowedRoles = ['admin_satker', 'admin_p2m', 'admin_berantas', 'admin_rehab', 'operator_satker', 'operator_p2m', 'operator_berantas', 'operator_rehab'];
        }
        if ($currentUser->hasRole('admin_satker')) {
            $allowedRoles = ['admin_p2m', 'admin_berantas', 'admin_rehab', 'operator_satker', 'operator_p2m', 'operator_berantas', 'operator_rehab'];
        }
        if ($currentUser->hasRole('admin_p2m')) $allowedRoles[] = 'operator_p2m';
        if ($currentUser->hasRole('admin_berantas')) $allowedRoles[] = 'operator_berantas';
        if ($currentUser->hasRole('admin_rehab')) $allowedRoles[] = 'operator_rehab';

        $allowedRoles = array_unique($allowedRoles);

        $request->validate([
            'pegawai_nip' => 'required|exists:pegawai,nip|unique:users,pegawai_nip',
            'role' => ['required', Rule::in($allowedRoles)],
        ]);

        $pegawai = Pegawai::find($request->pegawai_nip);

        User::create([
            'name' => $pegawai->nama,
            'email' => $pegawai->email,
            'password' => Hash::make('password'),
            'role' => $request->role,
            'pegawai_nip' => $request->pegawai_nip,
            'is_password_default' => true, 
            'email_verified_at' => now(), 
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dibuat.');
    }

    // --- HALAMAN EDIT (HANYA ADMIN PUSAT & ADMIN SATKER) ---
    public function edit($id)
    {
        $targetUser = User::with('pegawai.satuanKerja')->findOrFail($id);
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        // 1. PROTEKSI: Admin Bidang TIDAK BOLEH masuk sini
        if (!$currentUser->hasRole(['admin', 'admin_satker'])) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit detail user.');
        }

        // 2. Proteksi Lingkup Satker untuk Admin Satker
        if ($currentUser->role === 'admin_satker') {
            if ($targetUser->role === 'admin') abort(403);
            
            $targetSatkerId = $targetUser->pegawai->satuan_kerja_id ?? null;
            $mySatkerId = $currentUser->pegawai->satuan_kerja_id ?? null;

            if ($targetSatkerId !== $mySatkerId) {
                abort(403, 'Anda tidak berhak mengedit user dari Satuan Kerja lain.');
            }
        }

        return view('admin.users.edit', compact('targetUser'));
    }

    // --- PROSES UPDATE (HANYA ADMIN PUSAT & ADMIN SATKER) ---
    public function update(Request $request, $id)
    {
        $targetUser = User::findOrFail($id);
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        // Proteksi: Admin Bidang tidak boleh update
        if (!$currentUser->hasRole(['admin', 'admin_satker'])) {
            abort(403, 'Akses Ditolak.');
        }

        // Generate Allowed Roles (Sama seperti Store)
        $allowedRoles = [];
        if ($currentUser->hasRole('admin')) {
            $allowedRoles = ['admin_satker', 'admin_p2m', 'admin_berantas', 'admin_rehab', 'operator_satker', 'operator_p2m', 'operator_berantas', 'operator_rehab'];
        } elseif ($currentUser->hasRole('admin_satker')) {
            $allowedRoles = ['admin_p2m', 'admin_berantas', 'admin_rehab', 'operator_satker', 'operator_p2m', 'operator_berantas', 'operator_rehab'];
        }

        $request->validate([
            'role' => ['required', Rule::in($allowedRoles)],
        ]);

        if ($currentUser->id === $targetUser->id && $request->role !== $currentUser->role) {
             return back()->with('error', 'Anda tidak boleh mengubah role akun Anda sendiri.');
        }

        $targetUser->update(['role' => $request->role]);

        return redirect()->route('admin.users.index')->with('success', 'Role user berhasil diperbarui.');
    }

    // --- RESET PASSWORD (ADMIN BIDANG BOLEH AKSES) ---
    public function resetPassword($id)
    {
        $targetUser = User::findOrFail($id);
        $currentUser = Auth::user();

        // Cek Otoritas (Panggil fungsi helper di bawah)
        $this->checkAuthority($currentUser, $targetUser);

        $targetUser->update([
            'password' => Hash::make('password'),
            'is_password_default' => true,
            'pending_email' => null,
            'verification_token' => null,
        ]);

        return redirect()->back()->with('success', 'Password direset. User wajib mengganti password saat login.');
    }

    // --- HAPUS USER (ADMIN BIDANG BOLEH AKSES) ---
    public function destroy($id)
    {
        $targetUser = User::findOrFail($id);
        $currentUser = Auth::user();

        if ($targetUser->role === 'admin') {
            return redirect()->back()->with('error', 'Super Admin tidak bisa dihapus.');
        }

        // Cek Otoritas (Panggil fungsi helper di bawah)
        $this->checkAuthority($currentUser, $targetUser);

        $targetUser->delete();

        return redirect()->back()->with('success', 'User berhasil dihapus.');
    }

    /**
     * Helper untuk validasi hak akses Hapus & Reset Password
     */
    private function checkAuthority($currentUser, $targetUser)
    {
        // 1. Admin Pusat: Bebas
        if ($currentUser->role === 'admin') return true;

        // 2. Cek Kesamaan Satker (Wajib)
        $mySatkerId = $currentUser->pegawai->satuan_kerja_id ?? null;
        $targetSatkerId = $targetUser->pegawai->satuan_kerja_id ?? null;

        if (!$mySatkerId || $mySatkerId !== $targetSatkerId) {
            abort(403, 'Akses ditolak: User berbeda Satuan Kerja.');
        }

        // 3. Admin Satker: Bebas di satkernya (kecuali Super Admin)
        if ($currentUser->role === 'admin_satker') return true;

        // 4. Admin Bidang: Hanya operator bidangnya
        $map = [
            'admin_p2m' => 'operator_p2m',
            'admin_berantas' => 'operator_berantas',
            'admin_rehab' => 'operator_rehab',
        ];

        // Jika user login ada di map (admin bidang)
        if (isset($map[$currentUser->role])) {
            // Jika target user adalah operator yang sesuai
            if ($targetUser->role === $map[$currentUser->role]) {
                return true;
            }
        }

        // Jika tidak memenuhi syarat di atas
        abort(403, 'Anda tidak memiliki hak akses untuk mengelola user ini.');
    }
}