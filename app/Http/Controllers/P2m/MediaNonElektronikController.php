<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\P2mMediaNonElektronik;
use App\Models\SatuanKerja;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Exports\MediaNonElektronikExport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class MediaNonElektronikController extends Controller
{
    private function getFilteredQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $activeYears = $request->filled('tahun') ? $request->tahun : [date('Y')];
        
        $query = P2mMediaNonElektronik::with('satuanKerja');

        // Filter Satker & Role
        if ($user->isAdmin()) {
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('satuan_kerja_id', $request->satuan_kerja_id);
            }
        } else if ($user->isOperator()){
            $satkerId = $user->getSatkerId();
            $query->where('satuan_kerja_id', $satkerId);
        }

        // Filter Bulan
        if ($request->filled('bulan')) {
            $query->where(function($q) use ($request) {
                foreach ($request->bulan as $b) {
                    $q->orWhereMonth('tanggal_pelaksanaan', $b);
                }
            });
        }

        // Filter Tahun
        $query->where(function($q) use ($activeYears) {
            foreach ($activeYears as $y) {
                $q->orWhereYear('tanggal_pelaksanaan', $y);
            }
        });

        // Filter Anggaran
        if ($request->filled('anggaran_pelaksanaan')) {
            $query->whereIn('anggaran_pelaksanaan', $request->anggaran_pelaksanaan);
        }

        // Filter Jenis Media
        if ($request->filled('jenis_media')) {
            $query->whereIn('jenis_media', $request->jenis_media);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('tempat_kegiatan', 'LIKE', "%{$search}%")
                    ->orWhereHas('satuanKerja', function($subQ) use ($search) {
                        $subQ->where('satuan_kerja', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowSort = ['anggaran_pelaksanaan', 'jenis_media', 'durasi_pelaksanaan', 'tanggal_pelaksanaan', 'tempat_kegiatan', 'created_at', 'satuan_kerja'];

        if (in_array($sortBy, $allowSort)) {
            if ($sortBy === 'satuan_kerja') {
                $query->join('satuan_kerja', 'p2m_media_non_elektronik.satuan_kerja_id', '=', 'satuan_kerja.id')
                        ->orderBy('satuan_kerja.satuan_kerja', $sortOrder)
                        ->select('p2m_media_non_elektronik.*');
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

        $yearsQuery = P2mMediaNonElektronik::selectRaw('YEAR(tanggal_pelaksanaan) as year')->distinct();

        if ($user->isAdmin()) {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        } else {
            $satuanKerjas = [];
            $yearsQuery->where('satuan_kerja_id', $user->getSatkerId());
        }
        
        $years = $yearsQuery->orderBy('year', 'desc')->pluck('year');

        $query = $this->getFilteredQuery($request);

        $perPage = $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100])) $perPage = 10;
        
        $media_non_elektroniks = $query->paginate($perPage)->withQueryString();
                        
        return view('p2m.media-non-elektronik.index', compact('media_non_elektroniks', 'satuanKerjas', 'years', 'user'));
    }

    public function export(Request $request) 
    {
        $query = $this->getFilteredQuery($request);
        return Excel::download(new MediaNonElektronikExport($query), 'Laporan_P2M_Media_Non_Elektronik.xlsx');
    }

    public function create(): View 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $satuanKerjas = $user->isAdmin() ? SatuanKerja::orderBy('satuan_kerja', 'asc')->get() : [];

        return view('p2m.media-non-elektronik.create', compact('satuanKerjas'));
    }

    public function store(Request $request) 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validasi = $request->validate([
            'anggaran_pelaksanaan' => 'required',
            'jenis_media' => 'required',
            'durasi_pelaksanaan' => 'required|numeric|min:1',
            'tanggal_pelaksanaan' => 'required|date',
            'tempat_kegiatan' => 'required',
            'link_kelengkapan_dokumentasi' => 'required',
        ]);

        if ($user->isAdmin()) {
            $request->validate(['satuan_kerja_id' => 'required']);
        }

        DB::transaction(function () use ($user, $request, $validasi) {
            $data = $validasi;
            if ($user->isAdmin()) {
                $data['satuan_kerja_id'] = $request->satuan_kerja_id;
            } else {
                $data['satuan_kerja_id'] = $user->getSatkerId();
            }
            P2mMediaNonElektronik::create($data);
        });

        return redirect()->route('p2m.media_non_elektronik.index')->with('success', 'store')->with('message', 'Berhasil menambahkan data');
    }

    public function edit($id): View 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $data = P2mMediaNonElektronik::findOrFail($id);

        if ($user->isOperator() && $data->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403);
        }

        $satuanKerjas = $user->isAdmin() ? SatuanKerja::orderBy('satuan_kerja', 'asc')->get() : [];

        return view('p2m.media-non-elektronik.edit', compact('data', 'satuanKerjas'));
    }

    public function update(Request $request, $id) 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kegiatan = P2mMediaNonElektronik::findOrFail($id);

        if ($user->isOperator() && $kegiatan->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403);
        }

        $validasi = $request->validate([
            'anggaran_pelaksanaan' => 'required',
            'jenis_media' => 'required',
            'durasi_pelaksanaan' => 'required|numeric|min:1',
            'tanggal_pelaksanaan' => 'required|date',
            'tempat_kegiatan' => 'required',
            'link_kelengkapan_dokumentasi' => 'required',
        ]);

        if ($user->isAdmin()) {
            $request->validate(['satuan_kerja_id' => 'required']);
        }

        DB::transaction(function () use ($validasi, $request, $kegiatan, $user) {
            $data = $validasi;
            if ($user->isAdmin()) {
                $data['satuan_kerja_id'] = $request->satuan_kerja_id;
            }
            $kegiatan->update($data);
        });

        return redirect()->route('p2m.media_non_elektronik.index')->with('success', 'update')->with('message', 'Data berhasil diperbarui');
    }

    public function destroy($id) 
    {
        $data = P2mMediaNonElektronik::findOrFail($id);
        $data->delete();
        return redirect()->back()->with('success', 'destroy')->with('message', 'Data berhasil dihapus');
    }
}