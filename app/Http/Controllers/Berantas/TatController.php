<?php

namespace App\Http\Controllers\Berantas;

use App\Http\Controllers\Controller;
use App\Models\BerantasTat;
use App\Models\BerantasNarkotika;
use App\Models\SatuanKerja;
use App\Models\TemporaryFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TatExport;

class TatController extends Controller
{
    /**
     * Logic filter item barang bukti untuk PENCARIAN KASUS (whereHas)
     * Ini digunakan untuk menentukan APAKAH suatu kasus harus muncul atau tidak.
     * Di sini kita HARUS spesifik (misal: hanya cari kasus yang punya Sabu).
     */
    private function applyCaseFilter($query, Request $request)
    {
        if (!$request->filled('kategori_bb')) return $query;

        $kategori = (array)$request->kategori_bb;

        return $query->where(function($q) use ($kategori, $request) {
            
            // 1. Cek Blok Narkotika
            if (in_array('Narkotika', $kategori)) {
                $q->orWhere(function($sub) use ($request) {
                    $sub->where('kategori', 'Narkotika');
                    // Filter spesifik jenis narkotika (Hanya untuk pencarian kasus)
                    if ($request->filled('narkotika_ids')) {
                        $sub->whereIn('narkotika_id', (array)$request->narkotika_ids);
                    }
                });
            }

            // 2. Cek Blok Non-Narkotika
            if (in_array('Non-Narkotika', $kategori)) {
                $q->orWhere(function($sub) use ($request) {
                    $sub->where('kategori', 'Non-Narkotika');
                    // Filter spesifik nama barang (Hanya untuk pencarian kasus)
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
        
        // Eager Loading dengan Filter Tampilan Item (PERBAIKAN UTAMA DISINI)
        // Kita hanya memfilter berdasarkan KATEGORI, bukan item spesifik.
        // Agar jika user cari "Sabu", item "Ganja" (sesama Narkotika) tetap muncul.
        $query = BerantasTat::with([
            'satuanKerja', 
            'tersangka', 
            'barangBukti' => function($q) use ($request) {
                if ($request->filled('kategori_bb')) {
                    // Hanya tampilkan item yang sesuai dengan KATEGORI yang dipilih user.
                    // Misal: Pilih Narkotika -> Tampilkan semua Narkotika (Sabu, Ganja, dll).
                    // Item Non-Narkotika akan disembunyikan.
                    $q->whereIn('kategori', (array)$request->kategori_bb);
                }
            },
            'barangBukti.narkotika', 
            'dokumentasi'
        ]);

        // 1. Filter Role & Satker
        if (!$user->hasRole('admin')) {
            $query->where('satuan_kerja_id', $user->getSatkerId());
        } else {
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('satuan_kerja_id', (array)$request->satuan_kerja_id);
            }
        }

        // 2. Filter Waktu
        if ($request->filled('bulan')) {
            $query->whereIn(DB::raw('MONTH(tanggal_pelaksanaan)'), (array)$request->bulan);
        }
        $years = $request->filled('tahun') ? (array)$request->tahun : [date('Y')];
        $query->whereIn(DB::raw('YEAR(tanggal_pelaksanaan)'), $years);

        // 3. Filter Global Search
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

        // 4. Filter Kategori Barang Bukti (Filter Baris Kasus)
        // Gunakan logika spesifik untuk MENENTUKAN KASUS MANA yang muncul
        if ($request->filled('kategori_bb')) {
            $query->whereHas('barangBukti', function($q) use ($request) {
                $this->applyCaseFilter($q, $request);
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

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
        // ... (Kode Store SAMA, tidak ada perubahan) ...
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
        ], $messages);

        $validator->after(function ($validator) use ($request) {
            if ($request->has('barang_bukti')) {
                foreach ($request->barang_bukti as $index => $bb) {
                    if ($bb['kategori'] === 'Narkotika' && empty($bb['narkotika_ids'])) {
                        $validator->errors()->add("barang_bukti.$index.narkotika_ids", "Pilih minimal 1 jenis narkotika.");
                    }
                    if ($bb['kategori'] === 'Non-Narkotika' && empty($bb['nama_barang_bukti'])) {
                        $validator->errors()->add("barang_bukti.$index.nama_barang_bukti", "Nama barang wajib diisi.");
                    }
                }
            }
        });

        if ($validator->fails()) return back()->withErrors($validator)->withInput();

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

            foreach ($request->barang_bukti as $bbRow) {
                $items = ($bbRow['kategori'] === 'Narkotika') 
                    ? ($bbRow['narkotika_ids'] ?? []) 
                    : ($bbRow['nama_barang_bukti'] ?? []);

                if(!is_array($items)) $items = [$items];

                foreach ($items as $val) {
                    $tat->barangBukti()->create([
                        'kategori' => $bbRow['kategori'],
                        'narkotika_id' => ($bbRow['kategori'] === 'Narkotika') ? $val : null,
                        'nama_barang_non_narkotika' => ($bbRow['kategori'] === 'Non-Narkotika') ? $val : null,
                        'kuantitas' => $bbRow['jumlah'] ?? 0,
                        'satuan' => $bbRow['satuan']
                    ]);
                }
            }

            if ($request->filled('dokumentasi')) {
                foreach ($request->dokumentasi as $folder) {
                    $temp = TemporaryFile::where('folder', $folder)->first();
                    if ($temp) {
                        $path = 'dokumentasi/tat/' . date('Y');
                        if(!Storage::disk('public')->exists($path)) Storage::disk('public')->makeDirectory($path);
                        $dest = $path . '/' . $temp->filename;
                        Storage::disk('public')->move('public/tmp/' . $folder . '/' . $temp->filename, $dest);
                        $tat->dokumentasi()->create([
                            'nama_file_asli' => $temp->filename,
                            'path_file' => $dest,
                            'tipe_file' => Storage::mimeType('public/'.$dest),
                            'ukuran_file' => Storage::size('public/'.$dest)
                        ]);
                        Storage::deleteDirectory('public/tmp/' . $folder);
                        $temp->delete();
                    }
                }
            }

            DB::commit();
            return redirect()->route('berantas.tat.index')->with('success', 'Data TAT berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
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
            'tersangka.*.usia'    => 'required|numeric|min:0',
            'barang_bukti.*.jumlah' => 'required|numeric|min:0',
            'barang_bukti.*.satuan' => 'required|string',
            'pasal_disangkakan'   => 'nullable|string',
            'biaya'               => 'nullable|numeric|min:0',
        ], $messages);

        $validator->after(function ($validator) use ($request) {
            if ($request->has('barang_bukti')) {
                foreach ($request->barang_bukti as $index => $bb) {
                    if ($bb['kategori'] === 'Narkotika' && empty($bb['narkotika_ids'])) {
                        $validator->errors()->add("barang_bukti.$index.narkotika_ids", "Pilih minimal 1 jenis narkotika.");
                    }
                    if ($bb['kategori'] === 'Non-Narkotika' && empty($bb['nama_barang_bukti'])) {
                        $validator->errors()->add("barang_bukti.$index.nama_barang_bukti", "Nama barang wajib diisi.");
                    }
                }
            }
        });

        if ($validator->fails()) return back()->withErrors($validator)->withInput();

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
            $tat->barangBukti()->delete();
            foreach ($request->barang_bukti as $bbRow) {
                $items = ($bbRow['kategori'] === 'Narkotika') ? ($bbRow['narkotika_ids'] ?? []) : ($bbRow['nama_barang_bukti'] ?? []);
                if(!is_array($items)) $items = [$items];
                foreach ($items as $val) {
                    $tat->barangBukti()->create([
                        'kategori' => $bbRow['kategori'],
                        'narkotika_id' => ($bbRow['kategori'] === 'Narkotika') ? $val : null,
                        'nama_barang_non_narkotika' => ($bbRow['kategori'] === 'Non-Narkotika') ? $val : null,
                        'kuantitas' => $bbRow['jumlah'] ?? 0,
                        'satuan' => $bbRow['satuan']
                    ]);
                }
            }
            if ($request->has('delete_files')) {
                foreach($request->delete_files as $dfid) {
                    $file = $tat->dokumentasi()->find($dfid);
                    if($file) {
                        Storage::disk('public')->delete($file->path_file);
                        $file->delete();
                    }
                }
            }
            if ($request->filled('dokumentasi')) {
                foreach ($request->dokumentasi as $folder) {
                    $temp = TemporaryFile::where('folder', $folder)->first();
                    if ($temp) {
                        $path = 'dokumentasi/tat/' . date('Y');
                        if(!Storage::disk('public')->exists($path)) Storage::disk('public')->makeDirectory($path);
                        $dest = $path . '/' . $temp->filename;
                        Storage::disk('public')->move('public/tmp/' . $folder . '/' . $temp->filename, $dest);
                        $tat->dokumentasi()->create([
                            'nama_file_asli' => $temp->filename,
                            'path_file' => $dest,
                            'tipe_file' => Storage::mimeType('public/'.$dest),
                            'ukuran_file' => Storage::size('public/'.$dest)
                        ]);
                        Storage::deleteDirectory('public/tmp/' . $folder);
                        $temp->delete();
                    }
                }
            }
            DB::commit();
            return redirect()->route('berantas.tat.index')->with('success', 'Data TAT diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal update: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        $tat = BerantasTat::findOrFail($id);
        DB::beginTransaction();
        try {
            foreach($tat->dokumentasi as $doc) {
                if(Storage::disk('public')->exists($doc->path_file)) Storage::disk('public')->delete($doc->path_file);
            }
            $tat->delete();
            DB::commit();
            return back()->with('success', 'Data dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $query = $this->getFilteredQuery($request);
        return Excel::download(new TatExport($query), 'Laporan_TAT_'.date('d-m-Y').'.xlsx');
    }
}