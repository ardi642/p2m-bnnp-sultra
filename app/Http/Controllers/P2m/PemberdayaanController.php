<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\P2mPemberdayaan;
use App\Models\SatuanKerja;
use App\Models\Pegawai;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Exports\PemberdayaanExport;
use App\Helpers\SearchHelper;
use App\Models\Dokumen;
use App\Services\DokumenService;
use App\Constants\KategoriPemberdayaan;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class PemberdayaanController extends Controller
{
    private function getFilteredQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $activeYears = $request->filled('tahun') ? $request->tahun : [date('Y')];

        $query = P2mPemberdayaan::with('pegawai.satuanKerja', 'satuanKerja');

        if ($user->hasRole('admin')) {
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('satuan_kerja_id', $request->satuan_kerja_id);
            }
        } else {
            $satkerId = $user->getSatkerId();
            $query->where('satuan_kerja_id', $satkerId);
        }

        if ($request->filled('bulan')) {
            $query->where(function ($q) use ($request) {
                foreach ($request->bulan as $b) {
                    $q->orWhereMonth('tanggal_pelaksanaan', $b);
                }
            });
        }

        $query->where(function ($q) use ($activeYears) {
            foreach ($activeYears as $y) {
                $q->orWhereYear('tanggal_pelaksanaan', $y);
            }
        });

        if ($request->filled('anggaran_pelaksanaan')) {
            $query->whereIn('anggaran_pelaksanaan', $request->anggaran_pelaksanaan);
        }

        if ($request->filled('sasaran_kegiatan')) {
            $query->whereIn('sasaran_kegiatan', $request->sasaran_kegiatan);
        }

        if ($request->filled('sub_kegiatan')) {
            $query->whereIn('sub_kegiatan', $request->sub_kegiatan);
        }

        if ($request->filled('detail_kegiatan')) {
            $query->whereIn('detail_kegiatan', $request->detail_kegiatan);
        }

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

        if ($request->filled('search')) {
            $search = $request->search;
            $searchDate = SearchHelper::translateDateInput($search);
            $query->where(function ($q) use ($search, $searchDate) {
                $q->where('nama_kegiatan', 'LIKE', "%{$search}%")
                    ->orWhere('tempat_kegiatan', 'LIKE', "%{$search}%")
                    ->orWhere('jumlah_peserta', 'LIKE', "%{$search}%");
                $q->orWhereRaw("LOWER(DATE_FORMAT(tanggal_pelaksanaan, '%W, %d %M %Y')) LIKE ?", ["%{$searchDate}%"]);
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $rawSortOrder = $request->input('sort_order', 'desc');
        $sortOrder = in_array(strtolower($rawSortOrder), ['asc', 'desc']) ? strtolower($rawSortOrder) : 'desc';

        $allowSort = ['anggaran_pelaksanaan', 'sub_kegiatan', 'detail_kegiatan', 'nama_kegiatan', 'sasaran_kegiatan', 'tanggal_pelaksanaan', 'tempat_kegiatan', 'jumlah_peserta', 'created_at', 'satuan_kerja'];

        if (in_array($sortBy, $allowSort)) {
            if ($sortBy === 'satuan_kerja') {
                $query->join('satuan_kerja', 'p2m_pemberdayaan.satuan_kerja_id', '=', 'satuan_kerja.id')
                    ->orderBy('satuan_kerja.satuan_kerja', $sortOrder)
                    ->select('p2m_pemberdayaan.*');
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

        $yearQuery = P2mPemberdayaan::selectRaw('YEAR(tanggal_pelaksanaan) as year');

        if ($user->hasRole(['operator_satker', 'operator_p2m'])) {
            $yearQuery->where('satuan_kerja_id', $user->getSatkerId());
        }

        $years = $yearQuery->distinct()->orderBy('year', 'desc')->pluck('year');
        $currentYear = (int) date('Y');
        
        if (!$years->contains($currentYear)) {
            $years->push($currentYear)->sortDesc()->values();
        }

        $query = $this->getFilteredQuery($request);

        $statsQuery = clone $query;
        $totalKegiatan = $statsQuery->count();
        $totalPeserta = $statsQuery->sum('jumlah_peserta');

        $query->with('dokumen');

        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : 10;
        $kegiatans = $query->paginate($perPage)->withQueryString();

        $satkerLookup = SatuanKerja::pluck('satuan_kerja', 'id')->toArray();

        // Data Master Constant untuk AlpineJS
        $subKegiatanList   = KategoriPemberdayaan::SUB_KEGIATAN;
        $detailKegiatanMap = KategoriPemberdayaan::DETAIL_KEGIATAN_MAP;
        $allDetailKegiatan = KategoriPemberdayaan::getAllDetailLabels();

        return view('p2m.pemberdayaan.index', compact(
            'kegiatans', 'satuanKerjas', 'years', 'pegawais', 'user', 
            'satkerLookup', 'totalKegiatan', 'totalPeserta',
            'subKegiatanList', 'detailKegiatanMap', 'allDetailKegiatan'
        ));
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

        $subKegiatanList   = KategoriPemberdayaan::SUB_KEGIATAN;
        $detailKegiatanMap = KategoriPemberdayaan::DETAIL_KEGIATAN_MAP;

        return view('p2m.pemberdayaan.create', compact('satuanKerjas', 'pegawais', 'subKegiatanList', 'detailKegiatanMap'));
    }

    public function store(Request $request, DokumenService $dokumenService)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $rules = [
            'anggaran_pelaksanaan' => 'required',
            'sub_kegiatan'         => ['required', Rule::in(array_keys(KategoriPemberdayaan::SUB_KEGIATAN))],
            'detail_kegiatan'      => ['required', Rule::in(KategoriPemberdayaan::getAllDetailKeys())],
            'nama_kegiatan'        => 'required',
            'sasaran_kegiatan'     => 'required',
            'tanggal_pelaksanaan'  => 'required|date',
            'tempat_kegiatan'      => 'required',
            'jumlah_peserta'       => 'required|numeric',
            'pegawai_nips'         => 'required|array',
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
            $rules['satuan_kerja_id'] = 'required';
        }

        $validasi = $request->validate($rules);

        // Validasi ekstra: pastikan detail sesuai dengan parent sub-nya
        $validDetails = KategoriPemberdayaan::DETAIL_KEGIATAN_MAP;
        if (!isset($validDetails[$validasi['sub_kegiatan']]) || !array_key_exists($validasi['detail_kegiatan'], $validDetails[$validasi['sub_kegiatan']])) {
            return back()->withErrors(['detail_kegiatan' => 'Detail tidak sesuai dengan sub kegiatan.'])->withInput();
        }

        $uploadedPaths = [];
        DB::beginTransaction();

        try {
            $dataKegiatan = collect($validasi)->except(['dokumentasi', 'lampiran', 'pegawai_nips', 'dokumentasi_links', 'lampiran_links'])->toArray();

            if ($user->hasRole(['operator_satker', 'operator_p2m'])) {
                $dataKegiatan['satuan_kerja_id'] = $user->getSatkerId();
            }

            $kegiatan = P2mPemberdayaan::create($dataKegiatan);

            $listPegawai = Pegawai::whereIn('nip', $validasi['pegawai_nips'])->get();

            foreach ($listPegawai as $pgw) {
                // Attach satu per satu. NIP tetap murni String.
                $kegiatan->pegawai()->attach($pgw->nip, [
                    'saved_satuan_kerja_id' => $pgw->satuan_kerja_id
                ]);
            }

            if ($request->filled('dokumentasi')) {
                $dokumenService->moveToPermanent($request->input('dokumentasi'), $kegiatan, 'dokumentasi', $uploadedPaths);
            }
            if ($request->filled('lampiran')) {
                $dokumenService->moveToPermanent($request->input('lampiran'), $kegiatan, 'lampiran', $uploadedPaths);
            }
            if ($request->filled('dokumentasi_links')) {
                $dokumenService->saveLinks($request->input('dokumentasi_links'), $kegiatan, 'dokumentasi');
            }
            if ($request->filled('lampiran_links')) {
                $dokumenService->saveLinks($request->input('lampiran_links'), $kegiatan, 'lampiran');
            }

            DB::commit();
            return redirect()->route('p2m.pemberdayaan.index')->with('success', 'store')->with('message', 'Berhasil menambahkan data.');
        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($uploadedPaths as $path) {
                Storage::disk(config('filesystems.default'))->delete($path);
            }
            Log::error('Gagal simpan: ' . $e->getMessage());
            abort(500, 'Server Error.');
        }
    }

    public function edit($id): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kegiatan = P2mPemberdayaan::with('pegawai', 'dokumen')->findOrFail($id);

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
        $subKegiatanList   = KategoriPemberdayaan::SUB_KEGIATAN;
        $detailKegiatanMap = KategoriPemberdayaan::DETAIL_KEGIATAN_MAP;

        return view('p2m.pemberdayaan.edit', compact('kegiatan', 'satuanKerjas', 'pegawais', 'selectedPegawaiNips', 'subKegiatanList', 'detailKegiatanMap'));
    }

    public function update(Request $request, DokumenService $dokumenService, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kegiatan = P2mPemberdayaan::findOrFail($id);

        if ($user->hasRole(['operator_satker', 'operator_p2m']) && $kegiatan->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403);
        }

        $rules = [
            'anggaran_pelaksanaan' => 'required',
            'sub_kegiatan'         => ['required', Rule::in(array_keys(KategoriPemberdayaan::SUB_KEGIATAN))],
            'detail_kegiatan'      => ['required', Rule::in(KategoriPemberdayaan::getAllDetailKeys())],
            'nama_kegiatan'        => 'required',
            'sasaran_kegiatan'     => 'required',
            'tanggal_pelaksanaan'  => 'required|date',
            'tempat_kegiatan'      => 'required',
            'jumlah_peserta'       => 'required|numeric',
            'pegawai_nips'         => 'required|array',
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

        if ($user->isAdmin()) $rules['satuan_kerja_id'] = 'required';

        $validasi = $request->validate($rules);

        $validDetails = KategoriPemberdayaan::DETAIL_KEGIATAN_MAP;
        if (!isset($validDetails[$validasi['sub_kegiatan']]) || !array_key_exists($validasi['detail_kegiatan'], $validDetails[$validasi['sub_kegiatan']])) {
            return back()->withErrors(['detail_kegiatan' => 'Detail tidak sesuai dengan sub kegiatan.'])->withInput();
        }

        $newFilesMoved = [];
        $filesToDelete = [];

        DB::beginTransaction();

        try {
            $dataUpdate = collect($validasi)
                ->except(['dokumentasi', 'lampiran', 'pegawai_nips', 'delete_files', 'dokumentasi_links', 'lampiran_links'])
                ->toArray();
            if ($user->hasRole(['operator_satker', 'operator_p2m'])) unset($dataUpdate['satuan_kerja_id']);

            $kegiatan->update($dataUpdate);

            $oldPivotData = DB::table('pegawai_p2m_pemberdayaan')->where('p2m_pemberdayaan_id', $id)->get()->keyBy('pegawai_nip');
            $masterPegawais = Pegawai::whereIn('nip', $validasi['pegawai_nips'])->get()->keyBy('nip');

            $kegiatan->pegawai()->detach();
            foreach ($validasi['pegawai_nips'] as $nip) {
                $satkerToSave = (isset($oldPivotData[$nip]) && $oldPivotData[$nip]->saved_satuan_kerja_id) ? $oldPivotData[$nip]->saved_satuan_kerja_id : ($masterPegawais[$nip]->satuan_kerja_id ?? null);
                $kegiatan->pegawai()->attach($nip, [
                    'saved_satuan_kerja_id' => $satkerToSave
                ]);
            }

            if ($request->has('delete_files')) {
                $filesToRemove = Dokumen::whereIn('id', $request->delete_files)->get();
                foreach ($filesToRemove as $file) {
                    if (!$file->is_link) $filesToDelete[] = $file->path_file;
                    $file->delete();
                }
            }

            if ($request->filled('dokumentasi')) {
                $dokumenService->moveToPermanent($request->input('dokumentasi'), $kegiatan, 'dokumentasi', $newFilesMoved);
            }
            if ($request->filled('lampiran')) {
                $dokumenService->moveToPermanent($request->input('lampiran'), $kegiatan, 'lampiran', $newFilesMoved);
            }
            if ($request->filled('dokumentasi_links')) {
                $dokumenService->saveLinks($request->input('dokumentasi_links'), $kegiatan, 'dokumentasi');
            }
            if ($request->filled('lampiran_links')) {
                $dokumenService->saveLinks($request->input('lampiran_links'), $kegiatan, 'lampiran');
            }

            DB::commit();

            foreach ($filesToDelete as $path) {
                if (Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
            }

            return redirect()->route('p2m.pemberdayaan.index')->with('success', 'update')->with('message', 'Data diperbarui');
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
        $kegiatan = P2mPemberdayaan::findOrFail($id);
        $filesToDelete = [];

        foreach ($kegiatan->dokumen()->cursor() as $doc) {
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

        foreach ($filesToDelete as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        return redirect()->back()->with('success', 'destroy')->with('message', 'Data dan file berhasil dihapus.');
    }

    public function export(Request $request)
    {
        $query = $this->getFilteredQuery($request);
        return Excel::download(new PemberdayaanExport($query), 'Laporan_P2M_Pemberdayaan.xlsx');
    }
}