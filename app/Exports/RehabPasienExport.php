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

class RehabPasienExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithChunkReading
{
    protected $query;

    public function __construct($query) { $this->query = $query; }
    public function query() { return $this->query; }
    public function chunkSize(): int { return 1000; }

    public function headings(): array
    {
        return [
            'Satuan Kerja', 'No Rekam Medis', 'Nama Pasien', 'Jenis Kelamin', 
            'Tanggal Rehab', 'Usia Saat Ini', 'Pendidikan', 'Pekerjaan', 
            'Sumber Pasien', 'Jenis Narkotika'
        ];
    }

    public function map($row): array
    {
        // Ingat, $row adalah RehabRiwayat, bukan Pasien.
        $narkotikaList = $row->narkotika->pluck('nama_narkotika')->implode(', ');

        return [
            $row->pasien->satuanKerja->satuan_kerja ?? '-',
            $row->pasien->no_rekam_medis,
            $row->pasien->nama_pasien,
            $row->pasien->jenis_kelamin,
            $row->tanggal_rehab->format('d/m/Y'),
            $row->usia . ' Tahun',
            $row->pendidikan,
            $row->pekerjaan,
            $row->sumber_pasien,
            $narkotikaList
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A:J')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        return [ 1 => ['font' => ['bold' => true]] ];
    }
}