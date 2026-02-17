<?php

namespace App\Http\Controllers\Berantas;

use App\Http\Controllers\Controller;
use App\Models\BerantasUngkapKasus;
use App\Models\BerantasNarkotika;
use App\Models\SatuanKerja;
use App\Models\Dokumen;
use App\Services\DokumenService;
use App\Constants\Pekerjaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UngkapKasusExport;

class UngkapKasusController extends Controller
{
    // --- QUERY HELPER ---
    private function applyCaseFilter($query, Request $request)
    {
        $kategori = $request->input('kategori_bb', []);
        if (empty($kategori)) return $query;

        return $query->where(function($q) use ($kategori, $request) {
            if (in_array('Narkotika', $kategori)) {
                $q->orWhere(function($sub) use ($request) {
                    $sub->where('kategori', 'Narkotika');
                    if ($request->filled('narkotika_ids')) {
                        $sub->whereIn('narkotika_id', (array)$request->narkotika_ids);
                    }
                });
            }
            if (in_array('Non-Narkotika', $kategori)) {
                $q->orWhere(function($sub) use ($request) {
                    $sub->where('kategori', 'Non-Narkotika');
                    if ($request->filled('search_non_narkotika')) {
                        $keywords = (array)$request->search_non_narkotika;
                        $sub->where(function($kQ) use ($keywords) {
                            foreach ($keywords as $key) $kQ->orWhere('nama_barang_non_narkotika', 'LIKE', "%{$key}%");
                        });
                    }
                });
            }
        });
    }

    private function getFilteredQuery(Request $request)
    {
        $user = Auth::user();
        $activeYears = $request->filled('tahun') ? $request->tahun : [date('Y')];
        
        // Sorting default by ID karena urutan dihapus
        $query = BerantasUngkapKasus::with([
            'satuanKerja', 
            'tersangka' => function($q) { $q->orderBy('id', 'asc'); }, 
            'barangBukti' => function($q) use ($request) {
                $this->applyCaseFilter($q, $request);
                $q->orderBy('id', 'asc');
            },
            'barangBukti.tersangka', 'barangBukti.narkotika', 'dokumen'
        ]);

        if ($request->filled('kategori_bb')) {
            $query->whereHas('barangBukti', function($q) use ($request) { $this->applyCaseFilter($q, $request); });
        }

        if ($user->hasRole('admin')) {
            if ($request->filled('satuan_kerja_id')) $query->whereIn('berantas_ungkap_kasus.satuan_kerja_id', $request->satuan_kerja_id);
        } else {
            $query->where('berantas_ungkap_kasus.satuan_kerja_id', $user->getSatkerId());
        }

        if ($request->filled('bulan')) $query->whereIn(DB::raw('MONTH(tanggal_kejadian)'), $request->bulan);
        $query->whereIn(DB::raw('YEAR(tanggal_kejadian)'), $activeYears);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nomor_lkn', 'LIKE', "%{$search}%")
                ->orWhere('alamat_tkp', 'LIKE', "%{$search}%")
                ->orWhereHas('tersangka', function($sq) use ($search) {
                    $sq->where('nama_tersangka', 'LIKE', "%{$search}%");
                });
            });
        }

        $sortBy = $request->input('sort_by', 'created_at'); 
        $sortOrder = $request->input('sort_order', 'desc'); 
        $query->orderBy($sortBy, $sortOrder);

        return $query; 
    }

    // --- INDEX ---
