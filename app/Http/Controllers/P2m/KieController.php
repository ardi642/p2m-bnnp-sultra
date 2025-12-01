<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\P2mKie;
use App\Models\SatuanKerja;
use Illuminate\View\View;
use Illuminate\Http\Request;


class KieController extends Controller
{
    public function index(): View {
        $kies = P2mKie::all();
        return view('p2m.kie.index', compact('kies'));
    }

    public function create(): View {
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        return view('p2m.kie.create', compact('satuanKerjas'));
    }

    public function store(Request $request) {
                    
        $validasi = $request->validate([
            'satuan_kerja_id' => 'required',
            'tempat_kegiatan' => 'required',
            'tanggal_pelaksanaan' => 'required',
            'nama_pegawai' => 'required',
            'link_kelengkapan_dokumentasi' => 'required'
        ]);

        P2mKie::create($validasi);

        return redirect()->route('p2m.kie.index')->with('status', 'success');
    }
}
