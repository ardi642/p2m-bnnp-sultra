<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\P2mSosialisasi;
use App\Models\SatuanKerja;
use App\Models\Pegawai;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SosialisasiController extends Controller
{
    public function index(Request $request): View {
        
        // 1. DATA MASTER UNTUK FILTER
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();

        $pegawais = Pegawai::orderBy('nama', 'asc')->get(['nip', 'nama']);
        
        $years = P2mSosialisasi::selectRaw('YEAR(tanggal_pelaksanaan) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');
        
        // --- TENTUKAN TAHUN AKTIF (UNTUK DEFAULT QUERY) ---
        // Jika request tahun kosong, gunakan tahun saat ini sebagai nilai default query.
        $activeYears = $request->filled('tahun') ? $request->tahun : [date('Y')];

        // 2. MULAI QUERY DATA
        $query = P2mSosialisasi::with('pegawai', 'satuanKerja');

        // --- LOGIKA FILTERING (MULTIPLE) ---

        // A. Filter Satuan Kerja
        if ($request->filled('satuan_kerja_id')) {
            $query->whereIn('satuan_kerja_id', $request->satuan_kerja_id);
        }

        // B. Filter Bulan
        if ($request->filled('bulan')) {
            $query->where(function($q) use ($request) {
                foreach ($request->bulan as $b) {
                    $q->orWhereMonth('tanggal_pelaksanaan', $b);
                }
            });
        }
        
        // C. Filter TAHUN (Wajib ada, defaultnya tahun saat ini)
        $query->where(function($q) use ($activeYears) {
            foreach ($activeYears as $y) {
                $q->orWhereYear('tanggal_pelaksanaan', $y);
            }
        });

        // D. Filter Anggaran
        if ($request->filled('anggaran_pelaksanaan')) {
            $query->whereIn('anggaran_pelaksanaan', $request->anggaran_pelaksanaan);
        }

        // E. Filter Sasaran
        if ($request->filled('sasaran_kegiatan')) {
            $query->whereIn('sasaran_kegiatan', $request->sasaran_kegiatan);
        }

        if ($request->filled('pegawai_nips')) {
            $nips = $request->pegawai_nips;
            $logic = $request->input('pegawai_logic', 'OR'); // Default OR jika tidak ada

            if ($logic === 'AND') {
                // LOGIKA AND (Irisan/Intersection)
                // Loop setiap NIP, pastikan kegiatan memiliki relasi ke SETIAP NIP tersebut
                foreach ($nips as $nip) {
                    $query->whereHas('pegawai', function($q) use ($nip) {
                        $q->where('pegawai.nip', $nip);
                    });
                }
            } else {
                // LOGIKA OR (Gabungan/Union)
                // Cukup cek apakah kegiatan memiliki SALAH SATU dari NIP tersebut
                $query->whereHas('pegawai', function($q) use ($nips) {
                    $q->whereIn('pegawai.nip', $nips);
                });
            }
        }

        // --- LOGIKA PENCARIAN UMUM (LIKE QUERY) ---
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_kegiatan', 'LIKE', "%{$search}%")
                    ->orWhere('tempat_kegiatan', 'LIKE', "%{$search}%")
                    ->orWhere('sasaran_kegiatan', 'LIKE', "%{$search}%")
                    ->orWhereHas('satuanKerja', function($subQ) use ($search) {
                        $subQ->where('satuan_kerja', 'LIKE', "%{$search}%");
                    });
            });
        }

        // 3. EKSEKUSI
        $sosialisasis = $query->latest()
            ->paginate(10)
            ->withQueryString();
                        
        return view('p2m.sosialisasi.index', compact('sosialisasis', 'pegawais', 'satuanKerjas', 'years'));
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
