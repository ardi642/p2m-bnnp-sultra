<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use App\Models\SatuanKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class PegawaiController extends Controller
{
    /**
     * Helper: Mendapatkan ID Satker dari user yang sedang login
     * Berlaku untuk Admin Satker dan Admin Bidang
     */
    private function getMySatkerId()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Daftar role yang terikat dengan Satker tertentu
        $rolesBoundToSatker = [
            'admin_satker', 'admin_p2m', 'admin_berantas', 'admin_rehab'
        ];

        if ($user->hasRole($rolesBoundToSatker) && $user->pegawai) {
            return $user->pegawai->satuan_kerja_id;
        }
        
        return null;
    }

    /**
     * Helper: Cek apakah user punya hak untuk Mengelola (Create/Edit/Delete) Pegawai?
     * Hanya Admin Pusat dan Admin Satker yang boleh.
     */
    private function canManagePegawai()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); 
        return $user->hasRole(['admin', 'admin_satker']);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Pegawai::with('satuanKerja');

        // --- 1. LOGIKA FILTERING ---
        if ($user->role === 'admin') {
            // SUPER ADMIN: Bisa lihat semua, bisa filter by request
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('satuan_kerja_id', $request->satuan_kerja_id);
            }
        } else {
            // ADMIN LOKAL (Satker/P2M/Berantas/Rehab): Terkunci di Satkernya
            $mySatkerId = $this->getMySatkerId();

            if ($mySatkerId) {
                $query->where('satuan_kerja_id', $mySatkerId);
            } else {
                $query->where('id', 0); // Safety
            }
        }

        // --- 2. PENCARIAN ---
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%$search%")
                  ->orWhere('nip', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }

        // --- 3. SORTING ---
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowSort = ['nama', 'email', 'created_at', 'nip'];
        
        if (in_array($sortBy, $allowSort)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        // --- 4. PAGINATION ---
        $perPage = $request->input('per_page', 10);
        $pegawais = $query->paginate($perPage)->withQueryString();

        $satuanKerjas = ($user->role === 'admin') ? SatuanKerja::orderBy('satuan_kerja')->get() : collect([]);

        return view('admin.pegawai.index', compact('pegawais', 'satuanKerjas'));
    }

    public function create()
    {
        // PROTEKSI: Admin Bidang DITOLAK
        if (!$this->canManagePegawai()) {
            abort(403, 'Hanya Admin Satker yang boleh menambah pegawai.');
        }

        $user = Auth::user();
        $satuanKerjas = [];
        $mySatker = null;

        if ($user->role === 'admin') {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja')->get();
        } else {
            // Admin Satker otomatis dapat satkernya sendiri
            $mySatker = $user->pegawai->satuanKerja; 
        }

        return view('admin.pegawai.create', compact('satuanKerjas', 'mySatker'));
    }

    public function store(Request $request)
    {
        // PROTEKSI: Admin Bidang DITOLAK
        if (!$this->canManagePegawai()) {
            abort(403);
        }

        $user = Auth::user();

        // Validasi Dasar
        $rules = [
            'nip' => 'required|string|unique:pegawai,nip',
            'nama' => 'required|string|max:255',
            'email' => 'required|email|unique:pegawai,email',
            'nomor_hp' => 'nullable|string|max:20',
        ];

        // Validasi Satker: Wajib dipilih jika Super Admin
        if ($user->role === 'admin') {
            $rules['satuan_kerja_id'] = 'required|exists:satuan_kerja,id';
        }

        $validated = $request->validate($rules);

        // Jika Admin Satker, paksa satker_id sesuai akun login
        if ($user->role === 'admin_satker') {
            $validated['satuan_kerja_id'] = $this->getMySatkerId();
        }

        Pegawai::create($validated);

        return redirect()->route('admin.pegawai.index')->with('success', 'Pegawai berhasil ditambahkan.');
    }

    public function edit($nip) 
    {
        // PROTEKSI: Admin Bidang DITOLAK
        if (!$this->canManagePegawai()) {
            abort(403, 'Hanya Admin Satker yang boleh mengedit pegawai.');
        }

        $user = Auth::user();
        $pegawai = Pegawai::where('nip', $nip)->firstOrFail();

        // PROTEKSI: Admin Satker tidak boleh edit pegawai satker lain
        if ($user->role === 'admin_satker' && $pegawai->satuan_kerja_id !== $this->getMySatkerId()) {
            abort(403, 'Anda tidak berhak mengakses data pegawai ini.');
        }

        $satuanKerjas = ($user->role === 'admin') ? SatuanKerja::orderBy('satuan_kerja')->get() : [];
        
        return view('admin.pegawai.edit', compact('pegawai', 'satuanKerjas'));
    }

    public function update(Request $request, $nip)
    {
        // 1. PROTEKSI HAK AKSES (Sama seperti sebelumnya)
        if (!$this->canManagePegawai()) {
            abort(403);
        }

        $user = Auth::user();
        $pegawai = Pegawai::where('nip', $nip)->firstOrFail();

        if ($user->role === 'admin_satker' && $pegawai->satuan_kerja_id !== $this->getMySatkerId()) {
            abort(403);
        }

        // 2. VALIDASI DATA
        $rules = [
            'nip' => ['required', 'string', Rule::unique('pegawai', 'nip')->ignore($pegawai->nip, 'nip')],
            'nama' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('pegawai', 'email')->ignore($pegawai->nip, 'nip')],
            'nomor_hp' => 'nullable|string|max:20',
        ];

        if ($user->role === 'admin') {
            $rules['satuan_kerja_id'] = 'required|exists:satuan_kerja,id';
        }

        $validated = $request->validate($rules);

        if ($user->role === 'admin_satker') {
            unset($validated['satuan_kerja_id']);
        }

        // 3. EKSEKUSI UPDATE DENGAN TRANSACTION
        try {
            DB::transaction(function () use ($pegawai, $validated) {
                
                // A. Update Data Pegawai
                // Jika NIP berubah, karena Anda set 'onUpdate cascade' di migration,
                // maka foreign key di tabel users otomatis ikut berubah. Aman.
                $pegawai->update($validated);

                // B. Sinkronisasi Data User
                // Kita cari user berdasarkan NIP yang BARU (jika NIP diedit, $pegawai->nip sudah terupdate di memori model)
                $linkedUser = User::where('pegawai_nip', $pegawai->nip)->first();

                if ($linkedUser) {
                    // Update email & nama di tabel users agar sinkron
                    $linkedUser->update([
                        'email' => $validated['email'],
                        'name'  => $validated['nama'], 
                    ]);
                }
            });

            return redirect()->route('admin.pegawai.index')->with('success', 'Data pegawai (dan akun user terkait) berhasil diperbarui.');

        } catch (\Exception $e) {
            // Jika terjadi error di dalam transaction (misal email user bentrok dengan admin lain),
            // semua perubahan akan di-rollback.
            return back()->withInput()->with('error', 'Gagal mengupdate data: ' . $e->getMessage());
        }
    }
    
    public function destroy($nip)
    {
        // PROTEKSI: Admin Bidang DITOLAK
        if (!$this->canManagePegawai()) {
            abort(403);
        }

        $user = Auth::user();
        $pegawai = Pegawai::where('nip', $nip)->firstOrFail();

        if ($user->role === 'admin_satker' && $pegawai->satuan_kerja_id !== $this->getMySatkerId()) {
            abort(403);
        }

        $pegawai->delete();

        return redirect()->route('admin.pegawai.index')->with('success', 'Pegawai berhasil dihapus.');
    }
}