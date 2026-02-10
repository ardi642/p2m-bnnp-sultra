<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\P2mSosialisasi;
use App\Models\SatuanKerja;
use App\Models\Pegawai;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Exports\SosialisasiExport;
use App\Helpers\SearchHelper;
use App\Models\Dokumen;
use App\Models\TemporaryFile;
use App\Services\DokumenService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class SosialisasiController extends Controller
{
    // --- QUERY BUILDER (SAMA PERSIS) ---
    private function getFilteredQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $activeYears = $request->filled('tahun') ? $request->tahun : [date('Y')];
        
        $query = P2mSosialisasi::with('pegawai.satuanKerja', 'satuanKerja');

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

        // Filter Sasaran
        if ($request->filled('sasaran_kegiatan')) {
            $query->whereIn('sasaran_kegiatan', $request->sasaran_kegiatan);
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
                $q->where('nama_kegiatan', 'LIKE', "%{$search}%")
                    ->orWhere('tempat_kegiatan', 'LIKE', "%{$search}%")
                    ->orWhere('sasaran_kegiatan', 'LIKE', "%{$search}%")
                    ->orWhere('anggaran_pelaksanaan', 'LIKE', "%{$search}%")
                    ->orWhere('jumlah_peserta', 'LIKE', "%{$search}%")
                    ->orWhereHas('satuanKerja', function($subQ) use ($search) {
                        $subQ->where('satuan_kerja', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('pegawai', function($subQ) use ($search) {
                        $subQ->where('nama', 'LIKE', "%{$search}%");
                    });

                // Search Date
                $q->orWhereRaw("LOWER(DATE_FORMAT(tanggal_pelaksanaan, '%W, %d %M %Y')) LIKE ?", ["%{$searchDate}%"]);
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $rawSortOrder = $request->input('sort_order', 'desc');
        $sortOrder = in_array(strtolower($rawSortOrder), ['asc', 'desc']) ? strtolower($rawSortOrder) : 'desc';

        $allowSort = ['anggaran_pelaksanaan', 'nama_kegiatan', 'sasaran_kegiatan', 'tanggal_pelaksanaan', 'tempat_kegiatan', 'jumlah_peserta', 'created_at', 'satuan_kerja'];

        if (in_array($sortBy, $allowSort)) {
            if ($sortBy === 'satuan_kerja') {
                $query->join('satuan_kerja', 'p2m_sosialisasi.satuan_kerja_id', '=', 'satuan_kerja.id')
                        ->orderBy('satuan_kerja.satuan_kerja', $sortOrder)
                        ->select('p2m_sosialisasi.*');
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

        $yearQuery = P2mSosialisasi::selectRaw('YEAR(tanggal_pelaksanaan) as year');

        if ($user->hasRole(['operator_satker', 'operator_p2m'])) {
            $yearQuery->where('satuan_kerja_id', $user->getSatkerId());
        }

        $years = $yearQuery->distinct()->orderBy('year', 'desc')->pluck('year');

        $query = $this->getFilteredQuery($request);

        $statsQuery = clone $query;
        $totalKegiatan = $statsQuery->count();
        $totalPeserta = $statsQuery->sum('jumlah_peserta');

        // PENTING: Eager load dokumentasi
        $query->with('dokumen');

        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : 10;
        $sosialisasis = $query->paginate($perPage)->withQueryString();

        $satkerLookup = SatuanKerja::pluck('satuan_kerja', 'id')->toArray();
                        
        return view('p2m.sosialisasi.index', compact('sosialisasis', 'satuanKerjas', 'years', 'pegawais', 'user', 'satkerLookup', 'totalKegiatan', 'totalPeserta'));
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

        return view('p2m.sosialisasi.create', compact('satuanKerjas', 'pegawais'));
    }

    // --- REFACTOR STORE MENJADI SAMA SEPERTI UPACARA ---
    public function store(Request $request, DokumenService $dokumenService) {
        
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $rules = [
            'anggaran_pelaksanaan' => 'required',
            'nama_kegiatan'        => 'required',
            'sasaran_kegiatan'     => 'required',
            'tanggal_pelaksanaan'  => 'required|date',
            'tempat_kegiatan'      => 'required',
            'jumlah_peserta'       => 'required|numeric',
            'pegawai_nips'         => 'required|array',
            'pegawai_nips.*'       => 'exists:pegawai,nip',
            'dokumentasi'          => 'nullable|array', 
            'dokumentasi.*'        => 'required',
        ];

        if ($user->isAdmin()) {
            $rules['satuan_kerja_id'] = 'required';
        }

        $validasi = $request->validate($rules);
        
        // Array pelacak file agar bisa dihapus jika transaksi gagal
        $uploadedPaths = []; 

        DB::beginTransaction(); 

        try {
            $dataKegiatan = collect($validasi)->except('dokumentasi', 'pegawai_nips')->toArray();

            if ($user->hasRole(['operator_satker', 'operator_p2m'])) {
                $dataKegiatan['satuan_kerja_id'] = $user->getSatkerId();
            }

            // 1. Simpan Kegiatan
            $kegiatan = P2mSosialisasi::create($dataKegiatan);

            // 2. Simpan Pegawai
            $listPegawai = Pegawai::whereIn('nip', $validasi['pegawai_nips'])->get();
            $attachData = [];
            foreach ($listPegawai as $pgw) {
                $attachData[$pgw->nip] = ['saved_satuan_kerja_id' => $pgw->satuan_kerja_id];
            }
            $kegiatan->pegawai()->attach($attachData);

            if ($request->filled('dokumentasi')) {
                // 2. Oper $uploadedPaths sebagai Reference
                // Tidak perlu: $paths = ...
                $dokumenService->moveToPermanent(
                    $request->input('dokumentasi'), 
                    $kegiatan, 
                    'dokumentasi',
                    $uploadedPaths // <--- Variable ini akan terisi otomatis di dalam service
                );
            }

            if ($request->filled('lampiran')) {
                // Oper variable YANG SAMA. Jadi isinya gabungan dokumentasi & lampiran.
                $dokumenService->moveToPermanent(
                    $request->input('lampiran'), 
                    $kegiatan, 
                    'lampiran',
                    $uploadedPaths // <--- Variable ini terus bertambah isinya
                );
            }

            DB::commit();
            return redirect()->route('p2m.sosialisasi.index')
                ->with('success', 'store')
                ->with('message', 'Berhasil menambahkan data.');

        } catch (\Exception $e) {
            DB::rollBack();
            // 3. ROLLBACK FILE FISIK
            // Jika gagal, hapus file fisik yang sudah terlanjur dipindah
            // Karena pakai Reference, $uploadedPaths berisi semua file yang 
            // SUKSES ter-upload sebelum error terjadi.
            dd($e);
            foreach ($uploadedPaths as $path) {
                Storage::disk(config('filesystems.default'))->delete($path);
            }
            Log::error('Gagal simpan data P2M Sosialisasi: ' . $e->getMessage());
            abort(500, 'Maaf, terjadi kesalahan pada server. Silakan hubungi admin.');
        }

    }

    public function edit($id): View 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kegiatan = P2mSosialisasi::with('pegawai')->findOrFail($id);

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

        return view('p2m.sosialisasi.edit', compact('kegiatan', 'satuanKerjas', 'pegawais', 'selectedPegawaiNips'));
    }

    public function update(Request $request, DokumenService $dokumenService, $id) 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kegiatan = P2mSosialisasi::findOrFail($id);

        if ($user->hasRole(['operator_satker', 'operator_p2m']) && $kegiatan->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403, 'Akses Ditolak');
        }

        $rules = [
            'anggaran_pelaksanaan' => 'required',
            'nama_kegiatan'        => 'required',
            'sasaran_kegiatan'     => 'required',
            'tanggal_pelaksanaan'  => 'required|date',
            'tempat_kegiatan'      => 'required',
            'jumlah_peserta'       => 'required|numeric',
            'pegawai_nips'         => 'required|array',
            'pegawai_nips.*'       => 'exists:pegawai,nip',
            
            // Validasi File
            'delete_files'         => 'nullable|array', 
            'dokumentasi'          => 'nullable|array',
            'lampiran'             => 'nullable|array', // Tambahan untuk lampiran
        ];

        if ($user->isAdmin()) {
            $rules['satuan_kerja_id'] = 'required';
        }

        $validasi = $request->validate($rules);
        
        // Array Tracking untuk Rollback/Cleanup
        $newFilesMoved = []; // File baru yang berhasil dipindah (hapus jika error)
        $filesToDelete = []; // File lama yang harus dihapus fisik (hapus jika sukses commit)

        DB::beginTransaction();

        try {
            // 1. Update Data Kegiatan
            $dataUpdate = collect($validasi)->except(['dokumentasi', 'lampiran', 'pegawai_nips', 'delete_files'])->toArray();
            if ($user->hasRole(['operator_satker', 'operator_p2m'])) {
                unset($dataUpdate['satuan_kerja_id']);
            }

            $kegiatan->update($dataUpdate);

            // 2. Sync Pegawai
            $oldPivotData = DB::table('pegawai_p2m_sosialisasi')->where('p2m_sosialisasi_id', $id)->get()->keyBy('pegawai_nip');
            $masterPegawais = Pegawai::whereIn('nip', $validasi['pegawai_nips'])->get()->keyBy('nip');
            $syncData = [];

            foreach ($validasi['pegawai_nips'] as $nip) {
                $satkerToSave = (isset($oldPivotData[$nip]) && $oldPivotData[$nip]->saved_satuan_kerja_id) 
                    ? $oldPivotData[$nip]->saved_satuan_kerja_id 
                    : ($masterPegawais[$nip]->satuan_kerja_id ?? null);
                
                $syncData[$nip] = ['saved_satuan_kerja_id' => $satkerToSave];
            }
            $kegiatan->pegawai()->sync($syncData);

            // 3. Hapus File Lama (Database Record)
            if ($request->has('delete_files')) {
                // PERBAIKAN: Menggunakan Model 'Dokumen'
                $filesToRemove = Dokumen::whereIn('id', $request->delete_files)->get();
                
                foreach ($filesToRemove as $file) {
                    // Simpan path untuk dihapus nanti setelah commit sukses
                    $filesToDelete[] = $file->path_file;
                    $file->delete();
                }
            }

            // 4. Upload File Baru: DOKUMENTASI
            if ($request->filled('dokumentasi')) {
                $dokumenService->moveToPermanent(
                    $request->input('dokumentasi'), 
                    $kegiatan, 
                    'dokumentasi', // Kategori
                    $newFilesMoved // Pass by reference untuk tracking
                );
            }

            // 5. Upload File Baru: LAMPIRAN
            if ($request->filled('lampiran')) {
                $dokumenService->moveToPermanent(
                    $request->input('lampiran'), 
                    $kegiatan, 
                    'lampiran', // Kategori
                    $newFilesMoved // Pass by reference untuk tracking
                );
            }

            DB::commit();

            // --- SUKSES: Hapus Fisik File Lama ---
            // Dilakukan setelah commit agar jika DB gagal, file fisik tidak hilang duluan
            foreach ($filesToDelete as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            return redirect()->route('p2m.sosialisasi.index')
                ->with('success', 'update')
                ->with('message', 'Data berhasil diperbarui');

        } catch (\Exception $e) {
            DB::rollBack();
            
            // --- GAGAL: Hapus File Baru yang terlanjur dipindah ---
            // Agar server tidak penuh sampah file jika transaksi gagal
            foreach ($newFilesMoved as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
            
            Log::error('Gagal update sosialisasi: ' . $e->getMessage());
            return back()->with('error', 'update')->with('message', 'Gagal: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id) 
    {
        $kegiatan = P2mSosialisasi::findOrFail($id);
        
        $filesToDelete = [];
        foreach ($kegiatan->dokumen()->cursor() as $doc) {
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
            if (Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
        }

        return redirect()->back()->with('success', 'destroy')->with('message', 'Data dan file berhasil dihapus.');
    }

    public function export(Request $request) 
    {
        $query = $this->getFilteredQuery($request);
        return Excel::download(new SosialisasiExport($query), 'Laporan_P2M_Sosialisasi.xlsx');
    }

}