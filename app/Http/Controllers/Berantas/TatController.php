<?php

namespace App\Http\Controllers\Berantas;

use App\Http\Controllers\Controller;
use App\Models\BerantasTat;
use App\Models\SatuanKerja;
use App\Models\DokumentasiKegiatan;
use App\Models\TemporaryFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TatExport;
use Illuminate\Support\Str;

class TatController extends Controller
{
    private function getFilteredQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Cek apakah ada filter spesifik yang aktif
        $isUsingAdvancedFilter = $request->anyFilled([
            'filter_register', 'filter_nama', 'filter_nik', 
            'filter_instansi', 'filter_status', 'filter_tgl_mulai',
            'filter_narkoba', 'filter_tindak_lanjut', 'filter_lembaga' // Tambahan
        ]);

        $activeYears = $request->filled('tahun') ? $request->tahun : ($isUsingAdvancedFilter ? [] : [date('Y')]);

        $query = BerantasTat::with('satuanKerja', 'dokumentasi');

        // 1. SCOPE
        if ($user->hasRole('admin')) {
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('satuan_kerja_id', $request->satuan_kerja_id);
            }
        } else {
            $query->where('satuan_kerja_id', $user->getSatkerId());
        }

        // 2. WAKTU
        if ($request->filled('bulan')) {
            $query->where(function($q) use ($request) {
                foreach ($request->bulan as $b) {
                    $q->orWhereMonth('tanggal_pelaksanaan', $b);
                }
            });
        }
        
        if (!empty($activeYears)) {
            $query->where(function($q) use ($activeYears) {
                foreach ($activeYears as $y) {
                    $q->orWhereYear('tanggal_pelaksanaan', $y);
                }
            });
        }

        // 3. PENCARIAN UMUM (General Search)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('no_register', 'LIKE', "%{$search}%")
                  ->orWhere('nama_tersangka', 'LIKE', "%{$search}%")
                  ->orWhere('instansi_pengirim', 'LIKE', "%{$search}%")
                  ->orWhere('jenis_narkoba', 'LIKE', "%{$search}%")
                  ->orWhere('proses_hukum_lanjut', 'LIKE', "%{$search}%");
            });
        }

        // 4. FILTER SPESIFIK LENGKAP (Advanced Filter)
        
        if ($request->filled('filter_register')) {
            $query->where('no_register', 'like', '%' . $request->filter_register . '%');
        }
        if ($request->filled('filter_nama')) {
            $query->where('nama_tersangka', 'like', '%' . $request->filter_nama . '%');
        }
        if ($request->filled('filter_nik')) {
            $query->where('nik', 'like', '%' . $request->filter_nik . '%');
        }
        if ($request->filled('filter_instansi')) {
            $query->where('instansi_pengirim', 'like', '%' . $request->filter_instansi . '%');
        }
        if ($request->filled('filter_status')) {
            $query->where('proses_hukum_lanjut', 'like', '%' . $request->filter_status . '%');
        }
        if ($request->filled('filter_jk')) {
            $query->where('jenis_kelamin', $request->filter_jk);
        }
        // Tambahan Filter Baru
        if ($request->filled('filter_narkoba')) {
            $query->where('jenis_narkoba', 'like', '%' . $request->filter_narkoba . '%');
        }
        if ($request->filled('filter_lembaga')) {
            $query->where('lembaga_rehab', 'like', '%' . $request->filter_lembaga . '%');
        }
        if ($request->filled('filter_tindak_lanjut')) {
            $query->where('tindak_lanjut_rekomendasi', $request->filter_tindak_lanjut);
        }

        // Range Tanggal
        if ($request->filled('filter_tgl_mulai') && $request->filled('filter_tgl_selesai')) {
            $query->whereBetween('tanggal_pelaksanaan', [
                $request->filter_tgl_mulai, 
                $request->filter_tgl_selesai
            ]);
        }

        // 5. SORTING
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $allowedSorts = [
            'created_at', 'no_register', 'tanggal_pelaksanaan', 
            'nama_tersangka', 'instansi_pengirim', 'proses_hukum_lanjut', 
            'jumlah_satuan'
        ];

        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        return $query->orderBy($sortBy, $sortOrder);
    }

    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $satuanKerjas = $user->hasRole('admin') ? SatuanKerja::orderBy('satuan_kerja')->get() : [];
        
        $yearQuery = BerantasTat::selectRaw('YEAR(tanggal_pelaksanaan) as year');
        if ($user->isOperator()) {
            $yearQuery->where('satuan_kerja_id', $user->getSatkerId());
        }
        $years = $yearQuery->distinct()->orderByDesc('year')->pluck('year');

        $query = $this->getFilteredQuery($request);
        
        $perPage = $request->input('per_page', 10);
        $data = $query->paginate($perPage)->withQueryString();

        return view('berantas.tat.index', compact('data', 'satuanKerjas', 'years'));
    }

    public function create()
    {
        return view('berantas.tat.create');
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $rules = [
            'no_register'         => 'required|unique:berantas_tat,no_register',
            'tanggal_pelaksanaan' => 'required|date',
            'nama_tersangka'      => 'required|string',
            'nik'                 => 'nullable|string',
            'jenis_kelamin'       => 'required|in:Laki-laki,Perempuan',
            'usia'                => 'required|numeric|min:1|max:120',
            'pendidikan'          => 'required|string',
            'pekerjaan'           => 'nullable|string',
            'no_telepon'          => 'nullable|string',
            'pasal_disangkakan'   => 'required|string',
            'instansi_pengirim'   => 'required|string',
            'jenis_narkoba'       => 'required|string',
            'tanggal_penangkapan' => 'required|date',
            'tanggal_permohonan'  => 'required|date',
            'jumlah_satuan'       => 'nullable|string',
            'tim_hukum'           => 'required|string',
            'tim_medis'           => 'required|string',
            'lembaga_rehab'             => 'nullable|string',
            'proses_hukum_lanjut'       => 'nullable|string',
            'tindak_lanjut_rekomendasi' => 'nullable|in:dilaksanakan,tidak dilaksanakan',
            'biaya'                     => 'nullable|numeric',
            'dokumentasi'         => 'nullable|array',
        ];

        if ($user->isAdmin()) {
            $rules['satuan_kerja_id'] = 'required|exists:satuan_kerja,id';
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) return back()->withErrors($validator)->withInput();

        DB::beginTransaction();
        try {
            $data = $request->except(['dokumentasi', 'satuan_kerja_id']);
            $data['satuan_kerja_id'] = $user->isOperator() ? $user->getSatkerId() : $request->satuan_kerja_id;

            $tat = BerantasTat::create($data);

            if ($request->filled('dokumentasi')) {
                foreach ($request->input('dokumentasi') as $folder) {
                    $tempFile = TemporaryFile::where('folder', $folder)->first();
                    if ($tempFile) {
                        $sourcePath = 'public/tmp/' . $folder . '/' . $tempFile->filename;
                        $cleanName = time() . '_' . uniqid() . '_' . Str::slug(pathinfo($tempFile->filename, PATHINFO_FILENAME)) . '.' . pathinfo($tempFile->filename, PATHINFO_EXTENSION);
                        $destPath = 'dokumentasi/berantas/tat/' . date('Y') . '/' . $cleanName;

                        if (Storage::exists($sourcePath)) {
                            Storage::disk('public')->move($sourcePath, $destPath);
                            $tat->dokumentasi()->create([
                                'nama_file_asli' => $tempFile->filename,
                                'path_file'      => $destPath,
                                'tipe_file'      => Storage::mimeType('public/'.$destPath),
                                'ukuran_file'    => Storage::size('public/'.$destPath),
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
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $tat = BerantasTat::with('dokumentasi')->findOrFail($id);
        if ($user->isOperator() && $tat->satuan_kerja_id !== $user->getSatkerId()) abort(403);
        return view('berantas.tat.edit', compact('tat'));
    }

    public function update(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $tat = BerantasTat::findOrFail($id);
        if ($user->isOperator() && $tat->satuan_kerja_id !== $user->getSatkerId()) abort(403);

        $rules = [
            'no_register'         => 'required|unique:berantas_tat,no_register,' . $id,
            'tanggal_pelaksanaan' => 'required|date',
            'nama_tersangka'      => 'required|string',
            'nik'                 => 'nullable|string',
            'jenis_kelamin'       => 'required|in:Laki-laki,Perempuan',
            'usia'                => 'required|numeric|min:1',
            'pendidikan'          => 'required|string',
            'pekerjaan'           => 'nullable|string',
            'no_telepon'          => 'nullable|string',
            'pasal_disangkakan'   => 'required|string',
            'instansi_pengirim'   => 'required|string',
            'jenis_narkoba'       => 'required|string',
            'tanggal_penangkapan' => 'required|date',
            'tanggal_permohonan'  => 'required|date',
            'jumlah_satuan'       => 'nullable|string',
            'tim_hukum'           => 'required|string',
            'tim_medis'           => 'required|string',
            'lembaga_rehab'       => 'nullable|string',
            'proses_hukum_lanjut' => 'nullable|string',
            'tindak_lanjut_rekomendasi' => 'nullable|in:dilaksanakan,tidak dilaksanakan',
            'biaya'               => 'nullable|numeric',
            'delete_files'        => 'nullable|array',
            'dokumentasi'         => 'nullable|array',
        ];

        if ($user->isAdmin()) $rules['satuan_kerja_id'] = 'required|exists:satuan_kerja,id';

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) return back()->withErrors($validator)->withInput();

        DB::beginTransaction();
        try {
            $data = $request->except(['dokumentasi', 'delete_files', 'satuan_kerja_id']);
            if ($user->isAdmin() && $request->filled('satuan_kerja_id')) {
                $data['satuan_kerja_id'] = $request->satuan_kerja_id;
            }

            $tat->update($data);

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
                        $cleanName = time() . '_' . uniqid() . '_' . Str::slug(pathinfo($tempFile->filename, PATHINFO_FILENAME)) . '.' . pathinfo($tempFile->filename, PATHINFO_EXTENSION);
                        $destPath = 'dokumentasi/berantas/tat/' . date('Y') . '/' . $cleanName;
                        if (Storage::exists($sourcePath)) {
                            Storage::disk('public')->move($sourcePath, $destPath);
                            $tat->dokumentasi()->create([
                                'nama_file_asli' => $tempFile->filename,
                                'path_file'      => $destPath,
                                'tipe_file'      => Storage::mimeType('public/'.$destPath),
                                'ukuran_file'    => Storage::size('public/'.$destPath),
                            ]);
                            Storage::deleteDirectory('public/tmp/' . $folder);
                            $tempFile->delete();
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('berantas.tat.index')->with('success', 'Data TAT berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        $tat = BerantasTat::findOrFail($id);
        DB::beginTransaction();
        try {
            $tat->delete(); 
            DB::commit();
            return back()->with('success', 'Data berhasil dihapus.');
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