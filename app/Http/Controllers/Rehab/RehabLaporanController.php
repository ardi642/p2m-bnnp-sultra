<?php

namespace App\Http\Controllers\Rehab;

use App\Http\Controllers\Controller;
use App\Models\RehabLaporan;
use App\Models\RehabTarget;
use App\Models\SatuanKerja;
use App\Models\TemporaryFile;
use App\Models\DokumentasiKegiatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use App\Exports\RehabLaporanExport;
use App\Models\Dokumen;
use App\Services\DokumenService;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class RehabLaporanController extends Controller
{
    // =========================================================================
    // QUERY HELPER (FILTER & SORTING)
    // =========================================================================
    private function getFilteredQuery(Request $request)
    {
        $user = Auth::user();
        $query = RehabLaporan::with(['satuanKerja', 'dokumen']);

        // 1. Filter Satuan Kerja
        if ($user->isAdmin()) {
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('satuan_kerja_id', (array)$request->satuan_kerja_id);
            }
        } else {
            $query->where('satuan_kerja_id', $user->getSatkerId());
        }

        // 2. Filter Triwulan (Mengesampingkan Bulan jika ada)
        if ($request->filled('triwulan')) {
            $months = [];
            foreach ((array)$request->triwulan as $tw) {
                if ($tw == 1) $months = array_merge($months, [1, 2, 3]);
                elseif ($tw == 2) $months = array_merge($months, [4, 5, 6]);
                elseif ($tw == 3) $months = array_merge($months, [7, 8, 9]);
                elseif ($tw == 4) $months = array_merge($months, [10, 11, 12]);
            }
            $query->whereIn(DB::raw('MONTH(tanggal)'), $months);
        } 
        // 3. Filter Bulan (Hanya jalan jika Triwulan kosong)
        elseif ($request->filled('bulan')) {
            $query->whereIn(DB::raw('MONTH(tanggal)'), (array)$request->bulan);
        }
        
        // 4. Filter Tahun (name="tahun[]")
        $activeYears = $request->filled('tahun') ? (array)$request->tahun : [date('Y')];
        $query->whereIn(DB::raw('YEAR(tanggal)'), $activeYears);

        // ---------------------------------------------------------------------
        // 5. LOGIKA SORTING
        // ---------------------------------------------------------------------
        $sortBy = $request->input('sort_by', 'tanggal');
        $rawSortOrder = $request->input('sort_order', 'desc');
        $sortOrder = in_array(strtolower($rawSortOrder), ['asc', 'desc']) ? strtolower($rawSortOrder) : 'desc';

        $allowSort = [
            'tanggal', 'satuan_kerja_id', 'realisasi_rawat_jalan', 
            'realisasi_pasca_rehab', 'realisasi_skhpn', 'created_at'
        ];

        if (in_array($sortBy, $allowSort)) {
            if ($sortBy === 'satuan_kerja_id') {
                $query->join('satuan_kerja', 'rehab_laporan.satuan_kerja_id', '=', 'satuan_kerja.id')
                      ->orderBy('satuan_kerja.satuan_kerja', $sortOrder)
                      ->select('rehab_laporan.*');
            } else {
                $query->orderBy('rehab_laporan.' . $sortBy, $sortOrder);
            }
        } else {
            $query->orderBy('rehab_laporan.tanggal', 'desc');
        }

        return $query;
    }

    // =========================================================================
    // MAIN INDEX
    // =========================================================================
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $satuanKerjas = $user->isAdmin() ? SatuanKerja::orderBy('satuan_kerja')->get() : [];
        $allYears = RehabLaporan::selectRaw('YEAR(tanggal) as year')
            ->distinct()->orderByDesc('year')->pluck('year');
        $currentYear = (int) date('Y');
        
        if (!$allYears->contains($currentYear)) {
            $allYears->push($currentYear)->sortDesc()->values();
        }

        $query = $this->getFilteredQuery($request);
        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : 10;
        $data = $query->paginate($perPage)->withQueryString();

        $breakdownYear = $request->input('breakdown_year', date('Y'));

        $satkerFilter = function($q) use ($user, $request) {
            if ($user->isAdmin() && $request->filled('satuan_kerja_id')) {
                $q->whereIn('satuan_kerja_id', (array)$request->satuan_kerja_id);
            } elseif (!$user->isAdmin()) {
                $q->where('satuan_kerja_id', $user->getSatkerId());
            }
        };

        $agregat = DB::table('rehab_laporan')
            ->selectRaw('SUM(realisasi_rawat_jalan) as total_rj, SUM(realisasi_pasca_rehab) as total_pasca, SUM(realisasi_skhpn) as total_skhpn')
            ->where(function($q) use ($satkerFilter) { $satkerFilter($q); })
            ->whereYear('tanggal', $breakdownYear)
            ->first();

        $targetSum = RehabTarget::where('tahun', $breakdownYear)
            ->where(function($q) use ($satkerFilter) { $satkerFilter($q); })
            ->selectRaw('SUM(target_rawat_jalan) as tr_rj, SUM(target_pasca_rehab) as tr_pasca, SUM(target_skhpn) as tr_skhpn')
            ->first();

        $stats = [
            'tahun_label' => $breakdownYear,
            'rj' => $this->calculateStats($targetSum->tr_rj ?? 0, $agregat->total_rj),
            'pasca' => $this->calculateStats($targetSum->tr_pasca ?? 0, $agregat->total_pasca),
            'skhpn' => $this->calculateStats($targetSum->tr_skhpn ?? 0, $agregat->total_skhpn),
        ];

        $monthlyRealization = RehabLaporan::whereYear('tanggal', $breakdownYear)
            ->where(function($q) use ($satkerFilter) { $satkerFilter($q); })
            ->selectRaw('MONTH(tanggal) as bulan, SUM(realisasi_rawat_jalan) as rj, SUM(realisasi_pasca_rehab) as pasca, SUM(realisasi_skhpn) as skhpn')
            ->groupBy('bulan')->get()->keyBy('bulan');

        $monthsData = [];
        $accum = ['rj' => 0, 'pasca' => 0, 'skhpn' => 0];

        for ($m = 1; $m <= 12; $m++) {
            $curr = $monthlyRealization[$m] ?? null;
            $r_rj = $curr->rj ?? 0;
            $r_pasca = $curr->pasca ?? 0;
            $r_skhpn = $curr->skhpn ?? 0;

            $accum['rj'] += $r_rj;
            $accum['pasca'] += $r_pasca;
            $accum['skhpn'] += $r_skhpn;

            $monthsData[] = [
                'bulan_nama' => Carbon::create()->month($m)->locale('id')->translatedFormat('F'),
                'rj' => $this->calculateMonthlyStats($r_rj, $accum['rj'], $targetSum->tr_rj ?? 0),
                'pasca' => $this->calculateMonthlyStats($r_pasca, $accum['pasca'], $targetSum->tr_pasca ?? 0),
                'skhpn' => $this->calculateMonthlyStats($r_skhpn, $accum['skhpn'], $targetSum->tr_skhpn ?? 0)
            ];
        }

        $targetsQuery = RehabTarget::with('satuanKerja');
        if (!$user->isAdmin()) {
            $targetsQuery->where('satuan_kerja_id', $user->getSatkerId());
        }
        $allTargets = $targetsQuery->orderBy('tahun', 'desc')->get();
        $allTargets->transform(function($target) {
            $target->has_laporan = RehabLaporan::where('satuan_kerja_id', $target->satuan_kerja_id)
                ->whereYear('tanggal', $target->tahun)->exists();
            return $target;
        });

        return view('rehab.laporan.index', compact(
            'data', 'satuanKerjas', 'allYears', 'stats', 'allTargets', 
            'monthsData', 'breakdownYear'
        ));
    }

    private function calculateStats($target, $realisasi) {
        $target = $target ?? 0; $realisasi = $realisasi ?? 0;
        $sisa = $target - $realisasi;
        return [
            'target' => $target, 
            'realisasi' => $realisasi, 
            'sisa' => max(0, $sisa),
            'persen' => ($target > 0) ? ($realisasi / $target) * 100 : 0
        ];
    }

    private function calculateMonthlyStats($real, $accum, $targetTotal) {
        $sisa = $targetTotal - $accum;
        return [
            'real' => $real, 
            'akum' => $accum, 
            'sisa' => max(0, $sisa),
            'persen' => ($targetTotal > 0) ? ($accum / $targetTotal) * 100 : 0
        ];
    }

    // =========================================================================
    // EXPORT EXCEL (DIUBAH UNTUK TRIWULAN)
    // =========================================================================
    public function export(Request $request, $kategori)
    {
        $user = Auth::user();

        $validCategories = ['rawat_jalan', 'pasca_rehab', 'skhpn'];
        if (!in_array($kategori, $validCategories)) {
            abort(404, 'Kategori laporan tidak valid.');
        }

        $colReal = match($kategori) { 
            'pasca_rehab' => 'realisasi_pasca_rehab', 
            'skhpn'       => 'realisasi_skhpn', 
            default       => 'realisasi_rawat_jalan' 
        };
        
        $colTarget = match($kategori) { 
            'pasca_rehab' => 'target_pasca_rehab', 
            'skhpn'       => 'target_skhpn', 
            default       => 'target_rawat_jalan' 
        };

        $years = $request->filled('tahun') ? array_map('intval', (array)$request->tahun) : [(int)date('Y')];
        sort($years);

        // Identifikasi Triwulan yang dipilih, jika kosong tampilkan semua (1,2,3,4)
        $selectedTw = $request->filled('triwulan') ? array_map('intval', (array)$request->triwulan) : [1, 2, 3, 4];
        sort($selectedTw);

        $query = RehabLaporan::whereIn(DB::raw('YEAR(tanggal)'), $years);

        if (!$user->isAdmin()) {
            $query->where('satuan_kerja_id', $user->getSatkerId());
        } elseif ($request->filled('satuan_kerja_id')) {
            $query->whereIn('satuan_kerja_id', (array)$request->satuan_kerja_id);
        }

        $laporanHarian = $query->get();

        if (!$user->isAdmin()) {
            $satkers = SatuanKerja::where('id', $user->getSatkerId())->get();
        } else {
            if ($request->filled('satuan_kerja_id')) {
                $satkers = SatuanKerja::whereIn('id', (array)$request->satuan_kerja_id)->orderBy('satuan_kerja')->get();
            } else {
                $satkerIds = $laporanHarian->pluck('satuan_kerja_id')->unique();
                $satkers = SatuanKerja::whereIn('id', $satkerIds)->orderBy('satuan_kerja')->get();
            }
        }

        $exportData = [];
        foreach ($satkers as $satker) {
            $row = ['satker_nama' => $satker->satuan_kerja, 'years' => []];
            
            foreach ($years as $year) {
                $targetRow = RehabTarget::where('satuan_kerja_id', $satker->id)
                    ->where('tahun', $year)
                    ->first();
                
                $targetTotal = $targetRow ? $targetRow->$colTarget : 0;
                
                $twData = [];
                $realisasiTotalTw = 0; // Menghitung total hanya dari TW yang dipilih

                foreach ($selectedTw as $tw) {
                    $months = match($tw) { 1=>[1,2,3], 2=>[4,5,6], 3=>[7,8,9], 4=>[10,11,12] };
                    
                    $realisasiTw = $laporanHarian->filter(function($item) use ($satker, $year, $months) {
                        return $item->satuan_kerja_id == $satker->id 
                            && $item->tanggal->format('Y') == $year
                            && in_array($item->tanggal->format('n'), $months);
                    })->sum($colReal);

                    $twData[$tw] = $realisasiTw;
                    $realisasiTotalTw += $realisasiTw;
                }

                $persen = ($targetTotal > 0) ? ($realisasiTotalTw / $targetTotal) * 100 : 0;

                $row['years'][$year] = [
                    'tw' => $twData, // Data per TW
                    'realisasi_total' => $realisasiTotalTw, // Total akumulasi dari TW yang terfilter
                    'target'    => $targetTotal, // Target selalu 1 Tahun Full
                    'persen'    => $persen
                ];
            }
            $exportData[] = $row;
        }

        $fileName = 'Laporan_Rehab_' . strtoupper($kategori) . '_' . date('Ymd_His') . '.xlsx';
        
        return Excel::download(new RehabLaporanExport($exportData, $years, $kategori), $fileName);
    }

    public function exportFull(Request $request)
    {
        $query = $this->getFilteredQuery($request);
        $query->without('dokumen');
        $fileName = 'Data_Lengkap_Laporan_Rehab_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new \App\Exports\RehabLaporanFullExport($query), $fileName);
    }

    // =========================================================================
    // CRUD
    // =========================================================================
    public function create() {
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja')->get();
        return view('rehab.laporan.create', compact('satuanKerjas'));
    }

    public function store(Request $request, DokumenService $dokumenService) {
        $user = Auth::user();
        $satkerId = $user->isAdmin() ? $request->satuan_kerja_id : $user->getSatkerId();
        
        if (RehabLaporan::where('satuan_kerja_id', $satkerId)->where('tanggal', $request->tanggal)->exists()) {
            throw ValidationException::withMessages(['tanggal' => ['Laporan tanggal ini sudah ada.']]);
        }

        $request->validate([
            'tanggal' => 'required|date',
            'realisasi_rawat_jalan' => 'required|integer|min:0',
            'realisasi_pasca_rehab' => 'required|integer|min:0',
            'realisasi_skhpn' => 'required|integer|min:0',

            'dokumentasi'          => 'nullable|array', 
            'lampiran'             => 'nullable|array',
            'dokumentasi_links'    => 'nullable|array',
            'dokumentasi_links.*.nama' => 'required_with:dokumentasi_links.*.url|nullable|string|max:255',
            'dokumentasi_links.*.url'  => 'required_with:dokumentasi_links.*.nama|nullable|url',
            'lampiran_links'       => 'nullable|array',
            'lampiran_links.*.nama' => 'required_with:lampiran_links.*.url|nullable|string|max:255',
            'lampiran_links.*.url'  => 'required_with:lampiran_links.*.nama|nullable|url',
        ]);

        $uploadedPaths = []; 

        DB::beginTransaction();
        try {
            $data = $request->except(['dokumentasi', 'lampiran', 'dokumentasi_links', 'lampiran_links']);
            $data['satuan_kerja_id'] = $satkerId;
            $laporan = RehabLaporan::create($data);

            if ($request->filled('dokumentasi')) {
                $dokumenService->moveToPermanent($request->input('dokumentasi'), $laporan, 'dokumentasi', $uploadedPaths);
            }
            if ($request->filled('lampiran')) {
                $dokumenService->moveToPermanent($request->input('lampiran'), $laporan, 'lampiran', $uploadedPaths);
            }
            if ($request->filled('dokumentasi_links')) {
                $dokumenService->saveLinks($request->input('dokumentasi_links'), $laporan, 'dokumentasi');
            }
            if ($request->filled('lampiran_links')) {
                $dokumenService->saveLinks($request->input('lampiran_links'), $laporan, 'lampiran');
            }

            DB::commit();
            return redirect()->route('rehab.laporan.index')->with('success', 'Laporan berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($uploadedPaths as $path) {
                Storage::disk(config('filesystems.default'))->delete($path);
            }
            Log::error('Gagal simpan: ' . $e->getMessage());
            abort(500, 'Server Error.');
        }
    }

    public function edit($id) {
        $laporan = RehabLaporan::with(['dokumen', 'satuanKerja'])->findOrFail($id);
        if (!Auth::user()->isAdmin() && $laporan->satuan_kerja_id !== Auth::user()->getSatkerId()) abort(403);
        return view('rehab.laporan.edit', compact('laporan'));
    }

    public function update(Request $request, DokumenService $dokumenService, $id) {
        $laporan = RehabLaporan::findOrFail($id);
        if (!Auth::user()->isAdmin() && $laporan->satuan_kerja_id !== Auth::user()->getSatkerId()) abort(403);
        $rules = [
            'realisasi_rawat_jalan' => 'required|integer|min:0',
            'realisasi_pasca_rehab' => 'required|integer|min:0',
            'realisasi_skhpn' => 'required|integer|min:0',

            'delete_files'         => 'nullable|array', 
            'dokumentasi'          => 'nullable|array',
            'lampiran'             => 'nullable|array',
            'dokumentasi_links'    => 'nullable|array',
            'dokumentasi_links.*.nama' => 'required_with:dokumentasi_links.*.url|nullable|string|max:255',
            'dokumentasi_links.*.url'  => 'required_with:dokumentasi_links.*.nama|nullable|url',
            'lampiran_links'       => 'nullable|array',
            'lampiran_links.*.nama' => 'required_with:lampiran_links.*.url|nullable|string|max:255',
            'lampiran_links.*.url'  => 'required_with:lampiran_links.*.nama|nullable|url',
        ];

        $validasi = $request->validate($rules);
        $newFilesMoved = [];
        $filesToDelete = [];

        DB::beginTransaction();
        try {
            $dataUpdate = collect($validasi)
                        ->except(['dokumentasi', 'lampiran', 'pegawai_nips', 'delete_files', 
                        'dokumentasi_links', 'lampiran_links'])
                        ->toArray();
            unset($dataUpdate['satuan_kerja_id']);
            $laporan->update($dataUpdate);
            
            if ($request->has('delete_files')) {
                $filesToRemove = Dokumen::whereIn('id', $request->delete_files)->get();
                foreach ($filesToRemove as $file) {
                    if (!$file->is_link) $filesToDelete[] = $file->path_file; 
                    $file->delete();
                }
            }

            if ($request->filled('dokumentasi')) {
                $dokumenService->moveToPermanent($request->input('dokumentasi'), $laporan, 'dokumentasi', $newFilesMoved);
            }
            if ($request->filled('lampiran')) {
                $dokumenService->moveToPermanent($request->input('lampiran'), $laporan, 'lampiran', $newFilesMoved);
            }
            if ($request->filled('dokumentasi_links')) {
                $dokumenService->saveLinks($request->input('dokumentasi_links'), $laporan, 'dokumentasi');
            }
            if ($request->filled('lampiran_links')) {
                $dokumenService->saveLinks($request->input('lampiran_links'), $laporan, 'lampiran');
            }

            DB::commit();

            foreach ($filesToDelete as $path) {
                if (Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
            }

            return redirect()->route('rehab.laporan.index')->with('success', 'Laporan diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($newFilesMoved as $path) {
                if (Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
            }
            Log::error('Update error: ' . $e->getMessage());
            return back()->with('error', 'update')->withInput();
        }
    }

    public function destroy($id) 
    {
        $laporan = RehabLaporan::with('dokumen')->findOrFail($id);
        $filesToDelete = [];
        
        foreach ($laporan->dokumen()->cursor() as $doc) {
            if (!$doc->is_link && !empty($doc->path_file)) {
                $filesToDelete[] = $doc->path_file;
            }
        }

        DB::beginTransaction();
        try {
            $laporan->delete(); 
            DB::commit(); 
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'destroy')->with('message', 'Gagal menghapus data: ' . $e->getMessage());
        }

        foreach ($filesToDelete as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        return redirect()->back()->with('success', 'Data dan file berhasil dihapus.');
    }

    // =========================================================================
    // TARGET TAHUNAN
    // =========================================================================
    public function storeTarget(Request $request) {
        $request->validate([
            'tahun' => 'required|integer|min:2020',
            'target_rawat_jalan' => 'required|integer|min:0',
            'target_pasca_rehab' => 'required|integer|min:0',
            'target_skhpn' => 'required|integer|min:0',
        ]);
        $satkerId = Auth::user()->isAdmin() ? $request->satuan_kerja_id : Auth::user()->getSatkerId();
        
        RehabTarget::updateOrCreate(
            ['satuan_kerja_id' => $satkerId, 'tahun' => $request->tahun],
            [
                'target_rawat_jalan' => $request->target_rawat_jalan, 
                'target_pasca_rehab' => $request->target_pasca_rehab, 
                'target_skhpn' => $request->target_skhpn
            ]
        );
        return back()->with('success', 'Target disimpan.');
    }

    public function destroyTarget($id) {
        $target = RehabTarget::findOrFail($id);
        if (!Auth::user()->isAdmin() && $target->satuan_kerja_id !== Auth::user()->getSatkerId()) abort(403);
        
        if (RehabLaporan::where('satuan_kerja_id', $target->satuan_kerja_id)->whereYear('tanggal', $target->tahun)->exists()) {
            return back()->with('error', 'Gagal hapus! Laporan sudah ada.');
        }
        $target->delete();
        return back()->with('success', 'Target dihapus.');
    }
}