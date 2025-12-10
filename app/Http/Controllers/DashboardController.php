<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\P2mSosialisasi;
use App\Models\P2mDesaBersinar;
use App\Models\P2mElektronik;
use App\Models\P2mKie;
use App\Models\P2mLingkungan;
use App\Models\P2mOnline;
use App\Models\P2mSafariReligi;
use App\Models\P2mTesUrine;
use App\Models\P2mUpacara;
use App\Models\P2mCfd;

class DashboardController extends Controller
{
    public function index(Request $request)
    {

        // mapping kolom tanggal tiap tabel
        $dateColumns = [
            'p2m_sosialisasi'   => 'tanggal_pelaksanaan',
            'p2m_upacara'       => 'tanggal_pelaksanaan',
            'p2m_desa_bersinar' => 'tanggal_pencanangan', // beda
            'p2m_elektronik'    => 'tanggal_pelaksanaan',
            'p2m_kie'           => 'tanggal_pelaksanaan',
            'p2m_lingkungan'    => 'tanggal_pelaksanaan',
            'p2m_online'        => 'tanggal_pelaksanaan',
            'p2m_safari_religi' => 'tanggal_pelaksanaan',
            'p2m_tes_urine'     => 'tanggal_pelaksanaan',
            'p2m_cfd'           => 'tanggal_pelaksanaan',
        ];

        // CARI TAHUN PALING AWAL & PALING AKHIR DARI SEMUA TABEL
        $years = [];

        foreach ($dateColumns as $table => $column) {
            $min = DB::table($table)->min(DB::raw("YEAR($column)"));
            $max = DB::table($table)->max(DB::raw("YEAR($column)"));

            if ($min) $years[] = $min;
            if ($max) $years[] = $max;
        }

        // jika database kosong, fallback ke tahun sekarang
        $minYear = $years ? min($years) : date('Y');
        $maxYear = $years ? max($years) : date('Y');

        // Ambil filter dari URL (jika ada)
        // $year = $request->has('year') ? $request->year : date('Y');
        $year = $request->year ?? null;
        $month = $request->month ?? null;

        $filter = function ($table, $column = null) use ($year, $month, $dateColumns) {

            $dateField = $dateColumns[$table];
            $query = DB::table($table);

            if ($year) {
                $query->whereYear($dateField, $year);
            }

            if ($month) {
                $query->whereMonth($dateField, $month);
            }

            return $column ? $query->sum($column) : $query->count();
        };



        $totalJenis = '11';

        // TOTAL KEGIATAN
        $totalKegiatan =
            $filter('p2m_sosialisasi') +
            $filter('p2m_desa_bersinar') +
            $filter('p2m_elektronik') +
            $filter('p2m_kie') +
            $filter('p2m_lingkungan') +
            $filter('p2m_online') +
            $filter('p2m_safari_religi') +
            $filter('p2m_cfd') +
            $filter('p2m_upacara') +
            $filter('p2m_tes_urine');

        // TOTAL PESERTA
        $totalPeserta =
            $filter('p2m_sosialisasi', 'jumlah_peserta') +
            $filter('p2m_upacara', 'jumlah_peserta') +
            $filter('p2m_desa_bersinar', 'jumlah_penggiat') +
            $filter('p2m_lingkungan', 'jumlah_penggiat') +
            $filter('p2m_safari_religi', 'jumlah_masyarakat') +
            $filter('p2m_tes_urine', 'jumlah_peserta');

        // CHART DATA
        $models = [
            P2mSosialisasi::class    => ['label' => 'Sosialisasi Tatap Muka'],
            P2mUpacara::class        => ['label' => 'Upacara'],
            P2mDesaBersinar::class   => ['label' => 'Desa Bersinar'],
            P2mElektronik::class     => ['label' => 'Elektronik'],
            P2mKie::class            => ['label' => 'KIE'],
            P2mLingkungan::class     => ['label' => 'Lingkungan'],
            P2mOnline::class         => ['label' => 'Online'],
            P2mSafariReligi::class   => ['label' => 'Safari Religi'],
            P2mTesUrine::class       => ['label' => 'Tes Urine'],
            P2mCfd::class            => ['label' => 'CFD'],
        ];

        $chartData = [];

        foreach ($models as $model => $info) {
            $table = (new $model)->getTable();
            $dateField = $dateColumns[$table];

            $query = $model::query();

            if ($year) {
                $query->whereYear($dateField, $year);
            }

            if ($month) {
                $query->whereMonth($dateField, $month);
            }


            $chartData[] = [
                'kegiatan' => $info['label'],
                'nilai'    => $query->count(),
            ];
        }

        return view('dashboard', compact('chartData', 'totalKegiatan', 'totalJenis', 'totalPeserta', 'year', 'month', 'minYear', 'maxYear'));
    }
}
