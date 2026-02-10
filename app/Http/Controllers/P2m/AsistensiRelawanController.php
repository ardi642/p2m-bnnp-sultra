<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\P2mAsistensiRelawan;
use App\Models\SatuanKerja;
use App\Models\Pegawai;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Exports\AsistensiRelawanExport;
use App\Helpers\SearchHelper;
use App\Models\DokumentasiKegiatan;
use App\Models\TemporaryFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class AsistensiRelawanController extends Controller
{
    // --- QUERY BUILDER (SAMA PERSIS) ---
    private function getFilteredQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $activeYears = $request->filled('tahun') ? $request->tahun : [date('Y')];

        $query = P2mAsistensiRelawan::with('pegawai.satuanKerja', 'satuanKerja');

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
            $query->where(function ($q) use ($request) {
                foreach ($request->bulan as $b) {
                    $q->orWhereMonth('tanggal_pelaksanaan', $b);
                }
            });
        }

        // Filter Tahun
        $query->where(function ($q) use ($activeYears) {
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
                    $query->whereHas('pegawai', function ($q) use ($nip) {
                        $q->where('pegawai.nip', $nip);
                    });
                }
            } else {
                $query->whereHas('pegawai', function ($q) use ($nips) {
                    $q->whereIn('pegawai.nip', $nips);
                });
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $searchDate = SearchHelper::translateDateInput($search);
            $query->where(function ($q) use ($search, $searchDate) {
                $q->where('nama_kegiatan', 'LIKE', "%{$search}%")
                    ->orWhere('tempat_kegiatan', 'LIKE', "%{$search}%")
                    ->orWhere('sasaran_kegiatan', 'LIKE', "%{$search}%")
                    ->orWhere('anggaran_pelaksanaan', 'LIKE', "%{$search}%")
                    ->orWhere('jumlah_peserta', 'LIKE', "%{$search}%")
                    ->orWhereHas('satuanKerja', function ($subQ) use ($search) {
                        $subQ->where('satuan_kerja', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('pegawai', function ($subQ) use ($search) {
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
                $query->join('satuan_kerja', 'p2m_asistensi_relawan.satuan_kerja_id', '=', 'satuan_kerja.id')
                    ->orderBy('satuan_kerja.satuan_kerja', $sortOrder)
                    ->select('p2m_asistensi_relawan.*');
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

        $yearQuery = P2mAsistensiRelawan::selectRaw('YEAR(tanggal_pelaksanaan) as year');

        if ($user->hasRole(['operator_satker', 'operator_p2m'])) {
            $yearQuery->where('satuan_kerja_id', $user->getSatkerId());
        }

        $years = $yearQuery->distinct()->orderBy('year', 'desc')->pluck('year');

        $query = $this->getFilteredQuery($request);

        $statsQuery = clone $query;
        $totalKegiatan = $statsQuery->count();
        $totalPeserta = $statsQuery->sum('jumlah_peserta');

        // PENTING: Eager load dokumentasi
        $query->with('dokumentasi');

        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : 10;
        $asistensirelawans = $query->paginate($perPage)->withQueryString();

        $satkerLookup = SatuanKerja::pluck('satuan_kerja', 'id')->toArray();

        return view('p2m.asistensi-relawan.index', compact('asistensirelawans', 'satuanKerjas', 'years', 'pegawais', 'user', 'satkerLookup', 'totalKegiatan', 'totalPeserta'));
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

        return view('p2m.asistensi-relawan.create', compact('satuanKerjas', 'pegawais'));
    }

    // --- REFACTOR STORE MENJADI SAMA SEPERTI UPACARA ---
    public function store(Request $request)
    {

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
        $filesMoved = [];

        DB::beginTransaction();

        try {
            $dataKegiatan = collect($validasi)->except('dokumentasi', 'pegawai_nips')->toArray();

            if ($user->hasRole(['operator_satker', 'operator_p2m'])) {
                $dataKegiatan['satuan_kerja_id'] = $user->getSatkerId();
            }

            // 1. Simpan Kegiatan
            $kegiatan = P2mAsistensiRelawan::create($dataKegiatan);

            // 2. Simpan Pegawai
            $listPegawai = Pegawai::whereIn('nip', $validasi['pegawai_nips'])->get();
            $attachData = [];
            foreach ($listPegawai as $pgw) {
                $attachData[$pgw->nip] = ['saved_satuan_kerja_id' => $pgw->satuan_kerja_id];
            }
            $kegiatan->pegawai()->attach($attachData);

            // 3. Proses File (Panggil Helper Private)
            if ($request->filled('dokumentasi')) {
                // Pass $filesMoved by reference (&) agar bisa diisi di dalam fungsi
                $this->processFiles($request->input('dokumentasi'), $kegiatan, $filesMoved);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            // Jika gagal, hapus file fisik yang sudah terlanjur dipindah
            foreach ($filesMoved as $path) {
                if (Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
            }
            return back()->with('error', 'store')->with('message', 'Gagal: ' . $e->getMessage())->withInput();
        }

        // $kegiatan = P2mAsistensiRelawan::create($dataKegiatan);

        // dd($kegiatan->toArray());

        return redirect()->route('p2m.asistensi-relawan.index')->with('success', 'store')->with('message', 'Berhasil menambahkan data.');
    }

    public function edit($id): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kegiatan = P2mAsistensiRelawan::with('pegawai')->findOrFail($id);

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

        return view('p2m.asistensi-relawan.edit', compact('kegiatan', 'satuanKerjas', 'pegawais', 'selectedPegawaiNips'));
    }

    public function update(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kegiatan = P2mAsistensiRelawan::findOrFail($id);

        if ($user->hasRole(['operator_satker', 'operator_p2m']) && $kegiatan->satuan_kerja_id !== $user->getSatkerId()) abort(403);

        $rules = [
            'anggaran_pelaksanaan' => 'required',
            'nama_kegiatan'        => 'required',
            'sasaran_kegiatan'     => 'required',
            'tanggal_pelaksanaan'  => 'required|date',
            'tempat_kegiatan'      => 'required',
            'jumlah_peserta'       => 'required|numeric',
            'pegawai_nips'         => 'required|array',
            'pegawai_nips.*'       => 'exists:pegawai,nip',
            'delete_files'         => 'nullable|array',
            'dokumentasi'          => 'nullable|array',
        ];

        if ($user->isAdmin()) $rules['satuan_kerja_id'] = 'required';

        $validasi = $request->validate($rules);
        $newFilesMoved = [];
        $filesToDelete = [];

        DB::beginTransaction();

        try {
            $dataUpdate = collect($validasi)->except(['dokumentasi', 'pegawai_nips', 'delete_files'])->toArray();
            if ($user->hasRole(['operator_satker', 'operator_p2m'])) unset($dataUpdate['satuan_kerja_id']);

            $kegiatan->update($dataUpdate);

            // Sync Pegawai
            $oldPivotData = DB::table('pegawai_p2m_asistensi_relawan')->where('p2m_asistensi_relawan_id', $id)->get()->keyBy('pegawai_nip');
            $masterPegawais = Pegawai::whereIn('nip', $validasi['pegawai_nips'])->get()->keyBy('nip');
            $syncData = [];

            foreach ($validasi['pegawai_nips'] as $nip) {
                $satkerToSave = (isset($oldPivotData[$nip]) && $oldPivotData[$nip]->saved_satuan_kerja_id)
                    ? $oldPivotData[$nip]->saved_satuan_kerja_id
                    : ($masterPegawais[$nip]->satuan_kerja_id ?? null);

                $syncData[$nip] = ['saved_satuan_kerja_id' => $satkerToSave];
            }
            $kegiatan->pegawai()->sync($syncData);

            // Hapus File Lama
            if ($request->has('delete_files')) {
                $filesToRemove = DokumentasiKegiatan::whereIn('id', $request->delete_files)->get();
                foreach ($filesToRemove as $file) {
                    $filesToDelete[] = $file->path_file;
                    $file->delete();
                }
            }

            // Upload File Baru
            if ($request->filled('dokumentasi')) {
                $this->processFiles($request->input('dokumentasi'), $kegiatan, $newFilesMoved);
            }

            DB::commit();

            // Hapus Fisik File Lama
            foreach ($filesToDelete as $path) {
                if (Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
            }

            return redirect()->route('p2m.asistensi-relawan.index')->with('success', 'update')->with('message', 'Data berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($newFilesMoved as $path) {
                if (Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
            }
            return back()->with('error', 'update')->with('message', 'Gagal: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        $kegiatan = P2mAsistensiRelawan::findOrFail($id);

        $filesToDelete = [];
        foreach ($kegiatan->dokumentasi()->cursor() as $doc) {
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
        return Excel::download(new AsistensiRelawanExport($query), 'Laporan_P2M_Asistensi_Relawan.xlsx');
    }

    // --- HELPER PROCESS FILES (SAMA SEPERTI UPACARA) ---
    private function processFiles($tempFolders, $kegiatan, &$movedFilesLog)
    {
        foreach ($tempFolders as $folder) {
            $tempFile = TemporaryFile::where('folder', $folder)->first();
            if ($tempFile) {
                $sourcePath = 'public/tmp/' . $folder . '/' . $tempFile->filename;

                // Pastikan file ada sebelum diproses
                if (Storage::exists($sourcePath)) {
                    $mimeType = Storage::mimeType($sourcePath);
                    $size = Storage::size($sourcePath);
                    $ext = pathinfo($tempFile->filename, PATHINFO_EXTENSION);
                    $nameOnly = pathinfo($tempFile->filename, PATHINFO_FILENAME);

                    // Generate nama unik
                    $cleanFileName = time() . '_' . uniqid() . '_' . Str::slug($nameOnly) . '.' . $ext;
                    $destinationPath = 'dokumentasi/' . date('Y') . '/' . $cleanFileName;

                    // Pindahkan file
                    Storage::disk('public')->put($destinationPath, Storage::readStream($sourcePath));

                    // Catat file yang berhasil dipindah (untuk rollback)
                    $movedFilesLog[] = $destinationPath;

                    // Simpan ke DB
                    $kegiatan->dokumentasi()->create([
                        'nama_file_asli' => $tempFile->filename,
                        'path_file'      => $destinationPath,
                        'tipe_file'      => $mimeType,
                        'ukuran_file'    => $size,
                    ]);

                    // Bersihkan folder temp
                    Storage::deleteDirectory('public/tmp/' . $folder);
                    $tempFile->delete();
                }
            }
        }
    }
}
