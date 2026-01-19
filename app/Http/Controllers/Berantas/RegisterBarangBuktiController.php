<?php

namespace App\Http\Controllers\Berantas;

use App\Http\Controllers\Controller;
use App\Models\BerantasRegisterBarangBukti;
use App\Models\BerantasNarkotika;
use App\Models\SatuanKerja;
use App\Models\TemporaryFile;
use App\Models\DokumentasiKegiatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RegisterBarangBuktiExport;

class RegisterBarangBuktiController extends Controller
{
    private function getQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Eager load items dengan logic filter (closure)
        $itemFilterScope = function($query) use ($request) {
            if (!$request->filled('kategori_bb')) return;

            $categories = (array)$request->kategori_bb;
            $query->where(function($mainQ) use ($categories, $request) {
                // A. Filter Narkotika
                if (in_array('Narkotika', $categories)) {
                    $mainQ->orWhere(function($narkoQ) use ($request) {
                        $narkoQ->where('kategori', 'Narkotika');
                        if ($request->filled('narkotika_ids')) {
                            $narkoQ->whereIn('narkotika_id', (array)$request->narkotika_ids);
                        }
                    });
                }
                // B. Filter Non-Narkotika
                if (in_array('Non-Narkotika', $categories)) {
                    $mainQ->orWhere(function($nonQ) use ($request) {
                        $nonQ->where('kategori', 'Non-Narkotika');
                        if ($request->filled('search_non_narkotika')) {
                            $nonQ->where(function($textQ) use ($request) {
                                foreach ((array)$request->search_non_narkotika as $keyword) {
                                    $textQ->orWhere('nama_barang_non_narkotika', 'LIKE', "%{$keyword}%");
                                }
                            });
                        }
                    });
                }
            });
        };

        $query = BerantasRegisterBarangBukti::with([
            'satuanKerja', 
            'dokumentasi',
            'items' => $itemFilterScope, // Filter Child
            'items.narkotika'
        ]);

        // Filter Parent based on Child
        if ($request->filled('kategori_bb')) {
            $query->whereHas('items', $itemFilterScope);
        }

        // Standard Filter
        if (!$user->isAdmin()) {
            $query->where('satuan_kerja_id', $user->getSatkerId());
        } elseif ($request->filled('satuan_kerja_id')) {
            $query->whereIn('satuan_kerja_id', (array)$request->satuan_kerja_id);
        }

