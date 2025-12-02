<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\P2mSosialisasi;
use App\Models\SatuanKerja;
use App\Models\Pegawai;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Exports\SosialisasiExport; // Import Export Class
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel; // Import Facade Excel

class SosialisasiController extends Controller
{
    // 1. FUNGSI KHUSUS UNTUK BUILD QUERY (Re-usable)
    private function getFilteredQuery(Request $request)
    {
        $activeYears = $request->filled('tahun') ? $request->tahun : [date('Y')];
        
        $query = P2mSosialisasi::with('pegawai', 'satuanKerja');

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
        if ($request->filled('sasaran_kegiatan')) {
            $query->whereIn('sasaran_kegiatan', $request->sasaran_kegiatan);
        }
        
        // Filter Pegawai
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

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_kegiatan', 'LIKE', "%{$search}%")
                    ->orWhere('tempat_kegiatan', 'LIKE', "%{$search}%")
                    ->orWhere('sasaran_kegiatan', 'LIKE', "%{$search}%")
                    ->orWhereHas('satuanKerja', function($subQ) use ($search) {
                        $subQ->where('satuan_kerja', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('pegawai', function($subQ) use ($search) {
                        $subQ->where('nama', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowSort = ['anggaran_pelaksanaan', 'nama_kegiatan', 'sasaran_kegiatan', 'tanggal_pelaksanaan', 'tempat_kegiatan', 'jumlah_peserta', 'created_at', 'satuan_kerja'];

        if (in_array($sortBy, $allowSort)) {
            if ($sortBy === 'satuan_kerja') {
                $query->join('satuan_kerja', 'p2m_sosialisasi.satuan_kerja_id', '=', 'satuan_kerja.id')
                        ->orderBy('satuan_kerja.satuan_kerja', $sortOrder)
                        ->select('p2m_sosialisasi.*');
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
        $pegawais = Pegawai::orderBy('nama', 'asc')->get(['nip', 'nama']);
        $years = P2mSosialisasi::selectRaw('YEAR(tanggal_pelaksanaan) as year')->distinct()->orderBy('year', 'desc')->pluck('year');

        // Panggil fungsi query di atas
        $query = $this->getFilteredQuery($request);

        // Paginate untuk tampilan web
        $perPage = $request->input('per_page', 10);

        // 2. Validasi keamanan (agar user tidak iseng input angka 1000000 bikin server down)
        // Hanya izinkan angka: 10, 25, 50, 100
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        // 3. EKSEKUSI DENGAN ANGKA DINAMIS
        $sosialisasis = $query->paginate($perPage)->withQueryString();
                        
        return view('p2m.sosialisasi.index', compact('sosialisasis', 'satuanKerjas', 'years', 'pegawais'));
    }

    // 3. METHOD EXPORT (DOWNLOAD EXCEL)
    public function export(Request $request) 
    {
        // Panggil fungsi query yang SAMA PERSIS dengan index
        // Bedanya: Kita tidak pakai paginate(), tapi langsung lempar ke Class Export
        $query = $this->getFilteredQuery($request);

        return Excel::download(new SosialisasiExport($query), 'Laporan_P2M_Sosialisasi.xlsx');
    }

    public function create(): View {
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        
        // Ambil data pegawai untuk dropdown (urutkan nama a-z)
        $pegawais = Pegawai::with('satuanKerja')->orderBy('nama', 'asc')->get();

        return view('p2m.sosialisasi.create', compact('satuanKerjas', 'pegawais'));
    }

    public function store(Request $request) {
        
        // 1. Validasi Input
        $validasi = $request->validate([
            'satuan_kerja_id' => 'required',
            'anggaran_pelaksanaan' => 'required',
            'nama_kegiatan' => 'required',
            'sasaran_kegiatan' => 'required',
            'tanggal_pelaksanaan' => 'required|date',
            'tempat_kegiatan' => 'required',
            'jumlah_peserta' => 'required|numeric',
            'link_kelengkapan_dokumentasi' => 'required',
            
            // Validasi Array Pegawai (NIP)
            'pegawai_nips' => 'required|array', // Harus berbentuk array
            'pegawai_nips.*' => 'exists:pegawai,nip', // Pastikan NIP valid di DB
        ]);

        // Gunakan Database Transaction agar data aman (jika gagal simpan pivot, data utama batal)
        DB::transaction(function () use ($validasi) {
            
            // 2. Pisahkan data pegawai dari data utama
            // Kita hapus 'pegawai_nips' dari array validasi karena kolom ini tidak ada di tabel p2m_sosialisasi
            $dataKegiatan = collect($validasi)->except('pegawai_nips')->toArray();
            $pegawaiNips = $validasi['pegawai_nips'];

            // 3. Simpan Data Kegiatan (Tabel Utama)
            $kegiatan = P2mSosialisasi::create($dataKegiatan);

            // 4. Simpan Relasi Pegawai (Tabel Pivot)
            // Menggunakan method attach() untuk many-to-many
            $kegiatan->pegawai()->attach($pegawaiNips);
        });

        return redirect()->route('p2m.sosialisasi.index')
            ->with('success', 'store')
            ->with('message', 'Berhasil menambahkan data');
    }

    public function destroy($id) {
        $data = P2mSosialisasi::findOrFail($id);

        $data->delete();

        return redirect()->back()
        ->with('success', 'destroy')
        ->with('message', 'Data berhasil dihapus');
    }
}
