<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\P2mNonElektronik;
use App\Models\SatuanKerja;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Exports\NonElektronikExport;
use App\Helpers\SearchHelper;
use App\Models\Dokumen;
use App\Models\TemporaryFile;
use App\Services\DokumenService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class NonElektronikController extends Controller
{
    private function getFilteredQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $activeYears = $request->filled('tahun') ? $request->tahun : [date('Y')];

        $query = P2mNonElektronik::with('satuanKerja');

        // Filter Satker
        if ($user->hasRole('admin')) {
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('satuan_kerja_id', $request->satuan_kerja_id);
            }
        } else {
            $query->where('satuan_kerja_id', $user->getSatkerId());
        }

        // Filter Bulan & Tahun
        if ($request->filled('bulan')) {
            $query->where(function ($q) use ($request) {
                foreach ($request->bulan as $b) {
                    $q->orWhereMonth('tanggal_mulai_pelaksanaan', $b);
                }
            });
        }

        $query->where(function ($q) use ($activeYears) {
            foreach ($activeYears as $y) {
                $q->orWhereYear('tanggal_mulai_pelaksanaan', $y);
            }
        });

        // Filter Lainnya
        if ($request->filled('anggaran_pelaksanaan')) {
            $query->whereIn('anggaran_pelaksanaan', $request->anggaran_pelaksanaan);
        }

        if ($request->filled('jenis_media')) {
            $query->whereIn('jenis_media', $request->jenis_media);
        }

        // Search Logic
        if ($request->filled('search')) {
            $search = $request->search;
            $searchDate = SearchHelper::translateDateInput($search);
            $query->where(function ($q) use ($search, $searchDate) {
                $q->where('tempat_pemasangan', 'LIKE', "%{$search}%")
                    ->orWhere('jenis_media', 'LIKE', "%{$search}%")
                    ->orWhere('anggaran_pelaksanaan', 'LIKE', "%{$search}%")
                    ->orWhere('durasi_pelaksanaan', 'LIKE', "%{$search}%")
                    ->orWhereHas('satuanKerja', function ($subQ) use ($search) {
                        $subQ->where('satuan_kerja', 'LIKE', "%{$search}%");
                    });
                $q->orWhereRaw("LOWER(DATE_FORMAT(tanggal_mulai_pelaksanaan, '%W, %d %M %Y')) LIKE ?", ["%{$searchDate}%"]);
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowSort = [
            'jenis_media',
            'tanggal_mulai_pelaksanaan',
            'durasi_pelaksanaan',
            'created_at',
            'satuan_kerja',
            'anggaran_pelaksanaan',
            'tempat_pemasangan'
        ];

        if (in_array($sortBy, $allowSort)) {
            if ($sortBy === 'satuan_kerja') {
                $query->join('satuan_kerja', 'p2m_non_elektronik.satuan_kerja_id', '=', 'satuan_kerja.id')
                    ->orderBy('satuan_kerja.satuan_kerja', $sortOrder)
                    ->select('p2m_non_elektronik.*');
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
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        } else {
            $satuanKerjas = [];
        }

        $yearQuery = P2mNonElektronik::selectRaw('YEAR(tanggal_mulai_pelaksanaan) as year');
        if ($user->hasRole(['operator_satker', 'operator_p2m'])) {
            $yearQuery->where('satuan_kerja_id', $user->getSatkerId());
        }
        $years = $yearQuery->distinct()->orderBy('year', 'desc')->pluck('year');

        $query = $this->getFilteredQuery($request);

        $statsQuery = clone $query;
        $totalKegiatan = $statsQuery->count();

        $query->with('dokumen');

        $perPage = $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $datas = $query->paginate($perPage)->withQueryString();
        $mediaOptions = P2mNonElektronik::getJenisMediaOptions();

        // PERBAIKAN: Nama View menggunakan 'non-elektronik'
        return view('p2m.non-elektronik.index', compact('datas', 'satuanKerjas', 'years', 'user', 'mediaOptions', 'totalKegiatan'));
    }

    public function export(Request $request)
    {
        $query = $this->getFilteredQuery($request);
        return Excel::download(new NonElektronikExport($query), 'Laporan_P2M_Non_Elektronik.xlsx');
    }

    public function create(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->isAdmin()) {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        } else {
            $satuanKerjas = [];
        }
        $mediaOptions = P2mNonElektronik::getJenisMediaOptions();

        // PERBAIKAN: Nama View menggunakan 'non-elektronik'
        return view('p2m.non-elektronik.create', compact('satuanKerjas', 'mediaOptions'));
    }

    public function store(Request $request, DokumenService $dokumenService)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $rules = [
            'anggaran_pelaksanaan'      => 'required',
            'jenis_media'               => 'required',
            'tempat_pemasangan'         => 'required',
            'tanggal_mulai_pelaksanaan' => 'required|date',
            'durasi_pelaksanaan'        => 'required|numeric|min:1',
            'dokumentasi'               => 'nullable|array',
            'lampiran'                  => 'nullable|array',
            'dokumentasi_links'         => 'nullable|array',
            'dokumentasi_links.*.nama'  => 'required_with:dokumentasi_links.*.url|nullable|string|max:255',
            'dokumentasi_links.*.url'   => 'required_with:dokumentasi_links.*.nama|nullable|url',
            'lampiran_links'            => 'nullable|array',
            'lampiran_links.*.nama'     => 'required_with:lampiran_links.*.url|nullable|string|max:255',
            'lampiran_links.*.url'      => 'required_with:lampiran_links.*.nama|nullable|url',
        ];

        if ($user->isAdmin()) {
            $rules['satuan_kerja_id'] = 'required';
        }

        $validasi = $request->validate($rules);
        $uploadedPaths = [];

        DB::beginTransaction();
        try {
            $dataInsert = collect($validasi)->except(['dokumentasi', 'lampiran', 'dokumentasi_links', 'lampiran_links'])->toArray();
            if ($user->hasRole(['operator_satker', 'operator_p2m'])) {
                $dataInsert['satuan_kerja_id'] = $user->getSatkerId();
            }

            $kegiatan = P2mNonElektronik::create($dataInsert);

            // Handle Upload File Dokumentasi
            if ($request->filled('dokumentasi')) {
                $dokumenService->moveToPermanent($request->input('dokumentasi'), $kegiatan, 'dokumentasi', $uploadedPaths);
            }

            // Handle Upload File Lampiran
            if ($request->filled('lampiran')) {
                $dokumenService->moveToPermanent($request->input('lampiran'), $kegiatan, 'lampiran', $uploadedPaths);
            }

            // Handle Link Eksternal Dokumentasi
            if ($request->filled('dokumentasi_links')) {
                $dokumenService->saveLinks($request->input('dokumentasi_links'), $kegiatan, 'dokumentasi');
            }

            // Handle Link Eksternal Lampiran
            if ($request->filled('lampiran_links')) {
                $dokumenService->saveLinks($request->input('lampiran_links'), $kegiatan, 'lampiran');
            }

            DB::commit();
            return redirect()->route('p2m.non-elektronik.index')->with('success', 'store')->with('message', 'Berhasil menambahkan data');
        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($uploadedPaths as $path) {
                if (Storage::disk(config('filesystems.default'))->exists($path)) {
                    Storage::disk(config('filesystems.default'))->delete($path);
                }
            }
            Log::error('Gagal simpan Non-Elektronik: ' . $e->getMessage());
            return back()->with('error', 'store')->with('message', 'Terjadi kesalahan saat menyimpan data.')->withInput();
        }
    }

    public function edit($id): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kegiatan = P2mNonElektronik::findOrFail($id);

        if ($user->hasRole(['operator_satker', 'operator_p2m']) && $kegiatan->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403);
        }

        if ($user->isAdmin()) {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        } else {
            $satuanKerjas = [];
        }
        $mediaOptions = P2mNonElektronik::getJenisMediaOptions();

        // PERBAIKAN: Nama View menggunakan 'non-elektronik'
        return view('p2m.non-elektronik.edit', compact('kegiatan', 'satuanKerjas', 'mediaOptions'));
    }

    public function update(Request $request, DokumenService $dokumenService, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kegiatan = P2mNonElektronik::findOrFail($id);

        if ($user->hasRole(['operator_satker', 'operator_p2m']) && $kegiatan->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403);
        }

        $rules = [
            'anggaran_pelaksanaan'      => 'required',
            'jenis_media'               => 'required',
            'tempat_pemasangan'         => 'required',
            'tanggal_mulai_pelaksanaan' => 'required|date',
            'durasi_pelaksanaan'        => 'required|numeric|min:1',
            'delete_files'              => 'nullable|array',
            'dokumentasi'               => 'nullable|array',
            'lampiran'                  => 'nullable|array',
            'dokumentasi_links'         => 'nullable|array',
            'dokumentasi_links.*.nama'  => 'required_with:dokumentasi_links.*.url|nullable|string|max:255',
            'dokumentasi_links.*.url'   => 'required_with:dokumentasi_links.*.nama|nullable|url',
            'lampiran_links'            => 'nullable|array',
            'lampiran_links.*.nama'     => 'required_with:lampiran_links.*.url|nullable|string|max:255',
            'lampiran_links.*.url'      => 'required_with:lampiran_links.*.nama|nullable|url',
        ];
        if ($user->isAdmin()) {
            $rules['satuan_kerja_id'] = 'required';
        }

        $validasi = $request->validate($rules);
        $uploadedPaths = [];

        DB::beginTransaction();
        try {
            $dataUpdate = collect($validasi)->except(['dokumentasi', 'lampiran', 'delete_files', 'dokumentasi_links', 'lampiran_links'])->toArray();
            if ($user->hasRole(['operator_satker', 'operator_p2m'])) {
                unset($dataUpdate['satuan_kerja_id']);
            }

            $kegiatan->update($dataUpdate);

            // Handle Delete Files
            if ($request->has('delete_files')) {
                $files = Dokumen::whereIn('id', $request->delete_files)->get();
                foreach ($files as $f) {
                    if ($f->path_file && Storage::disk('public')->exists($f->path_file)) {
                        Storage::disk('public')->delete($f->path_file);
                    }
                    $f->delete();
                }
            }

            // Handle Upload File Dokumentasi
            if ($request->filled('dokumentasi')) {
                $dokumenService->moveToPermanent($request->input('dokumentasi'), $kegiatan, 'dokumentasi', $uploadedPaths);
            }

            // Handle Upload File Lampiran
            if ($request->filled('lampiran')) {
                $dokumenService->moveToPermanent($request->input('lampiran'), $kegiatan, 'lampiran', $uploadedPaths);
            }

            // Handle Link Eksternal Dokumentasi
            if ($request->filled('dokumentasi_links')) {
                $dokumenService->saveLinks($request->input('dokumentasi_links'), $kegiatan, 'dokumentasi');
            }

            // Handle Link Eksternal Lampiran
            if ($request->filled('lampiran_links')) {
                $dokumenService->saveLinks($request->input('lampiran_links'), $kegiatan, 'lampiran');
            }

            DB::commit();
            return redirect()->route('p2m.non-elektronik.index')->with('success', 'update')->with('message', 'Data berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($uploadedPaths as $path) {
                if (Storage::disk(config('filesystems.default'))->exists($path)) {
                    Storage::disk(config('filesystems.default'))->delete($path);
                }
            }
            Log::error('Gagal update Non-Elektronik: ' . $e->getMessage());
            return back()->with('error', 'update')->with('message', 'Terjadi kesalahan saat memperbarui data.')->withInput();
        }
    }

    public function destroy($id)
    {
        $kegiatan = P2mNonElektronik::findOrFail($id);

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
