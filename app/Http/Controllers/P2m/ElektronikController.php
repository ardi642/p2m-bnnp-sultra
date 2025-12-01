<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\p2mElektronik;
use App\Models\SatuanKerja;
use Illuminate\View\View;
use Illuminate\Http\Request;

class ElektronikController extends Controller
{
    public function index(): View {
        $elektroniks = p2mElektronik::all();
        return view('p2m.elektronik.index', compact('elektroniks'));
    }

    public function create(): View {
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        return view('p2m.elektronik.create', compact('satuanKerjas'));
    }

    public function store(Request $request) {
                    
        $validasi = $request->validate([
            'satuan_kerja_id' => 'required',
            'anggaran_pelaksanaan' => 'required',
            'media' => 'required',
            'durasi_pelaksanaan' => 'required',
            'tanggal_pelaksanaan' => 'required',
            'nama_media' => 'required',
            'link_kelengkapan_dokumentasi' => 'required'            
        ]);

        p2mElektronik::create($validasi);

        return redirect()->route('p2m.elektronik.index')->with('status', 'success');
    }
}
