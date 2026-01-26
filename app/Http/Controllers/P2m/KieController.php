<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\P2mKie;
use App\Models\SatuanKerja;
use App\Models\Pegawai;
use App\Models\TemporaryFile;
use App\Models\DokumentasiKegiatan;
use App\Helpers\SearchHelper;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
// use App\Exports\KieExport; // Import jika sudah ada

class KieController extends Controller
{
    // --- BUILD QUERY ---
    private function getFilteredQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $activeYears = $request->filled('tahun') ? $request->tahun : [date('Y')];
        
        $query = P2mKie::with('pegawai.satuanKerja', 'satuanKerja');

        // Filter Satker
        if ($user->hasRole('admin')) {
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('satuan_kerja_id', $request->satuan_kerja_id);
            }
        } else {
            $satkerId = $user->getSatkerId();
            $query->where('satuan_kerja_id', $satkerId);
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

        // Filter Anggaran
        if ($request->filled('anggaran_pelaksanaan')) {
            $query->whereIn('anggaran_pelaksanaan', $request->anggaran_pelaksanaan);
        }

        // Filter Pegawai
        if ($request->filled('pegawai_nips')) {
            $nips = $request->pegawai_nips;
            $logic = $request->input('pegawai_logic', 'OR');
            if ($logic === 'AND') {
                foreach ($nips as $nip) {
                    $query->whereHas('pegawai', function($q) use ($nip) {
                        $q->where('pegawai.nip', $nip);
                    });
                }
            } else {
                $query->whereHas('pegawai', function($q) use ($nips) {
                    $q->whereIn('pegawai.nip', $nips);
                });
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $searchDate = SearchHelper::translateDateInput($search);
            $query->where(function($q) use ($search, $searchDate) {
                $q->where('tempat_kegiatan', 'LIKE', "%{$search}%")
                  ->orWhereHas('satuanKerja', function($subQ) use ($search) {
                        $subQ->where('satuan_kerja', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('pegawai', function($subQ) use ($search) {
                        $subQ->where('nama', 'LIKE', "%{$search}%");
                  })
                  ->orWhere('anggaran_pelaksanaan', 'LIKE', "%{$search}%");

                // Search Date
                $q->orWhereRaw("LOWER(DATE_FORMAT(tanggal_pelaksanaan, '%W, %d %M %Y')) LIKE ?", ["%{$searchDate}%"]);
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $rawSortOrder = $request->input('sort_order', 'desc');
        $sortOrder = in_array(strtolower($rawSortOrder), ['asc', 'desc']) ? strtolower($rawSortOrder) : 'desc';
        
        $allowSort = ['anggaran_pelaksanaan', 'tempat_kegiatan', 'tanggal_pelaksanaan', 'created_at', 'satuan_kerja'];

        if (in_array($sortBy, $allowSort)) {
            if ($sortBy === 'satuan_kerja') {
                $query->join('satuan_kerja', 'p2m_kie.satuan_kerja_id', '=', 'satuan_kerja.id')
                        ->orderBy('satuan_kerja.satuan_kerja', $sortOrder)
                        ->select('p2m_kie.*');
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }
        } else {
            $query->latest();
        }

        return $query;
    }

    public function index(Request $request): View 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            $pegawais = Pegawai::orderBy('nama', 'asc')->get(['nip', 'nama']);
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        } else {
            $satkerId = $user->getSatkerId();
            $pegawais = Pegawai::where('satuan_kerja_id', $satkerId)->orderBy('nama', 'asc')->get(['nip', 'nama']);
            $satuanKerjas = [];
        }

        // Logic Tahun (Sesuai Satker)
        $yearQuery = P2mKie::selectRaw('YEAR(tanggal_pelaksanaan) as year');
        if ($user->hasRole(['operator_satker', 'operator_p2m'])) {
            $yearQuery->where('satuan_kerja_id', $user->getSatkerId());
        }
        $years = $yearQuery->distinct()->orderBy('year', 'desc')->pluck('year');

        $query = $this->getFilteredQuery($request);

        $statsQuery = clone $query;
        $totalKegiatan = $statsQuery->count();

        $query->with('dokumentasi');

        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : 10;
        $kies = $query->paginate($perPage)->withQueryString();

        return view('p2m.kie.index', compact('kies', 'satuanKerjas', 'years', 'pegawais', 'user', 'totalKegiatan'));
    }

    public function create(): View 
    {
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

        return view('p2m.kie.create', compact('satuanKerjas', 'pegawais'));
    }

    public function store(Request $request) 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $rules = [
            'tempat_kegiatan'        => 'required',
            'anggaran_pelaksanaan' => 'required|in:DIPA,NON DIPA',
            'tanggal_pelaksanaan'    => 'required|date',
            'pegawai_nips'           => 'required|array',
            'pegawai_nips.*'         => 'exists:pegawai,nip',
            'dokumentasi'            => 'nullable|array',
            'dokumentasi.*'          => 'required',
        ];

        if ($user->isAdmin()) {
            $rules['satuan_kerja_id'] = 'required';
        }

        $validasi = $request->validate($rules);
        $filesMoved = []; 

        DB::beginTransaction();

        try {
            $dataKegiatan = collect($validasi)->except('dokumentasi', 'pegawai_nips')->toArray();
            
            if ($user->hasRole(['operator_satker', 'operator_p2m'])) {
                $dataKegiatan['satuan_kerja_id'] = $user->getSatkerId();
            }

            $kegiatan = P2mKie::create($dataKegiatan);

            // Pivot Pegawai
            $listPegawai = Pegawai::whereIn('nip', $validasi['pegawai_nips'])->get();
            $attachData = [];
            foreach ($listPegawai as $pgw) {
                $attachData[$pgw->nip] = ['saved_satuan_kerja_id' => $pgw->satuan_kerja_id];
            }
            $kegiatan->pegawai()->attach($attachData);

            // Proses File
            if ($request->filled('dokumentasi')) {
                $this->processFiles($request->input('dokumentasi'), $kegiatan, $filesMoved);
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($filesMoved as $path) {
                 if (Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
            }
            return back()->with('error', 'store')->with('message', 'Gagal: ' . $e->getMessage())->withInput();
        }

        return redirect()->route('p2m.kie.index')->with('success', 'store')->with('message', 'Berhasil menambahkan data kegiatan');
    }

    public function edit($id): View 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kegiatan = P2mKie::with('pegawai')->findOrFail($id);

        if ($user->hasRole(['operator_satker', 'operator_p2m']) && $kegiatan->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403, 'Akses Ditolak');
        }

        if ($user->isAdmin()) {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
            $pegawais = Pegawai::orderBy('nama', 'asc')->get();
        } else {
            $satuanKerjas = []; 
            $satkerId = $user->getSatkerId();
            $pegawaiAktif = Pegawai::where('satuan_kerja_id', $satkerId)->get();
            $pegawaiExisting = $kegiatan->pegawai;
            $pegawais = $pegawaiAktif->merge($pegawaiExisting)->unique('nip')->sortBy('nama');
        }

        $selectedPegawaiNips = $kegiatan->pegawai->pluck('nip')->toArray();

        return view('p2m.kie.edit', compact('kegiatan', 'satuanKerjas', 'pegawais', 'selectedPegawaiNips'));
    }

    public function update(Request $request, $id) 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kegiatan = P2mKie::findOrFail($id);

        if ($user->hasRole(['operator_satker', 'operator_p2m']) && $kegiatan->satuan_kerja_id !== $user->getSatkerId()) abort(403);

        $rules = [
            'tempat_kegiatan'        => 'required',
            'anggaran_pelaksanaan' => 'required|in:DIPA,NON DIPA',
            'tanggal_pelaksanaan'    => 'required|date',
            'pegawai_nips'           => 'required|array',
            'pegawai_nips.*'         => 'exists:pegawai,nip',
            'delete_files'           => 'nullable|array',
            'dokumentasi'            => 'nullable|array',
        ];

        if ($user->isAdmin()) $rules['satuan_kerja_id'] = 'required';

        $validasi = $request->validate($rules);
        $newFilesMoved = [];
        $filesToDelete = [];

        DB::beginTransaction();

        try {
            $dataUpdate = collect($validasi)->except(['dokumentasi', 'pegawai_nips', 'delete_files'])->toArray();
            if ($user->hasRole(['operator_satker', 'operator_p2m'])) unset($dataUpdate['satuan_kerja_id']);

            $kegiatan->update($dataUpdate);

            // Sync Pegawai
            $oldPivotData = DB::table('pegawai_p2m_kie')->where('p2m_kie_id', $id)->get()->keyBy('pegawai_nip');
            $masterPegawais = Pegawai::whereIn('nip', $validasi['pegawai_nips'])->get()->keyBy('nip');
            $syncData = [];

            foreach ($validasi['pegawai_nips'] as $nip) {
                $satkerToSave = (isset($oldPivotData[$nip]) && $oldPivotData[$nip]->saved_satuan_kerja_id) 
                    ? $oldPivotData[$nip]->saved_satuan_kerja_id 
                    : ($masterPegawais[$nip]->satuan_kerja_id ?? null);
                
                $syncData[$nip] = ['saved_satuan_kerja_id' => $satkerToSave];
            }
            $kegiatan->pegawai()->sync($syncData);

            // Hapus File Lama
            if ($request->has('delete_files')) {
                $filesToRemove = DokumentasiKegiatan::whereIn('id', $request->delete_files)->get();
                foreach ($filesToRemove as $file) {
                    $filesToDelete[] = $file->path_file;
                    $file->delete();
                }
            }

            // Upload File Baru
            if ($request->filled('dokumentasi')) {
                $this->processFiles($request->input('dokumentasi'), $kegiatan, $newFilesMoved);
            }

            DB::commit();

            foreach ($filesToDelete as $path) {
                if (Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
            }

            return redirect()->route('p2m.kie.index')->with('success', 'update')->with('message', 'Data berhasil diperbarui');

        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($newFilesMoved as $path) {
                if (Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
            }
            return back()->with('error', 'update')->with('message', 'Gagal: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id) 
    {
        $kegiatan = P2mKie::findOrFail($id);
        $filesToDelete = [];
        foreach ($kegiatan->dokumentasi()->cursor() as $doc) {
            $filesToDelete[] = $doc->path_file;
        }

        DB::beginTransaction();
        try {
            $kegiatan->delete(); 
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'destroy')->with('message', 'Gagal menghapus data');
        }

        foreach ($filesToDelete as $path) {
            if (Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
        }

        return redirect()->back()->with('success', 'destroy')->with('message', 'Data berhasil dihapus.');
    }

    public function export(Request $request) 
    {
        return redirect()->back()->with('error', 'Fitur export belum tersedia.');
    }

    private function processFiles($tempFolders, $kegiatan, &$movedFilesLog) {
        foreach ($tempFolders as $folder) {
            $tempFile = TemporaryFile::where('folder', $folder)->first();
            if ($tempFile) {
                $sourcePath = 'public/tmp/' . $folder . '/' . $tempFile->filename;
                $mimeType = Storage::mimeType($sourcePath);
                $size = Storage::size($sourcePath);
                $ext = pathinfo($tempFile->filename, PATHINFO_EXTENSION);
                $nameOnly = pathinfo($tempFile->filename, PATHINFO_FILENAME);
                $cleanFileName = time() . '_' . uniqid() . '_' . Str::slug($nameOnly) . '.' . $ext;
                $destinationPath = 'dokumentasi/' . date('Y') . '/' . $cleanFileName;

                if (Storage::exists($sourcePath)) {
                    Storage::disk('public')->put($destinationPath, Storage::readStream($sourcePath));
                    $movedFilesLog[] = $destinationPath;
                    $kegiatan->dokumentasi()->create([
                        'nama_file_asli' => $tempFile->filename,
                        'path_file' => $destinationPath,
                        'tipe_file' => $mimeType,
                        'ukuran_file' => $size,
                    ]);
                    Storage::deleteDirectory('public/tmp/' . $folder);
                    $tempFile->delete();
                }
            }
        }
    }
}