<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MediaNonElektronikExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Satuan Kerja',
            'Anggaran',
            'Jenis Media',
            'Durasi (Hari)',
            'Tanggal Mulai',
            'Tempat Pemasangan',
            'Link Dokumentasi',
            'Dibuat Pada'
        ];
    }

    public function map($row): array
    {
        return [
            $row->satuanKerja->satuan_kerja ?? '-',
            $row->anggaran_pelaksanaan,
            $row->jenis_media,
            $row->durasi_pelaksanaan . ' Hari',
            $row->tanggal_pelaksanaan->translatedFormat('d F Y'),
            $row->tempat_kegiatan,
            $row->link_kelengkapan_dokumentasi,
            $row->created_at->translatedFormat('d F Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}