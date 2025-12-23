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
use App\Models\P2mNonElektronik;

class NonElektronikExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithChunkReading
{
    protected $query;
    protected $options;

    public function __construct($query)
    {
        $this->query = $query;
        $this->options = P2mNonElektronik::getJenisMediaOptions();
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
            'Keterangan Media',
            'Tempat Pemasangan',
            'Tanggal Mulai',
            'Durasi (Hari)',
            'Dibuat Pada'
        ];
    }

    public function map($row): array
    {
        $jenisLengkap = $this->options[$row->jenis_media] ?? $row->jenis_media;

        return [
            $row->satuanKerja->satuan_kerja ?? '-',
            $row->anggaran_pelaksanaan,
            $row->jenis_media,
            $jenisLengkap,
            $row->tempat_pemasangan,
            $row->tanggal_mulai_pelaksanaan->locale('id')->translatedFormat('d F Y'),
            $row->durasi_pelaksanaan . ' Hari',
            $row->created_at->locale('id')->translatedFormat('d F Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A:H')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        return [ 1 => ['font' => ['bold' => true]] ];
    }
}