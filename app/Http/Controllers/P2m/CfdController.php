<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\p2mcfd;
use App\Models\SatuanKerja;
use Illuminate\View\View;
use Illuminate\Http\Request;

class CfdController extends Controller
{
    public function index(): View {
        $cfds = P2mCfd::all();
        return view('p2m.cfd.index', compact('cfds'));
    }

    public function create(): View {
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        return view('p2m.cfd.create', compact('satuanKerjas'));
    }

    public function store(Request $request) {
                    
        $validasi = $request->validate([
            'satuan_kerja_id' => 'required',
            'tempat_kegiatan' => 'required',
            'tanggal_pelaksanaan' => 'required',
            'nama_pegawai' => 'required',
            'jumlah_peserta' => 'required',
            'link_kelengkapan_dokumentasi' => 'required'            
        ]);

        P2mCfd::create($validasi);

        return redirect()->route('p2m.cfd.index')->with('status', 'success');
    }
}
