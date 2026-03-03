<?php

namespace App\Constants;

class KategoriPemberdayaan
{
    // Master Sub Kegiatan
    public const SUB_KEGIATAN = [
        'pembinaan' => 'Pembinaan Teknis bagi Satker',
        'pemetaan'  => 'Pemetaan Kawasan Rawan Narkoba',
        'kapasitas' => 'Pengembangan Kapasitas Masyarakat',
        'monev'     => 'Monitoring dan Evaluasi'
    ];

    // Master Detail Kegiatan (Mapping berdasarkan Sub Kegiatan)
    public const DETAIL_KEGIATAN_MAP = [
        'pembinaan' => [
            'pembinaan_teknis_satker' => 'Pembinaan Teknis bagi Satker'
        ],
        'pemetaan' => [
            'pemetaan_sdm_sda' => 'Pemetaan SDM dan SDA',
            'rapat_kerja'      => 'Rapat Kerja'
        ],
        'kapasitas' => [
            'bimtek_life_skill' => 'Bimbingan Teknis Life Skill',
            'pengukuran_skm'    => 'Pengukuran SKM'
        ],
        'monev' => [
            'monev_program'        => 'Monev Program Pemberdayaan Alternatif',
            'pengukuran_ikkrn'     => 'Pengukuran IKKRN',
            'pengukuran_kuesioner' => 'Pengukuran Kuesioner'
        ]
    ];

    /**
     * Mengambil seluruh Key Detail Kegiatan untuk kebutuhan validasi form
     */
    public static function getAllDetailKeys(): array
    {
        $keys = [];
        foreach (self::DETAIL_KEGIATAN_MAP as $details) {
            foreach ($details as $key => $label) {
                $keys[] = $key;
            }
        }
        return $keys;
    }

    /**
     * Mengambil seluruh pasang Key-Value Detail Kegiatan (untuk filter Index)
     */
    public static function getAllDetailLabels(): array
    {
        $labels = [];
        foreach (self::DETAIL_KEGIATAN_MAP as $details) {
            foreach ($details as $key => $label) {
                $labels[$key] = $label;
            }
        }
        return $labels;
    }
}