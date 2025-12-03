<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\DesaBersinar;
use App\Models\SatuanKerja;
use Illuminate\Http\Request;
use Carbon\Carbon;


class DesaBersinarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $desabersinars = DesaBersinar::all();
        return view('p2m.desa-bersinar.index', compact('desabersinars'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {
        $desabersinars = DesaBersinar::orderBy('satuan_kerja_id', 'asc')->get();
        $satuanKerjas  = SatuanKerja::orderBy('satuan_kerja')->get();
        return view('p2m.desa-bersinar.create', compact('desabersinars', 'satuanKerjas'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'satker'                       => 'required|exists:satuan_kerja,id',
            'anggaran_pembentukan'         => 'required|in:DIPA,NON DIPA',
            'nama_desa'                    => 'required|string|max:255',
            'nama_kecamatan'               => 'required|string|max:255',
            'kabupaten_kota'               => 'required|string|max:255',
            'tanggal_pencanangan'          => 'required|date',
            'jumlah_penggiat_p4gn'         => 'required|integer|min:0',
            'keberadaan_ibm'               => 'required|in:Ada,Belum Ada',
            'nama_penanggung_jawab'        => 'required|string|max:255',
            'nomor_hp_penanggung_jawab'    => 'required|string|max:20',
            'link_kelengkapan_dokumentasi' => 'nullable|string|max:255',
        ]);

        $bulan = Carbon::parse($request->tanggal_pencanangan)
                    ->locale('id')
                    ->translatedFormat('F');

        $bulan = strtoupper($bulan);

        DesaBersinar::create([
            'satuan_kerja_id' => $request->satker,
            'anggaran_pembentukan' => $request->anggaran_pembentukan,
            'nama_desa' => $request->nama_desa,
            'nama_kecamatan' => $request->nama_kecamatan,
            'kabupaten_kota' => $request->kabupaten_kota,
            'tanggal_pencanangan' => $request->tanggal_pencanangan,
            'bulan_pelaksanaan' => $bulan,
            'jumlah_penggiat_p4gn' => $request->jumlah_penggiat_p4gn,
            'keberadaan_ibm' => $request->keberadaan_ibm,
            'nama_penanggung_jawab' => $request->nama_penanggung_jawab,
            'nomor_hp_penanggung_jawab' => $request->nomor_hp_penanggung_jawab,
            'link_kelengkapan_dokumentasi' => $request->link_kelengkapan_dokumentasi,
        ]);


        return redirect()
            ->route('p2m.desabersinar.index')
            ->with('success', 'Data Desa Bersinar berhasil ditambahkan.');
    }


    /**
     * Display the specified resource.
     */
    public function show(DesaBersinar $desaBersinar)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DesaBersinar $desaBersinar)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DesaBersinar $desaBersinar)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DesaBersinar $desaBersinar)
    {
        //
    }
}
