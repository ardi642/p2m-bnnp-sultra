<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SafariReligiExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
            'Tempat Kegiatan',
            'Tanggal Pelaksanaan',
            'Nama Pegawai',
            'Jumlah Masyarakat Tersosialisasi',
            'Link Dokumentasi',
            'Dibuat Pada'
        ];
    }

    public function map($row): array
    {
        $pegawaiNames = $row->pegawai->pluck('nama')->implode(', ');

        return [
            $row->satuanKerja->satuan_kerja ?? '-',
            $row->tempat_kegiatan,
            $row->tanggal_pelaksanaan->translatedFormat('d F Y'),
            $pegawaiNames,
            $row->jumlah_masyarakat,
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