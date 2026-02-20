<?php

namespace App\Http\Controllers\Rehab;

use App\Http\Controllers\Controller;
use App\Models\RehabPasien;
use App\Models\RehabRiwayat;
use App\Models\SatuanKerja;
use App\Models\BerantasNarkotika;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Constants\Pendidikan;
use App\Constants\Pekerjaan;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RehabPasienExport;
use Illuminate\Support\Facades\Log;

class RehabPasienController extends Controller
{
    private function getFilteredQuery(Request $request)
    {
        $user = Auth::user();
        
        $query = RehabRiwayat::with(['pasien.satuanKerja', 'narkotika'])
                    ->join('rehab_pasien', 'rehab_riwayat.rehab_pasien_id', '=', 'rehab_pasien.id')
                    ->select('rehab_riwayat.*');

        if (!$user->hasRole('admin')) {
            $query->where('rehab_pasien.satuan_kerja_id', $user->getSatkerId());
        } elseif ($request->filled('satuan_kerja_id')) {
            $query->whereIn('rehab_pasien.satuan_kerja_id', (array)$request->satuan_kerja_id);
        }

        if ($request->filled('bulan')) {
            $query->whereIn(DB::raw('MONTH(rehab_riwayat.tanggal_rehab)'), (array)$request->bulan);
        }
        
        $years = $request->filled('tahun') ? (array)$request->tahun : [date('Y')];
        $query->whereIn(DB::raw('YEAR(rehab_riwayat.tanggal_rehab)'), $years);

        if ($request->filled('jenis_kelamin')) {
            $query->whereIn('rehab_pasien.jenis_kelamin', (array)$request->jenis_kelamin);
        }
        if ($request->filled('pendidikan')) {
            $query->whereIn('rehab_riwayat.pendidikan', (array)$request->pendidikan);
        }
        if ($request->filled('pekerjaan')) {
            $query->whereIn('rehab_riwayat.pekerjaan', (array)$request->pekerjaan);
        }
        if ($request->filled('sumber_pasien')) {
            $query->whereIn('rehab_riwayat.sumber_pasien', (array)$request->sumber_pasien);
        }
        if ($request->filled('narkotika_ids')) {
            $query->whereHas('narkotika', fn($q) => $q->whereIn('berantas_narkotika.id', (array)$request->narkotika_ids));
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('rehab_pasien.no_rekam_medis', 'LIKE', "%{$s}%")
                  ->orWhere('rehab_pasien.nama_pasien', 'LIKE', "%{$s}%");
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        $riwayatSorts = ['tanggal_rehab', 'usia', 'pekerjaan', 'pendidikan', 'sumber_pasien', 'created_at'];
        $pasienSorts = ['no_rekam_medis', 'nama_pasien', 'jenis_kelamin'];

        if ($sortBy === 'satuan_kerja_id') {
            $query->join('satuan_kerja', 'rehab_pasien.satuan_kerja_id', '=', 'satuan_kerja.id')
                  ->orderBy('satuan_kerja.satuan_kerja', $sortOrder);
        } elseif (in_array($sortBy, $pasienSorts)) {
            $query->orderBy('rehab_pasien.' . $sortBy, $sortOrder);
        } elseif (in_array($sortBy, $riwayatSorts)) {
            $query->orderBy('rehab_riwayat.' . $sortBy, $sortOrder);
        } else {
            $query->orderBy('rehab_riwayat.created_at', 'desc');
        }

        return $query;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        
        $satuanKerjas = $user->isAdmin() ? SatuanKerja::orderBy('satuan_kerja')->get() : [];
        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika')->get();
        
        $years = RehabRiwayat::selectRaw('YEAR(tanggal_rehab) as year')->distinct()->orderBy('year', 'desc')->pluck('year');
        if (!$years->contains((int)date('Y'))) $years->push((int)date('Y'))->sortDesc()->values();

        $query = $this->getFilteredQuery($request);
        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : 10;
        $data = $query->paginate($perPage)->withQueryString();

        return view('rehab.pasien.index', compact('data', 'satuanKerjas', 'masterNarkotika', 'years'));
    }

    public function create()
    {
        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika')->get();
        $satuanKerjas = Auth::user()->isAdmin() ? SatuanKerja::orderBy('satuan_kerja')->get() : [];
        return view('rehab.pasien.create', compact('masterNarkotika', 'satuanKerjas'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'nama_pasien' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'tanggal_rehab' => 'required|date',
            'usia' => 'required|integer|min:1',
            'pendidikan' => 'required|string',
            'pekerjaan' => 'required|string',
            'sumber_pasien' => 'required|in:Voluntary,Compulsory',
            'narkotika_ids' => 'required|array|min:1'
        ]);

        $satkerId = $user->isAdmin() ? $request->satuan_kerja_id : $user->getSatkerId();

        DB::beginTransaction();
        try {
            $year = date('Y', strtotime($request->tanggal_rehab));
            $satkerStr = str_pad($satkerId, 2, '0', STR_PAD_LEFT);
            
            $lastPasien = RehabPasien::where('satuan_kerja_id', $satkerId)
                            ->whereYear('created_at', date('Y'))
                            ->orderBy('id', 'desc')->first();
            
            $increment = 1;
            if ($lastPasien) {
                $parts = explode('-', $lastPasien->no_rekam_medis);
                $increment = intval(end($parts)) + 1;
            }
            $no_rm = "RM-{$year}-{$satkerStr}-" . str_pad($increment, 4, '0', STR_PAD_LEFT);

            $pasien = RehabPasien::create([
                'satuan_kerja_id' => $satkerId,
                'no_rekam_medis' => $no_rm,
                'nama_pasien' => $request->nama_pasien,
                'jenis_kelamin' => $request->jenis_kelamin,
            ]);

            $riwayat = $pasien->riwayat()->create([
                'tanggal_rehab' => $request->tanggal_rehab,
                'usia' => $request->usia,
                'pendidikan' => $request->pendidikan,
                'pekerjaan' => $request->pekerjaan,
                'sumber_pasien' => $request->sumber_pasien,
            ]);

            $riwayat->narkotika()->sync($request->narkotika_ids);

            DB::commit();
            return redirect()->route('rehab.pasien.show', $pasien->id)->with('success', 'Pasien Baru Berhasil Didaftarkan. Harap catat No Rekam Medis pasien ini.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Store Pasien: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan server saat menyimpan data.')->withInput();
        }
    }

    public function show($id)
    {
        $pasien = RehabPasien::with(['riwayat.narkotika', 'satuanKerja'])->findOrFail($id);
        if (!Auth::user()->isAdmin() && $pasien->satuan_kerja_id !== Auth::user()->getSatkerId()) abort(403);
        
        return view('rehab.pasien.show', compact('pasien'));
    }

    public function edit($id)
    {
        $pasien = RehabPasien::findOrFail($id);
        if (!Auth::user()->isAdmin() && $pasien->satuan_kerja_id !== Auth::user()->getSatkerId()) abort(403);
        
        return view('rehab.pasien.edit', compact('pasien'));
    }

    public function update(Request $request, $id)
    {
        $pasien = RehabPasien::findOrFail($id);
        if (!Auth::user()->isAdmin() && $pasien->satuan_kerja_id !== Auth::user()->getSatkerId()) abort(403);

        $request->validate([
            'nama_pasien' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
        ]);

        $pasien->update([
            'nama_pasien' => $request->nama_pasien,
            'jenis_kelamin' => $request->jenis_kelamin,
        ]);

        if ($request->query('ref') === 'index') {
            return redirect()->route('rehab.pasien.index')->with('success', 'Identitas Pasien berhasil diperbarui.');
        }
        return redirect()->route('rehab.pasien.show', $pasien->id)->with('success', 'Identitas Pasien berhasil diperbarui.');
    }

    public function createRiwayat($pasien_id)
    {
        $pasien = RehabPasien::findOrFail($pasien_id);
        if (!Auth::user()->isAdmin() && $pasien->satuan_kerja_id !== Auth::user()->getSatkerId()) abort(403);

        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika')->get();
        return view('rehab.pasien.create_riwayat', compact('pasien', 'masterNarkotika'));
    }

    public function storeRiwayat(Request $request, $pasien_id)
    {
        $pasien = RehabPasien::findOrFail($pasien_id);
        if (!Auth::user()->isAdmin() && $pasien->satuan_kerja_id !== Auth::user()->getSatkerId()) abort(403);

        $request->validate([
            'tanggal_rehab' => 'required|date',
            'usia' => 'required|integer|min:1',
            'pendidikan' => 'required|string',
            'pekerjaan' => 'required|string',
            'sumber_pasien' => 'required|in:Voluntary,Compulsory',
            'narkotika_ids' => 'required|array|min:1'
        ]);

        DB::beginTransaction();
        try {
            $riwayat = $pasien->riwayat()->create([
                'tanggal_rehab' => $request->tanggal_rehab,
                'usia' => $request->usia,
                'pendidikan' => $request->pendidikan,
                'pekerjaan' => $request->pekerjaan,
                'sumber_pasien' => $request->sumber_pasien,
            ]);
            $riwayat->narkotika()->sync($request->narkotika_ids);

            DB::commit();
            return redirect()->route('rehab.pasien.show', $pasien->id)->with('success', 'Riwayat Rehabilitasi Baru Berhasil Ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menambahkan riwayat kedatangan.')->withInput();
        }
    }

    public function editRiwayat($id)
    {
        $riwayat = RehabRiwayat::with(['pasien', 'narkotika'])->findOrFail($id);
        if (!Auth::user()->isAdmin() && $riwayat->pasien->satuan_kerja_id !== Auth::user()->getSatkerId()) abort(403);

        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika')->get();
        return view('rehab.pasien.edit_riwayat', compact('riwayat', 'masterNarkotika'));
    }

    public function updateRiwayat(Request $request, $id)
    {
        $riwayat = RehabRiwayat::findOrFail($id);
        if (!Auth::user()->isAdmin() && $riwayat->pasien->satuan_kerja_id !== Auth::user()->getSatkerId()) abort(403);

        $request->validate([
            'tanggal_rehab' => 'required|date',
            'usia' => 'required|integer|min:1',
            'pendidikan' => 'required|string',
            'pekerjaan' => 'required|string',
            'sumber_pasien' => 'required|in:Voluntary,Compulsory',
            'narkotika_ids' => 'required|array|min:1'
        ]);

        DB::beginTransaction();
        try {
            $riwayat->update([
                'tanggal_rehab' => $request->tanggal_rehab,
                'usia' => $request->usia,
                'pendidikan' => $request->pendidikan,
                'pekerjaan' => $request->pekerjaan,
                'sumber_pasien' => $request->sumber_pasien,
            ]);
            $riwayat->narkotika()->sync($request->narkotika_ids);

            DB::commit();
            
            if ($request->query('ref') === 'index') {
                return redirect()->route('rehab.pasien.index')->with('success', 'Data Riwayat Kedatangan berhasil diperbarui.');
            }
            return redirect()->route('rehab.pasien.show', $riwayat->rehab_pasien_id)->with('success', 'Data Riwayat Kedatangan berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memperbarui riwayat kedatangan.')->withInput();
        }
    }

    public function destroy($id)
    {
        $riwayat = RehabRiwayat::findOrFail($id);
        $pasienId = $riwayat->rehab_pasien_id;
        
        $pasien = RehabPasien::findOrFail($pasienId);
        if (!Auth::user()->isAdmin() && $pasien->satuan_kerja_id !== Auth::user()->getSatkerId()) abort(403);

        DB::beginTransaction();
        try {
            $riwayat->delete();
            if(RehabRiwayat::where('rehab_pasien_id', $pasienId)->count() == 0) {
                $pasien->delete();
            }
            DB::commit();
            return back()->with('success', 'Data riwayat berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus data riwayat.');
        }
    }

    public function export(Request $request)
    {
        $query = $this->getFilteredQuery($request);
        $fileName = 'Data_Pasien_Rehab_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new RehabPasienExport($query), $fileName);
    }
}