<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\SatuanKerja;
use App\Constants\KategoriPeranSertaMasyarakat;
use App\Constants\KategoriPemberdayaan;

class DashboardP2MController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $minYear = (int) date('Y');
        $currentYear = (int) date('Y');

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
            'p2m_ikan'                    => 'tanggal_pelaksanaan',
            'p2m_rts'                     => 'tanggal_pelaksanaan',
            'p2m_peran_serta_masyarakat'  => 'tanggal_pelaksanaan',
            'p2m_pemberdayaan'            => 'tanggal_pelaksanaan',
        ];

        foreach ($tables as $table => $column) {
            $oldestDate = DB::table($table)->min($column);
            if ($oldestDate) {
                $year = (int) date('Y', strtotime($oldestDate));
                if ($year > 2000 && $year < $minYear) {
                    $minYear = $year;
                }
            }
        }

        $years = range($currentYear, $minYear);
        $showTabs = in_array(
            $user->role, 
            ['admin', 'admin_satker', 'operator_satker']
        );
        $satkers = ($user->role === 'admin') 
            ? SatuanKerja::orderBy('satuan_kerja', 'asc')->get() 
            : [];

        return view('dashboard.p2m.index', compact(
            'years', 'showTabs', 'satkers'
        ));
    }

    public function getGlobalData(Request $request) 
    {
        $year     = $request->input('year', date('Y'));
        $user     = Auth::user();
        $satkerId = ($user->role === 'admin') 
            ? $request->input('satker_id') 
            : $user->pegawai?->satuan_kerja_id;

        $count = function($table, $dateCol) use ($year, $satkerId) {
            $q = DB::table($table)->whereYear($dateCol, $year);
            if ($satkerId) $q->where('satuan_kerja_id', $satkerId);
            return $q->count();
        };

        $sum = function($table, $colSum, $dateCol) use ($year, $satkerId) {
            $q = DB::table($table)->whereYear($dateCol, $year);
            if ($satkerId) $q->where('satuan_kerja_id', $satkerId);
            return $q->sum($colSum);
        };

        $listOrang = [
            'Informasi Edukasi'   => $sum('p2m_informasi_edukasi', 'jumlah_peserta', 'tanggal_pelaksanaan'),
            'Asistensi Relawan'   => $sum('p2m_asistensi_relawan', 'jumlah_peserta', 'tanggal_pelaksanaan'),
            'Pelatihan'           => $sum('p2m_pelatihan', 'jumlah_peserta', 'tanggal_pelaksanaan'),
            'Ketahanan Keluarga'  => $sum('p2m_keluarga', 'jumlah_peserta', 'tanggal_pelaksanaan'),
            'IKAN'                => $sum('p2m_ikan', 'jumlah_peserta', 'tanggal_pelaksanaan'),
            'Remaja Teman Sebaya' => $sum('p2m_rts', 'jumlah_peserta', 'tanggal_pelaksanaan'),
        ];
        $totalOrang = array_sum($listOrang); 

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

        $psmCard = [];
        $rawPsm = DB::table('p2m_peran_serta_masyarakat')
            ->whereYear('tanggal_pelaksanaan', $year)
            ->when($satkerId, function($q) use ($satkerId) { 
                return $q->where('satuan_kerja_id', $satkerId); 
            })
            ->select(
                'kategori_kegiatan', 
                'nama_kegiatan', 
                DB::raw('COUNT(id) as total_kegiatan'),
                DB::raw('SUM(jumlah_peserta) as total_peserta')
            )
            ->groupBy('kategori_kegiatan', 'nama_kegiatan')
            ->get();

        $katPsmMap = KategoriPeranSertaMasyarakat::KATEGORI;

        foreach($rawPsm as $r) {
            $katLabel = $katPsmMap[$r->kategori_kegiatan] ?? ucwords(str_replace('_', ' ', $r->kategori_kegiatan));
            if(!isset($psmCard[$katLabel])) {
                $psmCard[$katLabel] = ['kegiatan' => 0, 'peserta' => 0, 'detail' => []];
            }

            $psmCard[$katLabel]['kegiatan'] += $r->total_kegiatan;
            $psmCard[$katLabel]['peserta'] += $r->total_peserta;
            $psmCard[$katLabel]['detail'][] = [
                'nama' => $r->nama_kegiatan, 
                'kegiatan' => (int)$r->total_kegiatan,
                'peserta' => (int)$r->total_peserta, 
                'is_tes_urine' => false
            ];
        }

        $tuCount   = $count('p2m_tes_urine', 'tanggal_pelaksanaan');
        $tuPeserta = $sum('p2m_tes_urine', 'jumlah_peserta', 'tanggal_pelaksanaan');
        $tuPositif = $sum('p2m_tes_urine', 'jumlah_positif', 'tanggal_pelaksanaan');
        
        if ($tuCount > 0) {
            $katLabel = $katPsmMap['pengembangan_kapasitas'] ?? 'Pengembangan Kapasitas & Pembinaan';
            if(!isset($psmCard[$katLabel])) {
                $psmCard[$katLabel] = ['kegiatan' => 0, 'peserta' => 0, 'detail' => []];
            }

            $psmCard[$katLabel]['kegiatan'] += $tuCount;
            $psmCard[$katLabel]['peserta'] += $tuPeserta;
            $psmCard[$katLabel]['detail'][] = [
                'nama' => 'Deteksi Dini Tes Urine Uji Narkoba',
                'kegiatan' => $tuCount,
                'peserta' => $tuPeserta, 
                'positif' => $tuPositif, 
                'is_tes_urine' => true
            ];
        }

        $paCard = [];
        $rawPa = DB::table('p2m_pemberdayaan')
            ->whereYear('tanggal_pelaksanaan', $year)
            ->when($satkerId, function($q) use ($satkerId) { 
                return $q->where('satuan_kerja_id', $satkerId); 
            })
            ->select(
                'sub_kegiatan', 
                'detail_kegiatan', 
                DB::raw('COUNT(id) as total_kegiatan'),
                DB::raw('SUM(jumlah_peserta) as total_peserta')
            )
            ->groupBy('sub_kegiatan', 'detail_kegiatan')
            ->get();

        $subPaMap = KategoriPemberdayaan::SUB_KEGIATAN;
        $detPaMap = KategoriPemberdayaan::getAllDetailLabels();

        foreach($rawPa as $r) {
            $subLabel = $subPaMap[$r->sub_kegiatan] ?? ucwords(str_replace('_', ' ', $r->sub_kegiatan));
            $detLabel = $detPaMap[$r->detail_kegiatan] ?? ucwords(str_replace('_', ' ', $r->detail_kegiatan));

            if(!isset($paCard[$subLabel])) {
                $paCard[$subLabel] = ['kegiatan' => 0, 'peserta' => 0, 'detail' => []];
            }

            $paCard[$subLabel]['kegiatan'] += $r->total_kegiatan;
            $paCard[$subLabel]['peserta'] += $r->total_peserta;
            $paCard[$subLabel]['detail'][] = [
                'nama' => $detLabel, 
                'kegiatan' => (int)$r->total_kegiatan,
                'peserta' => (int)$r->total_peserta
            ];
        }

        foreach($psmCard as $k => $v) { 
            usort($psmCard[$k]['detail'], function($a, $b) { return $b['peserta'] <=> $a['peserta']; }); 
        }
        foreach($paCard as $k => $v)  { 
            usort($paCard[$k]['detail'], function($a, $b) { return $b['peserta'] <=> $a['peserta']; }); 
        }

        $allActivities = [
            'Informasi & Edukasi'      => $count('p2m_informasi_edukasi', 'tanggal_pelaksanaan'),
            'Media Elektronik'         => $listMedia['Elektronik']['freq'],
            'Media Non-Elektronik'     => $listMedia['Non-Elektronik']['freq'],
            'Media Online'             => $listMedia['Online']['freq'],
            'Desa Bersinar'            => $listWilayah['Desa/Kel. Bersinar'],
            'Asistensi Relawan'        => $count('p2m_asistensi_relawan', 'tanggal_pelaksanaan'),
            'Pelatihan Soft Skill'     => $count('p2m_pelatihan', 'tanggal_pelaksanaan'),
            'Ketahanan Keluarga'       => $count('p2m_keluarga', 'tanggal_pelaksanaan'),
            'IKAN'                     => $count('p2m_ikan', 'tanggal_pelaksanaan'),
            'Remaja Teman Sebaya'      => $count('p2m_rts', 'tanggal_pelaksanaan'),
            'Peran Serta Masyarakat'   => $count('p2m_peran_serta_masyarakat', 'tanggal_pelaksanaan') + $tuCount,
            'Pemberdayaan Alternatif'  => $count('p2m_pemberdayaan', 'tanggal_pelaksanaan'),
        ];
        arsort($allActivities);

        return response()->json([
            'orang'    => ['total' => $totalOrang, 'list' => $listOrang],
            'media'    => ['total_freq' => $totalMediaFreq, 'total_durasi' => $totalMediaDurasi, 'list' => $listMedia],
            'wilayah'  => ['total' => $totalWilayah, 'list' => $listWilayah],
            'kegiatan' => ['total' => array_sum($allActivities)],
            'psm_card' => $psmCard,
            'pa_card'  => $paCard,
            'ranking_chart' => [
                'labels' => array_keys($allActivities), 
                'data' => array_values($allActivities)
            ]
        ]);
    }

    public function getChartData(Request $request) 
    {
        $type  = $request->input('type', 'informasi_edukasi'); 
        $year  = $request->input('year', date('Y'));
        $month = $request->input('month', 'all'); 
        
        $user = Auth::user();
        $isAdmin = ($user->role === 'admin');
        
        $selectedSatker = $request->input('satker_id');
        $mySatker = $isAdmin ? $selectedSatker : $user->pegawai?->satuan_kerja_id;
        $isMultiSatker = ($isAdmin && empty($selectedSatker));

        $config  = $this->getTableConfig($type); 
        $table   = $config['table'];
        $dateCol = $config['date_col'];
        $valCol  = $config['val_col'];
        
        $hasAnggaran = $config['has_anggaran'] ?? false;
        $hasSasaran  = $config['has_sasaran'] ?? false;
        $hasKategori = $config['has_kategori'] ?? false;
        $hasSubGiat  = $config['has_sub_kegiatan'] ?? false;

        $applyCompTime = function($q) use ($year, $month, $dateCol) {
            $q->whereYear($dateCol, $year);
            if ($month !== 'all' && $month !== 'per_bulan') {
                $q->whereMonth($dateCol, $month);
            }
            return $q;
        };

        // ====================================================================
        // 1. DATA TREN GRAFIK UTAMA
        // ====================================================================
        $chartKegiatan = []; 
        $chartPeserta = [];
        $trendLabels = [];

        if ($month === 'per_bulan') {
            $trendLabels = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            $timePoints  = range(1, 12);

            if ($isMultiSatker) {
                $satkers = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
                foreach ($satkers as $satker) {
                    $dataGiat = []; $dataPeserta = []; 
                    foreach ($timePoints as $timeVal) {
                        $q = DB::table($table)->where('satuan_kerja_id', $satker->id)->whereYear($dateCol, $year)->whereMonth($dateCol, $timeVal);
                        $dataGiat[] = $q->count();
                        $dataPeserta[] = $valCol ? (clone $q)->sum($valCol) : 0;
                        
                        if ($type === 'peran_serta_masyarakat') {
                            $qUrine = DB::table('p2m_tes_urine')->where('satuan_kerja_id', $satker->id)->whereYear('tanggal_pelaksanaan', $year)->whereMonth('tanggal_pelaksanaan', $timeVal);
                            $dataGiat[count($dataGiat)-1] += $qUrine->count();
                            $dataPeserta[count($dataPeserta)-1] += $qUrine->sum('jumlah_peserta');
                        }
                    }
                    $chartKegiatan[] = ['name' => $satker->satuan_kerja, 'data' => $dataGiat];
                    $chartPeserta[]  = ['name' => $satker->satuan_kerja, 'data' => $dataPeserta];
                }
            } else {
                $dataGiat = []; $dataPeserta = []; 
                foreach ($timePoints as $timeVal) {
                    $q = DB::table($table)->whereYear($dateCol, $year)->whereMonth($dateCol, $timeVal);
                    if ($mySatker) $q->where('satuan_kerja_id', $mySatker);
                    $dataGiat[] = $q->count();
                    $dataPeserta[] = $valCol ? (clone $q)->sum($valCol) : 0;
                    
                    if ($type === 'peran_serta_masyarakat') {
                        $qUrine = DB::table('p2m_tes_urine')->whereYear('tanggal_pelaksanaan', $year)->whereMonth('tanggal_pelaksanaan', $timeVal);
                        if ($mySatker) $qUrine->where('satuan_kerja_id', $mySatker);
                        $dataGiat[count($dataGiat)-1] += $qUrine->count();
                        $dataPeserta[count($dataPeserta)-1] += $qUrine->sum('jumlah_peserta');
                    }
                }
                $chartKegiatan[] = ['name' => 'Jumlah Kegiatan', 'data' => $dataGiat];
                $chartPeserta[]  = ['name' => 'Jumlah ' . $config['unit_label'], 'data' => $dataPeserta];
            }
        } else {
            $monthNames = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            $labelTime = $month === 'all' ? 'Total Akumulasi' : 'Bulan ' . $monthNames[(int)$month];
            
            if ($isMultiSatker) {
                $satkers = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
                $trendLabels = $satkers->pluck('satuan_kerja')->toArray();
                
                $dataGiat = []; $dataPeserta = [];
                foreach ($satkers as $satker) {
                    $q = DB::table($table)->where('satuan_kerja_id', $satker->id)->whereYear($dateCol, $year);
                    if ($month !== 'all') $q->whereMonth($dateCol, $month);
                    
                    $dataGiat[] = $q->count();
                    $pesertaCount = $valCol ? (clone $q)->sum($valCol) : 0;
                    
                    if ($type === 'peran_serta_masyarakat') {
                        $qUrine = DB::table('p2m_tes_urine')->where('satuan_kerja_id', $satker->id)->whereYear('tanggal_pelaksanaan', $year);
                        if ($month !== 'all') $qUrine->whereMonth('tanggal_pelaksanaan', $month);
                        
                        $dataGiat[count($dataGiat)-1] += $qUrine->count();
                        $pesertaCount += $qUrine->sum('jumlah_peserta');
                    }
                    $dataPeserta[] = $pesertaCount;
                }
                $chartKegiatan[] = ['name' => 'Total Kegiatan', 'data' => $dataGiat];
                $chartPeserta[]  = ['name' => 'Jumlah ' . $config['unit_label'], 'data' => $dataPeserta];
            } else {
                $trendLabels = [$labelTime];
                $q = DB::table($table)->whereYear($dateCol, $year);
                if ($mySatker) $q->where('satuan_kerja_id', $mySatker);
                if ($month !== 'all') $q->whereMonth($dateCol, $month);
                
                $giat = $q->count();
                $peserta = $valCol ? (clone $q)->sum($valCol) : 0;
                
                if ($type === 'peran_serta_masyarakat') {
                    $qUrine = DB::table('p2m_tes_urine')->whereYear('tanggal_pelaksanaan', $year);
                    if ($mySatker) $qUrine->where('satuan_kerja_id', $mySatker);
                    if ($month !== 'all') $qUrine->whereMonth('tanggal_pelaksanaan', $month);
                    
                    $giat += $qUrine->count();
                    $peserta += $qUrine->sum('jumlah_peserta');
                }
                $chartKegiatan[] = ['name' => 'Total Kegiatan', 'data' => [$giat]];
                $chartPeserta[]  = ['name' => 'Jumlah ' . $config['unit_label'], 'data' => [$peserta]];
            }
        }

        // ====================================================================
        // 2. DATA PROPORSI KINERJA (Dimensi yang dioptimasi untuk Filter Sekunder)
        // ====================================================================
        
        // Ambil SEMUA data dalam 1 atau 2 query untuk diolah di memori. Jauh lebih cepat!
        $qAll = DB::table($table)->whereYear($dateCol, $year);
        if ($month !== 'all' && $month !== 'per_bulan') $qAll->whereMonth($dateCol, $month);
        if (!$isMultiSatker && $mySatker) $qAll->where('satuan_kerja_id', $mySatker);
        $allRecords = $qAll->get();

        $urineRecords = collect();
        if ($type === 'peran_serta_masyarakat' || $hasKategori) {
             $qu = DB::table('p2m_tes_urine')->whereYear('tanggal_pelaksanaan', $year);
             if ($month !== 'all' && $month !== 'per_bulan') $qu->whereMonth('tanggal_pelaksanaan', $month);
             if (!$isMultiSatker && $mySatker) $qu->where('satuan_kerja_id', $mySatker);
             $urineRecords = $qu->get();
        }

        $compResult = [
            'anggaran'     => ['options' => ['DIPA', 'NON DIPA'], 'aggregated' => [], 'detailed' => []],
            'sasaran'      => ['options' => $config['sasaran_list'] ?? [], 'aggregated' => [], 'detailed' => []],
            'kategori'     => ['options' => $config['kategori_list'] ?? [], 'aggregated' => [], 'detailed' => []],
            'sub_kegiatan' => ['options' => $config['sub_kegiatan_list'] ?? [], 'aggregated' => [], 'detailed' => []],
        ];

        $satkerMap = SatuanKerja::pluck('satuan_kerja', 'id')->toArray();
        $mySatkerName = $mySatker ? ($satkerMap[$mySatker] ?? 'Satuan Kerja') : 'Satuan Kerja';

        if ($month === 'per_bulan') {
            $compLabels = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            
            // Inisialisasi array 0
            foreach (['anggaran', 'sasaran', 'kategori', 'sub_kegiatan'] as $catKey) {
                foreach ($compResult[$catKey]['options'] as $opt) {
                    $compResult[$catKey]['aggregated'][$opt] = array_fill(0, 12, 0);
                    if ($isMultiSatker) {
                        foreach ($satkerMap as $sId => $sName) {
                            $compResult[$catKey]['detailed'][$opt][$sName] = array_fill(0, 12, 0);
                        }
                    }
                }
            }

            foreach ($allRecords as $r) {
                $mIndex = (int)date('n', strtotime($r->$dateCol)) - 1;
                $sName = $satkerMap[$r->satuan_kerja_id] ?? 'Unknown';

                if ($hasAnggaran) {
                    $val = $r->{$config['col_anggaran']};
                    if (in_array($val, $compResult['anggaran']['options'])) {
                        $compResult['anggaran']['aggregated'][$val][$mIndex]++;
                        if ($isMultiSatker) $compResult['anggaran']['detailed'][$val][$sName][$mIndex]++;
                    }
                }
                if ($hasSasaran) {
                    $val = $r->sasaran_kegiatan;
                    if (in_array($val, $compResult['sasaran']['options'])) {
                        $compResult['sasaran']['aggregated'][$val][$mIndex]++;
                        if ($isMultiSatker) $compResult['sasaran']['detailed'][$val][$sName][$mIndex]++;
                    }
                }
                if ($hasKategori) {
                    $val = $r->kategori_kegiatan;
                    if (in_array($val, $compResult['kategori']['options'])) {
                        $compResult['kategori']['aggregated'][$val][$mIndex]++;
                        if ($isMultiSatker) $compResult['kategori']['detailed'][$val][$sName][$mIndex]++;
                    }
                }
                if ($hasSubGiat) {
                    $val = $r->sub_kegiatan;
                    if (in_array($val, $compResult['sub_kegiatan']['options'])) {
                        $compResult['sub_kegiatan']['aggregated'][$val][$mIndex]++;
                        if ($isMultiSatker) $compResult['sub_kegiatan']['detailed'][$val][$sName][$mIndex]++;
                    }
                }
            }

            if ($hasKategori) {
                foreach ($urineRecords as $ur) {
                    $mIndex = (int)date('n', strtotime($ur->tanggal_pelaksanaan)) - 1;
                    $sName = $satkerMap[$ur->satuan_kerja_id] ?? 'Unknown';
                    $val = 'pengembangan_kapasitas';
                    if (in_array($val, $compResult['kategori']['options'])) {
                        $compResult['kategori']['aggregated'][$val][$mIndex]++;
                        if ($isMultiSatker) $compResult['kategori']['detailed'][$val][$sName][$mIndex]++;
                    }
                }
            }

        } else {
            $compLabels = $isMultiSatker ? array_values(SatuanKerja::orderBy('satuan_kerja', 'asc')->pluck('satuan_kerja')->toArray()) : [$mySatkerName];
            $labelIndexes = array_flip($compLabels);

            foreach (['anggaran', 'sasaran', 'kategori', 'sub_kegiatan'] as $catKey) {
                foreach ($compResult[$catKey]['options'] as $opt) {
                    $compResult[$catKey]['aggregated'][$opt] = array_fill(0, count($compLabels), 0);
                }
            }

            foreach ($allRecords as $r) {
                $sName = $isMultiSatker ? ($satkerMap[$r->satuan_kerja_id] ?? null) : $mySatkerName;
                if (!$sName || !isset($labelIndexes[$sName])) continue;
                $sIndex = $labelIndexes[$sName];

                if ($hasAnggaran) {
                    $val = $r->{$config['col_anggaran']};
                    if (isset($compResult['anggaran']['aggregated'][$val])) {
                        $compResult['anggaran']['aggregated'][$val][$sIndex]++;
                    }
                }
                if ($hasSasaran) {
                    $val = $r->sasaran_kegiatan;
                    if (isset($compResult['sasaran']['aggregated'][$val])) {
                        $compResult['sasaran']['aggregated'][$val][$sIndex]++;
                    }
                }
                if ($hasKategori) {
                    $val = $r->kategori_kegiatan;
                    if (isset($compResult['kategori']['aggregated'][$val])) {
                        $compResult['kategori']['aggregated'][$val][$sIndex]++;
                    }
                }
                if ($hasSubGiat) {
                    $val = $r->sub_kegiatan;
                    if (isset($compResult['sub_kegiatan']['aggregated'][$val])) {
                        $compResult['sub_kegiatan']['aggregated'][$val][$sIndex]++;
                    }
                }
            }

            if ($hasKategori) {
                foreach ($urineRecords as $ur) {
                    $sName = $isMultiSatker ? ($satkerMap[$ur->satuan_kerja_id] ?? null) : $mySatkerName;
                    if (!$sName || !isset($labelIndexes[$sName])) continue;
                    $sIndex = $labelIndexes[$sName];
                    $val = 'pengembangan_kapasitas';
                    if (isset($compResult['kategori']['aggregated'][$val])) {
                        $compResult['kategori']['aggregated'][$val][$sIndex]++;
                    }
                }
            }
        }

        // Format data menjadi struktur Series untuk ApexCharts
        $formattedComp = [];
        foreach (['anggaran', 'sasaran', 'kategori', 'sub_kegiatan'] as $catKey) {
            $formattedComp[$catKey] = [
                'options' => [],
                'aggregated' => [],
                'detailed' => []
            ];

            $displayMap = [];
            if ($catKey === 'sasaran') {
                foreach ($compResult[$catKey]['options'] as $o) $displayMap[$o] = ucwords(str_replace('lingkungan ', '', $o));
            } elseif ($catKey === 'kategori') {
                $mapK = KategoriPeranSertaMasyarakat::KATEGORI;
                foreach ($compResult[$catKey]['options'] as $o) $displayMap[$o] = $mapK[$o] ?? ucwords(str_replace('_', ' ', $o));
            } elseif ($catKey === 'sub_kegiatan') {
                $mapS = KategoriPemberdayaan::SUB_KEGIATAN;
                foreach ($compResult[$catKey]['options'] as $o) $displayMap[$o] = $mapS[$o] ?? ucwords(str_replace('_', ' ', $o));
            } else {
                foreach ($compResult[$catKey]['options'] as $o) $displayMap[$o] = $o;
            }

            foreach ($compResult[$catKey]['options'] as $opt) {
                $formattedComp[$catKey]['options'][] = ['id' => $opt, 'label' => $displayMap[$opt]];
                $formattedComp[$catKey]['aggregated'][] = [
                    'name' => $displayMap[$opt],
                    'data' => $compResult[$catKey]['aggregated'][$opt]
                ];

                if ($month === 'per_bulan' && $isMultiSatker) {
                    $detailedSeries = [];
                    foreach ($compResult[$catKey]['detailed'][$opt] as $sName => $dataArr) {
                        $detailedSeries[] = [
                            'name' => $sName,
                            'data' => $dataArr
                        ];
                    }
                    $formattedComp[$catKey]['detailed'][$opt] = $detailedSeries;
                }
            }
        }

        // ====================================================================
        // 3. DATA RINCIAN SPESIFIK & DRILL DOWN (DENGAN NAMA SATKER)
        // ====================================================================
        $tableData = [];

        if ($type === 'peran_serta_masyarakat') {
            $qPsm = DB::table('p2m_peran_serta_masyarakat')
                ->leftJoin('satuan_kerja', 'p2m_peran_serta_masyarakat.satuan_kerja_id', '=', 'satuan_kerja.id')
                ->select(
                    'satuan_kerja.satuan_kerja as nama_satker',
                    'kategori_kegiatan', 
                    'nama_kegiatan', 
                    DB::raw('COUNT(p2m_peran_serta_masyarakat.id) as frekuensi'), 
                    DB::raw('SUM(jumlah_peserta) as peserta')
                )
                ->groupBy('satuan_kerja.satuan_kerja', 'kategori_kegiatan', 'nama_kegiatan');
            
            if ($mySatker) $qPsm->where('p2m_peran_serta_masyarakat.satuan_kerja_id', $mySatker);
            $qPsm = $applyCompTime($qPsm)->get();

            $mapKat = KategoriPeranSertaMasyarakat::KATEGORI;
            foreach ($qPsm as $r) {
                $tableData[] = [
                    'satker'    => $r->nama_satker ?? 'Tidak Diketahui',
                    'kategori'  => $mapKat[$r->kategori_kegiatan] ?? ucwords(str_replace('_', ' ', $r->kategori_kegiatan)),
                    'nama'      => $r->nama_kegiatan,
                    'frekuensi' => (int)$r->frekuensi,
                    'peserta'   => (int)$r->peserta,
                    'positif'   => 0
                ];
            }

            $qUrine = DB::table('p2m_tes_urine')
                ->leftJoin('satuan_kerja', 'p2m_tes_urine.satuan_kerja_id', '=', 'satuan_kerja.id')
                ->select(
                    'satuan_kerja.satuan_kerja as nama_satker',
                    DB::raw('COUNT(p2m_tes_urine.id) as frekuensi'),
                    DB::raw('SUM(jumlah_peserta) as peserta'),
                    DB::raw('SUM(jumlah_positif) as positif')
                )
                ->groupBy('satuan_kerja.satuan_kerja');
                
            if ($mySatker) $qUrine->where('p2m_tes_urine.satuan_kerja_id', $mySatker);
            $qUrine = $applyCompTime($qUrine)->get();
            
            foreach($qUrine as $tu) {
                $tableData[] = [
                    'satker'    => $tu->nama_satker ?? 'Tidak Diketahui',
                    'kategori'  => $mapKat['pengembangan_kapasitas'] ?? 'Pengembangan Kapasitas & Pembinaan',
                    'nama'      => 'Deteksi Dini Tes Urine Uji Narkoba',
                    'frekuensi' => (int)$tu->frekuensi,
                    'peserta'   => (int)$tu->peserta,
                    'positif'   => (int)$tu->positif
                ];
            }

        } elseif ($type === 'pemberdayaan') {
            $qPa = DB::table('p2m_pemberdayaan')
                ->leftJoin('satuan_kerja', 'p2m_pemberdayaan.satuan_kerja_id', '=', 'satuan_kerja.id')
                ->select(
                    'satuan_kerja.satuan_kerja as nama_satker',
                    'sub_kegiatan', 
                    'detail_kegiatan', 
                    DB::raw('COUNT(p2m_pemberdayaan.id) as frekuensi'), 
                    DB::raw('SUM(jumlah_peserta) as peserta')
                )
                ->groupBy('satuan_kerja.satuan_kerja', 'sub_kegiatan', 'detail_kegiatan');
            
            if ($mySatker) $qPa->where('p2m_pemberdayaan.satuan_kerja_id', $mySatker);
            $qPa = $applyCompTime($qPa)->get();

            $mapSub = KategoriPemberdayaan::SUB_KEGIATAN;
            $mapDet = KategoriPemberdayaan::getAllDetailLabels();
            foreach ($qPa as $r) {
                $tableData[] = [
                    'satker'    => $r->nama_satker ?? 'Tidak Diketahui',
                    'kategori'  => $mapSub[$r->sub_kegiatan] ?? ucwords(str_replace('_', ' ', $r->sub_kegiatan)),
                    'nama'      => $mapDet[$r->detail_kegiatan] ?? ucwords(str_replace('_', ' ', $r->detail_kegiatan)),
                    'frekuensi' => (int)$r->frekuensi,
                    'peserta'   => (int)$r->peserta,
                    'positif'   => 0
                ];
            }
        }

        usort($tableData, function($a, $b) {
            if ($a['kategori'] === $b['kategori']) {
                return $b['peserta'] <=> $a['peserta'];
            }
            return strcmp($a['kategori'], $b['kategori']);
        });

        return response()->json([
            'is_multi_satker' => $isMultiSatker,
            'config' => [
                'unit' => $config['unit_label'], 
                'has_anggaran' => $hasAnggaran, 'has_sasaran' => $hasSasaran, 
                'has_kategori' => $hasKategori, 'has_sub_kegiatan' => $hasSubGiat
            ],
            'trend_labels' => $trendLabels,
            'comp_labels'  => $compLabels,
            'trend' => ['kegiatan' => $chartKegiatan, 'peserta' => $chartPeserta],
            'comp'  => $formattedComp, // Struktur baru yang kaya dimensi
            'detail_table' => $tableData
        ]);
    }

    private function getTableConfig($type) {
        $defaultSasaran = ['lingkungan pendidikan', 'lingkungan pemerintah', 'lingkungan masyarakat', 'lingkungan swasta'];
        
        $map = [
            'informasi_edukasi' => [
                'table' => 'p2m_informasi_edukasi', 'date_col' => 'tanggal_pelaksanaan', 
                'val_col' => 'jumlah_peserta', 'unit_label' => 'Peserta', 
                'has_anggaran' => true, 'col_anggaran' => 'anggaran_pelaksanaan', 'has_sasaran' => true, 'sasaran_list' => $defaultSasaran
            ],
            'media_elektronik' => [
                'table' => 'p2m_elektronik', 'date_col' => 'tanggal_pelaksanaan', 
                'val_col' => 'durasi_pelaksanaan', 'unit_label' => 'Hari', 
                'has_anggaran' => true, 'col_anggaran' => 'anggaran_pelaksanaan'
            ],
            'media_non_elektronik' => [
                'table' => 'p2m_non_elektronik', 'date_col' => 'tanggal_mulai_pelaksanaan', 
                'val_col' => 'durasi_pelaksanaan', 'unit_label' => 'Hari', 
                'has_anggaran' => true, 'col_anggaran' => 'anggaran_pelaksanaan'
            ],
            'media_online' => [
                'table' => 'p2m_online', 'date_col' => 'tanggal_mulai_pelaksanaan', 
                'val_col' => 'durasi_pelaksanaan', 'unit_label' => 'Hari', 
                'has_anggaran' => true, 'col_anggaran' => 'anggaran_pelaksanaan'
            ],
            'desa_bersinar' => [
                'table' => 'p2m_desa_kelurahan_bersinar', 'date_col' => 'tanggal_pencanangan', 
                'val_col' => null, 'unit_label' => '-', 
                'has_anggaran' => true, 'col_anggaran' => 'anggaran_pembentukan'
            ],
            'asistensi' => [
                'table' => 'p2m_asistensi_relawan', 'date_col' => 'tanggal_pelaksanaan', 
                'val_col' => 'jumlah_peserta', 'unit_label' => 'Peserta', 
                'has_anggaran' => true, 'col_anggaran' => 'anggaran_pelaksanaan', 'has_sasaran' => true, 'sasaran_list' => $defaultSasaran
            ],
            'pelatihan' => [
                'table' => 'p2m_pelatihan', 'date_col' => 'tanggal_pelaksanaan', 
                'val_col' => 'jumlah_peserta', 'unit_label' => 'Peserta', 
                'has_anggaran' => true, 'col_anggaran' => 'anggaran_pelaksanaan', 'has_sasaran' => true, 'sasaran_list' => $defaultSasaran
            ],
            'keluarga' => [
                'table' => 'p2m_keluarga', 'date_col' => 'tanggal_pelaksanaan', 
                'val_col' => 'jumlah_peserta', 'unit_label' => 'Peserta', 
                'has_anggaran' => true, 'col_anggaran' => 'anggaran_pelaksanaan', 'has_sasaran' => true, 'sasaran_list' => $defaultSasaran
            ],
            'ikan' => [
                'table' => 'p2m_ikan', 'date_col' => 'tanggal_pelaksanaan', 
                'val_col' => 'jumlah_peserta', 'unit_label' => 'Peserta', 
                'has_anggaran' => true, 'col_anggaran' => 'anggaran_pelaksanaan', 'has_sasaran' => true, 'sasaran_list' => $defaultSasaran
            ],
            'rts' => [
                'table' => 'p2m_rts', 'date_col' => 'tanggal_pelaksanaan', 
                'val_col' => 'jumlah_peserta', 'unit_label' => 'Peserta', 
                'has_anggaran' => true, 'col_anggaran' => 'anggaran_pelaksanaan', 'has_sasaran' => true, 'sasaran_list' => $defaultSasaran
            ],
            'peran_serta_masyarakat' => [
                'table' => 'p2m_peran_serta_masyarakat', 'date_col' => 'tanggal_pelaksanaan', 
                'val_col' => 'jumlah_peserta', 'unit_label' => 'Peserta', 
                'has_anggaran' => true, 'col_anggaran' => 'anggaran_pelaksanaan', 
                'has_kategori' => true, 'kategori_list' => array_keys(KategoriPeranSertaMasyarakat::KATEGORI)
            ],
            'pemberdayaan' => [
                'table' => 'p2m_pemberdayaan', 'date_col' => 'tanggal_pelaksanaan', 
                'val_col' => 'jumlah_peserta', 'unit_label' => 'Peserta', 
                'has_anggaran' => true, 'col_anggaran' => 'anggaran_pelaksanaan', 'has_sasaran' => true, 'sasaran_list' => $defaultSasaran,
                'has_sub_kegiatan' => true, 'sub_kegiatan_list' => array_keys(KategoriPemberdayaan::SUB_KEGIATAN)
            ],
        ];

        return $map[$type] ?? $map['informasi_edukasi'];
    }
}