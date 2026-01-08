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
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UngkapKasusExport;

class UngkapKasusController extends Controller
{
    private function getFilteredQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $activeYears = $request->filled('tahun') ? $request->tahun : [date('Y')];

        $query = BerantasUngkapKasus::with([
            'satuanKerja', 
            'tersangka' => function($q) { $q->orderBy('urutan', 'asc'); }, 
            'barangBukti.tersangka' => function($q) { $q->orderBy('urutan', 'asc'); }
        ]);

        if ($user->hasRole('admin')) {
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('satuan_kerja_id', $request->satuan_kerja_id);
            }
        } else {
            $query->where('satuan_kerja_id', $user->getSatkerId());
        }

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

        $rules = [
            'nomor_lkn'        => 'required|unique:berantas_ungkap_kasus,nomor_lkn',
            'tanggal_kejadian' => 'required|date',
            'alamat_tkp'       => 'required|string',
            'tersangka'        => 'required|array|min:1',
            'tersangka.*.nama' => 'required|string',
            'tersangka.*.jk'   => 'required|in:Laki-Laki,Perempuan',
            'tersangka.*.tahap'=> 'required|string',
            'tersangka.*.pekerjaan' => 'required|string',
            'barang_bukti'     => 'required|array|min:1',
            'barang_bukti.*.jenis'  => 'required|string',
            'barang_bukti.*.jumlah' => 'required|numeric',
            // VALIDASI HANYA 3 SATUAN
            'barang_bukti.*.satuan' => 'required|in:Gram,Kg,Ton',
            'barang_bukti.*.pemilik_id' => 'required|array|min:1',
            'dokumentasi'      => 'nullable|array',
        ];

        if ($user->isAdmin()) $rules['satuan_kerja_id'] = 'required|exists:satuan_kerja,id';

        $validator = Validator::make($request->all(), $rules);

        // Validasi Relasi (Orphan Check)
        $validator->after(function ($validator) use ($request) {
            $inputTersangka = collect($request->tersangka);
            $inputBB = collect($request->barang_bukti);

            $allSuspectIds = $inputTersangka->pluck('temp_id')->filter();
            $linkedOwnerIds = $inputBB->pluck('pemilik_id')->flatten()->filter();

            $orphans = $allSuspectIds->diff($linkedOwnerIds);

            if ($orphans->isNotEmpty()) {
                $names = $inputTersangka->whereIn('temp_id', $orphans)->pluck('nama')->join(', ');
                $validator->errors()->add(
                    'tersangka_orphan', 
                    "Validasi Gagal: Tersangka ($names) belum dikaitkan dengan Barang Bukti manapun."
                );
            }
        });

        if ($validator->fails()) return back()->withErrors($validator)->withInput();

        DB::beginTransaction();
        try {
            $kasus = BerantasUngkapKasus::create([
                'nomor_lkn'        => $request->nomor_lkn,
                'tanggal_kejadian' => $request->tanggal_kejadian,
                'alamat_tkp'       => $request->alamat_tkp,
                'satuan_kerja_id'  => $user->isOperator() ? $satkerId : $request->satuan_kerja_id,
            ]);

            $mapId = []; 
            $urutanTsk = 1;
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
                    'pekerjaan'      => $tData['pekerjaan'],
                    'tahap'          => $tData['tahap'],
                    'foto_tersangka' => $fotoPath,
                    'urutan'         => $urutanTsk++,
                ]);

                if (isset($tData['temp_id'])) $mapId[$tData['temp_id']] = $tersangka->id;
            }

            $urutanBB = 1;
            foreach ($request->barang_bukti as $bbData) {
                $bb = $kasus->barangBukti()->create([
                    'jenis_barang_bukti'  => $bbData['jenis'],
                    'jumlah_barang_bukti' => $bbData['jumlah'],
                    'satuan_barang_bukti' => $bbData['satuan'],
                    'urutan'              => $urutanBB++,
                ]);

                $realOwnerIds = [];
                foreach ($bbData['pemilik_id'] as $tempId) {
                    if (isset($mapId[$tempId])) $realOwnerIds[] = $mapId[$tempId];
                }
                if (!empty($realOwnerIds)) $bb->tersangka()->attach($realOwnerIds);
            }

            if ($request->filled('dokumentasi')) {
                foreach ($request->input('dokumentasi') as $folder) {
                    $tempFile = TemporaryFile::where('folder', $folder)->first();
                    if ($tempFile) {
                        $sourcePath = 'public/tmp/' . $folder . '/' . $tempFile->filename;
                        $destPath = 'dokumentasi/berantas/' . date('Y') . '/' . $tempFile->filename;
                        if (Storage::exists($sourcePath)) {
                            Storage::disk('public')->move($sourcePath, $destPath);
                            $kasus->dokumentasi()->create([
                                'nama_file_asli' => $tempFile->filename,
                                'path_file' => $destPath,
                                'tipe_file' => Storage::mimeType('public/'.$destPath),
                                'ukuran_file' => Storage::size('public/'.$destPath),
                            ]);
                            Storage::deleteDirectory('public/tmp/' . $folder);
                            $tempFile->delete();
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('berantas.ungkap-kasus.index')->with('success', 'Data Berhasil Disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kasus = BerantasUngkapKasus::with([
            'tersangka' => function($q) { $q->orderBy('urutan', 'asc'); },
            'barangBukti.tersangka', 
            'dokumentasi'
        ])->findOrFail($id);

        if ($user->isOperator() && $kasus->satuan_kerja_id !== $user->getSatkerId()) abort(403);
        return view('berantas.ungkap-kasus.edit', compact('kasus'));
    }

    public function update(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kasus = BerantasUngkapKasus::findOrFail($id);
        if ($user->isOperator() && $kasus->satuan_kerja_id !== $user->getSatkerId()) abort(403);

        $rules = [
            'nomor_lkn'        => 'required|unique:berantas_ungkap_kasus,nomor_lkn,' . $id,
            'tanggal_kejadian' => 'required|date',
            'alamat_tkp'       => 'required|string',
            'tersangka'        => 'required|array|min:1',
            'tersangka.*.nama' => 'required|string',
            'tersangka.*.jk'   => 'required|in:Laki-Laki,Perempuan',
            'tersangka.*.tahap'=> 'required|string',
            'tersangka.*.pekerjaan' => 'required|string',
            'barang_bukti'     => 'required|array|min:1',
            'barang_bukti.*.jenis'  => 'required|string',
            'barang_bukti.*.jumlah' => 'required|numeric',
            // VALIDASI HANYA 3 SATUAN
            'barang_bukti.*.satuan' => 'required|in:Gram,Kg,Ton',
            'barang_bukti.*.pemilik_id' => 'required|array|min:1',
        ];

        if ($user->isAdmin()) $rules['satuan_kerja_id'] = 'required|exists:satuan_kerja,id';

        $validator = Validator::make($request->all(), $rules);
        $validator->after(function ($validator) use ($request) {
            $inputTersangka = collect($request->tersangka);
            $inputBB = collect($request->barang_bukti);
            $allSuspectIds = $inputTersangka->pluck('temp_id')->filter();
            $linkedOwnerIds = $inputBB->pluck('pemilik_id')->flatten()->filter();
            $orphans = $allSuspectIds->diff($linkedOwnerIds);
            if ($orphans->isNotEmpty()) {
                $names = $inputTersangka->whereIn('temp_id', $orphans)->pluck('nama')->join(', ');
                $validator->errors()->add('tersangka_orphan', "Update Gagal: Tersangka ($names) belum dikaitkan dengan BB.");
            }
        });

        if ($validator->fails()) return back()->withErrors($validator)->withInput();

        DB::beginTransaction();
        try {
            $dataUpdate = [
                'nomor_lkn' => $request->nomor_lkn,
                'tanggal_kejadian' => $request->tanggal_kejadian,
                'alamat_tkp' => $request->alamat_tkp,
            ];
            if ($user->isAdmin()) $dataUpdate['satuan_kerja_id'] = $request->satuan_kerja_id;
            $kasus->update($dataUpdate);

            // SYNC TERSANGKA
            $inputTersangka = $request->tersangka ?? [];
            $existingIds = collect($inputTersangka)->pluck('id')->filter()->toArray();
            
            $kasus->tersangka()->whereNotIn('id', $existingIds)->each(function($t) {
                if($t->foto_tersangka) Storage::disk('public')->delete($t->foto_tersangka);
                $t->delete();
            });

            $mapId = [];
            $urutanTsk = 1;
            foreach ($inputTersangka as $index => $tData) {
                $payload = [
                    'nama_tersangka' => $tData['nama'],
                    'jenis_kelamin'  => $tData['jk'],
                    'pekerjaan'      => $tData['pekerjaan'],
                    'tahap'          => $tData['tahap'],
                    'urutan'         => $urutanTsk++,
                ];

                if ($request->hasFile("tersangka.{$index}.foto")) {
                    $file = $request->file("tersangka.{$index}.foto");
                    $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $payload['foto_tersangka'] = $file->storeAs('foto_tersangka/' . date('Y'), $filename, 'public');
                }

                if (isset($tData['id']) && $tData['id']) {
                    $model = $kasus->tersangka()->find($tData['id']);
                    if ($model) {
                        if (isset($payload['foto_tersangka']) && $model->foto_tersangka) {
                            Storage::disk('public')->delete($model->foto_tersangka);
                        }
                        $model->update($payload);
                    }
                } else {
                    $model = $kasus->tersangka()->create($payload);
                }

                if ($model) {
                    if (isset($tData['temp_id'])) $mapId[$tData['temp_id']] = $model->id;
                    if (isset($tData['id'])) $mapId[$tData['id']] = $model->id;
                }
            }

            // SYNC BB
            $inputBB = $request->barang_bukti ?? [];
            $existingBBIds = collect($inputBB)->pluck('id')->filter()->toArray();
            $kasus->barangBukti()->whereNotIn('id', $existingBBIds)->delete();

            $urutanBB = 1;
            foreach ($inputBB as $bbData) {
                $payloadBB = [
                    'jenis_barang_bukti'  => $bbData['jenis'],
                    'jumlah_barang_bukti' => $bbData['jumlah'],
                    'satuan_barang_bukti' => $bbData['satuan'],
                    'urutan'              => $urutanBB++,
                ];

                if (isset($bbData['id']) && $bbData['id']) {
                    $bb = $kasus->barangBukti()->find($bbData['id']);
                    $bb->update($payloadBB);
                } else {
                    $bb = $kasus->barangBukti()->create($payloadBB);
                }

                $realOwnerIds = [];
                foreach ($bbData['pemilik_id'] as $tempId) {
                    if (isset($mapId[$tempId])) $realOwnerIds[] = $mapId[$tempId];
                    elseif (is_numeric($tempId)) $realOwnerIds[] = $tempId;
                }
                $bb->tersangka()->sync($realOwnerIds);
            }

            // FILES
            if ($request->has('delete_files')) {
                foreach (DokumentasiKegiatan::whereIn('id', $request->delete_files)->get() as $file) {
                    if(Storage::disk('public')->exists($file->path_file)) Storage::disk('public')->delete($file->path_file);
                    $file->delete();
                }
            }
            if ($request->filled('dokumentasi')) {
                foreach ($request->input('dokumentasi') as $folder) {
                    $tempFile = TemporaryFile::where('folder', $folder)->first();
                    if ($tempFile) {
                        $sourcePath = 'public/tmp/' . $folder . '/' . $tempFile->filename;
                        $destPath = 'dokumentasi/berantas/' . date('Y') . '/' . $tempFile->filename;
                        if (Storage::exists($sourcePath)) {
                            Storage::disk('public')->move($sourcePath, $destPath);
                            $kasus->dokumentasi()->create([
                                'nama_file_asli' => $tempFile->filename,
                                'path_file' => $destPath,
                                'tipe_file' => Storage::mimeType('public/'.$destPath),
                                'ukuran_file' => Storage::size('public/'.$destPath),
                            ]);
                            Storage::deleteDirectory('public/tmp/' . $folder);
                            $tempFile->delete();
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('berantas.ungkap-kasus.index')->with('success', 'Data diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        $kasus = BerantasUngkapKasus::findOrFail($id);
        DB::beginTransaction();
        try {
            $kasus->delete(); DB::commit(); return back()->with('success', 'Data dihapus.');
        } catch (\Exception $e) { DB::rollBack(); return back()->with('error', $e->getMessage()); }
    }

    public function export(Request $request)
    {
        $query = $this->getFilteredQuery($request);
        return Excel::download(new UngkapKasusExport($query), 'Laporan_Ungkap_Kasus_'.date('d-m-Y').'.xlsx');
    }
}