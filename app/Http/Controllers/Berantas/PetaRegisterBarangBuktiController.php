<?php

namespace App\Http\Controllers\Berantas;

use App\Http\Controllers\Controller;
use App\Models\BerantasRegisterBarangBukti; 
use App\Models\BerantasNarkotika;
use App\Models\SatuanKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PetaRegisterBarangBuktiController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $satuanKerjas = $user->hasRole('admin') ? SatuanKerja::orderBy('satuan_kerja')->get() : [];
        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika', 'asc')->get();
        
        $years = BerantasRegisterBarangBukti::selectRaw('YEAR(tanggal_perolehan) as year')
            ->distinct()->orderByDesc('year')->pluck('year');

        $isFreshLoad = empty($request->all());
        $selectedTahun = $isFreshLoad ? [date('Y')] : $request->input('tahun', []);

        return view('berantas.peta-sebaran-bb.index', compact(
            'satuanKerjas', 'masterNarkotika', 'years', 'selectedTahun'
        ));
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $query = BerantasRegisterBarangBukti::with(['items.narkotika']);

        // 1. Filter Satker
        if ($user->hasRole('admin')) {
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('satuan_kerja_id', (array)$request->satuan_kerja_id);
            }
        } else {
            $query->where('satuan_kerja_id', $user->getSatkerId());
        }

        // 2. Filter Bulan
        if ($request->filled('bulan')) {
            $query->whereIn(DB::raw('MONTH(tanggal_perolehan)'), (array)$request->bulan);
        }

        // 3. Filter Tahun
        $isFreshLoad = empty($request->all());
        if ($isFreshLoad) {
            $query->whereIn(DB::raw('YEAR(tanggal_perolehan)'), [date('Y')]);
        } else {
            $activeYears = $request->input('tahun', []);
            if (!empty($activeYears)) {
                $query->whereIn(DB::raw('YEAR(tanggal_perolehan)'), $activeYears);
            }
        }

        // 4. Filter Narkotika
        if ($request->filled('narkotika_ids')) {
            $ids = (array)$request->narkotika_ids;
            $logic = $request->input('narkotika_logic', 'OR');

            if ($logic === 'AND') {
                foreach ($ids as $id) {
                    $query->whereHas('items', function($q) use ($id) {
                        $q->where('kategori', 'Narkotika')->where('narkotika_id', $id);
                    });
                }
            } else {
                $query->whereHas('items', function($q) use ($ids) {
                    $q->where('kategori', 'Narkotika')->whereIn('narkotika_id', $ids);
                });
            }
        }

        // 5. Filter Sumber
        if ($request->filled('sumber')) {
            $sumber = (array)$request->sumber; 
            $query->whereHas('items', function($q) use ($sumber) {
                $q->whereIn('sumber_perolehan', $sumber);
            });
        }

        $collection = $query->get();

        // 6. Formatting GeoJSON
        $features = $collection->map(function($reg) {
            $totalBeratGram = 0;
            $totalItemNarko = 0;
            $beratTangkap = 0; // Berat Khusus Tangkap
            $rawNarkoba = [];
            
            $hasTangkap = false;
            $hasTemuan = false;
            
            foreach($reg->items as $item) {
                // Proses HANYA NARKOTIKA
                if($item->kategori === 'Narkotika') {
                    $totalItemNarko++;
                    
                    // Hitung Berat
                    $qty = $item->kuantitas;
                    if($item->satuan_narkotika === 'Kg') $qty *= 1000;
                    if($item->satuan_narkotika === 'Ton') $qty *= 1000000;
                    
                    $totalBeratGram += $qty;

                    // Cek Sumber untuk pewarnaan marker & statistik dashboard
                    if ($item->sumber_perolehan === 'Hasil Tangkap') {
                        $hasTangkap = true;
                        $beratTangkap += $qty; // Tambah ke berat tangkap
                    }
                    if ($item->sumber_perolehan === 'Temuan') {
                        $hasTemuan = true;
                    }
                    
                    $nama = $item->narkotika->nama_narkotika ?? 'Lainnya';
                    if(!isset($rawNarkoba[$nama])) $rawNarkoba[$nama] = 0;
                    $rawNarkoba[$nama] += $qty; 
                }
            }

            // LOGIKA WARNA MARKER
            $markerColor = '#dc3545'; // Default Merah (Tangkap)
            $statusLabel = 'Hasil Tangkap';
            $statusCode = 'tangkap'; 
            
            if ($hasTangkap && $hasTemuan) {
                $markerColor = '#6f42c1'; // Ungu (Campuran)
                $statusLabel = 'Campuran';
                $statusCode = 'campuran';
            } elseif ($hasTemuan && !$hasTangkap) {
                $markerColor = '#ffc107'; // Kuning (Temuan)
                $statusLabel = 'Temuan';
                $statusCode = 'temuan';
            }

            // HTML Popup
            arsort($rawNarkoba);
            $htmlBarang = '<ul class="mb-2 ps-3 text-start small list-unstyled">';
            if(!empty($rawNarkoba)) {
                foreach($rawNarkoba as $k => $v) {
                    $htmlBarang .= "<li>• <strong>{$k}</strong>: " . number_format($v, 0, ',', '.') . " g</li>";
                }
            } else {
                $htmlBarang .= "<li class='text-muted fst-italic'>- Tidak ada BB Narkotika -</li>";
            }
            $htmlBarang .= '</ul>';
            $htmlInfo = "<div class='border-top pt-1 mt-1 text-center fw-bold' style='color:{$markerColor}; font-size:0.7rem;'>{$statusLabel}</div>";

            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float)$reg->longitude, (float)$reg->latitude]
                ],
                'properties' => [
                    'id' => $reg->id,
                    'lokasi' => $reg->lokasi_perolehan ?? 'Lokasi tidak tercatat',
                    'tanggal' => $reg->tanggal_perolehan->format('d/m/Y'),
                    'bulan_angka' => (int)$reg->tanggal_perolehan->format('m'),
                    
                    // DATA UTAMA UNTUK DASHBOARD
                    'berat_gram' => $totalBeratGram,
                    'jml_item_narko' => $totalItemNarko,
                    'berat_tangkap' => $beratTangkap, // Data baru untuk dashboard
                    
                    'marker_color' => $markerColor,
                    'popup_html' => $htmlBarang . $htmlInfo,
                    'raw_narkoba' => $rawNarkoba,
                    'status_code' => $statusCode 
                ]
            ];
        })->filter(function($f) {
            return !empty($f['geometry']['coordinates'][0]) && !empty($f['geometry']['coordinates'][1]);
        })->values();

        $stats = [
            'total_register' => $collection->count(),
            'total_berat_gram' => $features->sum('properties.berat_gram'),
        ];

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
            'stats' => $stats
        ]);
    }

    public function show($id)
    {
        $register = BerantasRegisterBarangBukti::with(['satuanKerja', 'items.narkotika'])->findOrFail($id);
        $formatAngka = function($nilai) { return str_replace('.', ',', (string)(float)$nilai); };
        return view('berantas.peta-sebaran-bb.show', compact('register', 'formatAngka'));
    }
}