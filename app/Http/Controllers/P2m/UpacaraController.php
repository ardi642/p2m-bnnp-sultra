<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\P2mUpacara;
use App\Models\SatuanKerja;
use Illuminate\View\View;
use Illuminate\Http\Request;

class UpacaraController extends Controller
{
    public function index(): View {
        $upacaras = P2mUpacara::all();
        return view('p2m.upacara.index', compact('upacaras'));
    }

    public function create(): View {
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        return view('p2m.upacara.create', compact('satuanKerjas'));
    }

    public function store(Request $request) {
                    
        $validasi = $request->validate([
            'satuan_kerja_id' => 'required',
            'nama_sekolah' => 'required',
            'tanggal_pelaksanaan' => 'required',
            'nama_pegawai' => 'required',
            'jumlah_peserta' => 'required',
            'link_kelengkapan_dokumentasi' => 'required'
        ]);

        P2mUpacara::create($validasi);

        return redirect()->route('p2m.upacara.index')->with('status', 'success');
    }
}
