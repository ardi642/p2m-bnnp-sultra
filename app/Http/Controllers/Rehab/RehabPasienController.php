<?php

namespace App\Http\Controllers\Rehab;

use App\Http\Controllers\Controller;
use App\Models\RehabPasien;
use App\Models\RehabRiwayat;
use App\Models\SatuanKerja;
use App\Models\BerantasNarkotika;
use App\Models\Dokumen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Constants\Pendidikan;
use App\Constants\Pekerjaan;
use App\Constants\SumberPasien;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RehabPasienExport;
use App\Services\DokumenService;
use Illuminate\Support\Facades\Log;

class RehabPasienController extends Controller
{
    private function generateIdPasien($nama, $tglLahir, $jk) 
    {
        $namaBersih = strtoupper(trim($nama));
        $tgl = date('d-m-Y', strtotime($tglLahir));
        $kodeJk = $jk === 'Laki-laki' ? 'L' : 'P';
        
        return "{$namaBersih}-{$tgl}-{$kodeJk}";
    }

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
            $query->whereIn(
                DB::raw('MONTH(rehab_riwayat.tanggal_rehab)'), 
                (array)$request->bulan
            );
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
            $query->whereHas('narkotika', function($q) use ($request) {
                $q->whereIn('berantas_narkotika.id', (array)$request->narkotika_ids);
            });
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('rehab_pasien.id_pasien', 'LIKE', "%{$s}%")
                  ->orWhere('rehab_pasien.nama_pasien', 'LIKE', "%{$s}%");
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        $riwayatSorts = ['tanggal_rehab', 'pekerjaan', 'pendidikan', 'sumber_pasien', 'created_at'];
        $pasienSorts = ['id_pasien', 'nama_pasien', 'jenis_kelamin', 'tanggal_lahir'];

        if ($sortBy === 'satuan_kerja_id') {
            $query->join('satuan_kerja', 'rehab_pasien.satuan_kerja_id', '=', 'satuan_kerja.id')
                  ->orderBy('satuan_kerja.satuan_kerja', $sortOrder);
        } elseif ($sortBy === 'usia') {
            // Logika sorting khusus untuk usia menggunakan DATEDIFF di SQL
            $query->orderByRaw("DATEDIFF(rehab_riwayat.tanggal_rehab, rehab_pasien.tanggal_lahir) {$sortOrder}");
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
        
        // Mengambil semua nama pekerjaan dari database (termasuk yang diinput manual)
        $pekerjaans = RehabRiwayat::select('pekerjaan')
            ->whereNotNull('pekerjaan')
            ->distinct()
            ->orderBy('pekerjaan')
            ->pluck('pekerjaan');
        
        $years = RehabRiwayat::selectRaw('YEAR(tanggal_rehab) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');
            
        if (!$years->contains((int)date('Y'))) {
            $years->push((int)date('Y'))->sortDesc()->values();
        }

        $query = $this->getFilteredQuery($request);
        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) 
                   ? $request->input('per_page') : 10;
                   
        $data = $query->paginate($perPage)->withQueryString();

