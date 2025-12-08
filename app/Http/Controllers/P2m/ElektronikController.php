<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\p2mElektronik;
use App\Models\Pegawai;
use App\Models\SatuanKerja;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use Maatwebsite\Excel\Facades\Excel; // Import Facade Excel
use App\Exports\ElektronikExport;

class ElektronikController extends Controller
{
   
  // 1. FUNGSI KHUSUS UNTUK BUILD QUERY (Re-usable)
    private function getFilteredQuery(Request $request)
    {
        $activeYears = $request->filled('tahun') ? $request->tahun : [date('Y')];
        
        $query = p2mElektronik::with('satuanKerja');

        // --- FILTER SAMA PERSIS SEPERTI SEBELUMNYA ---
        if ($request->filled('satuan_kerja_id')) {
            $query->whereIn('satuan_kerja_id', $request->satuan_kerja_id);
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
        if ($request->filled('Media')) {
            $query->whereIn('Media', $request->Media);
        }
            


        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_media', 'LIKE', "%{$search}%")
                    ->orWhere('Media', 'LIKE', "%{$search}%")
                    ->orWhere('tanggal_pelaksanaan', 'LIKE', "%{$search}%")
                    ->orWhere('durasi_pelaksanaan', 'LIKE', "%{$search}%")
                    ->orWhereHas('satuanKerja', function($subQ) use ($search) {
                        $subQ->where('satuan_kerja', 'LIKE', "%{$search}%");
                    });
            });
        }

       

         // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowSort = ['anggaran_pelaksanaan', 'Media', 'nama_media', 'tanggal_pelaksanaan', 'durasi_pelaksanaan', 'created_at', 'satuan_kerja'];

        if (in_array($sortBy, $allowSort)) {
            if ($sortBy === 'satuan_kerja') {
                $query->join('satuan_kerja', 'p2m_elektronik.satuan_kerja_id', '=', 'satuan_kerja.id')
                        ->orderBy('satuan_kerja.satuan_kerja', $sortOrder)
                        ->select('p2m_elektronik.*');
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
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        $years = p2mElektronik::selectRaw('YEAR(tanggal_pelaksanaan) as year')->distinct()->orderBy('year', 'desc')->pluck('year');

        $query = $this->getFilteredQuery($request);

        $perPage = $request->input('per_page', 10);
        
        // Validasi keamanan (agar user tidak iseng input angka 1000000 bikin server down)
        // Hanya izinkan angka: 10, 25, 50, 100
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }
        $elektroniks = $query->paginate($perPage)->withQueryString();
                        
        return view('p2m.elektronik.index', compact('elektroniks', 'satuanKerjas', 'years'));
    }

    public function create(): View {
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        // Ambil data pegawai untuk dropdown (urutkan nama a-z)
             
        return view('p2m.elektronik.create', compact('satuanKerjas'));
    }


      // 3. METHOD EXPORT (DOWNLOAD EXCEL)
    public function export(Request $request) 
    {
        // Panggil fungsi query yang SAMA PERSIS dengan index
        // Bedanya: Kita tidak pakai paginate(), tapi langsung lempar ke Class Export
        $query = $this->getFilteredQuery($request);

        return Excel::download(new ElektronikExport($query), 'Laporan_P2M_Media Elektronik.xlsx');
    }

    public function store(Request $request) {
                    
        $validasi = $request->validate([
            'satuan_kerja_id' => 'required',
            'anggaran_pelaksanaan' => 'required',
            'media' => 'required',
            'durasi_pelaksanaan' => 'required',
            'tanggal_pelaksanaan' => 'required',
            'nama_media' => 'required',
            'link_kelengkapan_dokumentasi' => 'required' ,  
        ]);

         // Gunakan Database Transaction agar data aman (jika gagal simpan pivot, data utama batal)
        DB::transaction(function () use ($validasi) {
            
            // 2. Pisahkan data pegawai dari data utama
            // Kita hapus 'pegawai_nips' dari array validasi karena kolom ini tidak ada di tabel p2m_sosialisasi
            $dataKegiatan = collect($validasi)->toArray();
                      // 3. Simpan Data Kegiatan (Tabel Utama)
            p2mElektronik::create($dataKegiatan);
        });



        return redirect()->route('p2m.elektronik.index')
            ->with('success', 'store')
            ->with('message', 'Berhasil menambahkan data');

        // p2mElektronik::create($validasi);

        return redirect()->route('p2m.elektronik.index')->with('status', 'success');
    }

 public function destroy($id) {
        $data = p2mElektronik::findOrFail($id);

        $data->delete();

        return redirect()->back()
        ->with('success', 'destroy')
        ->with('message', 'Data berhasil dihapus');
    }


}
