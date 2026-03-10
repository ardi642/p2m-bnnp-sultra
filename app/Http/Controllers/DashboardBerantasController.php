<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\SatuanKerja;

class DashboardBerantasController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $currentYear = (int) date('Y');
        $minYear = $currentYear;

        $tables = [
            'berantas_ungkap_kasus' => 'tanggal_kejadian',
            'berantas_tat' => 'tanggal_pelaksanaan',
            'berantas_register_barang_bukti' => 'tanggal_perolehan',
        ];

        foreach ($tables as $table => $column) {
            $oldest = DB::table($table)->min($column);
            if ($oldest) {
                $year = (int) date('Y', strtotime($oldest));
                if ($year > 2000 && $year < $minYear) {
                    $minYear = $year;
                }
            }
        }
        $years = range($currentYear, $minYear);

        $narkotikas = DB::table('berantas_narkotika')->orderBy('nama_narkotika', 'asc')->get();

        $showTabs = in_array($user->role, ['admin', 'admin_satker', 'operator_satker']);
        $satkers = ($user->role === 'admin') ? SatuanKerja::orderBy('satuan_kerja', 'asc')->get() : [];

        return view('dashboard.berantas.index', compact('years', 'showTabs', 'satkers', 'narkotikas'));
    }

    private function getRawGram() {
        return "(CASE 
                    WHEN satuan_narkotika = 'Kg' THEN kuantitas * 1000 
                    WHEN satuan_narkotika = 'Ton' THEN kuantitas * 1000000 
                    ELSE kuantitas 
                END)";
    }

    private function parseFilter(Request $request) {
        $user = Auth::user();
        $isAdmin = ($user->role === 'admin');
        $selectedSatker = $request->input('satker_id');
        
        return [
            'year' => $request->input('year', date('Y')),
            'month' => $request->input('month', 'all'),
            'narkotika_id' => $request->input('narkotika_id', ''),
            'mySatker' => $isAdmin ? $selectedSatker : $user->pegawai?->satuan_kerja_id,
            'isMulti' => ($isAdmin && empty($selectedSatker))
        ];
    }

    private function applyBaseFilters($q, $dateCol, $f) {
        $q->whereYear($dateCol, $f['year']);
        if ($f['month'] !== 'all' && $f['month'] !== 'per_bulan') {
            $q->whereMonth($dateCol, $f['month']);
        }
        if ($f['mySatker']) {
            $q->where('satuan_kerja_id', $f['mySatker']);
        }
        return $q;
    }

    private function formatTrendSeries($data, $isMulti, $isPerBulan, $satkerMap) {
        $series = [];
        if ($isPerBulan && $isMulti) {
            foreach ($satkerMap as $sId => $sName) {
                $arr = array_fill(0, 12, 0);
                foreach ($data as $row) {
                    if ($row->satuan_kerja_id == $sId) $arr[$row->bulan - 1] = (float) $row->total;
                }
                $series[] = ['name' => $sName, 'data' => $arr];
            }
        } elseif ($isPerBulan && !$isMulti) {
            $arr = array_fill(0, 12, 0);
            foreach ($data as $row) {
                $arr[$row->bulan - 1] = (float) $row->total;
            }
            $series[] = ['name' => 'Total', 'data' => $arr];
        } elseif (!$isPerBulan && $isMulti) {
            $arr = [];
            foreach ($satkerMap as $sId => $sName) {
                $val = 0;
                foreach ($data as $row) {
                    if ($row->satuan_kerja_id == $sId) $val = (float) $row->total;
                }
                $arr[] = $val;
            }
            $series[] = ['name' => 'Total', 'data' => $arr];
        } else {
            $val = 0;
            foreach ($data as $row) { $val = (float) $row->total; }
            $series[] = ['name' => 'Total', 'data' => [$val]];
        }
        return $series;
    }

    // PERBAIKAN: Fungsi ini dirombak agar menjamin kembalian Panel sebagai Array Sejati
    private function formatCompSeries($data, $isMulti, $satkerMap) {
        $catTotals = [];
        foreach($data as $row) {
            $c = $row->cat ?: 'Tidak Diketahui';
            if (!isset($catTotals[$c])) $catTotals[$c] = 0;
            $catTotals[$c] += $row->total;
        }
        arsort($catTotals);
        $categories = array_keys($catTotals);

        $series = [];
        
        if ($isMulti) {
            $panelData = [];
            foreach($satkerMap as $sId => $sName) { 
                $panelData[$sId] = ['satker' => $sName, 'items' => []]; 
            }
            
            foreach ($categories as $cat) {
                $arr = [];
                foreach ($satkerMap as $sId => $sName) {
                    $val = 0;
                    foreach ($data as $row) {
                        $rCat = $row->cat ?: 'Tidak Diketahui';
                        if ($row->satuan_kerja_id == $sId && $rCat === $cat) {
                            $val = (int) $row->total;
                        }
                    }
                    $arr[] = $val;
                    if ($val > 0) {
                        $panelData[$sId]['items'][] = ['name' => $cat, 'count' => $val];
                    }
                }
                $series[] = ['name' => $cat, 'data' => $arr];
            }
            
            // Urutkan item dari terbesar ke terkecil per Satker
            foreach($panelData as $sId => &$pData) {
                usort($pData['items'], function($a, $b) { return $b['count'] <=> $a['count']; });
            }
            
            // Wajib menggunakan array_values agar frontend menerima Format [0,1,2] bukan Object
            return ['labels' => array_values($satkerMap), 'series' => $series, 'panel' => array_values($panelData)];
        } else {
            $arr = [];
            foreach ($categories as $cat) {
                $arr[] = $catTotals[$cat];
            }
            return ['labels' => $categories, 'series' => [['name' => 'Total', 'data' => $arr]], 'panel' => []];
        }
    }


    public function getGlobalData(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $user = Auth::user();
        $satkerId = ($user->role === 'admin') ? $request->input('satker_id') : $user->pegawai?->satuan_kerja_id;

        $applyBase = function($q, $dateCol) use ($year, $satkerId) {
            $q->whereYear($dateCol, $year);
            if ($satkerId) $q->where('satuan_kerja_id', $satkerId);
            return $q;
        };

        $totalLkn = $applyBase(DB::table('berantas_ungkap_kasus'), 'tanggal_kejadian')->count();
        $totalTskLkn = $applyBase(DB::table('berantas_ungkap_tersangka')->join('berantas_ungkap_kasus', 'berantas_ungkap_kasus_id', '=', 'berantas_ungkap_kasus.id'), 'tanggal_kejadian')->count();
        $bbLkn = $applyBase(DB::table('berantas_ungkap_barang_bukti')->join('berantas_ungkap_kasus', 'berantas_ungkap_kasus_id', '=', 'berantas_ungkap_kasus.id')->where('kategori', 'Narkotika'), 'tanggal_kejadian')
            ->select(DB::raw('COUNT(berantas_ungkap_barang_bukti.id) as item'), DB::raw('SUM(' . $this->getRawGram() . ') as gram'))->first();

        $totalTat = $applyBase(DB::table('berantas_tat'), 'tanggal_pelaksanaan')->count();
        $totalTskTat = $applyBase(DB::table('berantas_tat_tersangka')->join('berantas_tat', 'berantas_tat_id', '=', 'berantas_tat.id'), 'tanggal_pelaksanaan')->count();
        $bbTat = $applyBase(DB::table('berantas_tat_barang_bukti')->join('berantas_tat', 'berantas_tat_id', '=', 'berantas_tat.id')->where('kategori', 'Narkotika'), 'tanggal_pelaksanaan')
            ->select(DB::raw('COUNT(berantas_tat_barang_bukti.id) as item'), DB::raw('SUM(' . $this->getRawGram() . ') as gram'))->first();

        $bbReg = $applyBase(DB::table('berantas_register_barang_bukti_items')->join('berantas_register_barang_bukti', 'register_barang_bukti_id', '=', 'berantas_register_barang_bukti.id')->where('kategori', 'Narkotika'), 'tanggal_perolehan')
            ->select(DB::raw('COUNT(berantas_register_barang_bukti_items.id) as item'), DB::raw('SUM(' . $this->getRawGram() . ') as gram'), 'sumber_perolehan')
            ->groupBy('sumber_perolehan')->get();

        $regTotalGram = 0; $regTotalItem = 0; $regTangkapGram = 0; $regTangkapItem = 0; $regTemuanGram = 0; $regTemuanItem = 0;
        foreach ($bbReg as $b) {
            $regTotalGram += $b->gram; $regTotalItem += $b->item;
            if ($b->sumber_perolehan === 'Hasil Tangkap') { $regTangkapGram += $b->gram; $regTangkapItem += $b->item; }
            if ($b->sumber_perolehan === 'Temuan') { $regTemuanGram += $b->gram; $regTemuanItem += $b->item; }
        }

        return response()->json([
            'lkn' => ['kasus' => $totalLkn, 'tersangka' => $totalTskLkn, 'gram' => (float)$bbLkn->gram, 'item' => (int)$bbLkn->item],
            'tat' => ['kasus' => $totalTat, 'tersangka' => $totalTskTat, 'gram' => (float)$bbTat->gram, 'item' => (int)$bbTat->item],
            'reg' => [
                'total_gram' => $regTotalGram, 'total_item' => $regTotalItem,
                'tangkap_gram' => $regTangkapGram, 'tangkap_item' => $regTangkapItem,
                'temuan_gram' => $regTemuanGram, 'temuan_item' => $regTemuanItem
            ]
        ]);
    }

    public function getChartLkn(Request $request) {
        $f = $this->parseFilter($request);
        $isPerBulan = ($f['month'] === 'per_bulan');
        $satkerMap = $f['isMulti'] ? SatuanKerja::orderBy('satuan_kerja', 'asc')->pluck('satuan_kerja', 'id')->toArray() : [$f['mySatker'] => 'Satuan Kerja'];
        $trendLabels = $isPerBulan ? ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'] : ($f['isMulti'] ? array_values($satkerMap) : ['Total Akumulasi']);

        $qK = $this->applyBaseFilters(DB::table('berantas_ungkap_kasus'), 'tanggal_kejadian', $f);
        if ($f['narkotika_id']) { $qK->whereExists(function($q) use ($f) { $q->select(DB::raw(1))->from('berantas_ungkap_barang_bukti')->whereColumn('berantas_ungkap_kasus_id', 'berantas_ungkap_kasus.id')->where('narkotika_id', $f['narkotika_id']); }); }
        $qK->select('satuan_kerja_id', DB::raw('COUNT(id) as total'));
        if ($isPerBulan) { $qK->addSelect(DB::raw('MONTH(tanggal_kejadian) as bulan'))->groupBy('satuan_kerja_id', DB::raw('MONTH(tanggal_kejadian)')); } else { $qK->groupBy('satuan_kerja_id'); }
        $dataKasus = $this->formatTrendSeries($qK->get(), $f['isMulti'], $isPerBulan, $satkerMap);

        $qT = $this->applyBaseFilters(DB::table('berantas_ungkap_tersangka')->join('berantas_ungkap_kasus', 'berantas_ungkap_kasus_id', '=', 'berantas_ungkap_kasus.id'), 'tanggal_kejadian', $f);
        if ($f['narkotika_id']) { $qT->whereExists(function($q) use ($f) { $q->select(DB::raw(1))->from('berantas_ungkap_barang_bukti')->whereColumn('berantas_ungkap_kasus_id', 'berantas_ungkap_kasus.id')->where('narkotika_id', $f['narkotika_id']); }); }
        $qT->select('satuan_kerja_id', DB::raw('COUNT(berantas_ungkap_tersangka.id) as total'));
        if ($isPerBulan) { $qT->addSelect(DB::raw('MONTH(tanggal_kejadian) as bulan'))->groupBy('satuan_kerja_id', DB::raw('MONTH(tanggal_kejadian)')); } else { $qT->groupBy('satuan_kerja_id'); }
        $dataTsk = $this->formatTrendSeries($qT->get(), $f['isMulti'], $isPerBulan, $satkerMap);

        $qB = $this->applyBaseFilters(DB::table('berantas_ungkap_barang_bukti')->join('berantas_ungkap_kasus', 'berantas_ungkap_kasus_id', '=', 'berantas_ungkap_kasus.id')->where('kategori', 'Narkotika'), 'tanggal_kejadian', $f);
        if ($f['narkotika_id']) $qB->where('narkotika_id', $f['narkotika_id']);
        $qB->select('satuan_kerja_id', DB::raw('SUM(' . $this->getRawGram() . ') as total'));
        if ($isPerBulan) { $qB->addSelect(DB::raw('MONTH(tanggal_kejadian) as bulan'))->groupBy('satuan_kerja_id', DB::raw('MONTH(tanggal_kejadian)')); } else { $qB->groupBy('satuan_kerja_id'); }
        $dataBerat = $this->formatTrendSeries($qB->get(), $f['isMulti'], $isPerBulan, $satkerMap);

        $qComp = $this->applyBaseFilters(DB::table('berantas_ungkap_tersangka')->join('berantas_ungkap_kasus', 'berantas_ungkap_kasus_id', '=', 'berantas_ungkap_kasus.id'), 'tanggal_kejadian', $f);
        if ($f['narkotika_id']) { $qComp->whereExists(function($q) use ($f) { $q->select(DB::raw(1))->from('berantas_ungkap_barang_bukti')->whereColumn('berantas_ungkap_kasus_id', 'berantas_ungkap_kasus.id')->where('narkotika_id', $f['narkotika_id']); }); }
        
        $dataGender = $this->formatCompSeries((clone $qComp)->select('satuan_kerja_id', 'jenis_kelamin as cat', DB::raw('COUNT(*) as total'))->groupBy('satuan_kerja_id', 'jenis_kelamin')->get(), $f['isMulti'], $satkerMap);
        $dataPekerjaan = $this->formatCompSeries((clone $qComp)->select('satuan_kerja_id', 'pekerjaan as cat', DB::raw('COUNT(*) as total'))->groupBy('satuan_kerja_id', 'pekerjaan')->get(), $f['isMulti'], $satkerMap);

        foreach ($dataGender['series'] as &$s) { if(strtolower($s['name']) === 'laki-laki') $s['name'] = 'Laki-laki'; if(strtolower($s['name']) === 'perempuan') $s['name'] = 'Perempuan'; }

        return response()->json([
            'is_multi' => $f['isMulti'], 'trend_labels' => $trendLabels,
            'trend' => ['kasus' => $dataKasus, 'tersangka' => $dataTsk, 'berat' => $dataBerat],
            'comp' => [
                'gender' => ['labels' => $dataGender['labels'], 'series' => $dataGender['series']],
                'pekerjaan' => $dataPekerjaan
            ]
        ]);
    }

    public function getChartTat(Request $request) {
        $f = $this->parseFilter($request);
        $isPerBulan = ($f['month'] === 'per_bulan');
        $satkerMap = $f['isMulti'] ? SatuanKerja::orderBy('satuan_kerja', 'asc')->pluck('satuan_kerja', 'id')->toArray() : [$f['mySatker'] => 'Satuan Kerja'];
        $trendLabels = $isPerBulan ? ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'] : ($f['isMulti'] ? array_values($satkerMap) : ['Total Akumulasi']);

        $qK = $this->applyBaseFilters(DB::table('berantas_tat'), 'tanggal_pelaksanaan', $f);
        if ($f['narkotika_id']) { $qK->whereExists(function($q) use ($f) { $q->select(DB::raw(1))->from('berantas_tat_barang_bukti')->whereColumn('berantas_tat_id', 'berantas_tat.id')->where('narkotika_id', $f['narkotika_id']); }); }
        $qK->select('satuan_kerja_id', DB::raw('COUNT(id) as total'));
        if ($isPerBulan) { $qK->addSelect(DB::raw('MONTH(tanggal_pelaksanaan) as bulan'))->groupBy('satuan_kerja_id', DB::raw('MONTH(tanggal_pelaksanaan)')); } else { $qK->groupBy('satuan_kerja_id'); }
        $dataKasus = $this->formatTrendSeries($qK->get(), $f['isMulti'], $isPerBulan, $satkerMap);

        $qT = $this->applyBaseFilters(DB::table('berantas_tat_tersangka')->join('berantas_tat', 'berantas_tat_id', '=', 'berantas_tat.id'), 'tanggal_pelaksanaan', $f);
        if ($f['narkotika_id']) { $qT->whereExists(function($q) use ($f) { $q->select(DB::raw(1))->from('berantas_tat_barang_bukti')->whereColumn('berantas_tat_id', 'berantas_tat.id')->where('narkotika_id', $f['narkotika_id']); }); }
        $qT->select('satuan_kerja_id', DB::raw('COUNT(berantas_tat_tersangka.id) as total'));
        if ($isPerBulan) { $qT->addSelect(DB::raw('MONTH(tanggal_pelaksanaan) as bulan'))->groupBy('satuan_kerja_id', DB::raw('MONTH(tanggal_pelaksanaan)')); } else { $qT->groupBy('satuan_kerja_id'); }
        $dataTsk = $this->formatTrendSeries($qT->get(), $f['isMulti'], $isPerBulan, $satkerMap);

        $dataRekom = $this->formatCompSeries((clone $qK)->select('satuan_kerja_id', 'tindak_lanjut_rekomendasi as cat', DB::raw('COUNT(*) as total'))->groupBy('satuan_kerja_id', 'tindak_lanjut_rekomendasi')->get(), $f['isMulti'], $satkerMap);
        $dataGender = $this->formatCompSeries((clone $qT)->select('satuan_kerja_id', 'jenis_kelamin as cat', DB::raw('COUNT(*) as total'))->groupBy('satuan_kerja_id', 'jenis_kelamin')->get(), $f['isMulti'], $satkerMap);
        $dataDidik = $this->formatCompSeries((clone $qT)->select('satuan_kerja_id', 'pendidikan as cat', DB::raw('COUNT(*) as total'))->groupBy('satuan_kerja_id', 'pendidikan')->get(), $f['isMulti'], $satkerMap);
        $dataPekerjaan = $this->formatCompSeries((clone $qT)->select('satuan_kerja_id', 'pekerjaan as cat', DB::raw('COUNT(*) as total'))->groupBy('satuan_kerja_id', 'pekerjaan')->get(), $f['isMulti'], $satkerMap);

        foreach ($dataRekom['series'] as &$s) { $s['name'] = ucwords($s['name']); }
        foreach ($dataGender['series'] as &$s) { if(strtolower($s['name']) === 'laki-laki') $s['name'] = 'Laki-laki'; if(strtolower($s['name']) === 'perempuan') $s['name'] = 'Perempuan'; }

        return response()->json([
            'is_multi' => $f['isMulti'], 'trend_labels' => $trendLabels,
            'trend' => ['kasus' => $dataKasus, 'tersangka' => $dataTsk],
            'comp' => [
                'rekom' => ['labels' => $dataRekom['labels'], 'series' => $dataRekom['series']],
                'gender' => ['labels' => $dataGender['labels'], 'series' => $dataGender['series']],
                'pendidikan' => $dataDidik,
                'pekerjaan' => $dataPekerjaan
            ]
        ]);
    }

    public function getChartBb(Request $request) {
        $f = $this->parseFilter($request);
        $isPerBulan = ($f['month'] === 'per_bulan');
        $satkerMap = $f['isMulti'] ? SatuanKerja::orderBy('satuan_kerja', 'asc')->pluck('satuan_kerja', 'id')->toArray() : [$f['mySatker'] => 'Satuan Kerja'];
        $trendLabels = $isPerBulan ? ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'] : ($f['isMulti'] ? array_values($satkerMap) : ['Total Akumulasi']);

        $qB = $this->applyBaseFilters(DB::table('berantas_register_barang_bukti_items')->join('berantas_register_barang_bukti', 'register_barang_bukti_id', '=', 'berantas_register_barang_bukti.id')->where('kategori', 'Narkotika'), 'tanggal_perolehan', $f);
        if ($f['narkotika_id']) $qB->where('narkotika_id', $f['narkotika_id']);

        $qBerat = (clone $qB)->select('satuan_kerja_id', DB::raw('SUM(' . $this->getRawGram() . ') as total'));
        if ($isPerBulan) { $qBerat->addSelect(DB::raw('MONTH(tanggal_perolehan) as bulan'))->groupBy('satuan_kerja_id', DB::raw('MONTH(tanggal_perolehan)')); } else { $qBerat->groupBy('satuan_kerja_id'); }
        $dataBerat = $this->formatTrendSeries($qBerat->get(), $f['isMulti'], $isPerBulan, $satkerMap);

        $qItem = (clone $qB)->select('satuan_kerja_id', DB::raw('COUNT(berantas_register_barang_bukti_items.id) as total'));
        if ($isPerBulan) { $qItem->addSelect(DB::raw('MONTH(tanggal_perolehan) as bulan'))->groupBy('satuan_kerja_id', DB::raw('MONTH(tanggal_perolehan)')); } else { $qItem->groupBy('satuan_kerja_id'); }
        $dataItem = $this->formatTrendSeries($qItem->get(), $f['isMulti'], $isPerBulan, $satkerMap);

        $dataSumber = $this->formatCompSeries((clone $qB)->select('satuan_kerja_id', 'sumber_perolehan as cat', DB::raw('COUNT(*) as total'))->groupBy('satuan_kerja_id', 'sumber_perolehan')->get(), $f['isMulti'], $satkerMap);

        return response()->json([
            'is_multi' => $f['isMulti'], 'trend_labels' => $trendLabels,
            'trend' => ['berat' => $dataBerat, 'item' => $dataItem],
            'comp' => [
                'sumber' => ['labels' => $dataSumber['labels'], 'series' => $dataSumber['series']]
            ]
        ]);
    }

    public function getRankingNarkotika(Request $request) {
        $f = $this->parseFilter($request);
        $source = $request->input('source', 'lkn');
        $metric = $request->input('metric', 'berat');
        $limit  = $request->input('limit', 'all');

        if ($source === 'tat') {
            $q = DB::table('berantas_tat_barang_bukti')->join('berantas_tat', 'berantas_tat_id', '=', 'berantas_tat.id')->join('berantas_narkotika', 'narkotika_id', '=', 'berantas_narkotika.id')->where('berantas_tat_barang_bukti.kategori', 'Narkotika');
            $q = $this->applyBaseFilters($q, 'tanggal_pelaksanaan', $f);
        } else if ($source === 'bb') {
            $q = DB::table('berantas_register_barang_bukti_items')->join('berantas_register_barang_bukti', 'register_barang_bukti_id', '=', 'berantas_register_barang_bukti.id')->join('berantas_narkotika', 'narkotika_id', '=', 'berantas_narkotika.id')->where('berantas_register_barang_bukti_items.kategori', 'Narkotika');
            $q = $this->applyBaseFilters($q, 'tanggal_perolehan', $f);
        } else {
            $q = DB::table('berantas_ungkap_barang_bukti')->join('berantas_ungkap_kasus', 'berantas_ungkap_kasus_id', '=', 'berantas_ungkap_kasus.id')->join('berantas_narkotika', 'narkotika_id', '=', 'berantas_narkotika.id')->where('berantas_ungkap_barang_bukti.kategori', 'Narkotika');
            $q = $this->applyBaseFilters($q, 'tanggal_kejadian', $f);
        }

        $q->select('berantas_narkotika.nama_narkotika as name', DB::raw('COUNT(*) as freq'), DB::raw('SUM(' . $this->getRawGram() . ') as berat'))
          ->groupBy('berantas_narkotika.id', 'berantas_narkotika.nama_narkotika')
          ->orderBy($metric, 'desc');

        if ($limit !== 'all') { $q->limit((int)$limit); }

        $results = $q->get();
        $labels = []; $data = [];
        foreach ($results as $r) {
            $labels[] = $r->name;
            $data[] = ($metric === 'berat') ? (float)$r->berat : (int)$r->freq;
        }

        return response()->json([
            'labels' => $labels,
            'data' => $data,
            'metric' => $metric
        ]);
    }
}