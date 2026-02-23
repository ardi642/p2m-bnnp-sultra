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
        return "CASE 
                    WHEN usia < 15 THEN '< 15 tahun'
                    WHEN usia BETWEEN 15 AND 19 THEN '15-19 tahun'
                    WHEN usia BETWEEN 20 AND 34 THEN '20-34 tahun'
                    WHEN usia BETWEEN 35 AND 49 THEN '35-49 tahun'
                    WHEN usia BETWEEN 50 AND 64 THEN '50-64 tahun'
                    ELSE '65+ tahun'
                END";
    }

    public function getGlobalData(Request $request)
    {
        $start = $request->input('start_year', date('Y'));
        $end = $request->input('end_year', date('Y'));
        
        $user = Auth::user();
        $satkerId = ($user->role === 'admin') ? $request->input('satker_id') : $user->pegawai?->satuan_kerja_id;

        $qTarget = DB::table('rehab_target')->whereBetween('tahun', [$start, $end]);
        if ($satkerId) $qTarget->where('satuan_kerja_id', $satkerId);
        $target = $qTarget->select(
            DB::raw('SUM(target_rawat_jalan) as rj'),
            DB::raw('SUM(target_pasca_rehab) as pasca'),
            DB::raw('SUM(target_skhpn) as skhpn')
        )->first();

        $qLaporan = DB::table('rehab_laporan')->whereYear('tanggal', '>=', $start)->whereYear('tanggal', '<=', $end);
        if ($satkerId) $qLaporan->where('satuan_kerja_id', $satkerId);
        $realisasi = $qLaporan->select(
            DB::raw('SUM(realisasi_rawat_jalan) as rj'),
            DB::raw('SUM(realisasi_pasca_rehab) as pasca'),
            DB::raw('SUM(realisasi_skhpn) as skhpn')
        )->first();

        $qRiwayat = DB::table('rehab_riwayat')
            ->join('rehab_pasien', 'rehab_pasien.id', '=', 'rehab_riwayat.rehab_pasien_id')
            ->whereYear('tanggal_rehab', '>=', $start)->whereYear('tanggal_rehab', '<=', $end);
        if ($satkerId) $qRiwayat->where('rehab_pasien.satuan_kerja_id', $satkerId);

        $klienTotal = $qRiwayat->count();
        $klienUnik = $qRiwayat->distinct('rehab_pasien_id')->count('rehab_pasien_id');
        $sumber = (clone $qRiwayat)->select('sumber_pasien', DB::raw('count(*) as total'))->groupBy('sumber_pasien')->pluck('total', 'sumber_pasien');

        return response()->json([
            'rj' => [ 'realisasi' => (int)$realisasi->rj, 'target' => (int)$target->rj ],
            'pasca' => [ 'realisasi' => (int)$realisasi->pasca, 'target' => (int)$target->pasca ],
            'skhpn' => [ 'realisasi' => (int)$realisasi->skhpn, 'target' => (int)$target->skhpn ],
            'klien' => [ 
                'total' => $klienTotal, 
                'unik' => $klienUnik, 
                'voluntary' => $sumber['Voluntary'] ?? 0, 
                'compulsory' => $sumber['Compulsory'] ?? 0 
            ]
        ]);
    }

    private function parseFilter(Request $request) {
        $user = Auth::user();
        $isAdmin = ($user->role === 'admin');
        $selectedSatker = $request->input('satker_id');
        
        return [
            'mode' => $request->input('mode', 'monthly'),
            'year' => $request->input('year', date('Y')),
            'm_year' => $request->input('m_year', date('Y')),
            'm_month' => $request->input('m_month', 'all'),
            'y_start' => $request->input('y_start', date('Y')),
            'y_end' => $request->input('y_end', date('Y')),
            'mySatker' => $isAdmin ? $selectedSatker : $user->pegawai?->satuan_kerja_id,
            'isMulti' => ($isAdmin && empty($selectedSatker))
        ];
    }

    private function applyTime($q, $dateCol, $f, $val = null) {
        if ($val !== null) { 
            return ($f['mode'] === 'monthly') 
                ? $q->whereYear($dateCol, $f['m_year'])->whereMonth($dateCol, $val) 
                : $q->whereYear($dateCol, $val);
        } else { 
            if ($f['mode'] === 'monthly') {
                $q->whereYear($dateCol, $f['m_year']);
                if ($f['m_month'] !== 'all') $q->whereMonth($dateCol, (int)$f['m_month']);
                return $q;
            }
            return $q->whereYear($dateCol, '>=', $f['y_start'])->whereYear($dateCol, '<=', $f['y_end']);
        }
    }

    public function getChartLayanan(Request $request) {
        $f = $this->parseFilter($request);
        $year = $f['year'];
        
        $chartRj = []; $chartPasca = []; $chartSkhpn = []; $compLabels = [];
        $satkers = $f['isMulti'] ? SatuanKerja::orderBy('satuan_kerja', 'asc')->get() : [ (object)['id' => $f['mySatker'], 'satuan_kerja' => 'Satuan Kerja'] ];
        
        $totalTar = ['rj' => 0, 'pasca' => 0, 'skhpn' => 0];
        $totalReal = ['rj' => 0, 'pasca' => 0, 'skhpn' => 0];

        foreach ($satkers as $satker) {
            $compLabels[] = $satker->satuan_kerja;
            $dataRj = []; $dataPasca = []; $dataSkhpn = [];
            
            for ($m = 1; $m <= 12; $m++) {
                $qL = DB::table('rehab_laporan')->where('satuan_kerja_id', $satker->id)
                      ->whereYear('tanggal', $year)->whereMonth('tanggal', $m)
                      ->select(DB::raw('SUM(realisasi_rawat_jalan) as rj'), DB::raw('SUM(realisasi_pasca_rehab) as pasca'), DB::raw('SUM(realisasi_skhpn) as skhpn'))
                      ->first();
                $dataRj[] = (int) $qL->rj;
                $dataPasca[] = (int) $qL->pasca;
                $dataSkhpn[] = (int) $qL->skhpn;
            }
            $chartRj[] = ['name' => $satker->satuan_kerja, 'data' => $dataRj];
            $chartPasca[] = ['name' => $satker->satuan_kerja, 'data' => $dataPasca];
            $chartSkhpn[] = ['name' => $satker->satuan_kerja, 'data' => $dataSkhpn];

            $qT = DB::table('rehab_target')->where('satuan_kerja_id', $satker->id)->where('tahun', $year)->first();
            $totalTar['rj'] += $qT ? $qT->target_rawat_jalan : 0;
            $totalTar['pasca'] += $qT ? $qT->target_pasca_rehab : 0;
            $totalTar['skhpn'] += $qT ? $qT->target_skhpn : 0;

            $qR = DB::table('rehab_laporan')->where('satuan_kerja_id', $satker->id)->whereYear('tanggal', $year)
                    ->select(DB::raw('SUM(realisasi_rawat_jalan) as rj'), DB::raw('SUM(realisasi_pasca_rehab) as pasca'), DB::raw('SUM(realisasi_skhpn) as skhpn'))->first();
            $totalReal['rj'] += (int) $qR->rj;
            $totalReal['pasca'] += (int) $qR->pasca;
            $totalReal['skhpn'] += (int) $qR->skhpn;
        }

        $pctRj = $totalTar['rj'] > 0 ? round(($totalReal['rj'] / $totalTar['rj']) * 100, 1) : ($totalReal['rj'] > 0 ? 100 : 0);
        $pctPasca = $totalTar['pasca'] > 0 ? round(($totalReal['pasca'] / $totalTar['pasca']) * 100, 1) : ($totalReal['pasca'] > 0 ? 100 : 0);
        $pctSkhpn = $totalTar['skhpn'] > 0 ? round(($totalReal['skhpn'] / $totalTar['skhpn']) * 100, 1) : ($totalReal['skhpn'] > 0 ? 100 : 0);

        return response()->json([
            'is_multi' => $f['isMulti'], 
            'trend_labels' => ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
            'trend' => ['rj' => $chartRj, 'pasca' => $chartPasca, 'skhpn' => $chartSkhpn],
            'progress' => [
                'rj' => ['real' => $totalReal['rj'], 'target' => $totalTar['rj'], 'pct' => $pctRj],
                'pasca' => ['real' => $totalReal['pasca'], 'target' => $totalTar['pasca'], 'pct' => $pctPasca],
                'skhpn' => ['real' => $totalReal['skhpn'], 'target' => $totalTar['skhpn'], 'pct' => $pctSkhpn]
            ]
        ]);
    }

    public function getChartDemografi(Request $request) {
        $f = $this->parseFilter($request);
        $time = ($f['mode'] === 'monthly') 
            ? ['labels' => ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'], 'points' => range(1, 12)]
            : ['labels' => range($f['y_start'], $f['y_end']), 'points' => range($f['y_start'], $f['y_end'])];
        
        $chartKedatangan = []; $compLabels = [];
        $compSumber = []; $compGender = []; $compUsia = []; $compDik = []; $compPek = [];

        $satkers = $f['isMulti'] ? SatuanKerja::orderBy('satuan_kerja', 'asc')->get() : [ (object)['id' => $f['mySatker'], 'satuan_kerja' => 'Satuan Kerja'] ];
        
        foreach ($satkers as $satker) {
            $compLabels[] = $satker->satuan_kerja;
            $dataKedatangan = [];
            
            foreach ($time['points'] as $tVal) {
                $qK = DB::table('rehab_riwayat')->join('rehab_pasien', 'rehab_pasien.id', '=', 'rehab_riwayat.rehab_pasien_id')->where('satuan_kerja_id', $satker->id);
                $qK = $this->applyTime($qK, 'tanggal_rehab', $f, $tVal);
                $dataKedatangan[] = $qK->count();
            }
            $chartKedatangan[] = ['name' => $satker->satuan_kerja, 'data' => $dataKedatangan];

            $qComp = DB::table('rehab_riwayat')->join('rehab_pasien', 'rehab_pasien.id', '=', 'rehab_riwayat.rehab_pasien_id')->where('satuan_kerja_id', $satker->id);
            $qComp = $this->applyTime($qComp, 'tanggal_rehab', $f);

            $resSumber = (clone $qComp)->select('sumber_pasien', DB::raw('count(*) as total'))->groupBy('sumber_pasien')->pluck('total', 'sumber_pasien');
            $compSumber['Voluntary'][] = $resSumber['Voluntary'] ?? 0;
            $compSumber['Compulsory'][] = $resSumber['Compulsory'] ?? 0;

            $resGen = (clone $qComp)->select('jenis_kelamin', DB::raw('count(*) as total'))->groupBy('jenis_kelamin')->pluck('total', 'jenis_kelamin');
            $compGender['Laki-laki'][] = $resGen['Laki-laki'] ?? 0;
            $compGender['Perempuan'][] = $resGen['Perempuan'] ?? 0;

            $resUsia = (clone $qComp)->select(DB::raw($this->getRawUsiaGroup().' as grup'), DB::raw('count(*) as total'))->groupBy('grup')->pluck('total', 'grup');
            foreach ($resUsia as $grup => $tot) { if($grup) $compUsia[$grup][$satker->id] = $tot; }

            $resDik = (clone $qComp)->select('pendidikan', DB::raw('count(*) as total'))->groupBy('pendidikan')->pluck('total', 'pendidikan');
            foreach ($resDik as $dik => $tot) { if($dik) $compDik[$dik][$satker->id] = $tot; }

            $resPek = (clone $qComp)->select('pekerjaan', DB::raw('count(*) as total'))->groupBy('pekerjaan')->pluck('total', 'pekerjaan');
            foreach ($resPek as $pek => $tot) { if($pek) $compPek[$pek][$satker->id] = $tot; }
        }

        $formatSeries = function($compArray) use ($satkers) {
            $series = [];
            foreach ($compArray as $name => $satkerData) {
                $arr = []; foreach ($satkers as $s) { $arr[] = $satkerData[$s->id] ?? 0; }
                $series[] = ['name' => $name, 'data' => $arr];
            }
            return $series;
        };

        return response()->json([
            'is_multi' => $f['isMulti'], 'trend_labels' => $time['labels'], 'comp_labels' => $compLabels,
            'trend' => ['kedatangan' => $chartKedatangan],
            'comp' => [
                'sumber' => [['name' => 'Voluntary', 'data' => $compSumber['Voluntary'] ?? []], ['name' => 'Compulsory', 'data' => $compSumber['Compulsory'] ?? []]],
                'gender' => [['name' => 'Laki-laki', 'data' => $compGender['Laki-laki'] ?? []], ['name' => 'Perempuan', 'data' => $compGender['Perempuan'] ?? []]],
                'usia' => $formatSeries($compUsia),
                'pendidikan' => $formatSeries($compDik),
                'pekerjaan' => $formatSeries($compPek)
            ]
        ]);
    }

    public function getRankingNarkotika(Request $request) {
        $f = $this->parseFilter($request);
        $limit  = $request->input('limit', 'all');

        $q = DB::table('rehab_riwayat_narkotika')
            ->join('rehab_riwayat', 'rehab_riwayat_id', '=', 'rehab_riwayat.id')
            ->join('rehab_pasien', 'rehab_pasien.id', '=', 'rehab_riwayat.rehab_pasien_id')
            ->join('berantas_narkotika', 'narkotika_id', '=', 'berantas_narkotika.id');

        $q = $this->applyTime($q, 'tanggal_rehab', $f);

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