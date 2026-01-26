<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\P2mOnline;
use App\Models\SatuanKerja;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Exports\OnlineExport;
use App\Helpers\SearchHelper;
use App\Models\DokumentasiKegiatan;
use App\Models\TemporaryFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class OnlineController extends Controller
{
    private function getFilteredQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $activeYears = $request->filled('tahun') ? $request->tahun : [date('Y')];
        
        $query = P2mOnline::with('satuanKerja');

        if ($user->hasRole('admin')) {
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('satuan_kerja_id', $request->satuan_kerja_id);
            }
        } else {
            $query->where('satuan_kerja_id', $user->getSatkerId());
        }

        if ($request->filled('bulan')) {
            $query->where(function($q) use ($request) {
                foreach ($request->bulan as $b) {
                    $q->orWhereMonth('tanggal_mulai_pelaksanaan', $b);
                }
            });
        }

        $query->where(function($q) use ($activeYears) {
            foreach ($activeYears as $y) {
                $q->orWhereYear('tanggal_mulai_pelaksanaan', $y);
            }
        });

        if ($request->filled('anggaran_pelaksanaan')) {
            $query->whereIn('anggaran_pelaksanaan', $request->anggaran_pelaksanaan);
        }

        if ($request->filled('jenis_media')) {
            $query->whereIn('jenis_media', $request->jenis_media);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $searchDate = SearchHelper::translateDateInput($search);
            $query->where(function($q) use ($search, $searchDate) {
                $q->where('nama_media', 'LIKE', "%{$search}%")
                    ->orWhere('jenis_media', 'LIKE', "%{$search}%")
                    ->orWhere('anggaran_pelaksanaan', 'LIKE', "%{$search}%")
                    ->orWhere('durasi_pelaksanaan', 'LIKE', "%{$search}%")
                    ->orWhereHas('satuanKerja', function($subQ) use ($search) { 
                        $subQ->where('satuan_kerja', 'LIKE', "%{$search}%"); 
                    });
                $q->orWhereRaw("LOWER(DATE_FORMAT(tanggal_mulai_pelaksanaan, '%W, %d %M %Y')) LIKE ?", ["%{$searchDate}%"]);
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowSort = ['jenis_media', 'nama_media', 'tanggal_mulai_pelaksanaan', 'durasi_pelaksanaan', 'created_at', 'satuan_kerja', 'anggaran_pelaksanaan'];

        if (in_array($sortBy, $allowSort)) {
            if ($sortBy === 'satuan_kerja') {
                $query->join('satuan_kerja', 'p2m_online.satuan_kerja_id', '=', 'satuan_kerja.id')
                        ->orderBy('satuan_kerja.satuan_kerja', $sortOrder)
                        ->select('p2m_online.*');
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }
        } else {
            $query->latest();
        }

        return $query;
    }

    public function index(Request $request): View {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        } else {
            $satuanKerjas = [];
        }

        $yearQuery = P2mOnline::selectRaw('YEAR(tanggal_mulai_pelaksanaan) as year');
        if ($user->hasRole(['operator_satker', 'operator_p2m'])) { $yearQuery->where('satuan_kerja_id', $user->getSatkerId()); }
        $years = $yearQuery->distinct()->orderBy('year', 'desc')->pluck('year');

        $query = $this->getFilteredQuery($request);

        $statsQuery = clone $query;
        $totalKegiatan = $statsQuery->count();
        $totalDurasi = $statsQuery->sum('durasi_pelaksanaan');

        $query->with('dokumentasi');

        $perPage = $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100])) { $perPage = 10; }
        
        $datas = $query->paginate($perPage)->withQueryString(); 
        $mediaOptions = P2mOnline::getJenisMediaOptions();

        return view('p2m.online.index', compact('datas', 'satuanKerjas', 'years', 'user', 'mediaOptions', 'totalKegiatan', 'totalDurasi'));
    }

    public function export(Request $request) 
    {
        $query = $this->getFilteredQuery($request);
        return Excel::download(new OnlineExport($query), 'Laporan_P2M_Media_Online.xlsx');
    }

    public function create(): View {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->isAdmin()) {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        } else {
            $satuanKerjas = [];
        }
        $mediaOptions = P2mOnline::getJenisMediaOptions();
        return view('p2m.online.create', compact('satuanKerjas', 'mediaOptions'));
    }

    public function store(Request $request) {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $rules = [
            'anggaran_pelaksanaan'      => 'required',
            'jenis_media'               => 'required',
            'nama_media'                => 'required',
            'tanggal_mulai_pelaksanaan' => 'required|date',
            'durasi_pelaksanaan'        => 'required|numeric|min:1',
            'dokumentasi'               => 'nullable|array',
        ];

        if ($user->isAdmin()) { $rules['satuan_kerja_id'] = 'required'; }

        $validasi = $request->validate($rules);
        $filesMoved = [];

        DB::beginTransaction();
        try {
            $dataInsert = collect($validasi)->except('dokumentasi')->toArray();
            if ($user->hasRole(['operator_satker', 'operator_p2m'])) { $dataInsert['satuan_kerja_id'] = $user->getSatkerId(); }

            $kegiatan = P2mOnline::create($dataInsert);

            if ($request->filled('dokumentasi')) {
                foreach ($request->input('dokumentasi') as $folder) {
                    $tempFile = TemporaryFile::where('folder', $folder)->first();
                    if ($tempFile) {
                        $sourcePath = 'public/tmp/' . $folder . '/' . $tempFile->filename;
                        if (Storage::exists($sourcePath)) {
                            $ext = pathinfo($tempFile->filename, PATHINFO_EXTENSION);
                            $cleanName = time() . '_' . uniqid() . '_' . Str::slug(pathinfo($tempFile->filename, PATHINFO_FILENAME)) . '.' . $ext;
                            $destPath = 'dokumentasi/' . date('Y') . '/' . $cleanName;
                            
                            Storage::disk('public')->put($destPath, Storage::readStream($sourcePath));
                            $filesMoved[] = $destPath;

                            $kegiatan->dokumentasi()->create([
                                'nama_file_asli' => $tempFile->filename,
                                'path_file' => $destPath,
                                'tipe_file' => Storage::mimeType($sourcePath),
                                'ukuran_file' => Storage::size($sourcePath),
                            ]);
                            Storage::deleteDirectory('public/tmp/' . $folder);
                            $tempFile->delete();
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('p2m.online.index')->with('success', 'store')->with('message', 'Berhasil menambahkan data');
        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($filesMoved as $path) { if (Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path); }
            return back()->with('error', 'store')->with('message', $e->getMessage())->withInput();
        }
    }

    public function edit($id): View {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kegiatan = P2mOnline::findOrFail($id);

        if ($user->hasRole(['operator_satker', 'operator_p2m']) && $kegiatan->satuan_kerja_id !== $user->getSatkerId()) { abort(403); }

        if ($user->isAdmin()) {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        } else {
            $satuanKerjas = [];
        }
        $mediaOptions = P2mOnline::getJenisMediaOptions();

        return view('p2m.online.edit', compact('kegiatan', 'satuanKerjas', 'mediaOptions'));
    }

    public function update(Request $request, $id) {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kegiatan = P2mOnline::findOrFail($id);

        if ($user->hasRole(['operator_satker', 'operator_p2m']) && $kegiatan->satuan_kerja_id !== $user->getSatkerId()) { abort(403); }

        $rules = [
            'anggaran_pelaksanaan'      => 'required',
            'jenis_media'               => 'required',
            'nama_media'                => 'required',
            'tanggal_mulai_pelaksanaan' => 'required|date',
            'durasi_pelaksanaan'        => 'required|numeric|min:1',
            'delete_files'              => 'nullable|array',
            'dokumentasi'               => 'nullable|array',
        ];
        if ($user->isAdmin()) { $rules['satuan_kerja_id'] = 'required'; }

        $validasi = $request->validate($rules);
        
        DB::beginTransaction();
        try {
            $dataUpdate = collect($validasi)->except(['dokumentasi', 'delete_files'])->toArray();
            if ($user->hasRole(['operator_satker', 'operator_p2m'])) { unset($dataUpdate['satuan_kerja_id']); }
            
            $kegiatan->update($dataUpdate);

            if ($request->has('delete_files')) {
                $files = DokumentasiKegiatan::whereIn('id', $request->delete_files)->get();
                foreach($files as $f) { $f->delete(); if(Storage::disk('public')->exists($f->path_file)) Storage::disk('public')->delete($f->path_file); }
            }

            if ($request->filled('dokumentasi')) {
                foreach ($request->input('dokumentasi') as $folder) {
                    $tempFile = TemporaryFile::where('folder', $folder)->first();
                    if ($tempFile) {
                        $sourcePath = 'public/tmp/' . $folder . '/' . $tempFile->filename;
                        if (Storage::exists($sourcePath)) {
                            $ext = pathinfo($tempFile->filename, PATHINFO_EXTENSION);
                            $cleanName = time() . '_' . uniqid() . '_' . Str::slug(pathinfo($tempFile->filename, PATHINFO_FILENAME)) . '.' . $ext;
                            $destPath = 'dokumentasi/' . date('Y') . '/' . $cleanName;
                            Storage::disk('public')->put($destPath, Storage::readStream($sourcePath));
                            $kegiatan->dokumentasi()->create([
                                'nama_file_asli' => $tempFile->filename, 'path_file' => $destPath,
                                'tipe_file' => Storage::mimeType($sourcePath), 'ukuran_file' => Storage::size($sourcePath),
                            ]);
                            Storage::deleteDirectory('public/tmp/' . $folder);
                            $tempFile->delete();
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('p2m.online.index')->with('success', 'update')->with('message', 'Data berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'update')->with('message', $e->getMessage());
        }
    }

    public function destroy($id) {
        $kegiatan = P2mOnline::findOrFail($id);
        $filesToDelete = [];
        foreach ($kegiatan->dokumentasi()->cursor() as $doc) { $filesToDelete[] = $doc->path_file; }
        
        DB::beginTransaction();
        try {
            $kegiatan->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'destroy')->with('message', $e->getMessage());
        }

        foreach ($filesToDelete as $path) { if (Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path); }
        return back()->with('success', 'destroy')->with('message', 'Data dihapus');
    }
}