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
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Exports\RehabLaporanExport;
use Maatwebsite\Excel\Facades\Excel;

class RehabLaporanController extends Controller
{
    // --- QUERY FILTER UTAMA (Digunakan oleh Index & Export) ---
    private function getFilteredQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $query = RehabLaporan::with(['satuanKerja', 'dokumentasi']);

        // 1. Filter Satuan Kerja
        if ($user->hasRole('admin')) {
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('rehab_laporan.satuan_kerja_id', (array)$request->satuan_kerja_id);
            }
        } else {
            // User biasa hanya melihat data satkernya sendiri
            $query->where('rehab_laporan.satuan_kerja_id', $user->getSatkerId());
        }

        // 2. Filter Bulan
        if ($request->filled('bulan')) {
            $query->whereIn(DB::raw('MONTH(rehab_laporan.tanggal)'), (array)$request->bulan);
        }
        
        // 3. Filter Tahun
        if ($request->filled('tahun')) {
            $query->whereIn(DB::raw('YEAR(rehab_laporan.tanggal)'), (array)$request->tahun);
        } elseif (!$request->has('tahun')) {
            // Default tahun ini jika tidak ada filter
            $query->whereYear('rehab_laporan.tanggal', date('Y'));
        }

        // 4. Sorting
        $sortBy = $request->input('sort_by', 'tanggal');
        $sortOrder = $request->input('sort_order', 'desc');

        if ($sortBy === 'satuan_kerja_id') {
            $query->join('satuan_kerja', 'rehab_laporan.satuan_kerja_id', '=', 'satuan_kerja.id')
                  ->orderBy('satuan_kerja.satuan_kerja', $sortOrder)
                  ->select('rehab_laporan.*');
        } else {
            $query->orderBy('rehab_laporan.' . $sortBy, $sortOrder);
        }

        return $query;
    }

    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        // =================================================================
        // 1. LOGIKA DROPDOWN TAHUN (Clean & Efisien)
        // =================================================================
        
        // A. Ambil tahun masa lalu dari DATA yang sudah ada (Laporan & Target)
        $yearsLaporan = RehabLaporan::selectRaw('YEAR(tanggal) as year')->distinct()->pluck('year')->toArray();
        $yearsTarget  = RehabTarget::select('tahun as year')->distinct()->pluck('year')->toArray();

        // B. Buat Range Tahun Masa Depan (Hanya Tahun Ini + 1 Tahun ke Depan)
        $futureYears = range(date('Y'), date('Y') + 1);

        // C. GABUNGKAN & URUTKAN
        $years = array_unique(array_merge($yearsLaporan, $yearsTarget, $futureYears));
        rsort($years);

        // =================================================================
        
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja')->get();
        $perPage = $request->input('per_page', 10);
        
        // 2. DATA LAPORAN HARIAN (Main Table)
        $query = $this->getFilteredQuery($request);
        $dataForSummary = $query->get(); 
        $data = $query->paginate($perPage)->withQueryString();

        // 3. LOGIC SUMMARY WIDGET (Rekapitulasi)
        $summary = [];
        if ($dataForSummary->isNotEmpty()) {
            $grouped = $dataForSummary->groupBy(fn($item) => $item->tanggal->format('Y-m'));

            foreach ($grouped as $key => $items) {
                list($tahun, $bulan) = explode('-', $key);
                
                $targetQ = RehabTarget::where('bulan', $bulan)->where('tahun', $tahun);
                
                if ($user->hasRole('admin') && $request->filled('satuan_kerja_id')) {
                    $targetQ->whereIn('satuan_kerja_id', (array)$request->satuan_kerja_id);
                } elseif (!$user->hasRole('admin')) {
                    $targetQ->where('satuan_kerja_id', $user->getSatkerId());
                }

                $targetRow = $targetQ->selectRaw('SUM(target_rawat_jalan) as t_rj, SUM(target_pasca_rehab) as t_pasca, SUM(target_skhpn) as t_skhpn')->first();

                $summary[] = [
                    'periode' => Carbon::createFromDate($tahun, $bulan, 1)->locale('id')->translatedFormat('F Y'),
                    'target_rj'    => $targetRow->t_rj ?? 0,
                    'real_rj'      => $items->sum('realisasi_rawat_jalan'),
                    'target_pasca' => $targetRow->t_pasca ?? 0,
                    'real_pasca'   => $items->sum('realisasi_pasca_rehab'),
                    'target_skhpn' => $targetRow->t_skhpn ?? 0,
                    'real_skhpn'   => $items->sum('realisasi_skhpn'),
                ];
            }
        }

        // 4. LIST TARGET (Untuk Modal Kelola Target)
        $targetsQuery = RehabTarget::with('satuanKerja');

        // A. Filter Satker
        if ($user->hasRole('admin')) {
            if ($request->filled('satuan_kerja_id')) {
                $targetsQuery->whereIn('satuan_kerja_id', (array)$request->satuan_kerja_id);
            }
        } else {
            $targetsQuery->where('satuan_kerja_id', $user->getSatkerId());
        }

        // B. Filter Tahun (Ikut filter dashboard agar relevan)
        if ($request->filled('tahun')) {
            $targetsQuery->whereIn('tahun', (array)$request->tahun);
        } else {
            // Default tahun ini
            $targetsQuery->where('tahun', date('Y'));
        }

        // C. Filter Bulan (DIABAIKAN agar user bisa lihat target setahun penuh)

        $allTargets = $targetsQuery->orderBy('tahun', 'desc')
                                   ->orderBy('bulan', 'asc')
                                   ->get();

        // 5. Inject Status 'has_laporan' ke object target (Untuk Restrict Delete)
        $allTargets->transform(function($target) {
            $exists = RehabLaporan::where('satuan_kerja_id', $target->satuan_kerja_id)
                ->whereYear('tanggal', $target->tahun)
                ->whereMonth('tanggal', $target->bulan)
                ->exists();
            $target->has_laporan = $exists;
            return $target;
        });

        return view('rehab.laporan.index', compact('data', 'satuanKerjas', 'years', 'summary', 'allTargets'));
    }

    // --- FITUR: SIMPAN TARGET BULANAN ---
    public function storeTarget(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $request->validate([
            'satuan_kerja_id'    => $user->isAdmin() ? 'required' : 'nullable',
            'bulan'              => 'required|integer|min:1|max:12',
            'tahun'              => 'required|integer|min:2020',
            'target_rawat_jalan' => 'required|integer|min:0',
            'target_pasca_rehab' => 'required|integer|min:0',
            'target_skhpn'       => 'required|integer|min:0',
        ]);

        $satkerId = $user->isAdmin() ? $request->satuan_kerja_id : $user->getSatkerId();

        RehabTarget::updateOrCreate(
            [
                'satuan_kerja_id' => $satkerId,
                'bulan'           => $request->bulan,
                'tahun'           => $request->tahun
            ],
            [
                'target_rawat_jalan' => $request->target_rawat_jalan,
                'target_pasca_rehab' => $request->target_pasca_rehab,
                'target_skhpn'       => $request->target_skhpn,
            ]
        );

        return back()->with('success', 'Target Bulanan berhasil disimpan.');
    }

    // --- FITUR: HAPUS TARGET (Restrict Delete) ---
    public function destroyTarget($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $target = RehabTarget::findOrFail($id);
        
        // Cek Hak Akses
        if (!$user->isAdmin() && $target->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403);
        }

        // Cek apakah ada laporan harian di bulan & tahun target tersebut
        $hasLaporan = RehabLaporan::where('satuan_kerja_id', $target->satuan_kerja_id)
            ->whereYear('tanggal', $target->tahun)
            ->whereMonth('tanggal', $target->bulan)
            ->exists();

        if ($hasLaporan) {
            return back()->with('error', 'Gagal menghapus! Target ini sudah digunakan dalam laporan harian.');
        }

        $target->delete();
        return back()->with('success', 'Target berhasil dihapus.');
    }

    // --- CREATE HARIAN ---
    public function create()
    {
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja')->get();
        return view('rehab.laporan.create', compact('satuanKerjas'));
    }

    // --- STORE HARIAN ---
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $satkerId = $user->isAdmin() ? $request->satuan_kerja_id : $user->getSatkerId();
        
        // Cek Duplikasi Tanggal
        $exists = RehabLaporan::where('satuan_kerja_id', $satkerId)
            ->where('tanggal', $request->tanggal)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'tanggal' => ['Laporan untuk tanggal tersebut sudah ada. Silakan edit data yang sudah ada.']
            ]);
        }

        $request->validate([
            'tanggal'               => 'required|date', 
            'satuan_kerja_id'       => $user->isAdmin() ? 'required' : 'nullable',
            'realisasi_rawat_jalan' => 'required|integer|min:0',
            'realisasi_pasca_rehab' => 'required|integer|min:0',
            'realisasi_skhpn'       => 'required|integer|min:0',
            'dokumentasi'           => 'nullable|array'
        ]);

        DB::beginTransaction();
        try {
            $data = $request->except(['dokumentasi', 'satuan_kerja_id']);
            $data['satuan_kerja_id'] = $satkerId;

            $laporan = RehabLaporan::create($data);

            // Upload File
            if ($request->filled('dokumentasi')) {
                foreach ($request->input('dokumentasi') as $folder) {
                    $tempFile = TemporaryFile::where('folder', $folder)->first();
                    if ($tempFile) {
                        $sourcePath = 'public/tmp/' . $folder . '/' . $tempFile->filename;
                        $ext = pathinfo($tempFile->filename, PATHINFO_EXTENSION);
                        $cleanName = time() . '_' . uniqid() . '_rehab.' . $ext;
                        $destPath = 'dokumentasi/rehab/' . date('Y') . '/' . $cleanName;

                        if (Storage::exists($sourcePath)) {
                            Storage::disk('public')->put($destPath, Storage::readStream($sourcePath));
                            $laporan->dokumentasi()->create([
                                'nama_file_asli' => $tempFile->filename,
                                'path_file'      => $destPath,
                                'tipe_file'      => Storage::mimeType($sourcePath),
                                'ukuran_file'    => Storage::size($sourcePath)
                            ]);
                            Storage::deleteDirectory('public/tmp/' . $folder);
                            $tempFile->delete();
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('rehab.laporan.index')->with('success', 'Laporan Harian berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $laporan = RehabLaporan::with(['dokumentasi'])->findOrFail($id);
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->isAdmin() && $laporan->satuan_kerja_id !== $user->getSatkerId()) abort(403);

        return view('rehab.laporan.edit', compact('laporan'));
    }

    public function update(Request $request, $id)
    {
        $laporan = RehabLaporan::findOrFail($id);
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->isAdmin() && $laporan->satuan_kerja_id !== $user->getSatkerId()) abort(403);

        $request->validate([
            'realisasi_rawat_jalan' => 'required|integer|min:0',
            'realisasi_pasca_rehab' => 'required|integer|min:0',
            'realisasi_skhpn'       => 'required|integer|min:0',
            'delete_files'          => 'nullable|array',
            'dokumentasi'           => 'nullable|array'
        ]);

        DB::beginTransaction();
        try {
            $laporan->update($request->only(['realisasi_rawat_jalan', 'realisasi_pasca_rehab', 'realisasi_skhpn']));

            if ($request->has('delete_files')) {
                $filesToRemove = DokumentasiKegiatan::whereIn('id', $request->delete_files)->get();
                foreach ($filesToRemove as $file) {
                    if (Storage::disk('public')->exists($file->path_file)) Storage::disk('public')->delete($file->path_file);
                    $file->delete();
                }
            }

            if ($request->filled('dokumentasi')) {
                foreach ($request->input('dokumentasi') as $folder) {
                    $tempFile = TemporaryFile::where('folder', $folder)->first();
                    if ($tempFile) {
                        $sourcePath = 'public/tmp/' . $folder . '/' . $tempFile->filename;
                        $ext = pathinfo($tempFile->filename, PATHINFO_EXTENSION);
                        $cleanName = time() . '_' . uniqid() . '_rehab_upd.' . $ext;
                        $destPath = 'dokumentasi/rehab/' . date('Y') . '/' . $cleanName;

                        if (Storage::exists($sourcePath)) {
                            Storage::disk('public')->put($destPath, Storage::readStream($sourcePath));
                            $laporan->dokumentasi()->create([
                                'nama_file_asli' => $tempFile->filename,
                                'path_file'      => $destPath,
                                'tipe_file'      => Storage::mimeType($sourcePath),
                                'ukuran_file'    => Storage::size($sourcePath)
                            ]);
                            Storage::deleteDirectory('public/tmp/' . $folder);
                            $tempFile->delete();
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('rehab.laporan.index')->with('success', 'Laporan diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal update: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        $laporan = RehabLaporan::with('dokumentasi')->findOrFail($id);
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->isAdmin() && $laporan->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403);
        }

        $filesToDelete = [];
        foreach($laporan->dokumentasi as $doc) {
            $filesToDelete[] = $doc->path_file;
        }

        DB::beginTransaction();
        try {
            $laporan->delete();
            DB::commit(); 
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }

        foreach ($filesToDelete as $path) {
            try {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            } catch (\Exception $e) { }
        }

        return back()->with('success', 'Data laporan harian berhasil dihapus.');
    }

    // --- EXPORT (Super Admin All, User Satker Sendiri) ---
    public function export(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            $satkers = SatuanKerja::orderBy('id')->get();
        } else {
            $satkers = SatuanKerja::where('id', $user->getSatkerId())->get();
        }

        $category = $request->query('kategori', 'rawat_jalan');
        
        $colReal = match($category) {
            'pasca_rehab' => 'realisasi_pasca_rehab',
            'skhpn'       => 'realisasi_skhpn',
            default       => 'realisasi_rawat_jalan'
        };
        $colTarget = match($category) {
            'pasca_rehab' => 'target_pasca_rehab',
            'skhpn'       => 'target_skhpn',
            default       => 'target_rawat_jalan'
        };

        $query = $this->getFilteredQuery($request);
        $laporanHarian = $query->get();

        $years = $laporanHarian->pluck('tanggal')
            ->map(fn($d) => $d->format('Y'))
            ->unique()->sort()->values()->toArray();

        if (empty($years)) {
            $years = $request->filled('tahun') ? array_map('intval', (array)$request->tahun) : [date('Y')];
            sort($years);
        }

        $exportData = [];

        foreach ($satkers as $satker) {
            $row = [
                'satker_nama' => $satker->satuan_kerja,
                'years' => []
            ];

            foreach ($years as $year) {
                $realisasiTotal = $laporanHarian->filter(function($item) use ($satker, $year) {
                    return $item->satuan_kerja_id == $satker->id && $item->tanggal->format('Y') == $year;
                })->sum($colReal);

                $targetTotal = RehabTarget::where('satuan_kerja_id', $satker->id)
                    ->where('tahun', $year)
                    ->sum($colTarget);

                $p = $targetTotal > 0 ? ($realisasiTotal / $targetTotal) * 100 : 0;

                $row['years'][$year] = [
                    'target' => $targetTotal,
                    'realisasi' => $realisasiTotal,
                    'persen' => $p
                ];
            }
            $exportData[] = $row;
        }

        $fileName = 'Laporan_Rehab_' . strtoupper($category) . '_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new RehabLaporanExport($exportData, $years, $category), $fileName);
    }
}