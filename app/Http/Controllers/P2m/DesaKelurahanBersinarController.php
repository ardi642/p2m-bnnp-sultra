<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\P2mDesaKelurahanBersinar;
use App\Models\SatuanKerja;
use App\Models\Pegawai;
use App\Models\KabupatenKota;
use App\Models\TemporaryFile;
use App\Models\DokumentasiKegiatan;
use App\Helpers\SearchHelper;
use App\Exports\DesaKelurahanBersinarExport;
use App\Models\Dokumen;
use App\Services\DokumenService;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class DesaKelurahanBersinarController extends Controller
{
    // --- BUILD QUERY FILTER ---
    private function getFilteredQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $activeYears = $request->filled('tahun') ? $request->tahun : [date('Y')];
        
        $query = P2mDesaKelurahanBersinar::with('pegawai.satuanKerja', 'satuanKerja', 'kabupatenKota');

        // 1. Filter Satker
        if ($user->hasRole('admin')) {
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('satuan_kerja_id', $request->satuan_kerja_id);
            }
        } else {
            $query->where('satuan_kerja_id', $user->getSatkerId());
        }

        // 2. Filter Waktu
        if ($request->filled('bulan')) {
            $query->where(function($q) use ($request) {
                foreach ($request->bulan as $b) {
                    $q->orWhereMonth('tanggal_pencanangan', $b);
                }
            });
        }
        $query->where(function($q) use ($activeYears) {
            foreach ($activeYears as $y) {
                $q->orWhereYear('tanggal_pencanangan', $y);
            }
        });

        // 3. Filter Spesifik
        if ($request->filled('anggaran_pembentukan')) {
            $query->whereIn('anggaran_pembentukan', $request->anggaran_pembentukan);
        }
        if ($request->filled('kabupaten_kota_id')) {
            $query->whereIn('kabupaten_kota_id', $request->kabupaten_kota_id);
        }

        // 4. Filter Pegawai
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

        // 5. Search
        if ($request->filled('search')) {
            $search = $request->search;
            $searchDate = SearchHelper::translateDateInput($search);
            
            $query->where(function($q) use ($search, $searchDate) {
                $q->where('nama_desa', 'LIKE', "%{$search}%")
                  ->orWhere('nama_kelurahan', 'LIKE', "%{$search}%")
                  ->orWhere('anggaran_pembentukan', 'LIKE', "%{$search}%")
                  ->orWhere('jumlah_penggiat', 'LIKE', "%{$search}%")
                  ->orWhere('keberadaan_ibm', 'LIKE', "%{$search}%")
                  ->orWhereHas('kabupatenKota', function($subQ) use ($search) {
                      $subQ->where('nama', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('satuanKerja', function($subQ) use ($search) {
                      $subQ->where('satuan_kerja', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('pegawai', function($subQ) use ($search) {
                      $subQ->where('nama', 'LIKE', "%{$search}%");
                  });

                $q->orWhereRaw("LOWER(DATE_FORMAT(tanggal_pencanangan, '%d %M %Y')) LIKE ?", ["%{$searchDate}%"]);
                $q->orWhereRaw("LOWER(DATE_FORMAT(created_at, '%d %b %Y')) LIKE ?", ["%{$searchDate}%"]);
            });
        }

        // 6. Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        // PERBAIKAN: Menambahkan kolom 'nama_kelurahan' dan 'keberadaan_ibm'
        $allowSort = [
            'created_at', 
            'tanggal_pencanangan', 
            'nama_desa_kelurahan', 
            'anggaran_pembentukan',
            'jumlah_penggiat', 
            'satuan_kerja',
            'keberadaan_ibm'
        ];

        if (in_array($sortBy, $allowSort)) {
            if ($sortBy === 'satuan_kerja') {
                $query->join('satuan_kerja', 'p2m_desa_kelurahan_bersinar.satuan_kerja_id', '=', 'satuan_kerja.id')
                        ->orderBy('satuan_kerja.satuan_kerja', $sortOrder)
                        ->select('p2m_desa_kelurahan_bersinar.*');
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }
        } else {
            $query->latest();
        }

        return $query;
    }

    // --- INDEX ---
    public function index(Request $request): View 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $kabupatens = KabupatenKota::orderBy('nama', 'asc')->get();
        
        if ($user->hasRole('admin')) {
            $pegawais = Pegawai::orderBy('nama', 'asc')->get(['nip', 'nama']);
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        } else {
            $pegawais = Pegawai::where('satuan_kerja_id', $user->getSatkerId())->orderBy('nama', 'asc')->get(['nip', 'nama']);
            $satuanKerjas = [];
        }

        $yearQuery = P2mDesaKelurahanBersinar::selectRaw('YEAR(tanggal_pencanangan) as year');
        if ($user->hasRole(['operator_satker', 'operator_p2m'])) {
            $yearQuery->where('satuan_kerja_id', $user->getSatkerId());
        }
        $years = $yearQuery->distinct()->orderBy('year', 'desc')->pluck('year');

        $query = $this->getFilteredQuery($request);

        $statsQuery = clone $query;
        $totalKegiatan = $statsQuery->count();
        
        $query->with('dokumen');
        $perPage = in_array($request->per_page, [10, 25, 50, 100]) ? $request->per_page : 10;
        $desas = $query->paginate($perPage)->withQueryString();

        return view('p2m.desa-kelurahan-bersinar.index', compact('desas', 'satuanKerjas', 'years', 'pegawais', 'user', 'kabupatens', 'totalKegiatan'));
    }

    // --- EXPORT ---
    public function export(Request $request) 
    {
        $query = $this->getFilteredQuery($request);
        return Excel::download(new DesaKelurahanBersinarExport($query), 'Laporan_P2M_Desa_Bersinar.xlsx');
    }

    // --- CREATE ---
    public function create(): View 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kabupatens = KabupatenKota::orderBy('nama', 'asc')->get();

        if ($user->isAdmin()) {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
            $pegawais = Pegawai::with('satuanKerja')->orderBy('nama', 'asc')->get();
        } else {
            $satuanKerjas = [];
            $pegawais = Pegawai::with('satuanKerja')->where('satuan_kerja_id', $user->getSatkerId())->orderBy('nama', 'asc')->get();
        }

        return view('p2m.desa-kelurahan-bersinar.create', compact('satuanKerjas', 'pegawais', 'kabupatens'));
    }

    // --- STORE ---
    public function store(Request $request, DokumenService $dokumenService) 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Validasi: nama_kelurahan required
        $rules = [
            'anggaran_pembentukan' => 'required',
            'nama_desa_kelurahan' => 'required',
            'kabupaten_kota_id' => 'required|exists:kabupaten_kota,id',
            'tanggal_pencanangan' => 'required|date',
            'jumlah_penggiat' => 'required|numeric',
            'keberadaan_ibm' => 'required',
            'pegawai_nips' => 'required|array',
            'pegawai_nips.*' => 'exists:pegawai,nip',
            'no_hp_penanggung_jawab' => 'nullable|string|max:20',
            'dokumentasi'          => 'nullable|array', 
            'lampiran'             => 'nullable|array',
            'dokumentasi_links'    => 'nullable|array',
            'dokumentasi_links.*.nama' => 'required_with:dokumentasi_links.*.url|nullable|string|max:255',
            'dokumentasi_links.*.url'  => 'required_with:dokumentasi_links.*.nama|nullable|url',
            'lampiran_links'       => 'nullable|array',
            'lampiran_links.*.nama' => 'required_with:lampiran_links.*.url|nullable|string|max:255',
            'lampiran_links.*.url'  => 'required_with:lampiran_links.*.nama|nullable|url',
        ];

        if ($user->isAdmin()) {
            $rules['satuan_kerja_id'] = 'required|exists:satuan_kerja,id';
        }

        $validasi = $request->validate($rules);
        $uploadedPaths = []; 

        DB::beginTransaction();

        try {
            $dataInput = collect($validasi)->except('dokumentasi', 'pegawai_nips')->toArray();
            $pegawaiNips = $validasi['pegawai_nips'];

            if ($user->hasRole(['operator_satker', 'operator_p2m'])) {
                $dataInput['satuan_kerja_id'] = $user->getSatkerId();
            }

            $desa = P2mDesaKelurahanBersinar::create($dataInput);

            $listPegawai = Pegawai::whereIn('nip', $pegawaiNips)->get();
            $attachData = [];
            foreach ($listPegawai as $pgw) {
                $attachData[$pgw->nip] = ['saved_satuan_kerja_id' => $pgw->satuan_kerja_id];
            }
            $desa->pegawai()->attach($attachData);

            if ($request->filled('dokumentasi')) {
                $dokumenService->moveToPermanent($request->input('dokumentasi'), $desa, 'dokumentasi', $uploadedPaths);
            }
            if ($request->filled('lampiran')) {
                $dokumenService->moveToPermanent($request->input('lampiran'), $desa, 'lampiran', $uploadedPaths);
            }
            if ($request->filled('dokumentasi_links')) {
                $dokumenService->saveLinks($request->input('dokumentasi_links'), $desa, 'dokumentasi');
            }
            if ($request->filled('lampiran_links')) {
                $dokumenService->saveLinks($request->input('lampiran_links'), $desa, 'lampiran');
            }

            DB::commit();
        return redirect()->route('p2m.desa-kelurahan-bersinar.index')->with('success', 'store')->with('message', 'Berhasil menambahkan data');


        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($uploadedPaths as $path) {
                Storage::disk(config('filesystems.default'))->delete($path);
            }
            Log::error('Gagal simpan: ' . $e->getMessage());
            abort(500, 'Server Error.');
        }

    }

    // --- EDIT ---
    public function edit($id): View 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $desa = P2mDesaKelurahanBersinar::with('pegawai')->findOrFail($id);
        $kabupatens = KabupatenKota::orderBy('nama', 'asc')->get();

        if ($user->hasRole(['operator_satker', 'operator_p2m']) && $desa->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403);
        }

        if ($user->isAdmin()) {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
            $pegawais = Pegawai::orderBy('nama', 'asc')->get();
        } else {
            $satuanKerjas = [];
            $satkerId = $user->getSatkerId();
            $pegawaiAktif = Pegawai::where('satuan_kerja_id', $satkerId)->get();
            $pegawaiExisting = $desa->pegawai;
            $pegawais = $pegawaiAktif->merge($pegawaiExisting)->unique('nip')->sortBy('nama');
        }

        $selectedPegawaiNips = $desa->pegawai->pluck('nip')->toArray();

        return view('p2m.desa-kelurahan-bersinar.edit', compact('desa', 'satuanKerjas', 'pegawais', 'kabupatens', 'selectedPegawaiNips'));
    }

    // --- UPDATE ---
    public function update(Request $request, DokumenService $dokumenService, $id) 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $desa = P2mDesaKelurahanBersinar::findOrFail($id);

        if ($user->hasRole(['operator_satker', 'operator_p2m']) && $desa->satuan_kerja_id !== $user->getSatkerId()) { abort(403); }

        $rules = [
            'anggaran_pembentukan' => 'required',
            'nama_desa_kelurahan' => 'required',
            'kabupaten_kota_id' => 'required|exists:kabupaten_kota,id',
            'tanggal_pencanangan' => 'required|date',
            'jumlah_penggiat' => 'required|numeric',
            'keberadaan_ibm' => 'required',
            'pegawai_nips' => 'required|array',
            'pegawai_nips.*' => 'exists:pegawai,nip',
            'delete_files' => 'nullable|array',
            'no_hp_penanggung_jawab' => 'nullable|string|max:20',
            'dokumentasi'          => 'nullable|array', 
            'lampiran'             => 'nullable|array',
            'dokumentasi_links'    => 'nullable|array',
            'dokumentasi_links.*.nama' => 'required_with:dokumentasi_links.*.url|nullable|string|max:255',
            'dokumentasi_links.*.url'  => 'required_with:dokumentasi_links.*.nama|nullable|url',
            'lampiran_links'       => 'nullable|array',
            'lampiran_links.*.nama' => 'required_with:lampiran_links.*.url|nullable|string|max:255',
            'lampiran_links.*.url'  => 'required_with:lampiran_links.*.nama|nullable|url',
        ];
        if ($user->isAdmin()) { $rules['satuan_kerja_id'] = 'required'; }

        $validasi = $request->validate($rules);
        $newFilesMoved = [];
        $filesToDelete = [];

        DB::beginTransaction();

        try {
            $pegawaiNips = $validasi['pegawai_nips'];
            $dataUpdate = collect($validasi)->except(['dokumentasi', 'pegawai_nips', 'delete_files', 'lampiran', 'dokumentasi_links', 'lampiran_links'])->toArray();

            if ($user->hasRole(['operator_satker', 'operator_p2m'])) { unset($dataUpdate['satuan_kerja_id']); }

            $desa->update($dataUpdate);

            $oldPivotData = DB::table('pegawai_p2m_desa_kelurahan_bersinar')->where('p2m_desa_kelurahan_bersinar_id', $id)->get()->keyBy('pegawai_nip');
            $masterPegawais = Pegawai::whereIn('nip', $pegawaiNips)->get()->keyBy('nip');
            $syncData = [];
            
            foreach ($pegawaiNips as $nip) {
                $satkerToSave = (isset($oldPivotData[$nip]) && $oldPivotData[$nip]->saved_satuan_kerja_id) 
                                ? $oldPivotData[$nip]->saved_satuan_kerja_id 
                                : ($masterPegawais[$nip]->satuan_kerja_id ?? null);
                
                $syncData[$nip] = ['saved_satuan_kerja_id' => $satkerToSave];
            }
            $desa->pegawai()->sync($syncData);

            if ($request->has('delete_files')) {
                $filesToRemove = Dokumen::whereIn('id', $request->delete_files)->get();
                foreach ($filesToRemove as $file) {
                    if (!$file->is_link) $filesToDelete[] = $file->path_file; 
                    $file->delete();
                }
            }

            if ($request->filled('dokumentasi')) {
                $dokumenService->moveToPermanent($request->input('dokumentasi'), $desa, 'dokumentasi', $newFilesMoved);
            }
            if ($request->filled('lampiran')) {
                $dokumenService->moveToPermanent($request->input('lampiran'), $desa, 'lampiran', $newFilesMoved);
            }
            if ($request->filled('dokumentasi_links')) {
                $dokumenService->saveLinks($request->input('dokumentasi_links'), $desa, 'dokumentasi');
            }
            if ($request->filled('lampiran_links')) {
                $dokumenService->saveLinks($request->input('lampiran_links'), $desa, 'lampiran');
            }

            DB::commit();

            foreach ($filesToDelete as $path) {
                if (Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
            }

            return redirect()->route('p2m.desa-kelurahan-bersinar.index')->with('success', 'update')->with('message', 'Data berhasil diperbarui');

        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($newFilesMoved as $path) {
                if (Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
            }
            Log::error('Update error: ' . $e->getMessage());
            abort(500, 'Server Error.');
        }
    }

    // --- DESTROY ---
    public function destroy($id) 
    {
        $kegiatan = P2mDesaKelurahanBersinar::findOrFail($id);
        
        $filesToDelete = [];
        
        // Loop dokumen, tapi filter isinya
        foreach ($kegiatan->dokumen()->cursor() as $doc) {
            // Cek 1: Pastikan bukan Link (karena link tidak punya file fisik)
            // Cek 2: Pastikan path_file TIDAK NULL dan TIDAK KOSONG
            if (!$doc->is_link && !empty($doc->path_file)) {
                $filesToDelete[] = $doc->path_file;
            }
        }

        DB::beginTransaction();
        try {
            $kegiatan->delete(); 
            DB::commit(); 
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'destroy')->with('message', 'Gagal menghapus data: ' . $e->getMessage());
        }

        // Hapus file fisik
        foreach ($filesToDelete as $path) {
            // Double check: Pastikan $path adalah string (bukan null) sebelum akses Storage
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        return redirect()->back()->with('success', 'destroy')->with('message', 'Data dan file berhasil dihapus.');
    }

}