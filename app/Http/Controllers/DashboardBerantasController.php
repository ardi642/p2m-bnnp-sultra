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

        // RADAR DETEKSI TAHUN OTOMATIS
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

        // AMBIL MASTER NARKOTIKA UNTUK FILTER
        $narkotikas = DB::table('berantas_narkotika')->orderBy('nama_narkotika', 'asc')->get();

        $showTabs = in_array($user->role, ['admin', 'admin_satker', 'operator_satker']);
        $satkers = ($user->role === 'admin') ? SatuanKerja::orderBy('satuan_kerja', 'asc')->get() : [];

        return view('dashboard.berantas.index', compact('years', 'showTabs', 'satkers', 'narkotikas'));
    }

    // RUMUS HELPER: KONVERSI SATUAN
    private function getRawGram() {
        return "(CASE 
                    WHEN satuan_narkotika = 'Kg' THEN kuantitas * 1000 
                    WHEN satuan_narkotika = 'Ton' THEN kuantitas * 1000000 
                    ELSE kuantitas 
                END)";
    }

    public function getGlobalData(Request $request)
    {
        $start = $request->input('start_year', date('Y'));
        $end = $request->input('end_year', date('Y'));
        
        $user = Auth::user();
        $satkerId = ($user->role === 'admin') ? $request->input('satker_id') : $user->pegawai?->satuan_kerja_id;

        // KARTU 1: LKN
        $qLkn = DB::table('berantas_ungkap_kasus')
            ->whereYear('tanggal_kejadian', '>=', $start)
            ->whereYear('tanggal_kejadian', '<=', $end);
        if ($satkerId) $qLkn->where('satuan_kerja_id', $satkerId);
        
        $totalLkn = $qLkn->count();
        $totalTskLkn = DB::table('berantas_ungkap_tersangka')
            ->join('berantas_ungkap_kasus', 'berantas_ungkap_kasus_id', '=', 'berantas_ungkap_kasus.id')
            ->whereYear('tanggal_kejadian', '>=', $start)->whereYear('tanggal_kejadian', '<=', $end)
            ->when($satkerId, function($q) use ($satkerId) { return $q->where('satuan_kerja_id', $satkerId); })
            ->count();
            
        $bbLkn = DB::table('berantas_ungkap_barang_bukti')
            ->join('berantas_ungkap_kasus', 'berantas_ungkap_kasus_id', '=', 'berantas_ungkap_kasus.id')
            ->where('kategori', 'Narkotika')
            ->whereYear('tanggal_kejadian', '>=', $start)->whereYear('tanggal_kejadian', '<=', $end)
            ->when($satkerId, function($q) use ($satkerId) { return $q->where('satuan_kerja_id', $satkerId); })
            ->select(
                DB::raw('COUNT(berantas_ungkap_barang_bukti.id) as item'), 
                DB::raw('SUM(' . $this->getRawGram() . ') as gram')
            )->first();

        // KARTU 2: TAT
        $qTat = DB::table('berantas_tat')
            ->whereYear('tanggal_pelaksanaan', '>=', $start)->whereYear('tanggal_pelaksanaan', '<=', $end);
        if ($satkerId) $qTat->where('satuan_kerja_id', $satkerId);
        
        $totalTat = $qTat->count();
        $totalTskTat = DB::table('berantas_tat_tersangka')
            ->join('berantas_tat', 'berantas_tat_id', '=', 'berantas_tat.id')
            ->whereYear('tanggal_pelaksanaan', '>=', $start)->whereYear('tanggal_pelaksanaan', '<=', $end)
            ->when($satkerId, function($q) use ($satkerId) { return $q->where('satuan_kerja_id', $satkerId); })
            ->count();
            
        $bbTat = DB::table('berantas_tat_barang_bukti')
            ->join('berantas_tat', 'berantas_tat_id', '=', 'berantas_tat.id')
            ->where('kategori', 'Narkotika')
            ->whereYear('tanggal_pelaksanaan', '>=', $start)->whereYear('tanggal_pelaksanaan', '<=', $end)
            ->when($satkerId, function($q) use ($satkerId) { return $q->where('satuan_kerja_id', $satkerId); })
            ->select(
                DB::raw('COUNT(berantas_tat_barang_bukti.id) as item'), 
                DB::raw('SUM(' . $this->getRawGram() . ') as gram')
            )->first();

        // KARTU 3: REGISTER BB
        $bbReg = DB::table('berantas_register_barang_bukti_items')
            ->join('berantas_register_barang_bukti', 'register_barang_bukti_id', '=', 'berantas_register_barang_bukti.id')
            ->where('kategori', 'Narkotika')
            ->whereYear('tanggal_perolehan', '>=', $start)->whereYear('tanggal_perolehan', '<=', $end)
            ->when($satkerId, function($q) use ($satkerId) { return $q->where('satuan_kerja_id', $satkerId); })
            ->select(
                DB::raw('COUNT(berantas_register_barang_bukti_items.id) as item'), 
                DB::raw('SUM(' . $this->getRawGram() . ') as gram'),
                'sumber_perolehan'
            )->groupBy('sumber_perolehan')->get();

        $regTotalGram = 0; $regTotalItem = 0;
        $regTangkapGram = 0; $regTangkapItem = 0;
        $regTemuanGram = 0; $regTemuanItem = 0;

        foreach ($bbReg as $b) {
            $regTotalGram += $b->gram;
            $regTotalItem += $b->item;
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

    private function parseFilter(Request $request) {
        $user = Auth::user();
        $isAdmin = ($user->role === 'admin');
        $selectedSatker = $request->input('satker_id');
        
        return [
            'mode' => $request->input('mode', 'monthly'),
            'm_year' => $request->input('m_year', date('Y')),
            'm_month' => $request->input('m_month', 'all'),
            'y_start' => $request->input('y_start', date('Y')),
            'y_end' => $request->input('y_end', date('Y')),
            'narkotika_id' => $request->input('narkotika_id', ''),
            'mySatker' => $isAdmin ? $selectedSatker : $user->pegawai?->satuan_kerja_id,
            'isMulti' => ($isAdmin && empty($selectedSatker))
        ];
    }

    private function getLabelsAndTime($f) {
        if ($f['mode'] === 'monthly') {
            return ['labels' => ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'], 'points' => range(1, 12)];
        }
        return ['labels' => range($f['y_start'], $f['y_end']), 'points' => range($f['y_start'], $f['y_end'])];
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

    // =========================================================================
    // API: BLOK A (UNGKAP KASUS LKN)
    // =========================================================================
    public function getChartLkn(Request $request) {
        $f = $this->parseFilter($request);
        $time = $this->getLabelsAndTime($f);
        
        $chartKasus = []; $chartTsk = []; $chartBerat = [];
        $compGender = []; $compKerja = []; $compLabels = [];

        $satkers = $f['isMulti'] ? SatuanKerja::orderBy('satuan_kerja', 'asc')->get() : [ (object)['id' => $f['mySatker'], 'satuan_kerja' => 'Satuan Kerja'] ];
        
        foreach ($satkers as $satker) {
            $compLabels[] = $satker->satuan_kerja;
            $dataKasus = []; $dataTsk = []; $dataBerat = [];
            
            foreach ($time['points'] as $tVal) {
                // TREN
                $qK = DB::table('berantas_ungkap_kasus')->where('satuan_kerja_id', $satker->id);
                $qK = $this->applyTime($qK, 'tanggal_kejadian', $f, $tVal);
                if ($f['narkotika_id']) {
                    $qK->whereExists(function($q) use ($f) {
                        $q->select(DB::raw(1))->from('berantas_ungkap_barang_bukti')
                          ->whereColumn('berantas_ungkap_kasus_id', 'berantas_ungkap_kasus.id')
                          ->where('narkotika_id', $f['narkotika_id']);
                    });
                }
                $dataKasus[] = $qK->count();

                $qT = DB::table('berantas_ungkap_tersangka')
                    ->join('berantas_ungkap_kasus', 'berantas_ungkap_kasus_id', '=', 'berantas_ungkap_kasus.id')
                    ->where('satuan_kerja_id', $satker->id);
                $qT = $this->applyTime($qT, 'tanggal_kejadian', $f, $tVal);
                if ($f['narkotika_id']) {
                    $qT->whereExists(function($q) use ($f) {
                        $q->select(DB::raw(1))->from('berantas_ungkap_barang_bukti')
                          ->whereColumn('berantas_ungkap_kasus_id', 'berantas_ungkap_kasus.id')
                          ->where('narkotika_id', $f['narkotika_id']);
                    });
                }
                $dataTsk[] = $qT->count();

                $qB = DB::table('berantas_ungkap_barang_bukti')
                    ->join('berantas_ungkap_kasus', 'berantas_ungkap_kasus_id', '=', 'berantas_ungkap_kasus.id')
                    ->where('kategori', 'Narkotika')->where('satuan_kerja_id', $satker->id);
                $qB = $this->applyTime($qB, 'tanggal_kejadian', $f, $tVal);
                if ($f['narkotika_id']) $qB->where('narkotika_id', $f['narkotika_id']);
                $dataBerat[] = (float) $qB->sum(DB::raw($this->getRawGram()));
            }
            $chartKasus[] = ['name' => $satker->satuan_kerja, 'data' => $dataKasus];
            $chartTsk[]   = ['name' => $satker->satuan_kerja, 'data' => $dataTsk];
            $chartBerat[] = ['name' => $satker->satuan_kerja, 'data' => $dataBerat];

            // PROPORSI DEMOGRAFI
            $qComp = DB::table('berantas_ungkap_tersangka')
                ->join('berantas_ungkap_kasus', 'berantas_ungkap_kasus_id', '=', 'berantas_ungkap_kasus.id')
                ->where('satuan_kerja_id', $satker->id);
            $qComp = $this->applyTime($qComp, 'tanggal_kejadian', $f);
            if ($f['narkotika_id']) {
                $qComp->whereExists(function($q) use ($f) {
                    $q->select(DB::raw(1))->from('berantas_ungkap_barang_bukti')
                      ->whereColumn('berantas_ungkap_kasus_id', 'berantas_ungkap_kasus.id')
                      ->where('narkotika_id', $f['narkotika_id']);
                });
            }

            $resGen = (clone $qComp)->select('jenis_kelamin', DB::raw('count(*) as total'))->groupBy('jenis_kelamin')->pluck('total', 'jenis_kelamin');
            // PERBAIKAN BUG LAKI-LAKI LKN (Huruf L Besar)
            $compGender['Laki-laki'][] = $resGen['Laki-Laki'] ?? 0;
            $compGender['Perempuan'][] = $resGen['Perempuan'] ?? 0;

            $resPek = (clone $qComp)->select('pekerjaan', DB::raw('count(*) as total'))->groupBy('pekerjaan')->pluck('total', 'pekerjaan');
            foreach ($resPek as $pek => $tot) { if($pek) $compKerja[$pek][$satker->id] = $tot; }
        }

        $seriesKerja = [];
        foreach ($compKerja as $pekName => $satkerData) {
            $dataArr = [];
            foreach ($satkers as $s) { $dataArr[] = $satkerData[$s->id] ?? 0; }
            $seriesKerja[] = ['name' => $pekName, 'data' => $dataArr];
        }

        return response()->json([
            'is_multi' => $f['isMulti'], 'trend_labels' => $time['labels'], 'comp_labels' => $compLabels,
            'trend' => ['kasus' => $chartKasus, 'tersangka' => $chartTsk, 'berat' => $chartBerat],
            'comp' => [
                'gender' => [['name' => 'Laki-laki', 'data' => $compGender['Laki-laki'] ?? []], ['name' => 'Perempuan', 'data' => $compGender['Perempuan'] ?? []]],
                'pekerjaan' => $seriesKerja
            ]
        ]);
    }

    // =========================================================================
    // API: BLOK B (TIM ASESMEN TERPADU - TAT)
    // =========================================================================
    public function getChartTat(Request $request) {
        $f = $this->parseFilter($request);
        $time = $this->getLabelsAndTime($f);
        
        $chartKasus = []; $chartTsk = []; 
        $compRekom = []; $compGender = []; $compDidik = []; $compKerja = []; $compLabels = [];

        $satkers = $f['isMulti'] ? SatuanKerja::orderBy('satuan_kerja', 'asc')->get() : [ (object)['id' => $f['mySatker'], 'satuan_kerja' => 'Satuan Kerja'] ];
        
        foreach ($satkers as $satker) {
            $compLabels[] = $satker->satuan_kerja;
            $dataKasus = []; $dataTsk = [];
            
            foreach ($time['points'] as $tVal) {
                $qK = DB::table('berantas_tat')->where('satuan_kerja_id', $satker->id);
                $qK = $this->applyTime($qK, 'tanggal_pelaksanaan', $f, $tVal);
                if ($f['narkotika_id']) {
                    $qK->whereExists(function($q) use ($f) {
                        $q->select(DB::raw(1))->from('berantas_tat_barang_bukti')
                          ->whereColumn('berantas_tat_id', 'berantas_tat.id')
                          ->where('narkotika_id', $f['narkotika_id']);
                    });
                }
                $dataKasus[] = $qK->count();

                $qT = DB::table('berantas_tat_tersangka')
                    ->join('berantas_tat', 'berantas_tat_id', '=', 'berantas_tat.id')
                    ->where('satuan_kerja_id', $satker->id);
                $qT = $this->applyTime($qT, 'tanggal_pelaksanaan', $f, $tVal);
                if ($f['narkotika_id']) {
                    $qT->whereExists(function($q) use ($f) {
                        $q->select(DB::raw(1))->from('berantas_tat_barang_bukti')
                          ->whereColumn('berantas_tat_id', 'berantas_tat.id')
                          ->where('narkotika_id', $f['narkotika_id']);
                    });
                }
                $dataTsk[] = $qT->count();
            }
            $chartKasus[] = ['name' => $satker->satuan_kerja, 'data' => $dataKasus];
            $chartTsk[]   = ['name' => $satker->satuan_kerja, 'data' => $dataTsk];

            // PROPORSI REKOMENDASI
            $qRekom = DB::table('berantas_tat')->where('satuan_kerja_id', $satker->id);
            $qRekom = $this->applyTime($qRekom, 'tanggal_pelaksanaan', $f);
            if ($f['narkotika_id']) {
                $qRekom->whereExists(function($q) use ($f) { $q->select(DB::raw(1))->from('berantas_tat_barang_bukti')->whereColumn('berantas_tat_id', 'berantas_tat.id')->where('narkotika_id', $f['narkotika_id']); });
            }
            $resRekom = (clone $qRekom)->select('tindak_lanjut_rekomendasi as rek', DB::raw('count(*) as total'))->groupBy('rek')->pluck('total', 'rek');
            $compRekom['Dilaksanakan'][] = $resRekom['dilaksanakan'] ?? 0;
            $compRekom['Tidak Dilaksanakan'][] = $resRekom['tidak dilaksanakan'] ?? 0;

            // PROPORSI DEMOGRAFI
            $qTsk = DB::table('berantas_tat_tersangka')->join('berantas_tat', 'berantas_tat_id', '=', 'berantas_tat.id')->where('satuan_kerja_id', $satker->id);
            $qTsk = $this->applyTime($qTsk, 'tanggal_pelaksanaan', $f);
            if ($f['narkotika_id']) {
                $qTsk->whereExists(function($q) use ($f) { $q->select(DB::raw(1))->from('berantas_tat_barang_bukti')->whereColumn('berantas_tat_id', 'berantas_tat.id')->where('narkotika_id', $f['narkotika_id']); });
            }
            
            $resGen = (clone $qTsk)->select('jenis_kelamin', DB::raw('count(*) as total'))->groupBy('jenis_kelamin')->pluck('total', 'jenis_kelamin');
            // PERBAIKAN BUG LAKI-LAKI TAT (Huruf l Kecil)
            $compGender['Laki-laki'][] = $resGen['Laki-laki'] ?? 0;
            $compGender['Perempuan'][] = $resGen['Perempuan'] ?? 0;

            $resDik = (clone $qTsk)->select('pendidikan', DB::raw('count(*) as total'))->groupBy('pendidikan')->pluck('total', 'pendidikan');
            foreach ($resDik as $dik => $tot) { if($dik) $compDidik[$dik][$satker->id] = $tot; }

            $resPek = (clone $qTsk)->select('pekerjaan', DB::raw('count(*) as total'))->groupBy('pekerjaan')->pluck('total', 'pekerjaan');
            foreach ($resPek as $pek => $tot) { if($pek) $compKerja[$pek][$satker->id] = $tot; }
        }

        $seriesDidik = []; foreach ($compDidik as $n => $sd) { $arr = []; foreach ($satkers as $s) { $arr[] = $sd[$s->id] ?? 0; } $seriesDidik[] = ['name' => $n, 'data' => $arr]; }
        $seriesKerja = []; foreach ($compKerja as $n => $sd) { $arr = []; foreach ($satkers as $s) { $arr[] = $sd[$s->id] ?? 0; } $seriesKerja[] = ['name' => $n, 'data' => $arr]; }

        return response()->json([
            'is_multi' => $f['isMulti'], 'trend_labels' => $time['labels'], 'comp_labels' => $compLabels,
            'trend' => ['kasus' => $chartKasus, 'tersangka' => $chartTsk],
            'comp' => [
                'rekom' => [['name' => 'Dilaksanakan', 'data' => $compRekom['Dilaksanakan'] ?? []], ['name' => 'Tidak Dilaksanakan', 'data' => $compRekom['Tidak Dilaksanakan'] ?? []]],
                'gender' => [['name' => 'Laki-laki', 'data' => $compGender['Laki-laki'] ?? []], ['name' => 'Perempuan', 'data' => $compGender['Perempuan'] ?? []]],
                'pendidikan' => $seriesDidik, 'pekerjaan' => $seriesKerja
            ]
        ]);
    }

    // =========================================================================
    // API: BLOK C (REGISTER BARANG BUKTI)
    // =========================================================================
    public function getChartBb(Request $request) {
        $f = $this->parseFilter($request);
        $time = $this->getLabelsAndTime($f);
        
        $chartBerat = []; $chartItem = []; $compSumber = []; $compLabels = [];
        $satkers = $f['isMulti'] ? SatuanKerja::orderBy('satuan_kerja', 'asc')->get() : [ (object)['id' => $f['mySatker'], 'satuan_kerja' => 'Satuan Kerja'] ];
        
        foreach ($satkers as $satker) {
            $compLabels[] = $satker->satuan_kerja;
            $dataBerat = []; $dataItem = [];
            
            foreach ($time['points'] as $tVal) {
                $qB = DB::table('berantas_register_barang_bukti_items')
                    ->join('berantas_register_barang_bukti', 'register_barang_bukti_id', '=', 'berantas_register_barang_bukti.id')
                    ->where('kategori', 'Narkotika')->where('satuan_kerja_id', $satker->id);
                $qB = $this->applyTime($qB, 'tanggal_perolehan', $f, $tVal);
                if ($f['narkotika_id']) $qB->where('narkotika_id', $f['narkotika_id']);
                
                $res = $qB->select(
                    DB::raw('COUNT(berantas_register_barang_bukti_items.id) as item'), 
                    DB::raw('SUM(' . $this->getRawGram() . ') as gram') 
                )->first();
                
                $dataBerat[] = (float) $res->gram;
                $dataItem[] = (int) $res->item;
            }
            $chartBerat[] = ['name' => $satker->satuan_kerja, 'data' => $dataBerat];
            $chartItem[]  = ['name' => $satker->satuan_kerja, 'data' => $dataItem];

            // PROPORSI SUMBER PEROLEHAN
            $qComp = DB::table('berantas_register_barang_bukti_items')
                ->join('berantas_register_barang_bukti', 'register_barang_bukti_id', '=', 'berantas_register_barang_bukti.id')
                ->where('kategori', 'Narkotika')->where('satuan_kerja_id', $satker->id);
            $qComp = $this->applyTime($qComp, 'tanggal_perolehan', $f);
            if ($f['narkotika_id']) $qComp->where('narkotika_id', $f['narkotika_id']);

            $resSumber = (clone $qComp)->select('sumber_perolehan', DB::raw('count(*) as total'))->groupBy('sumber_perolehan')->pluck('total', 'sumber_perolehan');
            $compSumber['Hasil Tangkap'][] = $resSumber['Hasil Tangkap'] ?? 0;
            $compSumber['Temuan'][] = $resSumber['Temuan'] ?? 0;
        }

        return response()->json([
            'is_multi' => $f['isMulti'], 'trend_labels' => $time['labels'], 'comp_labels' => $compLabels,
            'trend' => ['berat' => $chartBerat, 'item' => $chartItem],
            'comp' => [
                'sumber' => [['name' => 'Hasil Tangkap', 'data' => $compSumber['Hasil Tangkap'] ?? []], ['name' => 'Temuan', 'data' => $compSumber['Temuan'] ?? []]]
            ]
        ]);
    }

    // =========================================================================
    // API: BLOK D (RANKING NARKOTIKA TERBANYAK/TERBERAT)
    // =========================================================================
    public function getRankingNarkotika(Request $request) {
        $f = $this->parseFilter($request);
        
        $source = $request->input('source', 'lkn');
        $metric = $request->input('metric', 'berat');
        $limit  = $request->input('limit', 'all');

        if ($source === 'tat') {
            $q = DB::table('berantas_tat_barang_bukti')->join('berantas_tat', 'berantas_tat_id', '=', 'berantas_tat.id')
                ->join('berantas_narkotika', 'narkotika_id', '=', 'berantas_narkotika.id')
                ->where('berantas_tat_barang_bukti.kategori', 'Narkotika');
            $q = $this->applyTime($q, 'tanggal_pelaksanaan', $f);
        } else if ($source === 'bb') {
            $q = DB::table('berantas_register_barang_bukti_items')->join('berantas_register_barang_bukti', 'register_barang_bukti_id', '=', 'berantas_register_barang_bukti.id')
                ->join('berantas_narkotika', 'narkotika_id', '=', 'berantas_narkotika.id')
                ->where('berantas_register_barang_bukti_items.kategori', 'Narkotika');
            $q = $this->applyTime($q, 'tanggal_perolehan', $f);
        } else {
            $q = DB::table('berantas_ungkap_barang_bukti')->join('berantas_ungkap_kasus', 'berantas_ungkap_kasus_id', '=', 'berantas_ungkap_kasus.id')
                ->join('berantas_narkotika', 'narkotika_id', '=', 'berantas_narkotika.id')
                ->where('berantas_ungkap_barang_bukti.kategori', 'Narkotika');
            $q = $this->applyTime($q, 'tanggal_kejadian', $f);
        }

        if ($f['mySatker']) {
            $q->where('satuan_kerja_id', $f['mySatker']);
        }

        $q->select(
            'berantas_narkotika.nama_narkotika as name',
            DB::raw('COUNT(*) as freq'),
            DB::raw('SUM(' . $this->getRawGram() . ') as berat')
        )->groupBy('berantas_narkotika.id', 'berantas_narkotika.nama_narkotika');

        $q->orderBy($metric, 'desc');

        if ($limit !== 'all') {
            $q->limit((int)$limit);
        }

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