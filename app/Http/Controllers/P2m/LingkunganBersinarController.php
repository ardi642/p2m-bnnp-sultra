<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\P2mLingkunganBersinar;
use App\Models\SatuanKerja;
use App\Models\Pegawai;
use App\Models\DokumentasiKegiatan;
use App\Models\TemporaryFile;
use App\Exports\LingkunganBersinarExport;
use App\Helpers\SearchHelper;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class LingkunganBersinarController extends Controller
{
    // 1. FUNGSI KHUSUS UNTUK BUILD QUERY (Re-usable)
    private function getFilteredQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $activeYears = $request->filled('tahun') ? $request->tahun : [date('Y')];
        
        // Eager Load Relasi agar query ringan
        $query = P2mLingkunganBersinar::with('pegawai.satuanKerja', 'satuanKerja');

        // --- FILTER LOGIC ---

        // Filter Satuan Kerja (Role Based)
        if ($user->hasRole('admin')) {
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('satuan_kerja_id', $request->satuan_kerja_id);
            }
        }
        else {
            // Jika operator, kunci ke satker dia sendiri
            $satkerId = $user->getSatkerId();
            $query->where('satuan_kerja_id', $satkerId);
        }

        // Filter Bulan Pelaksanaan
        if ($request->filled('bulan')) {
            $query->where(function($q) use ($request) {
                foreach ($request->bulan as $b) {
                    $q->orWhereMonth('tanggal_pencanangan', $b);
                }
            });
        }

        // Filter Tahun Pelaksanaan
        $query->where(function($q) use ($activeYears) {
            foreach ($activeYears as $y) {
                $q->orWhereYear('tanggal_pencanangan', $y);
            }
        });

        // Filter Anggaran
        if ($request->filled('anggaran_pelaksanaan')) {
            $query->whereIn('anggaran_pelaksanaan', $request->anggaran_pelaksanaan);
        }

        // Filter Sasaran Kegiatan
        if ($request->filled('sasaran_kegiatan')) {
            $query->whereIn('sasaran_kegiatan', $request->sasaran_kegiatan);
        }
        
        // Filter Pegawai / Penanggung Jawab
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

        // Search Global
        if ($request->filled('search')) {
            $search = $request->search;
            $searchDate = SearchHelper::translateDateInput($search);

            $query->where(function($q) use ($search, $searchDate) {
                // Pencarian Kolom Teks Utama
                $q->where('nama_tempat_wilayah', 'LIKE', "%{$search}%")
                  ->orWhere('anggaran_pelaksanaan', 'LIKE', "%{$search}%")
                  ->orWhere('sasaran_kegiatan', 'LIKE', "%{$search}%")
                  ->orWhere('no_hp_penanggung_jawab', 'LIKE', "%{$search}%") // Cari No HP
                  
                  // Pencarian Angka
                  ->orWhere('jumlah_penggiat_p4gn', 'LIKE', "%{$search}%")

                  // Pencarian Relasi Satker
                  ->orWhereHas('satuanKerja', function($subQ) use ($search) {
                        $subQ->where('satuan_kerja', 'LIKE', "%{$search}%");
                  })

                  // Pencarian Relasi Pegawai (PJ)
                  ->orWhereHas('pegawai', function($subQ) use ($search) {
                        $subQ->where('nama', 'LIKE', "%{$search}%");
                  });

                  // Pencarian Tanggal (Format: Kamis, 04 September 2025)
                  $q->orWhereRaw("LOWER(DATE_FORMAT(tanggal_pencanangan, '%W, %d %M %Y')) LIKE ?", ["%{$searchDate}%"]);
                  
                  // Pencarian Tanggal Dibuat (Created At)
                  $q->orWhereRaw("LOWER(DATE_FORMAT(created_at, '%d %b %Y')) LIKE ?", ["%{$searchDate}%"]);
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        $allowSort = [
            'anggaran_pelaksanaan',
            'nama_tempat_wilayah', 
            'sasaran_kegiatan', 
            'tanggal_pencanangan', 
            'jumlah_penggiat_p4gn', 
            'created_at', 
            'satuan_kerja'
        ];

        if (in_array($sortBy, $allowSort)) {
            if ($sortBy === 'satuan_kerja') {
                $query->join('satuan_kerja', 'p2m_lingkungan_bersinar.satuan_kerja_id', '=', 'satuan_kerja.id')
                        ->orderBy('satuan_kerja.satuan_kerja', $sortOrder)
                        ->select('p2m_lingkungan_bersinar.*');
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
        // Data Master
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            $pegawais = Pegawai::orderBy('nama', 'asc')->get(['nip', 'nama']);
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        } 
        else {
            $satkerId = $user->getSatkerId();
            $pegawais = Pegawai::where('satuan_kerja_id', $satkerId)
                                ->orderBy('nama', 'asc')
                                ->get(['nip', 'nama']);
            $satuanKerjas = [];
        }

        // Logic Filter Tahun di Dropdown
        $yearQuery = P2mLingkunganBersinar::selectRaw('YEAR(tanggal_pencanangan) as year');

        if ($user->hasRole(['operator_satker', 'operator_p2m'])) {
            $yearQuery->where('satuan_kerja_id', $user->getSatkerId());
        }

        $years = $yearQuery->distinct()->orderBy('year', 'desc')->pluck('year');

        // Panggil Query Utama
        $query = $this->getFilteredQuery($request);
        $statsQuery = clone $query;
        $totalKegiatan = $statsQuery->count();

        // Tambahkan relasi dokumentasi untuk view index (agar bisa dihitung/preview)
        $query->with('dokumentasi');

        $perPage = $request->input('per_page', 10);
        
        // Validasi pagination
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }
        
        $datas = $query->paginate($perPage)->withQueryString();

        // Optimasi Lookup Satker
        $satkerLookup = SatuanKerja::pluck('satuan_kerja', 'id')->toArray();

        return view('p2m.lingkungan-bersinar.index', compact('datas', 'satuanKerjas', 'years', 'pegawais', 'user', 'satkerLookup', 'totalKegiatan'));
    }

    // 3. METHOD EXPORT (DOWNLOAD EXCEL)
    public function export(Request $request) 
    {
        // Panggil fungsi query yang SAMA PERSIS dengan index
        $query = $this->getFilteredQuery($request);

        return Excel::download(new LingkunganBersinarExport($query), 'Laporan_P2M_Lingkungan_Bersinar.xlsx');
    }

    public function create(): View 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->isAdmin()) {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
            $pegawais = Pegawai::with('satuanKerja')->orderBy('nama', 'asc')->get();
        } 
        else if ($user->hasRole(['operator_satker', 'operator_p2m'])){
            $satuanKerjas = [];
            $satkerId = $user->getSatkerId();
            $pegawais = Pegawai::with('satuanKerja')
                ->where('satuan_kerja_id', $satkerId)
                ->orderBy('nama', 'asc')
                ->get();
        }

        return view('p2m.lingkungan-bersinar.create', compact('satuanKerjas', 'pegawais'));
    }

    public function store(Request $request) 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // SIAPKAN RULES VALIDASI
        $rules = [
            'sasaran_kegiatan'       => 'required',
            'anggaran_pelaksanaan' => 'required|in:DIPA,NON DIPA',
            'nama_tempat_wilayah'    => 'required|string',
            'tanggal_pencanangan'    => 'required|date',
            'jumlah_penggiat_p4gn'   => 'required|numeric',
            'no_hp_penanggung_jawab' => 'nullable|string|max:20',
            
            // Validasi Array Pegawai
            'pegawai_nips'           => 'required|array',
            'pegawai_nips.*'         => 'exists:pegawai,nip',

            // Validasi Dokumentasi
            'dokumentasi'            => 'nullable|array',
            'dokumentasi.*'          => 'required',
        ];

        // Jika Admin, wajib pilih Satuan Kerja
        if ($user->isAdmin()) {
            $rules['satuan_kerja_id'] = 'required';
        }

        // EKSEKUSI VALIDASI
        $validasi = $request->validate($rules);

        $filesMoved = []; // Array pelacak file (untuk rollback)

        DB::beginTransaction(); // MULAI TRANSAKSI

        try {
            // Pisahkan data input
            $dataInput = collect($validasi)->except('dokumentasi', 'pegawai_nips')->toArray();
            $pegawaiNips = $validasi['pegawai_nips'];

            // Jika Operator, set Satker ID otomatis
            if ($user->hasRole(['operator_satker', 'operator_p2m'])) {
                $dataInput['satuan_kerja_id'] = $user->getSatkerId();
            }

            // SIMPAN DATA UTAMA
            $kegiatan = P2mLingkunganBersinar::create($dataInput);

            // -----------------------------------------------------------
            // SIMPAN RELASI PEGAWAI (PIVOT)
            // -----------------------------------------------------------
            
            // Ambil detail pegawai untuk history satker
            $listPegawai = Pegawai::whereIn('nip', $pegawaiNips)->get();

            $attachData = [];
            foreach ($listPegawai as $pgw) {
                $attachData[$pgw->nip] = [
                    'saved_satuan_kerja_id' => $pgw->satuan_kerja_id
                ];
            }

            // Simpan ke Pivot
            $kegiatan->pegawai()->attach($attachData);

            // -----------------------------------------------------------
            // PROSES PINDAH FILE (Dari Temp ke Storage Public)
            // -----------------------------------------------------------
            if ($request->filled('dokumentasi')) {
                $tempFolders = $request->input('dokumentasi');

                foreach ($tempFolders as $folder) {
                    $tempFile = TemporaryFile::where('folder', $folder)->first();

                    if ($tempFile) {
                        // Path Sumber
                        $sourcePath = 'public/tmp/' . $folder . '/' . $tempFile->filename;

                        // Metadata File
                        $mimeType = Storage::mimeType($sourcePath); 
                        $size = Storage::size($sourcePath);

                        // Generate Nama Unik
                        $ext = pathinfo($tempFile->filename, PATHINFO_EXTENSION);
                        $nameOnly = pathinfo($tempFile->filename, PATHINFO_FILENAME);
                        $cleanFileName = time() . '_' . uniqid() . '_' . Str::slug($nameOnly) . '.' . $ext;

                        // Path Tujuan
                        $destPath = 'dokumentasi/' . date('Y') . '/' . $cleanFileName;

                        if (Storage::exists($sourcePath)) {
                            // Copy File
                            Storage::disk('public')->put($destPath, Storage::readStream($sourcePath));
                            $filesMoved[] = $destPath;

                            // Simpan ke DB
                            $kegiatan->dokumentasi()->create([
                                'nama_file_asli' => $tempFile->filename,
                                'path_file'      => $destPath,
                                'tipe_file'      => $mimeType,
                                'ukuran_file'    => $size,
                            ]);

                            // Hapus Temp
                            Storage::deleteDirectory('public/tmp/' . $folder);
                            $tempFile->delete();
                        }
                    }
                }
            }

            // KOMIT TRANSAKSI
            DB::commit();

            return redirect()->route('p2m.lingkungan-bersinar.index')
                ->with('success', 'store')
                ->with('message', 'Data berhasil disimpan.');

        } catch (\Exception $e) {
            // JIKA GAGAL: ROLLBACK
            DB::rollBack();

            // Hapus file fisik yang terlanjur tercopy
            foreach ($filesMoved as $path) {
                if(Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            return back()
                ->with('error', 'store')
                ->with('message', 'Gagal menyimpan data: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit($id): View 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Ambil Data Kegiatan
        $data = P2mLingkunganBersinar::with('pegawai')->findOrFail($id);

        // Proteksi Hak Akses
        if ($user->hasRole(['operator_satker', 'operator_p2m']) && $data->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403, 'Anda tidak berhak mengubah data Satuan Kerja lain.');
        }

        // Siapkan Data Master
        if ($user->isAdmin()) {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
            $pegawais = Pegawai::orderBy('nama', 'asc')->get();
        } 
        else {
            $satuanKerjas = [];
            $satkerId = $user->getSatkerId();

            // Pegawai Aktif + Pegawai Existing (History)
            $pegawaiAktif = Pegawai::where('satuan_kerja_id', $satkerId)->get();
            $pegawaiExisting = $data->pegawai;

            $pegawais = $pegawaiAktif->merge($pegawaiExisting)->unique('nip')->sortBy('nama');
        }

        $selectedPegawaiNips = $data->pegawai->pluck('nip')->toArray();

        return view('p2m.lingkungan-bersinar.edit', compact('data', 'satuanKerjas', 'pegawais', 'selectedPegawaiNips'));
    }

    public function update(Request $request, $id) 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kegiatan = P2mLingkunganBersinar::findOrFail($id);

        // Proteksi Update
        if ($user->hasRole(['operator_satker', 'operator_p2m']) && $kegiatan->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403);
        }

        // 1. Validasi
        $rules = [
            'sasaran_kegiatan'       => 'required',
            'anggaran_pelaksanaan' => 'required|in:DIPA,NON DIPA',
            'nama_tempat_wilayah'    => 'required|string',
            'tanggal_pencanangan'    => 'required|date',
            'jumlah_penggiat_p4gn'   => 'required|numeric',
            'no_hp_penanggung_jawab' => 'nullable|string|max:20',
            'pegawai_nips'           => 'required|array',
            'pegawai_nips.*'         => 'exists:pegawai,nip',
            
            // File Handling
            'delete_files'           => 'nullable|array',
            'dokumentasi'            => 'nullable|array',
        ];

        if ($user->isAdmin()) {
            $rules['satuan_kerja_id'] = 'required';
        }

        $validasi = $request->validate($rules);

        // Variabel pelacak
        $newFilesMoved = []; 
        $filesToDelete = []; 

        DB::beginTransaction();

        try {
            $pegawaiNips = $validasi['pegawai_nips'];
            $dataUpdate = collect($validasi)->except(['dokumentasi', 'pegawai_nips', 'delete_files'])->toArray();

            if ($user->hasRole(['operator_satker', 'operator_p2m'])) {
                unset($dataUpdate['satuan_kerja_id']);
            }

            // Update Data Utama
            $kegiatan->update($dataUpdate);

            // -----------------------------------------------------------
            // UPDATE PIVOT (HISTORY PRESERVATION)
            // -----------------------------------------------------------
            
            // Ambil Data Pivot LAMA
            $oldPivot = DB::table('pegawai_p2m_lingkungan_bersinar')
                          ->where('p2m_lingkungan_bersinar_id', $id)
                          ->get()
                          ->keyBy('pegawai_nip');
            
            // Ambil Data Master Pegawai
            $masterPegawais = Pegawai::whereIn('nip', $pegawaiNips)->get()->keyBy('nip');
            
            $syncData = [];

            foreach ($pegawaiNips as $nip) {
                // Logika Pertahankan History Satker
                if (isset($oldPivot[$nip]) && $oldPivot[$nip]->saved_satuan_kerja_id) {
                    $satkerToSave = $oldPivot[$nip]->saved_satuan_kerja_id;
                } else {
                    $satkerToSave = $masterPegawais[$nip]->satuan_kerja_id ?? null;
                }

                $syncData[$nip] = ['saved_satuan_kerja_id' => $satkerToSave];
            }

            // Eksekusi Sync
            $kegiatan->pegawai()->sync($syncData);

            // -----------------------------------------------------------
            // FILE HANDLING
            // -----------------------------------------------------------

            // A. Hapus File Lama
            if ($request->has('delete_files')) {
                $filesToRemove = DokumentasiKegiatan::whereIn('id', $request->delete_files)->get();
                
                foreach ($filesToRemove as $file) {
                    $filesToDelete[] = $file->path_file; // Simpan path fisik untuk dihapus nanti
                    $file->delete(); // Hapus DB
                }
            }

            // B. Upload File Baru
            if ($request->filled('dokumentasi')) {
                $tempFolders = $request->input('dokumentasi');

                foreach ($tempFolders as $folder) {
                    $tempFile = TemporaryFile::where('folder', $folder)->first();

                    if ($tempFile) {
                        $sourcePath = 'public/tmp/' . $folder . '/' . $tempFile->filename;

                        if (Storage::exists($sourcePath)) {
                            $cleanName = time() . '_' . uniqid() . '_' . Str::slug(pathinfo($tempFile->filename, PATHINFO_FILENAME)) . '.' . pathinfo($tempFile->filename, PATHINFO_EXTENSION);
                            $destPath = 'dokumentasi/' . date('Y') . '/' . $cleanName;
                            
                            Storage::disk('public')->put($destPath, Storage::readStream($sourcePath));
                            $newFilesMoved[] = $destPath;

                            $kegiatan->dokumentasi()->create([
                                'nama_file_asli' => $tempFile->filename,
                                'path_file'      => $destPath,
                                'tipe_file'      => Storage::mimeType($sourcePath),
                                'ukuran_file'    => Storage::size($sourcePath),
                            ]);

                            Storage::deleteDirectory('public/tmp/' . $folder);
                            $tempFile->delete();
                        }
                    }
                }
            }

            // COMMIT TRANSAKSI
            DB::commit();

            // C. Cleanup Fisik (Setelah sukses DB)
            foreach ($filesToDelete as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            return redirect()->route('p2m.lingkungan-bersinar.index')
                ->with('success', 'update')
                ->with('message', 'Data berhasil diperbarui.');

        } catch (\Exception $e) {
            // ROLLBACK
            DB::rollBack();

            // Hapus file BARU yang terlanjur tercopy
            foreach ($newFilesMoved as $path) {
                if(Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            return back()
                ->with('error', 'update')
                ->with('message', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }

    public function destroy($id) 
    {
        // 1. Cari Data
        $kegiatan = P2mLingkunganBersinar::findOrFail($id);
        
        // 2. Kumpulkan Path File (Efisiensi Memori dengan Cursor)
        $filesToDelete = [];
        foreach ($kegiatan->dokumentasi()->cursor() as $doc) {
            $filesToDelete[] = $doc->path_file;
        }

        // 3. Hapus Database
        DB::beginTransaction();
        try {
            $kegiatan->delete(); // Akan trigger delete dokumentasi via boot() model
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'destroy')
                ->with('message', 'Gagal menghapus data: ' . $e->getMessage());
        }

        // 4. Hapus File Fisik
        foreach ($filesToDelete as $path) {
            try { 
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path); 
                }
            } catch (\Exception $e) {
                // Silent fail untuk file fisik
            }
        }
        
        return back()
            ->with('success', 'destroy')
            ->with('message', 'Data dan file berhasil dihapus.');
    }
}