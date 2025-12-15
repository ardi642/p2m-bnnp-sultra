<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use App\Models\SatuanKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PegawaiController extends Controller
{
    private function getMySatkerId()
    {
        $user = Auth::user();
        if ($user->role === 'admin_satker' && $user->pegawai) {
            return $user->pegawai->satuan_kerja_id;
        }
        return null;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        // 1. Mulai Query
        $query = Pegawai::with('satuanKerja');

        // 2. CEK ROLE USER YANG LOGIN
        if ($user->role === 'admin') {
            
            // --- LOGIC SUPER ADMIN ---
            // Boleh melihat semua, tapi kalau dia pilih filter, kita turuti.
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('satuan_kerja_id', $request->satuan_kerja_id);
            }

        } else {
            
            // --- LOGIC ADMIN SATKER ---
            // KITA PAKSA QUERY-NYA. Tidak peduli input filter apa yang dikirim.
            // Ambil ID Satker dari Pegawai yang terhubung dengan User ini.
            $mySatkerId = $user->pegawai->satuan_kerja_id ?? null;

            // Jika user punya satker, filter query berdasarkan ID tersebut
            if ($mySatkerId) {
                $query->where('satuan_kerja_id', $mySatkerId);
            } else {
                // (Opsional) Jika user tidak punya satker (error data), jangan tampilkan apa-apa
                $query->where('id', 0); 
            }
        }

        // 3. Logic Pencarian (Search Global) - Berlaku untuk kedua role
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%$search%")
                ->orWhere('nip', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%");
            });
        }

        // 4. Logic Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowSort = ['nama', 'email', 'created_at', 'nip'];
        
        if (in_array($sortBy, $allowSort)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        // 5. Pagination
        $perPage = $request->input('per_page', 10);
        $pegawais = $query->paginate($perPage)->appends($request->all());

        // Data dropdown hanya dikirim jika admin (untuk efisiensi)
        $satuanKerjas = $user->role === 'admin' ? SatuanKerja::all() : collect([]);

        return view('admin.pegawai.index', compact('pegawais', 'satuanKerjas'));
    }

    public function create()
    {
        $user = Auth::user();
        $satuanKerjas = [];
        $mySatker = null;

        if ($user->role === 'admin') {
            $satuanKerjas = SatuanKerja::all();
        } else {
            // Ambil data satker milik admin tersebut
            $mySatker = $user->pegawai->satuanKerja; 
        }

        return view('admin.pegawai.create', compact('satuanKerjas', 'mySatker'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // 1. Validasi Input
        $rules = [
            'nip' => 'required|string|unique:pegawai,nip', // NIP harus unik
            'nama' => 'required|string|max:255',
            'email' => 'required|email|unique:pegawai,email', // Email harus unik
            'nomor_hp' => 'nullable|string|max:20',
        ];

        // Validasi Satker (Khusus Admin Pusat)
        if ($user->role === 'admin') {
            $rules['satuan_kerja_id'] = 'required|exists:satuan_kerja,id';
        }

        $validated = $request->validate($rules);

        // 2. Override Satker ID jika Admin Satker
        if ($user->role === 'admin_satker') {
            $validated['satuan_kerja_id'] = $this->getMySatkerId();
        }

        Pegawai::create($validated);

        return redirect()->route('admin.pegawai.index')->with('success', 'Pegawai berhasil ditambahkan.');
    }

    public function edit($nip) 
    {
        $user = Auth::user();

        // 2. Cari manual menggunakan primary key (nip)
        // Gunakan findOrFail agar jika NIP salah/tidak ketemu, langsung 404
        $pegawai = Pegawai::where('nip', $nip)->firstOrFail();

        // PROTEKSI: Cek Satker
        if ($user->role === 'admin_satker' && $pegawai->satuan_kerja_id !== $this->getMySatkerId()) {
            abort(403, 'Anda tidak berhak mengakses data pegawai dari satker lain.');
        }

        $satuanKerjas = ($user->role === 'admin') ? SatuanKerja::all() : [];
        // Kirim ke view
        return view('admin.pegawai.edit', compact('pegawai', 'satuanKerjas'));
    }

    public function update(Request $request, $nip)
    {
        $user = Auth::user();
        
        // Cari manual juga disini
        $pegawai = Pegawai::where('nip', $nip)->firstOrFail();

        // PROTEKSI
        if ($user->role === 'admin_satker' && $pegawai->satuan_kerja_id !== $this->getMySatkerId()) {
            abort(403);
        }

        // VALIDASI
        $rules = [
            // Ignore validasi unique untuk NIP milik pegawai ini sendiri
            'nip' => ['required', 'string', \Illuminate\Validation\Rule::unique('pegawai', 'nip')->ignore($pegawai->nip, 'nip')],
            'nama' => 'required|string|max:255',
            'email' => ['required', 'email', \Illuminate\Validation\Rule::unique('pegawai', 'email')->ignore($pegawai->nip, 'nip')],
            'nomor_hp' => 'nullable|string|max:20',
        ];

        if ($user->role === 'admin') {
            $rules['satuan_kerja_id'] = 'required|exists:satuan_kerja,id';
        }

        $validated = $request->validate($rules);

        if ($user->role === 'admin_satker') {
            unset($validated['satuan_kerja_id']);
        }

        // Update data
        // Perhatikan: Jika NIP diubah, primary key di DB berubah. 
        // Laravel biasanya menangani ini, tapi hati-hati jika ada relasi foreign key.
        $pegawai->update($validated);

        return redirect()->route('admin.pegawai.index')->with('success', 'Data pegawai berhasil diperbarui.');
    }
    
    public function destroy($nip)
    {
        $user = Auth::user();
        $pegawai = Pegawai::where('nip', $nip)->firstOrFail();

        if ($user->role === 'admin_satker' && $pegawai->satuan_kerja_id !== $this->getMySatkerId()) {
            abort(403);
        }

        $pegawai->delete();

        return redirect()->route('admin.pegawai.index')->with('success', 'Pegawai berhasil dihapus.');
    }
}