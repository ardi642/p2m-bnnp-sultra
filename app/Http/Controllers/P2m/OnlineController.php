<?php

namespace App\Http\Controllers\P2m;
use App\Http\Controllers\Controller;
use App\Models\p2mOnline;
use App\Models\SatuanKerja;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OnlineController extends Controller
{
    public function index(): View {
          $onlines = p2mOnline::with('satuanKerja')
            ->latest()
            ->paginate(10);
        return view('p2m.online.index', compact('onlines'));
    }

    public function create(): View {
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        return view('p2m.online.create', compact('satuanKerjas'));
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
            p2mOnline::create($dataKegiatan);
        });


        
        return redirect()->route('p2m.online.index')
            ->with('success', 'store')
            ->with('message', 'Berhasil menambahkan data');

        // p2mElektronik::create($validasi);

        return redirect()->route('p2m.online.index')->with('status', 'success');
    }


    public function destroy($id) {
        $data = p2mOnline::findOrFail($id);

        $data->delete();

        return redirect()->back()
        ->with('success', 'destroy')
        ->with('message', 'Data berhasil dihapus');
    }




}