        return view('rehab.pasien.index', compact(
            'data', 'satuanKerjas', 'masterNarkotika', 'years', 'pekerjaans'
        ));
    }

    public function create()
    {
        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika')->get();
        $satuanKerjas = Auth::user()->isAdmin() ? SatuanKerja::orderBy('satuan_kerja')->get() : [];
        
        return view('rehab.pasien.create', compact('masterNarkotika', 'satuanKerjas'));
    }

    public function store(Request $request, DokumenService $dokumenService)
    {
        $user = Auth::user();
        
        $request->validate([
            'nama_pasien' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'tanggal_rehab' => 'required|date',
            'pendidikan' => 'required|string',
            'pekerjaan' => 'required|string',
            'pekerjaan_lainnya' => 'required_if:pekerjaan,Lainnya|nullable|string|max:255',
            'sumber_pasien' => 'required|string',
            'narkotika_ids' => 'required|array|min:1',
            'dokumentasi' => 'nullable|array', 
            'lampiran' => 'nullable|array',
            'dokumentasi_links' => 'nullable|array',
            'dokumentasi_links.*.nama' => 'nullable|string|max:255',
            'dokumentasi_links.*.url'  => 'nullable|url',
            'lampiran_links' => 'nullable|array',
            'lampiran_links.*.nama' => 'nullable|string|max:255',
            'lampiran_links.*.url'  => 'nullable|url',
        ]);

        $satkerId = $user->isAdmin() ? $request->satuan_kerja_id : $user->getSatkerId();
                        
        $pekerjaanFix = $request->pekerjaan === 'Lainnya' 
                        ? $request->pekerjaan_lainnya 
                        : $request->pekerjaan;

        $idPasienBaru = $this->generateIdPasien(
            $request->nama_pasien, 
            $request->tanggal_lahir, 
            $request->jenis_kelamin
        );

        $uploadedPaths = [];

        DB::beginTransaction();
        try {
            $pasien = RehabPasien::create([
                'satuan_kerja_id' => $satkerId,
                'id_pasien' => $idPasienBaru,
                'nama_pasien' => $request->nama_pasien,
                'tanggal_lahir' => $request->tanggal_lahir,
                'jenis_kelamin' => $request->jenis_kelamin,
            ]);

            $riwayat = $pasien->riwayat()->create([
                'tanggal_rehab' => $request->tanggal_rehab,
                'pendidikan' => $request->pendidikan,
                'pekerjaan' => $pekerjaanFix,
                'sumber_pasien' => $request->sumber_pasien,
            ]);

            $riwayat->narkotika()->sync($request->narkotika_ids);

            if ($request->filled('dokumentasi')) { 
                $dokumenService->moveToPermanent($request->input('dokumentasi'), $riwayat, 'dokumentasi', $uploadedPaths); 
            }
            if ($request->filled('lampiran')) { 
                $dokumenService->moveToPermanent($request->input('lampiran'), $riwayat, 'lampiran', $uploadedPaths); 
            }
            if ($request->filled('dokumentasi_links')) { 
                $dokumenService->saveLinks($request->input('dokumentasi_links'), $riwayat, 'dokumentasi'); 
            }
            if ($request->filled('lampiran_links')) { 
                $dokumenService->saveLinks($request->input('lampiran_links'), $riwayat, 'lampiran'); 
            }

            DB::commit();
            return redirect()
                ->route('rehab.pasien.show', $pasien->id)
                ->with('success', 'Pasien Baru Berhasil Didaftarkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($uploadedPaths as $path) { 
                Storage::disk(config('filesystems.default'))->delete($path); 
            }
            Log::error('Error Store Pasien: ' . $e->getMessage());
            return back()
                ->with('error', 'Terjadi kesalahan server saat menyimpan data.')
                ->withInput();
        }
    }

    public function show($id)
    {
        $pasien = RehabPasien::with(['riwayat.narkotika', 'riwayat.dokumen', 'satuanKerja'])
                             ->findOrFail($id);
                             
        if (!Auth::user()->isAdmin() && $pasien->satuan_kerja_id !== Auth::user()->getSatkerId()) {
            abort(403);
        }
        
        return view('rehab.pasien.show', compact('pasien'));
    }

    public function edit($id)
    {
        $pasien = RehabPasien::findOrFail($id);
        if (!Auth::user()->isAdmin() && $pasien->satuan_kerja_id !== Auth::user()->getSatkerId()) {
            abort(403);
        }
        
        return view('rehab.pasien.edit', compact('pasien'));
    }

    public function update(Request $request, $id)
    {
        $pasien = RehabPasien::findOrFail($id);
        if (!Auth::user()->isAdmin() && $pasien->satuan_kerja_id !== Auth::user()->getSatkerId()) {
            abort(403);
        }

        $request->validate([
            'nama_pasien' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
        ]);

        $idPasienBaru = $this->generateIdPasien(
            $request->nama_pasien, 
            $request->tanggal_lahir, 
            $request->jenis_kelamin
        );

        $pasien->update([
            'id_pasien' => $idPasienBaru,
            'nama_pasien' => $request->nama_pasien,
            'tanggal_lahir' => $request->tanggal_lahir,
            'jenis_kelamin' => $request->jenis_kelamin,
        ]);

        if ($request->query('ref') === 'index') {
            return redirect()
                ->route('rehab.pasien.index')
                ->with('success', 'Identitas Pasien berhasil diperbarui.');
        }
        
        return redirect()
            ->route('rehab.pasien.show', $pasien->id)
            ->with('success', 'Identitas Pasien berhasil diperbarui.');
    }

    public function createRiwayat($pasien_id)
    {
        $pasien = RehabPasien::findOrFail($pasien_id);
        if (!Auth::user()->isAdmin() && $pasien->satuan_kerja_id !== Auth::user()->getSatkerId()) {
            abort(403);
        }

        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika')->get();
        return view('rehab.pasien.create_riwayat', compact('pasien', 'masterNarkotika'));
    }

    public function storeRiwayat(Request $request, DokumenService $dokumenService, $pasien_id)
    {
        $pasien = RehabPasien::findOrFail($pasien_id);
        if (!Auth::user()->isAdmin() && $pasien->satuan_kerja_id !== Auth::user()->getSatkerId()) {
            abort(403);
        }

        $request->validate([
            'tanggal_rehab' => 'required|date',
            'pendidikan' => 'required|string',
            'pekerjaan' => 'required|string',
            'pekerjaan_lainnya' => 'required_if:pekerjaan,Lainnya|nullable|string|max:255',
            'sumber_pasien' => 'required|string',
            'narkotika_ids' => 'required|array|min:1',
            'dokumentasi' => 'nullable|array', 
            'lampiran' => 'nullable|array',
            'dokumentasi_links' => 'nullable|array',
            'lampiran_links' => 'nullable|array',
        ]);

        $pekerjaanFix = $request->pekerjaan === 'Lainnya' 
                        ? $request->pekerjaan_lainnya 
                        : $request->pekerjaan;
                        
        $uploadedPaths = [];

        DB::beginTransaction();
        try {
            $riwayat = $pasien->riwayat()->create([
                'tanggal_rehab' => $request->tanggal_rehab,
                'pendidikan' => $request->pendidikan,
                'pekerjaan' => $pekerjaanFix,
                'sumber_pasien' => $request->sumber_pasien,
            ]);
            
            $riwayat->narkotika()->sync($request->narkotika_ids);

            if ($request->filled('dokumentasi')) { 
                $dokumenService->moveToPermanent($request->input('dokumentasi'), $riwayat, 'dokumentasi', $uploadedPaths); 
            }
            if ($request->filled('lampiran')) { 
                $dokumenService->moveToPermanent($request->input('lampiran'), $riwayat, 'lampiran', $uploadedPaths); 
            }
            if ($request->filled('dokumentasi_links')) { 
                $dokumenService->saveLinks($request->input('dokumentasi_links'), $riwayat, 'dokumentasi'); 
            }
            if ($request->filled('lampiran_links')) { 
                $dokumenService->saveLinks($request->input('lampiran_links'), $riwayat, 'lampiran'); 
            }

            DB::commit();
            return redirect()
                ->route('rehab.pasien.show', $pasien->id)
                ->with('success', 'Riwayat Rehabilitasi Baru Berhasil Ditambahkan.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($uploadedPaths as $path) { 
                Storage::disk(config('filesystems.default'))->delete($path); 
            }
            return back()
                ->with('error', 'Gagal menambahkan riwayat kedatangan.')
                ->withInput();
        }
    }

    public function editRiwayat($id)
    {
        $riwayat = RehabRiwayat::with(['pasien', 'narkotika', 'dokumen'])->findOrFail($id);
        if (!Auth::user()->isAdmin() && $riwayat->pasien->satuan_kerja_id !== Auth::user()->getSatkerId()) {
            abort(403);
        }

        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika')->get();
        return view('rehab.pasien.edit_riwayat', compact('riwayat', 'masterNarkotika'));
    }

    public function updateRiwayat(Request $request, DokumenService $dokumenService, $id)
    {
        $riwayat = RehabRiwayat::findOrFail($id);
        if (!Auth::user()->isAdmin() && $riwayat->pasien->satuan_kerja_id !== Auth::user()->getSatkerId()) {
            abort(403);
        }

        $request->validate([
            'tanggal_rehab' => 'required|date',
            'pendidikan' => 'required|string',
            'pekerjaan' => 'required|string',
            'pekerjaan_lainnya' => 'required_if:pekerjaan,Lainnya|nullable|string|max:255',
            'sumber_pasien' => 'required|string',
            'narkotika_ids' => 'required|array|min:1',
            'delete_files' => 'nullable|array', 
            'dokumentasi' => 'nullable|array',
            'lampiran' => 'nullable|array',
            'dokumentasi_links' => 'nullable|array',
            'lampiran_links' => 'nullable|array',
        ]);

        $pekerjaanFix = $request->pekerjaan === 'Lainnya' 
                        ? $request->pekerjaan_lainnya 
                        : $request->pekerjaan;
                        
        $newFilesMoved = [];
        $filesToDelete = [];

        DB::beginTransaction();
        try {
            $riwayat->update([
                'tanggal_rehab' => $request->tanggal_rehab,
                'pendidikan' => $request->pendidikan,
                'pekerjaan' => $pekerjaanFix,
                'sumber_pasien' => $request->sumber_pasien,
            ]);
            $riwayat->narkotika()->sync($request->narkotika_ids);

            if ($request->has('delete_files')) {
                $filesToRemove = Dokumen::whereIn('id', $request->delete_files)->get();
                foreach ($filesToRemove as $file) {
                    if (!$file->is_link) $filesToDelete[] = $file->path_file; 
                    $file->delete();
                }
            }

            if ($request->filled('dokumentasi')) { 
                $dokumenService->moveToPermanent($request->input('dokumentasi'), $riwayat, 'dokumentasi', $newFilesMoved); 
            }
            if ($request->filled('lampiran')) { 
                $dokumenService->moveToPermanent($request->input('lampiran'), $riwayat, 'lampiran', $newFilesMoved); 
            }
            if ($request->filled('dokumentasi_links')) { 
                $dokumenService->saveLinks($request->input('dokumentasi_links'), $riwayat, 'dokumentasi'); 
            }
            if ($request->filled('lampiran_links')) { 
                $dokumenService->saveLinks($request->input('lampiran_links'), $riwayat, 'lampiran'); 
            }

            DB::commit();

            foreach ($filesToDelete as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            if ($request->query('ref') === 'index') {
                return redirect()
                    ->route('rehab.pasien.index')
                    ->with('success', 'Data Riwayat Kedatangan berhasil diperbarui.');
            }
            
            return redirect()
                ->route('rehab.pasien.show', $riwayat->rehab_pasien_id)
                ->with('success', 'Data Riwayat Kedatangan berhasil diperbarui.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($newFilesMoved as $path) { 
                if(Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path); 
            }
            return back()->with('error', 'Gagal memperbarui riwayat kedatangan.')->withInput();
        }
    }

    public function destroy($id)
    {
        $riwayat = RehabRiwayat::with('dokumen')->findOrFail($id);
        $pasienId = $riwayat->rehab_pasien_id;
        
        $pasien = RehabPasien::findOrFail($pasienId);
        if (!Auth::user()->isAdmin() && $pasien->satuan_kerja_id !== Auth::user()->getSatkerId()) {
            abort(403);
        }

        $filesToDelete = [];
        foreach ($riwayat->dokumen()->cursor() as $doc) {
            if (!$doc->is_link && !empty($doc->path_file)) {
                $filesToDelete[] = $doc->path_file;
            }
        }

        DB::beginTransaction();
        try {
            $riwayat->delete();
            if(RehabRiwayat::where('rehab_pasien_id', $pasienId)->count() == 0) {
                $pasien->delete();
            }
            DB::commit();

            foreach ($filesToDelete as $path) {
                if ($path && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            return back()->with('success', 'Data riwayat dan dokumen berhasil dihapus.');
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