<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\SatuanKerja;

class DashboardP2MController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $years = range(date('Y'), date('Y') - 4);
        
        // Cek apakah user berhak melihat tab menu bidang lain
        $showTabs = in_array($user->role, ['admin', 'admin_satker', 'operator_satker']);
        
        // Ambil daftar Satker untuk label grafik komparasi (Khusus Super Admin)
        $satkers = ($user->role === 'admin') ? SatuanKerja::orderBy('satuan_kerja', 'asc')->get() : [];

        return view('dashboard.p2m.index', compact('years', 'showTabs', 'satkers'));
    }

    // --- API 1: KARTU GLOBAL & RANKING TAHUNAN ---
    public function getGlobalData(Request $request) 
    {
        $startYear = $request->input('start_year', date('Y'));
        $endYear   = $request->input('end_year', date('Y'));
        
        /** @var \App\Models\User $user */
        $user      = Auth::user();
        $satkerId  = ($user->role === 'admin') ? $request->input('satker_id') : $user->pegawai?->satuan_kerja_id;

        $count = function($table, $dateCol) use ($startYear, $endYear, $satkerId) {
            $q = DB::table($table)->whereYear($dateCol, '>=', $startYear)->whereYear($dateCol, '<=', $endYear);
            if ($satkerId) $q->where('satuan_kerja_id', $satkerId);
            return $q->count();
        };

        $sum = function($table, $colSum, $dateCol) use ($startYear, $endYear, $satkerId) {
            $q = DB::table($table)->whereYear($dateCol, '>=', $startYear)->whereYear($dateCol, '<=', $endYear);
            if ($satkerId) $q->where('satuan_kerja_id', $satkerId);
            return $q->sum($colSum);
        };

        // 1. DATA ORANG TERLAYANI
        $listOrang = [
            'Informasi Edukasi' => $sum('p2m_informasi_edukasi', 'jumlah_peserta', 'tanggal_pelaksanaan'),
            'Tes Urine'         => [
                'val' => $sum('p2m_tes_urine', 'jumlah_peserta', 'tanggal_pelaksanaan'), 
                'positif' => $sum('p2m_tes_urine', 'jumlah_positif', 'tanggal_pelaksanaan'), 
                'is_tes_urine' => true
            ],
            'Asistensi Relawan' => $sum('p2m_asistensi_relawan', 'jumlah_peserta', 'tanggal_pelaksanaan'),
            'Pelatihan'         => $sum('p2m_pelatihan', 'jumlah_peserta', 'tanggal_pelaksanaan'),
            'Ketahanan Keluarga'=> $sum('p2m_keluarga', 'jumlah_peserta', 'tanggal_pelaksanaan'),
            'Monev'             => $sum('p2m_monev', 'jumlah_peserta', 'tanggal_pelaksanaan'),
            'Pemetaan SDM/SDA'  => $sum('p2m_pemetaan_sdm_sda', 'jumlah_peserta', 'tanggal_pelaksanaan'),
            'IKAN'              => $sum('p2m_ikan', 'jumlah_peserta', 'tanggal_pelaksanaan'),
        ];
        $totalOrang = 0; 
        foreach($listOrang as $v) $totalOrang += (is_array($v) ? $v['val'] : $v);

        // 2. DATA MEDIA
        $listMedia = [
            'Elektronik'     => [
                'freq' => $count('p2m_elektronik', 'tanggal_pelaksanaan'), 
                'durasi' => $sum('p2m_elektronik', 'durasi_pelaksanaan', 'tanggal_pelaksanaan')
            ],
            'Non-Elektronik' => [
                'freq' => $count('p2m_non_elektronik', 'tanggal_mulai_pelaksanaan'), 
                'durasi' => $sum('p2m_non_elektronik', 'durasi_pelaksanaan', 'tanggal_mulai_pelaksanaan')
            ],
            'Online'         => [
                'freq' => $count('p2m_online', 'tanggal_mulai_pelaksanaan'), 
                'durasi' => $sum('p2m_online', 'durasi_pelaksanaan', 'tanggal_mulai_pelaksanaan')
            ],
        ];
        $totalMediaFreq = 0; $totalMediaDurasi = 0; 
        foreach($listMedia as $m) { $totalMediaFreq += $m['freq']; $totalMediaDurasi += $m['durasi']; }

        // 3. DATA WILAYAH
        $listWilayah = [
            'Desa/Kel. Bersinar' => $count('p2m_desa_kelurahan_bersinar', 'tanggal_pencanangan')
        ];
        $totalWilayah = array_sum($listWilayah);

        // 4. RANKING SEMUA KEGIATAN
        $allActivities = [
            'Info Edukasi'      => $count('p2m_informasi_edukasi', 'tanggal_pelaksanaan'),
            'Tes Urine'         => $count('p2m_tes_urine', 'tanggal_pelaksanaan'),
            'Media Elektronik'  => $listMedia['Elektronik']['freq'],
            'Media Non-Elek'    => $listMedia['Non-Elektronik']['freq'],
            'Media Online'      => $listMedia['Online']['freq'],
            'Desa Bersinar'     => $listWilayah['Desa/Kel. Bersinar'],
            'Asistensi Relawan' => $count('p2m_asistensi_relawan', 'tanggal_pelaksanaan'),
            'Pelatihan'         => $count('p2m_pelatihan', 'tanggal_pelaksanaan'),
            'Keluarga'          => $count('p2m_keluarga', 'tanggal_pelaksanaan'),
            'Monev'             => $count('p2m_monev', 'tanggal_pelaksanaan'),
            'Pemetaan SDM/SDA'  => $count('p2m_pemetaan_sdm_sda', 'tanggal_pelaksanaan'),
            'IKAN'              => $count('p2m_ikan', 'tanggal_pelaksanaan'),
        ];
        $totalGiat = array_sum($allActivities);
        arsort($allActivities);

        return response()->json([
            'orang' => ['total' => $totalOrang, 'list' => $listOrang],
            'media' => ['total_freq' => $totalMediaFreq, 'total_durasi' => $totalMediaDurasi, 'list' => $listMedia],
            'wilayah' => ['total' => $totalWilayah, 'list' => $listWilayah],
            'kegiatan' => ['total' => $totalGiat],
            'ranking_chart' => ['labels' => array_keys($allActivities), 'data' => array_values($allActivities)]
        ]);
    }

    // --- API 2: DATA GRAFIK ANALISIS (TREN & KOMPOSISI) ---
    public function getChartData(Request $request) 
    {
        $year      = $request->input('year', date('Y'));
        $type      = $request->input('type', 'informasi_edukasi'); 
        $trendMode = $request->input('trend_mode', 'monthly'); // 'monthly' | 'yearly'
        $barMonth  = $request->input('bar_month', 'all');      // 'all' | 1 | 2 ... 12
        
        /** @var \App\Models\User $user */
        $user      = Auth::user();
        $isAdmin   = ($user->role === 'admin');
        $mySatker  = $user->pegawai?->satuan_kerja_id;

        $config    = $this->getTableConfig($type); 
        $table     = $config['table'];
        $dateCol   = $config['date_col'];
        $valCol    = $config['val_col'];
        
        $hasAnggaran = Schema::hasColumn($table, 'anggaran_pelaksanaan') || Schema::hasColumn($table, 'anggaran_pembentukan');
        $colAnggaran = Schema::hasColumn($table, 'anggaran_pembentukan') ? 'anggaran_pembentukan' : 'anggaran_pelaksanaan';
        $hasSasaran  = Schema::hasColumn($table, 'sasaran_kegiatan');

        $sasaranCategories = ['lingkungan pendidikan', 'lingkungan pemerintah', 'lingkungan masyarakat', 'lingkungan swasta'];

        // Siapkan Label X-Axis untuk Line Chart (Tren)
        $trendLabels = [];
        $timePoints = [];
        if ($trendMode === 'monthly') {
            $trendLabels = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            $timePoints  = range(1, 12);
        } else {
            $trendLabels = range($year - 4, $year);
            $timePoints  = range($year - 4, $year);
        }

        // Siapkan Fungsi Builder Query Waktu
        $applyTimeScope = function($q, $val, $mode) use ($dateCol, $year) {
            if ($mode === 'monthly') {
                return $q->whereYear($dateCol, $year)->whereMonth($dateCol, $val);
            } else {
                return $q->whereYear($dateCol, $val);
            }
        };

        if ($isAdmin) {
            // ==========================================
            // LOGIKA SUPER ADMIN (MULTI SATKER)
            // ==========================================
            $satkers = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
            $satkerNames = $satkers->pluck('satuan_kerja')->toArray();
            
            // Variabel Chart Line (Tren)
            $chartKegiatan = []; $chartPeserta = []; $chartPositif = [];
            
            // Variabel Chart Bar (Anggaran & Sasaran)
            $barAnggaranDipa = []; $barAnggaranNon = [];
            $barSasaran = array_fill_keys($sasaranCategories, []);

            foreach ($satkers as $satker) {
                // 1. Ambil Data Tren (Line Chart)
                $dataGiat = []; $dataPeserta = []; $dataPositif = [];
                foreach ($timePoints as $timeVal) {
                    $q = DB::table($table)->where('satuan_kerja_id', $satker->id);
                    $q = $applyTimeScope($q, $timeVal, $trendMode);
                    
                    $dataGiat[]    = $q->count();
                    $dataPeserta[] = $valCol ? (clone $q)->sum($valCol) : 0;
                    $dataPositif[] = ($type === 'tes_urine') ? (clone $q)->sum('jumlah_positif') : 0;
                }
                $chartKegiatan[] = ['name' => $satker->satuan_kerja, 'data' => $dataGiat];
                $chartPeserta[]  = ['name' => $satker->satuan_kerja, 'data' => $dataPeserta];
                if ($type === 'tes_urine') $chartPositif[] = ['name' => $satker->satuan_kerja, 'data' => $dataPositif];

                // 2. Ambil Data Komposisi (Bar Chart)
                $qBar = DB::table($table)->whereYear($dateCol, $year)->where('satuan_kerja_id', $satker->id);
                if ($barMonth !== 'all') {
                    $qBar->whereMonth($dateCol, $barMonth);
                }

                if ($hasAnggaran) {
                    $barAnggaranDipa[] = (clone $qBar)->where($colAnggaran, 'DIPA')->count();
                    $barAnggaranNon[]  = (clone $qBar)->where($colAnggaran, 'NON DIPA')->count();
                }

                if ($hasSasaran) {
                    foreach ($sasaranCategories as $sasaran) {
                        $barSasaran[$sasaran][] = (clone $qBar)->where('sasaran_kegiatan', $sasaran)->count();
                    }
                }
            }

            $sasaranSeries = [];
            foreach ($barSasaran as $label => $dataArr) {
                $sasaranSeries[] = ['name' => ucwords(str_replace('lingkungan ', '', $label)), 'data' => $dataArr];
            }

            return response()->json([
                'is_admin'     => true,
                'config'       => ['unit' => $config['unit_label'], 'has_anggaran' => $hasAnggaran, 'has_sasaran' => $hasSasaran, 'has_positif' => ($type === 'tes_urine')],
                'trend_labels' => $trendLabels,
                'bar_labels'   => $satkerNames,
                'tren' => [
                    'kegiatan' => $chartKegiatan,
                    'peserta'  => $chartPeserta,
                    'positif'  => $chartPositif
                ],
                'komposisi' => [
                    'anggaran' => [
                        ['name' => 'DIPA', 'data' => $barAnggaranDipa],
                        ['name' => 'NON DIPA', 'data' => $barAnggaranNon]
                    ],
                    'sasaran' => $sasaranSeries
                ]
            ]);

        } else {
            // ==========================================
            // LOGIKA NON-ADMIN (SATKER TUNGGAL)
            // ==========================================
            // Variabel Chart Line (Tren)
            $dataGiat = []; $dataPeserta = []; $dataPositif = [];
            
            // 1. Ambil Data Tren (Line Chart)
            foreach ($timePoints as $timeVal) {
                $q = DB::table($table);
                if ($mySatker) $q->where('satuan_kerja_id', $mySatker);
                $q = $applyTimeScope($q, $timeVal, $trendMode);

                $dataGiat[]    = $q->count();
                $dataPeserta[] = $valCol ? (clone $q)->sum($valCol) : 0;
                $dataPositif[] = ($type === 'tes_urine') ? (clone $q)->sum('jumlah_positif') : 0;
            }

            // 2. Ambil Data Komposisi (Bar Chart)
            $barLabels = [];
            $barAnggaranDipa = []; $barAnggaranNon = [];
            $barSasaran = array_fill_keys($sasaranCategories, []);

            // Jika "all", sumbu X adalah Bulan (1-12). Jika pilih bulan spesifik, sumbu X adalah 1 bar bulan tsb.
            $barTimePoints = ($barMonth === 'all') ? range(1, 12) : [(int)$barMonth];
            $monthNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

            foreach ($barTimePoints as $m) {
                $barLabels[] = $monthNames[$m - 1];
                $qBar = DB::table($table)->whereYear($dateCol, $year)->whereMonth($dateCol, $m);
                if ($mySatker) $qBar->where('satuan_kerja_id', $mySatker);

                if ($hasAnggaran) {
                    $barAnggaranDipa[] = (clone $qBar)->where($colAnggaran, 'DIPA')->count();
                    $barAnggaranNon[]  = (clone $qBar)->where($colAnggaran, 'NON DIPA')->count();
                }

                if ($hasSasaran) {
                    foreach ($sasaranCategories as $sasaran) {
                        $barSasaran[$sasaran][] = (clone $qBar)->where('sasaran_kegiatan', $sasaran)->count();
                    }
                }
            }

            $sasaranSeries = [];
            foreach ($barSasaran as $label => $dataArr) {
                $sasaranSeries[] = ['name' => ucwords(str_replace('lingkungan ', '', $label)), 'data' => $dataArr];
            }

            return response()->json([
                'is_admin'     => false,
                'config'       => ['unit' => $config['unit_label'], 'has_anggaran' => $hasAnggaran, 'has_sasaran' => $hasSasaran, 'has_positif' => ($type === 'tes_urine')],
                'trend_labels' => $trendLabels,
                'bar_labels'   => $barLabels,
                'tren' => [
                    'kegiatan' => [['name' => 'Jumlah Giat', 'data' => $dataGiat]],
                    'peserta'  => [['name' => 'Jumlah ' . $config['unit_label'], 'data' => $dataPeserta]],
                    'positif'  => [['name' => 'Indikasi Positif', 'data' => $dataPositif]]
                ],
                'komposisi' => [
                    'anggaran' => [
                        ['name' => 'DIPA', 'data' => $barAnggaranDipa],
                        ['name' => 'NON DIPA', 'data' => $barAnggaranNon]
                    ],
                    'sasaran' => $sasaranSeries
                ]
            ]);
        }
    }

    private function getTableConfig($type) {
        $map = [
            'informasi_edukasi'   => ['table' => 'p2m_informasi_edukasi', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'jumlah_peserta', 'unit_label' => 'Peserta'],
            'tes_urine'           => ['table' => 'p2m_tes_urine', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'jumlah_peserta', 'unit_label' => 'Peserta'],
            'media_elektronik'    => ['table' => 'p2m_elektronik', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'durasi_pelaksanaan', 'unit_label' => 'Hari'],
            'media_non_elektronik'=> ['table' => 'p2m_non_elektronik', 'date_col' => 'tanggal_mulai_pelaksanaan', 'val_col' => 'durasi_pelaksanaan', 'unit_label' => 'Hari'],
            'media_online'        => ['table' => 'p2m_online', 'date_col' => 'tanggal_mulai_pelaksanaan', 'val_col' => 'durasi_pelaksanaan', 'unit_label' => 'Hari'],
            'desa_bersinar'       => ['table' => 'p2m_desa_kelurahan_bersinar', 'date_col' => 'tanggal_pencanangan', 'val_col' => null, 'unit_label' => '-'],
            'asistensi'           => ['table' => 'p2m_asistensi_relawan', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'jumlah_peserta', 'unit_label' => 'Peserta'],
            'pelatihan'           => ['table' => 'p2m_pelatihan', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'jumlah_peserta', 'unit_label' => 'Peserta'],
            'keluarga'            => ['table' => 'p2m_keluarga', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'jumlah_peserta', 'unit_label' => 'Peserta'],
            'monev'               => ['table' => 'p2m_monev', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'jumlah_peserta', 'unit_label' => 'Peserta'],
            'pemetaan'            => ['table' => 'p2m_pemetaan_sdm_sda', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'jumlah_peserta', 'unit_label' => 'Peserta'],
            'ikan'                => ['table' => 'p2m_ikan', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'jumlah_peserta', 'unit_label' => 'Peserta'],
        ];
        return $map[$type] ?? $map['informasi_edukasi'];
    }
}