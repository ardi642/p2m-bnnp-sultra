<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\P2mElektronik;
use App\Models\SatuanKerja;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Exports\ElektronikExport;
use App\Helpers\SearchHelper;
use App\Models\Dokumen;
use App\Models\DokumentasiKegiatan;
use App\Models\TemporaryFile;
use App\Services\DokumenService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class ElektronikController extends Controller
{
    private function getFilteredQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $activeYears = $request->filled('tahun') ? $request->tahun : [date('Y')];
        
        // Tidak perlu with('pegawai')
        $query = P2mElektronik::with('satuanKerja');

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
                    ->orWhereHas('satuanKerja', function($subQ) use ($search) { $subQ->where('satuan_kerja', 'LIKE', "%{$search}%"); });
                $q->orWhereRaw("LOWER(DATE_FORMAT(tanggal_pelaksanaan, '%W, %d %M %Y')) LIKE ?", ["%{$searchDate}%"]);
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowSort = ['nama_media', 'jenis_media', 'tanggal_pelaksanaan', 'durasi_pelaksanaan', 'created_at', 'satuan_kerja', 'anggaran_pelaksanaan'];

        if (in_array($sortBy, $allowSort)) {
            if ($sortBy === 'satuan_kerja') {
                $query->join('satuan_kerja', 'p2m_elektronik.satuan_kerja_id', '=', 'satuan_kerja.id')
                        ->orderBy('satuan_kerja.satuan_kerja', $sortOrder)->select('p2m_elektronik.*');
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

        // Hanya Satker yang dibutuhkan untuk filter, Pegawai TIDAK PERLU
        if ($user->hasRole('admin')) {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        } else {
            $satuanKerjas = [];
        }

        $yearQuery = P2mElektronik::selectRaw('YEAR(tanggal_pelaksanaan) as year');
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

        $query = $this->getFilteredQuery($request);
        
        $statsQuery = clone $query;
        $totalKegiatan = $statsQuery->count();

        $query->with('dokumen');

        $perPage = $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100])) { $perPage = 10; }
        
        $datas = $query->paginate($perPage)->withQueryString(); 
        
        return view('p2m.elektronik.index', compact('datas', 'satuanKerjas', 'years', 'user', 'totalKegiatan'));
    }

    public function export(Request $request) 
    {
        $query = $this->getFilteredQuery($request);
        return Excel::download(new ElektronikExport($query), 'Laporan_P2M_Media_Elektronik.xlsx');
    }

    public function create(): View {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->isAdmin()) {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        } else {
            $satuanKerjas = [];
        }
        return view('p2m.elektronik.create', compact('satuanKerjas'));
    }

    public function store(Request $request, DokumenService $dokumenService) {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $rules = [
            'anggaran_pelaksanaan' => 'required',
            'jenis_media'          => 'required',
            'nama_media'           => 'required',
            'tanggal_pelaksanaan'  => 'required|date',
            'durasi_pelaksanaan'   => 'required|numeric|min:1',
            'dokumentasi'          => 'nullable|array', 
            'lampiran'             => 'nullable|array',
            'dokumentasi_links'    => 'nullable|array',
            'dokumentasi_links.*.nama' => 'required_with:dokumentasi_links.*.url|nullable|string|max:255',
            'dokumentasi_links.*.url'  => 'required_with:dokumentasi_links.*.nama|nullable|url',
            'lampiran_links'       => 'nullable|array',
            'lampiran_links.*.nama' => 'required_with:lampiran_links.*.url|nullable|string|max:255',
            'lampiran_links.*.url'  => 'required_with:lampiran_links.*.nama|nullable|url',
        ];

        if ($user->isAdmin()) { $rules['satuan_kerja_id'] = 'required'; }

        $validasi = $request->validate($rules);
        $uploadedPaths = [];

        DB::beginTransaction();
        try {
            $dataInsert = collect($validasi)->except(['dokumentasi', 'lampiran', 'dokumentasi_links', 'lampiran_links'])->toArray();
            if ($user->hasRole(['operator_satker', 'operator_p2m'])) { $dataInsert['satuan_kerja_id'] = $user->getSatkerId(); }

            // Simpan Data Utama (Tanpa Attach Pegawai)
            $kegiatan = P2mElektronik::create($dataInsert);

            if ($request->filled('dokumentasi')) {
                $dokumenService->moveToPermanent($request->input('dokumentasi'), $kegiatan, 'dokumentasi', $uploadedPaths);
            }
            if ($request->filled('lampiran')) {
                $dokumenService->moveToPermanent($request->input('lampiran'), $kegiatan, 'lampiran', $uploadedPaths);
            }
            if ($request->filled('dokumentasi_links')) {
                $dokumenService->saveLinks($request->input('dokumentasi_links'), $kegiatan, 'dokumentasi');
            }
            if ($request->filled('lampiran_links')) {
                $dokumenService->saveLinks($request->input('lampiran_links'), $kegiatan, 'lampiran');
            }

            DB::commit();
            return redirect()->route('p2m.elektronik.index')->with('success', 'store')->with('message', 'Berhasil menambahkan data');
        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($uploadedPaths as $path) {
                Storage::disk(config('filesystems.default'))->delete($path);
            }
            Log::error('Gagal simpan: ' . $e->getMessage());
            abort(500, 'Server Error.');
        }
    }

    public function edit($id): View {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kegiatan = P2mElektronik::findOrFail($id);

        if ($user->hasRole(['operator_satker', 'operator_p2m']) && $kegiatan->satuan_kerja_id !== $user->getSatkerId()) { abort(403); }

        if ($user->isAdmin()) {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        } else {
            $satuanKerjas = [];
        }

        return view('p2m.elektronik.edit', compact('kegiatan', 'satuanKerjas'));
    }

    public function update(Request $request, DokumenService $dokumenService, $id) {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kegiatan = P2mElektronik::findOrFail($id);

        if ($user->hasRole(['operator_satker', 'operator_p2m']) && $kegiatan->satuan_kerja_id !== $user->getSatkerId()) { abort(403); }

        $rules = [
            'anggaran_pelaksanaan' => 'required',
            'jenis_media'          => 'required',
            'nama_media'           => 'required',
            'tanggal_pelaksanaan'  => 'required|date',
            'durasi_pelaksanaan'   => 'required|numeric|min:1',
            
            // Validasi File & Link
            'delete_files'         => 'nullable|array', 
            'dokumentasi'          => 'nullable|array',
            'lampiran'             => 'nullable|array',
            
            'dokumentasi_links'        => 'nullable|array',
            'dokumentasi_links.*.nama' => 'required_with:dokumentasi_links.*.url|nullable|string|max:255',
            'dokumentasi_links.*.url'  => 'required_with:dokumentasi_links.*.nama|nullable|url',

            'lampiran_links'        => 'nullable|array',
            'lampiran_links.*.nama' => 'required_with:lampiran_links.*.url|nullable|string|max:255',
            'lampiran_links.*.url'  => 'required_with:lampiran_links.*.nama|nullable|url',
        ];
        if ($user->isAdmin()) { $rules['satuan_kerja_id'] = 'required'; }

        $validasi = $request->validate($rules);
        $newFilesMoved = [];
        $filesToDelete = [];
        
        DB::beginTransaction();
        try {
            $dataUpdate = collect($validasi)->except(['dokumentasi', 'lampiran', 'delete_files', 'dokumentasi_links', 'lampiran_links'])->toArray();
            if ($user->hasRole(['operator_satker', 'operator_p2m'])) { unset($dataUpdate['satuan_kerja_id']); }
            
            $kegiatan->update($dataUpdate);

            // Hapus Dokumen Lama (File atau Link)
            if ($request->has('delete_files')) {
                $filesToRemove = Dokumen::whereIn('id', $request->delete_files)->get();
                foreach ($filesToRemove as $file) {
                    if (!$file->is_link) $filesToDelete[] = $file->path_file; // Hanya hapus fisik jika bukan link
                    $file->delete();
                }
            }
            
            // Upload File Baru
            if ($request->filled('dokumentasi')) {
                $dokumenService->moveToPermanent($request->input('dokumentasi'), $kegiatan, 'dokumentasi', $newFilesMoved);
            }
            if ($request->filled('lampiran')) {
                $dokumenService->moveToPermanent($request->input('lampiran'), $kegiatan, 'lampiran', $newFilesMoved);
            }

            // Simpan Link Baru
            if ($request->filled('dokumentasi_links')) {
                $dokumenService->saveLinks($request->input('dokumentasi_links'), $kegiatan, 'dokumentasi');
            }
            if ($request->filled('lampiran_links')) {
                $dokumenService->saveLinks($request->input('lampiran_links'), $kegiatan, 'lampiran');
            }

            DB::commit();
            foreach ($filesToDelete as $path) {
                if (Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
            }
            
            return redirect()->route('p2m.elektronik.index')
                ->with('success', 'update')
                ->with('message', 'Data berhasil diperbarui');

        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($newFilesMoved as $path) {
                if (Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
            }
            Log::error('Update error: ' . $e->getMessage());
            abort(500, 'Server Error.');
        }
    }

    // --- DESTROY ---
    public function destroy($id) 
    {
        $kegiatan = P2mElektronik::findOrFail($id);
        
        $filesToDelete = [];
        
        // Loop dokumen, tapi filter isinya
        foreach ($kegiatan->dokumen()->cursor() as $doc) {
            // Cek 1: Pastikan bukan Link (karena link tidak punya file fisik)
            // Cek 2: Pastikan path_file TIDAK NULL dan TIDAK KOSONG
            if (!$doc->is_link && !empty($doc->path_file)) {
                $filesToDelete[] = $doc->path_file;
            }
        }

        DB::beginTransaction();
        try {
            $kegiatan->delete(); 
            DB::commit(); 
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'destroy')->with('message', 'Gagal menghapus data: ' . $e->getMessage());
        }

        // Hapus file fisik
        foreach ($filesToDelete as $path) {
            // Double check: Pastikan $path adalah string (bukan null) sebelum akses Storage
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        return redirect()->back()->with('success', 'destroy')->with('message', 'Data dan file berhasil dihapus.');
    }
}