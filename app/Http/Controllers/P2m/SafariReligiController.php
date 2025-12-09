<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\P2mSafariReligi;
use App\Models\SatuanKerja;
use App\Models\Pegawai;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Exports\SafariReligiExport; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class SafariReligiController extends Controller
{
    private function getFilteredQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $activeYears = $request->filled('tahun') ? $request->tahun : [date('Y')];
        
        $query = P2mSafariReligi::with('pegawai', 'satuanKerja');

        // ... (Logic Filter Satker, Bulan, Tahun, Pegawai, Search TETAP SAMA) ...
        // Saya persingkat bagian ini karena tidak berubah logicnya
        if ($user->isAdmin() && $request->filled('satuan_kerja_id')) $query->whereIn('satuan_kerja_id', $request->satuan_kerja_id);
        elseif ($user->isOperator()) $query->where('satuan_kerja_id', $user->getSatkerId());

        if ($request->filled('bulan')) {
            $query->where(function($q) use ($request) {
                foreach ($request->bulan as $b) $q->orWhereMonth('tanggal_pelaksanaan', $b);
            });
        }
        $query->where(function($q) use ($activeYears) {
            foreach ($activeYears as $y) $q->orWhereYear('tanggal_pelaksanaan', $y);
        });

        // Filter Pegawai & Search (Sama seperti sebelumnya)... 
        // (Pastikan logic filter pegawai dan search dari kode sebelumnya tetap ada di sini)
        if ($request->filled('pegawai_nips')) {
            $nips = $request->pegawai_nips;
            $logic = $request->input('pegawai_logic', 'OR');

            if ($logic === 'OR') {
                // LOGIKA OR (Salah satu pegawai ada)
                // Gunakan whereHas + whereIn. 
                // JANGAN pakai orWhereHas di level terluar!
                $query->whereHas('pegawai', function ($q) use ($nips) {
                    $q->whereIn('nip', $nips);
                });
            } else {
                // LOGIKA AND (Semua pegawai yang dipilih harus ada di kegiatan itu)
                // Kita looping whereHas untuk memastikan setiap NIP ada
                foreach ($nips as $nip) {
                    $query->whereHas('pegawai', function ($q) use ($nip) {
                        $q->where('nip', $nip);
                    });
                }
            }
        }
        // --- UPDATE SORTING ---
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        // Ubah jumlah_peserta jadi jumlah_masyarakat di allowSort
        $allowSort = ['tempat_kegiatan', 'tanggal_pelaksanaan', 'jumlah_masyarakat', 'created_at', 'satuan_kerja'];

        if (in_array($sortBy, $allowSort)) {
            if ($sortBy === 'satuan_kerja') {
                $query->join('satuan_kerja', 'p2m_safari_religi.satuan_kerja_id', '=', 'satuan_kerja.id')
                        ->orderBy('satuan_kerja.satuan_kerja', $sortOrder)->select('p2m_safari_religi.*');
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }
        } else {
            $query->latest();
        }

        return $query;
    }

    public function index(Request $request): View {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Load Data Master (Logic sama)
        if ($user->isAdmin()) {
            $pegawais = Pegawai::orderBy('nama', 'asc')->get(['nip', 'nama']);
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        } else {
            $satkerId = $user->getSatkerId();
            $pegawais = Pegawai::where('satuan_kerja_id', $satkerId)->orderBy('nama', 'asc')->get(['nip', 'nama']);
            $satuanKerjas = [];
        }
        $years = P2mSafariReligi::selectRaw('YEAR(tanggal_pelaksanaan) as year')->distinct()->orderBy('year', 'desc')->pluck('year');
        
        $query = $this->getFilteredQuery($request);
        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : 10;
        
        $safari_religis = $query->paginate($perPage)->withQueryString();
        
        return view('p2m.safari-religi.index', compact('safari_religis', 'satuanKerjas', 'years', 'pegawais', 'user'));
    }

    public function export(Request $request) {
        $query = $this->getFilteredQuery($request);
        return Excel::download(new SafariReligiExport($query), 'Laporan_P2M_Safari_Religi.xlsx');
    }

    public function create(): View {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        // Logic Data Master Create (Sama)
        if ($user->isAdmin()) {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
            $pegawais = Pegawai::with('satuanKerja')->orderBy('nama', 'asc')->get();
        } else {
            $satuanKerjas = [];
            $satkerId = $user->getSatkerId();
            $pegawais = Pegawai::with('satuanKerja')->where('satuan_kerja_id', $satkerId)->orderBy('nama', 'asc')->get();
        }
        return view('p2m.safari-religi.create', compact('satuanKerjas', 'pegawais'));
    }

    public function store(Request $request) {

        /** @var \App\Models\User $user */
        $user = Auth::user();
        // --- UPDATE VALIDASI ---
        $rules = [
            'tanggal_pelaksanaan' => 'required|date',
            'tempat_kegiatan' => 'required',
            // Ganti jadi jumlah_masyarakat
            'jumlah_masyarakat' => 'required|numeric|min:0',
            'link_kelengkapan_dokumentasi' => 'required',
            'pegawai_nips' => 'required|array',
            'pegawai_nips.*' => 'exists:pegawai,nip',
        ];

        if ($user->isAdmin()) $rules['satuan_kerja_id'] = 'required|exists:satuan_kerja,id';

        $validasi = $request->validate($rules);

        DB::transaction(function () use ($user, $validasi) {
            $pegawaiNips = $validasi['pegawai_nips'];
            $dataKegiatan = collect($validasi)->except('pegawai_nips')->toArray();

            if ($user->isOperator()) $dataKegiatan['satuan_kerja_id'] = $user->getSatkerId();

            $kegiatan = P2mSafariReligi::create($dataKegiatan);
            $kegiatan->pegawai()->attach($pegawaiNips);
        });

        return redirect()->route('p2m.safari_religi.index')
            ->with('success', 'store')->with('message', 'Berhasil menambahkan data Safari Religi');
    }

    public function edit($id): View {
        
        // Logic Edit (Sama)
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kegiatan = P2mSafariReligi::with('pegawai')->findOrFail($id);
        if ($user->isOperator() && $kegiatan->satuan_kerja_id !== $user->getSatkerId()) abort(403);

        if ($user->isAdmin()) {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
            $pegawais = Pegawai::orderBy('nama', 'asc')->get();
        } else {
            $satuanKerjas = [];
            $satkerId = $user->getSatkerId();
            $pegawais = Pegawai::where('satuan_kerja_id', $satkerId)->orderBy('nama', 'asc')->get();
        }
        $selectedPegawaiNips = $kegiatan->pegawai->pluck('nip')->toArray();
        return view('p2m.safari-religi.edit', compact('kegiatan', 'satuanKerjas', 'pegawais', 'selectedPegawaiNips'));
    }

    public function update(Request $request, $id) {
        
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kegiatan = P2mSafariReligi::findOrFail($id);
        if ($user->isOperator() && $kegiatan->satuan_kerja_id !== $user->getSatkerId()) abort(403);

        // --- UPDATE VALIDASI ---
        $rules = [
            'tanggal_pelaksanaan' => 'required|date',
            'tempat_kegiatan' => 'required',
            // Ganti jadi jumlah_masyarakat
            'jumlah_masyarakat' => 'required|numeric|min:0',
            'link_kelengkapan_dokumentasi' => 'required',
            'pegawai_nips' => 'required|array',
        ];

        if ($user->isAdmin()) $rules['satuan_kerja_id'] = 'required';

        $validasi = $request->validate($rules);

        DB::transaction(function () use ($validasi, $kegiatan, $user) {
            $pegawaiNips = $validasi['pegawai_nips'];
            $dataUpdate = collect($validasi)->except('pegawai_nips')->toArray();
            if ($user->isOperator()) unset($dataUpdate['satuan_kerja_id']);

            $kegiatan->update($dataUpdate);
            $kegiatan->pegawai()->sync($pegawaiNips);
        });

        return redirect()->route('p2m.safari_religi.index')
            ->with('success', 'update')->with('message', 'Data berhasil diperbarui');
    }

    // Destroy Method (Sama)
    public function destroy($id) {

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $data = P2mSafariReligi::findOrFail($id);
        if ($user->isOperator() && $data->satuan_kerja_id !== $user->getSatkerId()) abort(403);
        $data->delete();
        return redirect()->back()->with('success', 'destroy')->with('message', 'Data berhasil dihapus');
    }
}