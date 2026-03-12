<?php

namespace App\Constants;

class KategoriPeranSertaMasyarakat
{
    public const KATEGORI = [
        'pembinaan_teknis'          => 'Pembinaan Teknis',
        'pemetaan_kelompok_sasaran' => 'Pemetaan Kelompok Sasaran',
        'pengembangan_kapasitas'    => 'Pengembangan Kapasitas & Pembinaan Masyarakat',
        'monitoring_evaluasi'       => 'Monitoring dan Evaluasi'
    ];

    public const KEGIATAN_MAP = [
        'pembinaan_teknis' => [
            'Rapat Kerja Teknis Program Pemberdayaan Masyarakat',
            'Rapat Kerja Teknis BNN Provinsi dan BNN Kabupaten/Kota dalam Upaya Sinkronisasi'
        ],
        'pemetaan_kelompok_sasaran' => [
            'Rapat Koordinasi Pemetaan Program Pemberdayaan Masyarakat',
            'Audiensi dengan Stakeholder dalam Rangka Pemetaan Program Pemberdayaan Masyarakat',
            'Rakor Pengembangan dan Pembinaan Kabupaten/Kota Tanggap Ancaman Narkoba'
        ],
        'pengembangan_kapasitas' => [
            'Bimbingan Teknis Penggiat P4GN',
            'Workshop Penggiat P4GN',
            'Workshop Tematik Penggiat P4GN',
            'Asistensi Kabupaten/Kota Tanggap Ancaman Narkoba',
            'Sinkronisasi Kabupaten/Kota Tanggap Ancaman Narkoba'
        ],
        'monitoring_evaluasi' => [
            'Monitoring Program Pemberdayaan Masyarakat',
            'Evaluasi Program Pemberdayaan Masyarakat',
            'Pengukuran Indeks Kemandirian Partisipasi',
            'Pengukuran Indeks Kabupaten/Kota Tanggap Ancaman Narkoba'
        ]
    ];

    /**
     * Helper untuk mengambil semua nama kegiatan menjadi array datar untuk kebutuhan validasi
     */
    public static function getAllKegiatan(): array
    {
        return collect(self::KEGIATAN_MAP)->flatten()->toArray();
    }
}