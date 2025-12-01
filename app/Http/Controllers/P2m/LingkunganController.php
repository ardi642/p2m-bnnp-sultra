<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\P2mLingkungan;
use App\Models\SatuanKerja;
use Illuminate\View\View;
use Illuminate\Http\Request;

class LingkunganController extends Controller
{
    public function index(): View {
        $lingkungans = P2mLingkungan::all();
        return view('p2m.lingkungan.index', compact('lingkungans'));
    }

    public function create(): View {
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        return view('p2m.lingkungan.create', compact('satuanKerjas'));
    }

    public function store(Request $request) {
                    
        $validasi = $request->validate([
            'satuan_kerja_id' => 'required',
            'sasaran' => 'required',
            'nama_tempat' => 'required',
            'tanggal_pelaksanaan' => 'required',
            'jumlah_penggiat' => 'required',
            'nama_penanggungjawab' => 'required',
            'nomor_hp' => 'required',
            'link_kelengkapan_dokumentasi' => 'required'
        ]);

        P2mLingkungan::create($validasi);

        return redirect()->route('p2m.lingkungan.index')->with('status', 'success');
    }
}
