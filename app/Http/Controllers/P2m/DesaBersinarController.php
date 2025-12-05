<?php

namespace App\Http\Controllers\p2m;

use App\Models\DesaBersinar;
use App\Models\SatuanKerja;
use Illuminate\Http\Request;

class DesaBersinarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $desaBersinar = DesaBersinar::with('satuanKerja')
                        ->latest()
                        ->paginate(10);

        return view('p2m.desa-bersinar.index', compact('desaBersinar'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $satuanKerjas  = SatuanKerja::all();

        return view('p2m.desa-bersinar.create', compact('satuanKerjas'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    $validated = $request->validate([
        'satker' => 'required|exists:satuan_kerja,id',
        'anggaran_pembentukan' => 'required|string',

        'nama_desa' => 'required|string|max:255',
        'nama_kecamatan' => 'required|string|max:255',
        'nama_kota_kabupaten' => 'required|string|max:255',

        'tanggal_pencanangan' => 'required|date',
        'bulan_pelaksanaan' => 'required|string',

        'jumlah_penggiat_p4gn' => 'required|integer|min:0',
        'keberadaan_ibm' => 'required|string',

        'nama_penanggung_jawab' => 'required|string|max:255',
        'nomor_hp_penanggung_jawab' => 'required|string|max:20',

        'link_kelengkapan_dokumentasi' => 'nullable|string|max:500',
    ]);

    DesaBersinar::create([
        'satker' => $validated['satker'],
        'anggaran_pembentukan' => $validated['anggaran_pembentukan'],

        'nama_desa' => $validated['nama_desa'],
        'nama_kecamatan' => $validated['nama_kecamatan'],
        'nama_kota_kabupaten' => $validated['nama_kota_kabupaten'],

        'tanggal_pencanangan' => $validated['tanggal_pencanangan'],
        'bulan_pelaksanaan' => $validated['bulan_pelaksanaan'],

        'jumlah_penggiat_p4gn' => $validated['jumlah_penggiat_p4gn'],
        'keberadaan_ibm' => $validated['keberadaan_ibm'],

        'nama_penanggung_jawab' => $validated['nama_penanggung_jawab'],
        'nomor_hp_penanggung_jawab' => $validated['nomor_hp_penanggung_jawab'],

        'link_dokumentasi' => $validated['link_kelengkapan_dokumentasi'],
    ]);

    return redirect()->route('p2m.desabersinar.index')
                     ->with('success', 'Data Desa Bersinar berhasil ditambahkan.');
}


    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $desa = DesaBersinar::findOrFail($id);
        $satkers = SatuanKerja::all();

        return view('p2m.desabersinar.edit', compact('desa', 'satkers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'satuan_kerja_id' => 'required|exists:satuan_kerja,id',
            'nama_desa' => 'required|string|max:255',
            'nama_kecamatan' => 'required|string|max:255',
            'nama_kota' => 'required|string|max:255',
            'tanggal_pencanangan' => 'required|date',
            'bulan_pelaksanaan' => 'required|string|max:255',
            'jumlah_penggiat' => 'required|integer|min:0',
            'keberadaan_ibm' => 'required|in:Ada,Belum Ada',
            'penanggung_jawab' => 'required|string|max:255',
            'nomor_hp' => 'required|string|max:20',
            'link_dokumentasi' => 'required|string',
        ]);

        $desa = DesaBersinar::findOrFail($id);
        $desa->update($request->all());

        return redirect()->route('desa-bersinar.index')
            ->with('success', 'Data Desa Bersinar berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $desa = DesaBersinar::findOrFail($id);
        $desa->delete();

        return redirect()->route('desa-bersinar.index')
            ->with('success', 'Data Desa Bersinar berhasil dihapus!');
    }
}