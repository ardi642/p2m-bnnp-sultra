<?php

namespace App\Http\Controllers\Berantas;

use App\Http\Controllers\Controller;
use App\Models\BerantasRegisterBarangBukti;
use App\Models\BerantasNarkotika;
use App\Models\SatuanKerja;
use App\Models\TemporaryFile;
use App\Models\DokumentasiKegiatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RegisterBarangBuktiExport;
use App\Models\Dokumen;
use App\Services\DokumenService;
use Illuminate\Support\Facades\Log;

class RegisterBarangBuktiController extends Controller
{
    /**
     * Membangun Query dengan Filter yang Kompleks
     */
    private function getQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Scope Filter Item (Re-usable untuk whereHas)
        $itemFilterScope = function($query) use ($request) {
            // 1. Filter Berdasarkan Kategori & Nama Barang/Narkotika
            if ($request->filled('kategori_bb')) {
                $categories = (array)$request->kategori_bb;
                $query->where(function($mainQ) use ($categories, $request) {
                    
                    // Blok Narkotika
                    if (in_array('Narkotika', $categories)) {
                        $mainQ->orWhere(function($narkoQ) use ($request) {
                            $narkoQ->where('kategori', 'Narkotika');
                            if ($request->filled('narkotika_ids')) {
                                $narkoQ->whereIn('narkotika_id', (array)$request->narkotika_ids);
                            }
                        });
                    }

                    // Blok Non-Narkotika
                    if (in_array('Non-Narkotika', $categories)) {
                        $mainQ->orWhere(function($nonQ) use ($request) {
                            $nonQ->where('kategori', 'Non-Narkotika');
                            if ($request->filled('search_non_narkotika')) {
                                $nonQ->where(function($textQ) use ($request) {
                                    foreach ((array)$request->search_non_narkotika as $keyword) {
                                        $textQ->orWhere('nama_barang_non_narkotika', 'LIKE', "%{$keyword}%");
                                    }
                                });
                            }
                        });
                    }
                });
            }

            // 2. Filter Berdasarkan Sumber Perolehan (Sekarang ada di Item)
            if ($request->filled('sumber_perolehan')) {
                $query->whereIn('sumber_perolehan', (array)$request->sumber_perolehan);
            }
        };

        // Query Utama
        $query = BerantasRegisterBarangBukti::with([
            'satuanKerja', 
            'dokumen',
            'items' => $itemFilterScope, // Eager Load dengan filter (agar yang tampil di tabel hanya yg dicari)
            'items.narkotika'
        ]);

        // Terapkan Filter Item ke Parent (Hanya tampilkan Register yang punya Item sesuai filter)
        if ($request->filled('kategori_bb') || $request->filled('sumber_perolehan') || $request->filled('narkotika_ids') || $request->filled('search_non_narkotika')) {
            $query->whereHas('items', $itemFilterScope);
        }

        // Filter Satuan Kerja (Admin vs Operator)
        if (!$user->isAdmin()) {
            $query->where('satuan_kerja_id', $user->getSatkerId());
        } elseif ($request->filled('satuan_kerja_id')) {
            $query->whereIn('satuan_kerja_id', (array)$request->satuan_kerja_id);
        }

        // Filter Waktu
        if ($request->filled('bulan')) {
            $query->whereIn(DB::raw('MONTH(tanggal_perolehan)'), (array)$request->bulan);
        }
        
        $years = $request->filled('tahun') ? (array)$request->tahun : [date('Y')];
        $query->whereIn(DB::raw('YEAR(tanggal_perolehan)'), $years);

        // Pencarian Global (Search Bar)
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('lokasi_perolehan', 'LIKE', "%{$s}%")
                  ->orWhereHas('items', function($iq) use ($s) {
                      $iq->where('nama_barang_non_narkotika', 'LIKE', "%{$s}%")
                         ->orWhereHas('narkotika', fn($nq) => $nq->where('nama_narkotika', 'LIKE', "%{$s}%"));
                  });
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Custom Sort untuk Sumber Perolehan (karena sekarang di child, kita skip atau sorting based on first item)
        // Disini kita defaultkan ke created_at jika sort by sumber dipilih (karena sumber sudah menjadi one-to-many)
        if($sortBy === 'sumber_perolehan') {
            $sortBy = 'created_at'; 
        }

