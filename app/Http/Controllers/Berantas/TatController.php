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
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TatExport;
use App\Services\DokumenService;
use Illuminate\Support\Facades\Log;

class TatController extends Controller
{
    private function getFilteredQuery(Request $request)
    {
        $user = Auth::user();
        
        $query = BerantasTat::with([
            'satuanKerja', 
            'tersangka', 
            'barangBukti.narkotika',
            'dokumen',
            'barangBukti' => function($q) use ($request) {
                if ($request->filled('kategori_bb')) {
                    $q->whereIn('kategori', (array) $request->kategori_bb);
                }
            }
        ]);

        $isGlobalSearch = $request->filled('filter_nik') || 
                          $request->filled('filter_no_telepon');

        // 1. Filter Satker
        if (!$user->hasRole('admin') && !$isGlobalSearch) {
            $query->where('satuan_kerja_id', $user->getSatkerId());
        } elseif ($user->hasRole('admin') && !$isGlobalSearch) {
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('satuan_kerja_id', (array) $request->satuan_kerja_id);
            }
        }

        // 2. Filter Lintas Satker (NIK / No HP)
        if ($isGlobalSearch) {
            $query->whereHas('tersangka', function ($q) use ($request) {
                if ($request->filled('filter_nik')) {
                    $q->whereIn('nik', (array) $request->filter_nik);
                }
                if ($request->filled('filter_no_telepon')) {
                    $q->whereIn('no_telepon', (array) $request->filter_no_telepon);
                }
            });
        }

