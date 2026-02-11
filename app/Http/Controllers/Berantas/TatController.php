<?php

namespace App\Http\Controllers\Berantas;

use App\Http\Controllers\Controller;
use App\Models\BerantasTat;
use App\Models\BerantasNarkotika;
use App\Models\SatuanKerja;
use App\Models\TemporaryFile;
use App\Models\DokumentasiKegiatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TatExport;
use App\Models\BerantasTatBarangBukti;
use App\Models\BerantasTatTersangka;
use App\Models\Dokumen;
use App\Services\DokumenService;
use Illuminate\Support\Facades\Log;

class TatController extends Controller
{
    private function applyCaseFilter($query, Request $request)
    {
        if (!$request->filled('kategori_bb')) return $query;

        $kategori = (array)$request->kategori_bb;

        return $query->where(function($q) use ($kategori, $request) {
            if (in_array('Narkotika', $kategori)) {
                $q->orWhere(function($sub) use ($request) {
                    $sub->where('kategori', 'Narkotika');
                    if ($request->filled('narkotika_ids')) {
                        $sub->whereIn('narkotika_id', (array)$request->narkotika_ids);
                    }
                });
            }
            if (in_array('Non-Narkotika', $kategori)) {
                $q->orWhere(function($sub) use ($request) {
                    $sub->where('kategori', 'Non-Narkotika');
                    if ($request->filled('search_non_narkotika')) {
                        $keywords = (array)$request->search_non_narkotika;
                        $sub->where(function($kQ) use ($keywords) {
                            foreach ($keywords as $key) {
                                $kQ->orWhere('nama_barang_non_narkotika', 'LIKE', "%{$key}%");
                            }
                        });
                    }
                });
            }
        });
    }

    private function getFilteredQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $query = BerantasTat::with([
            'satuanKerja', 
            'tersangka', 
            'barangBukti' => function($q) use ($request) {
                if ($request->filled('kategori_bb')) {
                    $q->whereIn('kategori', (array)$request->kategori_bb);
                }
            },
            'barangBukti.narkotika', 
            'dokumen'
        ]);

