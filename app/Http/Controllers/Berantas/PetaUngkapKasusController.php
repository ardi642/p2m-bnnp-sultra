<?php

namespace App\Http\Controllers\Berantas;

use App\Http\Controllers\Controller;
use App\Models\BerantasUngkapKasus;
use App\Models\BerantasNarkotika;
use App\Models\SatuanKerja;
use App\Models\BerantasUngkapTersangka;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PetaUngkapKasusController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $satuanKerjas = $user->hasRole('admin') ? SatuanKerja::orderBy('satuan_kerja')->get() : [];
        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika', 'asc')->get();
        
        // List Pekerjaan Unik
        $listPekerjaan = BerantasUngkapTersangka::select('pekerjaan')
            ->whereNotNull('pekerjaan')->distinct()->orderBy('pekerjaan')->pluck('pekerjaan');

        $years = BerantasUngkapKasus::selectRaw('YEAR(tanggal_kejadian) as year')
            ->distinct()->orderByDesc('year')->pluck('year');

        // Logic Default Tahun
        $isFreshLoad = empty($request->all());
        $selectedTahun = $isFreshLoad ? [date('Y')] : $request->input('tahun', []);

        return view('berantas.peta-ungkap-kasus.index', compact(
            'satuanKerjas', 'masterNarkotika', 'listPekerjaan', 'years', 'selectedTahun'
        ));
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $query = BerantasUngkapKasus::with(['barangBukti.narkotika', 'tersangka']);

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
            $query->whereIn(DB::raw('MONTH(tanggal_kejadian)'), (array)$request->bulan);
        }

        // 3. Filter Tahun
        $isFreshLoad = empty($request->all());
        if ($isFreshLoad) {
            $query->whereIn(DB::raw('YEAR(tanggal_kejadian)'), [date('Y')]);
        } else {
            $activeYears = $request->input('tahun', []);
            if (!empty($activeYears)) {
                $query->whereIn(DB::raw('YEAR(tanggal_kejadian)'), $activeYears);
            }
        }

        // 4. Filter Narkotika (AND/OR Logic)
        if ($request->filled('narkotika_ids')) {
            $ids = (array)$request->narkotika_ids;
            $logic = $request->input('narkotika_logic', 'OR');

            if ($logic === 'AND') {
                foreach ($ids as $id) {
                    $query->whereHas('barangBukti', function($q) use ($id) {
                        $q->where('kategori', 'Narkotika')->where('narkotika_id', $id);
                    });
                }
            } else {
                $query->whereHas('barangBukti', function($q) use ($ids) {
                    $q->where('kategori', 'Narkotika')->whereIn('narkotika_id', $ids);
                });
            }
        }

        // 5. Filter Pekerjaan (AND/OR Logic)
        if ($request->filled('pekerjaan')) {
            $jobs = (array)$request->pekerjaan;
            $logic = $request->input('pekerjaan_logic', 'OR');

            if ($logic === 'AND') {
                foreach ($jobs as $job) {
                    $query->whereHas('tersangka', function($q) use ($job) {
                        $q->where('pekerjaan', $job);
                    });
                }
            } else {
                $query->whereHas('tersangka', function($q) use ($jobs) {
                    $q->whereIn('pekerjaan', $jobs);
                });
            }
        }

        $kasusCollection = $query->get();

        // 6. Formatting GeoJSON
        $features = $kasusCollection->map(function($item) {
            $totalBeratGram = 0;
            $totalItemNarko = 0; // Tambahan: Hitung Item
            $rawNarkoba = []; 
            
            // Proses BB (HANYA NARKOTIKA)
            foreach($item->barangBukti as $bb) {
                if($bb->kategori === 'Narkotika') {
                    $totalItemNarko++; // Increment Item Count
                    
                    $qty = $bb->kuantitas;
                    if($bb->satuan_narkotika === 'Kg') $qty *= 1000;
                    if($bb->satuan_narkotika === 'Ton') $qty *= 1000000;
                    $totalBeratGram += $qty;
                    
                    $nama = $bb->narkotika->nama_narkotika ?? 'Lainnya';
                    if(!isset($rawNarkoba[$nama])) $rawNarkoba[$nama] = 0;
                    $rawNarkoba[$nama] += $qty; 
                }
            }

            // Proses Tersangka
            $rawPekerjaan = [];
            foreach($item->tersangka as $t) {
                $rawPekerjaan[] = $t->pekerjaan ?? 'Tidak Diketahui';
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

            $countPekerjaan = array_count_values($rawPekerjaan);
            arsort($countPekerjaan);
            $htmlTersangka = '<ul class="mb-0 ps-3 text-start small list-unstyled border-top pt-2">';
            if(!empty($countPekerjaan)) {
                $htmlTersangka .= "<li class='fw-bold text-secondary mb-1' style='font-size:0.7rem;'>TERSANGKA (" . count($rawPekerjaan) . ")</li>";
                foreach($countPekerjaan as $k => $v) {
                    $htmlTersangka .= "<li>• {$k}: <strong>{$v}</strong></li>";
                }
            } else {
                $htmlTersangka .= "<li class='text-muted fst-italic'>- Tidak ada tsk -</li>";
            }
            $htmlTersangka .= '</ul>';

            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float)$item->longitude, (float)$item->latitude]
                ],
                'properties' => [
                    'id' => $item->id,
                    'lkn' => $item->nomor_lkn,
                    'tkp' => $item->alamat_tkp,
                    'tanggal' => $item->tanggal_kejadian->format('d/m/Y'),
                    'bulan_angka' => (int)$item->tanggal_kejadian->format('m'),
                    
                    // DATA UTAMA
                    'berat_gram' => $totalBeratGram,
                    'jml_item_narko' => $totalItemNarko, // Dikirim ke JS
                    
                    'popup_html' => $htmlBarang . $htmlTersangka,
                    'raw_narkoba' => $rawNarkoba,
                    'raw_pekerjaan' => $rawPekerjaan
                ]
            ];
        })->filter(function($f) {
            return !empty($f['geometry']['coordinates'][0]) && !empty($f['geometry']['coordinates'][1]);
        })->values();

        $ids = $kasusCollection->pluck('id');
        $stats = [
            'total_kasus' => $kasusCollection->count(),
            'total_tersangka' => BerantasUngkapTersangka::whereIn('berantas_ungkap_kasus_id', $ids)->count(),
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
        $item = BerantasUngkapKasus::with([
            'satuanKerja', 'tersangka', 'barangBukti.tersangka', 'barangBukti.narkotika', 'dokumen'
        ])->findOrFail($id);
        
        $evidenceGroups = $item->barangBukti->groupBy(function($bb) { 
            return $bb->tersangka->pluck('id')->sort()->values()->implode('-'); 
        });
        
        $suspectsWithEvidenceIds = $item->barangBukti->flatMap->tersangka->pluck('id')->unique()->toArray();
        $orphanSuspects = $item->tersangka->whereNotIn('id', $suspectsWithEvidenceIds);
        $showLabel = ($evidenceGroups->count() > 1);
        $formatAngka = function($nilai) { return str_replace('.', ',', (string)(float)$nilai); };

        return view('berantas.peta-ungkap-kasus.show', compact('item', 'evidenceGroups', 'orphanSuspects', 'showLabel', 'formatAngka'));
    }
}