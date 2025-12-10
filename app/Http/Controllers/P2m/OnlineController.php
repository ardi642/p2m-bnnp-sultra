<?php

namespace App\Http\Controllers\P2m;
use App\Models\p2mOnline;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\SatuanKerja;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OnlineExport;

class OnlineController extends Controller
{
    // 1. FUNGSI KHUSUS UNTUK BUILD QUERY (Re-usable)
    private function getFilteredQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $activeYears = $request->filled('tahun') ? $request->tahun : [date('Y')];       
        $query = p2mOnline::with('satuanKerja');

        // --- FILTER SAMA PERSIS SEPERTI SEBELUMNYA ---

        if ($user->isAdmin()) {
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('satuan_kerja_id', $request->satuan_kerja_id);
            }
        }
        else if ($user->isOperator()){
            $satkerId = $user->getSatkerId();
            $query->where('satuan_kerja_id', $satkerId);
        }

        if ($request->filled('bulan')) {
            $query->where(function($q) use ($request) {
                foreach ($request->bulan as $b) {
                    $q->orWhereMonth('tanggal_pelaksanaan', $b);
                }
            });
        }
        $query->where(function($q) use ($activeYears) {
            foreach ($activeYears as $y) {
                $q->orWhereYear('tanggal_pelaksanaan', $y);
            }
        });
        if ($request->filled('anggaran_pelaksanaan')) {
            $query->whereIn('anggaran_pelaksanaan', $request->anggaran_pelaksanaan);
        }
         if ($request->filled('media')) {
            $query->whereIn('media', $request->media);
        }

         if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_media', 'LIKE', "%{$search}%")
                    ->orWhere('media', 'LIKE', "%{$search}%")
                    ->orWhere('anggaran_pelaksanaan', 'LIKE', "%{$search}%")
                    ->orWhere('tanggal_pelaksanaan', 'LIKE', "%{$search}%")
                    ->orWhere('durasi_pelaksanaan', 'LIKE', "%{$search}%")
                    ->orWhereHas('satuanKerja', function($subQ) use ($search) {
                        $subQ->where('satuan_kerja', 'LIKE', "%{$search}%");
                    });
            });
        }
    
    
     $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowSort = ['anggaran_pelaksanaan', 'media', 'nama_media', 'tanggal_pelaksanaan', 'durasi_pelaksanaan', 'created_at', 'satuan_kerja'];

        if (in_array($sortBy, $allowSort)) {
            if ($sortBy === 'satuan_kerja') {
                $query->join('satuan_kerja', 'p2m_online.satuan_kerja_id', '=', 'satuan_kerja.id')
                        ->orderBy('satuan_kerja.satuan_kerja', $sortOrder)
                        ->select('p2m_online.*');
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }
        } else {
            $query->latest();
        }

        return $query;
    }

    public function index(Request $request): View {
        // Data Master

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->isAdmin()) {
           
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        }
        else if ($user->isOperator()) {
            $satkerId = $user->getSatkerId();
            $satuanKerjas = [];
        }

        $years = p2mOnline::selectRaw('YEAR(tanggal_pelaksanaan) as year')->distinct()->orderBy('year', 'desc')->pluck('year');
        $query = $this->getFilteredQuery($request);
        $perPage = $request->input('per_page', 10);
        
        // Validasi keamanan (agar user tidak iseng input angka 1000000 bikin server down)
        // Hanya izinkan angka: 10, 25, 50, 100
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }
        $onlines = $query->paginate($perPage)->withQueryString();
                        
        return view('p2m.online.index', compact('onlines', 'satuanKerjas', 'years','user'));
    }

    // 3. METHOD EXPORT (DOWNLOAD EXCEL)
    public function export(Request $request) 
    {
        // Panggil fungsi query yang SAMA PERSIS dengan index
        // Bedanya: Kita tidak pakai paginate(), tapi langsung lempar ke Class Export
        $query = $this->getFilteredQuery($request);

        return Excel::download(new OnlineExport($query), 'Laporan_P2M_Media Online.xlsx');
    }

    public function create(): View {

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->isAdmin()) {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        }
        else if ($user->isOperator()){
            $satuanKerjas = [];
            $satkerId = $user->getSatkerId();
            
        }

        return view('p2m.online.create', compact('satuanKerjas'));
    }

    public function store(Request $request) {
        
        /** @var \App\Models\User $user */
        $user = Auth::user();
        // 1. Validasi Input
        $validasi = $request->validate([
            'anggaran_pelaksanaan' => 'required',
            'media' => 'required',
            'durasi_pelaksanaan' => 'required',
            'tanggal_pelaksanaan' => 'required',
            'nama_media' => 'required',
            'link_kelengkapan_dokumentasi' => 'required' ,
        ]);

        if ($user->isAdmin()) {
            $rules['satuan_kerja_id'] = 'required';
        }

        // Gunakan Database Transaction agar data aman (jika gagal simpan pivot, data utama batal)
        DB::transaction(function () use ($user, $validasi) {
            
            $dataKegiatan = collect($validasi)->toArray();
           
            if ($user->isOperator()) {
                $dataKegiatan['satuan_kerja_id'] = $user->getSatkerId();
            }

            // 3. Simpan Data Kegiatan (Tabel Utama)
            p2mOnline::create($dataKegiatan);

        });

        return redirect()->route('p2m.online.index')
            ->with('success', 'store')
            ->with('message', 'Berhasil menambahkan data');
    }

    public function edit($id): View 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Ambil Data Kegiatan beserta relasi Pegawai (untuk pre-fill input)
        $kegiatan = p2mOnline::findOrFail($id);
        // Proteksi Hak Akses
        // Jika Operator mencoba edit data milik Satker lain -> 403 Forbidden
        if ($user->isOperator() && $kegiatan->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403, 'Anda tidak berhak mengubah data Satuan Kerja lain.');
        }

        // Siapkan Data Master (Logic sama seperti Create)
        if ($user->isAdmin()) {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        } 
        else {
            $satuanKerjas = []; // Tidak dipakai di view operator
            $satkerId = $user->getSatkerId();
        }

        return view('p2m.online.edit', compact('kegiatan', 'satuanKerjas'));
    }

    public function update(Request $request, $id) 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kegiatan = p2mOnline::findOrFail($id);

        // Proteksi Update
        if ($user->isOperator() && $kegiatan->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403);
        }

        // Validasi
        $rules = [
            'anggaran_pelaksanaan' => 'required',
            'media' => 'required',
            'durasi_pelaksanaan' => 'required',
            'tanggal_pelaksanaan' => 'required',
            'nama_media' => 'required',
            'link_kelengkapan_dokumentasi' => 'required' ,
        ];

        // Jika Admin edit, validasi satker. Jika Operator, abaikan (pakai data lama)
        if ($user->isAdmin()) {
            $rules['satuan_kerja_id'] = 'required';
        }

        $validasi = $request->validate($rules);

        DB::transaction(function () use ($validasi, $kegiatan, $user) {
            
            $dataUpdate = collect($validasi)->toArray();

            // PENTING: Untuk Operator, JANGAN update satuan_kerja_id (biarkan yang lama)
            // Untuk Admin, update sesuai input form
            if ($user->isOperator()) {
                unset($dataUpdate['satuan_kerja_id']); 
            }

            // Update Data Utama
            $kegiatan->update($dataUpdate);

        });

        return redirect()->route('p2m.online.index')
            ->with('success', 'update') // Ubah wording session di JS index jika perlu
            ->with('message', 'Data berhasil diperbarui');
    }



    public function destroy($id) {
        $data = p2mOnline::findOrFail($id);

        $data->delete();

        return redirect()->back()
        ->with('success', 'destroy')
        ->with('message', 'Data berhasil dihapus');
    }
}