        $query->orderBy($sortBy, $sortOrder);

        return $query;
    }

    public function index(Request $request)
    {
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja')->get();
        // Ambil tahun dari model parent
        $years = BerantasRegisterBarangBukti::selectRaw('YEAR(tanggal_perolehan) as year')
            ->distinct()->orderBy('year', 'desc')->pluck('year');
        
        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika')->get();

        // 1. Query untuk Tabel (Pagination) - Tetap pakai getQuery (Aman karena ada limit page)
        $query = $this->getQuery($request);
        $data = $query->paginate($request->input('per_page', 10))->withQueryString();

        // 2. Query untuk Agregasi Statistik (OPTIMALISASI JOIN)
        // Mulai dari Tabel Item -> Join ke Parent
        $statsQuery = \App\Models\BerantasRegisterBarangBuktiItem::query()
            ->join('berantas_register_barang_bukti as parent', 'berantas_register_barang_bukti_items.register_barang_bukti_id', '=', 'parent.id');

        // --- A. Terapkan Filter PARENT pada StatsQuery (pakai alias 'parent') ---
        
        // Filter Satuan Kerja
        if (!Auth::user()->isAdmin()) {
            $statsQuery->where('parent.satuan_kerja_id', Auth::user()->getSatkerId());
        } elseif ($request->filled('satuan_kerja_id')) {
            $statsQuery->whereIn('parent.satuan_kerja_id', (array)$request->satuan_kerja_id);
        }

        // Filter Waktu
        if ($request->filled('bulan')) {
            $statsQuery->whereIn(DB::raw('MONTH(parent.tanggal_perolehan)'), (array)$request->bulan);
        }
        $filterYears = $request->filled('tahun') ? (array)$request->tahun : [date('Y')];
        $statsQuery->whereIn(DB::raw('YEAR(parent.tanggal_perolehan)'), $filterYears);

        // Filter Search Global (Logic gabungan Parent & Child)
        if ($request->filled('search')) {
            $s = $request->search;
            $statsQuery->where(function($q) use ($s) {
                $q->where('parent.lokasi_perolehan', 'LIKE', "%{$s}%") // Kolom Parent
                  ->orWhere('berantas_register_barang_bukti_items.nama_barang_non_narkotika', 'LIKE', "%{$s}%") // Kolom Child
                  ->orWhereHas('narkotika', fn($nq) => $nq->where('nama_narkotika', 'LIKE', "%{$s}%")); // Relasi Child
            });
        }

        // --- B. Terapkan Filter ITEM pada StatsQuery ---
        
        if ($request->filled('kategori_bb')) {
            $categories = (array)$request->kategori_bb;
            $statsQuery->where(function($q) use ($categories, $request) {
                if (in_array('Narkotika', $categories)) {
                    $q->orWhere(function($narkoQ) use ($request) {
                        $narkoQ->where('kategori', 'Narkotika');
                        if ($request->filled('narkotika_ids')) {
                            $narkoQ->whereIn('narkotika_id', (array)$request->narkotika_ids);
                        }
                    });
                }
                if (in_array('Non-Narkotika', $categories)) {
                    $q->orWhere(function($nonQ) use ($request) {
                        $nonQ->where('kategori', 'Non-Narkotika');
                        if ($request->filled('search_non_narkotika')) {
                            $nonQ->where(function($textQ) use ($request) {
                                foreach ((array)$request->search_non_narkotika as $keyword) {
                                    $textQ->orWhere('nama_barang_non_narkotika', 'LIKE', "%{$keyword}%");
                                }
                            });
                        }
                    });
                }
            });
        }

        if ($request->filled('sumber_perolehan')) {
            $statsQuery->whereIn('sumber_perolehan', (array)$request->sumber_perolehan);
        }

        // --- C. Hitung Statistik (Query Cepat) ---

        // 1. Total Barang Bukti Narkotika
        // Gunakan clone agar query dasar tidak berubah
        $totalBBNarkotika = (clone $statsQuery)->where('kategori', 'Narkotika')->count();

        // 2. Total Berat Konversi ke Gram
        $totalBeratGram = (clone $statsQuery)
            ->where('kategori', 'Narkotika')
            ->selectRaw("SUM(
                CASE 
                    WHEN satuan_narkotika = 'Kg' THEN kuantitas * 1000 
                    WHEN satuan_narkotika = 'Ton' THEN kuantitas * 1000000 
                    ELSE kuantitas 
                END
            ) as total_gram")->value('total_gram') ?? 0;

        // 3. Statistik Sumber Perolehan
        $sumberStats = (clone $statsQuery)
            ->where('kategori', 'Narkotika')
            ->select('sumber_perolehan', DB::raw('count(*) as total'))
            ->groupBy('sumber_perolehan') // Group by langsung di database, sangat cepat
            ->pluck('total', 'sumber_perolehan');
            
        $totalItemsNarkotika = $sumberStats->sum();
        $totalTangkap = $sumberStats['Hasil Tangkap'] ?? 0;
        $totalTemuan = $sumberStats['Temuan'] ?? 0;
        
        $persenTangkap = $totalItemsNarkotika > 0 ? round(($totalTangkap / $totalItemsNarkotika) * 100, 1) : 0;
        $persenTemuan = $totalItemsNarkotika > 0 ? round(($totalTemuan / $totalItemsNarkotika) * 100, 1) : 0;

        // Total Register (Header) diambil dari pagination metadata saja agar hemat query
        $totalRegister = $data->total(); 

        return view('berantas.register-barang-bukti.index', compact(
            'data', 'satuanKerjas', 'years', 'masterNarkotika',
            'totalRegister', 'totalBBNarkotika', 'totalBeratGram', 
            'totalTangkap', 'totalTemuan', 'persenTangkap', 'persenTemuan'
        ));
    }

    public function create()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika')->get();
        $satuanKerjas = $user->isAdmin() ? SatuanKerja::orderBy('satuan_kerja')->get() : [];
        
        return view('berantas.register-barang-bukti.create', compact('masterNarkotika', 'satuanKerjas'));
    }

    public function store(Request $request, DokumenService $dokumenService)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'tanggal_perolehan' => 'required|date',
            'lokasi_perolehan'  => 'nullable|string',
            'latitude'          => 'required|numeric',
            'longitude'         => 'required|numeric',
            
            // Validasi Items
            'items'                     => 'required|array|min:1',
            'items.*.kategori'          => 'required|in:Narkotika,Non-Narkotika',
            'items.*.modus_pengiriman'  => 'nullable|string',
            'items.*.sumber_perolehan'  => 'required|in:Hasil Tangkap,Temuan',
            'items.*.jumlah'            => 'required|numeric|min:0',
            
            // Validasi Kondisional Narkotika
            'items.*.satuan_narkotika' => [
                'nullable', 
                Rule::requiredIf(fn() => request('items.*.kategori') === 'Narkotika'),
                Rule::in(['Gram', 'Kg', 'Ton'])
            ],
            // Validasi Kondisional Non-Narkotika
            'items.*.satuan_non_narkotika' => 'nullable|required_if:items.*.kategori,Non-Narkotika|string',
            
            'dokumentasi'       => 'nullable|array',
            'lampiran'          => 'nullable|array',
            'dokumentasi_links' => 'nullable|array',
            'lampiran_links'    => 'nullable|array',
        ]);

        if ($user->isAdmin()) {
            $validator->addRules(['satuan_kerja_id' => 'required|exists:satuan_kerja,id']);
        }

        $validator->after(function ($validator) use ($request) {
            if($request->has('items')) {
                foreach ($request->items as $i => $item) {
                    if ($item['kategori'] == 'Narkotika' && empty($item['narkotika_id'])) {
                        $validator->errors()->add("items.$i.narkotika_id", 'Jenis Narkotika wajib dipilih.');
                    }
                    if ($item['kategori'] == 'Non-Narkotika' && empty($item['nama_barang_non_narkotika'])) {
                        $validator->errors()->add("items.$i.nama_barang_non_narkotika", 'Nama Barang wajib diisi.');
                    }
                }
            }
        });

        if ($validator->fails()) return back()->withErrors($validator)->withInput();

        $uploadedPaths = []; 

        DB::beginTransaction();

        try {
            // 1. Simpan Header Register
            $register = BerantasRegisterBarangBukti::create([
                'satuan_kerja_id'   => $user->isAdmin() ? $request->satuan_kerja_id : $user->getSatkerId(),
                'tanggal_perolehan' => $request->tanggal_perolehan,
                'lokasi_perolehan'  => $request->lokasi_perolehan,
                'latitude'          => $request->latitude,
                'longitude'         => $request->longitude,
            ]);

            // 2. Simpan Item Barang Bukti
            foreach ($request->items as $item) {
                $register->items()->create([
                    'kategori'                  => $item['kategori'],
                    'sumber_perolehan'          => $item['sumber_perolehan'],
                    'modus_pengiriman'          => $item['modus_pengiriman'] ?? null,
                    'narkotika_id'              => $item['kategori'] == 'Narkotika' ? $item['narkotika_id'] : null,
                    'nama_barang_non_narkotika' => $item['kategori'] == 'Non-Narkotika' ? $item['nama_barang_non_narkotika'] : null,
                    'kuantitas'                 => $item['jumlah'],
                    'satuan_narkotika'          => $item['kategori'] == 'Narkotika' ? $item['satuan_narkotika'] : null,
                    'satuan_non_narkotika'      => $item['kategori'] == 'Non-Narkotika' ? $item['satuan_non_narkotika'] : null,
                ]);
            }

            if ($request->filled('dokumentasi')) $dokumenService->moveToPermanent($request->input('dokumentasi'), $register, 'dokumentasi', $uploadedPaths);
            if ($request->filled('lampiran')) $dokumenService->moveToPermanent($request->input('lampiran'), $register, 'lampiran', $uploadedPaths);
            if ($request->filled('dokumentasi_links')) $dokumenService->saveLinks($request->input('dokumentasi_links'), $register, 'dokumentasi');
            if ($request->filled('lampiran_links')) $dokumenService->saveLinks($request->input('lampiran_links'), $register, 'lampiran');

            DB::commit();
            return redirect()->route('berantas.register-barang-bukti.index')->with('success', 'Data berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($uploadedPaths as $path) Storage::disk('public')->delete($path);
            Log::error('Store Kasus Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal: ' . $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $register = BerantasRegisterBarangBukti::with(['items', 'dokumen'])->findOrFail($id);
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if ($user->hasRole(['operator_satker', 'operator_berantas']) && $register->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403);
        }
        
        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika')->get();
        $satuanKerjas = $user->isAdmin() ? SatuanKerja::orderBy('satuan_kerja')->get() : [];

        return view('berantas.register-barang-bukti.edit', compact('register', 'masterNarkotika', 'satuanKerjas'));
    }

    public function update(Request $request, DokumenService $dokumenService, $id)
    {
        $register = BerantasRegisterBarangBukti::findOrFail($id);
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if ($user->hasRole(['operator_satker', 'operator_berantas']) && $register->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403);
        }
        
        $validator = Validator::make($request->all(), [
            'tanggal_perolehan' => 'required|date',
            'lokasi_perolehan'  => 'nullable|string',
            'latitude'          => 'required|numeric',
            'longitude'         => 'required|numeric',
            
            'items'                     => 'required|array|min:1',
            'items.*.kategori'          => 'required|in:Narkotika,Non-Narkotika',
            'items.*.modus_pengiriman'  => 'nullable|string',
            'items.*.sumber_perolehan'  => 'required|in:Hasil Tangkap,Temuan',
            'items.*.jumlah'            => 'required|numeric|min:0',
            
            'items.*.satuan_narkotika' => [
                'nullable', 
                Rule::requiredIf(fn() => request('items.*.kategori') === 'Narkotika'),
                Rule::in(['Gram', 'Kg', 'Ton'])
            ],
            'items.*.satuan_non_narkotika' => 'nullable|required_if:items.*.kategori,Non-Narkotika|string',
            
            'delete_files' => 'nullable|array', 
            'dokumentasi' => 'nullable|array',
            'lampiran' => 'nullable|array',
            'dokumentasi_links' => 'nullable|array',
            'lampiran_links' => 'nullable|array',

        ]);

        if ($user->isAdmin()) {
            $validator->addRules(['satuan_kerja_id' => 'required|exists:satuan_kerja,id']);
        }

        $validator->after(function ($validator) use ($request) {
            if($request->has('items')) {
                foreach ($request->items as $i => $item) {
                    if ($item['kategori'] == 'Narkotika' && empty($item['narkotika_id'])) {
                        $validator->errors()->add("items.$i.narkotika_id", 'Pilih Narkotika.');
                    }
                    if ($item['kategori'] == 'Non-Narkotika' && empty($item['nama_barang_non_narkotika'])) {
                        $validator->errors()->add("items.$i.nama_barang_non_narkotika", 'Isi Nama Barang.');
                    }
                }
            }
        });

        if ($validator->fails()) return back()->withErrors($validator)->withInput();

        $newFilesMoved = [];
        $filesToDelete = [];

        DB::beginTransaction();

        try {
            // 1. Update Header
            $updateData = [
                'tanggal_perolehan' => $request->tanggal_perolehan,
                'lokasi_perolehan'  => $request->lokasi_perolehan,
                'latitude'          => $request->latitude,
                'longitude'         => $request->longitude,
            ];
            if ($user->isAdmin()) {
                $updateData['satuan_kerja_id'] = $request->satuan_kerja_id;
            }
            $register->update($updateData);

            // 2. Replace Items (Hapus semua child lama, buat baru)
            $register->items()->delete();
            
            foreach ($request->items as $item) {
                $register->items()->create([
                    'kategori'                  => $item['kategori'],
                    'sumber_perolehan'          => $item['sumber_perolehan'],
                    'narkotika_id'              => $item['kategori'] == 'Narkotika' ? $item['narkotika_id'] : null,
                    'modus_pengiriman'          => $item['modus_pengiriman'] ?? null,
                    'nama_barang_non_narkotika' => $item['kategori'] == 'Non-Narkotika' ? $item['nama_barang_non_narkotika'] : null,
                    'kuantitas'                 => $item['jumlah'],
                    'satuan_narkotika'          => $item['kategori'] == 'Narkotika' ? $item['satuan_narkotika'] : null,
                    'satuan_non_narkotika'      => $item['kategori'] == 'Non-Narkotika' ? $item['satuan_non_narkotika'] : null,
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
                $dokumenService->moveToPermanent($request->input('dokumentasi'), $register, 'dokumentasi', $newFilesMoved);
            }
            if ($request->filled('lampiran')) {
                $dokumenService->moveToPermanent($request->input('lampiran'), $register, 'lampiran', $newFilesMoved);
            }
            if ($request->filled('dokumentasi_links')) {
                $dokumenService->saveLinks($request->input('dokumentasi_links'), $register, 'dokumentasi');
            }
            if ($request->filled('lampiran_links')) {
                $dokumenService->saveLinks($request->input('lampiran_links'), $register, 'lampiran');
            }

            DB::commit();
            foreach ($filesToDelete ?? [] as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            return redirect()->route('berantas.register-barang-bukti.index')->with('success', 'Data berhasil diperbarui.');

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
        $register = BerantasRegisterBarangBukti::with('dokumentasi')->findOrFail($id);
        $filesToDelete = [];
        foreach ($register->dokumen()->cursor() as $doc) {
            if (!$doc->is_link && !empty($doc->path_file)) {
                $filesToDelete[] = $doc->path_file;
            }
        }

        DB::beginTransaction();
        try {
            $register->delete(); 
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
        $query = $this->getQuery($request);
        return Excel::download(new RegisterBarangBuktiExport($query), 'Register_BB_'.date('d-m-Y').'.xlsx');
    }
}