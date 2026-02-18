<?php

namespace App\Http\Controllers\Rehab;

use App\Http\Controllers\Controller;
use App\Models\RehabPasien;
use App\Models\SatuanKerja;
use App\Models\BerantasNarkotika;
use Illuminate\Http\Request;
use App\Exports\RehabPasienExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class RehabPasienController extends Controller
{
    public function index(Request $request)
    {
        $tahunSekarang = date('Y');

        $query = $this->getFilteredQuery($request);

        // Kalau tidak ada filter tahun sama sekali → pakai tahun terakhir yang ada di DB
        if (!$request->filled('tahun')) {
            $tahunTerakhir = RehabPasien::selectRaw('YEAR(created_at) as tahun')
                ->orderBy('tahun', 'desc')
                ->value('tahun');

            if ($tahunTerakhir) {
                $request->merge(['tahun' => [$tahunTerakhir]]);
                $query->whereYear('created_at', $tahunTerakhir);
            }
        }

        $pasien = $query->paginate(10);

        $user = auth()->user();
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        $narkotikas = BerantasNarkotika::orderBy('nama_narkotika', 'asc')->get();

        $tahuns = RehabPasien::selectRaw('DISTINCT YEAR(created_at) as tahun')
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->toArray();

        if (!in_array($tahunSekarang, $tahuns)) {
            $tahuns[] = $tahunSekarang;
        }

        rsort($tahuns);
        return view('rehab.pasien.index', compact(
            'pasien',
            'user',
            'satuanKerjas',
            'narkotikas',
            'tahuns',
            'tahunSekarang'
        ));
    }


    public function create()
    {
        $narkotikas = BerantasNarkotika::orderBy('nama_narkotika', 'asc')->get();
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        return view('rehab.pasien.create', compact('narkotikas', 'satuanKerjas'));
    }

    public function store(Request $request)
    {
        // If user is operator (not admin), ensure we attach their satker to the request
        if (!auth()->user()->isAdmin()) {
            $satkerId = auth()->user()->getSatkerId();
            if (!$satkerId) {
                return back()->with('error', 'User belum memiliki satuan kerja.')->withInput();
            }
            $request->merge(['satuan_kerja_id' => $satkerId]);
        }

        $rules = [
            'nama_pasien' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'usia' => 'required|integer|min:0|max:120',
            'pekerjaan' => 'required|in:' . implode(',', RehabPasien::Pekerjaan),
            'pendidikan' => 'required|in:' . implode(',', RehabPasien::Pendidikan),
            'narkotika_id' => 'required|array',
            'narkotika_id.*' => 'exists:berantas_narkotika,id',
            'sumber_pasien' => 'required|in:' . implode(',', RehabPasien::Sumber_pasien),
            'satuan_kerja_id' => 'required|exists:satuan_kerja,id',
        ];
        // Note: for operators we merged satuan_kerja_id into the request above

        $validated = $request->validate($rules, [
            'nama_pasien.required' => 'Nama pasien harus diisi',
            'jenis_kelamin.required' => 'Jenis kelamin harus dipilih',
            'usia.required' => 'Usia harus diisi',
            'usia.integer' => 'Usia harus berupa angka',
            'usia.min' => 'Usia tidak boleh kurang dari 0',
            'usia.max' => 'Usia tidak boleh lebih dari 120',
            'pekerjaan.required' => 'Pekerjaan harus diisi',
            'pendidikan.required' => 'Pendidikan harus dipilih',
            'narkotika_id.required' => 'Jenis narkotika harus dipilih',
            'narkotika_id.exists' => 'Jenis narkotika tidak valid',
            'sumber_pasien.required' => 'Sumber pasien harus dipilih',
            'satuan_kerja_id.required' => 'Satuan kerja harus dipilih',
            'satuan_kerja_id.exists' => 'Satuan kerja tidak valid',
        ]);

        try {
            // Untuk Nomor Rekam Medis
            DB::transaction(function () use ($validated) {

                $tahun = now()->format('Y');
                $satkerId = str_pad($validated['satuan_kerja_id'], 2, '0', STR_PAD_LEFT);

                // Ambil nomor terakhir berdasarkan format RM03-0001/2026
                $last = RehabPasien::where('satuan_kerja_id', $validated['satuan_kerja_id'])
                    ->where('rekam_medis', 'like', "RM{$satkerId}-%/{$tahun}")
                    ->lockForUpdate()
                    ->orderByDesc('rekam_medis')
                    ->first();

                $lastNumber = 0;

                if ($last) {
                    preg_match('/-(\d+)\//', $last->rekam_medis, $matches);
                    $lastNumber = isset($matches[1]) ? (int) $matches[1] : 0;
                }

                $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

                $rekamMedis = "RM{$satkerId}-{$newNumber}/{$tahun}";

                $pasien = RehabPasien::create([
                    'satuan_kerja_id' => $validated['satuan_kerja_id'],
                    'rekam_medis'     => $rekamMedis,
                    'nama_pasien'     => $validated['nama_pasien'],
                    'jenis_kelamin'   => $validated['jenis_kelamin'],
                    'usia'            => $validated['usia'],
                    'pekerjaan'       => $validated['pekerjaan'],
                    'pendidikan'      => $validated['pendidikan'],
                    'sumber_pasien'   => $validated['sumber_pasien'],
                ]);

                // simpan ke tabel pivot
                $pasien->narkotikas()->attach($validated['narkotika_id']);
            });

            return redirect()
                ->route('rehab.pasien.index')
                ->with('success', 'Data pasien berhasil ditambahkan');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Gagal menyimpan data: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit(RehabPasien $pasien)
    {
        $narkotikas = BerantasNarkotika::orderBy('nama_narkotika', 'asc')->get();
        $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        return view('rehab.pasien.edit', compact('pasien', 'narkotikas', 'satuanKerjas'));
    }

    public function update(Request $request, RehabPasien $pasien)
    {
        // Merge satker for non-admins so validation can require it (DB requires non-null)
        if (!auth()->user()->isAdmin()) {
            $satkerId = auth()->user()->getSatkerId();
            if (!$satkerId) {
                return back()->with('error', 'User belum memiliki satuan kerja.')->withInput();
            }
            $request->merge(['satuan_kerja_id' => $satkerId]);
        }

        $rules = [
            'nama_pasien' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'usia' => 'required|integer|min:0|max:120',
            'pekerjaan' => 'required|in:' . implode(',', RehabPasien::Pekerjaan),
            'pendidikan' => 'required|in:' . implode(',', RehabPasien::Pendidikan),
            'narkotika_id' => 'required|array',
            'narkotika_id.*' => 'exists:berantas_narkotika,id',
            'sumber_pasien' => 'required|in:' . implode(',', RehabPasien::Sumber_pasien),
            'satuan_kerja_id' => 'required|exists:satuan_kerja,id',
        ];

        // Admin harus pilih satuan kerja, operator otomatis terisi
        if (auth()->user()->isAdmin()) {
            $rules['satuan_kerja_id'] = 'required|exists:satuan_kerja,id';
        }

        $validated = $request->validate($rules, [
            'nama_pasien.required' => 'Nama pasien harus diisi',
            'jenis_kelamin.required' => 'Jenis kelamin harus dipilih',
            'usia.required' => 'Usia harus diisi',
            'usia.integer' => 'Usia harus berupa angka',
            'usia.min' => 'Usia tidak boleh kurang dari 0',
            'usia.max' => 'Usia tidak boleh lebih dari 120',
            'pekerjaan.required' => 'Pekerjaan harus diisi',
            'pendidikan.required' => 'Pendidikan harus dipilih',
            'narkotika_id.required' => 'Jenis narkotika harus dipilih',
            'narkotika_id.exists' => 'Jenis narkotika tidak valid',
            'sumber_pasien.required' => 'Sumber pasien harus dipilih',
            'satuan_kerja_id.required' => 'Satuan kerja harus dipilih',
            'satuan_kerja_id.exists' => 'Satuan kerja tidak valid',
        ]);

        try {
            $pasien->update([
                'satuan_kerja_id' => $validated['satuan_kerja_id'],
                'nama_pasien'     => $validated['nama_pasien'],
                'jenis_kelamin'   => $validated['jenis_kelamin'],
                'usia'            => $validated['usia'],
                'pekerjaan'       => $validated['pekerjaan'],
                'pendidikan'      => $validated['pendidikan'],
                'sumber_pasien'   => $validated['sumber_pasien'],
            ]);

            $pasien->narkotikas()->sync($validated['narkotika_id']);

            return redirect()->route('rehab.pasien.index')->with('success', 'Data pasien berhasil diperbarui');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui data: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(RehabPasien $pasien)
    {
        try {
            $nama = $pasien->nama_pasien;
            $pasien->delete();
            return redirect()->route('rehab.pasien.index')->with('success', "Data pasien '$nama' berhasil dihapus");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    private function getFilteredQuery(Request $request)
    {
        $query = RehabPasien::with(['narkotikas', 'satuanKerja']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('rekam_medis', 'like', "%$search%")
                    ->orWhere('nama_pasien', 'like', "%$search%")
                    ->orWhere('pekerjaan', 'like', "%$search%")
                    ->orWhere('pendidikan', 'like', "%$search%");
            });
        }

        if ($request->filled('jenis_kelamin')) {
            $query->whereIn('jenis_kelamin', $request->jenis_kelamin);
        }

        if ($request->filled('pekerjaan')) {
            $query->whereIn('pekerjaan', $request->pekerjaan);
        }

        if ($request->filled('pendidikan')) {
            $query->whereIn('pendidikan', $request->pendidikan);
        }

        if ($request->filled('sumber_pasien')) {
            $query->whereIn('sumber_pasien', $request->sumber_pasien);
        }

        if ($request->filled('narkotika_id')) {
            $query->whereHas('narkotikas', function ($q) use ($request) {
                $q->whereIn('berantas_narkotika.id', $request->narkotika_id);
            });
        }

        if ($request->filled('satuan_kerja_id')) {
            $query->whereIn('satuan_kerja_id', $request->satuan_kerja_id);
        }

        if ($request->filled('tahun')) {
            $tahunFilter = $request->tahun;

            if (is_array($tahunFilter)) {
                $query->whereIn(DB::raw('YEAR(created_at)'), $tahunFilter);
            } else {
                $query->whereYear('created_at', $tahunFilter);
            }
        }

        return $query->latest();
    }

    public function export(Request $request)
    {
        $query = $this->getFilteredQuery($request)->with(['narkotikas', 'satuanKerja']);
        return Excel::download(new RehabPasienExport($query), 'Laporan_Rehab_Pasien.xlsx');
    }
}
