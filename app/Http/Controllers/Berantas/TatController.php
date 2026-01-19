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
            'dokumentasi'
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

        $perPage = $request->input('per_page', 10);
        $data = $this->getFilteredQuery($request)->paginate($perPage)->withQueryString();

        return view('berantas.tat.index', compact('data', 'satuanKerjas', 'years', 'masterNarkotika'));
    }

    public function create()
    {
        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika')->get();
        $satuanKerjas = SatuanKerja::all();
        return view('berantas.tat.create', compact('masterNarkotika', 'satuanKerjas'));
    }

    public function store(Request $request)
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
            'dokumentasi'         => 'nullable|array',
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

        $filesMoved = [];

        DB::beginTransaction();
        try {
            $data = $request->except(['tersangka', 'barang_bukti', 'dokumentasi']);
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

            // SAFE UPLOAD
            if ($request->filled('dokumentasi')) {
                foreach ($request->input('dokumentasi') as $folder) {
                    $tempFile = TemporaryFile::where('folder', $folder)->first();

                    if ($tempFile) {
                        $sourcePath = 'public/tmp/' . $folder . '/' . $tempFile->filename;
                        $mimeType = Storage::mimeType($sourcePath);
                        $size = Storage::size($sourcePath);

                        $ext = pathinfo($tempFile->filename, PATHINFO_EXTENSION);
                        $nameOnly = pathinfo($tempFile->filename, PATHINFO_FILENAME);
                        $cleanFileName = time() . '_' . uniqid() . '_' . Str::slug($nameOnly) . '.' . $ext;

                        $destinationPath = 'dokumentasi/tat/' . date('Y') . '/' . $cleanFileName;

                        if (Storage::exists($sourcePath)) {
                            Storage::disk('public')->put($destinationPath, Storage::readStream($sourcePath));
                            $filesMoved[] = $destinationPath;

                            $tat->dokumentasi()->create([
                                'nama_file_asli' => $tempFile->filename,
                                'path_file' => $destinationPath,
                                'tipe_file' => $mimeType,
                                'ukuran_file' => $size
                            ]);

                            Storage::deleteDirectory('public/tmp/' . $folder);
                            $tempFile->delete();
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('berantas.tat.index')->with('success', 'Data TAT berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($filesMoved as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $tat = BerantasTat::with(['tersangka', 'barangBukti', 'dokumentasi'])->findOrFail($id);
        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika')->get();
        $satuanKerjas = SatuanKerja::all();

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->isAdmin() && $tat->satuan_kerja_id !== $user->getSatkerId()) abort(403);
        return view('berantas.tat.edit', compact('tat', 'masterNarkotika', 'satuanKerjas'));
    }

    public function update(Request $request, $id)
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
            'delete_files'        => 'nullable|array',
            'delete_files.*'      => 'exists:dokumentasi_kegiatan,id',
            'dokumentasi'         => 'nullable|array',
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
        $filesToDelete = [];

        DB::beginTransaction();
        try {
            $tat->update($request->except(['tersangka', 'barang_bukti', 'dokumentasi', 'delete_files']));
            
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

            if ($request->has('delete_files')) {
                $filesToRemove = DokumentasiKegiatan::whereIn('id', $request->delete_files)->get();
                foreach ($filesToRemove as $file) {
                    $filesToDelete[] = $file->path_file;
                    $file->delete();
                }
            }

            if ($request->filled('dokumentasi')) {
                foreach ($request->input('dokumentasi') as $folder) {
                    $tempFile = TemporaryFile::where('folder', $folder)->first();

                    if ($tempFile) {
                        $sourcePath = 'public/tmp/' . $folder . '/' . $tempFile->filename;
                        $mimeType = Storage::mimeType($sourcePath);
                        $size = Storage::size($sourcePath);

                        $ext = pathinfo($tempFile->filename, PATHINFO_EXTENSION);
                        $nameOnly = pathinfo($tempFile->filename, PATHINFO_FILENAME);
                        $cleanFileName = time() . '_' . uniqid() . '_' . Str::slug($nameOnly) . '.' . $ext;
                        $destinationPath = 'dokumentasi/tat/' . date('Y') . '/' . $cleanFileName;

                        if (Storage::exists($sourcePath)) {
                            Storage::disk('public')->put($destinationPath, Storage::readStream($sourcePath));
                            $newFilesMoved[] = $destinationPath;

                            $tat->dokumentasi()->create([
                                'nama_file_asli' => $tempFile->filename,
                                'path_file' => $destinationPath,
                                'tipe_file' => $mimeType,
                                'ukuran_file' => $size
                            ]);

                            Storage::deleteDirectory('public/tmp/' . $folder);
                            $tempFile->delete();
                        }
                    }
                }
            }

            DB::commit();

            foreach ($filesToDelete as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            return redirect()->route('berantas.tat.index')->with('success', 'Data TAT diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($newFilesMoved as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
            return back()->with('error', 'Gagal update: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        $tat = BerantasTat::findOrFail($id);
        
        $filesToDelete = [];
        foreach($tat->dokumentasi as $doc) {
            $filesToDelete[] = $doc->path_file;
        }

        DB::beginTransaction();
        try {
            $tat->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }

        foreach ($filesToDelete as $path) {
            if(Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
        }

        return back()->with('success', 'Data dihapus.');
    }

    public function export(Request $request)
    {
        $query = $this->getFilteredQuery($request);
        return Excel::download(new TatExport($query), 'Laporan_TAT_'.date('d-m-Y').'.xlsx');
    }
}