<?php

namespace App\Exports;

use App\Models\P2mKie;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KieExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $query;

    // Kita terima Query Builder yang sudah difilter dari Controller
    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    // Header Excel
    public function headings(): array
    {
        return [
            'Satuan Kerja',
            'Anggaran',
            'Tempat kegiatan',
            'Tanggal Pelaksanaan',
            'Pegawai',
            'Link Dokumentasi',
            'Dibuat Pada'
        ];
    }

    // Mapping Data per Baris
    public function map($row): array
    {
        // Ambil nama pegawai dan gabungkan dengan koma
        $pegawaiNames = $row->pegawai->pluck('nama')->implode(', ');

        return [
            $row->satuanKerja->satuan_kerja ?? '-',
            $row->anggaran_pelaksanaan,
            $row->tempat_kegiatan,
            $row->tanggal_pelaksanaan->translatedFormat('d F Y'), // Format tanggal Indo
            $pegawaiNames, // List pegawai
            $row->link_kelengkapan_dokumentasi,
            $row->created_at->translatedFormat('d F Y H:i'),
        ];
    }

    // Styling Header (Bold)
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}