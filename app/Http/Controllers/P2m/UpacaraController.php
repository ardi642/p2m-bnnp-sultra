<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\P2mUpacara;
use App\Models\SatuanKerja;
use App\Models\Pegawai; // Import Model Pegawai
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\UpacaraExport; // Import Export Class
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel; // Import Facade Excel

class UpacaraController extends Controller
{
    private function getFilteredQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $activeYears = $request->filled('tahun') ? $request->tahun : [date('Y')];

        $query = P2mUpacara::with('pegawai', 'satuanKerja');

        // --- FILTER SAMA PERSIS SEPERTI SEBELUMNYA ---
        if ($user->isAdmin()) {
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('satuan_kerja_id', $request->satuan_kerja_id);
            }
        } else if ($user->isOperator()) {
            $satkerId = $user->getSatkerId();
            $query->where('satuan_kerja_id', $satkerId);
        }

        if ($request->filled('bulan')) {
            $query->where(function ($q) use ($request) {
                foreach ($request->bulan as $b) {
                    $q->orWhereMonth('tanggal_pelaksanaan', $b);
                }
            });
        }
        $query->where(function ($q) use ($activeYears) {
            foreach ($activeYears as $y) {
                $q->orWhereYear('tanggal_pelaksanaan', $y);
            }
        });

        // Filter Pegawai
        if ($request->filled('pegawai_nips')) {
            $nips = $request->pegawai_nips;
            $logic = $request->input('pegawai_logic', 'OR');
            if ($logic === 'AND') {
                foreach ($nips as $nip) {
                    $query->whereHas('pegawai', function ($q) use ($nip) {
                        $q->where('pegawai.nip', $nip);
                    });
                }
            } else {
                $query->whereHas('pegawai', function ($q) use ($nips) {
                    $q->whereIn('pegawai.nip', $nips);
                });
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_sekolah', 'LIKE', "%{$search}%")
                    ->orWhereHas('satuanKerja', function ($subQ) use ($search) {
                        $subQ->where('satuan_kerja', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('pegawai', function ($subQ) use ($search) {
                        $subQ->where('nama', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowSort = ['nama_Sekolah', 'tanggal_pelaksanaan', 'jumlah_peserta', 'created_at', 'satuan_kerja'];

        if (in_array($sortBy, $allowSort)) {
            if ($sortBy === 'satuan_kerja') {
                $query->join('satuan_kerja', 'p2m_upacara.satuan_kerja_id', '=', 'satuan_kerja.id')
                    ->orderBy('satuan_kerja.satuan_kerja', $sortOrder)
                    ->select('p2m_upacara.*');
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
        // Data Master

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->isAdmin()) {
            $pegawais = Pegawai::orderBy('nama', 'asc')->get(['nip', 'nama']);
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        } else if ($user->isOperator()) {
            $satkerId = $user->getSatkerId();
            $pegawais = Pegawai::where('satuan_kerja_id', $satkerId)
                ->orderBy('nama', 'asc')
                ->get(['nip', 'nama']);
            $satuanKerjas = [];
        }

        $years = P2mUpacara::selectRaw('YEAR(tanggal_pelaksanaan) as year')->distinct()->orderBy('year', 'desc')->pluck('year');

        $query = $this->getFilteredQuery($request);

        $perPage = $request->input('per_page', 10);

        // Validasi keamanan (agar user tidak iseng input angka 1000000 bikin server down)
        // Hanya izinkan angka: 10, 25, 50, 100
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }
        $upacaras = $query->paginate($perPage)->withQueryString();

        return view('p2m.upacara.index', compact('upacaras', 'satuanKerjas', 'years', 'pegawais', 'user'));
    }

    public function export(Request $request)
    {
        // Panggil fungsi query yang SAMA PERSIS dengan index
        // Bedanya: Kita tidak pakai paginate(), tapi langsung lempar ke Class Export
        $query = $this->getFilteredQuery($request);

        return Excel::download(new UpacaraExport($query), 'Laporan_P2M_Upacara.xlsx');
    }

    public function create(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->isAdmin()) {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
            $pegawais = Pegawai::with('satuanKerja')->orderBy('nama', 'asc')->get();
        } else if ($user->isOperator()) {
            $satuanKerjas = [];
            $satkerId = $user->getSatkerId();
            $pegawais = Pegawai::with('satuanKerja')
                ->where('satuan_kerja_id', $satkerId)
                ->orderBy('nama', 'asc')
                ->get();
        }
        return view('p2m.upacara.create', compact('satuanKerjas', 'pegawais'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validasi = $request->validate([
            'nama_sekolah' => 'required',
            'tanggal_pelaksanaan' => 'required',
            'jumlah_peserta' => 'required',
            'link_kelengkapan_dokumentasi' => 'required',

            // Validasi Array Pegawai (NIP)
            'pegawai_nips' => 'required|array', // Harus berbentuk array
            'pegawai_nips.*' => 'exists:pegawai,nip', // Pastikan NIP valid di DB
        ]);

        if ($user->isAdmin()) {
            $rules['satuan_kerja_id'] = 'required';
        }

        // Gunakan Database Transaction agar data aman (jika gagal simpan pivot, data utama batal)
        DB::transaction(function () use ($user, $validasi) {

            // 2. Pisahkan data pegawai dari data utama
            // Kita hapus 'pegawai_nips' dari array validasi karena kolom ini tidak ada di tabel p2m_upacara
            $dataKegiatan = collect($validasi)->except('pegawai_nips')->toArray();
            $pegawaiNips = $validasi['pegawai_nips'];

            if ($user->isOperator()) {
                $dataKegiatan['satuan_kerja_id'] = $user->getSatkerId();
            }

            // 3. Simpan Data Kegiatan (Tabel Utama)
            $kegiatan = P2mUpacara::create($dataKegiatan);

            // 4. Simpan Relasi Pegawai (Tabel Pivot)
            // Menggunakan method attach() untuk many-to-many
            $kegiatan->pegawai()->attach($pegawaiNips);
        });

        return redirect()->route('p2m.upacara.index')
            ->with('success', 'store')
            ->with('message', 'Berhasil menambahkan data');
    }

    // BATAS KERJAKU SAMPE DI EDIT
    public function edit($id): View 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Ambil Data Kegiatan beserta relasi Pegawai (untuk pre-fill input)
        $kegiatan = P2mUpacara::with('pegawai')->findOrFail($id);

        // Proteksi Hak Akses
        // Jika Operator mencoba edit data milik Satker lain -> 403 Forbidden
        if ($user->isOperator() && $kegiatan->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403, 'Anda tidak berhak mengubah data Satuan Kerja lain.');
        }

        // Siapkan Data Master (Logic sama seperti Create)
        if ($user->isAdmin()) {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
            $pegawais = Pegawai::orderBy('nama', 'asc')->get();
        } 
        else {
            $satuanKerjas = []; // Tidak dipakai di view operator
            $satkerId = $user->getSatkerId();
            $pegawais = Pegawai::where('satuan_kerja_id', $satkerId)
                ->orderBy('nama', 'asc')
                ->get();
        }

        // Ambil Array NIP Pegawai yang sudah terpilih sebelumnya
        // Ini penting untuk mengisi Tom Select nanti
        $selectedPegawaiNips = $kegiatan->pegawai->pluck('nip')->toArray();

        return view('p2m.upacara.edit', compact('kegiatan', 'satuanKerjas', 'pegawais', 'selectedPegawaiNips'));
    }

    public function update(Request $request, $id) 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kegiatan = P2mUpacara::findOrFail($id);

        // Proteksi Update
        if ($user->isOperator() && $kegiatan->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403);
        }

        // Validasi
        $rules = [
            'nama_sekolah' => 'required',
            'tanggal_pelaksanaan' => 'required',
            'jumlah_peserta' => 'required',
            'link_kelengkapan_dokumentasi' => 'required',
            'pegawai_nips' => 'required|array',
            'pegawai_nips.*' => 'exists:pegawai,nip', 
        ];

        // Jika Admin edit, validasi satker. Jika Operator, abaikan (pakai data lama)
        if ($user->isAdmin()) {
            $rules['satuan_kerja_id'] = 'required';
        }

        $validasi = $request->validate($rules);

        DB::transaction(function () use ($validasi, $kegiatan, $user) {
            
            $pegawaiNips = $validasi['pegawai_nips'];
            $dataUpdate = collect($validasi)->except('pegawai_nips')->toArray();

            // PENTING: Untuk Operator, JANGAN update satuan_kerja_id (biarkan yang lama)
            // Untuk Admin, update sesuai input form
            if ($user->isOperator()) {
                unset($dataUpdate['satuan_kerja_id']); 
            }

            // Update Data Utama
            $kegiatan->update($dataUpdate);

            // Update Relasi Pegawai (SYNC)
            // sync() akan menghapus yang tidak dipilih, dan menambah yang baru dipilih
            $kegiatan->pegawai()->sync($pegawaiNips);
        });

        return redirect()->route('p2m.upacara.index')
            ->with('success', 'update') // Ubah wording session di JS index jika perlu
            ->with('message', 'Data berhasil diperbarui');
    }

    public function destroy($id)
    {
        $data = P2mUpacara::findOrFail($id);

        $data->delete();

        return redirect()->back()
            ->with('success', 'destroy')
            ->with('message', 'Data berhasil dihapus');
    }
}
