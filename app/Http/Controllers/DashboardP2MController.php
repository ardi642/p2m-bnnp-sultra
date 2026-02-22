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
        
        // =========================================================
        // RADAR DETEKSI TAHUN OTOMATIS (MENCARI DATA PALING LAMA)
        // =========================================================
        $minYear = (int) date('Y'); // Default batas awal adalah tahun ini
        $currentYear = (int) date('Y');

        // Daftar tabel dan kolom tanggal yang akan di-scan
        $tables = [
            'p2m_informasi_edukasi'       => 'tanggal_pelaksanaan',
            'p2m_tes_urine'               => 'tanggal_pelaksanaan',
            'p2m_elektronik'              => 'tanggal_pelaksanaan',
            'p2m_non_elektronik'          => 'tanggal_mulai_pelaksanaan',
            'p2m_online'                  => 'tanggal_mulai_pelaksanaan',
            'p2m_desa_kelurahan_bersinar' => 'tanggal_pencanangan',
            'p2m_asistensi_relawan'       => 'tanggal_pelaksanaan',
            'p2m_pelatihan'               => 'tanggal_pelaksanaan',
            'p2m_keluarga'                => 'tanggal_pelaksanaan',
            'p2m_monev'                   => 'tanggal_pelaksanaan',
            'p2m_pemetaan_sdm_sda'        => 'tanggal_pelaksanaan',
            'p2m_ikan'                    => 'tanggal_pelaksanaan',
        ];

        foreach ($tables as $table => $column) {
            // Ambil tanggal paling lawas di setiap tabel
            $oldestDate = DB::table($table)->min($column);
            if ($oldestDate) {
                $year = (int) date('Y', strtotime($oldestDate));
                // Pastikan tahun logis (> 2000) dan simpan jika lebih lama dari $minYear saat ini
                if ($year > 2000 && $year < $minYear) {
                    $minYear = $year;
                }
            }
        }

        // Buat rentang dari tahun sekarang mundur ke tahun paling lawas
        $years = range($currentYear, $minYear);
        // =========================================================
        
        $showTabs = in_array($user->role, ['admin', 'admin_satker', 'operator_satker']);
        
        $satkers = ($user->role === 'admin') 
            ? SatuanKerja::orderBy('satuan_kerja', 'asc')->get() 
            : [];

        return view('dashboard.p2m.index', compact('years', 'showTabs', 'satkers'));
    }

    public function getGlobalData(Request $request) 
    {
        $startYear = $request->input('start_year', date('Y'));
        $endYear   = $request->input('end_year', date('Y'));
        
        /** @var \App\Models\User $user */
        $user      = Auth::user();
        $satkerId  = ($user->role === 'admin') ? $request->input('satker_id') : $user->pegawai?->satuan_kerja_id;

        $count = function($table, $dateCol) use ($startYear, $endYear, $satkerId) {
            $q = DB::table($table)
                ->whereYear($dateCol, '>=', $startYear)
                ->whereYear($dateCol, '<=', $endYear);
            if ($satkerId) $q->where('satuan_kerja_id', $satkerId);
            return $q->count();
        };

        $sum = function($table, $colSum, $dateCol) use ($startYear, $endYear, $satkerId) {
            $q = DB::table($table)
                ->whereYear($dateCol, '>=', $startYear)
                ->whereYear($dateCol, '<=', $endYear);
            if ($satkerId) $q->where('satuan_kerja_id', $satkerId);
            return $q->sum($colSum);
        };

        $listOrang = [
            'Informasi Edukasi'  => $sum('p2m_informasi_edukasi', 'jumlah_peserta', 'tanggal_pelaksanaan'),
            'Tes Urine'          => [
                'val' => $sum('p2m_tes_urine', 'jumlah_peserta', 'tanggal_pelaksanaan'), 
                'positif' => $sum('p2m_tes_urine', 'jumlah_positif', 'tanggal_pelaksanaan'), 
                'is_tes_urine' => true
            ],
            'Asistensi Relawan'  => $sum('p2m_asistensi_relawan', 'jumlah_peserta', 'tanggal_pelaksanaan'),
            'Pelatihan'          => $sum('p2m_pelatihan', 'jumlah_peserta', 'tanggal_pelaksanaan'),
            'Ketahanan Keluarga' => $sum('p2m_keluarga', 'jumlah_peserta', 'tanggal_pelaksanaan'),
            'Monev'              => $sum('p2m_monev', 'jumlah_peserta', 'tanggal_pelaksanaan'),
            'Pemetaan SDM/SDA'   => $sum('p2m_pemetaan_sdm_sda', 'jumlah_peserta', 'tanggal_pelaksanaan'),
            'IKAN'               => $sum('p2m_ikan', 'jumlah_peserta', 'tanggal_pelaksanaan'),
        ];
        
        $totalOrang = 0; 
        foreach($listOrang as $v) { 
            $totalOrang += (is_array($v) ? $v['val'] : $v); 
        }

        $listMedia = [
            'Elektronik' => [
                'freq' => $count('p2m_elektronik', 'tanggal_pelaksanaan'), 
                'durasi' => $sum('p2m_elektronik', 'durasi_pelaksanaan', 'tanggal_pelaksanaan')
            ],
            'Non-Elektronik' => [
                'freq' => $count('p2m_non_elektronik', 'tanggal_mulai_pelaksanaan'), 
                'durasi' => $sum('p2m_non_elektronik', 'durasi_pelaksanaan', 'tanggal_mulai_pelaksanaan')
            ],
            'Online' => [
                'freq' => $count('p2m_online', 'tanggal_mulai_pelaksanaan'), 
                'durasi' => $sum('p2m_online', 'durasi_pelaksanaan', 'tanggal_mulai_pelaksanaan')
            ],
        ];
        
        $totalMediaFreq = 0; 
        $totalMediaDurasi = 0; 
        foreach($listMedia as $m) { 
            $totalMediaFreq += $m['freq']; 
            $totalMediaDurasi += $m['durasi']; 
        }

        $listWilayah = [
            'Desa/Kel. Bersinar' => $count('p2m_desa_kelurahan_bersinar', 'tanggal_pencanangan')
        ];
        $totalWilayah = array_sum($listWilayah);

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
        
        $totalKegiatan = array_sum($allActivities);
        arsort($allActivities);

        return response()->json([
            'orang'   => ['total' => $totalOrang, 'list' => $listOrang],
            'media'   => ['total_freq' => $totalMediaFreq, 'total_durasi' => $totalMediaDurasi, 'list' => $listMedia],
            'wilayah' => ['total' => $totalWilayah, 'list' => $listWilayah],
            'kegiatan'=> ['total' => $totalKegiatan],
            'ranking_chart' => ['labels' => array_keys($allActivities), 'data' => array_values($allActivities)]
        ]);
    }

    public function getChartData(Request $request) 
    {
        $type      = $request->input('type', 'informasi_edukasi'); 
        $mode      = $request->input('mode', 'monthly'); 
        $mYear     = $request->input('m_year', date('Y'));
        $mMonth    = $request->input('m_month', 'all');
        $yStart    = $request->input('y_start', date('Y'));
        $yEnd      = $request->input('y_end', date('Y'));
        
        /** @var \App\Models\User $user */
        $user      = Auth::user();
        $isAdmin   = ($user->role === 'admin');
        
        $selectedSatker = $request->input('satker_id');
        $mySatker       = $isAdmin ? $selectedSatker : $user->pegawai?->satuan_kerja_id;
        $isMultiSatker  = ($isAdmin && empty($selectedSatker));

        $config    = $this->getTableConfig($type); 
        $table     = $config['table'];
        $dateCol   = $config['date_col'];
        $valCol    = $config['val_col'];
        $hasAnggaran = $config['has_anggaran'];
        $colAnggaran = $config['col_anggaran'];
        $hasSasaran  = $config['has_sasaran'];
        $sasaranList = $config['sasaran_list'];

        $trendLabels = [];
        $timePoints  = [];

        if ($mode === 'monthly') {
            $trendLabels = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            $timePoints  = range(1, 12);
        } else {
            $trendLabels = range($yStart, $yEnd);
            $timePoints  = range($yStart, $yEnd);
        }

        $applyTrendTime = function($q, $val) use ($mode, $mYear, $dateCol) {
            if ($mode === 'monthly') {
                return $q->whereYear($dateCol, $mYear)->whereMonth($dateCol, $val);
            }
            return $q->whereYear($dateCol, $val);
        };

        $applyCompTime = function($q) use ($mode, $mYear, $mMonth, $yStart, $yEnd, $dateCol) {
            if ($mode === 'monthly') {
                $q = $q->whereYear($dateCol, $mYear);
                if ($mMonth !== 'all') {
                    $q = $q->whereMonth($dateCol, (int)$mMonth);
                }
                return $q;
            }
            return $q->whereYear($dateCol, '>=', $yStart)->whereYear($dateCol, '<=', $yEnd);
        };

        $chartKegiatan = []; 
        $chartPeserta  = []; 
        $chartPositif  = [];
        $barAnggaranDipa = []; 
        $barAnggaranNon  = [];
        $barSasaran = array_fill_keys($sasaranList, []);
        $compLabels = [];

        if ($isMultiSatker) {
            $satkers = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
            $compLabels = $satkers->pluck('satuan_kerja')->toArray();

            foreach ($satkers as $satker) {
                $dataGiat = []; $dataPeserta = []; $dataPositif = [];
                
                foreach ($timePoints as $timeVal) {
                    $q = DB::table($table)->where('satuan_kerja_id', $satker->id);
                    $q = $applyTrendTime($q, $timeVal);
                    
                    $dataGiat[]    = $q->count();
                    $dataPeserta[] = $valCol ? (clone $q)->sum($valCol) : 0;
                    $dataPositif[] = ($type === 'tes_urine') ? (clone $q)->sum('jumlah_positif') : 0;
                }
                $chartKegiatan[] = ['name' => $satker->satuan_kerja, 'data' => $dataGiat];
                $chartPeserta[]  = ['name' => $satker->satuan_kerja, 'data' => $dataPeserta];
                if ($type === 'tes_urine') {
                    $chartPositif[] = ['name' => $satker->satuan_kerja, 'data' => $dataPositif];
                }

                $qComp = DB::table($table)->where('satuan_kerja_id', $satker->id);
                $qComp = $applyCompTime($qComp);

                if ($hasAnggaran) {
                    $barAnggaranDipa[] = (clone $qComp)->where($colAnggaran, 'DIPA')->count();
                    $barAnggaranNon[]  = (clone $qComp)->where($colAnggaran, 'NON DIPA')->count();
                }
                if ($hasSasaran) {
                    foreach ($sasaranList as $sas) {
                        $barSasaran[$sas][] = (clone $qComp)->where('sasaran_kegiatan', $sas)->count();
                    }
                }
            }

        } else {
            $satkerName = 'Satuan Kerja';
            if ($mySatker) {
                $stk = SatuanKerja::find($mySatker);
                if ($stk) $satkerName = $stk->satuan_kerja;
            }
            $compLabels = [$satkerName];

            $dataGiat = []; $dataPeserta = []; $dataPositif = [];
            
            foreach ($timePoints as $timeVal) {
                $q = DB::table($table);
                if ($mySatker) $q->where('satuan_kerja_id', $mySatker);
                $q = $applyTrendTime($q, $timeVal);

                $dataGiat[]    = $q->count();
                $dataPeserta[] = $valCol ? (clone $q)->sum($valCol) : 0;
                $dataPositif[] = ($type === 'tes_urine') ? (clone $q)->sum('jumlah_positif') : 0;
            }
            $chartKegiatan[] = ['name' => 'Jumlah Kegiatan', 'data' => $dataGiat];
            $chartPeserta[]  = ['name' => 'Jumlah ' . $config['unit_label'], 'data' => $dataPeserta];
            if ($type === 'tes_urine') {
                $chartPositif[] = ['name' => 'Indikasi Positif', 'data' => $dataPositif];
            }

            $qComp = DB::table($table);
            if ($mySatker) $qComp->where('satuan_kerja_id', $mySatker);
            $qComp = $applyCompTime($qComp);

            if ($hasAnggaran) {
                $barAnggaranDipa[] = (clone $qComp)->where($colAnggaran, 'DIPA')->count();
                $barAnggaranNon[]  = (clone $qComp)->where($colAnggaran, 'NON DIPA')->count();
            }
            if ($hasSasaran) {
                foreach ($sasaranList as $sas) {
                    $barSasaran[$sas][] = (clone $qComp)->where('sasaran_kegiatan', $sas)->count();
                }
            }
        }

        $sasaranSeries = [];
        if ($hasSasaran) {
            foreach ($barSasaran as $label => $dataArr) {
                $sasaranSeries[] = [
                    'name' => ucwords(str_replace('lingkungan ', '', $label)), 
                    'data' => $dataArr
                ];
            }
        }

        return response()->json([
            'is_multi_satker' => $isMultiSatker,
            'config' => [
                'unit' => $config['unit_label'], 
                'has_anggaran' => $hasAnggaran, 
                'has_sasaran'  => $hasSasaran, 
                'has_positif'  => ($type === 'tes_urine')
            ],
            'trend_labels' => $trendLabels,
            'comp_labels'  => $compLabels,
            'trend' => [
                'kegiatan' => $chartKegiatan,
                'peserta'  => $chartPeserta,
                'positif'  => $chartPositif
            ],
            'comp' => [
                'anggaran' => [
                    ['name' => 'DIPA', 'data' => $barAnggaranDipa],
                    ['name' => 'NON DIPA', 'data' => $barAnggaranNon]
                ],
                'sasaran' => $sasaranSeries
            ]
        ]);
    }

    private function getTableConfig($type) {
        $defaultSasaran = ['lingkungan pendidikan', 'lingkungan pemerintah', 'lingkungan masyarakat', 'lingkungan swasta'];
        $urineSasaran = ['instansi pemerintah', 'lingkungan pendidikan', 'pekerja swasta', 'lingkungan masyarakat'];

        $map = [
            'informasi_edukasi' => [
                'table' => 'p2m_informasi_edukasi', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'jumlah_peserta', 
                'unit_label' => 'Peserta', 'has_anggaran' => true, 'col_anggaran' => 'anggaran_pelaksanaan', 
                'has_sasaran' => true, 'sasaran_list' => $defaultSasaran
            ],
            'tes_urine' => [
                'table' => 'p2m_tes_urine', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'jumlah_peserta', 
                'unit_label' => 'Peserta', 'has_anggaran' => true, 'col_anggaran' => 'anggaran_pelaksanaan', 
                'has_sasaran' => true, 'sasaran_list' => $urineSasaran
            ],
            'media_elektronik' => [
                'table' => 'p2m_elektronik', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'durasi_pelaksanaan', 
                'unit_label' => 'Hari', 'has_anggaran' => true, 'col_anggaran' => 'anggaran_pelaksanaan', 
                'has_sasaran' => false, 'sasaran_list' => []
            ],
            'media_non_elektronik' => [
                'table' => 'p2m_non_elektronik', 'date_col' => 'tanggal_mulai_pelaksanaan', 'val_col' => 'durasi_pelaksanaan', 
                'unit_label' => 'Hari', 'has_anggaran' => true, 'col_anggaran' => 'anggaran_pelaksanaan', 
                'has_sasaran' => false, 'sasaran_list' => []
            ],
            'media_online' => [
                'table' => 'p2m_online', 'date_col' => 'tanggal_mulai_pelaksanaan', 'val_col' => 'durasi_pelaksanaan', 
                'unit_label' => 'Hari', 'has_anggaran' => true, 'col_anggaran' => 'anggaran_pelaksanaan', 
                'has_sasaran' => false, 'sasaran_list' => []
            ],
            'desa_bersinar' => [
                'table' => 'p2m_desa_kelurahan_bersinar', 'date_col' => 'tanggal_pencanangan', 'val_col' => null, 
                'unit_label' => '-', 'has_anggaran' => true, 'col_anggaran' => 'anggaran_pembentukan', 
                'has_sasaran' => false, 'sasaran_list' => []
            ],
            'asistensi' => [
                'table' => 'p2m_asistensi_relawan', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'jumlah_peserta', 
                'unit_label' => 'Peserta', 'has_anggaran' => true, 'col_anggaran' => 'anggaran_pelaksanaan', 
                'has_sasaran' => true, 'sasaran_list' => $defaultSasaran
            ],
            'pelatihan' => [
                'table' => 'p2m_pelatihan', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'jumlah_peserta', 
                'unit_label' => 'Peserta', 'has_anggaran' => true, 'col_anggaran' => 'anggaran_pelaksanaan', 
                'has_sasaran' => true, 'sasaran_list' => $defaultSasaran
            ],
            'keluarga' => [
                'table' => 'p2m_keluarga', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'jumlah_peserta', 
                'unit_label' => 'Peserta', 'has_anggaran' => true, 'col_anggaran' => 'anggaran_pelaksanaan', 
                'has_sasaran' => true, 'sasaran_list' => $defaultSasaran
            ],
            'monev' => [
                'table' => 'p2m_monev', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'jumlah_peserta', 
                'unit_label' => 'Peserta', 'has_anggaran' => true, 'col_anggaran' => 'anggaran_pelaksanaan', 
                'has_sasaran' => true, 'sasaran_list' => $defaultSasaran
            ],
            'pemetaan' => [
                'table' => 'p2m_pemetaan_sdm_sda', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'jumlah_peserta', 
                'unit_label' => 'Peserta', 'has_anggaran' => true, 'col_anggaran' => 'anggaran_pelaksanaan', 
                'has_sasaran' => true, 'sasaran_list' => $defaultSasaran
            ],
            'ikan' => [
                'table' => 'p2m_ikan', 'date_col' => 'tanggal_pelaksanaan', 'val_col' => 'jumlah_peserta', 
                'unit_label' => 'Peserta', 'has_anggaran' => true, 'col_anggaran' => 'anggaran_pelaksanaan', 
                'has_sasaran' => true, 'sasaran_list' => $defaultSasaran
            ],
        ];

        return $map[$type] ?? $map['informasi_edukasi'];
    }
}