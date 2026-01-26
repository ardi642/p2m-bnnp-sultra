<?php

namespace App\Http\Controllers\Rehab;

use App\Http\Controllers\Controller;
use App\Models\RehabLaporanBulanan;
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
    // --- 1. QUERY FILTER UTAMA (Index & Export pakai ini) ---
    private function getFilteredQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $query = RehabLaporanBulanan::with(['satuanKerja', 'dokumentasi']);

        // Filter Satuan Kerja
        if ($user->hasRole('admin')) {
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('rehab_laporan_bulanan.satuan_kerja_id', (array)$request->satuan_kerja_id);
            }
        } else {
            $query->where('rehab_laporan_bulanan.satuan_kerja_id', $user->getSatkerId());
        }

        // Filter Bulan
        if ($request->filled('bulan')) {
            $query->whereIn(DB::raw('MONTH(rehab_laporan_bulanan.periode)'), (array)$request->bulan);
        }
        
        // Filter Tahun
        if ($request->filled('tahun')) {
            $query->whereIn(DB::raw('YEAR(rehab_laporan_bulanan.periode)'), (array)$request->tahun);
        } 
        elseif (!$request->has('tahun')) {
            // Jika tidak ada filter tahun sama sekali, default ke tahun ini
            $query->whereYear('rehab_laporan_bulanan.periode', date('Y'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'periode');
        $sortOrder = $request->input('sort_order', 'desc');

        if ($sortBy === 'satuan_kerja_id') {
            $query->join('satuan_kerja', 'rehab_laporan_bulanan.satuan_kerja_id', '=', 'satuan_kerja.id')
                  ->orderBy('satuan_kerja.satuan_kerja', $sortOrder)
                  ->select('rehab_laporan_bulanan.*');
        } else {
            $query->orderBy('rehab_laporan_bulanan.' . $sortBy, $sortOrder);
        }

        return $query;
    }

    public function index(Request $request)
    {
        // Ambil tahun unik untuk DROPDOWN (Tetap ambil semua tahun yg ada di DB agar bisa dipilih)
        $years = RehabLaporanBulanan::selectRaw('YEAR(periode) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja')->get();
        
        $perPage = $request->input('per_page', 10);
        $data = $this->getFilteredQuery($request)->paginate($perPage)->withQueryString();

        return view('rehab.laporan.index', compact('data', 'satuanKerjas', 'years'));
    }

    // --- 2. METHOD EXPORT (PERBAIKAN LOGIKA TAHUN) ---
    public function export(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // A. CEK HAK AKSES
        $userSatker = ($user->pegawai && $user->pegawai->satuanKerja) ? $user->pegawai->satuanKerja : null;
        $isSuperAdmin = $user->hasRole('admin') && !$userSatker;
        $isBnnpSultra = false;
        if ($userSatker) {
            $namaSatker = strtoupper(trim($userSatker->satuan_kerja));
            $isBnnpSultra = ($namaSatker === 'BNNP SULTRA');
        }

        if (!$isSuperAdmin && !$isBnnpSultra) {
            abort(403, 'Maaf, Anda tidak memiliki hak akses untuk mengunduh rekapitulasi laporan ini.');
        }

        // B. TENTUKAN KATEGORI & KOLOM
        $category = $request->query('kategori', 'rawat_jalan');
        
        $colTarget = match($category) {
            'pasca_rehab' => 'target_pasca_rehab',
            'skhpn'       => 'target_skhpn',
            default       => 'target_rawat_jalan'
        };
        $colReal = match($category) {
            'pasca_rehab' => 'realisasi_pasca_rehab',
            'skhpn'       => 'realisasi_skhpn',
            default       => 'realisasi_rawat_jalan'
        };

        // C. AMBIL DATA SESUAI FILTER
        // Menggunakan getFilteredQuery() memastikan data yg diambil = data yg tampil di layar
        $query = $this->getFilteredQuery($request);
        $laporan = $query->get(); // Ambil semua (tanpa pagination)

        // D. EKSTRAK TAHUN DARI HASIL FILTER (KUNCI PERBAIKAN)
        // Kita hanya mengambil tahun yang muncul di variable $laporan.
        // Jika filter tahun = 2026, maka $laporan hanya berisi data 2026.
        // Otomatis $years hanya berisi [2026].
        $years = $laporan->pluck('periode')
            ->map(fn($d) => date('Y', strtotime($d)))
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        // E. HANDLING JIKA DATA KOSONG
        // Jika hasil filter kosong (misal filter tahun 2026 tapi belum ada data),
        // Kita tetap ingin menampilkan Header Kolom 2026 (kosong), bukan tabel tanpa header tahun.
        if (empty($years) && $request->filled('tahun')) {
            $years = array_map('intval', (array)$request->tahun); // Pakai tahun dari request
            sort($years);
        } elseif (empty($years)) {
            // Jika kosong total dan tidak ada filter, default tahun ini
            $years = [date('Y')]; 
        }

        // F. PIVOT DATA (GROUPING PER SATKER)
        $satkers = SatuanKerja::orderBy('id')->get(); 
        $exportData = [];

        foreach ($satkers as $satker) {
            $row = [
                'satker_nama' => $satker->satuan_kerja,
                'years' => []
            ];

            foreach ($years as $year) {
                // Filter data $laporan (yang sudah difilter dari DB) untuk satker & tahun ini
                $dataTahun = $laporan->filter(function($item) use ($satker, $year) {
                    return $item->satuan_kerja_id == $satker->id && date('Y', strtotime($item->periode)) == $year;
                });

                $t = $dataTahun->sum($colTarget);
                $r = $dataTahun->sum($colReal);
                $p = $t > 0 ? ($r / $t) * 100 : 0;

                $row['years'][$year] = [
                    'target' => $t,
                    'realisasi' => $r,
                    'persen' => $p
                ];
            }
            $exportData[] = $row;
        }

        $fileName = 'Laporan_' . strtoupper($category) . '_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new RehabLaporanExport($exportData, $years, $category), $fileName);
    }

    public function create()
    {
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja')->get();
        return view('rehab.laporan.create', compact('satuanKerjas'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $satkerId = $user->isAdmin() ? $request->satuan_kerja_id : $user->getSatkerId();
        $periodeDate = $request->periode_input . '-01'; 

        $exists = RehabLaporanBulanan::where('satuan_kerja_id', $satkerId)
            ->where('periode', $periodeDate)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'periode' => ['Laporan untuk periode tersebut sudah tersedia. Mohon cek kembali.']
            ]);
        }

        $request->validate([
            'periode_input'         => 'required', 
            'satuan_kerja_id'       => $user->isAdmin() ? 'required' : 'nullable',
            'target_rawat_jalan'    => 'required|integer|min:0',
            'realisasi_rawat_jalan' => 'required|integer|min:0',
            'target_pasca_rehab'    => 'required|integer|min:0',
            'realisasi_pasca_rehab' => 'required|integer|min:0',
            'target_skhpn'          => 'required|integer|min:0',
            'realisasi_skhpn'       => 'required|integer|min:0',
            'dokumentasi'           => 'nullable|array'
        ]);

        DB::beginTransaction();
        try {
            $data = $request->except(['dokumentasi', 'periode_input', 'satuan_kerja_id']);
            $data['satuan_kerja_id'] = $satkerId;
            $data['periode'] = $periodeDate;

            $laporan = RehabLaporanBulanan::create($data);

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
            return redirect()->route('rehab.laporan.index')->with('success', 'Laporan berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $laporan = RehabLaporanBulanan::with(['dokumentasi'])->findOrFail($id);
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->isAdmin() && $laporan->satuan_kerja_id !== $user->getSatkerId()) abort(403);

        return view('rehab.laporan.edit', compact('laporan'));
    }

    public function update(Request $request, $id)
    {
        $laporan = RehabLaporanBulanan::findOrFail($id);
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->isAdmin() && $laporan->satuan_kerja_id !== $user->getSatkerId()) abort(403);

        $request->validate([
            'target_rawat_jalan'    => 'required|integer|min:0',
            'realisasi_rawat_jalan' => 'required|integer|min:0',
            'target_pasca_rehab'    => 'required|integer|min:0',
            'realisasi_pasca_rehab' => 'required|integer|min:0',
            'target_skhpn'          => 'required|integer|min:0',
            'realisasi_skhpn'       => 'required|integer|min:0',
            'delete_files'          => 'nullable|array',
            'dokumentasi'           => 'nullable|array'
        ]);

        DB::beginTransaction();
        try {
            $laporan->update($request->except(['dokumentasi', 'delete_files', 'periode_input', 'satuan_kerja_id']));

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
        $laporan = RehabLaporanBulanan::with('dokumentasi')->findOrFail($id);
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

        return back()->with('success', 'Data laporan berhasil dihapus.');
    }
}