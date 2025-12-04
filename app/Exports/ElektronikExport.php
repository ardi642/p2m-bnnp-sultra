<?php

namespace App\Exports;

use App\Models\p2mElektronik;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ElektronikExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
            'Jenis Anggaran',
            'Jenis Media',
            'Nama Media',
            'Tanggal Pelaksanaan',
            'Durasi Pelaksanaan (Hari)',
            'Link Dokumentasi',
            'Dibuat Pada'
        ];
    }

    // Mapping Data per Baris
    public function map($row): array
    {
        
        return [
            $row->satuanKerja->satuan_kerja ?? '-',
            $row->anggaran_pelaksanaan,
            $row->Media,
            $row->nama_media,
            $row->tanggal_pelaksanaan->translatedFormat('d F Y'), // Format tanggal Indo
            $row->durasi_pelaksanaan,
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