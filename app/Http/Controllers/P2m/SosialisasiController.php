<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\P2mSosialisasi;
use App\Models\SatuanKerja;
use App\Models\Pegawai; // Import Model Pegawai
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Import DB untuk transaksi (opsional tapi bagus)

class SosialisasiController extends Controller
{
    public function index(): View {
        // Eager load 'pegawai' agar query lebih cepat saat menampilkan list
        $sosialisasis = P2mSosialisasi::with('pegawai', 'satuanKerja')
            ->latest()
            ->paginate(10);
                        
        return view('p2m.sosialisasi.index', compact('sosialisasis'));
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
