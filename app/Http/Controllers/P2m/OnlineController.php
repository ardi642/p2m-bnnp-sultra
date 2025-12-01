<?php

namespace App\Http\Controllers\P2m;
use App\Http\Controllers\Controller;
use App\Models\p2mOnline;
use App\Models\SatuanKerja;
use Illuminate\View\View;
use Illuminate\Http\Request;

class OnlineController extends Controller
{
    public function index(): View {
        $onlines = p2mOnline::all();
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
            'link_kelengkapan_dokumentasi' => 'required'            
        ]);

        p2mOnline::create($validasi);

        return redirect()->route('p2m.online.index')->with('status', 'success');
    }
}
