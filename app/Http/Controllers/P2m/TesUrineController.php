<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\P2mTesUrine;
use App\Models\SatuanKerja;
use App\Models\Pegawai;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Exports\TesUrineExport; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // <--- JANGAN LUPA INI
use Maatwebsite\Excel\Facades\Excel;

class TesUrineController extends Controller
{
    // --- PRIVATE QUERY BUILDER (Untuk Index & Export) ---
    private function getFilteredQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $activeYears = $request->filled('tahun') ? $request->tahun : [date('Y')];
        
        $query = P2mTesUrine::with('pegawai', 'satuanKerja');

        // ... (Bagian Filter Satker, Bulan, Tahun, Anggaran, Sasaran, Pegawai TETAP SAMA seperti sebelumnya) ...
        // Filter Satker
        if ($user->isAdmin()) {
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('satuan_kerja_id', $request->satuan_kerja_id);
            }
        } else if ($user->isOperator()){
            $query->where('satuan_kerja_id', $user->getSatkerId());
        }

        // Filter Bulan
        if ($request->filled('bulan')) {
            $query->where(function($q) use ($request) {
                foreach ($request->bulan as $b) {
                    $q->orWhereMonth('tanggal_pelaksanaan', $b);
                }
            });
        }
        
        // Filter Tahun
        $query->where(function($q) use ($activeYears) {
            foreach ($activeYears as $y) {
                $q->orWhereYear('tanggal_pelaksanaan', $y);
            }
        });

        if ($request->filled('anggaran_pelaksanaan')) $query->whereIn('anggaran_pelaksanaan', $request->anggaran_pelaksanaan);
        if ($request->filled('sasaran_kegiatan')) $query->whereIn('sasaran_kegiatan', $request->sasaran_kegiatan);
        
        if ($request->filled('pegawai_nips')) {
            $nips = $request->pegawai_nips;
            $logic = $request->input('pegawai_logic', 'OR');
            if ($logic === 'AND') {
                foreach ($nips as $nip) $query->whereHas('pegawai', fn($q) => $q->where('pegawai.nip', $nip));
            } else {
                $query->whereHas('pegawai', fn($q) => $q->whereIn('pegawai.nip', $nips));
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_instansi_pelaksana', 'LIKE', "%{$search}%")
                    ->orWhere('tempat_kegiatan', 'LIKE', "%{$search}%")
                    ->orWhere('sasaran_kegiatan', 'LIKE', "%{$search}%")
                    ->orWhere('keterangan_positif', 'LIKE', "%{$search}%")
                    ->orWhereHas('satuanKerja', fn($subQ) => $subQ->where('satuan_kerja', 'LIKE', "%{$search}%"))
                    ->orWhereHas('pegawai', fn($subQ) => $subQ->where('nama', 'LIKE', "%{$search}%"));
            });
        }

        // --- PERBAIKAN SORTING DI SINI ---
        $sortBy = $request->input('sort_by', 'created_at'); // Default 'created_at'
        $sortOrder = $request->input('sort_order', 'desc'); // Default 'desc'

        $allowSort = [
            'anggaran_pelaksanaan', 
            'nama_instansi_pelaksana', 
            'sasaran_kegiatan', 
            'tanggal_pelaksanaan', 
            'tempat_kegiatan', 
            'jumlah_peserta', 
            'jumlah_positif', 
            'created_at', // Pastikan ini ada
            'satuan_kerja'
        ];

        // Validasi agar tidak error query column not found
        if (in_array($sortBy, $allowSort)) {
            if ($sortBy === 'satuan_kerja') {
                // Join agar bisa sort berdasarkan nama satker, bukan ID
                $query->join('satuan_kerja', 'p2m_tes_urine.satuan_kerja_id', '=', 'satuan_kerja.id')
                        ->orderBy('satuan_kerja.satuan_kerja', $sortOrder)
                        // PENTING: Select tabel utama agar ID tidak tertimpa ID satker
                        ->select('p2m_tes_urine.*'); 
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }
        } else {
            // Fallback default sorting
            $query->latest(); 
        }

        return $query;
    }

    // --- 1. INDEX ---
    public function index(Request $request): View {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->isAdmin()) {
            $pegawais = Pegawai::orderBy('nama', 'asc')->get(['nip', 'nama']);
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        } else {
            $satkerId = $user->getSatkerId();
            $pegawais = Pegawai::where('satuan_kerja_id', $satkerId)->orderBy('nama', 'asc')->get(['nip', 'nama']);
            $satuanKerjas = [];
        }

        $years = P2mTesUrine::selectRaw('YEAR(tanggal_pelaksanaan) as year')->distinct()->orderBy('year', 'desc')->pluck('year');
        $query = $this->getFilteredQuery($request);
        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : 10;
        
        $tes_urines = $query->paginate($perPage)->withQueryString();
        return view('p2m.tes-urine.index', compact('tes_urines', 'satuanKerjas', 'years', 'pegawais', 'user'));
    }

    // --- 2. EXPORT ---
    public function export(Request $request) {
        $query = $this->getFilteredQuery($request);
        return Excel::download(new TesUrineExport($query), 'Laporan_P2M_Tes_Urine.xlsx');
    }

    // --- 3. CREATE ---
    public function create(): View {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->isAdmin()) {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
            $pegawais = Pegawai::with('satuanKerja')->orderBy('nama', 'asc')->get();
        } else {
            $satuanKerjas = [];
            $satkerId = $user->getSatkerId();
            $pegawais = Pegawai::with('satuanKerja')->where('satuan_kerja_id', $satkerId)->orderBy('nama', 'asc')->get();
        }

        return view('p2m.tes-urine.create', compact('satuanKerjas', 'pegawais'));
    }

    // --- 4. STORE ---
    public function store(Request $request) {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $rules = [
            'anggaran_pelaksanaan' => 'required',
            'sasaran_kegiatan' => 'required',
            'nama_instansi_pelaksana' => 'required|string|max:255',
            'tanggal_pelaksanaan' => 'required|date',
            'tempat_kegiatan' => 'required',
            'jumlah_peserta' => 'required|numeric|min:0',
            // Validasi: Jumlah Positif tidak boleh lebih dari peserta
            'jumlah_positif' => 'required|numeric|min:0|lte:jumlah_peserta', 
            'keterangan_positif' => 'nullable|string',
            'link_kelengkapan_dokumentasi' => 'required',
            'pegawai_nips' => 'required|array',
            'pegawai_nips.*' => 'exists:pegawai,nip',
        ];

        if ($user->isAdmin()) $rules['satuan_kerja_id'] = 'required|exists:satuan_kerja,id';

        $validasi = $request->validate($rules);

        DB::transaction(function () use ($user, $validasi) {
            $pegawaiNips = $validasi['pegawai_nips'];
            $dataKegiatan = collect($validasi)->except('pegawai_nips')->toArray();

            if ($user->isOperator()) $dataKegiatan['satuan_kerja_id'] = $user->getSatkerId();

            $kegiatan = P2mTesUrine::create($dataKegiatan);
            $kegiatan->pegawai()->attach($pegawaiNips);
        });

        return redirect()->route('p2m.tes_urine.index')
            ->with('success', 'store')->with('message', 'Berhasil menambahkan data Tes Urine');
    }

    // --- 5. EDIT ---
    public function edit($id): View {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kegiatan = P2mTesUrine::with('pegawai')->findOrFail($id);

        if ($user->isOperator() && $kegiatan->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403, 'Anda tidak berhak mengubah data Satuan Kerja lain.');
        }

        if ($user->isAdmin()) {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
            $pegawais = Pegawai::orderBy('nama', 'asc')->get();
        } else {
            $satuanKerjas = [];
            $satkerId = $user->getSatkerId();
            $pegawais = Pegawai::where('satuan_kerja_id', $satkerId)->orderBy('nama', 'asc')->get();
        }

        $selectedPegawaiNips = $kegiatan->pegawai->pluck('nip')->toArray();
        return view('p2m.tes-urine.edit', compact('kegiatan', 'satuanKerjas', 'pegawais', 'selectedPegawaiNips'));
    }

    // --- 6. UPDATE ---
    public function update(Request $request, $id) {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kegiatan = P2mTesUrine::findOrFail($id);

        if ($user->isOperator() && $kegiatan->satuan_kerja_id !== $user->getSatkerId()) abort(403);

        $rules = [
            'anggaran_pelaksanaan' => 'required',
            'sasaran_kegiatan' => 'required',
            'nama_instansi_pelaksana' => 'required|string|max:255',
            'tanggal_pelaksanaan' => 'required|date',
            'tempat_kegiatan' => 'required',
            'jumlah_peserta' => 'required|numeric|min:0',
            'jumlah_positif' => 'required|numeric|min:0|lte:jumlah_peserta',
            'keterangan_positif' => 'nullable|string',
            'link_kelengkapan_dokumentasi' => 'required',
            'pegawai_nips' => 'required|array',
            'pegawai_nips.*' => 'exists:pegawai,nip',
        ];

        if ($user->isAdmin()) $rules['satuan_kerja_id'] = 'required';

        $validasi = $request->validate($rules);

        DB::transaction(function () use ($validasi, $kegiatan, $user) {
            $pegawaiNips = $validasi['pegawai_nips'];
            $dataUpdate = collect($validasi)->except('pegawai_nips')->toArray();

            if ($user->isOperator()) unset($dataUpdate['satuan_kerja_id']);

            $kegiatan->update($dataUpdate);
            $kegiatan->pegawai()->sync($pegawaiNips);
        });

        return redirect()->route('p2m.tes_urine.index')
            ->with('success', 'update')->with('message', 'Data berhasil diperbarui');
    }

    // --- 7. DESTROY ---
    public function destroy($id) {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $data = P2mTesUrine::findOrFail($id);

        if ($user->isOperator() && $data->satuan_kerja_id !== $user->getSatkerId()) abort(403);

        $data->delete();
        return redirect()->back()
            ->with('success', 'destroy')->with('message', 'Data berhasil dihapus');
    }
}