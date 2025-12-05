<?php

namespace App\Http\Controllers;

use App\Models\SafariReligi;
use App\Models\SatuanKerja;
use Illuminate\Http\Request;

class SafariReligiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $safarireligi = SafariReligi::with('satuan_kerja')->latest()->paginate(10);
        return view('p2m.safari-religi.index', compact('safarireligi'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $satuanKerjas = SatuanKerja::all();
        return view('p2m.safari-religi.create', compact('satuanKerjas'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'satker' => 'required|exists:satuan_kerja,id',
            'anggaran_pembentukan' => 'required',
            'nama_desa' => 'required|string',
            'nama_kecamatan' => 'required|string',
            'nama_kota_kabupaten' => 'required|string',
            'tanggal_pencanangan' => 'required|date',
            'bulan_pelaksanaan' => 'required|string',
            'jumlah_penggiat_p4gn' => 'required|integer',
            'keberadaan_ibm' => 'required|in:Ada,Belum Ada',
            'nama_penanggung_jawab' => 'required|string',
            'nomor_hp_penanggung_jawab' => 'required|string',
            'link_kelengkapan_dokumentasi' => 'required|string',
        ]);

        SafariReligi::create($request->all());

        return redirect()->route('p2m.safarireligi.index')
                         ->with('success', 'Data Safari Religi berhasil ditambahkan!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $item = SafariReligi::findOrFail($id);
        $satkers = SatuanKerja::all();

        return view('p2m.safarireligi.edit', compact('item', 'satkers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'satker_id' => 'required|exists:satuan_kerja,id',
            'anggaran_pembentukan' => 'required',
            'anggaran_dipa' => 'nullable|boolean',
            'anggaran_non_dipa' => 'nullable|boolean',
            'nama_desa' => 'required|string',
            'nama_kecamatan' => 'required|string',
            'nama_kota_kabupaten' => 'required|string',
            'tanggal_pencanangan' => 'required|date',
            'bulan_pelaksanaan' => 'required|string',
            'jumlah_penggiat_p4gn' => 'required|integer',
            'keberadaan_ibm' => 'required|in:Ada,Belum Ada',
            'nama_penanggung_jawab' => 'required|string',
            'nomor_hp_penanggung_jawab' => 'required|string',
            'link_kelengkapan_dokumentasi' => 'required|string',
        ]);

        $item = SafariReligi::findOrFail($id);
        $item->update($request->all());

        return redirect()->route('p2m.safarireligi.index')
                         ->with('success', 'Data Safari Religi berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $item = SafariReligi::findOrFail($id);
        $item->delete();

        return redirect()->route('p2m.safarireligi.index')
                         ->with('success', 'Data Safari Religi berhasil dihapus!');
    }
}
