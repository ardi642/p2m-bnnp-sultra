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
    /**
     * Menampilkan Halaman Peta Utama
     */
    public function index()
    {
        $user = Auth::user();
        $satuanKerjas = $user->hasRole('admin') ? SatuanKerja::orderBy('satuan_kerja')->get() : [];
        $masterNarkotika = BerantasNarkotika::orderBy('nama_narkotika', 'asc')->get();
        
        // Ambil tahun yang tersedia di data
        $years = BerantasUngkapKasus::selectRaw('YEAR(tanggal_kejadian) as year')
            ->distinct()->orderByDesc('year')->pluck('year');

        return view('berantas.peta-ungkap-kasus.index', compact('satuanKerjas', 'masterNarkotika', 'years'));
    }

    /**
     * API JSON: Mengembalikan data GeoJSON & Statistik untuk Peta
     */
    public function data(Request $request)
    {
        $user = Auth::user();
        
        // 1. Base Query (Mirip dengan Index Table tapi dioptimasi)
        $query = BerantasUngkapKasus::with(['barangBukti.narkotika']);

        // --- FILTERING ---
        
        // Filter Satker
        if ($user->hasRole('admin')) {
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('satuan_kerja_id', (array)$request->satuan_kerja_id);
            }
        } else {
            $query->where('satuan_kerja_id', $user->getSatkerId());
        }

        // Filter Waktu
        if ($request->filled('bulan')) {
            $query->whereIn(DB::raw('MONTH(tanggal_kejadian)'), (array)$request->bulan);
        }
        $activeYears = $request->filled('tahun') ? (array)$request->tahun : [date('Y')];
        $query->whereIn(DB::raw('YEAR(tanggal_kejadian)'), $activeYears);

        // Filter Narkotika (Filter Parent berdasarkan Child)
        if ($request->filled('narkotika_ids')) {
            $query->whereHas('barangBukti', function($q) use ($request) {
                $q->where('kategori', 'Narkotika')
                  ->whereIn('narkotika_id', (array)$request->narkotika_ids);
            });
        }

        // Ambil Data
        $kasusCollection = $query->get();

        // 2. Format Data untuk GeoJSON
        $features = $kasusCollection->map(function($item) {
            // Hitung Total Berat (Gram) untuk Radius Marker
            $totalBeratGram = 0;
            $previewBarang = [];

            foreach($item->barangBukti as $bb) {
                if($bb->kategori === 'Narkotika') {
                    $qty = $bb->kuantitas;
                    // Konversi ke Gram
                    if($bb->satuan_narkotika === 'Kg') $qty *= 1000;
                    if($bb->satuan_narkotika === 'Ton') $qty *= 1000000;
                    
                    $totalBeratGram += $qty;
                    
                    $namaNarko = $bb->narkotika->nama_narkotika ?? 'Narkotika';
                    if(!in_array($namaNarko, $previewBarang)) $previewBarang[] = $namaNarko;
                }
            }

            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float)$item->longitude, (float)$item->latitude] // GeoJSON urutannya: [Lng, Lat]
                ],
                'properties' => [
                    'id' => $item->id,
                    'lkn' => $item->nomor_lkn,
                    'tkp' => $item->alamat_tkp,
                    'tanggal' => $item->tanggal_kejadian->format('d/m/Y'),
                    'berat_gram' => $totalBeratGram, // Value utama untuk visualisasi ukuran lingkaran
                    'info_barang' => implode(', ', array_slice($previewBarang, 0, 2)) . (count($previewBarang)>2 ? '...' : ''),
                ]
            ];
        })->filter(function($f) {
            // Pastikan koordinat valid agar tidak error di Leaflet
            return !empty($f['geometry']['coordinates'][0]) && !empty($f['geometry']['coordinates'][1]);
        })->values();

        // 3. Hitung Statistik Footer
        // Gunakan pluck ID untuk hitung relasi tersangka agar efisien
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

    /**
     * HTML Partial: Detail Modal (Dipanggil saat klik marker)
     */
    public function show($id)
    {
        $item = BerantasUngkapKasus::with([
            'satuanKerja', 
            'tersangka', 
            'barangBukti.tersangka', 
            'barangBukti.narkotika', 
            'dokumen'
        ])->findOrFail($id);
        
        // Logika Grouping untuk Tabel Detail (Sama persis dengan Index)
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