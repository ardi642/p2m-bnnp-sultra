<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\P2mSafariReligi; // Model Baru
use App\Models\SatuanKerja;
use App\Models\Pegawai;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Exports\SafariReligiExport; // Export Class Baru
use App\Helpers\SearchHelper;
use App\Models\DokumentasiKegiatan;
use App\Models\TemporaryFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class SafariReligiController extends Controller
{
    // 1. BUILD QUERY
    private function getFilteredQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $activeYears = $request->filled('tahun') ? $request->tahun : [date('Y')];
        
        $query = P2mSafariReligi::with('pegawai.satuanKerja', 'satuanKerja');

        // Filter Satker (Admin)
        if ($user->hasRole('admin')) {
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('satuan_kerja_id', $request->satuan_kerja_id);
            }
        } else {
            $query->where('satuan_kerja_id', $user->getSatkerId());
        }

        // Filter Waktu
        if ($request->filled('bulan')) {
            $query->where(function($q) use ($request) {
                foreach ($request->bulan as $b) {
                    $q->orWhereMonth('tanggal_pelaksanaan', $b);
                }
            });
        }
        $query->where(function($q) use ($activeYears) {
            foreach ($activeYears as $y) {
                $q->orWhereYear('tanggal_pelaksanaan', $y);
            }
        });

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
                  ->orWhere('anggaran_pelaksanaan', 'LIKE', "%{$search}%")
                  ->orWhere('jumlah_masyarakat', 'LIKE', "%{$search}%")
                  ->orWhereHas('satuanKerja', function($subQ) use ($search) {
                      $subQ->where('satuan_kerja', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('pegawai', function($subQ) use ($search) {
                      $subQ->where('nama', 'LIKE', "%{$search}%");
                  });
                
                // Search Date format Indonesia
                $q->orWhereRaw("LOWER(DATE_FORMAT(tanggal_pelaksanaan, '%W, %d %M %Y')) LIKE ?", ["%{$searchDate}%"]);
                $q->orWhereRaw("LOWER(DATE_FORMAT(created_at, '%d %b %Y %H:%i')) LIKE ?", ["%{$searchDate}%"]);
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowSort = ['anggaran_pelaksanaan', 'tanggal_pelaksanaan', 'tempat_kegiatan', 'jumlah_masyarakat', 'created_at', 'satuan_kerja'];

        if (in_array($sortBy, $allowSort)) {
            if ($sortBy === 'satuan_kerja') {
                $query->join('satuan_kerja', 'p2m_safari_religi.satuan_kerja_id', '=', 'satuan_kerja.id')
                        ->orderBy('satuan_kerja.satuan_kerja', $sortOrder)
                        ->select('p2m_safari_religi.*');
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }
        } else {
            $query->latest(); // Default Created At Desc
        }

        return $query;
    }

    public function index(Request $request): View {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Data Master Setup
        if ($user->hasRole('admin')) {
            $pegawais = Pegawai::orderBy('nama', 'asc')->get(['nip', 'nama']);
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        } else {
            $satkerId = $user->getSatkerId();
            $pegawais = Pegawai::where('satuan_kerja_id', $satkerId)->orderBy('nama', 'asc')->get(['nip', 'nama']);
            $satuanKerjas = [];
        }

        // Dropdown Tahun
        $yearQuery = P2mSafariReligi::selectRaw('YEAR(tanggal_pelaksanaan) as year');
        if ($user->hasRole(['operator_satker', 'operator_p2m'])) {
            $yearQuery->where('satuan_kerja_id', $user->getSatkerId());
        }
        $years = $yearQuery->distinct()->orderBy('year', 'desc')->pluck('year');
        $currentYear = (int) date('Y');
        // Cek apakah tahun sekarang sudah ada di koleksi
        if (!$years->contains($currentYear)) {
            // Tambahkan dan urutkan ulang hanya jika perlu
            $years->push($currentYear)->sortDesc()->values();
        }

        // Main Query
        $query = $this->getFilteredQuery($request);

        $statsQuery = clone $query;
        $totalKegiatan = $statsQuery->count();
        $totalPeserta = $statsQuery->sum('jumlah_masyarakat');

        $query->with('dokumentasi');

        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : 10;
        $safariReligis = $query->paginate($perPage)->withQueryString();

        $satkerLookup = SatuanKerja::pluck('satuan_kerja', 'id')->toArray();
                        
        return view('p2m.safari-religi.index', compact('safariReligis', 'satuanKerjas', 'years', 'pegawais', 'user', 'satkerLookup',
                    'totalKegiatan', 'totalPeserta'));
    }

    public function export(Request $request) 
    {
        $query = $this->getFilteredQuery($request);
        return Excel::download(new SafariReligiExport($query), 'Laporan_P2M_Safari_Religi.xlsx');
    }

    public function create(): View {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->isAdmin()) {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
            $pegawais = Pegawai::with('satuanKerja')->orderBy('nama', 'asc')->get();
        } else {
            $satuanKerjas = [];
            $pegawais = Pegawai::with('satuanKerja')
                ->where('satuan_kerja_id', $user->getSatkerId())
                ->orderBy('nama', 'asc')->get();
        }

        return view('p2m.safari-religi.create', compact('satuanKerjas', 'pegawais'));
    }

    public function store(Request $request) {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $rules = [
            'tanggal_pelaksanaan' => 'required|date',
            'anggaran_pelaksanaan' => 'required|in:DIPA,NON DIPA',
            'tempat_kegiatan'     => 'required',
            'jumlah_masyarakat'   => 'required|numeric', // Ganti jumlah_peserta jadi jumlah_masyarakat
            'pegawai_nips'        => 'required|array',
            'pegawai_nips.*'      => 'exists:pegawai,nip',
            'dokumentasi'         => 'nullable|array',
            'dokumentasi.*'       => 'required',
        ];

        if ($user->isAdmin()) {
            $rules['satuan_kerja_id'] = 'required';
        }

        $validasi = $request->validate($rules);
        $filesMoved = [];

        DB::beginTransaction();

        try {
            $dataKegiatan = collect($validasi)->except('dokumentasi', 'pegawai_nips')->toArray();
            $pegawaiNips  = $validasi['pegawai_nips'];

            if ($user->hasRole(['operator_satker', 'operator_p2m'])) {
                $dataKegiatan['satuan_kerja_id'] = $user->getSatkerId();
            }

            // CREATE MAIN DATA
            $kegiatan = P2mSafariReligi::create($dataKegiatan);

            // ATTACH PEGAWAI (History Satker)
            $listPegawai = Pegawai::whereIn('nip', $pegawaiNips)->get();
            $attachData = [];
            foreach ($listPegawai as $pgw) {
                $attachData[$pgw->nip] = ['saved_satuan_kerja_id' => $pgw->satuan_kerja_id];
            }
            $kegiatan->pegawai()->attach($pegawaiNips); // Pivot default Laravel akan ambil extra attributes dr attachData?
            // Revisi Pivot Logic manual agar aman:
            $kegiatan->pegawai()->sync($attachData);

            // FILE UPLOAD LOGIC (Sama Persis)
            if ($request->filled('dokumentasi')) {
                foreach ($request->input('dokumentasi') as $folder) {
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
                            $filesMoved[] = $destinationPath;

                            $kegiatan->dokumentasi()->create([
                                'nama_file_asli' => $tempFile->filename,
                                'path_file'      => $destinationPath,    
                                'tipe_file'      => $mimeType,           
                                'ukuran_file'    => $size,               
                            ]);

                            Storage::deleteDirectory('public/tmp/' . $folder);
                            $tempFile->delete();
                        }
                    }
                }
            }

            DB::commit(); 

        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($filesMoved as $path) {
                if (Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
            }
            return back()->with('error', 'store')->with('message', 'Gagal menyimpan data: ' . $e->getMessage())->withInput();
        }

        return redirect()->route('p2m.safari-religi.index')
            ->with('success', 'store')
            ->with('message', 'Berhasil menambahkan data kegiatan');
    }

    public function edit($id): View 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kegiatan = P2mSafariReligi::with('pegawai')->findOrFail($id);

        if ($user->hasRole(['operator_satker', 'operator_p2m']) && $kegiatan->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403, 'Anda tidak berhak mengubah data Satuan Kerja lain.');
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

        return view('p2m.safari-religi.edit', compact('kegiatan', 'satuanKerjas', 'pegawais', 'selectedPegawaiNips'));
    }

    public function update(Request $request, $id) 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kegiatan = P2mSafariReligi::findOrFail($id);

        if ($user->hasRole(['operator_satker', 'operator_p2m']) && $kegiatan->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403);
        }

        $rules = [
            'tanggal_pelaksanaan' => 'required|date',
            'anggaran_pelaksanaan' => 'required|in:DIPA,NON DIPA',
            'tempat_kegiatan'     => 'required',
            'jumlah_masyarakat'   => 'required|numeric',
            'pegawai_nips'        => 'required|array',
            'pegawai_nips.*'      => 'exists:pegawai,nip',
            'delete_files'        => 'nullable|array', 
            'delete_files.*'      => 'exists:dokumentasi_kegiatan,id',
            'dokumentasi'         => 'nullable|array',
        ];

        if ($user->isAdmin()) {
            $rules['satuan_kerja_id'] = 'required';
        }

        $validasi = $request->validate($rules);
        $newFilesMoved = []; 
        $filesToDelete = []; 

        DB::beginTransaction();

        try {
            $pegawaiNips = $validasi['pegawai_nips'];
            $dataUpdate = collect($validasi)->except(['dokumentasi', 'pegawai_nips', 'delete_files'])->toArray();

            if ($user->hasRole(['operator_satker', 'operator_p2m'])) {
                unset($dataUpdate['satuan_kerja_id']); 
            }

            $kegiatan->update($dataUpdate);

            // SYNC PEGAWAI WITH HISTORY PRESERVATION
            $oldPivotData = DB::table('pegawai_p2m_safari_religi')
                                ->where('p2m_safari_religi_id', $id)->get()->keyBy('pegawai_nip');
            $masterPegawais = Pegawai::whereIn('nip', $pegawaiNips)->get()->keyBy('nip');
            $syncData = [];

            foreach ($pegawaiNips as $nip) {
                if (isset($oldPivotData[$nip]) && $oldPivotData[$nip]->saved_satuan_kerja_id) {
                    $satkerToSave = $oldPivotData[$nip]->saved_satuan_kerja_id; 
                } else {
                    $satkerToSave = $masterPegawais[$nip]->satuan_kerja_id ?? null;
                }
                $syncData[$nip] = ['saved_satuan_kerja_id' => $satkerToSave];
            }
            $kegiatan->pegawai()->sync($syncData);

            // DELETE OLD FILES
            if ($request->has('delete_files')) {
                $filesToRemove = DokumentasiKegiatan::whereIn('id', $request->delete_files)->get();
                foreach ($filesToRemove as $file) {
                    $filesToDelete[] = $file->path_file; 
                    $file->delete();
                }
            }

            // UPLOAD NEW FILES
            if ($request->filled('dokumentasi')) {
                foreach ($request->input('dokumentasi') as $folder) {
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
                            $newFilesMoved[] = $destinationPath;

                            $kegiatan->dokumentasi()->create([
                                'nama_file_asli' => $tempFile->filename,
                                'path_file'      => $destinationPath, 
                                'tipe_file'      => $mimeType,
                                'ukuran_file'    => $size,
                            ]);

                            Storage::deleteDirectory('public/tmp/' . $folder);
                            $tempFile->delete();
                        }
                    }
                }
            }

            DB::commit();

            // CLEANUP PHYSICAL FILES
            foreach ($filesToDelete as $path) {
                if (Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
            }

            return redirect()->route('p2m.safari-religi.index')
                ->with('success', 'update')
                ->with('message', 'Data berhasil diperbarui');

        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($newFilesMoved as $path) {
                if (Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
            }
            return back()->with('error', 'update')->with('message', 'Gagal memperbarui data: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id) 
    {
        $kegiatan = P2mSafariReligi::findOrFail($id);
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
            return back()->with('error', 'destroy')->with('message', 'Gagal menghapus data: ' . $e->getMessage());
        }

        foreach ($filesToDelete as $path) {
            try {
                if (Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
            } catch (\Exception $e) {}
        }

        return redirect()->back()->with('success', 'destroy')->with('message', 'Data dan file berhasil dihapus.');
    }
}