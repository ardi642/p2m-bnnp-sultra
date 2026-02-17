<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\SatuanKerja;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Filter Satker (Hanya Super Admin)
        $satkers = ($user->hasRole('admin')) ? SatuanKerja::orderBy('satuan_kerja', 'asc')->get() : [];
        $years = range(date('Y'), date('Y') - 4);
        
        $permissions = [
            'p2m' => $user->hasRole(['admin', 'admin_p2m', 'operator_p2m', 'admin_satker', 'operator_satker']),
            'berantas' => $user->hasRole(['admin', 'admin_berantas', 'operator_berantas', 'admin_satker', 'operator_satker']),
            'rehab' => $user->hasRole(['admin', 'admin_rehab', 'operator_rehab', 'admin_satker', 'operator_satker']),
        ];
        
        $defaultTab = 'p2m';
        if (!$permissions['p2m'] && $permissions['berantas']) $defaultTab = 'berantas';
        if (!$permissions['p2m'] && !$permissions['berantas'] && $permissions['rehab']) $defaultTab = 'rehab';

        return view('dashboard.index', compact('satkers', 'years', 'permissions', 'defaultTab'));
    }

    // --- MAIN API: GLOBAL DATA (KARTU ATAS) ---
    public function getGlobalData(Request $request) {
        $startYear = $request->input('start_year', date('Y'));
        $endYear   = $request->input('end_year', date('Y'));
        $satkerId  = $this->getSatkerId($request);
        $scope     = $request->input('scope', 'p2m');

        if ($scope === 'rehab') {
            return $this->getGlobalRehab($startYear, $endYear, $satkerId);
        }
        if ($scope === 'berantas') {
            return $this->getGlobalBerantas($startYear, $endYear, $satkerId);
        }
        
        return $this->getGlobalP2M($startYear, $endYear, $satkerId);
    }

    // --- MAIN API: CHART DATA (GRAFIK BAWAH) ---
    public function getChartData(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $satkerId = $this->getSatkerId($request);
        $scope = $request->input('scope', 'p2m');

        // =====================================================================
        // 1. SCOPE REHABILITASI (LOGIKA BARU - TABEL TERPISAH)
        // =====================================================================
        if ($scope === 'rehab') {
            $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            
            // Inisialisasi array data 0 untuk 12 bulan
            $tren = [
                'rj' => ['t' => array_fill(0, 12, 0), 'r' => array_fill(0, 12, 0)],
                'pr' => ['t' => array_fill(0, 12, 0), 'r' => array_fill(0, 12, 0)],
                'sk' => ['t' => array_fill(0, 12, 0), 'r' => array_fill(0, 12, 0)]
            ];

            // A. Ambil Data TARGET (rehab_target)
            $targets = DB::table('rehab_target')
                ->where('tahun', $year)
                ->when($satkerId, fn($q) => $q->where('satuan_kerja_id', $satkerId))
                ->selectRaw('
                    bulan,
                    SUM(target_rawat_jalan) as t_rj,
                    SUM(target_pasca_rehab) as t_pr,
                    SUM(target_skhpn) as t_sk
                ')
                ->groupBy('bulan')
                ->get();

            foreach ($targets as $t) {
                $idx = $t->bulan - 1; // Konversi bulan (1-12) ke index array (0-11)
                if ($idx >= 0 && $idx < 12) {
                    $tren['rj']['t'][$idx] = (int)$t->t_rj;
                    $tren['pr']['t'][$idx] = (int)$t->t_pr;
                    $tren['sk']['t'][$idx] = (int)$t->t_sk;
                }
            }

            // B. Ambil Data REALISASI (rehab_laporan harian di-sum per bulan)
            $realisasi = DB::table('rehab_laporan')
                ->whereYear('tanggal', $year)
                ->when($satkerId, fn($q) => $q->where('satuan_kerja_id', $satkerId))
                ->selectRaw('
                    MONTH(tanggal) as bulan,
                    SUM(realisasi_rawat_jalan) as r_rj,
                    SUM(realisasi_pasca_rehab) as r_pr,
                    SUM(realisasi_skhpn) as r_sk
                ')
                ->groupBy('bulan')
                ->get();

            foreach ($realisasi as $r) {
                $idx = $r->bulan - 1;
                if ($idx >= 0 && $idx < 12) {
                    $tren['rj']['r'][$idx] = (int)$r->r_rj;
                    $tren['pr']['r'][$idx] = (int)$r->r_pr;
                    $tren['sk']['r'][$idx] = (int)$r->r_sk;
                }
            }

            // C. Hitung Summary Tahunan untuk Badge (Total dari array yang sudah diisi)
            $calcPct = fn($r, $t) => $t > 0 ? round(($r / $t) * 100, 1) : 0;
            $sum = fn($arr) => array_sum($arr);

            $sumT_RJ = $sum($tren['rj']['t']); $sumR_RJ = $sum($tren['rj']['r']);
            $sumT_PR = $sum($tren['pr']['t']); $sumR_PR = $sum($tren['pr']['r']);
            $sumT_SK = $sum($tren['sk']['t']); $sumR_SK = $sum($tren['sk']['r']);

            return response()->json([
                'labels' => $labels,
                'tren' => $tren,
                'summary' => [
                    'rj' => ['t' => number_format($sumT_RJ), 'r' => number_format($sumR_RJ), 'p' => $calcPct($sumR_RJ, $sumT_RJ)],
                    'pr' => ['t' => number_format($sumT_PR), 'r' => number_format($sumR_PR), 'p' => $calcPct($sumR_PR, $sumT_PR)],
                    'sk' => ['t' => number_format($sumT_SK), 'r' => number_format($sumR_SK), 'p' => $calcPct($sumR_SK, $sumT_SK)],
                ]
            ]);
        }

        // =====================================================================
        // 2. SCOPE PEMBERANTASAN
        // =====================================================================
        if ($scope === 'berantas') {
            return $this->getBerantasChartData($year, $satkerId);
        }

        // =====================================================================
        // 3. SCOPE P2M (DEFAULT)
        // =====================================================================
        return $this->getP2MChartData($request, $year, $satkerId);
    }

    // =========================================================================
    // PRIVATE HELPER FUNCTIONS
    // =========================================================================

    // --- 1. REHABILITASI (LOGIKA GLOBAL KARTU ATAS) ---
    private function getGlobalRehab($yStart, $yEnd, $satkerId) {
        // Hitung Total Target (dari tabel rehab_target)
        $qTarget = DB::table('rehab_target')->whereBetween('tahun', [$yStart, $yEnd]);
        if ($satkerId) $qTarget->where('satuan_kerja_id', $satkerId);
        
        $dataTarget = $qTarget->selectRaw('SUM(target_rawat_jalan) as t_rj, SUM(target_pasca_rehab) as t_pr, SUM(target_skhpn) as t_sk')->first();

        // Hitung Total Realisasi (dari tabel rehab_laporan harian)
        $qRealisasi = DB::table('rehab_laporan')->whereYear('tanggal', '>=', $yStart)->whereYear('tanggal', '<=', $yEnd);
        if ($satkerId) $qRealisasi->where('satuan_kerja_id', $satkerId);

        $dataRealisasi = $qRealisasi->selectRaw('SUM(realisasi_rawat_jalan) as r_rj, SUM(realisasi_pasca_rehab) as r_pr, SUM(realisasi_skhpn) as r_sk')->first();

        // Gabungkan
        $calcPct = fn($r, $t) => $t > 0 ? round(($r / $t) * 100, 1) : 0;

        return response()->json([
            'rj' => [
                'target'    => number_format($dataTarget->t_rj ?? 0), 
                'realisasi' => number_format($dataRealisasi->r_rj ?? 0), 
                'pct'       => $calcPct(($dataRealisasi->r_rj ?? 0), ($dataTarget->t_rj ?? 0))
            ],
            'pr' => [
                'target'    => number_format($dataTarget->t_pr ?? 0), 
                'realisasi' => number_format($dataRealisasi->r_pr ?? 0), 
                'pct'       => $calcPct(($dataRealisasi->r_pr ?? 0), ($dataTarget->t_pr ?? 0))
            ],
            'sk' => [
                'target'    => number_format($dataTarget->t_sk ?? 0), 
                'realisasi' => number_format($dataRealisasi->r_sk ?? 0), 
                'pct'       => $calcPct(($dataRealisasi->r_sk ?? 0), ($dataTarget->t_sk ?? 0))
            ],
        ]);
    }

    // --- 2. PEMBERANTASAN ---
    private function getGlobalBerantas($yStart, $yEnd, $satkerId) {
        $filter = function($q, $col) use ($yStart, $yEnd, $satkerId) {
            $q->whereYear($col, '>=', $yStart)->whereYear($col, '<=', $yEnd);
            if ($satkerId) $q->where('satuan_kerja_id', $satkerId);
            return $q;
        };

        $lknIds = $filter(DB::table('berantas_ungkap_kasus'), 'tanggal_kejadian')->pluck('id');
        $tatIds = $filter(DB::table('berantas_tat'), 'tanggal_pelaksanaan')->pluck('id');
        $regIds = $filter(DB::table('berantas_register_barang_bukti'), 'tanggal_perolehan')->pluck('id');

        $sqlW = "SUM(CASE WHEN satuan_narkotika = 'Kg' THEN kuantitas * 1000 WHEN satuan_narkotika = 'Ton' THEN kuantitas * 1000000 ELSE kuantitas END)";

        $qLkn = DB::table('berantas_ungkap_barang_bukti')->whereIn('berantas_ungkap_kasus_id', $lknIds)->where('kategori', 'Narkotika');
        $qTat = DB::table('berantas_tat_barang_bukti')->whereIn('berantas_tat_id', $tatIds)->where('kategori', 'Narkotika');
        $qReg = DB::table('berantas_register_barang_bukti_items')->whereIn('register_barang_bukti_id', $regIds)->where('kategori', 'Narkotika');

        $qTangkap = (clone $qReg)->where('sumber_perolehan', 'Hasil Tangkap');
        $qTemuan = (clone $qReg)->where('sumber_perolehan', 'Temuan');

        return response()->json([
            'lkn' => [
                'kasus' => number_format($lknIds->count()),
                'tersangka' => number_format(DB::table('berantas_ungkap_tersangka')->whereIn('berantas_ungkap_kasus_id', $lknIds)->count()),
                'berat' => number_format((clone $qLkn)->selectRaw($sqlW." as t")->value('t') ?? 0, 2, ',', '.') . ' g',
                'item' => number_format((clone $qLkn)->count()) . ' Item'
            ],
            'tat' => [
                'kasus' => number_format($tatIds->count()),
                'tersangka' => number_format(DB::table('berantas_tat_tersangka')->whereIn('berantas_tat_id', $tatIds)->count()),
                'berat' => number_format((clone $qTat)->sum('kuantitas') ?? 0, 2, ',', '.') . ' g',
                'item' => number_format((clone $qTat)->count()) . ' Item'
            ],
            'bb' => [
                'total_berat' => number_format((clone $qReg)->selectRaw($sqlW." as t")->value('t') ?? 0, 2, ',', '.') . ' g',
                'total_item' => number_format((clone $qReg)->count()) . ' Item',
                'tangkap_berat' => number_format((clone $qTangkap)->selectRaw($sqlW." as t")->value('t') ?? 0, 2, ',', '.') . ' g',
                'tangkap_item' => number_format((clone $qTangkap)->count()) . ' Item',
                'temuan_berat' => number_format((clone $qTemuan)->selectRaw($sqlW." as t")->value('t') ?? 0, 2, ',', '.') . ' g',
                'temuan_item' => number_format((clone $qTemuan)->count()) . ' Item'
            ]
        ]);
    }

    private function getBerantasChartData($year, $satkerId) {
        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        
        $lknData = ['kasus' => [], 'tersangka' => [], 'item' => [], 'berat' => []];
        $tatData = ['kasus' => [], 'tersangka' => [], 'item' => [], 'berat' => []];
        $bbData  = ['reg' => [], 'tangkap' => [], 'temuan' => [], 'item' => [], 'berat' => []];
        
        $sqlGram = "SUM(CASE WHEN satuan_narkotika = 'Kg' THEN kuantitas * 1000 WHEN satuan_narkotika = 'Ton' THEN kuantitas * 1000000 ELSE kuantitas END)";

        for ($m = 1; $m <= 12; $m++) {
            // 1. DATA LKN
            $lknIds = DB::table('berantas_ungkap_kasus')->whereYear('tanggal_kejadian', $year)->whereMonth('tanggal_kejadian', $m)->when($satkerId, fn($q) => $q->where('satuan_kerja_id', $satkerId))->pluck('id');
            $lknData['kasus'][] = $lknIds->count();
            $lknData['tersangka'][] = DB::table('berantas_ungkap_tersangka')->whereIn('berantas_ungkap_kasus_id', $lknIds)->count();
            $lknData['item'][] = DB::table('berantas_ungkap_barang_bukti')->whereIn('berantas_ungkap_kasus_id', $lknIds)->where('kategori', 'Narkotika')->count();
            $lknData['berat'][] = round(DB::table('berantas_ungkap_barang_bukti')->whereIn('berantas_ungkap_kasus_id', $lknIds)->where('kategori', 'Narkotika')->selectRaw($sqlGram." as t")->value('t') ?? 0, 2);

            // 2. DATA TAT
            $tatIds = DB::table('berantas_tat')->whereYear('tanggal_pelaksanaan', $year)->whereMonth('tanggal_pelaksanaan', $m)->when($satkerId, fn($q) => $q->where('satuan_kerja_id', $satkerId))->pluck('id');
            $tatData['kasus'][] = $tatIds->count();
            $tatData['tersangka'][] = DB::table('berantas_tat_tersangka')->whereIn('berantas_tat_id', $tatIds)->count();
            $tatData['item'][] = DB::table('berantas_tat_barang_bukti')->whereIn('berantas_tat_id', $tatIds)->where('kategori', 'Narkotika')->count();
            $tatData['berat'][] = round(DB::table('berantas_tat_barang_bukti')->whereIn('berantas_tat_id', $tatIds)->where('kategori', 'Narkotika')->sum('kuantitas') ?? 0, 2);

            // 3. DATA REGISTER BB
            $regIds = DB::table('berantas_register_barang_bukti')->whereYear('tanggal_perolehan', $year)->whereMonth('tanggal_perolehan', $m)->when($satkerId, fn($q) => $q->where('satuan_kerja_id', $satkerId))->pluck('id');
            $bbData['reg'][] = $regIds->count();
            $bbData['tangkap'][] = DB::table('berantas_register_barang_bukti_items')->whereIn('register_barang_bukti_id', $regIds)->where('kategori', 'Narkotika')->where('sumber_perolehan', 'Hasil Tangkap')->count();
            $bbData['temuan'][] = DB::table('berantas_register_barang_bukti_items')->whereIn('register_barang_bukti_id', $regIds)->where('kategori', 'Narkotika')->where('sumber_perolehan', 'Temuan')->count();
            $bbData['item'][] = DB::table('berantas_register_barang_bukti_items')->whereIn('register_barang_bukti_id', $regIds)->where('kategori', 'Narkotika')->count();
            $bbData['berat'][] = round(DB::table('berantas_register_barang_bukti_items')->whereIn('register_barang_bukti_id', $regIds)->where('kategori', 'Narkotika')->selectRaw($sqlGram." as t")->value('t') ?? 0, 2);
        }

        // Summary Tahunan untuk Badge
        $yLknIds = DB::table('berantas_ungkap_kasus')->whereYear('tanggal_kejadian', $year)->when($satkerId, fn($q) => $q->where('satuan_kerja_id', $satkerId))->pluck('id');
        $yTatIds = DB::table('berantas_tat')->whereYear('tanggal_pelaksanaan', $year)->when($satkerId, fn($q) => $q->where('satuan_kerja_id', $satkerId))->pluck('id');
        $yRegIds = DB::table('berantas_register_barang_bukti')->whereYear('tanggal_perolehan', $year)->when($satkerId, fn($q) => $q->where('satuan_kerja_id', $satkerId))->pluck('id');
        
        $regItems = DB::table('berantas_register_barang_bukti_items')->whereIn('register_barang_bukti_id', $yRegIds)->where('kategori', 'Narkotika');
        $regTotalCount = (clone $regItems)->count();
        $regTangkap = (clone $regItems)->where('sumber_perolehan', 'Hasil Tangkap')->count();
        $regTemuan = (clone $regItems)->where('sumber_perolehan', 'Temuan')->count();

        return response()->json([
            'labels' => $labels,
            'tren' => ['lkn' => $lknData, 'tat' => $tatData, 'bb' => $bbData],
            'summary' => [
                'lkn' => [
                    'kasus' => number_format($yLknIds->count()),
                    'tersangka' => number_format(DB::table('berantas_ungkap_tersangka')->whereIn('berantas_ungkap_kasus_id', $yLknIds)->count()),
                    'item' => number_format(DB::table('berantas_ungkap_barang_bukti')->whereIn('berantas_ungkap_kasus_id', $yLknIds)->where('kategori', 'Narkotika')->count()),
                    'berat' => number_format(array_sum($lknData['berat']), 2, ',', '.') . ' g'
                ],
                'tat' => [
                    'kasus' => number_format($yTatIds->count()),
                    'tersangka' => number_format(DB::table('berantas_tat_tersangka')->whereIn('berantas_tat_id', $yTatIds)->count()),
                    'item' => number_format(DB::table('berantas_tat_barang_bukti')->whereIn('berantas_tat_id', $yTatIds)->where('kategori', 'Narkotika')->count()),
                    'berat' => number_format(array_sum($tatData['berat']), 2, ',', '.') . ' g'
                ],
                'bb' => [
                    'total_reg' => number_format($yRegIds->count()),
                    'total_item' => number_format($regTotalCount),
                    'total_berat' => number_format(array_sum($bbData['berat']), 2, ',', '.') . ' g',
                    'tangkap' => $regTangkap . " (" . ($regTotalCount > 0 ? round(($regTangkap/$regTotalCount)*100, 1) : 0) . "%)",
                    'temuan' => $regTemuan . " (" . ($regTotalCount > 0 ? round(($regTemuan/$regTotalCount)*100, 1) : 0) . "%)",
                ]
            ]
        ]);
    }

    // --- 3. P2M ---
    private function getGlobalP2M($yStart, $yEnd, $satkerId)
    {
        $count = function($table, $dateCol) use ($yStart, $yEnd, $satkerId) {
            $q = DB::table($table)->whereYear($dateCol, '>=', $yStart)->whereYear($dateCol, '<=', $yEnd);
            if ($satkerId) $q->where('satuan_kerja_id', $satkerId);
            return $q->count();
        };

        $sum = function($table, $colSum, $dateCol) use ($yStart, $yEnd, $satkerId) {
            $q = DB::table($table)->whereYear($dateCol, '>=', $yStart)->whereYear($dateCol, '<=', $yEnd);
            if ($satkerId) $q->where('satuan_kerja_id', $satkerId);
            return $q->sum($colSum);
        };

        $listOrang = [
            'Sosialisasi Tatap Muka' => $sum('p2m_sosialisasi', 'jumlah_peserta', 'tanggal_pelaksanaan'),
            'Tes Urine' => ['val' => $sum('p2m_tes_urine', 'jumlah_peserta', 'tanggal_pelaksanaan'), 'positif' => $sum('p2m_tes_urine', 'jumlah_positif', 'tanggal_pelaksanaan'), 'is_tes_urine' => true],
            'Pembina Upacara' => $sum('p2m_upacara', 'jumlah_peserta_upacara', 'tanggal_pelaksanaan'),
            'Car Free Day' => $sum('p2m_cfd', 'jumlah_peserta', 'tanggal_pelaksanaan'),
            'Penggiat P4GN' => $sum('p2m_lingkungan_bersinar', 'jumlah_penggiat_p4gn', 'tanggal_pencanangan'),
            'Jemaah Safari Religi' => $sum('p2m_safari_religi', 'jumlah_masyarakat', 'tanggal_pelaksanaan'),
            'Pelatihan Soft Skill' => $sum('p2m_pelatihan', 'jumlah_peserta', 'tanggal_pelaksanaan'),
            'Ketahanan Keluarga' => $sum('p2m_keluarga', 'jumlah_peserta', 'tanggal_pelaksanaan'),
        ];
        $totalOrang = 0; foreach($listOrang as $v) $totalOrang += (is_array($v) ? $v['val'] : $v);

        $listMedia = [
            'Media Elektronik' => ['freq' => $count('p2m_elektronik', 'tanggal_pelaksanaan'), 'durasi' => $sum('p2m_elektronik', 'durasi_pelaksanaan', 'tanggal_pelaksanaan')],
            'Media Non-Elektronik' => ['freq' => $count('p2m_non_elektronik', 'tanggal_mulai_pelaksanaan'), 'durasi' => $sum('p2m_non_elektronik', 'durasi_pelaksanaan', 'tanggal_mulai_pelaksanaan')],
            'Media Online' => ['freq' => $count('p2m_online', 'tanggal_mulai_pelaksanaan'), 'durasi' => $sum('p2m_online', 'durasi_pelaksanaan', 'tanggal_mulai_pelaksanaan')],
            'KIE Keliling' => ['freq' => $count('p2m_kie', 'tanggal_pelaksanaan'), 'durasi' => 0],
        ];
        $totalMediaFreq = 0; $totalMediaDurasi = 0; foreach($listMedia as $m) { $totalMediaFreq += $m['freq']; $totalMediaDurasi += $m['durasi']; }

        $listWilayah = [
            'Desa Bersinar' => $count('p2m_desa_bersinar', 'tanggal_pencanangan'),
            'Lingkungan Bersinar' => $count('p2m_lingkungan_bersinar', 'tanggal_pencanangan'),
        ];
        $totalWilayah = array_sum($listWilayah);

        $allActivities = [
            'Sosialisasi' => $count('p2m_sosialisasi', 'tanggal_pelaksanaan'),
            'Upacara' => $count('p2m_upacara', 'tanggal_pelaksanaan'),
            'Tes Urine' => $count('p2m_tes_urine', 'tanggal_pelaksanaan'),
            'Media Elektronik' => $listMedia['Media Elektronik']['freq'],
            'Media Non-Elektronik' => $listMedia['Media Non-Elektronik']['freq'],
            'Media Online' => $listMedia['Media Online']['freq'],
            'KIE Keliling' => $listMedia['KIE Keliling']['freq'],
            'Car Free Day' => $count('p2m_cfd', 'tanggal_pelaksanaan'),
            'Safari Religi' => $count('p2m_safari_religi', 'tanggal_pelaksanaan'),
            'Desa Bersinar' => $listWilayah['Desa Bersinar'],
            'Lingkungan Bersinar' => $listWilayah['Lingkungan Bersinar'],
            'Pelatihan Soft Skill' => $count('p2m_pelatihan', 'tanggal_pelaksanaan'),
            'Ketahanan Keluarga' => $count('p2m_keluarga', 'tanggal_pelaksanaan'),
        ];
        $totalGiat = array_sum($allActivities);
        arsort($allActivities);
        $chartRanking = $allActivities; 

        return response()->json([
            'orang' => ['total' => number_format($totalOrang), 'list' => $listOrang],
            'media' => ['total_freq' => number_format($totalMediaFreq), 'total_durasi' => number_format($totalMediaDurasi), 'list' => $listMedia],
            'wilayah' => ['total' => number_format($totalWilayah), 'list' => $listWilayah],
            'kegiatan' => ['total' => number_format($totalGiat)],
            'ranking_chart' => ['labels' => array_keys($chartRanking), 'data' => array_values($chartRanking)]
        ]);
    }

    private function getP2MChartData($request, $year, $satkerId) {
        $type = $request->input('type', 'sosialisasi'); 
        $months = range(1, 12);
        
        $activityCount = []; 
        $impactValue = [];   
        $positiveValue = []; 
        
        $dipa = []; $nonDipa = [];

        $config = $this->getTableConfig($type); 
        $tableName = $config['table'];
        $dateCol   = $config['date_col'];
        $valCol    = $config['val_col'];

        $colAnggaran = Schema::hasColumn($tableName, 'anggaran_pembentukan') ? 'anggaran_pembentukan' : 'anggaran_pelaksanaan';
        $hasAnggaran = Schema::hasColumn($tableName, $colAnggaran);
        $hasSasaran  = Schema::hasColumn($tableName, 'sasaran_kegiatan');

        foreach ($months as $m) {
            $q = DB::table($tableName)->whereYear($dateCol, $year)->whereMonth($dateCol, $m);
            if ($satkerId) $q->where('satuan_kerja_id', $satkerId);

            $activityCount[] = $q->count(); 
            
            if ($valCol && Schema::hasColumn($tableName, $valCol)) {
                $impactValue[] = $q->sum($valCol);
            } else {
                $impactValue[] = 0;
            }

            if ($type === 'tes_urine') {
                $positiveValue[] = $q->sum('jumlah_positif');
            }

            if ($hasAnggaran) {
                $qA = clone $q; $qB = clone $q;
                $dipa[]    = $qA->where($colAnggaran, 'DIPA')->count(); 
                $nonDipa[] = $qB->where($colAnggaran, 'NON DIPA')->count();
            } else {
                $dipa[] = 0; $nonDipa[] = 0;
            }
        }

        $sasaranSeries = [];
        if ($hasSasaran) {
            $sasaranMap = [];
            if ($type === 'tes_urine') {
                $sasaranMap = [
                    'Instansi Pemerintah' => ['instansi pemerintah'],
                    'Lingk. Pendidikan'   => ['lingkungan pendidikan'],
                    'Pekerja Swasta'      => ['pekerja swasta'],
                    'Lingk. Masyarakat'   => ['lingkungan masyarakat']
                ];
            } elseif ($type === 'sosialisasi' || $type === 'lingkungan_bersinar') {
                $sasaranMap = [
                    'Lingk. Pendidikan' => ['lingkungan pendidikan'],
                    'Lingk. Kerja'      => ['lingkungan kerja'],
                    'Lingk. Swasta'     => ['lingkungan swasta'], 
                    'Lingk. Masyarakat' => ['lingkungan masyarakat']
                ];
            } 
            
            elseif ($type === 'pelatihan' || $type === 'keluarga') {
                $sasaranMap = [
                    'Lingk. Pendidikan' => ['lingkungan pendidikan'],
                    'Lingk. Pemerintah' => ['lingkungan pemerintah'],
                    'Lingk. Swasta'     => ['lingkungan swasta'], 
                    'Lingk. Masyarakat' => ['lingkungan masyarakat']
                ];
            } 
            else {
                $sasaranMap = [
                    'Pendidikan' => ['lingkungan pendidikan'],
                    'Kerja/Swasta' => ['lingkungan kerja', 'instansi pemerintah', 'pekerja swasta', 'lingkungan swasta'],
                    'Masyarakat' => ['lingkungan masyarakat'],
                ];
            }

            foreach ($sasaranMap as $label => $values) {
                $monthlyData = [];
                foreach ($months as $m) {
                    $q = DB::table($tableName)->whereYear($dateCol, $year)->whereMonth($dateCol, $m);
                    if ($satkerId) $q->where('satuan_kerja_id', $satkerId);
                    $monthlyData[] = $q->whereIn('sasaran_kegiatan', $values)->count();
                }
                $sasaranSeries[] = ['name' => $label, 'data' => $monthlyData];
            }
        }

        return response()->json([
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            'config' => [
                'label_unit' => $config['unit_label'],
                'has_sasaran' => $hasSasaran,
                'has_positif' => ($type === 'tes_urine')
            ],
            'tren' => [
                'kegiatan' => $activityCount,
                'dampak'    => $impactValue,
                'positif'  => $positiveValue
            ],
            'anggaran' => [
                'dipa' => $dipa,
                'non_dipa' => $nonDipa
            ],
            'sasaran' => $sasaranSeries
        ]);
    }

    private function getTableConfig($type) {
        switch ($type) {
            case 'sosialisasi': 
                return ['table' => 'p2m_sosialisasi', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'jumlah_peserta', 'unit_label' => 'Peserta (Orang)'];
            case 'tes_urine':   
                return ['table' => 'p2m_tes_urine', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'jumlah_peserta', 'unit_label' => 'Peserta (Orang)'];
            case 'upacara':     
                return ['table' => 'p2m_upacara', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'jumlah_peserta_upacara', 'unit_label' => 'Peserta (Orang)'];
            case 'cfd':         
                return ['table' => 'p2m_cfd', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'jumlah_peserta', 'unit_label' => 'Peserta (Orang)'];
            case 'safari':      
                return ['table' => 'p2m_safari_religi', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'jumlah_masyarakat', 'unit_label' => 'Peserta (Orang)'];
            case 'media_elektronik':     
                return ['table' => 'p2m_elektronik', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'durasi_pelaksanaan', 'unit_label' => 'Durasi (Hari)'];
            case 'media_non_elektronik': 
                return ['table' => 'p2m_non_elektronik', 'date_col' => 'tanggal_mulai_pelaksanaan', 'val_col' => 'durasi_pelaksanaan', 'unit_label' => 'Durasi (Hari)'];
            case 'media_online':         
                return ['table' => 'p2m_online', 'date_col' => 'tanggal_mulai_pelaksanaan', 'val_col' => 'durasi_pelaksanaan', 'unit_label' => 'Durasi (Hari)'];
            case 'kie': 
                return ['table' => 'p2m_kie', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => null, 'unit_label' => '-'];
            case 'desa_bersinar':        
                return ['table' => 'p2m_desa_bersinar', 'date_col' => 'tanggal_pencanangan', 'val_col' => null, 'unit_label' => '-'];
            case 'lingkungan_bersinar': 
                return ['table' => 'p2m_lingkungan_bersinar', 'date_col' => 'tanggal_pencanangan', 'val_col' => 'jumlah_penggiat_p4gn', 'unit_label' => 'Penggiat (Orang)'];
            case 'pelatihan': 
                return ['table' => 'p2m_pelatihan', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'jumlah_peserta', 'unit_label' => 'Peserta (Orang)'];
            case 'keluarga': 
                return ['table' => 'p2m_keluarga', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'jumlah_peserta', 'unit_label' => 'Peserta (Orang)'];
            default: 
                return ['table' => 'p2m_sosialisasi', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'jumlah_peserta', 'unit_label' => 'Peserta'];
        }
    }

    private function getSatkerId($request) {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return ($user->hasRole('admin')) ? $request->input('satker_id') : $user->satuan_kerja_id;
    }
}