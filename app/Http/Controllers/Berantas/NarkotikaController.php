<?php

namespace App\Http\Controllers\Berantas;

use App\Http\Controllers\Controller;
use App\Models\BerantasNarkotika;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException; // WAJIB ADA: Untuk menangkap error RESTRICT

class NarkotikaController extends Controller
{
    public function index(Request $request)
    {
        // 1. Whitelist Kolom yang boleh di-sort
        $allowedSorts = ['nama_narkotika', 'golongan', 'created_at'];

        // 2. Default Sort
        $sortBy = $request->input('sort_by');
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at'; 
        }

        // 3. Default Direction
        $sortDirection = $request->input('sort_direction');
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        // 4. Query Data
        $query = BerantasNarkotika::query();

        // Logika Pencarian
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nama_narkotika', 'LIKE', '%' . $search . '%')
                  ->orWhere('golongan', 'LIKE', '%' . $search . '%');
            });
        }

        // Eksekusi Sorting & Pagination
        $data = $query->orderBy($sortBy, $sortDirection)
                      ->paginate($perPage);

        // 5. Appends (Agar filter search/sort tidak hilang saat klik halaman berikutnya)
        $data->appends([
            'sort_by' => $sortBy, 
            'sort_direction' => $sortDirection, 
            'per_page' => $perPage,
            'search' => $search
        ]);

        return view('berantas.narkotika.index', compact('data', 'sortBy', 'sortDirection', 'perPage', 'search'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_narkotika' => 'required|string|max:255|unique:berantas_narkotika,nama_narkotika',
            'golongan' => 'required|in:Golongan I,Golongan II,Golongan III,Non Golongan'
        ]);

        BerantasNarkotika::create($request->all());
        return back()->with('success', 'Data berhasil disimpan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_narkotika' => 'required|string|max:255|unique:berantas_narkotika,nama_narkotika,'.$id,
            'golongan' => 'required|in:Golongan I,Golongan II,Golongan III,Non Golongan'
        ]);

        BerantasNarkotika::findOrFail($id)->update($request->all());
        return back()->with('success', 'Data berhasil diperbarui.');
    }

    // --- BAGIAN PENTING: TRY-CATCH DELETE UNTUK RESTRICT ---
    public function destroy($id)
    {
        try {
            $item = BerantasNarkotika::findOrFail($id);
            $item->delete();
            
            return back()->with('success', 'Data berhasil dihapus.');

        } catch (QueryException $e) {
            // Error Code 23000 = Integrity Constraint Violation (Foreign Key Restrict Error)
            // Ini terjadi karena kita pasang onDelete('restrict') di migration
            if ($e->getCode() == '23000') {
                return back()->with('error', 'GAGAL MENGHAPUS! Data Narkotika ini sedang digunakan sebagai referensi di 
                data lain (seperti Ungkap Kasus, TAT, atau Barang Bukti). Mohon hapus data terkait terlebih dahulu.');
            }
            
            // Error database lain
            return back()->with('error', 'Terjadi kesalahan database: ' . $e->getMessage());
        } catch (\Exception $e) {
            // Error umum
            return back()->with('error', 'Terjadi kesalahan sistem.');
        }
    }
}