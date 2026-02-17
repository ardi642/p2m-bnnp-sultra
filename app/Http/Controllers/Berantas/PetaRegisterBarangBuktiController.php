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
        
        // Ambil tahun dari tanggal_perolehan
        $years = BerantasRegisterBarangBukti::selectRaw('YEAR(tanggal_perolehan) as year')
            ->distinct()->orderByDesc('year')->pluck('year');

        // Logic Default: Tahun Sekarang jika Fresh Load
        $isFreshLoad = empty($request->all());
        $selectedTahun = $isFreshLoad ? [date('Y')] : $request->input('tahun', []);

        return view('berantas.peta-sebaran-bb.index', compact(
            'satuanKerjas', 'masterNarkotika', 'years', 'selectedTahun'
        ));
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        
        // Eager Load Items & Narkotika
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

        // 3. Filter Tahun (Default Current Year)
        $isFreshLoad = empty($request->all());
        if ($isFreshLoad) {
            $query->whereIn(DB::raw('YEAR(tanggal_perolehan)'), [date('Y')]);
        } else {
            $activeYears = $request->input('tahun', []);
            if (!empty($activeYears)) {
                $query->whereIn(DB::raw('YEAR(tanggal_perolehan)'), $activeYears);
            }
        }

        // 4. Filter Narkotika (Multiple dengan Logic AND/OR)
        if ($request->filled('narkotika_ids')) {
            $ids = (array)$request->narkotika_ids;
            $logic = $request->input('narkotika_logic', 'OR');

            if ($logic === 'AND') {
                // Harus mengandung SEMUA jenis narkotika yang dipilih dalam satu register
                foreach ($ids as $id) {
                    $query->whereHas('items', function($q) use ($id) {
                        $q->where('kategori', 'Narkotika')->where('narkotika_id', $id);
                    });
                }
            } else {
                // Mengandung SALAH SATU jenis
                $query->whereHas('items', function($q) use ($ids) {
                    $q->where('kategori', 'Narkotika')->whereIn('narkotika_id', $ids);
                });
            }
        }

        // 5. Filter Sumber Perolehan (Checkbox)
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
            $rawNarkoba = [];
            
            // Flags untuk Warna Marker
            $hasTangkap = false;
            $hasTemuan = false;
            
            foreach($reg->items as $item) {
                // Cek Sumber
                if ($item->sumber_perolehan === 'Hasil Tangkap') $hasTangkap = true;
                if ($item->sumber_perolehan === 'Temuan') $hasTemuan = true;

                // Hitung Berat Narkotika
                if($item->kategori === 'Narkotika') {
                    $qty = $item->kuantitas;
                    if($item->satuan_narkotika === 'Kg') $qty *= 1000;
                    if($item->satuan_narkotika === 'Ton') $qty *= 1000000;
                    $totalBeratGram += $qty;
                    
                    $nama = $item->narkotika->nama_narkotika ?? 'Lainnya';
                    if(!isset($rawNarkoba[$nama])) $rawNarkoba[$nama] = 0;
                    $rawNarkoba[$nama] += $qty; 
                }
            }

            // LOGIKA WARNA (Opsi 1)
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

            // HTML Popup Marker
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
                    'berat_gram' => $totalBeratGram,
                    'marker_color' => $markerColor, // Warna dikirim ke JS
                    'popup_html' => $htmlBarang . $htmlInfo,
                    
                    // Raw Data untuk Kalkulasi Dashboard Wilayah di JS
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