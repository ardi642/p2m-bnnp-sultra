<?php

namespace App\Http\Controllers;

use App\Models\TesUrin;
use App\Models\SatuanKerja;
use Illuminate\Http\Request;

class TesUrinController extends Controller
{
    public function index()
    {
        $tesUrins = TesUrin::with('satuanKerja')->latest()->paginate(10);
        return view('p2m.tes_urin.index', compact('tesUrins'));
    }

    public function create()
    {
        $satuanKerjas = SatuanKerja::all();
        return view('p2m.tes_urin.create', compact('satuanKerjas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'satker_id'                     => 'required|exists:satuan_kerja,id',
            'anggaran_pelaksanaan'          => 'required|in:DIPA,NON DIPA',
            'sasaran_kegiatan'              => 'required|in:Instansi Pemerintah,Lingkungan Pendidikan,Pekerja Swasta,Lingkungan Masyarakat',
            'nama_instansi_pelaksana'       => 'required|string',
            'tanggal_pelaksanaan'           => 'required|date',
            'nama_katim'                    => 'required|string',
            'link_kelengkapan_dokumentasi'  => 'required|string',
            'jumlah_peserta_test_urin'      => 'required|integer',
            'jumlah_terindikasi_positif'    => 'required|integer|min:0',
            'keterangan_parameter_positif'  => 'nullable|string',
        ]);

        TesUrin::create($validated);

        return redirect()->route('p2m.tesurine.index')->with('success', 'Data Tes Urin berhasil ditambahkan.');
    }

    public function show($id)
    {
        $tesUrin = TesUrin::findOrFail($id);
        return view('tesurin.show', compact('tesUrin'));
    }

    public function edit($id)
    {
        $tesUrin = TesUrin::findOrFail($id);
        $satuanKerjas = SatuanKerja::all();

        return view('tesurin.edit', compact('tesUrin', 'satuanKerjas'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'satker_id'                     => 'required|exists:satuan_kerja,id',
            'anggaran_pelaksanaan'          => 'required|in:DIPA,NON DIPA',
            'sasaran_kegiatan'              => 'required|in:Instansi Pemerintah,Lingkungan Pendidikan,Pekerja Swasta,Lingkungan Masyarakat',
            'nama_instansi_pelaksana'       => 'required|string',
            'tanggal_pelaksanaan'           => 'required|date',
            'nama_katim'                    => 'required|string',
            'link_kelengkapan_dokumentasi'  => 'required|string',
            'jumlah_peserta_test_urin'      => 'required|integer',
            'jumlah_terindikasi_positif'    => 'required|integer|min:0',
            'keterangan_parameter_positif'  => 'nullable|string',
        ]);

        TesUrin::findOrFail($id)->update($validated);

        return redirect()->route('p2m.tesurin.index')->with('success', 'Data Tes Urin berhasil diperbarui.');
    }

    public function destroy($id)
    {
        TesUrin::findOrFail($id)->delete();

        return redirect()->route('p2m.tesurin.index')->with('success', 'Data Tes Urin berhasil dihapus.');
    }
}