        if (!$user->hasRole('admin')) {
            $query->where('satuan_kerja_id', $user->getSatkerId());
        } else {
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('satuan_kerja_id', (array)$request->satuan_kerja_id);
            }
        }

        if ($request->filled('bulan')) {
            $query->whereIn(DB::raw('MONTH(tanggal_pelaksanaan)'), (array)$request->bulan);
        }
        $years = $request->filled('tahun') ? (array)$request->tahun : [date('Y')];
        $query->whereIn(DB::raw('YEAR(tanggal_pelaksanaan)'), $years);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('no_register', 'LIKE', "%{$s}%")
                  ->orWhere('instansi_pengirim', 'LIKE', "%{$s}%")
                  ->orWhere('pasal_disangkakan', 'LIKE', "%{$s}%")
                  ->orWhereHas('tersangka', function($sq) use ($s) {
                      $sq->where('nama_tersangka', 'LIKE', "%{$s}%");
                  });
            });
        }

        if ($request->filled('kategori_bb')) {
            $query->whereHas('barangBukti', function($q) use ($request) {
                $this->applyCaseFilter($q, $request);
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSorts = ['no_register', 'satuan_kerja_id', 'created_at', 'tanggal_pelaksanaan'];
        
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    public function index(Request $request)
    {
        $years = BerantasTat::selectRaw('YEAR(tanggal_pelaksanaan) as year')
            ->distinct()->orderBy('year', 'desc')->pluck('year');
        
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja')->get();
        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika')->get();

        $query = $this->getFilteredQuery($request);

        // LOGIKA AGREGAT EFISIEN (LEVEL DATABASE)
        // Buat "Resep" Subquery (Hanya instruksi, tidak memakan RAM)
        $tatIdSubquery = (clone $query)->select('berantas_tat.id');

        // Hitung Total Kasus TAT
        $totalKasus = (clone $query)->count();

        // Hitung Total Tersangka TAT menggunakan Subquery
        $totalTersangka = BerantasTatTersangka::whereIn('berantas_tat_id', $tatIdSubquery)->count();

        // Hitung Total Jenis Barang Bukti Narkotika pada TAT
        $totalBBNarkotika = BerantasTatBarangBukti::whereIn('berantas_tat_id', $tatIdSubquery)
                            ->where('kategori', 'Narkotika')
                            ->count();

        // Hitung Total Berat Narkotika (Konversi Otomatis ke Gram di level DB)
        // Note: Kita asumsikan input satuan adalah 'Gram', 'Kg', atau 'Ton'
        $totalBeratGram = BerantasTatBarangBukti::whereIn('berantas_tat_id', $tatIdSubquery)
                            ->where('kategori', 'Narkotika')
                            ->selectRaw("SUM(CASE 
                                WHEN satuan = 'Kg' THEN kuantitas * 1000 
                                WHEN satuan = 'Ton' THEN kuantitas * 1000000 
                                ELSE kuantitas 
                            END) as total")
                            ->value('total') ?? 0;

        $perPage = $request->input('per_page', 10);
        
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $data = $query->paginate($perPage)->withQueryString();

        return view('berantas.tat.index', compact(
            'data', 'satuanKerjas', 'years', 'masterNarkotika',
            'totalKasus', 'totalTersangka', 'totalBBNarkotika', 'totalBeratGram'
        ));
    }

    public function create()
    {
        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika')->get();
        $satuanKerjas = SatuanKerja::all();
        return view('berantas.tat.create', compact('masterNarkotika', 'satuanKerjas'));
    }

    public function store(Request $request, DokumenService $dokumenService)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $messages = [
            'tersangka.*.nama.required' => 'Nama tersangka wajib diisi.',
            'barang_bukti.*.jumlah.required' => 'Jumlah/Berat BB wajib diisi.',
            'barang_bukti.*.satuan.required' => 'Satuan BB wajib diisi.',
        ];

        $validator = Validator::make($request->all(), [
            'no_register'         => 'required|unique:berantas_tat',
            'tanggal_pelaksanaan' => 'required|date',
            'tersangka'           => 'required|array|min:1',
            'barang_bukti'        => 'required|array|min:1',
            'tersangka.*.nama'    => 'required|string',
            'tersangka.*.nik'     => 'required|numeric',
            'tersangka.*.jk'      => 'required|in:Laki-laki,Perempuan',
            'tersangka.*.usia'    => 'required|numeric|min:0',
            'tersangka.*.pendidikan' => 'required|string',
            'tersangka.*.pekerjaan'  => 'required|string',
            'tersangka.*.no_telepon' => 'required|string',
            'barang_bukti.*.kategori' => 'required|in:Narkotika,Non-Narkotika',
            'barang_bukti.*.jumlah'   => 'required|numeric|min:0',
            'barang_bukti.*.satuan'   => 'required|string',
            'pasal_disangkakan'   => 'nullable|string',
            'biaya'               => 'nullable|numeric|min:0',

            'dokumentasi'          => 'nullable|array', 
            'lampiran'             => 'nullable|array',
            'dokumentasi_links'    => 'nullable|array',
            'dokumentasi_links.*.nama' => 'required_with:dokumentasi_links.*.url|nullable|string|max:255',
            'dokumentasi_links.*.url'  => 'required_with:dokumentasi_links.*.nama|nullable|url',
            'lampiran_links'       => 'nullable|array',
            'lampiran_links.*.nama' => 'required_with:lampiran_links.*.url|nullable|string|max:255',
            'lampiran_links.*.url'  => 'required_with:lampiran_links.*.nama|nullable|url',
        ], $messages);

        // VALIDASI SINGLE VALUE
        $validator->after(function ($validator) use ($request) {
            if ($request->has('barang_bukti')) {
                foreach ($request->barang_bukti as $index => $bb) {
                    // Cek narkotika_id (Single)
                    if ($bb['kategori'] === 'Narkotika' && empty($bb['narkotika_id'])) {
                        $validator->errors()->add("barang_bukti.$index.narkotika_id", "Pilih jenis narkotika.");
                    }
                    if ($bb['kategori'] === 'Non-Narkotika' && empty($bb['nama_barang_bukti'])) {
                        $validator->errors()->add("barang_bukti.$index.nama_barang_bukti", "Nama barang wajib diisi.");
                    }
                }
            }
        });

        if ($validator->fails()) return back()->withErrors($validator)->withInput();

        $uploadedPaths = [];

        DB::beginTransaction();
        try {
            $data = $request->except(['tersangka', 'barang_bukti', 'dokumentasi', 'lampiran', 'dokumentasi_links', 'lampiran_links']);
            $data['satuan_kerja_id'] = $user->isAdmin() ? $request->satuan_kerja_id : $user->getSatkerId();
            $tat = BerantasTat::create($data);

            foreach ($request->tersangka as $t) {
                $tat->tersangka()->create([
                    'nama_tersangka' => $t['nama'],
                    'nik'            => $t['nik'],
                    'jenis_kelamin'  => $t['jk'],
                    'usia'           => $t['usia'],
                    'pendidikan'     => $t['pendidikan'],
                    'pekerjaan'      => $t['pekerjaan'],
                    'no_telepon'     => $t['no_telepon'],
                ]);
            }

            // SIMPAN BB (Single Row Logic)
            foreach ($request->barang_bukti as $bbRow) {
                $tat->barangBukti()->create([
                    'kategori' => $bbRow['kategori'],
                    'narkotika_id' => ($bbRow['kategori'] === 'Narkotika') ? $bbRow['narkotika_id'] : null,
                    'nama_barang_non_narkotika' => ($bbRow['kategori'] === 'Non-Narkotika') ? $bbRow['nama_barang_bukti'] : null,
                    'kuantitas' => $bbRow['jumlah'] ?? 0,
                    'satuan' => $bbRow['satuan']
                ]);
            }

            if ($request->filled('dokumentasi')) {
                $dokumenService->moveToPermanent($request->input('dokumentasi'), $tat, 'dokumentasi', $uploadedPaths);
            }
            if ($request->filled('lampiran')) {
                $dokumenService->moveToPermanent($request->input('lampiran'), $tat, 'lampiran', $uploadedPaths);
            }
            if ($request->filled('dokumentasi_links')) {
                $dokumenService->saveLinks($request->input('dokumentasi_links'), $tat, 'dokumentasi');
            }
            if ($request->filled('lampiran_links')) {
                $dokumenService->saveLinks($request->input('lampiran_links'), $tat, 'lampiran');
            }

            DB::commit();
            return redirect()->route('berantas.tat.index')->with('success', 'Data TAT berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
            foreach ($uploadedPaths as $path) {
                Storage::disk(config('filesystems.default'))->delete($path);
            }
            Log::error('Gagal simpan: ' . $e->getMessage());
            abort(500, 'Server Error.');
        }
    }

    public function edit($id)
    {
        $tat = BerantasTat::with(['tersangka', 'barangBukti', 'dokumen'])->findOrFail($id);
        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika')->get();
        $satuanKerjas = SatuanKerja::all();

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->isAdmin() && $tat->satuan_kerja_id !== $user->getSatkerId()) abort(403);
        return view('berantas.tat.edit', compact('tat', 'masterNarkotika', 'satuanKerjas'));
    }

    public function update(Request $request, DokumenService $dokumenService, $id)
    {
        $tat = BerantasTat::findOrFail($id);
        
        $messages = [
            'tersangka.*.nama.required' => 'Nama tersangka wajib diisi.',
            'barang_bukti.*.jumlah.required' => 'Jumlah BB wajib diisi.',
            'barang_bukti.*.satuan.required' => 'Satuan BB wajib diisi.',
        ];

        $validator = Validator::make($request->all(), [
            'no_register'         => 'required|unique:berantas_tat,no_register,' . $id,
            'tersangka'           => 'required|array|min:1',
            'barang_bukti'        => 'required|array|min:1',
            'tersangka.*.nama'    => 'required|string',
            'tersangka.*.nik'     => 'required|numeric',
            'barang_bukti.*.jumlah' => 'required|numeric|min:0',
            'barang_bukti.*.satuan' => 'required|string',

            // Validasi File & Link
            'delete_files'         => 'nullable|array', 
            'dokumentasi'          => 'nullable|array',
            'lampiran'             => 'nullable|array',
            
            'dokumentasi_links'        => 'nullable|array',
            'dokumentasi_links.*.nama' => 'required_with:dokumentasi_links.*.url|nullable|string|max:255',
            'dokumentasi_links.*.url'  => 'required_with:dokumentasi_links.*.nama|nullable|url',

            'lampiran_links'        => 'nullable|array',
            'lampiran_links.*.nama' => 'required_with:lampiran_links.*.url|nullable|string|max:255',
            'lampiran_links.*.url'  => 'required_with:lampiran_links.*.nama|nullable|url',
        ], $messages);

        $validator->after(function ($validator) use ($request) {
            if ($request->has('barang_bukti')) {
                foreach ($request->barang_bukti as $index => $bb) {
                    if ($bb['kategori'] === 'Narkotika' && empty($bb['narkotika_id'])) {
                        $validator->errors()->add("barang_bukti.$index.narkotika_id", "Pilih jenis narkotika.");
                    }
                    if ($bb['kategori'] === 'Non-Narkotika' && empty($bb['nama_barang_bukti'])) {
                        $validator->errors()->add("barang_bukti.$index.nama_barang_bukti", "Nama barang wajib diisi.");
                    }
                }
            }
        });

        if ($validator->fails()) return back()->withErrors($validator)->withInput();

        $newFilesMoved = [];

        DB::beginTransaction();
        try {
            $tat->update($request->except(['tersangka', 'barang_bukti', 'delete_files', 'dokumentasi', 'lampiran', 'dokumentasi_links', 'lampiran_links']));
            
            $tat->tersangka()->delete();
            foreach ($request->tersangka as $t) {
                $tat->tersangka()->create([
                    'nama_tersangka' => $t['nama'],
                    'nik'            => $t['nik'],
                    'jenis_kelamin'  => $t['jk'],
                    'usia'           => $t['usia'],
                    'pendidikan'     => $t['pendidikan'],
                    'pekerjaan'      => $t['pekerjaan'],
                    'no_telepon'     => $t['no_telepon'],
                ]);
            }

            // UPDATE BB (Single Row Logic)
            $tat->barangBukti()->delete();
            foreach ($request->barang_bukti as $bbRow) {
                $tat->barangBukti()->create([
                    'kategori' => $bbRow['kategori'],
                    'narkotika_id' => ($bbRow['kategori'] === 'Narkotika') ? $bbRow['narkotika_id'] : null,
                    'nama_barang_non_narkotika' => ($bbRow['kategori'] === 'Non-Narkotika') ? $bbRow['nama_barang_bukti'] : null,
                    'kuantitas' => $bbRow['jumlah'] ?? 0,
                    'satuan' => $bbRow['satuan']
                ]);
            }

            // Hapus Dokumen Lama (File atau Link)
            if ($request->has('delete_files')) {
                $filesToRemove = Dokumen::whereIn('id', $request->delete_files)->get();
                foreach ($filesToRemove as $file) {
                    if (!$file->is_link) $filesToDelete[] = $file->path_file; // Hanya hapus fisik jika bukan link
                    $file->delete();
                }
            }

            // Upload File Baru
            if ($request->filled('dokumentasi')) {
                $dokumenService->moveToPermanent($request->input('dokumentasi'), $tat, 'dokumentasi', $newFilesMoved);
            }
            if ($request->filled('lampiran')) {
                $dokumenService->moveToPermanent($request->input('lampiran'), $tat, 'lampiran', $newFilesMoved);
            }

            // Simpan Link Baru
            if ($request->filled('dokumentasi_links')) {
                $dokumenService->saveLinks($request->input('dokumentasi_links'), $tat, 'dokumentasi');
            }
            if ($request->filled('lampiran_links')) {
                $dokumenService->saveLinks($request->input('lampiran_links'), $tat, 'lampiran');
            }

            DB::commit();
            foreach ($filesToDelete as $path) {
                if (Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
            }

            return redirect()->route('berantas.tat.index')->with('success', 'Data TAT diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($newFilesMoved as $path) {
                if (Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
            }
            Log::error('Update error: ' . $e->getMessage());
            abort(500, 'Server Error.');
        }
    }


    public function destroy($id) 
    {
        $tat = BerantasTat::findOrFail($id);
        
        $filesToDelete = [];
        
        // Loop dokumen, tapi filter isinya
        foreach ($tat->dokumen()->cursor() as $doc) {
            // Cek 1: Pastikan bukan Link (karena link tidak punya file fisik)
            // Cek 2: Pastikan path_file TIDAK NULL dan TIDAK KOSONG
            if (!$doc->is_link && !empty($doc->path_file)) {
                $filesToDelete[] = $doc->path_file;
            }
        }

        DB::beginTransaction();
        try {
            $tat->delete(); 
            DB::commit(); 
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'destroy')->with('message', 'Gagal menghapus data: ' . $e->getMessage());
        }

        // Hapus file fisik
        foreach ($filesToDelete as $path) {
            // Double check: Pastikan $path adalah string (bukan null) sebelum akses Storage
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        return redirect()->back()->with('success', 'destroy')->with('message', 'Data dan file berhasil dihapus.');
    }

    public function export(Request $request)
    {
        $query = $this->getFilteredQuery($request);
        return Excel::download(new TatExport($query), 'Laporan_TAT_'.date('d-m-Y').'.xlsx');
    }
}