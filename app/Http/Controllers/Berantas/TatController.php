<?php

namespace App\Http\Controllers\Berantas;

use App\Http\Controllers\Controller;
use App\Models\BerantasTat;
use App\Models\BerantasNarkotika;
use App\Models\SatuanKerja;
use App\Models\BerantasTatBarangBukti;
use App\Models\BerantasTatTersangka;
use App\Models\Dokumen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Constants\Pendidikan;
use App\Constants\Pekerjaan;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TatExport;
use App\Services\DokumenService;
use Illuminate\Support\Facades\Log;

class TatController extends Controller
{
    
    private function getFilteredQuery(Request $request)
    {
        $user = Auth::user();
        
        // 1. EAGER LOADING (Menyiapkan data relasi untuk ditampilkan)
        $query = BerantasTat::with([
            'satuanKerja', 
            'tersangka', 
            'barangBukti.narkotika',
            'dokumen',
            'barangBukti' => function($q) use ($request) {
                // Filter tampilan BB di tabel berdasarkan kategori
                if ($request->filled('kategori_bb')) {
                    $q->whereIn('kategori', (array)$request->kategori_bb);
                }
            }
        ]);

        // 2. FILTER BARANG BUKTI (Ini yang menyembunyikan kasus TAT jika tidak sesuai filter)
        if ($request->filled('kategori_bb') || $request->filled('narkotika_ids') || $request->filled('search_non_narkotika')) {
            $query->whereHas('barangBukti', function ($q) use ($request) {
                
                // Filter Kategori (Narkotika / Non-Narkotika)
                if ($request->filled('kategori_bb')) {
                    $q->whereIn('kategori', (array) $request->kategori_bb);
                }

                $hasNarkotika = $request->filled('narkotika_ids');
                $hasNonNarkotika = $request->filled('search_non_narkotika');

                // Filter Spesifik Jenis Narkotika atau Nama Barang Non-Narkotika
                if ($hasNarkotika || $hasNonNarkotika) {
                    $q->where(function ($subQ) use ($request, $hasNarkotika, $hasNonNarkotika) {
                        
                        if ($hasNarkotika) {
                            $subQ->orWhere(function ($qNarkotika) use ($request) {
                                $qNarkotika->where('kategori', 'Narkotika')
                                        ->whereIn('narkotika_id', (array) $request->narkotika_ids);
                            });
                        }

                        if ($hasNonNarkotika) {
                            $subQ->orWhere(function ($qNonNarkotika) use ($request) {
                                $qNonNarkotika->where('kategori', 'Non-Narkotika')
                                            ->whereIn('nama_barang_non_narkotika', (array) $request->search_non_narkotika);
                            });
                        }
                        
                    });
                }
            });
        }

        // 3. Filter Satker
        if (!$user->hasRole('admin')) {
            $query->where('satuan_kerja_id', $user->getSatkerId());
        } else {
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('satuan_kerja_id', (array)$request->satuan_kerja_id);
            }
        }

        // 4. Filter Waktu
        if ($request->filled('bulan')) {
            $query->whereIn(DB::raw('MONTH(tanggal_pelaksanaan)'), (array)$request->bulan);
        }
        
        $years = $request->filled('tahun') ? (array)$request->tahun : [date('Y')];
        $query->whereIn(DB::raw('YEAR(tanggal_pelaksanaan)'), $years);