        // 3. Filter Barang Bukti
        if (
            $request->filled('kategori_bb') || 
            $request->filled('narkotika_ids') || 
            $request->filled('search_non_narkotika')
        ) {
            $query->whereHas('barangBukti', function ($q) use ($request) {
                if ($request->filled('kategori_bb')) {
                    $q->whereIn('kategori', (array) $request->kategori_bb);
                }

                $hasNarkotika = $request->filled('narkotika_ids');
                $hasNonNarkotika = $request->filled('search_non_narkotika');

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

        // 4. Filter Waktu
        if ($request->filled('bulan')) {
            $query->whereIn(DB::raw('MONTH(tanggal_pelaksanaan)'), (array) $request->bulan);
        }
        $years = $request->filled('tahun') ? (array) $request->tahun : [date('Y')];
        $query->whereIn(DB::raw('YEAR(tanggal_pelaksanaan)'), $years);

        // 5. Filter Pencarian Umum
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('no_register', 'LIKE', "%{$s}%")
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
        $years = BerantasTat::selectRaw('YEAR(tanggal_pelaksanaan) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');
            
        $currentYear = (int) date('Y');
        if (!$years->contains($currentYear)) {
            $years->push($currentYear)->sortDesc()->values();
        }
        
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja')->get();
        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika')->get();

        $query = $this->getFilteredQuery($request);
        $perPage = $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }
        
        $data = $query->paginate($perPage)->withQueryString();

        // Helper Agregasi Parent Filter
        $applyParentFilters = function($q) use ($request) {
            $isGlobalSearch = $request->filled('filter_nik') || 
                              $request->filled('filter_no_telepon');

            if (!Auth::user()->hasRole('admin') && !$isGlobalSearch) {
                $q->where('parent.satuan_kerja_id', Auth::user()->getSatkerId());
            } elseif ($request->filled('satuan_kerja_id')) {
                $q->whereIn('parent.satuan_kerja_id', (array) $request->satuan_kerja_id);
            }

            if ($request->filled('bulan')) {
                $q->whereIn(DB::raw('MONTH(parent.tanggal_pelaksanaan)'), (array) $request->bulan);
            }
            
            $yearFilter = $request->filled('tahun') ? (array) $request->tahun : [date('Y')];
            $q->whereIn(DB::raw('YEAR(parent.tanggal_pelaksanaan)'), $yearFilter);

            // Subquery Filter Tersangka
            if ($isGlobalSearch) {
                $q->whereExists(function ($subQ) use ($request) {
                    $subQ->select(DB::raw(1))
                         ->from('berantas_tat_tersangka as tsk_filter')
                         ->whereColumn('tsk_filter.berantas_tat_id', 'parent.id');
                         
                    if ($request->filled('filter_nik')) {
                        $subQ->whereIn('tsk_filter.nik', (array) $request->filter_nik);
                    }
                    if ($request->filled('filter_no_telepon')) {
                        $subQ->whereIn('tsk_filter.no_telepon', (array) $request->filter_no_telepon);
                    }
                });
            }

            // Subquery Filter Barang Bukti
            if (
                $request->filled('kategori_bb') || 
                $request->filled('narkotika_ids') || 
                $request->filled('search_non_narkotika')
            ) {
                $q->whereExists(function ($subQ) use ($request) {
                    $subQ->select(DB::raw(1))
                         ->from('berantas_tat_barang_bukti as bb_filter')
                         ->whereColumn('bb_filter.berantas_tat_id', 'parent.id')
                         ->where(function($bbQ) use ($request) {
                             if ($request->filled('kategori_bb')) {
                                 $bbQ->whereIn('bb_filter.kategori', (array) $request->kategori_bb);
                             }
                             
                             $hasNarkotika = $request->filled('narkotika_ids');
                             $hasNonNarkotika = $request->filled('search_non_narkotika');

                             if ($hasNarkotika || $hasNonNarkotika) {
                                 $bbQ->where(function ($typeQ) use ($request, $hasNarkotika, $hasNonNarkotika) {
                                     if ($hasNarkotika) {
                                         $typeQ->orWhere(function ($nQ) use ($request) {
                                             $nQ->where('bb_filter.kategori', 'Narkotika')
                                                ->whereIn('bb_filter.narkotika_id', (array) $request->narkotika_ids);
                                         });
                                     }
                                     if ($hasNonNarkotika) {
                                         $typeQ->orWhere(function ($nnQ) use ($request) {
                                             $nnQ->where('bb_filter.kategori', 'Non-Narkotika')
                                                ->whereIn('bb_filter.nama_barang_non_narkotika', (array) $request->search_non_narkotika);
                                         });
                                     }
                                 });
                             }
                         });
                });
            }

            // Filter Pencarian Umum di Parent
            if ($request->filled('search')) {
                $s = $request->search;
                $q->where(function($sq) use ($s) {
                    $sq->where('parent.no_register', 'LIKE', "%{$s}%")
                       ->orWhere('parent.instansi_pengirim', 'LIKE', "%{$s}%")
                       ->orWhere('parent.pasal_disangkakan', 'LIKE', "%{$s}%")
                       ->orWhereExists(function($subQ) use ($s) {
                           $subQ->select(DB::raw(1))
                                ->from('berantas_tat_tersangka as tsk_search')
                                ->whereColumn('tsk_search.berantas_tat_id', 'parent.id')
                                ->where('tsk_search.nama_tersangka', 'LIKE', "%{$s}%");
                       });
                });
            }
        };

        // A. HITUNG TOTAL TERSANGKA
        $statsTersangka = BerantasTatTersangka::query()
            ->join('berantas_tat as parent', 'berantas_tat_tersangka.berantas_tat_id', '=', 'parent.id');
        
        $applyParentFilters($statsTersangka);
        $totalTersangka = $statsTersangka->count();

        // B. HITUNG TOTAL BARANG BUKTI
        $statsBB = BerantasTatBarangBukti::query()
            ->join('berantas_tat as parent', 'berantas_tat_barang_bukti.berantas_tat_id', '=', 'parent.id');
            
        $applyParentFilters($statsBB);

        if ($request->filled('kategori_bb')) {
            $statsBB->whereIn('berantas_tat_barang_bukti.kategori', (array) $request->kategori_bb);
        }
        if ($request->filled('narkotika_ids')) {
            $statsBB->whereIn('berantas_tat_barang_bukti.narkotika_id', (array) $request->narkotika_ids);
        }
        if ($request->filled('search_non_narkotika')) {
            $statsBB->whereIn('berantas_tat_barang_bukti.nama_barang_non_narkotika', (array) $request->search_non_narkotika);
        }

        $resultBB = $statsBB->where('berantas_tat_barang_bukti.kategori', 'Narkotika')
            ->selectRaw('COUNT(*) as total_items')
            ->selectRaw("SUM(CASE 
                WHEN berantas_tat_barang_bukti.satuan_narkotika = 'Kg' THEN berantas_tat_barang_bukti.kuantitas * 1000 
                WHEN berantas_tat_barang_bukti.satuan_narkotika = 'Ton' THEN berantas_tat_barang_bukti.kuantitas * 1000000 
                ELSE berantas_tat_barang_bukti.kuantitas 
            END) as total_berat")
            ->first();

        $totalBBNarkotika = $resultBB->total_items ?? 0;
        $totalBeratGram   = $resultBB->total_berat ?? 0;
        $totalKasus       = $data->total();

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
        
        return view('berantas.tat.create', compact('masterNarkotika', 'satuanKerjas'));
    }

    public function store(Request $request, DokumenService $dokumenService)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'no_register'              => 'required|unique:berantas_tat',
            'tanggal_pelaksanaan'      => 'required|date',
            
            'tersangka'                => 'required|array|min:1',
            'tersangka.*.nama'         => 'required|string',
            'tersangka.*.nik'          => 'nullable|numeric',
            'tersangka.*.jk'           => 'required|in:Laki-laki,Perempuan',
            'tersangka.*.usia'         => 'required|numeric|min:0',
            'tersangka.*.pendidikan'   => ['required', Rule::in(Pendidikan::ALL)],
            'tersangka.*.pekerjaan'    => ['required', 'string'],
            'tersangka.*.no_telepon'   => 'nullable|string',

            'barang_bukti'             => 'required|array|min:1',
            'barang_bukti.*.kategori'  => 'required|in:Narkotika,Non-Narkotika',
            'barang_bukti.*.jumlah'    => 'required|numeric|min:0',

            'tim_hukum'                => 'nullable|array',
            'tim_hukum.*.nama'         => 'required_with:tim_hukum|string',
            'tim_hukum.*.instansi'     => 'required_with:tim_hukum|string',
            
            'tim_medis'                => 'nullable|array',
            'tim_medis.*.nama'         => 'required_with:tim_medis|string',

            'pasal_disangkakan'        => 'nullable|string',
            'biaya'                    => 'nullable|numeric|min:0',
            
            'dokumentasi'              => 'nullable|array', 
            'lampiran'                 => 'nullable|array',
            'dokumentasi_links'        => 'nullable|array',
            'lampiran_links'           => 'nullable|array',
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->has('barang_bukti')) {
                foreach ($request->barang_bukti as $index => $bb) {
                    if ($bb['kategori'] === 'Narkotika') {
                        if (empty($bb['narkotika_id'])) {
                            $validator->errors()->add("barang_bukti.$index.narkotika_id", "Pilih Narkotika.");
                        }
                        if (empty($bb['satuan_narkotika'])) {
                            $validator->errors()->add("barang_bukti.$index.satuan_narkotika", "Pilih Satuan.");
                        }
                    }
                    if ($bb['kategori'] === 'Non-Narkotika') {
                        if (empty($bb['nama_barang_bukti'])) {
                            $validator->errors()->add("barang_bukti.$index.nama_barang_bukti", "Nama wajib diisi.");
                        }
                        if (empty($bb['satuan_non_narkotika'])) {
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
                'tersangka', 
                'barang_bukti', 
                'dokumentasi', 
                'lampiran', 
                'dokumentasi_links', 
                'lampiran_links'
            ]);
            
            $data['satuan_kerja_id'] = $user->isAdmin() 
                ? $request->satuan_kerja_id 
                : $user->getSatkerId();
            
            $tat = BerantasTat::create($data);

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

            foreach ($request->barang_bukti as $bb) {
                $isNarkotika = $bb['kategori'] === 'Narkotika';
                
                $tat->barangBukti()->create([
                    'kategori'                  => $bb['kategori'],
                    'narkotika_id'              => $isNarkotika ? $bb['narkotika_id'] : null,
                    'nama_barang_non_narkotika' => !$isNarkotika ? $bb['nama_barang_bukti'] : null,
                    'kuantitas'                 => $bb['jumlah'] ?? 0,
                    'satuan_narkotika'          => $isNarkotika ? $bb['satuan_narkotika'] : null,
                    'satuan_non_narkotika'      => !$isNarkotika ? $bb['satuan_non_narkotika'] : null,
                ]);
            }

            // Simpan Dokumen / Link
            if ($request->filled('dokumentasi')) {
                $dokumenService->moveToPermanent(
                    $request->input('dokumentasi'), $tat, 'dokumentasi', $uploadedPaths
                );
            }
            if ($request->filled('lampiran')) {
                $dokumenService->moveToPermanent(
                    $request->input('lampiran'), $tat, 'lampiran', $uploadedPaths
                );
            }
            if ($request->filled('dokumentasi_links')) {
                $dokumenService->saveLinks(
                    $request->input('dokumentasi_links'), $tat, 'dokumentasi'
                );
            }
            if ($request->filled('lampiran_links')) {
                $dokumenService->saveLinks(
                    $request->input('lampiran_links'), $tat, 'lampiran'
                );
            }

            DB::commit();
            return redirect()
                ->route('berantas.tat.index')
                ->with('success', 'Data TAT berhasil disimpan.');
            
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
        
        return view('berantas.tat.edit', compact('tat', 'masterNarkotika', 'satuanKerjas'));
    }

    public function update(Request $request, DokumenService $dokumenService, $id)
    {
        $tat = BerantasTat::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'no_register'              => 'required|unique:berantas_tat,no_register,' . $id,
            
            'tersangka'                => 'required|array|min:1',
            'tersangka.*.nama'         => 'required|string',
            'tersangka.*.nik'          => 'nullable|numeric',
            'tersangka.*.pendidikan'   => ['required', Rule::in(Pendidikan::ALL)],
            'tersangka.*.pekerjaan'    => ['required', 'string'],
            
            'barang_bukti'             => 'required|array|min:1',
            'barang_bukti.*.jumlah'    => 'required|numeric|min:0',
            
            'tim_hukum'                => 'nullable|array',
            'tim_hukum.*.nama'         => 'required_with:tim_hukum|string',
            
            'tim_medis'                => 'nullable|array',
            'tim_medis.*.nama'         => 'required_with:tim_medis|string',
            
            'delete_files'             => 'nullable|array', 
            'dokumentasi'              => 'nullable|array',
            'lampiran'                 => 'nullable|array',
            'dokumentasi_links'        => 'nullable|array',
            'lampiran_links'           => 'nullable|array',
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->has('barang_bukti')) {
                foreach ($request->barang_bukti as $index => $bb) {
                    if ($bb['kategori'] === 'Narkotika') {
                        if (empty($bb['narkotika_id'])) {
                            $validator->errors()->add("barang_bukti.$index.narkotika_id", "Pilih Narkotika.");
                        }
                        if (empty($bb['satuan_narkotika'])) {
                            $validator->errors()->add("barang_bukti.$index.satuan_narkotika", "Pilih Satuan.");
                        }
                    }
                    if ($bb['kategori'] === 'Non-Narkotika') {
                        if (empty($bb['nama_barang_bukti'])) {
                            $validator->errors()->add("barang_bukti.$index.nama_barang_bukti", "Nama wajib diisi.");
                        }
                        if (empty($bb['satuan_non_narkotika'])) {
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
                'tersangka', 
                'barang_bukti', 
                'delete_files', 
                'dokumentasi', 
                'lampiran', 
                'dokumentasi_links', 
                'lampiran_links'
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
                    'kategori'                  => $bb['kategori'],
                    'narkotika_id'              => $isNarkotika ? $bb['narkotika_id'] : null,
                    'nama_barang_non_narkotika' => !$isNarkotika ? $bb['nama_barang_bukti'] : null,
                    'kuantitas'                 => $bb['jumlah'] ?? 0,
                    'satuan_narkotika'          => $isNarkotika ? $bb['satuan_narkotika'] : null,
                    'satuan_non_narkotika'      => !$isNarkotika ? $bb['satuan_non_narkotika'] : null,
                ]);
            }

            // Hapus Dokumen Lama
            if ($request->has('delete_files')) {
                $filesToRemove = Dokumen::whereIn('id', $request->delete_files)->get();
                foreach ($filesToRemove as $file) {
                    if (!$file->is_link) {
                        $filesToDelete[] = $file->path_file;
                    }
                    $file->delete();
                }
            }

            // Simpan Dokumen / Link Baru
            if ($request->filled('dokumentasi')) {
                $dokumenService->moveToPermanent(
                    $request->input('dokumentasi'), $tat, 'dokumentasi', $newFilesMoved
                );
            }
            if ($request->filled('lampiran')) {
                $dokumenService->moveToPermanent(
                    $request->input('lampiran'), $tat, 'lampiran', $newFilesMoved
                );
            }
            if ($request->filled('dokumentasi_links')) {
                $dokumenService->saveLinks(
                    $request->input('dokumentasi_links'), $tat, 'dokumentasi'
                );
            }
            if ($request->filled('lampiran_links')) {
                $dokumenService->saveLinks(
                    $request->input('lampiran_links'), $tat, 'lampiran'
                );
            }

            DB::commit();
            foreach ($filesToDelete ?? [] as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            return redirect()
                ->route('berantas.tat.index')
                ->with('success', 'Data TAT diperbarui.');

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
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }

        foreach ($filesToDelete as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        return redirect()->back()->with('success', 'Data dan file berhasil dihapus.');
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