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

    // API: Data Global Kartu Atas
    public function getGlobalData(Request $request) {
        $startYear = $request->input('start_year', date('Y'));
        $endYear   = $request->input('end_year', date('Y'));
        $satkerId  = $this->getSatkerId($request);
        return $this->getGlobalP2M($startYear, $endYear, $satkerId);
    }

    // =========================================================================
    // API CHART DATA
    // =========================================================================
    public function getChartData(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $satkerId = $this->getSatkerId($request);
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