<?php

namespace App\Http\Controllers\Rehab;

use App\Http\Controllers\Controller;
use App\Models\RehabPasien;
use App\Models\SatuanKerja;
use App\Models\BerantasNarkotika;
use Illuminate\Http\Request;
use App\Exports\RehabPasienExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class RehabPasienController extends Controller
{
    public function index()
    {
        $query = RehabPasien::with(['narkotika', 'satuanKerja']);
        $tahunSekarang = date('Y');

        // Paksa default tahun masuk ke request
        if (!request()->has('tahun')) {
            request()->merge([
                'tahun' => [$tahunSekarang]
            ]);
        }

        // Search filter
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_pasien', 'like', "%$search%")
                    ->orWhere('pekerjaan', 'like', "%$search%")
                    ->orWhere('pendidikan', 'like', "%$search%");
            });
        }

        // Jenis Kelamin
        if (request()->filled('jenis_kelamin')) {
            $query->whereIn('jenis_kelamin', request('jenis_kelamin'));
        }

        // Pekerjaan
        if (request()->filled('pekerjaan')) {
            $query->whereIn('pekerjaan', request('pekerjaan'));
        }

        // Pendidikan
        if (request()->filled('pendidikan')) {
            $query->whereIn('pendidikan', request('pendidikan'));
        }

        // Sumber Pasien
        if (request()->filled('sumber_pasien')) {
            $query->whereIn('sumber_pasien', request('sumber_pasien'));
        }

        // Narkotika
        if (request()->filled('narkotika_id')) {
            $query->whereIn('narkotika_id', request('narkotika_id'));
        }

        // Satuan Kerja (Admin Only)
        if (request()->filled('satuan_kerja_id')) {
            $query->whereIn('satuan_kerja_id', request('satuan_kerja_id'));
        }


        // Apply year filter - default to current year if no filter selected
        $tahunFilter = request('tahun');

        if (is_array($tahunFilter)) {
            $query->whereIn(DB::raw('YEAR(created_at)'), $tahunFilter);
        } else {
            $query->whereYear('created_at', $tahunFilter);
        }


        $pasien = $query->latest()->paginate(10);
        $user = auth()->user();
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        $narkotikas = BerantasNarkotika::orderBy('nama_narkotika', 'asc')->get();

        // Get available years from database
        $tahuns = DB::table('rehab_pasien')
            ->selectRaw('DISTINCT YEAR(created_at) as tahun')
            ->whereNotNull('created_at')
            ->orderBy('tahun', 'desc')
            ->pluck('tahun');

        return view('rehab.pasien.index', compact('pasien', 'user', 'satuanKerjas', 'narkotikas', 'tahuns', 'tahunSekarang'));
    }

    public function create()
    {
        $narkotikas = BerantasNarkotika::orderBy('nama_narkotika', 'asc')->get();
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        return view('rehab.pasien.create', compact('narkotikas', 'satuanKerjas'));
    }

    public function store(Request $request)
    {
        // If user is operator (not admin), ensure we attach their satker to the request
        if (!auth()->user()->isAdmin()) {
            $satkerId = auth()->user()->getSatkerId();
            if (!$satkerId) {
                return back()->with('error', 'User belum memiliki satuan kerja.')->withInput();
            }
            $request->merge(['satuan_kerja_id' => $satkerId]);
        }

        $rules = [
            'nama_pasien' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'usia' => 'required|integer|min:0|max:120',
            'pekerjaan' => 'required|in:' . implode(',', RehabPasien::Pekerjaan),
            'pendidikan' => 'required|in:' . implode(',', RehabPasien::Pendidikan),
            'narkotika_id' => 'required|exists:berantas_narkotika,id',
            'sumber_pasien' => 'required|in:' . implode(',', RehabPasien::Sumber_pasien),
            'satuan_kerja_id' => 'required|exists:satuan_kerja,id',
        ];
        // Note: for operators we merged satuan_kerja_id into the request above

        $validated = $request->validate($rules, [
            'nama_pasien.required' => 'Nama pasien harus diisi',
            'jenis_kelamin.required' => 'Jenis kelamin harus dipilih',
            'usia.required' => 'Usia harus diisi',
            'usia.integer' => 'Usia harus berupa angka',
            'usia.min' => 'Usia tidak boleh kurang dari 0',
            'usia.max' => 'Usia tidak boleh lebih dari 120',
            'pekerjaan.required' => 'Pekerjaan harus diisi',
            'pendidikan.required' => 'Pendidikan harus dipilih',
            'narkotika_id.required' => 'Jenis narkotika harus dipilih',
            'narkotika_id.exists' => 'Jenis narkotika tidak valid',
            'sumber_pasien.required' => 'Sumber pasien harus dipilih',
            'satuan_kerja_id.required' => 'Satuan kerja harus dipilih',
            'satuan_kerja_id.exists' => 'Satuan kerja tidak valid',
        ]);

        try {
            RehabPasien::create($validated);
            return redirect()->route('rehab.pasien.index')->with('success', 'Data pasien berhasil ditambahkan');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage())->withInput();
        }
    }

    public function edit(RehabPasien $pasien)
    {
        $narkotikas = BerantasNarkotika::orderBy('nama_narkotika', 'asc')->get();
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        return view('rehab.pasien.edit', compact('pasien', 'narkotikas', 'satuanKerjas'));
    }

    public function update(Request $request, RehabPasien $pasien)
    {
        // Merge satker for non-admins so validation can require it (DB requires non-null)
        if (!auth()->user()->isAdmin()) {
            $satkerId = auth()->user()->getSatkerId();
            if (!$satkerId) {
                return back()->with('error', 'User belum memiliki satuan kerja.')->withInput();
            }
            $request->merge(['satuan_kerja_id' => $satkerId]);
        }

        $rules = [
            'nama_pasien' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'usia' => 'required|integer|min:0|max:120',
            'pekerjaan' => 'required|in:' . implode(',', RehabPasien::Pekerjaan),
            'pendidikan' => 'required|in:' . implode(',', RehabPasien::Pendidikan),
            'narkotika_id' => 'required|exists:berantas_narkotika,id',
            'sumber_pasien' => 'required|in:' . implode(',', RehabPasien::Sumber_pasien),
            'satuan_kerja_id' => 'required|exists:satuan_kerja,id',
        ];

        // Admin harus pilih satuan kerja, operator otomatis terisi
        if (auth()->user()->isAdmin()) {
            $rules['satuan_kerja_id'] = 'required|exists:satuan_kerja,id';
        }

        $validated = $request->validate($rules, [
            'nama_pasien.required' => 'Nama pasien harus diisi',
            'jenis_kelamin.required' => 'Jenis kelamin harus dipilih',
            'usia.required' => 'Usia harus diisi',
            'usia.integer' => 'Usia harus berupa angka',
            'usia.min' => 'Usia tidak boleh kurang dari 0',
            'usia.max' => 'Usia tidak boleh lebih dari 120',
            'pekerjaan.required' => 'Pekerjaan harus diisi',
            'pendidikan.required' => 'Pendidikan harus dipilih',
            'narkotika_id.required' => 'Jenis narkotika harus dipilih',
            'narkotika_id.exists' => 'Jenis narkotika tidak valid',
            'sumber_pasien.required' => 'Sumber pasien harus dipilih',
            'satuan_kerja_id.required' => 'Satuan kerja harus dipilih',
            'satuan_kerja_id.exists' => 'Satuan kerja tidak valid',
        ]);

        try {
            $pasien->update($validated);
            return redirect()->route('rehab.pasien.index')->with('success', 'Data pasien berhasil diperbarui');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui data: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(RehabPasien $pasien)
    {
        try {
            $nama = $pasien->nama_pasien;
            $pasien->delete();
            return redirect()->route('rehab.pasien.index')->with('success', "Data pasien '$nama' berhasil dihapus");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    private function getFilteredQuery(Request $request)
    {
        $query = \App\Models\RehabPasien::query();

        if ($request->filled('pendidikan')) {
            $query->whereIn('pendidikan', $request->pendidikan);
        }

        if ($request->filled('pekerjaan')) {
            $query->whereIn('pekerjaan', $request->pekerjaan);
        }

        if ($request->filled('sumber_pasien')) {
            $query->whereIn('sumber_pasien', $request->sumber_pasien);
        }

        return $query;
    }


    public function export(Request $request)
    {
        $query = $this->getFilteredQuery($request)->with(['narkotika', 'satuanKerja']);
        return Excel::download(new RehabPasienExport($query), 'Laporan_P2M_Rehab_Pasien.xlsx');
    }
}
