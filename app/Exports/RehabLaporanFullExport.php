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
use Carbon\Carbon;

class RehabLaporanFullExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithChunkReading
{
    protected $query;

    public function __construct($query)
    {
        // Menyimpan query builder yang dikirim dari Controller
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    // Efisiensi memori: Membaca database per 1000 baris
    public function chunkSize(): int
    {
        return 1000;
    }

    public function headings(): array
    {
        return [
            'Tanggal Laporan',
            'Satuan Kerja',
            'Realisasi Rawat Jalan',
            'Realisasi Pasca Rehab',
            'Realisasi SKHPN',
            'Dibuat Pada'
        ];
    }

    public function map($row): array
    {
        return [
            Carbon::parse($row->tanggal)->format('d/m/Y'),
            $row->satuanKerja->satuan_kerja ?? '-',
            $row->realisasi_rawat_jalan,
            $row->realisasi_pasca_rehab,
            $row->realisasi_skhpn,
            $row->created_at->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Align data ke atas, header jadi bold
        $sheet->getStyle('A:F')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        return [
            1 => ['font' => ['bold' => true]], 
        ];
    }
}