        if ($request->filled('bulan')) $query->whereIn(DB::raw('MONTH(tanggal_perolehan)'), (array)$request->bulan);
        $years = $request->filled('tahun') ? (array)$request->tahun : [date('Y')];
        $query->whereIn(DB::raw('YEAR(tanggal_perolehan)'), $years);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('lokasi_perolehan', 'LIKE', "%{$s}%")
                  ->orWhereHas('items', function($iq) use ($s) {
                      $iq->where('nama_barang_non_narkotika', 'LIKE', "%{$s}%")
                         ->orWhereHas('narkotika', fn($nq) => $nq->where('nama_narkotika', 'LIKE', "%{$s}%"));
                  });
            });
        }

        if ($request->filled('sumber_perolehan')) {
            $query->whereIn('sumber_perolehan', (array)$request->sumber_perolehan);
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $allowedSorts = ['created_at', 'tanggal_perolehan', 'satuan_kerja_id', 'sumber_perolehan'];
        
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    public function index(Request $request)
    {
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja')->get();
        $years = BerantasRegisterBarangBukti::selectRaw('YEAR(tanggal_perolehan) as year')->distinct()->orderBy('year', 'desc')->pluck('year');
        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika')->get();
        $data = $this->getQuery($request)->paginate($request->get('per_page', 10))->withQueryString();
        
        return view('berantas.register-barang-bukti.index', compact('data', 'satuanKerjas', 'years', 'masterNarkotika'));
    }

    public function create()
    {
        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika')->get();
        return view('berantas.register-barang-bukti.create', compact('masterNarkotika'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'tanggal_perolehan' => 'required|date',
            'sumber_perolehan'  => 'required|in:Hasil Tangkap,Temuan',
            'lokasi_perolehan'  => 'nullable|string',
            
            'items'             => 'required|array|min:1',
            'items.*.kategori'  => 'required|in:Narkotika,Non-Narkotika',
            'items.*.jumlah'    => 'required|numeric|min:0',
            
            // VALIDASI KEAMANAN (SECURITY):
            // 1. Jika Narkotika, wajib isi satuan_narkotika dan NILAINYA HARUS Gram/Kg/Ton.
            'items.*.satuan_narkotika' => [
                'nullable', 
                Rule::requiredIf(fn() => request('items.*.kategori') === 'Narkotika'),
                Rule::in(['Gram', 'Kg', 'Ton']) // RESTRICT VALUE (Security Layer)
            ],
            // 2. Jika Non-Narkotika, wajib isi satuan_non_narkotika (string bebas)
            'items.*.satuan_non_narkotika' => 'nullable|required_if:items.*.kategori,Non-Narkotika|string',
            
            // Validasi Dokumentasi
            'dokumentasi'   => 'nullable|array',
            'dokumentasi.*' => 'required',
        ]);

        // Validasi Relasi Item
        $validator->after(function ($validator) use ($request) {
            if($request->has('items')) {
                foreach ($request->items as $i => $item) {
                    if ($item['kategori'] == 'Narkotika' && empty($item['narkotika_id'])) {
                        $validator->errors()->add("items.$i.narkotika_id", 'Jenis Narkotika wajib dipilih.');
                    }
                    if ($item['kategori'] == 'Non-Narkotika' && empty($item['nama_barang_non_narkotika'])) {
                        $validator->errors()->add("items.$i.nama_barang_non_narkotika", 'Nama Barang wajib diisi.');
                    }
                }
            }
        });

        if ($validator->fails()) return back()->withErrors($validator)->withInput();

        // Array pelacak file fisik untuk rollback
        $filesMoved = [];

        DB::beginTransaction();

        try {
            $register = BerantasRegisterBarangBukti::create([
                'satuan_kerja_id'   => $user->isAdmin() ? $request->satuan_kerja_id : $user->getSatkerId(),
                'tanggal_perolehan' => $request->tanggal_perolehan,
                'sumber_perolehan'  => $request->sumber_perolehan,
                'lokasi_perolehan'  => $request->lokasi_perolehan,
            ]);

            foreach ($request->items as $item) {
                $register->items()->create([
                    'kategori' => $item['kategori'],
                    'narkotika_id' => $item['kategori'] == 'Narkotika' ? $item['narkotika_id'] : null,
                    'nama_barang_non_narkotika' => $item['kategori'] == 'Non-Narkotika' ? $item['nama_barang_non_narkotika'] : null,
                    'kuantitas' => $item['jumlah'],
                    
                    // Mapping ke kolom DB yang sesuai
                    'satuan_narkotika' => $item['kategori'] == 'Narkotika' ? $item['satuan_narkotika'] : null,
                    'satuan_non_narkotika' => $item['kategori'] == 'Non-Narkotika' ? $item['satuan_non_narkotika'] : null,
                ]);
            }

            // PROSES PINDAH FILE (SAFE UPLOAD)
            if ($request->filled('dokumentasi')) {
                foreach ($request->input('dokumentasi') as $folder) {
                    $tempFile = TemporaryFile::where('folder', $folder)->first();

                    if ($tempFile) {
                        // Path Sumber
                        $sourcePath = 'public/tmp/' . $folder . '/' . $tempFile->filename;

                        // Ambil Metadata dari file sumber
                        $mimeType = Storage::mimeType($sourcePath);
                        $size = Storage::size($sourcePath);

                        // Generate Nama Unik
                        $ext = pathinfo($tempFile->filename, PATHINFO_EXTENSION);
                        $nameOnly = pathinfo($tempFile->filename, PATHINFO_FILENAME);
                        $cleanFileName = time() . '_' . uniqid() . '_' . Str::slug($nameOnly) . '.' . $ext;

                        // Path Tujuan
                        $destinationPath = 'dokumentasi/register-barang-bukti/' . date('Y') . '/' . $cleanFileName;

                        if (Storage::exists($sourcePath)) {
                            // Copy File ke Public
                            Storage::disk('public')->put($destinationPath, Storage::readStream($sourcePath));
                            $filesMoved[] = $destinationPath; // Catat untuk rollback

                            // Simpan DB
                            $register->dokumentasi()->create([
                                'nama_file_asli' => $tempFile->filename,
                                'path_file'      => $destinationPath,
                                'tipe_file'      => $mimeType,
                                'ukuran_file'    => $size
                            ]);

                            // Cleanup Temp
                            Storage::deleteDirectory('public/tmp/' . $folder);
                            $tempFile->delete();
                        }
                    }
                }
            }

            DB::commit();

            return redirect()->route('berantas.register-barang-bukti.index')->with('success', 'Data berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();

            // ROLLBACK FILE FISIK (Hapus file yang terlanjur tercopy)
            foreach ($filesMoved as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $register = BerantasRegisterBarangBukti::with(['items', 'dokumentasi'])->findOrFail($id);
        
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->isOperator() && $register->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403);
        }

        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika')->get();
        return view('berantas.register-barang-bukti.edit', compact('register', 'masterNarkotika'));
    }

    public function update(Request $request, $id)
    {
        $register = BerantasRegisterBarangBukti::findOrFail($id);
        
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->isOperator() && $register->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403);
        }
        
        $validator = Validator::make($request->all(), [
            'tanggal_perolehan' => 'required|date',
            'sumber_perolehan'  => 'required|in:Hasil Tangkap,Temuan',
            'lokasi_perolehan'  => 'nullable|string',
            
            'items'             => 'required|array|min:1',
            'items.*.kategori'  => 'required|in:Narkotika,Non-Narkotika',
            'items.*.jumlah'    => 'required|numeric|min:0',
            
            // VALIDASI KEAMANAN SAAT UPDATE
            'items.*.satuan_narkotika' => [
                'nullable', 
                Rule::requiredIf(fn() => request('items.*.kategori') === 'Narkotika'),
                Rule::in(['Gram', 'Kg', 'Ton'])
            ],
            'items.*.satuan_non_narkotika' => 'nullable|required_if:items.*.kategori,Non-Narkotika|string',
            
            'delete_files'   => 'nullable|array',
            'delete_files.*' => 'exists:dokumentasi_kegiatan,id',
            'dokumentasi'    => 'nullable|array',
        ]);

        $validator->after(function ($validator) use ($request) {
            if($request->has('items')) {
                foreach ($request->items as $i => $item) {
                    if ($item['kategori'] == 'Narkotika' && empty($item['narkotika_id'])) {
                        $validator->errors()->add("items.$i.narkotika_id", 'Pilih Narkotika.');
                    }
                    if ($item['kategori'] == 'Non-Narkotika' && empty($item['nama_barang_non_narkotika'])) {
                        $validator->errors()->add("items.$i.nama_barang_non_narkotika", 'Isi Nama Barang.');
                    }
                }
            }
        });

        if ($validator->fails()) return back()->withErrors($validator)->withInput();

        // Variabel Pelacak
        $newFilesMoved = []; // File baru yang sukses upload (untuk rollback)
        $filesToDelete = []; // File lama yang harus dihapus (setelah commit)

        DB::beginTransaction();

        try {
            // Update Data Utama
            $register->update([
                'tanggal_perolehan' => $request->tanggal_perolehan,
                'sumber_perolehan'  => $request->sumber_perolehan,
                'lokasi_perolehan'  => $request->lokasi_perolehan,
            ]);

            // Replace Items (Simplifikasi Update)
            $register->items()->delete();
            foreach ($request->items as $item) {
                $register->items()->create([
                    'kategori' => $item['kategori'],
                    'narkotika_id' => $item['kategori'] == 'Narkotika' ? $item['narkotika_id'] : null,
                    'nama_barang_non_narkotika' => $item['kategori'] == 'Non-Narkotika' ? $item['nama_barang_non_narkotika'] : null,
                    'kuantitas' => $item['jumlah'],
                    
                    'satuan_narkotika' => $item['kategori'] == 'Narkotika' ? $item['satuan_narkotika'] : null,
                    'satuan_non_narkotika' => $item['kategori'] == 'Non-Narkotika' ? $item['satuan_non_narkotika'] : null,
                ]);
            }

            // A. PROSES HAPUS FILE LAMA (Hapus DB dulu, simpan path fisik)
            if ($request->has('delete_files')) {
                $filesToRemove = DokumentasiKegiatan::whereIn('id', $request->delete_files)->get();
                foreach ($filesToRemove as $file) {
                    $filesToDelete[] = $file->path_file;
                    $file->delete();
                }
            }

            // B. PROSES UPLOAD FILE BARU
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

                        $destinationPath = 'dokumentasi/register-barang-bukti/' . date('Y') . '/' . $cleanFileName;

                        if (Storage::exists($sourcePath)) {
                            Storage::disk('public')->put($destinationPath, Storage::readStream($sourcePath));
                            $newFilesMoved[] = $destinationPath;

                            $register->dokumentasi()->create([
                                'nama_file_asli' => $tempFile->filename,
                                'path_file'      => $destinationPath,
                                'tipe_file'      => $mimeType,
                                'ukuran_file'    => $size
                            ]);

                            Storage::deleteDirectory('public/tmp/' . $folder);
                            $tempFile->delete();
                        }
                    }
                }
            }

            DB::commit();

            // C. CLEANUP FILE FISIK LAMA (Post-Commit)
            foreach ($filesToDelete as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            return redirect()->route('berantas.register-barang-bukti.index')->with('success', 'Data berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();

            // ROLLBACK FILE BARU
            foreach ($newFilesMoved as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            return back()->with('error', 'Gagal memperbarui data: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        $register = BerantasRegisterBarangBukti::with('dokumentasi')->findOrFail($id);
        
        $filesToDelete = [];
        foreach ($register->dokumentasi as $doc) {
            $filesToDelete[] = $doc->path_file;
        }

        DB::beginTransaction();
        try {
            $register->delete(); 
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }

        // Cleanup Fisik
        foreach ($filesToDelete as $path) {
            try {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            } catch (\Exception $e) {
                // Silent fail
            }
        }

        return back()->with('success', 'Data berhasil dihapus.');
    }

    public function export(Request $request)
    {
        $query = $this->getQuery($request);
        return Excel::download(new RegisterBarangBuktiExport($query), 'Register_Barang_Bukti_'.date('d-m-Y').'.xlsx');
    }
}