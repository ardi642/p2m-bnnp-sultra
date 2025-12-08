<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\P2mDesaBersinar;
use App\Models\SatuanKerja;
use App\Models\Pegawai;
use App\Models\KabupatenKota;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Exports\DesaBersinarExport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DesaBersinarController extends Controller
{
    private function getFilteredQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $activeYears = $request->filled('tahun') ? $request->tahun : [date('Y')];
        $query = P2mDesaBersinar::with('pegawai', 'satuanKerja', 'kabupatenKota');

        if ($user->isAdmin()) {
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('satuan_kerja_id', $request->satuan_kerja_id);
            }
        } else if ($user->isOperator()) {
            $satkerId = $user->getSatkerId();
            $query->where('satuan_kerja_id', $satkerId);
        }

        if ($request->filled('bulan')) {
            $query->where(function($q) use ($request) {
                foreach ($request->bulan as $b) {
                    $q->orWhereMonth('tanggal_pencanangan', $b);
                }
            });
        }

        $query->where(function($q) use ($activeYears) {
            foreach ($activeYears as $y) {
                $q->orWhereYear('tanggal_pencanangan', $y);
            }
        });

        if ($request->filled('anggaran_pembentukan')) {
            $query->whereIn('anggaran_pembentukan', $request->anggaran_pembentukan);
        }

        if ($request->filled('kabupaten_kota_id')) {
            $query->whereIn('kabupaten_kota_id', $request->kabupaten_kota_id);
        }

        // Filter Pegawai
        if ($request->filled('pegawai_nips')) {
            $nips = $request->pegawai_nips;
            $logic = $request->input('pegawai_logic', 'OR');
            if ($logic === 'AND') {
                foreach ($nips as $nip) {
                    $query->whereHas('pegawai', fn($q) => $q->where('nip', $nip));
                }
            } else {
                $query->whereHas('pegawai', fn($q) => $q->whereIn('nip', $nips));
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_desa', 'LIKE', "%{$search}%")
                    ->orWhere('nama_kelurahan', 'LIKE', "%{$search}%")
                    ->orWhereHas('kabupatenKota', fn($sub) => $sub->where('nama', 'LIKE', "%{$search}%"))
                    ->orWhereHas('pegawai', fn($sub) => $sub->where('nama', 'LIKE', "%{$search}%"));
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowSort = [
            'anggaran_pembentukan',
            'nama_desa',
            'nama_kelurahan',
            'tanggal_pencanangan',
            'jumlah_penggiat',
            'keberadaan_ibm',
            'created_at',
            'satuan_kerja',
            'kabupaten_kota'
        ];

        if (in_array($sortBy, $allowSort)) {
            if ($sortBy === 'satuan_kerja') {
                $query->join('satuan_kerja', 'p2m_desa_bersinar.satuan_kerja_id', '=', 'satuan_kerja.id')
                    ->orderBy('satuan_kerja.satuan_kerja', $sortOrder)
                    ->select('p2m_desa_bersinar.*');
            } elseif ($sortBy === 'kabupaten_kota') {
                $query->join('kabupaten_kota', 'p2m_desa_bersinar.kabupaten_kota_id', '=', 'kabupaten_kota.id')
                    ->orderBy('kabupaten_kota.nama', $sortOrder)
                    ->select('p2m_desa_bersinar.*');
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }
        } else {
            $query->latest();
        }

        return $query;
    }

    public function index(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->isAdmin()) {
            $pegawais = Pegawai::orderBy('nama')->get(['nip', 'nama']);
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja')->get();
            $kabupatenKotas = KabupatenKota::orderBy('nama')->get();
        } else {
            $satkerId = $user->getSatkerId();
            $pegawais = Pegawai::where('satuan_kerja_id', $satkerId)->orderBy('nama')->get(['nip', 'nama']);
            $satuanKerjas = [];
            $kabupatenKotas = KabupatenKota::orderBy('nama')->get();
        }

        $years = P2mDesaBersinar::selectRaw('YEAR(tanggal_pencanangan) as year')
                        ->distinct()
                        ->orderBy('year', 'desc')
                        ->pluck('year');

        $query = $this->getFilteredQuery($request);
        $perPage = in_array($request->per_page, [10, 25, 50, 100]) ? $request->per_page : 10;
        $desaBersinars = $query->paginate($perPage)->withQueryString();

        return view('p2m.desa-bersinar.index', compact('desaBersinars', 'satuanKerjas', 'years', 'pegawais', 'kabupatenKotas', 'user'));
    }

    public function export(Request $request)
    {
        $query = $this->getFilteredQuery($request);
        return Excel::download(new DesaBersinarExport($query), 'Laporan_P2M_Desa_Bersinar.xlsx');
    }

    public function create(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $satkerId = $user->getSatkerId();

        $pegawais = Pegawai::where('satuan_kerja_id', $satkerId)->orderBy('nama')->get();
        $kabupatenKotas = KabupatenKota::orderBy('nama')->get();

        return view('p2m.desa-bersinar.create', compact('pegawais', 'kabupatenKotas'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $satkerId = $user->getSatkerId();

        $validasi = $request->validate([
            'anggaran_pembentukan' => 'required|in:DIPA,NON DIPA',
            'nama_desa' => 'required|string|max:255',
            'nama_kelurahan' => 'required|string|max:255',
            'kabupaten_kota_id' => 'required|exists:kabupaten_kota,id',
            'tanggal_pencanangan' => 'required|date',
            'jumlah_penggiat' => 'required|integer|min:1',
            'keberadaan_ibm' => 'required|in:ada,belum ada',
            'nomor_hp_penanggung_jawab' => 'required|string',
            'link_kelengkapan_dokumentasi' => 'required|url',
            'pegawai_nips' => 'required|array',
            'pegawai_nips.*' => 'exists:pegawai,nip',
        ]);

        DB::transaction(function () use ($satkerId, $validasi) {
            $data = $validasi;
            
            $data = collect($validasi)->except('pegawai_nips')->toArray();
            $pegawaiNips = $validasi['pegawai_nips'];

            $data['satuan_kerja_id'] = $satkerId;
            $desa = P2mDesaBersinar::create($data);

            $desa->pegawai()->attach($pegawaiNips);
        });

        return redirect()->route('p2m.desa_bersinar.index')
            ->with('success', 'store')
            ->with('message', 'Data Desa Bersinar berhasil ditambahkan');
    }

    public function edit($id): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $satkerId = $user->getSatkerId();

        $desa = P2mDesaBersinar::with('pegawai')->where('satuan_kerja_id', $satkerId)->findOrFail($id);

        $pegawais = Pegawai::where('satuan_kerja_id', $satkerId)->orderBy('nama')->get();
        $kabupatenKotas = KabupatenKota::orderBy('nama')->get();
        $selectedPegawaiNips = $desa->pegawai->pluck('nip')->toArray();

        return view('p2m.desa-bersinar.edit', compact('desa', 'pegawais', 'kabupatenKotas', 'selectedPegawaiNips'));
    }

    public function update(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $satkerId = $user->getSatkerId();

        $desa = P2mDesaBersinar::where('satuan_kerja_id', $satkerId)->findOrFail($id);

        $validasi = $request->validate([
            'anggaran_pembentukan' => 'required|in:DIPA,NON DIPA',
            'nama_desa' => 'required|string|max:255',
            'nama_kelurahan' => 'required|string|max:255',
            'kabupaten_kota_id' => 'required|exists:kabupaten_kota,id',
            'tanggal_pencanangan' => 'required|date',
            'jumlah_penggiat' => 'required|integer|min:1',
            'keberadaan_ibm' => 'required|in:ada,belum ada',
            'nomor_hp_penanggung_jawab' => 'required|string',
            'link_kelengkapan_dokumentasi' => 'required|url',
            'pegawai_nips' => 'required|array',
            'pegawai_nips.*' => 'exists:pegawai,nip',
        ]);

        DB::transaction(function () use ($validasi, $desa) {
            $pegawaiNips = $validasi['pegawai_nips'];
            $dataUpdate = collect($validasi)->except('pegawai_nips')->toArray();
            unset($dataUpdate['satuan_kerja_id']); 
            $desa->update($dataUpdate);
            $desa->pegawai()->sync($pegawaiNips);
        });

        return redirect()->route('p2m.desa_bersinar.index')
            ->with('success', 'update')
            ->with('message', 'Data Desa Bersinar berhasil diperbarui');
    }

    public function destroy($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $satkerId = $user->getSatkerId();

        $desa = P2mDesaBersinar::where('satuan_kerja_id', $satkerId)->findOrFail($id);
        $desa->delete();

        return redirect()->back()
            ->with('success', 'destroy')
            ->with('message', 'Data Desa Bersinar berhasil dihapus');
    }
}