        // 5. Filter Pencarian
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('no_register', 'LIKE', "%{$s}%")
                ->orWhere('instansi_pengirim', 'LIKE', "%{$s}%")
                ->orWhere('pasal_disangkakan', 'LIKE', "%{$s}%")
                ->orWhereHas('tersangka', function($sq) use ($s) {
                    $sq->where('nama_tersangka', 'LIKE', "%{$s}%");
                });
            });
        }
        
        // 6. Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSorts = [
            'no_register', 
            'satuan_kerja_id', 
            'created_at', 
            'tanggal_pelaksanaan'
        ];
        
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    public function index(Request $request)
    {
        // 1. Data Dropdown (Ringan)
        $years = BerantasTat::selectRaw('YEAR(tanggal_pelaksanaan) as year')
            ->distinct()->orderBy('year', 'desc')->pluck('year');
        $currentYear = (int) date('Y');
        // Cek apakah tahun sekarang sudah ada di koleksi
        if (!$years->contains($currentYear)) {
            // Tambahkan dan urutkan ulang hanya jika perlu
            $years->push($currentYear)->sortDesc()->values();
        }
        
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja')->get();
        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika')->get();

        // 2. Query Utama untuk Tabel (Pagination)
        // Tetap gunakan ini untuk tampilan tabel karena pagination melimit data yg diambil
        $query = $this->getFilteredQuery($request);
        
        $perPage = $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100])) $perPage = 10;
        
        $data = $query->paginate($perPage)->withQueryString();


        // --- MULAI OPTIMALISASI AGREGASI (GANTI whereIn DENGAN JOIN) ---

        /**
         * Helper Closure untuk menerapkan Filter Parent ke Query Statistik.
         * Ini mencegah duplikasi kode filter antara Stats Tersangka & Stats BB.
         */
        $applyParentFilters = function($q) use ($request) {
            // Filter Satker
            if (!Auth::user()->hasRole('admin')) {
                $q->where('parent.satuan_kerja_id', Auth::user()->getSatkerId());
            } elseif ($request->filled('satuan_kerja_id')) {
                $q->whereIn('parent.satuan_kerja_id', (array)$request->satuan_kerja_id);
            }

            // Filter Waktu
            if ($request->filled('bulan')) {
                $q->whereIn(DB::raw('MONTH(parent.tanggal_pelaksanaan)'), (array)$request->bulan);
            }
            $yearFilter = $request->filled('tahun') ? (array)$request->tahun : [date('Y')];
            $q->whereIn(DB::raw('YEAR(parent.tanggal_pelaksanaan)'), $yearFilter);

            // Filter Search (Hanya bagian Parent/Header)
            if ($request->filled('search')) {
                $s = $request->search;
                $q->where(function($sq) use ($s) {
                    $sq->where('parent.no_register', 'LIKE', "%{$s}%")
                       ->orWhere('parent.instansi_pengirim', 'LIKE', "%{$s}%")
                       ->orWhere('parent.pasal_disangkakan', 'LIKE', "%{$s}%");
                       // Catatan: Pencarian nama tersangka di-handle berbeda tiap query di bawah
                });
            }
        };

        // --- A. HITUNG TOTAL TERSANGKA ---
        // Start dari Tabel Tersangka -> JOIN ke Parent
        $statsTersangka = BerantasTatTersangka::query()
            ->join('berantas_tat as parent', 'berantas_tat_tersangka.berantas_tat_id', '=', 'parent.id');
        
        $applyParentFilters($statsTersangka);

        // Tambahan filter search khusus nama tersangka (karena kita sedang di tabel tersangka)
        if ($request->filled('search')) {
            $statsTersangka->orWhere('nama_tersangka', 'LIKE', "%{$request->search}%");
        }
        
        // Kita juga perlu memfilter Tersangka berdasarkan filter Barang Bukti (Relasi tidak langsung)
        // Jika user filter "Sabu", maka hanya tersangka dari kasus Sabu yang dihitung.
        if ($request->filled('kategori_bb') || $request->filled('narkotika_ids') || $request->filled('search_non_narkotika')) {
            $statsTersangka->whereHas('parent.barangBukti', function($q) use ($request) {
                // ... (Copy logika filter BB dari getFilteredQuery di sini agar akurat) ...
                // Atau sederhananya: Memastikan Parent punya BB sesuai kriteria
                if ($request->filled('kategori_bb')) $q->whereIn('kategori', (array)$request->kategori_bb);
                if ($request->filled('narkotika_ids')) $q->whereIn('narkotika_id', (array)$request->narkotika_ids);
            });
        }

        $totalTersangka = $statsTersangka->count();


        // --- B. HITUNG TOTAL BB & BERAT ---
        // Start dari Tabel BB -> JOIN ke Parent
        $statsBB = BerantasTatBarangBukti::query()
            ->join('berantas_tat as parent', 'berantas_tat_barang_bukti.berantas_tat_id', '=', 'parent.id');

        $applyParentFilters($statsBB);

        // Filter Search Global (Jika search match nama tersangka, BB-nya juga harus ikut terhitung)
        if ($request->filled('search')) {
            $statsBB->orWhereHas('parent.tersangka', fn($q) => $q->where('nama_tersangka', 'LIKE', "%{$request->search}%"));
        }

        // Filter Spesifik BB (Langsung di tabel ini)
        if ($request->filled('kategori_bb')) {
            $statsBB->whereIn('kategori', (array)$request->kategori_bb);
        }
        if ($request->filled('narkotika_ids')) {
            $statsBB->whereIn('narkotika_id', (array)$request->narkotika_ids);
        }
        if ($request->filled('search_non_narkotika')) {
            $statsBB->whereIn('nama_barang_non_narkotika', (array)$request->search_non_narkotika);
        }

        // Eksekusi Hitung BB (Sekali jalan untuk Count dan Sum)
        $resultBB = $statsBB->where('kategori', 'Narkotika')
            ->selectRaw('COUNT(*) as total_items')
            ->selectRaw("SUM(CASE 
                WHEN satuan_narkotika = 'Kg' THEN kuantitas * 1000 
                WHEN satuan_narkotika = 'Ton' THEN kuantitas * 1000000 
                ELSE kuantitas 
            END) as total_berat")
            ->first();

        $totalBBNarkotika = $resultBB->total_items ?? 0;
        $totalBeratGram   = $resultBB->total_berat ?? 0;

        // Total Kasus (Header) diambil dari metadata pagination
        $totalKasus = $data->total();

        // --- END OPTIMALISASI ---

        return view('berantas.tat.index', compact(
            'data', 
            'satuanKerjas', 
            'years', 
            'masterNarkotika',
            'totalKasus', 
            'totalTersangka', 
            'totalBBNarkotika', 
            'totalBeratGram'
        ));
    }

    public function create()
    {
        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika')->get();
        $satuanKerjas = SatuanKerja::all();
        
        return view('berantas.tat.create', compact(
            'masterNarkotika', 
            'satuanKerjas'
        ));
    }

    public function store(Request $request, DokumenService $dokumenService)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'no_register' => 'required|unique:berantas_tat',
            'tanggal_pelaksanaan' => 'required|date',
            
            'tersangka' => 'required|array|min:1',
            'tersangka.*.nama' => 'required|string',
            'tersangka.*.nik' => 'nullable|numeric',
            'tersangka.*.jk' => 'required|in:Laki-laki,Perempuan',
            'tersangka.*.usia' => 'required|numeric|min:0',
            'tersangka.*.pendidikan' => ['required', Rule::in(Pendidikan::ALL)],
            'tersangka.*.pekerjaan' => ['required', Rule::in(Pekerjaan::ALL)],
            'tersangka.*.no_telepon' => 'nullable|string',

            'barang_bukti' => 'required|array|min:1',
            'barang_bukti.*.kategori' => 'required|in:Narkotika,Non-Narkotika',
            'barang_bukti.*.jumlah' => 'required|numeric|min:0',

            'tim_hukum' => 'nullable|array',
            'tim_hukum.*.nama' => 'required_with:tim_hukum|string',
            'tim_hukum.*.instansi' => 'required_with:tim_hukum|string',
            
            'tim_medis' => 'nullable|array',
            'tim_medis.*.nama' => 'required_with:tim_medis|string',

            'pasal_disangkakan' => 'nullable|string',
            'biaya' => 'nullable|numeric|min:0',
            
            'dokumentasi' => 'nullable|array', 
            'lampiran' => 'nullable|array',
            'dokumentasi_links' => 'nullable|array',
            'lampiran_links' => 'nullable|array',
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->has('barang_bukti')) {
                foreach ($request->barang_bukti as $index => $bb) {
                    if ($bb['kategori'] === 'Narkotika') {
                        if(empty($bb['narkotika_id'])) {
                            $validator->errors()->add("barang_bukti.$index.narkotika_id", "Pilih Narkotika.");
                        }
                        if(empty($bb['satuan_narkotika'])) {
                            $validator->errors()->add("barang_bukti.$index.satuan_narkotika", "Pilih Satuan.");
                        }
                    }
                    if ($bb['kategori'] === 'Non-Narkotika') {
                        if(empty($bb['nama_barang_bukti'])) {
                            $validator->errors()->add("barang_bukti.$index.nama_barang_bukti", "Nama wajib diisi.");
                        }
                        if(empty($bb['satuan_non_narkotika'])) {
                            $validator->errors()->add("barang_bukti.$index.satuan_non_narkotika", "Satuan wajib diisi.");
                        }
                    }
                }
            }
        });

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $uploadedPaths = [];
        DB::beginTransaction();
        
        try {
            $data = $request->except([
                'tersangka', 'barang_bukti', 
                'dokumentasi', 'lampiran', 
                'dokumentasi_links', 'lampiran_links'
            ]);
            
            $data['satuan_kerja_id'] = $user->isAdmin() ? $request->satuan_kerja_id : $user->getSatkerId();
            
            $tat = BerantasTat::create($data);

            // Simpan Tersangka
            foreach ($request->tersangka as $t) {
                $tat->tersangka()->create([
                    'nama_tersangka' => $t['nama'],
                    'nik'            => $t['nik'],
                    'jenis_kelamin'  => $t['jk'],
                    'usia'           => $t['usia'],
                    'pendidikan'     => $t['pendidikan'],
                    'pekerjaan'      => $t['pekerjaan'],
                    'no_telepon'     => $t['no_telepon'] ?? null,
                ]);
            }

            // Simpan BB
            foreach ($request->barang_bukti as $bb) {
                $isNarkotika = $bb['kategori'] === 'Narkotika';
                
                $tat->barangBukti()->create([
                    'kategori' => $bb['kategori'],
                    'narkotika_id' => $isNarkotika ? $bb['narkotika_id'] : null,
                    'nama_barang_non_narkotika' => !$isNarkotika ? $bb['nama_barang_bukti'] : null,
                    'kuantitas' => $bb['jumlah'] ?? 0,
                    'satuan_narkotika' => $isNarkotika ? $bb['satuan_narkotika'] : null,
                    'satuan_non_narkotika' => !$isNarkotika ? $bb['satuan_non_narkotika'] : null,
                ]);
            }

            // Upload Dokumen
            if ($request->filled('dokumentasi')) {
                $dokumenService->moveToPermanent($request->input('dokumentasi'), $tat, 'dokumentasi', $uploadedPaths);
            }
            if ($request->filled('lampiran')) {
                $dokumenService->moveToPermanent($request->input('lampiran'), $tat, 'lampiran', $uploadedPaths);
            }
            if ($request->filled('dokumentasi_links')) {
                $dokumenService->saveLinks($request->input('dokumentasi_links'), $tat, 'dokumentasi');
            }
            if ($request->filled('lampiran_links')) {
                $dokumenService->saveLinks($request->input('lampiran_links'), $tat, 'lampiran');
            }

            DB::commit();
            return redirect()->route('berantas.tat.index')->with('success', 'Data TAT berhasil disimpan.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($uploadedPaths as $path) {
                Storage::disk(config('filesystems.default'))->delete($path);
            }
            Log::error('Error Store TAT: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan server.')->withInput();
        }
    }

    public function edit($id)
    {
        $tat = BerantasTat::with(['tersangka', 'barangBukti', 'dokumen'])->findOrFail($id);
        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika')->get();
        $satuanKerjas = SatuanKerja::all();

        $user = Auth::user();
        if (!$user->isAdmin() && $tat->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403);
        }
        
        return view('berantas.tat.edit', compact(
            'tat', 
            'masterNarkotika', 
            'satuanKerjas'
        ));
    }

    public function update(Request $request, DokumenService $dokumenService, $id)
    {
        $tat = BerantasTat::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'no_register' => 'required|unique:berantas_tat,no_register,' . $id,
            
            'tersangka' => 'required|array|min:1',
            'tersangka.*.nama' => 'required|string',
            'tersangka.*.nik' => 'nullable|numeric',
            'tersangka.*.pendidikan' => ['required', Rule::in(Pendidikan::ALL)],
            'tersangka.*.pekerjaan' => ['required', Rule::in(Pekerjaan::ALL)],
            
            'barang_bukti' => 'required|array|min:1',
            'barang_bukti.*.jumlah' => 'required|numeric|min:0',
            
            'tim_hukum' => 'nullable|array',
            'tim_hukum.*.nama' => 'required_with:tim_hukum|string',
            
            'tim_medis' => 'nullable|array',
            'tim_medis.*.nama' => 'required_with:tim_medis|string',
            
            'delete_files' => 'nullable|array', 
            'dokumentasi' => 'nullable|array',
            'lampiran' => 'nullable|array',
            'dokumentasi_links' => 'nullable|array',
            'lampiran_links' => 'nullable|array',
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->has('barang_bukti')) {
                foreach ($request->barang_bukti as $index => $bb) {
                    if ($bb['kategori'] === 'Narkotika') {
                        if(empty($bb['narkotika_id'])) {
                            $validator->errors()->add("barang_bukti.$index.narkotika_id", "Pilih Narkotika.");
                        }
                        if(empty($bb['satuan_narkotika'])) {
                            $validator->errors()->add("barang_bukti.$index.satuan_narkotika", "Pilih Satuan.");
                        }
                    }
                    if ($bb['kategori'] === 'Non-Narkotika') {
                        if(empty($bb['nama_barang_bukti'])) {
                            $validator->errors()->add("barang_bukti.$index.nama_barang_bukti", "Nama wajib diisi.");
                        }
                        if(empty($bb['satuan_non_narkotika'])) {
                            $validator->errors()->add("barang_bukti.$index.satuan_non_narkotika", "Satuan wajib diisi.");
                        }
                    }
                }
            }
        });

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $newFilesMoved = [];
        DB::beginTransaction();
        
        try {
            $tat->update($request->except([
                'tersangka', 'barang_bukti', 'delete_files', 
                'dokumentasi', 'lampiran', 
                'dokumentasi_links', 'lampiran_links'
            ]));
            
            // Re-create Tersangka
            $tat->tersangka()->delete();
            foreach ($request->tersangka as $t) {
                $tat->tersangka()->create([
                    'nama_tersangka' => $t['nama'],
                    'nik'            => $t['nik'],
                    'jenis_kelamin'  => $t['jk'],
                    'usia'           => $t['usia'],
                    'pendidikan'     => $t['pendidikan'],
                    'pekerjaan'      => $t['pekerjaan'],
                    'no_telepon'     => $t['no_telepon'] ?? null,
                ]);
            }

            // Re-create BB
            $tat->barangBukti()->delete();
            foreach ($request->barang_bukti as $bb) {
                $isNarkotika = $bb['kategori'] === 'Narkotika';
                $tat->barangBukti()->create([
                    'kategori' => $bb['kategori'],
                    'narkotika_id' => $isNarkotika ? $bb['narkotika_id'] : null,
                    'nama_barang_non_narkotika' => !$isNarkotika ? $bb['nama_barang_bukti'] : null,
                    'kuantitas' => $bb['jumlah'] ?? 0,
                    'satuan_narkotika' => $isNarkotika ? $bb['satuan_narkotika'] : null,
                    'satuan_non_narkotika' => !$isNarkotika ? $bb['satuan_non_narkotika'] : null,
                ]);
            }

            // Hapus Dokumen Lama
            if ($request->has('delete_files')) {
                $filesToRemove = Dokumen::whereIn('id', $request->delete_files)->get();
                foreach ($filesToRemove as $file) {
                    if (!$file->is_link) $filesToDelete[] = $file->path_file;
                    $file->delete();
                }
            }

            if ($request->filled('dokumentasi')) {
                $dokumenService->moveToPermanent($request->input('dokumentasi'), $tat, 'dokumentasi', $newFilesMoved);
            }
            if ($request->filled('lampiran')) {
                $dokumenService->moveToPermanent($request->input('lampiran'), $tat, 'lampiran', $newFilesMoved);
            }
            if ($request->filled('dokumentasi_links')) {
                $dokumenService->saveLinks($request->input('dokumentasi_links'), $tat, 'dokumentasi');
            }
            if ($request->filled('lampiran_links')) {
                $dokumenService->saveLinks($request->input('lampiran_links'), $tat, 'lampiran');
            }

            DB::commit();
            foreach ($filesToDelete ?? [] as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            return redirect()->route('berantas.tat.index')->with('success', 'Data TAT diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($newFilesMoved as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
            Log::error('Update error: ' . $e->getMessage());
            abort(500, 'Server Error.');
        }
    }

    public function destroy($id) 
    {
        $tat = BerantasTat::findOrFail($id);
        $filesToDelete = [];
        foreach ($tat->dokumen()->cursor() as $doc) {
            if (!$doc->is_link && !empty($doc->path_file)) {
                $filesToDelete[] = $doc->path_file;
            }
        }

        DB::beginTransaction();
        try {
            $tat->delete(); 
            DB::commit(); 
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
            return back()
                ->with('error', 'destroy')
                ->with('message', 'Gagal menghapus data: ' . $e->getMessage());
        }

        foreach ($filesToDelete as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        return redirect()->back()
            ->with('success', 'Data dan file berhasil dihapus')
            ->with('message', 'Data dan file berhasil dihapus.');
    }

    public function export(Request $request)
    {
        $query = $this->getFilteredQuery($request);
        return Excel::download(
            new TatExport($query), 
            'Laporan_TAT_'.date('d-m-Y').'.xlsx'
        );
    }
}