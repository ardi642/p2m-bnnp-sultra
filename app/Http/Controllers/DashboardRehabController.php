<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\SatuanKerja;

class DashboardRehabController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $currentYear = (int) date('Y');
        $minYear = $currentYear;

        // RADAR DETEKSI TAHUN OTOMATIS
        $tables = [
            'rehab_target' => 'tahun',
            'rehab_laporan' => 'tanggal',
            'rehab_riwayat' => 'tanggal_rehab',
        ];

        foreach ($tables as $table => $column) {
            if ($table === 'rehab_target') {
                $oldest = DB::table($table)->min($column);
                if ($oldest && $oldest > 2000 && $oldest < $minYear) $minYear = (int) $oldest;
            } else {
                $oldest = DB::table($table)->min($column);
                if ($oldest) {
                    $year = (int) date('Y', strtotime($oldest));
                    if ($year > 2000 && $year < $minYear) $minYear = $year;
                }
            }
        }
        $years = range($currentYear, $minYear);

        $showTabs = in_array($user->role, ['admin', 'admin_satker', 'operator_satker']);
        $satkers = ($user->role === 'admin') ? SatuanKerja::orderBy('satuan_kerja', 'asc')->get() : [];

        return view('dashboard.rehab.index', compact('years', 'showTabs', 'satkers'));
    }

    private function getRawUsiaGroup() {
        $ageCalc = "TIMESTAMPDIFF(YEAR, rehab_pasien.tanggal_lahir, rehab_riwayat.tanggal_rehab)";
        return "CASE 
                    WHEN $ageCalc < 15 THEN '< 15 tahun'
                    WHEN $ageCalc BETWEEN 15 AND 19 THEN '15-19 tahun'
                    WHEN $ageCalc BETWEEN 20 AND 34 THEN '20-34 tahun'
                    WHEN $ageCalc BETWEEN 35 AND 49 THEN '35-49 tahun'
                    WHEN $ageCalc BETWEEN 50 AND 64 THEN '50-64 tahun'
                    ELSE '65+ tahun'
                END";
    }

    private function parseFilter(Request $request) {
        $user = Auth::user();
        $isAdmin = ($user->role === 'admin');
        $selectedSatker = $request->input('satker_id');
        
        return [
            'year' => $request->input('year', date('Y')),
            'time' => $request->input('time', 'all'), 
            'mode_hitung' => $request->input('mode_hitung', 'layanan'),
            'mySatker' => $isAdmin ? $selectedSatker : $user->pegawai?->satuan_kerja_id,
            'isMulti' => ($isAdmin && empty($selectedSatker))
        ];
    }

    private function applyTimeFilter($q, $dateCol, $f, $satkerCol = 'satuan_kerja_id') {
        $q->whereYear($dateCol, $f['year']);
        
        if ($f['time'] !== 'all' && $f['time'] !== 'per_bulan' && $f['time'] !== 'per_triwulan') {
            if (strpos($f['time'], 'Q') === 0) {
                // Filter Kuartal / Triwulan Spesifik
                $quarter = (int) str_replace('Q', '', $f['time']);
                $q->whereRaw("QUARTER({$dateCol}) = ?", [$quarter]);
            } else {
                // Filter Bulan Spesifik
                $q->whereMonth($dateCol, (int) $f['time']);
            }
        }
        
        if ($f['mySatker']) {
            $q->where($satkerCol, $f['mySatker']);
        }
        return $q;
    }

    public function getGlobalData(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $user = Auth::user();
        $satkerId = ($user->role === 'admin') ? $request->input('satker_id') : $user->pegawai?->satuan_kerja_id;

        $qTarget = DB::table('rehab_target')->where('tahun', $year);
        if ($satkerId) $qTarget->where('satuan_kerja_id', $satkerId);
        $target = $qTarget->select(
            DB::raw('SUM(target_rawat_jalan) as rj'),
            DB::raw('SUM(target_pasca_rehab) as pasca'),
            DB::raw('SUM(target_skhpn) as skhpn')
        )->first();

        $qLaporan = DB::table('rehab_laporan')->whereYear('tanggal', $year);
        if ($satkerId) $qLaporan->where('satuan_kerja_id', $satkerId);
        $realisasi = $qLaporan->select(
            DB::raw('SUM(realisasi_rawat_jalan) as rj'),
            DB::raw('SUM(realisasi_pasca_rehab) as pasca'),
            DB::raw('SUM(realisasi_skhpn) as skhpn')
        )->first();

        $qRiwayat = DB::table('rehab_riwayat')
            ->join('rehab_pasien', 'rehab_pasien.id', '=', 'rehab_riwayat.rehab_pasien_id')
            ->whereYear('tanggal_rehab', $year);
        if ($satkerId) $qRiwayat->where('rehab_pasien.satuan_kerja_id', $satkerId);

        $klienTotal = $qRiwayat->count();
        $klienUnik = $qRiwayat->distinct('rehab_pasien_id')->count('rehab_pasien_id');

        return response()->json([
            'rj' => [ 'real' => (int)$realisasi->rj, 'target' => (int)$target->rj ],
            'pasca' => [ 'real' => (int)$realisasi->pasca, 'target' => (int)$target->pasca ],
            'skhpn' => [ 'real' => (int)$realisasi->skhpn, 'target' => (int)$target->skhpn ],
            'klien' => [ 'total' => $klienTotal, 'unik' => $klienUnik ]
        ]);
    }

    // TREN KUNJUNGAN VS PASIEN BARU (MIXED CHART)
    public function getChartTrendKunjungan(Request $request) {
        $f = $this->parseFilter($request);
        $year = $f['year'];
        $mode = $request->input('trend_mode', 'per_bulan'); 
        $satkerMap = $f['isMulti'] ? SatuanKerja::orderBy('satuan_kerja', 'asc')->pluck('satuan_kerja', 'id')->toArray() : [$f['mySatker'] => 'Satuan Kerja'];

        // 1. Query Total Kunjungan
        $kunjunganQ = DB::table('rehab_riwayat')
            ->join('rehab_pasien', 'rehab_pasien.id', '=', 'rehab_riwayat.rehab_pasien_id')
            ->whereYear('tanggal_rehab', $year);
        
        if ($f['mySatker']) {
            $kunjunganQ->where('rehab_pasien.satuan_kerja_id', $f['mySatker']);
        }

        $kPeriodeCol = ($mode === 'per_triwulan') ? 'QUARTER(tanggal_rehab)' : 'MONTH(tanggal_rehab)';
        $kunjunganQ->selectRaw("rehab_pasien.satuan_kerja_id, {$kPeriodeCol} as periode, COUNT(rehab_riwayat.id) as total")
                   ->groupBy('rehab_pasien.satuan_kerja_id', DB::raw($kPeriodeCol));
        
        $resKunjungan = $kunjunganQ->get();

        // 2. Query Pasien Baru
        $sub = DB::table('rehab_riwayat')
            ->select('rehab_pasien_id', DB::raw('MIN(tanggal_rehab) as min_tanggal'))
            ->groupBy('rehab_pasien_id');

        $baruQ = DB::table('rehab_pasien')
            ->joinSub($sub, 'first_rehab', function ($join) {
                $join->on('rehab_pasien.id', '=', 'first_rehab.rehab_pasien_id');
            })
            ->whereYear('first_rehab.min_tanggal', $year);

        if ($f['mySatker']) {
            $baruQ->where('rehab_pasien.satuan_kerja_id', $f['mySatker']);
        }

        $bPeriodeCol = ($mode === 'per_triwulan') ? 'QUARTER(first_rehab.min_tanggal)' : 'MONTH(first_rehab.min_tanggal)';
        $baruQ->selectRaw("rehab_pasien.satuan_kerja_id, {$bPeriodeCol} as periode, COUNT(rehab_pasien.id) as total")
              ->groupBy('rehab_pasien.satuan_kerja_id', DB::raw($bPeriodeCol));
        
        $resBaru = $baruQ->get();

        // 3. Format Output
        $len = ($mode === 'per_triwulan') ? 4 : 12;
        $labels = ($mode === 'per_triwulan') 
            ? ['Triwulan I', 'Triwulan II', 'Triwulan III', 'Triwulan IV'] 
            : ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        
        $dataKunjunganGlobal = array_fill(0, $len, 0);
        $dataBaruGlobal = array_fill(0, $len, 0);
        $panel = [];

        if ($f['isMulti']) {
            foreach ($satkerMap as $sId => $sName) {
                $arrK = array_fill(0, $len, 0);
                $arrB = array_fill(0, $len, 0);
                foreach ($resKunjungan as $row) { if ($row->satuan_kerja_id == $sId) $arrK[$row->periode - 1] = (int) $row->total; }
                foreach ($resBaru as $row) { if ($row->satuan_kerja_id == $sId) $arrB[$row->periode - 1] = (int) $row->total; }
                
                $panel[] = [
                    'satker' => $sName,
                    'kunjungan' => $arrK,
                    'baru' => $arrB
                ];
            }
        } else {
            foreach ($resKunjungan as $row) { $dataKunjunganGlobal[$row->periode - 1] = (int) $row->total; }
            foreach ($resBaru as $row) { $dataBaruGlobal[$row->periode - 1] = (int) $row->total; }
        }

        return response()->json([
            'is_multi' => $f['isMulti'],
            'labels' => $labels,
            'kunjungan' => $dataKunjunganGlobal,
            'baru' => $dataBaruGlobal,
            'panel' => $panel
        ]);
    }

    public function getChartLayanan(Request $request) {
        $f = $this->parseFilter($request);
        $isPerBulan = ($f['time'] === 'per_bulan');
        $isPerTriwulan = ($f['time'] === 'per_triwulan');
        $satkerMap = $f['isMulti'] ? SatuanKerja::orderBy('satuan_kerja', 'asc')->pluck('satuan_kerja', 'id')->toArray() : [$f['mySatker'] => 'Satuan Kerja'];
        
        $trendLabels = [];
        if ($isPerBulan) {
            $trendLabels = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        } else if ($isPerTriwulan) {
            $trendLabels = ['Triwulan I', 'Triwulan II', 'Triwulan III', 'Triwulan IV'];
        } else {
            $trendLabels = $f['isMulti'] ? array_values($satkerMap) : ['Total Akumulasi'];
        }

        $qL = $this->applyTimeFilter(DB::table('rehab_laporan'), 'tanggal', $f, 'satuan_kerja_id');
        $qL->select('satuan_kerja_id', 
            DB::raw('SUM(realisasi_rawat_jalan) as rj'), 
            DB::raw('SUM(realisasi_pasca_rehab) as pasca'), 
            DB::raw('SUM(realisasi_skhpn) as skhpn')
        );

        if ($isPerBulan) { 
            $qL->addSelect(DB::raw('MONTH(tanggal) as periode'))->groupBy('satuan_kerja_id', DB::raw('MONTH(tanggal)')); 
        } else if ($isPerTriwulan) {
            $qL->addSelect(DB::raw('QUARTER(tanggal) as periode'))->groupBy('satuan_kerja_id', DB::raw('QUARTER(tanggal)')); 
        } else { 
            $qL->groupBy('satuan_kerja_id'); 
        }
        
        $data = $qL->get();
        
        $chartRj = $this->formatLayananSeries($data, 'rj', $f['isMulti'], $isPerBulan, $isPerTriwulan, $satkerMap);
        $chartPasca = $this->formatLayananSeries($data, 'pasca', $f['isMulti'], $isPerBulan, $isPerTriwulan, $satkerMap);
        $chartSkhpn = $this->formatLayananSeries($data, 'skhpn', $f['isMulti'], $isPerBulan, $isPerTriwulan, $satkerMap);

        return response()->json([
            'is_multi' => $f['isMulti'], 
            'trend_labels' => $trendLabels,
            'trend' => ['rj' => $chartRj, 'pasca' => $chartPasca, 'skhpn' => $chartSkhpn]
        ]);
    }

    private function formatLayananSeries($data, $column, $isMulti, $isPerBulan, $isPerTriwulan, $satkerMap) {
        $series = [];
        $len = $isPerBulan ? 12 : ($isPerTriwulan ? 4 : 1);

        if (($isPerBulan || $isPerTriwulan) && $isMulti) {
            foreach ($satkerMap as $sId => $sName) {
                $arr = array_fill(0, $len, 0);
                foreach ($data as $row) {
                    if ($row->satuan_kerja_id == $sId) $arr[$row->periode - 1] = (int) $row->{$column};
                }
                $series[] = ['name' => $sName, 'data' => $arr];
            }
        } elseif (($isPerBulan || $isPerTriwulan) && !$isMulti) {
            $arr = array_fill(0, $len, 0);
            foreach ($data as $row) { $arr[$row->periode - 1] = (int) $row->{$column}; }
            $series[] = ['name' => 'Realisasi', 'data' => $arr];
        } elseif (!($isPerBulan || $isPerTriwulan) && $isMulti) {
            $arr = [];
            foreach ($satkerMap as $sId => $sName) {
                $val = 0;
                foreach ($data as $row) { if ($row->satuan_kerja_id == $sId) $val = (int) $row->{$column}; }
                $arr[] = $val;
            }
            $series[] = ['name' => 'Realisasi', 'data' => $arr];
        } else {
            $val = 0;
            foreach ($data as $row) { $val = (int) $row->{$column}; }
            $series[] = ['name' => 'Realisasi', 'data' => [$val]];
        }
        return $series;
    }

    private function buildCompData($query, $f, $satkerMap, $selectStr, $groupStr) {
        $qAcc = clone $query;
        $qAcc->select('rehab_pasien.satuan_kerja_id', DB::raw("$selectStr as cat"));
        $resAcc = $qAcc->addSelect(DB::raw('COUNT(*) as total'))->groupBy('rehab_pasien.satuan_kerja_id', DB::raw($groupStr))->get();
        
        $data = $this->formatCompSeries($resAcc, $f['isMulti'], $satkerMap);
        $data['type'] = 'accumulated'; 
        return $data;
    }

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
            foreach($satkerMap as $sId => $sName) { $panelData[$sId] = ['satker' => $sName, 'items' => []]; }
            foreach ($categories as $cat) {
                $arr = [];
                foreach ($satkerMap as $sId => $sName) {
                    $val = 0;
                    foreach ($data as $row) {
                        $rCat = $row->cat ?: 'Tidak Diketahui';
                        if ($row->satuan_kerja_id == $sId && $rCat === $cat) $val = (int) $row->total;
                    }
                    $arr[] = $val;
                    if ($val > 0) $panelData[$sId]['items'][] = ['name' => $cat, 'count' => $val];
                }
                $series[] = ['name' => $cat, 'data' => $arr];
            }
            foreach($panelData as $sId => &$pData) { usort($pData['items'], function($a, $b) { return $b['count'] <=> $a['count']; }); }
            return ['labels' => array_values($satkerMap), 'series' => $series, 'panel' => array_values($panelData)];
        } else {
            $arr = [];
            foreach ($categories as $cat) { $arr[] = $catTotals[$cat]; }
            return ['labels' => $categories, 'series' => [['name' => 'Total', 'data' => $arr]], 'panel' => []];
        }
    }

    public function getChartDemografi(Request $request) {
        $f = $this->parseFilter($request);
        $satkerMap = $f['isMulti'] ? SatuanKerja::orderBy('satuan_kerja', 'asc')->pluck('satuan_kerja', 'id')->toArray() : [$f['mySatker'] => 'Satuan Kerja'];
        
        if ($f['mode_hitung'] === 'unik') {
            $sub = DB::table('rehab_riwayat')->select('rehab_pasien_id', DB::raw('MAX(id) as max_id'));
            $sub = $this->applyTimeFilter($sub, 'tanggal_rehab', $f, 'rehab_riwayat.id'); 
            $sub->groupBy('rehab_pasien_id');

            $qComp = DB::table('rehab_riwayat')
                ->joinSub($sub, 'latest', function($join) {
                    $join->on('rehab_riwayat.id', '=', 'latest.max_id');
                })
                ->join('rehab_pasien', 'rehab_pasien.id', '=', 'rehab_riwayat.rehab_pasien_id');
        } else {
            $qComp = DB::table('rehab_riwayat')->join('rehab_pasien', 'rehab_pasien.id', '=', 'rehab_riwayat.rehab_pasien_id');
            $qComp = $this->applyTimeFilter($qComp, 'tanggal_rehab', $f, 'rehab_pasien.satuan_kerja_id');
        }

        if ($f['mySatker']) {
            $qComp->where('rehab_pasien.satuan_kerja_id', $f['mySatker']);
        }
        
        $dataSumber = $this->buildCompData($qComp, $f, $satkerMap, 'sumber_pasien', 'sumber_pasien');
        $dataGender = $this->buildCompData($qComp, $f, $satkerMap, 'jenis_kelamin', 'jenis_kelamin');
        $dataDidik = $this->buildCompData($qComp, $f, $satkerMap, 'pendidikan', 'pendidikan');
        $dataPekerjaan = $this->buildCompData($qComp, $f, $satkerMap, 'pekerjaan', 'pekerjaan');
        $usiaCase = $this->getRawUsiaGroup();
        $dataUsia = $this->buildCompData($qComp, $f, $satkerMap, $usiaCase, $usiaCase);

        return response()->json([
            'is_multi' => $f['isMulti'], 
            'comp' => [
                'sumber' => $dataSumber, 'gender' => $dataGender, 'usia' => $dataUsia, 'pendidikan' => $dataDidik, 'pekerjaan' => $dataPekerjaan
            ]
        ]);
    }

    public function getRankingNarkotika(Request $request) {
        $f = $this->parseFilter($request);
        $limit  = $request->input('limit', 'all');

        if ($f['mode_hitung'] === 'unik') {
            $sub = DB::table('rehab_riwayat')->select('rehab_pasien_id', DB::raw('MAX(id) as max_id'));
            $sub = $this->applyTimeFilter($sub, 'tanggal_rehab', $f, 'rehab_riwayat.id'); 
            $sub->groupBy('rehab_pasien_id');

            $q = DB::table('rehab_riwayat_narkotika')
                ->join('rehab_riwayat', 'rehab_riwayat_id', '=', 'rehab_riwayat.id')
                ->joinSub($sub, 'latest', function($join) {
                    $join->on('rehab_riwayat.id', '=', 'latest.max_id');
                })
                ->join('rehab_pasien', 'rehab_pasien.id', '=', 'rehab_riwayat.rehab_pasien_id')
                ->join('berantas_narkotika', 'narkotika_id', '=', 'berantas_narkotika.id');
        } else {
            $q = DB::table('rehab_riwayat_narkotika')
                ->join('rehab_riwayat', 'rehab_riwayat_id', '=', 'rehab_riwayat.id')
                ->join('rehab_pasien', 'rehab_pasien.id', '=', 'rehab_riwayat.rehab_pasien_id')
                ->join('berantas_narkotika', 'narkotika_id', '=', 'berantas_narkotika.id');
            $q = $this->applyTimeFilter($q, 'tanggal_rehab', $f, 'rehab_pasien.satuan_kerja_id');
        }

        if ($f['mySatker']) {
            $q->where('rehab_pasien.satuan_kerja_id', $f['mySatker']);
        }

        $q->select('berantas_narkotika.nama_narkotika as name', DB::raw('COUNT(*) as freq'))
          ->groupBy('berantas_narkotika.id', 'berantas_narkotika.nama_narkotika')
          ->orderBy('freq', 'desc');

        if ($limit !== 'all') $q->limit((int)$limit);

        $results = $q->get();
        $labels = []; $data = [];
        foreach ($results as $r) {
            $labels[] = $r->name;
            $data[] = (int)$r->freq;
        }

        return response()->json([ 'labels' => $labels, 'data' => $data ]);
    }
}