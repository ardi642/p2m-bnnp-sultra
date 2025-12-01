<?php

namespace App\Http\Controllers\P2m;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use App\Models\SafariReligi;
use App\Models\SatuanKerja;

class SafariReligiController extends Controller
{
    public function index()
    {
        $safarireligis = SafariReligi::with('namapegawai')->get();

        return view('p2m.safari-religi.index', compact('safarireligis'));
    }

    public function create() {
    $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
    $pegawais = Pegawai::orderBy('nama', 'asc')->get();
    return view('p2m.safari-religi.create', compact('satuanKerjas', 'pegawais'));
    }

    public function store(Request $request) {

        $request->validate([
            'satker' => 'required|exists:satuan_kerja,id',
            'tempat_kegiatan' => 'required|string|max:255',
            'tanggal_pelaksanaan' => 'required|date',
            'bulan_pelaksanaan' => 'required|string',
            'pegawai' => 'required|exists:pegawai,nip',
            'jumlah_masyarakat' => 'required|integer|min:1',
            'link_dokumentasi' => 'nullable|url|max:255',
        ]);


        SafariReligi::create([
            'satker' => $request->satker,
            'pegawai' => $request->pegawai,
            'tempat_kegiatan' => $request->tempat_kegiatan,
            'tanggal_pelaksanaan' => $request->tanggal_pelaksanaan,
            'bulan_pelaksanaan' => $request->bulan_pelaksanaan,
            'jumlah_masyarakat' => $request->jumlah_masyarakat,
            'link_dokumentasi' => $request->link_dokumentasi,
        ]);

        return redirect()->route('p2m.safarireligi.index')
                        ->with('success', 'Data safari religi berhasil ditambahkan.');
    }



}