// --- INDEX (OPTIMIZED) ---
    public function index(Request $request)
    {
        $user = Auth::user();
        $satuanKerjas = $user->hasRole('admin') ? SatuanKerja::orderBy('satuan_kerja')->get() : [];
        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika', 'asc')->get();
        
        // Ambil tahun dari DB (optimasi distinct)
        $years = BerantasUngkapKasus::selectRaw('YEAR(tanggal_kejadian) as year')
            ->distinct()->orderByDesc('year')->pluck('year');

        // 1. Query Utama untuk Tabel (Pagination)
        // Tetap gunakan getFilteredQuery karena ada limit per halaman (aman)
        $query = $this->getFilteredQuery($request);
        $perPage = $request->input('per_page', 10);
        $kasus = $query->paginate($perPage)->withQueryString();


        // --- MULAI OPTIMALISASI AGREGASI (GANTI whereIn DENGAN JOIN) ---

        /**
         * Helper Closure untuk menerapkan Filter Parent ke Query Statistik.
         * Mencegah duplikasi kode filter Parent (Satker, Waktu, Search Global).
         */
        $applyParentFilters = function($q) use ($request, $user) {
            // Filter Satker
            if ($user->hasRole('admin')) {
                if ($request->filled('satuan_kerja_id')) {
                    $q->whereIn('parent.satuan_kerja_id', (array)$request->satuan_kerja_id);
                }
            } else {
                $q->where('parent.satuan_kerja_id', $user->getSatkerId());
            }

            // Filter Waktu
            if ($request->filled('bulan')) {
                $q->whereIn(DB::raw('MONTH(parent.tanggal_kejadian)'), (array)$request->bulan);
            }
            $activeYears = $request->filled('tahun') ? (array)$request->tahun : [date('Y')];
            $q->whereIn(DB::raw('YEAR(parent.tanggal_kejadian)'), $activeYears);

            // Filter Search Global (Hanya kolom Parent)
            if ($request->filled('search')) {
                $s = $request->search;
                $q->where(function($sq) use ($s) {
                    $sq->where('parent.nomor_lkn', 'LIKE', "%{$s}%")
                       ->orWhere('parent.alamat_tkp', 'LIKE', "%{$s}%");
                       // Note: Search nama tersangka di-handle di masing-masing query di bawah
                });
            }
        };

        // --- A. HITUNG TOTAL TERSANGKA ---
        // Start dari Tabel Tersangka -> JOIN ke Parent
        $statsTersangka = \App\Models\BerantasUngkapTersangka::query()
            ->join('berantas_ungkap_kasus as parent', 'berantas_ungkap_tersangka.berantas_ungkap_kasus_id', '=', 'parent.id');
        
        $applyParentFilters($statsTersangka);

        // Filter Search (Nama Tersangka)
        if ($request->filled('search')) {
            $statsTersangka->orWhere('nama_tersangka', 'LIKE', "%{$request->search}%");
        }

        // Filter Jika User memfilter BB (Relasi Tersangka <-> BB <-> Parent agak kompleks)
        // Jika filter BB aktif, kita harus pastikan Kasus (Parent) memiliki BB tersebut.
        // Di sini kita gunakan whereExists / whereHas ke tabel BB yang terhubung ke Parent yang sama
        if ($request->filled('kategori_bb') || $request->filled('narkotika_ids') || $request->filled('search_non_narkotika')) {
            $statsTersangka->whereExists(function ($query) use ($request) {
                $query->select(DB::raw(1))
                      ->from('berantas_ungkap_barang_bukti as sub_bb')
                      ->whereColumn('sub_bb.berantas_ungkap_kasus_id', 'parent.id'); // Hubungkan ke Parent

                // Terapkan filter BB (menggunakan logika applyCaseFilter tapi versi Query Builder biasa)
                $this->applyCaseFilter($query, $request);
            });
        }

        $totalTersangka = $statsTersangka->count();


        // --- B. HITUNG TOTAL BB & BERAT ---
        // Start dari Tabel BB -> JOIN ke Parent
        $statsBB = \App\Models\BerantasUngkapBarangBukti::query()
            ->join('berantas_ungkap_kasus as parent', 'berantas_ungkap_barang_bukti.berantas_ungkap_kasus_id', '=', 'parent.id');

        $applyParentFilters($statsBB);

        // Filter Search Global (Jika search match nama tersangka, BB dari kasus itu harus ikut)
        if ($request->filled('search')) {
            $statsBB->orWhereExists(function($sq) use ($request) {
                $sq->select(DB::raw(1))
                   ->from('berantas_ungkap_tersangka as sub_tsk')
                   ->whereColumn('sub_tsk.berantas_ungkap_kasus_id', 'parent.id')
                   ->where('nama_tersangka', 'LIKE', "%{$request->search}%");
            });
        }

        // Filter Spesifik BB (Langsung di tabel BB ini)
        // Kita gunakan helper yang sudah ada, tapi sesuaikan context
        $this->applyCaseFilter($statsBB, $request);

        // Eksekusi Hitung BB (Sekali query untuk Count dan Sum)
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
        $totalKasus = $kasus->total();

        // --- END OPTIMALISASI ---

        return view('berantas.ungkap-kasus.index', compact(
            'kasus', 'satuanKerjas', 'years', 'masterNarkotika', 
            'totalKasus', 'totalTersangka', 'totalBBNarkotika', 'totalBeratGram'
        ));
    }

    // --- CREATE ---
    public function create()
    {
        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika', 'asc')->get();
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja')->get();
        return view('berantas.ungkap-kasus.create', compact('masterNarkotika', 'satuanKerjas'));
    }

    // --- STORE ---
    public function store(Request $request, DokumenService $dokumenService)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'nomor_lkn'        => 'required|unique:berantas_ungkap_kasus,nomor_lkn',
            'tanggal_kejadian' => 'required|date',
            'alamat_tkp'       => 'required|string',
            'latitude'         => 'required|numeric',
            'longitude'        => 'required|numeric',
            'kronologis'       => 'nullable|string',
            
            // TERSANGKA
            'tersangka'             => 'required|array|min:1',
            'tersangka.*.nama'      => 'required|string',
            'tersangka.*.jk'        => 'required|in:Laki-Laki,Perempuan',
            'tersangka.*.pekerjaan' => ['required', Rule::in(Pekerjaan::ALL)],
            'tersangka.*.tahap'     => 'required|string',
            
            // BB GROUPS
            'bb_groups'          => 'required|array|min:1',
            'bb_groups.*.owners' => 'required|array|min:1', 
            'bb_groups.*.items'  => 'required|array|min:1',
            
            // ITEM BB
            'bb_groups.*.items.*.kategori' => 'required|in:Narkotika,Non-Narkotika',
            'bb_groups.*.items.*.jumlah'   => 'required|numeric',
            'bb_groups.*.items.*.narkotika_id'      => 'required_if:bb_groups.*.items.*.kategori,Narkotika',
            'bb_groups.*.items.*.nama_barang_bukti' => 'required_if:bb_groups.*.items.*.kategori,Non-Narkotika',

            'dokumentasi'       => 'nullable|array',
            'lampiran'          => 'nullable|array',
            'dokumentasi_links' => 'nullable|array',
            'lampiran_links'    => 'nullable|array',
        ]);

        if ($validator->fails()) return back()->withErrors($validator)->withInput();

        $uploadedPaths = []; 
        DB::beginTransaction();

        try {
            $kasus = BerantasUngkapKasus::create([
                'nomor_lkn'        => $request->nomor_lkn,
                'tanggal_kejadian' => $request->tanggal_kejadian,
                'alamat_tkp'       => $request->alamat_tkp,
                'latitude'         => $request->latitude,
                'longitude'        => $request->longitude,
                'kronologis'       => $request->kronologis,
                'satuan_kerja_id'  => $user->isAdmin() ? $request->satuan_kerja_id : $user->getSatkerId(),
            ]);

            $tempIdToDbId = [];

            foreach ($request->tersangka as $index => $tData) {
                $fotoPath = null;
                if ($request->hasFile("tersangka.{$index}.foto")) {
                    $file = $request->file("tersangka.{$index}.foto");
                    $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $fotoPath = $file->storeAs('foto_tersangka/' . date('Y'), $filename, 'public');
                    $uploadedPaths[] = $fotoPath;
                }

                $tersangka = $kasus->tersangka()->create([
                    'nama_tersangka' => $tData['nama'],
                    'jenis_kelamin'  => $tData['jk'],
                    'pekerjaan'      => $tData['pekerjaan'],
                    'tahap'          => $tData['tahap'], 
                    'foto_tersangka' => $fotoPath,
                ]);

                if (isset($tData['temp_id'])) $tempIdToDbId[$tData['temp_id']] = $tersangka->id;
            }

            foreach ($request->bb_groups as $group) {
                $ownerDbIds = [];
                if (isset($group['owners']) && is_array($group['owners'])) {
                    foreach ($group['owners'] as $tempId) {
                        if (isset($tempIdToDbId[$tempId])) $ownerDbIds[] = $tempIdToDbId[$tempId];
                    }
                }

                if (isset($group['items']) && is_array($group['items'])) {
                    foreach ($group['items'] as $bbData) {
                        $isNarkotika = ($bbData['kategori'] === 'Narkotika');
                        $narkotikaId = $isNarkotika ? ($bbData['narkotika_id'] ?? null) : null;
                        $namaBarang  = !$isNarkotika ? ($bbData['nama_barang_bukti'] ?? null) : null;

                        if ($isNarkotika && empty($narkotikaId)) continue;
                        if (!$isNarkotika && empty($namaBarang)) continue;

                        $bb = $kasus->barangBukti()->create([
                            'kategori'                  => $bbData['kategori'],
                            'narkotika_id'              => $narkotikaId,
                            'nama_barang_non_narkotika' => $namaBarang,
                            'kuantitas'                 => $bbData['jumlah'],
                            'satuan_narkotika'          => $isNarkotika ? ($bbData['satuan'] ?? 'Gram') : null,
                            'satuan_non_narkotika'      => !$isNarkotika ? ($bbData['satuan'] ?? null) : null,
                        ]);

                        if (!empty($ownerDbIds)) $bb->tersangka()->attach($ownerDbIds);
                    }
                }
            }

            if ($request->filled('dokumentasi')) $dokumenService->moveToPermanent($request->input('dokumentasi'), $kasus, 'dokumentasi', $uploadedPaths);
            if ($request->filled('lampiran')) $dokumenService->moveToPermanent($request->input('lampiran'), $kasus, 'lampiran', $uploadedPaths);
            if ($request->filled('dokumentasi_links')) $dokumenService->saveLinks($request->input('dokumentasi_links'), $kasus, 'dokumentasi');
            if ($request->filled('lampiran_links')) $dokumenService->saveLinks($request->input('lampiran_links'), $kasus, 'lampiran');

            DB::commit();
            return redirect()->route('berantas.ungkap-kasus.index')->with('success', 'Data Berhasil Disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($uploadedPaths as $path) Storage::disk('public')->delete($path);
            Log::error('Store Kasus Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal: ' . $e->getMessage())->withInput();
        }
    }

    // --- EDIT ---
    public function edit($id)
    {
        $kasus = BerantasUngkapKasus::with(['tersangka', 'barangBukti.tersangka', 'dokumen'])->findOrFail($id);
        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika', 'asc')->get();
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja')->get();
        return view('berantas.ungkap-kasus.edit', compact('kasus', 'masterNarkotika', 'satuanKerjas'));
    }

    // --- UPDATE ---
    public function update(Request $request, DokumenService $dokumenService, $id)
    {
        $user = Auth::user();
        $kasus = BerantasUngkapKasus::findOrFail($id);

        if ($user->hasRole(['operator_satker', 'operator_berantas']) && $kasus->satuan_kerja_id !== $user->getSatkerId()) abort(403);

        $validator = Validator::make($request->all(), [
            'nomor_lkn'        => 'required|unique:berantas_ungkap_kasus,nomor_lkn,' . $id,
            'tanggal_kejadian' => 'required|date',
            'alamat_tkp'       => 'required|string',
            'latitude'         => 'required|numeric',
            'longitude'        => 'required|numeric',
            'kronologis'       => 'nullable|string',
            
            'tersangka'             => 'required|array|min:1',
            'tersangka.*.nama'      => 'required|string',
            'tersangka.*.jk'        => 'required|in:Laki-Laki,Perempuan',
            'tersangka.*.pekerjaan' => ['required', Rule::in(Pekerjaan::ALL)],
            'tersangka.*.tahap'     => 'required|string',
            
            'bb_groups'          => 'required|array|min:1',
            'bb_groups.*.owners' => 'required|array|min:1', 
            'bb_groups.*.items'  => 'required|array|min:1',
            'bb_groups.*.items.*.kategori'          => 'required|in:Narkotika,Non-Narkotika',
            'bb_groups.*.items.*.jumlah'            => 'required|numeric',
            'bb_groups.*.items.*.narkotika_id'      => 'required_if:bb_groups.*.items.*.kategori,Narkotika',
            'bb_groups.*.items.*.nama_barang_bukti' => 'required_if:bb_groups.*.items.*.kategori,Non-Narkotika',

            'delete_files'      => 'nullable|array',
            'dokumentasi'       => 'nullable|array',
            'lampiran'          => 'nullable|array',
            'dokumentasi_links' => 'nullable|array',
            'lampiran_links'    => 'nullable|array',
        ]);

        if ($validator->fails()) return back()->withErrors($validator)->withInput();

        $uploadedPaths = []; 
        $filesToDelete = []; 

        DB::beginTransaction();
        try {
            $dataUpdate = [
                'nomor_lkn'        => $request->nomor_lkn,
                'tanggal_kejadian' => $request->tanggal_kejadian,
                'alamat_tkp'       => $request->alamat_tkp,
                'latitude'         => $request->latitude,
                'longitude'        => $request->longitude,
                'kronologis'       => $request->kronologis,
            ];
            if ($user->isAdmin()) $dataUpdate['satuan_kerja_id'] = $request->satuan_kerja_id;
            $kasus->update($dataUpdate);

            // Bersihkan data lama
            foreach ($kasus->tersangka as $oldTsk) {
                if ($oldTsk->foto_tersangka) $filesToDelete[] = $oldTsk->foto_tersangka;
                $oldTsk->delete(); 
            }
            $kasus->barangBukti()->delete();

            $tempIdToDbId = []; 

            foreach ($request->tersangka as $index => $tData) {
                $fotoPath = null;
                if ($request->hasFile("tersangka.{$index}.foto")) {
                    $file = $request->file("tersangka.{$index}.foto");
                    $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $fotoPath = $file->storeAs('foto_tersangka/' . date('Y'), $filename, 'public');
                    $uploadedPaths[] = $fotoPath;
                } elseif (isset($tData['old_foto']) && !empty($tData['old_foto'])) {
                    // Jika ada old_foto yang dikirim kembali, berarti tidak dihapus
                    $fotoPath = $tData['old_foto'];
                    $filesToDelete = array_diff($filesToDelete, [$fotoPath]);
                }

                $tersangka = $kasus->tersangka()->create([
                    'nama_tersangka' => $tData['nama'],
                    'jenis_kelamin'  => $tData['jk'],
                    'pekerjaan'      => $tData['pekerjaan'],
                    'tahap'          => $tData['tahap'],
                    'foto_tersangka' => $fotoPath,
                ]);

                if (isset($tData['temp_id'])) $tempIdToDbId[$tData['temp_id']] = $tersangka->id;
            }

            foreach ($request->bb_groups as $group) {
                $ownerDbIds = [];
                if (isset($group['owners']) && is_array($group['owners'])) {
                    foreach ($group['owners'] as $tempId) {
                        if (isset($tempIdToDbId[$tempId])) $ownerDbIds[] = $tempIdToDbId[$tempId];
                    }
                }

                if (isset($group['items']) && is_array($group['items'])) {
                    foreach ($group['items'] as $bbData) {
                        $isNarkotika = ($bbData['kategori'] === 'Narkotika');
                        $narkotikaId = $isNarkotika ? ($bbData['narkotika_id'] ?? null) : null;
                        $namaBarang  = !$isNarkotika ? ($bbData['nama_barang_bukti'] ?? null) : null;

                        if ($isNarkotika && empty($narkotikaId)) continue;
                        if (!$isNarkotika && empty($namaBarang)) continue;

                        $bb = $kasus->barangBukti()->create([
                            'kategori'                  => $bbData['kategori'],
                            'narkotika_id'              => $narkotikaId,
                            'nama_barang_non_narkotika' => $namaBarang,
                            'kuantitas'                 => $bbData['jumlah'],
                            'satuan_narkotika'          => $isNarkotika ? ($bbData['satuan'] ?? 'Gram') : null,
                            'satuan_non_narkotika'      => !$isNarkotika ? ($bbData['satuan'] ?? null) : null,
                        ]);

                        if (!empty($ownerDbIds)) $bb->tersangka()->attach($ownerDbIds);
                    }
                }
            }

            if ($request->has('delete_files')) {
                $filesToRemove = Dokumen::whereIn('id', $request->delete_files)->get();
                foreach ($filesToRemove as $file) {
                    if (!$file->is_link) $filesToDelete[] = $file->path_file;
                    $file->delete();
                }
            }

            if ($request->filled('dokumentasi')) $dokumenService->moveToPermanent($request->input('dokumentasi'), $kasus, 'dokumentasi', $uploadedPaths);
            if ($request->filled('lampiran')) $dokumenService->moveToPermanent($request->input('lampiran'), $kasus, 'lampiran', $uploadedPaths);
            if ($request->filled('dokumentasi_links')) $dokumenService->saveLinks($request->input('dokumentasi_links'), $kasus, 'dokumentasi');
            if ($request->filled('lampiran_links')) $dokumenService->saveLinks($request->input('lampiran_links'), $kasus, 'lampiran');

            DB::commit();

            foreach ($filesToDelete as $path) {
                if ($path && Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
            }

            return redirect()->route('berantas.ungkap-kasus.index')->with('success', 'Data diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($uploadedPaths as $path) Storage::disk('public')->delete($path);
            Log::error('Update error: ' . $e->getMessage());
            return back()->with('error', 'Gagal update: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id) 
    {
        $kasus = BerantasUngkapKasus::findOrFail($id);
        $filesToDelete = [];

        foreach ($kasus->dokumen()->cursor() as $doc) {
            if (!$doc->is_link && !empty($doc->path_file)) $filesToDelete[] = $doc->path_file;
        }
        foreach ($kasus->tersangka as $tsk) {
            if (!empty($tsk->foto_tersangka)) $filesToDelete[] = $tsk->foto_tersangka;
        }

        DB::beginTransaction();
        try {
            $kasus->delete(); 
            DB::commit(); 
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }

        foreach ($filesToDelete as $path) {
            if ($path && Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
        }

        return redirect()->back()->with('success', 'Data dihapus.');
    }

    public function export(Request $request)
    {
        $query = $this->getFilteredQuery($request);
        return Excel::download(new UngkapKasusExport($query), 'Laporan_Ungkap_Kasus_'.date('d-m-Y').'.xlsx');
    }
}