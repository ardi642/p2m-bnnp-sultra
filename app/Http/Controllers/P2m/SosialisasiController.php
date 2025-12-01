<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\P2mSosialisasi;
use App\Models\SatuanKerja;
use Illuminate\View\View;
use Illuminate\Http\Request;

class SosialisasiController extends Controller
{
    public function index(): View {
        $sosialisasis = P2mSosialisasi::all();
        return view('p2m.sosialisasi.index', compact('sosialisasis'));
    }

    public function create(): View {
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        return view('p2m.sosialisasi.create', compact('satuanKerjas'));
    }

    public function store(Request $request) {

        $validasi = $request->validate([
            'satuan_kerja_id' => 'required',
            'anggaran_pelaksanaan' => 'required',
            'nama_kegiatan' => 'required',
            'sasaran_kegiatan' => 'required',
            'tanggal_pelaksanaan' => 'required',
            'tempat_kegiatan' => 'required',
            'nama_pegawai' => 'required',
            'jumlah_peserta' => 'required',
            'link_kelengkapan_dokumentasi' => 'required'
        ]);

        P2mSosialisasi::create($validasi);

        return redirect()->route('p2m.sosialisasi.index')->with('status', 'success');
    }
}
