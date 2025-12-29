<?php

namespace App\Http\Controllers\Berantas;

use App\Http\Controllers\Controller;
use App\Models\BerantasUngkapKasus;
use App\Models\SatuanKerja;
use App\Models\DokumentasiKegiatan;
use App\Models\TemporaryFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UngkapKasusController extends Controller
{
    /**
     * Helper Query Filter (Pola P2M)
     */
    private function getFilteredQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $activeYears = $request->filled('tahun') ? $request->tahun : [date('Y')];

        $query = BerantasUngkapKasus::with(['satuanKerja', 'tersangka', 'barangBukti']);

        // 1. Filter Satuan Kerja
        if ($user->hasRole('admin')) {
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('satuan_kerja_id', $request->satuan_kerja_id);
            }
        } else {
            $satkerId = $user->getSatkerId();
            $query->where('satuan_kerja_id', $satkerId);
        }

        // 2. Filter Waktu
        if ($request->filled('bulan')) {
            $query->where(function($q) use ($request) {
                foreach ($request->bulan as $b) {
                    $q->orWhereMonth('tanggal_kejadian', $b);
                }
            });
        }
        
        $query->where(function($q) use ($activeYears) {
            foreach ($activeYears as $y) {
                $q->orWhereYear('tanggal_kejadian', $y);
            }
        });

        // 3. Search Logic
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nomor_lkn', 'LIKE', "%{$search}%")
                  ->orWhere('alamat_tkp', 'LIKE', "%{$search}%")
                  ->orWhereHas('tersangka', function($sq) use ($search) {
                      $sq->where('nama_tersangka', 'LIKE', "%{$search}%");
                  });
            });
        }

        return $query->latest();
    }

    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $satuanKerjas = $user->hasRole('admin') ? SatuanKerja::orderBy('satuan_kerja')->get() : [];
        
        $yearQuery = BerantasUngkapKasus::selectRaw('YEAR(tanggal_kejadian) as year');
        if ($user->isOperator()) {
            $yearQuery->where('satuan_kerja_id', $user->getSatkerId());
        }
        $years = $yearQuery->distinct()->orderByDesc('year')->pluck('year');

        $query = $this->getFilteredQuery($request);
        $query->with('dokumentasi');

        $perPage = $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100])) $perPage = 10;

        $kasus = $query->paginate($perPage)->withQueryString();

        return view('berantas.ungkap-kasus.index', compact('kasus', 'satuanKerjas', 'years'));
    }

    public function create()
    {
        return view('berantas.ungkap-kasus.create');
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $satkerId = $user->getSatkerId();

        if (!$satkerId && $user->isOperator()) {
            return back()->with('error', 'Gagal Simpan: Akun Anda tidak terhubung dengan Satuan Kerja.');
        }

        // VALIDASI BACKEND (HTML required dihapus)
        $rules = [
            'nomor_lkn'        => 'required|unique:berantas_ungkap_kasus,nomor_lkn',
            'tanggal_kejadian' => 'required|date',
            'alamat_tkp'       => 'required|string',
            
            // Tersangka & Foto
            'tersangka'        => 'required|array|min:1',
            'tersangka.*.nama' => 'required|string',
            'tersangka.*.jk'   => 'required|in:Laki-Laki,Perempuan',
            'tersangka.*.tahap'=> 'required|string', // WAJIB DIISI
            'tersangka.*.foto' => 'nullable|image|max:2048', 

            // BB
            'barang_bukti'     => 'required|array|min:1',
            'barang_bukti.*.jenis'  => 'required|string',
            'barang_bukti.*.jumlah' => 'required|numeric',
            'barang_bukti.*.satuan' => 'required|string',
            
            // Dokumentasi
            'dokumentasi'      => 'nullable|array',
        ];

        if ($user->isAdmin()) {
            $rules['satuan_kerja_id'] = 'required|exists:satuan_kerja,id';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        $filesMoved = []; 

        try {
            $dataKasus = [
                'nomor_lkn'        => $request->nomor_lkn,
                'tanggal_kejadian' => $request->tanggal_kejadian,
                'alamat_tkp'       => $request->alamat_tkp,
                'satuan_kerja_id'  => $user->isOperator() ? $satkerId : $request->satuan_kerja_id,
            ];
            
            $kasus = BerantasUngkapKasus::create($dataKasus);

            $mapId = []; 
            foreach ($request->tersangka as $index => $tData) {
                $fotoPath = null;
                if ($request->hasFile("tersangka.{$index}.foto")) {
                    $file = $request->file("tersangka.{$index}.foto");
                    $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $fotoPath = $file->storeAs('foto_tersangka/' . date('Y'), $filename, 'public');
                }

                $tersangka = $kasus->tersangka()->create([
                    'nama_tersangka' => $tData['nama'],
                    'jenis_kelamin'  => $tData['jk'],
                    'status_tahap'   => $tData['tahap'], // Sudah divalidasi required
                    'foto_tersangka' => $fotoPath,
                ]);

                if (isset($tData['temp_id'])) {
                    $mapId[$tData['temp_id']] = $tersangka->id;
                }
            }

            foreach ($request->barang_bukti as $bbData) {
                $pemilikRef = $bbData['pemilik_id'] ?? 'kasus';
                $finalIdTersangka = null;

                if ($pemilikRef !== 'kasus' && isset($mapId[$pemilikRef])) {
                    $finalIdTersangka = $mapId[$pemilikRef];
                }

                $kasus->barangBukti()->create([
                    'berantas_ungkap_tersangka_id' => $finalIdTersangka,
                    'jenis_barang_bukti'  => $bbData['jenis'],
                    'jumlah_barang_bukti' => $bbData['jumlah'],
                    'satuan_barang_bukti' => $bbData['satuan'], 
                ]);
            }

            if ($request->filled('dokumentasi')) {
                foreach ($request->input('dokumentasi') as $folder) {
                    $tempFile = TemporaryFile::where('folder', $folder)->first();
                    if ($tempFile) {
                        $sourcePath = 'public/tmp/' . $folder . '/' . $tempFile->filename;
                        $ext = pathinfo($tempFile->filename, PATHINFO_EXTENSION);
                        $cleanName = time() . '_' . uniqid() . '_' . Str::slug(pathinfo($tempFile->filename, PATHINFO_FILENAME)) . '.' . $ext;
                        $destPath = 'dokumentasi/berantas/' . date('Y') . '/' . $cleanName;

                        if (Storage::exists($sourcePath)) {
                            Storage::disk('public')->put($destPath, Storage::readStream($sourcePath));
                            $filesMoved[] = $destPath;

                            $kasus->dokumentasi()->create([
                                'nama_file_asli' => $tempFile->filename,
                                'path_file'      => $destPath,
                                'tipe_file'      => Storage::mimeType($sourcePath),
                                'ukuran_file'    => Storage::size($sourcePath),
                            ]);

                            Storage::deleteDirectory('public/tmp/' . $folder);
                            $tempFile->delete();
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('berantas.ungkap-kasus.index')
                ->with('success', 'store')
                ->with('message', 'Data Ungkap Kasus Berhasil Disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($filesMoved as $path) {
                if(Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
            }
            return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kasus = BerantasUngkapKasus::with(['tersangka', 'barangBukti', 'dokumentasi'])->findOrFail($id);

        if ($user->isOperator() && $kasus->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403, 'Anda tidak berhak mengubah data Satuan Kerja lain.');
        }

        return view('berantas.ungkap-kasus.edit', compact('kasus'));
    }

    public function update(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kasus = BerantasUngkapKasus::findOrFail($id);

        if ($user->isOperator() && $kasus->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403);
        }

        $rules = [
            'nomor_lkn'        => 'required|unique:berantas_ungkap_kasus,nomor_lkn,' . $id,
            'tanggal_kejadian' => 'required|date',
            'alamat_tkp'       => 'required|string',
            'tersangka'        => 'required|array|min:1',
            'tersangka.*.nama' => 'required|string',
            'tersangka.*.jk'   => 'required|in:Laki-Laki,Perempuan',
            'tersangka.*.tahap'=> 'required|string', // WAJIB DIISI
            'tersangka.*.foto' => 'nullable|image|max:2048',
            'barang_bukti'     => 'required|array|min:1',
            'barang_bukti.*.jenis'  => 'required|string',
            'barang_bukti.*.jumlah' => 'required|numeric',
            'barang_bukti.*.satuan' => 'required|string',
            'delete_files'     => 'nullable|array',
            'dokumentasi'      => 'nullable|array',
        ];

        if ($user->isAdmin()) {
            $rules['satuan_kerja_id'] = 'required|exists:satuan_kerja,id';
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) return back()->withErrors($validator)->withInput();

        DB::beginTransaction();
        $newFilesMoved = [];
        $filesToDelete = [];

        try {
            $dataUpdate = [
                'nomor_lkn' => $request->nomor_lkn,
                'tanggal_kejadian' => $request->tanggal_kejadian,
                'alamat_tkp' => $request->alamat_tkp,
            ];
            if ($user->isAdmin()) {
                $dataUpdate['satuan_kerja_id'] = $request->satuan_kerja_id;
            }
            $kasus->update($dataUpdate);

            // Sync Tersangka
            $inputTersangka = $request->tersangka ?? [];
            $existingIds = collect($inputTersangka)->pluck('id')->filter()->toArray();
            
            // Hapus tersangka & fotonya jika dihapus dari form
            $deletedTersangkas = $kasus->tersangka()->whereNotIn('id', $existingIds)->get();
            foreach($deletedTersangkas as $dt) {
                if($dt->foto_tersangka && Storage::disk('public')->exists($dt->foto_tersangka)) {
                    Storage::disk('public')->delete($dt->foto_tersangka);
                }
                $dt->delete();
            }

            $mapId = [];
            foreach ($inputTersangka as $index => $tData) {
                $payload = [
                    'nama_tersangka' => $tData['nama'],
                    'jenis_kelamin'  => $tData['jk'],
                    'status_tahap'   => $tData['tahap'],
                ];

                if ($request->hasFile("tersangka.{$index}.foto")) {
                    $file = $request->file("tersangka.{$index}.foto");
                    $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $payload['foto_tersangka'] = $file->storeAs('foto_tersangka/' . date('Y'), $filename, 'public');
                }
                
                if (isset($tData['id']) && $tData['id']) {
                    $model = $kasus->tersangka()->find($tData['id']);
                    if($model) {
                        if (isset($payload['foto_tersangka']) && $model->foto_tersangka) {
                            if(Storage::disk('public')->exists($model->foto_tersangka)) {
                                Storage::disk('public')->delete($model->foto_tersangka);
                            }
                        }
                        $model->update($payload);
                    }
                } else {
                    $model = $kasus->tersangka()->create($payload);
                }
                
                if($model && isset($tData['temp_id'])) {
                    $mapId[$tData['temp_id']] = $model->id;
                }
            }

            // Sync Barang Bukti
            $inputBB = $request->barang_bukti ?? [];
            $existingBBIds = collect($inputBB)->pluck('id')->filter()->toArray();
            $kasus->barangBukti()->whereNotIn('id', $existingBBIds)->delete();

            foreach ($inputBB as $bbData) {
                $pemilikRef = $bbData['pemilik_id'] ?? 'kasus';
                $finalIdTersangka = null;

                if ($pemilikRef !== 'kasus') {
                    if (isset($mapId[$pemilikRef])) {
                        $finalIdTersangka = $mapId[$pemilikRef];
                    } elseif (is_numeric($pemilikRef)) {
                        $finalIdTersangka = $pemilikRef;
                    }
                }

                $payloadBB = [
                    'berantas_ungkap_tersangka_id' => $finalIdTersangka,
                    'jenis_barang_bukti'  => $bbData['jenis'],
                    'jumlah_barang_bukti' => $bbData['jumlah'],
                    'satuan_barang_bukti' => $bbData['satuan'],
                ];

                if (isset($bbData['id']) && $bbData['id']) {
                    $kasus->barangBukti()->where('id', $bbData['id'])->update($payloadBB);
                } else {
                    $kasus->barangBukti()->create($payloadBB);
                }
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
                        $ext = pathinfo($tempFile->filename, PATHINFO_EXTENSION);
                        $cleanName = time() . '_' . uniqid() . '_' . Str::slug(pathinfo($tempFile->filename, PATHINFO_FILENAME)) . '.' . $ext;
                        $destPath = 'dokumentasi/berantas/' . date('Y') . '/' . $cleanName;

                        if (Storage::exists($sourcePath)) {
                            Storage::disk('public')->put($destPath, Storage::readStream($sourcePath));
                            $newFilesMoved[] = $destPath;

                            $kasus->dokumentasi()->create([
                                'nama_file_asli' => $tempFile->filename,
                                'path_file'      => $destPath,
                                'tipe_file'      => Storage::mimeType($sourcePath),
                                'ukuran_file'    => Storage::size($sourcePath),
                            ]);

                            Storage::deleteDirectory('public/tmp/' . $folder);
                            $tempFile->delete();
                        }
                    }
                }
            }

            DB::commit();

            foreach ($filesToDelete as $path) {
                if(Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
            }

            return redirect()->route('berantas.ungkap-kasus.index')
                ->with('success', 'update')
                ->with('message', 'Data berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($newFilesMoved as $path) {
                if(Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
            }
            return back()->with('error', 'Gagal update: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        $kasus = BerantasUngkapKasus::with('tersangka')->findOrFail($id);
        
        $filesToDelete = [];
        foreach ($kasus->dokumentasi()->cursor() as $doc) $filesToDelete[] = $doc->path_file;
        foreach ($kasus->tersangka as $tsk) {
            if($tsk->foto_tersangka) $filesToDelete[] = $tsk->foto_tersangka;
        }

        DB::beginTransaction();
        try {
            $kasus->delete(); 
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }

        foreach ($filesToDelete as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        return back()->with('success', 'destroy')->with('message', 'Data dan file berhasil dihapus.');
    }
}