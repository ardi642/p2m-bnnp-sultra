<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ElektronikExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithChunkReading
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

    public function chunkSize(): int
    {
        return 1000;
    }

    public function headings(): array
    {
        return [
            'Satuan Kerja',
            'Anggaran',
            'Jenis Media',
            'Nama Media',
            'Tanggal Pelaksanaan',
            'Durasi (Hari)',
            'Dibuat Pada'
        ];
    }

    public function map($row): array
    {
        return [
            $row->satuanKerja->satuan_kerja ?? '-',
            $row->anggaran_pelaksanaan,
            ucwords($row->jenis_media),
            $row->nama_media,
            $row->tanggal_pelaksanaan->locale('id')->translatedFormat('d F Y'),
            $row->durasi_pelaksanaan . ' Hari',
            $row->created_at->locale('id')->translatedFormat('d F Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A:G')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        return [ 1 => ['font' => ['bold' => true]] ];
    }
}