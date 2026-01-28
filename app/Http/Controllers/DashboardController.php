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

    // --- UPDATE FUNGSI INI ---
    public function getGlobalData(Request $request) {
        $startYear = $request->input('start_year', date('Y'));
        $endYear   = $request->input('end_year', date('Y'));
        $satkerId  = $this->getSatkerId($request);
        $scope     = $request->input('scope', 'p2m');

        if ($scope === 'rehab') return $this->getGlobalRehab($startYear, $endYear, $satkerId);
        if ($scope === 'berantas') {
            return $this->getGlobalBerantas($startYear, $endYear, $satkerId);
        }
        
        return $this->getGlobalP2M($startYear, $endYear, $satkerId);
    }

    // =========================================================================
    // API CHART DATA
    // =========================================================================
    public function getChartData(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $satkerId = $this->getSatkerId($request);

        $scope = $request->input('scope', 'p2m');

        // --- SCOPE REHAB ---
        if ($scope === 'rehab') {
            $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            
            // Inisialisasi array data
            $tren = [
                'rj' => ['t' => array_fill(0, 12, 0), 'r' => array_fill(0, 12, 0)],
                'pr' => ['t' => array_fill(0, 12, 0), 'r' => array_fill(0, 12, 0)],
                'sk' => ['t' => array_fill(0, 12, 0), 'r' => array_fill(0, 12, 0)]
            ];

            // Ambil data dalam satu query untuk efisiensi
            $data = DB::table('rehab_laporan_bulanan')
                ->whereYear('periode', $year)
                ->when($satkerId, fn($q) => $q->where('satuan_kerja_id', $satkerId))
                ->selectRaw('
                    MONTH(periode) as bulan,
                    SUM(target_rawat_jalan) as t_rj, SUM(realisasi_rawat_jalan) as r_rj,
                    SUM(target_pasca_rehab) as t_pr, SUM(realisasi_pasca_rehab) as r_pr,
                    SUM(target_skhpn) as t_sk, SUM(realisasi_skhpn) as r_sk
                ')
                ->groupBy('bulan')
                ->get();

            // Mapping data ke array bulanan (index 0 = Januari)
            foreach ($data as $d) {
                $idx = $d->bulan - 1; 
                $tren['rj']['t'][$idx] = (int)$d->t_rj; $tren['rj']['r'][$idx] = (int)$d->r_rj;
                $tren['pr']['t'][$idx] = (int)$d->t_pr; $tren['pr']['r'][$idx] = (int)$d->r_pr;
                $tren['sk']['t'][$idx] = (int)$d->t_sk; $tren['sk']['r'][$idx] = (int)$d->r_sk;
            }

            // Hitung Summary Tahunan untuk Badge
            $calcPct = fn($r, $t) => $t > 0 ? round(($r / $t) * 100, 1) : 0;
            $sum = fn($arr) => array_sum($arr);

            return response()->json([
                'labels' => $labels,
                'tren' => $tren,
                'summary' => [
                    'rj' => ['t' => number_format($sum($tren['rj']['t'])), 'r' => number_format($sum($tren['rj']['r'])), 'p' => $calcPct($sum($tren['rj']['r']), $sum($tren['rj']['t']))],
                    'pr' => ['t' => number_format($sum($tren['pr']['t'])), 'r' => number_format($sum($tren['pr']['r'])), 'p' => $calcPct($sum($tren['pr']['r']), $sum($tren['pr']['t']))],
                    'sk' => ['t' => number_format($sum($tren['sk']['t'])), 'r' => number_format($sum($tren['sk']['r'])), 'p' => $calcPct($sum($tren['sk']['r']), $sum($tren['sk']['t']))],
                ]
            ]);
        }

        if ($scope === 'berantas') {
            $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            $sqlGram = "SUM(CASE WHEN satuan_narkotika = 'Kg' THEN kuantitas * 1000 WHEN satuan_narkotika = 'Ton' THEN kuantitas * 1000000 ELSE kuantitas END)";

            $lknData = ['kasus' => [], 'tersangka' => [], 'item' => [], 'berat' => []];
            $tatData = ['kasus' => [], 'tersangka' => [], 'item' => [], 'berat' => []];
            $bbData  = ['reg' => [], 'tangkap' => [], 'temuan' => [], 'item' => [], 'berat' => []];

            for ($m = 1; $m <= 12; $m++) {
                // 1. DATA LKN BULANAN
                $lknIds = DB::table('berantas_ungkap_kasus')->whereYear('tanggal_kejadian', $year)->whereMonth('tanggal_kejadian', $m)->when($satkerId, fn($q) => $q->where('satuan_kerja_id', $satkerId))->pluck('id');
                $lknData['kasus'][] = $lknIds->count();
                $lknData['tersangka'][] = DB::table('berantas_ungkap_tersangka')->whereIn('berantas_ungkap_kasus_id', $lknIds)->count();
                $lknData['item'][] = DB::table('berantas_ungkap_barang_bukti')->whereIn('berantas_ungkap_kasus_id', $lknIds)->where('kategori', 'Narkotika')->count();
                $lknData['berat'][] = round(DB::table('berantas_ungkap_barang_bukti')->whereIn('berantas_ungkap_kasus_id', $lknIds)->where('kategori', 'Narkotika')->selectRaw($sqlGram." as t")->value('t') ?? 0, 2);

                // 2. DATA TAT BULANAN
                $tatIds = DB::table('berantas_tat')->whereYear('tanggal_pelaksanaan', $year)->whereMonth('tanggal_pelaksanaan', $m)->when($satkerId, fn($q) => $q->where('satuan_kerja_id', $satkerId))->pluck('id');
                $tatData['kasus'][] = $tatIds->count();
                $tatData['tersangka'][] = DB::table('berantas_tat_tersangka')->whereIn('berantas_tat_id', $tatIds)->count();
                $tatData['item'][] = DB::table('berantas_tat_barang_bukti')->whereIn('berantas_tat_id', $tatIds)->where('kategori', 'Narkotika')->count();
                $tatData['berat'][] = round(DB::table('berantas_tat_barang_bukti')->whereIn('berantas_tat_id', $tatIds)->where('kategori', 'Narkotika')->sum('kuantitas') ?? 0, 2);

                // 3. DATA REGISTER BB BULANAN
                $regIds = DB::table('berantas_register_barang_bukti')->whereYear('tanggal_perolehan', $year)->whereMonth('tanggal_perolehan', $m)->when($satkerId, fn($q) => $q->where('satuan_kerja_id', $satkerId))->pluck('id');
                $bbData['reg'][] = $regIds->count();
                $bbData['tangkap'][] = DB::table('berantas_register_barang_bukti_items')->whereIn('register_barang_bukti_id', $regIds)->where('kategori', 'Narkotika')->where('sumber_perolehan', 'Hasil Tangkap')->count();
                $bbData['temuan'][] = DB::table('berantas_register_barang_bukti_items')->whereIn('register_barang_bukti_id', $regIds)->where('kategori', 'Narkotika')->where('sumber_perolehan', 'Temuan')->count();
                $bbData['item'][] = DB::table('berantas_register_barang_bukti_items')->whereIn('register_barang_bukti_id', $regIds)->where('kategori', 'Narkotika')->count();
                $bbData['berat'][] = round(DB::table('berantas_register_barang_bukti_items')->whereIn('register_barang_bukti_id', $regIds)->where('kategori', 'Narkotika')->selectRaw($sqlGram." as t")->value('t') ?? 0, 2);
            }

            // --- DATA SUMMARY TAHUNAN UNTUK BADGES ---
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

        $type = $request->input('type', 'sosialisasi'); 

        $months = range(1, 12);
        
        // Output Data
        $activityCount = []; // Batang (Jumlah Kegiatan)
        $impactValue = [];   // Garis (Peserta/Durasi)
        $positiveValue = []; // Garis Merah (Khusus Tes Urine)
        
        $dipa = []; $nonDipa = [];

        // 1. Ambil Konfigurasi Tabel
        $config = $this->getTableConfig($type); 
        $tableName = $config['table'];
        $dateCol   = $config['date_col'];
        $valCol    = $config['val_col'];

        // 2. Cek Kolom Database
        $colAnggaran = Schema::hasColumn($tableName, 'anggaran_pembentukan') ? 'anggaran_pembentukan' : 'anggaran_pelaksanaan';
        $hasAnggaran = Schema::hasColumn($tableName, $colAnggaran);
        $hasSasaran  = Schema::hasColumn($tableName, 'sasaran_kegiatan');

        // 3. Loop Data Bulanan
        foreach ($months as $m) {
            $q = DB::table($tableName)->whereYear($dateCol, $year)->whereMonth($dateCol, $m);
            if ($satkerId) $q->where('satuan_kerja_id', $satkerId);

            // A. DATA CHART KIRI
            $activityCount[] = $q->count(); 
            
            if ($valCol && Schema::hasColumn($tableName, $valCol)) {
                $impactValue[] = $q->sum($valCol);
            } else {
                $impactValue[] = 0;
            }

            if ($type === 'tes_urine') {
                $positiveValue[] = $q->sum('jumlah_positif');
            }

            // B. DATA CHART KANAN (ANGGARAN)
            if ($hasAnggaran) {
                $qA = clone $q; $qB = clone $q;
                $dipa[]    = $qA->where($colAnggaran, 'DIPA')->count(); 
                $nonDipa[] = $qB->where($colAnggaran, 'NON DIPA')->count();
            } else {
                $dipa[] = 0; $nonDipa[] = 0;
            }
        }

        // C. DATA CHART KANAN (SASARAN)
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
            } else {
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
                'dampak'   => $impactValue,
                'positif'  => $positiveValue
            ],
            'anggaran' => [
                'dipa' => $dipa,
                'non_dipa' => $nonDipa
            ],
            'sasaran' => $sasaranSeries
        ]);
    }


    private function getBerantasChartData($year, $satkerId) {
        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $lknW = []; $tatW = []; $items = [];
        $sqlW = "SUM(CASE WHEN satuan_narkotika = 'Kg' THEN kuantitas * 1000 WHEN satuan_narkotika = 'Ton' THEN kuantitas * 1000000 ELSE kuantitas END)";

        for ($m = 1; $m <= 12; $m++) {
            $lknIds = DB::table('berantas_ungkap_kasus')->whereYear('tanggal_kejadian',$year)->whereMonth('tanggal_kejadian',$m)->when($satkerId, fn($q)=>$q->where('satuan_kerja_id',$satkerId))->pluck('id');
            $tatIds = DB::table('berantas_tat')->whereYear('tanggal_pelaksanaan',$year)->whereMonth('tanggal_pelaksanaan',$m)->when($satkerId, fn($q)=>$q->where('satuan_kerja_id',$satkerId))->pluck('id');

            $lknW[] = round(DB::table('berantas_ungkap_barang_bukti')->whereIn('berantas_ungkap_kasus_id',$lknIds)->where('kategori','Narkotika')->selectRaw($sqlW." as t")->value('t') ?? 0, 2);
            $tatW[] = round(DB::table('berantas_tat_barang_bukti')->whereIn('berantas_tat_id',$tatIds)->where('kategori','Narkotika')->sum('kuantitas') ?? 0, 2);
            $items[] = DB::table('berantas_ungkap_barang_bukti')->whereIn('berantas_ungkap_kasus_id',$lknIds)->where('kategori','Narkotika')->count() + 
                    DB::table('berantas_tat_barang_bukti')->whereIn('berantas_tat_id',$tatIds)->where('kategori','Narkotika')->count();
        }
        return response()->json(['labels' => $labels, 'tren' => ['lkn_gram' => $lknW, 'tat_gram' => $tatW, 'total_item_count' => $items]]);
    }

    // Helper: Konfigurasi Mapping Tabel
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
                // PERBAIKAN LABEL: Peserta (bukan Jemaah)
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

            default: 
                return ['table' => 'p2m_sosialisasi', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'jumlah_peserta', 'unit_label' => 'Peserta'];
        }
    }

    private function getGlobalRehab($yStart, $yEnd, $satkerId) {
        $data = DB::table('rehab_laporan_bulanan')->whereYear('periode', '>=', $yStart)->whereYear('periode', '<=', $yEnd)
            ->when($satkerId, fn($q) => $q->where('satuan_kerja_id', $satkerId))
            ->selectRaw('SUM(target_rawat_jalan) as t_rj, SUM(realisasi_rawat_jalan) as r_rj, SUM(target_pasca_rehab) as t_pr, SUM(realisasi_pasca_rehab) as r_pr, SUM(target_skhpn) as t_sk, SUM(realisasi_skhpn) as r_sk')
            ->first();

        $calcPct = fn($r, $t) => $t > 0 ? round(($r / $t) * 100, 1) : 0;
        return response()->json([
            'rj' => ['target' => number_format($data->t_rj ?? 0), 'realisasi' => number_format($data->r_rj ?? 0), 'pct' => $calcPct($data->r_rj, $data->t_rj)],
            'pr' => ['target' => number_format($data->t_pr ?? 0), 'realisasi' => number_format($data->r_pr ?? 0), 'pct' => $calcPct($data->r_pr, $data->t_pr)],
            'sk' => ['target' => number_format($data->t_sk ?? 0), 'realisasi' => number_format($data->r_sk ?? 0), 'pct' => $calcPct($data->r_sk, $data->t_sk)],
        ]);
    }

    // --- TAMBAHKAN FUNGSI HELPER BARU DI PALING BAWAH CLASS ---
    private function getGlobalBerantas($yStart, $yEnd, $satkerId) {
        $filter = function($q, $col) use ($yStart, $yEnd, $satkerId) {
            $q->whereYear($col, '>=', $yStart)->whereYear($col, '<=', $yEnd);
            if ($satkerId) $q->where('satuan_kerja_id', $satkerId);
            return $q;
        };

        // Mengambil ID Parent berdasarkan filter waktu dan satker
        $lknIds = $filter(DB::table('berantas_ungkap_kasus'), 'tanggal_kejadian')->pluck('id');
        $tatIds = $filter(DB::table('berantas_tat'), 'tanggal_pelaksanaan')->pluck('id');
        $regIds = $filter(DB::table('berantas_register_barang_bukti'), 'tanggal_perolehan')->pluck('id');

        // SQL Helper untuk konversi berat (Kg/Ton ke Gram) secara presisi
        $sqlW = "SUM(CASE WHEN satuan_narkotika = 'Kg' THEN kuantitas * 1000 WHEN satuan_narkotika = 'Ton' THEN kuantitas * 1000000 ELSE kuantitas END)";

        // Query dasar untuk kategori Narkotika pada masing-masing pilar
        $qLkn = DB::table('berantas_ungkap_barang_bukti')->whereIn('berantas_ungkap_kasus_id', $lknIds)->where('kategori', 'Narkotika');
        $qTat = DB::table('berantas_tat_barang_bukti')->whereIn('berantas_tat_id', $tatIds)->where('kategori', 'Narkotika');
        
        // Query dasar untuk Register Barang Bukti (Pilar ke-3)
        $qReg = DB::table('berantas_register_barang_bukti_items')->whereIn('register_barang_bukti_id', $regIds)->where('kategori', 'Narkotika');

        // Kloning query untuk mendapatkan data spesifik Hasil Tangkap dan Temuan
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
                
                // Data spesifik Hasil Tangkap
                'tangkap_berat' => number_format((clone $qTangkap)->selectRaw($sqlW." as t")->value('t') ?? 0, 2, ',', '.') . ' g',
                'tangkap_item' => number_format((clone $qTangkap)->count()) . ' Item',
                
                // Data spesifik Temuan
                'temuan_berat' => number_format((clone $qTemuan)->selectRaw($sqlW." as t")->value('t') ?? 0, 2, ',', '.') . ' g',
                'temuan_item' => number_format((clone $qTemuan)->count()) . ' Item'
            ]
        ]);
    }

    // --- LOGIKA KARTU ATAS (GET GLOBAL P2M) - TETAP SAMA ---
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

    private function getSatkerId($request) {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return ($user->hasRole('admin')) ? $request->input('satker_id') : $user->satuan_kerja_id;
    }
}